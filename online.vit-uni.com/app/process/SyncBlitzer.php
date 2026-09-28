<?php
namespace app\process;

use support\Log;
use Workerman\Timer;
use support\think\Db;

class SyncBlitzer
{
    public function onWorkerStart()
    {
        
        
        // 每 15 分钟执行一次
        Timer::add(120, [$this, 'run']);

        // 启动时立刻执行一次
        $this->run();
    }

    public function run()
    {
        $pidFile = runtime_path() . '/blitzer_sync.pid';

        if (file_exists($pidFile)) {
            return;
        }

        file_put_contents($pidFile, getmypid());

        try {
            Log::info('Blitzer 下载开始');
            $key = config('app.export_key');
            $url = 'http://direct.blitzer.de/export/v3/json/?key='.$key;
            $tmpFile = runtime_path() . '/blitzer_full.json';

            $this->downloadJson($url, $tmpFile);

            if (!$this->isValidJson($tmpFile)) {
                throw new \Exception('JSON 不完整');
            }

            Log::info('Blitzer 下载完成，文件已就绪：' . $tmpFile);
        } catch (\Throwable $e) {
            Log::error('Blitzer 下载失败：' . $e->getMessage());
            @unlink($tmpFile);
        } finally {
            @unlink($pidFile);
        }
    }

    /**
     * 仅入库（假设 blitzer_full.json 已经由 jsonStart 下载完成）
     * - 检查文件是否存在且完整
     * - 创建临时表 blitzer_new，解析并批量写入
     * - 原子切换表，删除旧表
     *
     * @return array ['status' => 'success'|'failed', 'message' => string]
     */
    public function insertFromDownloadedFile(): array
    {
        $pidFile = runtime_path() . '/blitzer_insert.pid';

        if (file_exists($pidFile)) {
            return ['status' => 'skipped', 'message' => 'JSON 文件下载中'];
        }

        $tmpFile = runtime_path() . '/blitzer_full.json';
        if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
            return ['status' => 'failed', 'message' => 'JSON 文件不存在或为空，请先调用 jsonStart 下载'];
        }

        //file_put_contents($pidFile, getmypid());

        try {
            Log::info('Blitzer 入库开始：' . $tmpFile);

            if (!$this->isValidJson($tmpFile)) {
                throw new \RuntimeException('JSON 文件校验失败，请重新下载');
            }

            $startTime = microtime(true);

            Db::execute("DROP TABLE IF EXISTS blitzer_new");
            Db::execute("CREATE TABLE blitzer_new LIKE wa_device_event");

            $stats = $this->parseJson($tmpFile);

            Db::execute("
                RENAME TABLE wa_device_event TO blitzer_old,
                             blitzer_new TO wa_device_event
            ");
            Db::execute("DROP TABLE IF EXISTS blitzer_old");

            $elapsed = round(microtime(true) - $startTime, 2);
            Log::info(sprintf(
                'Blitzer 入库完成：共 %d 条，耗时 %.2fs',
                $stats['count'] ?? 0,
                $elapsed
            ));

            @unlink($tmpFile);

            return [
                'status'  => 'success',
                'message' => sprintf(
                    '入库完成：%d 条，耗时 %.2fs',
                    $stats['count'] ?? 0,
                    $elapsed
                ),
                'count'   => $stats['count'] ?? 0,
                'elapsed' => $elapsed,
            ];
        } catch (\Throwable $e) {
            Log::error('Blitzer 入库失败：' . $e->getMessage());
            try {
                Db::execute("DROP TABLE IF EXISTS blitzer_new");
            } catch (\Throwable $ignore) {
            }
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } finally {
            @unlink($pidFile);
        }
    }

    private function downloadJson(string $url, string $file, int $retries = 20): void
    {
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $fp = fopen($file, 'w');
            if (!$fp) {
                throw new \RuntimeException("无法创建临时文件: {$file}");
            }

            $totalWritten  = 0;
            $firstChunk    = '';
            $responseHeaders = [];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_ENCODING       => '',
                CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use ($fp, &$totalWritten, &$firstChunk) {
                    $written = fwrite($fp, $chunk);
                    $totalWritten += $written;
                    if ($firstChunk === '' && $totalWritten >= 500) {
                        $firstChunk = substr($chunk, 0, 500);
                    }
                    return $written;
                },
                CURLOPT_HEADERFUNCTION => function ($ch, $headerLine) use (&$responseHeaders) {
                    $len = strlen($headerLine);
                    $parts = explode(':', $headerLine, 2);
                    if (count($parts) === 2) {
                        $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }
                    return $len;
                },
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_LOW_SPEED_LIMIT => 1024,
                CURLOPT_LOW_SPEED_TIME  => 120,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
            ]);

            $startTime = microtime(true);
            $result    = curl_exec($ch);
            $elapsed   = round(microtime(true) - $startTime, 2);

            $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentLength  = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $downloadSize   = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            $curlErrno      = curl_errno($ch);
            $curlError      = curl_error($ch);
            curl_close($ch);

            fflush($fp);
            fclose($fp);
            clearstatcache();

            if ($result === false || $curlErrno) {
                Log::warning("Blitzer 下载第 {$attempt} 次失败: cURL错误[{$curlErrno}] {$curlError}, " .
                    "已写入 {$totalWritten} 字节 (耗时 {$elapsed}s)");

                if ($attempt < $retries) {
                    Log::warning("将在 5 秒后清理部分文件并从头重试...");
                    @unlink($file);
                    sleep(5);
                    continue;
                }

                throw new \RuntimeException("cURL 下载失败: [{$curlErrno}] {$curlError}");
            }

            if ($httpCode >= 400) {
                throw new \RuntimeException("HTTP 返回错误码: {$httpCode}");
            }

            $finalSize = filesize($file);
            Log::info("Blitzer 下载完成: HTTP {$httpCode}, 写入 {$totalWritten} 字节, " .
                "文件大小 {$finalSize} 字节, 服务器Content-Length: " . ($contentLength > 0 ? $contentLength : 'chunked') .
                ", 传输大小 {$downloadSize} 字节, 耗时 {$elapsed}s" .
                ($attempt > 1 ? " (第 {$attempt} 次重试成功)" : ''));

            // 记录关键响应头用于诊断
            $diagHeaders = [];
            foreach (['content-encoding', 'content-length', 'transfer-encoding', 'content-type'] as $key) {
                if (isset($responseHeaders[$key])) {
                    $diagHeaders[$key] = $responseHeaders[$key];
                }
            }
            if (!empty($diagHeaders)) {
                Log::info("Blitzer 响应头: " . json_encode($diagHeaders, JSON_UNESCAPED_UNICODE));
            }

            if ($firstChunk !== '') {
                Log::debug("Blitzer 响应前 500 字节: " . $firstChunk);
            }

            return;
        }

        throw new \RuntimeException("Blitzer 下载失败，已重试 {$retries} 次");
    }

    private function isValidJson(string $file): bool
    {
        clearstatcache();
        $size = filesize($file);

        if ($size === 0) {
            Log::warning("JSON 文件为空");
            return false;
        }

        $fp = fopen($file, 'r');
        if (!$fp) {
            return false;
        }

        $head = fread($fp, 4096);

        if ($size > 4096) {
            fseek($fp, -4096, SEEK_END);
            $tail = fread($fp, 4096);
        } else {
            $tail = $head;
        }

        fclose($fp);

        $head = ltrim($head);
        if (!isset($head[0]) || ($head[0] !== '{' && $head[0] !== '[')) {
            Log::warning("JSON 文件首字符不是 {{ 或 [，实际首字符: " . ($head[0] ?? '空'));
            return false;
        }

        $tail = rtrim($tail);
        $last = $tail[strlen($tail) - 1] ?? '';
        if ($last !== '}' && $last !== ']') {
            Log::warning("JSON 文件尾字符不是 }} 或 ]，实际尾字符: " . ($last ?: '空'));
            return false;
        }

        if ($size <= 5 * 1024 * 1024) {
            $json = file_get_contents($file);
            json_decode($json);
            $error = json_last_error();
            if ($error !== JSON_ERROR_NONE) {
                Log::warning("JSON 完整校验失败: " . json_last_error_msg());
                return false;
            }
        }

        return true;
    }

    private function parseJson(string $file): array
    {
        $data = json_decode(file_get_contents($file), true);

        if (empty($data['traffic_informations']['event'])) {
            throw new \Exception('JSON 解析失败');
        }

        $events = $data['traffic_informations']['event'];
        $total  = count($events);
        $batch  = [];
        $count  = 0;

        foreach ($events as $item) {
            $batch[] = [
                'id'            => $item['id'],
                'type'          => empty($item['type'])?0:$item['type'],
                'speed'         => empty($item['speed'])?0:$item['speed'],
                //'address'       => json_encode($item['address'] ?? []),
                //'city'          => $item['address']['city'] ?? '',
                //'street'        => $item['address']['street'] ?? '',
                'latitude'      => empty($item['location']['point']['latitude'])?0:$item['location']['point']['latitude'],
                'longitude'     => empty($item['location']['point']['longitude'])?0:$item['location']['point']['longitude'],
                'direction'     => empty($item['direction'])?0:$item['direction'],
                'directiontype' => empty($item['directiontype'])?0:$item['directiontype']
                //'quality'       => $item['quality'] ?? null,
                //'createtime'    => $item['date']['create'] ?? null,
                //'lastconfirm'   => $item['date']['lastconfirm'] ?? null,
                //'lastuse'       => $item['date']['lastuse'] ?? null,
            ];
            $count++;

            if (count($batch) >= 500) {
                Db::table('blitzer_new')->insertAll($batch);
                $batch = [];

                if ($count % 5000 === 0) {
                    Log::info('Blitzer 入库进度: ' . $count . ' / ' . $total);
                }
            }
        }

        if (!empty($batch)) {
            Db::table('blitzer_new')->insertAll($batch);
        }

        Log::info('Blitzer 解析完成: 共 ' . $count . ' 条');

        return ['count' => $count, 'total' => $total];
    }
}