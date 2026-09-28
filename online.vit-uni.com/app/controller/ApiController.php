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
    /**
     * 不需要登录的方法
     * # XML格式
        http://direct.blitzer.de/export/v3/xml/?key=YOUR_KEY
        
        # JSON格式
        http://direct.blitzer.de/export/v3/json/?key=YOUR_KEY
     */
    protected $noNeedLogin = ['*'];
    
    public function index(){
        Log::info('123456789');
        return json(['code' => 1, 'msg' => '','data'=>null]);
    }

    public function devices_update(){
        
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
                            $insert = $this->processCameraData($currentCamera,$insert);

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
        if(!empty($insert)){
            $message = [
                'type'  => 'data',
                'batch' => $insert,
                'count' => count($insert),
            ];
            Redis::db_insert($message);
        }
        $message = [
            'type'  => 'finish'
        ];
        Redis::db_insert($message);
        unset($insert);
        return json(['code' => 0, 'msg' => "等待队列处理"]);
    }
    public function processCameraData($v,$insert){
        $child = [
            "id"=> $v['ID'],
            "country"=> empty($v['Country'])?'':$v['Country'],
            "county"=> empty($v['County'])?'':$v['County'],
            "state"=>  empty($v['State'])?'':$v['State'],
            //"postcode"=> empty($v['Postcode'])?0:$v['Postcode'],
            "city"=> empty($v['City'])?'':$v['City'],
            "district"=> empty($v['District'])?'':$v['District'],
            "street"=> empty($v['Street'])?'':$v['Street'],
            
            "type"=> empty($v['Type'])?0:$v['Type'],
            "speed"=> empty($v['Speed'])?0:$v['Speed'],
            "longitude"=> empty($v['Longitude'])?0:$v['Longitude'],
            "latitude"=> empty($v['Latitude'])?0:$v['Latitude'],
            "direction"=> empty($v['Direction'])?0:$v['Direction'],
            "dirtype"=> empty($v['DirType'])?0:$v['DirType'],
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
                'type'  => 'data',
                'batch' => $insert,
                'count' => count($insert),
            ];
            Redis::db_insert($message);
            $insert = [];
        }
            
        return $insert;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    //新增摄像头 / 危险上报（POST）
    public function reportNewCamera(Request $request)
    {
        return [
            'code' => 1,
           
            'msg'     => '上报成功'
        ];
        //参数	类型	必填	说明
        //api_key	string	✅	接口密钥
        //latitude	float	✅	纬度
        //longitude	float	✅	经度
        //current_speed	int	❌	上报时车辆当前速度
        //speed_limit	int	❌	道路限速
        //speed_unit	string	❌	速度单位："kph"或 "mph"
        //session_id	string	✅	会话标识
        //direction_deg	int	❌	摄像头朝向（0–359°）
        //event_type	int	✅	事件类型（见下方编码表）
        //partner_id	string	❌	合作方内部业务 ID
        //comment	string	❌	备注说明
        $latitude = $request->inupt('latitude');
        $longitude = $request->inupt('longitude');
        $current_speed = $request->inupt('current_speed');
        $speed_limit = $request->inupt('speed_limit');
        $speed_unit = $request->inupt('speed_unit');
        $event_type = $request->inupt('event_type');

        $key = config('app.export_key');
        $reportData = [
            'api_key'      => $key,               // 正式环境替换为真实 key
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'current_speed'=> $current_speed,
            'speed_limit'  => $speed_limit,
            'speed_unit'   => $speed_unit,
            //'session_id'   => '550e8400-e29b-41d4-a716-446655440000',
            'event_type'   => $event_type, 
            'comment'      => ''
        ];
        // 编码	含义
        // 1	移动测速
        // 2	移动闯红灯
        // 6	移动车距监测
        // 20	拥堵/车尾结束
        // 21	事故
        // 22	临时施工
        // 23	路面障碍物
        // 24	路面湿滑
        // 25	视线受阻
        // 26	长期施工
        // 29	故障车辆
        
        
        // 编码	含义
        // 101	车距监测
        // 104	专用车道监控
        // 107	固定测速
        // 108	限重监控
        // 109	限高监控
        // 110	测速 + 闯红灯
        // 111	闯红灯
        // 112	区间测速（入口/中段）
        // 113	区间测速（出口）

        $url = 'https://direct.blitzer.de/api/v2/';
    
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
    
        return [
            'code' => $httpCode,
            'data'  => json_decode($response, true),
            'msg'     => $error
        ];
    }
    
    
    //更新已有摄像头 / 危险（PUT）
    public function updateCameraReport(Request $request)
    {
        
        return [
            'code' => 1,
           
            'msg'     => '上报成功'
        ];
        
        
        // ===== 使用示例 =====
        //参数	类型	必填	说明
        //api_key	string	✅	接口密钥
        //latitude	float	✅	纬度
        //longitude	float	✅	经度
        //session_id	string	✅	会话标识
        //event_id	string	✅	原事件 ID（未知则填 "0"）
        //update_type	int	✅	更新类型（见下方编码表）
        //comment	string	❌	更新原因说明
        //partner_id	string	❌	合作方内部业务 ID
        $latitude = $request->inupt('latitude');
        $longitude = $request->inupt('longitude');
        $event_id = $request->inupt('event_id');
        $update_type = $request->inupt('update_type');
        
        $key = config('app.export_key');
        // ===== 使用示例 =====
        $updateData = [
            'api_key'    => $key,
            'latitude'   => $latitude,
            'longitude'  => $longitude,
            //'session_id' => '550e8400-e29b-41d4-a716-446655440000',
            'event_id'   => $event_id,   // 已知 ID
            'update_type'=> $update_type,        // 摄像头已移除
            'comment'    => ''
        ];
        
        // 编码	含义
        // 102	临时停用
        // 103	不是摄像头
        // 104	已拆除
        // 120	类型错误
        // 121	位置错误
        // 122	限速错误
        // 123	方向错误
        
        //$result = updateCameraReport($updateData);
        $url = 'https://direct.blitzer.de/api/v2/update/';
    
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
    
        return [
            'code' => $httpCode,
            'data'  => json_decode($response, true),
            'msg'     => $error
        ];
    }



    
}
