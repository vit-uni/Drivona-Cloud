<?php
namespace app\process;

use support\Log;
use support\think\Db;
use support\Redis;

class XmlBlitzer
{
    /**
     * 已成功插入的记录数
     */
    private int $successCount = 0;

    /**
     * 插入失败的记录数
     */
    private int $failedCount = 0;

    /**
     * 判断同步任务是否正在执行（Windows 兼容：基于 PID 文件 + 超时时间）
     */
    public function isRunning(int $timeout = 1800): bool
    {
        $pidFile = runtime_path() . '/xml_sync.pid';
        if (!file_exists($pidFile)) {
            return false;
        }

        // 超时保护：PID 文件存在但超过 $timeout 秒未更新，视为僵死，清理掉
        if (time() - filemtime($pidFile) > $timeout) {
            @unlink($pidFile);
            Log::warning('XML 同步 PID 文件超时，已清理');
            return false;
        }

        return true;
    }

    /**
     * 执行一次 XML 同步任务（由 HTTP 接口触发，不再使用 Timer）
     *
     * @return array ['status' => 'started'|'skipped'|'failed', 'message' => string]
     */
    public function run(): array
    {
        $pidFile = runtime_path() . '/xml_sync.pid';

        if ($this->isRunning()) {
            Log::debug('XML 同步跳过：上一次任务仍在执行');
            return ['status' => 'skipped', 'message' => '上一次任务仍在执行'];
        }

        file_put_contents($pidFile, getmypid());

        try {
            Log::info('XML 同步开始');
            $xmlFile = runtime_path() . '/device.xml';

            $this->downloadXml($xmlFile);

            if (!$this->isValidXml($xmlFile)) {
                throw new \RuntimeException('XML 文件无效');
            }

            // Db::execute("DROP TABLE IF EXISTS device_new");
            // Db::execute("CREATE TABLE device_new LIKE wa_device");

            // $this->parseXml($xmlFile);

            // Db::execute("RENAME TABLE wa_device TO device_old,device_new TO wa_device");
            // Db::execute("DROP TABLE IF EXISTS device_old");

            //@unlink($xmlFile);
            @unlink($pidFile);

            Log::info('XML 下载完成，文件已就绪');
            return ['status' => 'started', 'message' => '下载已完成'];

        } catch (\Throwable $e) {
            Log::error('XML 下载失败：' . $e->getMessage());
            @unlink($pidFile);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * 仅入库（假设 device.xml 已经由 xmlStart 下载完成）
     * - 检查文件是否存在且完整
     * - 创建临时表 device_new
     * - 解析 XML，将数据推送到 Redis 队列
     * - 队列消费者会负责实际写入数据库和原子切换表
     *
     * @return array ['status' => 'success'|'failed'|'skipped', 'message' => string]
     */
    public function insertFromDownloadedFile(): array
    {
        $pidFile = runtime_path() . '/xml_insert.pid';

        if ($this->isRunning()) {
            return ['status' => 'skipped', 'message' => 'XML 入库任务已在执行中'];
        }

        $xmlFile = runtime_path() . '/device.xml';
        if (!file_exists($xmlFile) || filesize($xmlFile) === 0) {
            return ['status' => 'failed', 'message' => 'XML 文件不存在或为空，请先调用 xmlStart 下载'];
        }

        file_put_contents($pidFile, getmypid());

        try {
            Log::info('XML 入库（队列模式）开始：' . $xmlFile);

            if (!$this->isValidXml($xmlFile)) {
                throw new \RuntimeException('XML 文件校验失败，请重新下载');
            }

            // 1. 创建临时表
            Db::execute("DROP TABLE IF EXISTS device_new");
            Db::execute("CREATE TABLE device_new LIKE wa_device");

            // 2. 清空可能残留的旧队列
            Redis::deleteDeviceXmlQueue();

            // 3. 解析 XML 并将数据推送到队列
            $startTime = microtime(true);
            $totalPushed = $this->parseXmlToQueue($xmlFile);
            $elapsed = round(microtime(true) - $startTime, 2);

            // 4. 发送完成信号（包含 XML 文件路径，供消费者处理完成后删除）
            Redis::db_insert([
                'type'     => 'complete',
                'total'    => $totalPushed,
                'file'     => $xmlFile,
                'timestamp'=> time(),
            ]);

            Log::info(sprintf(
                'XML 数据解析完成并已推入队列：共 %d 条，耗时 %.2fs',
                $totalPushed,
                $elapsed
            ));

            return [
                'status'  => 'success',
                'message' => sprintf(
                    '数据已推入队列：%d 条，耗时 %.2fs，等待消费者入库',
                    $totalPushed,
                    $elapsed
                ),
                'total'   => $totalPushed,
                'elapsed' => $elapsed,
            ];
        } catch (\Throwable $e) {
            Log::error('XML 入库（队列模式）失败：' . $e->getMessage());
            try {
                Db::execute("DROP TABLE IF EXISTS device_new");
            } catch (\Throwable $ignore) {
            }
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } finally {
            @unlink($pidFile);
        }
    }

    /* ========= XML 有效性检查 ========= */
    private function isValidXml(string $file): bool
    {
        clearstatcache();
        $size = filesize($file);

        if ($size === 0) {
            Log::warning('XML 文件为空');
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
        if (!isset($head[0]) || $head[0] !== '<') {
            Log::warning('XML 文件首字符不是 <，实际首字符: ' . ($head[0] ?? '空'));
            return false;
        }

        $tail = rtrim($tail);
        if ($tail === '') {
            Log::warning('XML 文件尾部为空');
            return false;
        }

        // 取尾部最后一个字符（rtrim 后应为 '>'）
        $lastChar = substr($tail, -1);
        if ($lastChar !== '>') {
            // 额外调试：展示尾部最后 200 字符的内容，便于排查
            $tailSample = htmlspecialchars(substr($tail, -200));
            Log::warning("XML 文件末尾不以 '>' 结尾，实际最后字符: {$lastChar}，尾部片段: {$tailSample}");
            return false;
        }

        // 额外检查：尾部 4096 字节内必须存在至少一个关闭标签 </xxx>
        if (strpos($tail, '</') === false) {
            Log::warning('XML 文件尾部 4096 字节内未发现关闭标签 </');
            return false;
        }

        return true;
    }

    /* ========= 流式下载 XML ========= */
    private function downloadXml(string $file, int $retries = 3): void
    {
        $url = 'https://clients.scdb.info/d/tExokgUzPA3teZg0qXQl580GvdZR79FTPmy5o5gg/228';

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $fp = fopen($file, 'w');
            if (!$fp) {
                throw new \RuntimeException("无法创建临时文件: {$file}");
            }

            $totalWritten = 0;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => false,
                CURLOPT_ENCODING        => '',
                CURLOPT_WRITEFUNCTION   => function ($ch, $chunk) use ($fp, &$totalWritten) {
                    $written = fwrite($fp, $chunk);
                    $totalWritten += $written;
                    return $written;
                },
                CURLOPT_CONNECTTIMEOUT  => 30,
                CURLOPT_TIMEOUT         => 0,
                CURLOPT_LOW_SPEED_LIMIT => 1024,
                CURLOPT_LOW_SPEED_TIME  => 120,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false,
            ]);

            $startTime = microtime(true);
            $result    = curl_exec($ch);
            $elapsed   = round(microtime(true) - $startTime, 2);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            fflush($fp);
            fclose($fp);
            clearstatcache();

            if ($result === false || $curlErrno) {
                Log::warning("XML 下载第 {$attempt} 次失败: [{$curlErrno}] {$curlError} (耗时 {$elapsed}s)");
                if ($attempt < $retries) {
                    @unlink($file);
                    sleep(5);
                    continue;
                }
                throw new \RuntimeException("XML 下载失败: [{$curlErrno}] {$curlError}");
            }

            if ($httpCode >= 400) {
                throw new \RuntimeException("HTTP 错误码: {$httpCode}");
            }

            $finalSize = filesize($file);
            Log::info("XML 下载完成: HTTP {$httpCode}, 写入 {$totalWritten} 字节, 文件大小 {$finalSize} 字节, 耗时 {$elapsed}s");
            return;
        }

        throw new \RuntimeException("XML 下载失败，已重试 {$retries} 次");
    }



    /* ========= XMLReader 解析（推送到队列） ========= */
    private function parseXmlToQueue(string $file): int
    {
        $reader = new \XMLReader();
        if (!$reader->open($file)) {
            throw new \RuntimeException("无法打开 XML 文件: {$file}");
        }

        $insert               = [];
        $item                 = [];
        $total                = 0;
        $inAdditionalCoord    = false;
        $currentCoord         = [];
        $additionalCoords     = [];

        $simpleMap = [
            'ID'              => 'ID',
            'Country'         => 'Country',
            'County'          => 'County',
            'State'           => 'State',
            'Postcode'        => 'Postcode',
            'City'            => 'City',
            'District'        => 'District',
            'Street'          => 'Street',
            'Type'            => 'Type',
            'Speed'           => 'Speed',
            'Longitude'       => 'Longitude',
            'Latitude'        => 'Latitude',
            'Direction'       => 'Direction',
            'DirType'         => 'DirType',
            'LastUpdate'      => 'LastUpdate',
            'Status'          => 'Status',
            'VisualCheck'     => 'VisualCheck',
            'VisualCheckDate' => 'VisualCheckDate',
            'Description'     => 'Description',
        ];

        $coordMap = [
            'ID'        => 'id',
            'Type'      => 'type',
            'Longitude' => 'longitude',
            'Latitude'  => 'latitude',
            'Direction' => 'direction',
            'Polyline'  => 'polyline',
        ];

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT) {
                $nodeName = $reader->name;

                if ($nodeName === 'AdditionalCoordinate') {
                    $inAdditionalCoord = true;
                    $currentCoord      = [];
                    continue;
                }

                $value = $reader->readString();

                if ($inAdditionalCoord) {
                    if (isset($coordMap[$nodeName])) {
                        $currentCoord[$coordMap[$nodeName]] = $value;
                    }
                } else {
                    if (isset($simpleMap[$nodeName])) {
                        $item[$simpleMap[$nodeName]] = $value;
                    }
                }
            }

            if ($reader->nodeType === \XMLReader::END_ELEMENT
                && $reader->name === 'AdditionalCoordinate') {
                if (!empty($currentCoord)) {
                    $additionalCoords[] = $currentCoord;
                }
                $inAdditionalCoord = false;
                $currentCoord      = [];
            }

            if ($reader->nodeType === \XMLReader::END_ELEMENT
                && $reader->name === 'camera') {
                if (!empty($additionalCoords)) {
                    $item['AdditionalCoordinate'] = json_encode($additionalCoords, JSON_UNESCAPED_UNICODE);
                } else {
                    $item['AdditionalCoordinate'] = null;
                }

                $insert = $this->processCameraData($item, $insert);
                $total++;
                $item                 = [];
                $additionalCoords     = [];
                $inAdditionalCoord    = false;
                $currentCoord         = [];

                if ($total % 2000 === 0) {
                    $this->pushBatchToQueue($insert);
                    $insert = [];
                }

                if ($total % 10000 === 0) {
                    Log::info("XML 解析进度（推队列）: {$total} 条");
                }
            }
        }

        if (!empty($insert)) {
            $this->pushBatchToQueue($insert);
        }

        $reader->close();
        Log::info("XML 解析完成（推队列）: 共 {$total} 条记录");

        return $total;
    }

    /**
     * 将一批数据推送到 Redis 队列
     */
    private function pushBatchToQueue(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        $message = [
            'type'  => 'data',
            'batch' => $batch,
            'count' => count($batch),
        ];

        Redis::db_insert($message);
    }

    /* ========= 数据组装 + 批量插入 ========= */
    public function processCameraData($v, $insert)
    {
        $child = [
            "camera_id"         => !empty($v['ID']) ? $v['ID'] : 0,
            "country"           => !empty($v['Country']) ? $v['Country'] : '',
            "county"            => !empty($v['County']) ? $v['County'] : '',
            "state"             => !empty($v['State']) ? $v['State'] : '',
            "postcode"          => !empty($v['Postcode']) ? $v['Postcode'] : '',
            "city"              => !empty($v['City']) ? $v['City'] : '',
            "district"          => !empty($v['District']) ? $v['District'] : '',
            "street"            => !empty($v['Street']) ? $v['Street'] : '',
            "type"              => !empty($v['Type']) ? $v['Type'] : '',
            "speed"             => !empty($v['Speed']) ? $v['Speed'] : 0,
            "longitude"         => !empty($v['Longitude']) ? $v['Longitude'] : 0,
            "latitude"          => !empty($v['Latitude']) ? $v['Latitude'] : 0,
            "direction"         => !empty($v['Direction']) ? $v['Direction'] : 0,
            "dirtype"           => !empty($v['DirType']) ? $v['DirType'] : 0,
            "lastupdate"        => !empty($v['LastUpdate']) ? $v['LastUpdate'] : null,
            "status"            => !empty($v['Status']) ? $v['Status'] : '',
            "visualcheck"       => !empty($v['VisualCheck']) ? $v['VisualCheck'] : '',
            "visualcheckdate"   => !empty($v['VisualCheckDate']) ? $v['VisualCheckDate'] : null,
            "description"       => !empty($v['Description']) ? $v['Description'] : ''
        ];
        if(!empty($v['ID']))$child['id'] = $v['ID'];
        if (!empty($v['AdditionalCoordinate'])) {
            // 已经是 parseXml 中 json_encode 好的字符串（或 null）
            $child['additionalcoordinate'] = $v['AdditionalCoordinate'];
        } else {
            $child['additionalcoordinate'] = '';
        }

        $insert[] = $child;
        
        return $insert;
    }

    
}
