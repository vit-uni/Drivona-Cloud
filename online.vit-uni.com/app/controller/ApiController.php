<?php

namespace app\controller;

use support\Request;
use support\think\Db;
use support\Http;
use support\Redis;
use support\Log;
use \XMLReader;

class ApiController
{


    private static $i18n = [
        '成功' => ['Success', 'Erfolg'],
        '失败' => ['Failed', 'Fehlgeschlagen'],
        '参数错误' => ['Invalid parameter', 'Ungültiger Parameter'],
        '经纬度参数无效' => ['Invalid latitude/longitude', 'Ungültige Breiten-/Längengrade'],
    ];


    /**
     * 根据请求头 language（de/en）返回对应语言文案，缺省中文
     */
    private static function t(Request $request, string $key): string
    {
        $lang = strtolower(trim(substr((string)$request->header('language', ''), 0, 2)));
        if ($lang === 'en' && isset(self::$i18n[$key][0])) {
            return self::$i18n[$key][0];
        }
        if ($lang === 'de' && isset(self::$i18n[$key][1])) {
            return self::$i18n[$key][1];
        }
        return $key;
    }


    /**
     * 不需要登录的方法
     * # XML格式
     * http://direct.blitzer.de/export/v3/xml/?key=YOUR_KEY
     *
     * # JSON格式
     * http://direct.blitzer.de/export/v3/json/?key=YOUR_KEY
     */
    protected $noNeedLogin = ['*'];

    public function index()
    {
        Log::info('123456789');
        return json(['code' => 1, 'msg' => '', 'data' => null]);
    }

    public function devices_update()
    {

        set_time_limit(0);
        $xmlFile = runtime_path() . '/device.xml';
        $reader = new XMLReader();
        if (!$reader->open($xmlFile)) {
            return json(['code' => 0, 'msg' => '无法打开文件']);
        }

        $cameraCount = 0;
        $currentCamera = null;
        $currentElement = '';

        $insert = [];

        Db::execute("DROP TABLE IF EXISTS device_new");
        Db::execute("CREATE TABLE device_new LIKE wa_device");


        try {
            while ($reader->read()) {
                switch ($reader->nodeType) {
                    case XMLReader::ELEMENT:
                        if ($reader->name == 'camera') {
                            $currentCamera = [
                                'AdditionalCoordinates' => []
                            ];
                        } elseif ($reader->name == 'AdditionalCoordinate') {
                            $currentCoord = [];
                            $depth = $reader->depth;

                            // 读取整个 AdditionalCoordinate 节点
                            while ($reader->read() && !($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == 'AdditionalCoordinate')) {
                                if ($reader->nodeType == XMLReader::ELEMENT) {
                                    $elementName = $reader->name;
                                    $reader->read();
                                    if ($reader->nodeType == XMLReader::TEXT) {
                                        $currentCoord[$elementName] = $reader->value;
                                    }
                                }
                            }

                            if (!empty($currentCoord)) {
                                $currentCamera['AdditionalCoordinates'][] = $currentCoord;
                            }
                        } elseif ($reader->name != 'cameras' && $reader->name != 'AdditionalCoordinate') {
                            $currentElement = $reader->name;
                        }
                        break;

                    case XMLReader::TEXT:
                        if ($currentCamera !== null && $currentElement) {
                            $currentCamera[$currentElement] = $reader->value;
                        }
                        break;

                    case XMLReader::END_ELEMENT:
                        if ($reader->name == 'camera') {
                            // 处理完整的摄像头数据
                            $insert = $this->processCameraData($currentCamera, $insert);

                            $currentCamera = null;
                        }
                        $currentElement = '';
                        break;
                }
            }

            //echo "\n完成！总计: {$cameraCount} 个摄像头\n";
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        } finally {
            $reader->close();
        }
        if (!empty($insert)) {
            $message = [
                'type' => 'data',
                'batch' => $insert,
                'count' => count($insert),
            ];
            Redis::db_insert($message);
        }
        $message = [
            'type' => 'finish'
        ];
        Redis::db_insert($message);
        unset($insert);
        return json(['code' => 0, 'msg' => "等待队列处理"]);
    }

    public function processCameraData($v, $insert)
    {
        $child = [
            "id" => $v['ID'],
            "country" => empty($v['Country']) ? '' : $v['Country'],
            "county" => empty($v['County']) ? '' : $v['County'],
            "state" => empty($v['State']) ? '' : $v['State'],
            //"postcode"=> empty($v['Postcode'])?0:$v['Postcode'],
            "city" => empty($v['City']) ? '' : $v['City'],
            "district" => empty($v['District']) ? '' : $v['District'],
            "street" => empty($v['Street']) ? '' : $v['Street'],

            "type" => empty($v['Type']) ? 0 : $v['Type'],
            "speed" => empty($v['Speed']) ? 0 : $v['Speed'],
            "longitude" => empty($v['Longitude']) ? 0 : $v['Longitude'],
            "latitude" => empty($v['Latitude']) ? 0 : $v['Latitude'],
            "direction" => empty($v['Direction']) ? 0 : $v['Direction'],
            "dirtype" => empty($v['DirType']) ? 0 : $v['DirType'],
            //"lastupdate"=> $v['LastUpdate']??null  empty($v['LastUpdate'])?0:$v['Type'],
            //"status"=> $v['Status']??null,
            //"visualcheck"=> $v['VisualCheck']??null,
            //"visualcheckdate"=> $v['VisualCheckDate']??null,
            //"description"=> $v['Description']??null
        ];
        // if(!empty($v['AdditionalCoordinate'])){
        //     $child['additionalcoordinate'] = json_encode($v['AdditionalCoordinate']);
        // }else{
        //     $child['additionalcoordinate'] = null;
        // }
        $insert[] = $child;

        if (count($insert) >= 5000) {

            $message = [
                'type' => 'data',
                'batch' => $insert,
                'count' => count($insert),
            ];
            Redis::db_insert($message);
            $insert = [];
        }

        return $insert;
    }


    //app新增摄像头 / 危险上报（POST）
    public function reportNewCamera(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $type = $request->input('type');

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return json(['code' => 0, 'msg' => self::t($request, '经纬度参数无效')]);
        }

        $comment = "";
        $event_type = "";
        // 1: 固定摄像头 2. 移动摄像头  3. 路况
        if ($type == "1") {
            $comment = "There are fixed cameras here.";
            $event_type = 107;
        } else if ($type == "2") {
            $comment = "Here, there is a moving camera.";
            $event_type = 1;
        } else if ($type == "3") {
            $comment = "There are road conditions risks here.";
            $event_type = 20;
        }

        $inc = [];
        $inc['longitude'] = $longitude;
        $inc['latitude'] = $latitude;
        $inc['classify'] = $type;
        $inc['event_type'] = $event_type;
        $inc['comment'] = $comment;
        $inc['push_status'] = 0;
        $inc['create_date'] = date('Y-m-d H:i:s');
        $inc['update_date'] = date('Y-m-d H:i:s');
        $res = Db::name('event_push')->insert($inc);
        return json(['code' => 1, 'msg' => self::t($request, '成功')]);
    }


    //app更新已有摄像头 / 危险（PUT）
    public function updateCameraReport(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $type = $request->input('type');
        $id = $request->input('eventId');
        $eventType = $request->input('eventType');

        $older = null;

        // 1: 固定摄像头 2. 移动摄像头  3. 路况
        if ($type == "1") {
            $older = Db::name('device')->where('id', $id)->find();
            $comment = "There are no fixed cameras here.";
        } else if ($type == "2") {
            $older = Db::name('device_event')->where('id', $id)->find();
            $comment = "There is no need to move the camera here.";
        } else if ($type == "3") {
            $older = Db::name('device_event')->where('id', $id)->find();
            $comment = "There is no danger on the road here.";
        }

        // 查不到数据
        if (empty($older)) {
            return json(['code' => 0, 'msg' => '未找到对应的事件记录，eventId=' . $id . '，type=' . $type]);
        }

        $latitude = $older["latitude"];
        $longitude = $older["longitude"];

        $inc = [];
        $inc['longitude'] = $longitude;
        $inc['latitude'] = $latitude;
        $inc['classify'] = $type;
        $inc['event_id'] = $id;
        $inc['update_type'] = 104;
        $inc['comment'] = $comment;
        $inc['push_status'] = 0;
        $inc['create_date'] = date('Y-m-d H:i:s');
        $inc['update_date'] = date('Y-m-d H:i:s');
        $res = Db::name('event_push')->insert($inc);
        return json(['code' => 1, 'msg' => self::t($request, '成功')]);

    }


    //推送新增摄像头 / 危险上报（POST）
    public function pushNewCamera(Request $request)
    {
        set_time_limit(0);

        //查询未推送的新增摄像头 / 危险路况数据（event_type 非空、push_status=0）
        $list = Db::name('event_push')
            ->where('push_status', 0)
            ->where('event_type is not null')
            ->select();

        if (empty($list)) {
            return json([
                'code' => 1,
                'msg' => self::t($request, '成功'),
                'data' => ['total' => 0, 'success' => 0, 'fail' => 0],
            ]);
        }

        $key = config('app.export_key');
        $url = 'https://direct.blitzer.de/api/v2/';
        $total = count($list);
        $success = 0;
        $fail = 0;

        foreach ($list as $row) {
            $reportData = [
                'api_key' => $key,
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'session_id' => 'anonymous',
                'event_type' => (int)$row['event_type'],
                'comment' => (string)$row['comment'],
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($reportData),
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            if ($curlErr === '' && is_array($result) && isset($result['status']) && (int)$result['status'] === 1) {
                Db::name('event_push')->where('id', $row['id'])->update([
                    'push_status' => 1,
                    'update_date' => date('Y-m-d H:i:s'),
                ]);
                $success++;
            } else {
                $fail++;
                Log::warning('推送新增摄像头失败 id=' . $row['id'] . ' resp=' . $response . ' err=' . $curlErr);
            }
        }

        return json([
            'code' => 1,
            'msg' => self::t($request, '成功'),
            'data' => ['total' => $total, 'success' => $success, 'fail' => $fail],
        ]);
    }


    //推送更新已有摄像头 / 危险（PUT）
    public function pushUpdateCamera(Request $request)
    {
        set_time_limit(0);

        //查询未推送的更新摄像头 / 危险路况数据（event_id、update_type 非空、push_status=0）
        $list = Db::name('event_push')
            ->where('push_status', 0)
            ->where('event_id is not null')
            ->where('update_type is not null')
            ->select();

        if (empty($list)) {
            return json([
                'code' => 1,
                'msg' => self::t($request, '成功'),
                'data' => ['total' => 0, 'success' => 0, 'fail' => 0],
            ]);
        }

        $key = config('app.export_key');
        $url = 'https://direct.blitzer.de/api/v2/update/';
        $total = count($list);
        $success = 0;
        $fail = 0;

        foreach ($list as $row) {
            $updateData = [
                'api_key' => $key,
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'session_id' => 'anonymous',
                'event_id' => (string)$row['event_id'],
                'update_type' => (int)$row['update_type'],
                'comment' => (string)$row['comment'],
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($updateData),
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            if ($curlErr === '' && is_array($result) && isset($result['status']) && (int)$result['status'] === 1) {
                Db::name('event_push')->where('id', $row['id'])->update([
                    'push_status' => 1,
                    'update_date' => date('Y-m-d H:i:s'),
                ]);
                $success++;
            } else {
                $fail++;
                Log::warning('推送更新摄像头失败 id=' . $row['id'] . ' resp=' . $response . ' err=' . $curlErr);
            }
        }

        return json([
            'code' => 1,
            'msg' => self::t($request, '成功'),
            'data' => ['total' => $total, 'success' => $success, 'fail' => $fail],
        ]);
    }


}
