<?php

namespace app\controller;

use support\Request;
use support\think\Db;
use app\model\Email;
use app\model\Sms;
use plugin\admin\app\common\Util;
use support\Log;
use app\model\Location;
use app\model\Rsa;
class IndexController
{
    /**
     * 不需要登录的方法
     */
    protected $noNeedLogin = ['sendMail','sign','login','upload','agreement','forget','index','devices2','feedback','checkMail','publicKey'];
    
    /**
     * 接口提示语文案：中文原文 => [en, de]
     */
    private static $i18n = [
        '成功' => ['Success', 'Erfolg'],
        '失败' => ['Failed', 'Fehlgeschlagen'],
        '参数错误' => ['Invalid parameter', 'Ungültiger Parameter'],
        '该手机号未注册' => ['This phone number is not registered', 'Diese Handynummer ist nicht registriert'],
        '邮箱错误' => ['Invalid email address', 'Ungültige E-Mail-Adresse'],
        '邮箱未注册' => ['Email address not registered', 'E-Mail-Adresse nicht registriert'],
        '邮箱已注册' => ['Email address already registered', 'E-Mail-Adresse bereits registriert'],
        '发送成功' => ['Sent successfully', 'Erfolgreich gesendet'],
        '验证码错误' => ['Invalid verification code', 'Ungültiger Bestätigungscode'],
        '验证成功' => ['Verified successfully', 'Erfolgreich verifiziert'],
        '注册成功' => ['Registered successfully', 'Erfolgreich registriert'],
        '注册失败' => ['Registration failed', 'Registrierung fehlgeschlagen'],
        '操作成功' => ['Operation successful', 'Vorgang erfolgreich'],
        '公钥获取失败' => ['Failed to get public key', 'Öffentlicher Schlüssel konnte nicht abgerufen werden'],
        '解密失败' => ['Decryption failed', 'Entschlüsselung fehlgeschlagen'],
        '解密数据格式错误' => ['Decrypted data format error', 'Formatfehler der entschlüsselten Daten'],
        '密码错误' => ['Incorrect password', 'Falsches Passwort'],
        '账户禁用' => ['Account disabled', 'Konto deaktiviert'],
        '登陆成功' => ['Login successful', 'Anmeldung erfolgreich'],
        '旧密码错误' => ['Current password is incorrect', 'Das aktuelle Passwort ist falsch'],
        '保存成功' => ['Saved successfully', 'Erfolgreich gespeichert'],
        '设备已被其他用户绑定，解绑后在使用' => ['This device is linked to another account. Unlink it before using it.', 'Dieses Gerät ist bereits mit einem anderen Konto verknüpft. Heben Sie die Verknüpfung auf, bevor Sie es verwenden.'],
        '新设备' => ['New device', 'Neues Gerät'],
        '添加成功' => ['Added successfully', 'Erfolgreich hinzugefügt'],
        '设备不存在' => ['Device not found', 'Gerät nicht gefunden'],
        '删除成功' => ['Deleted successfully', 'Erfolgreich gelöscht'],
        '设置成功' => ['Settings saved', 'Einstellungen gespeichert'],
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
    
    
    public function index(){
       
        return redirect('/app/admin');
    }
    
    
    
    public function agreement(Request $request){
        $id = $request->input('id');
        $res = Db::name('agreement')->find($id);
        return json(['code' => 1, 'msg' => self::t($request, '成功'),'data'=>$res]);
    }


    
    //本地文档上传
    public function upload(Request $request)
    {
        $file = $request->file('file');
        if ($file && $file->isValid()) {
            $name = date('YmdHis').mt_rand(0,100);
            $path = '/upload/'.$name.'.'.$file->getUploadExtension();
            $file->move(public_path().$path);
            
            
            return json(['code' => 1, 'msg' => self::t($request, '成功'),'path'=>$path,'data'=>$path]);
        }
        return json(['code' => 0, 'msg' => self::t($request, '失败')]);
    }
    
    //上传图片
    public function image(Request $request)
    {
        $file = $request->file('file');
        if ($file && $file->isValid()) {
            $name = date('YmdHis').mt_rand(0,100);
            $path = '/upload/'.$name.'.'.$file->getUploadExtension();
            $file->move(public_path().$path);
            
            return json(['code' => 1, 'msg' => self::t($request, '成功'),'data'=>$path]);
        }
        return json(['code' => 0, 'msg' => self::t($request, '失败')]);
    }



    
    //发送验证码
    public function sendSms(Request $request)
    {
        $mobile = $request->post('mobile');
        $type = $request->post('type');
        if(empty($mobile)||empty($type)){
            return json(['code' => 0, 'msg' => self::t($request, '参数错误')]);
        }
        if($type=='forget'){
            $older = Db::name('users')->where('mobile',$mobile)->find();
            if(empty($older)){
                return json(['code' => 0, 'msg' => self::t($request, '该手机号未注册')]);
            }
        }
        $lang = strtolower(trim(substr((string)$request->header('language', ''), 0, 2)));
        Email::send($email,$type,$request->header('x-real-ip')??'',$lang);
        return json(['code' => 1, 'msg' => self::t($request, '发送成功')]);
    }
    
    //发送邮件
    public function sendMail(Request $request)
    {
        $email = $request->post('email');
        $type = $request->post('type');
        if(empty($email)||empty($type)){
            return json(['code' => 0, 'msg' => self::t($request, '参数错误')]);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
        } else {
            return json(['code' => 0, 'msg' => self::t($request, '邮箱错误')]);
        }


        $older = Db::name('users')->where('email',$email)->find();
        if($type=='forget'){
            if(empty($older)){
                return json(['code' => 0, 'msg' => self::t($request, '邮箱未注册')]);
            }
        }else{
            if($older)return json(['code' => 0, 'msg' => self::t($request, '邮箱已注册')]);
        }
        $lang = strtolower(trim(substr((string)$request->header('language', ''), 0, 2)));
        Email::send($email,$type,$request->header('x-real-ip')??'',$lang);
        return json(['code' => 1, 'msg' => self::t($request, '发送成功')]);
    }
    
    
    //验证码验证
    public function checkMail(Request $request)
    {
        $email = $request->post('email');
        $code = $request->post('code');
        $type = $request->post('type');
        if(empty($type))$type = 1;
        $older = Db::name('users')->where('email',$email)->find();
        if($type==1){
            if(!empty($older)){
                return json(['code' => 1, 'msg' => self::t($request, '邮箱已注册'),'data'=>0]);
            }
            $mod = 'sign';
        }
        if($type==2){
            if(empty($older)){
                return json(['code' => 1, 'msg' => self::t($request, '邮箱未注册'),'data'=>0]);
            }
            $mod = 'forget';
        }

        $check = Email::check($email,$code,$mod);
        if(empty($check)){
            return json(['code' => 1, 'msg' => self::t($request, '验证码错误'),'data'=>0]);
        }
        
        return json(['code' => 1, 'msg' => self::t($request, '验证成功'),'data'=>1]);
    }
    
    //用户注册
    public function sign(Request $request)
    {
        $email = $request->post('email');
        $code = $request->post('code');
        $password = $request->post('password');
        if(empty($email)||empty($code)||empty($password)){
            return json(['code' => 0, 'msg' => self::t($request, '参数错误')]);
        }
        $older = Db::name('users')->where('email',$email)->find();
        if($older){
            return json(['code' => 0, 'msg' => self::t($request, '邮箱已注册')]);
        }
        $check = Email::check($email,$code,'sign');
        if(empty($check)){
            return json(['code' => 0, 'msg' => self::t($request, '验证码错误')]);
        }

        $inc = [];
        $inc['nickname'] =  random('alpha',8);
        $inc['username'] = $inc['nickname'];
        $inc['email'] = $email;
        $inc['avatar'] = avatar();
        $inc['password'] = Util::passwordHash($password);
        $inc['status'] = 1;
        $inc['created_at'] = date('Y-m-d H:i:s');
        $res = Db::name('users')->insert($inc);
        if($res)return json(['code' => 1, 'msg' => self::t($request, '注册成功')]);
        return json(['code' => 0, 'msg' => self::t($request, '注册失败')]);
    }
    
    public function login_out(Request $request){
        $user = $request->input('user_info');
        Db::name('users')->update(['id'=>$user['id'],'token'=>'']);
        return json(['code' =>1, 'msg' => self::t($request, '操作成功')]);
    }
    public function login_off(Request $request){
        $user = $request->input('user_info');
        Db::name('users')->where('id',$user['id'])->delete();
        Db::name('equipment')->where('user_id',$user['id'])->delete();
        return json(['code' => 1, 'msg' => self::t($request, '操作成功')]);
    }
    
    
    

    
    //获取RSA公钥
    public function publicKey(Request $request)
    {
        $key = Rsa::publicKey();
        if (empty($key)) {
            return json(['code' => 0, 'msg' => self::t($request, '公钥获取失败')]);
        }
        return json(['code' => 1, 'msg' => self::t($request, '成功'), 'data' => $key]);
    }


    //用户登录
    public function login(Request $request)
    {
        // email 明文，password 为前端用公钥加密后的 base64 密文，后端解密得到明文
        $email = $request->post('email');
        $password = $request->post('password');
        if ($password) {
            $plain = Rsa::decrypt($password);
            if ($plain === null) {
                return json(['code' => 0, 'msg' => self::t($request, '密码错误')]);
            }
            $password = $plain;
        }

        if(empty($email)||empty($password)){
            return json(['code' => 0, 'msg' => self::t($request, '参数错误')]);
        }
        $older = Db::name('users')->where('email',$email)->find();
        if(empty($older)){
            return json(['code' => 0, 'msg' => self::t($request, '邮箱未注册')]);
        }
       
        if (!Util::passwordVerify($password, $older['password'])) {
            return json(['code' => 0, 'msg' => self::t($request, '密码错误')]);
        }
        
        if(empty($older['status'])){
            return json(['code' => 0, 'msg' => self::t($request, '账户禁用')]);
        }

        $token = random('unique');
        $older['token'] = $token;
        Db::name('users')->update($older);
        return json(['code' => 1, 'msg' => self::t($request, '登陆成功'),'data'=>$token]);
    }
    //修改密码
    public function forget(Request $request){
        $email = $request->post('email');
        $code = $request->post('code');
        $password = $request->post('password');
        if(empty($email)||empty($code)||empty($password)){
            return json(['code' => 0, 'msg' => self::t($request, '参数错误')]);
        }
        $older = Db::name('users')->where('email',$email)->find();
        if(empty($older)){
            return json(['code' => 0, 'msg' => self::t($request, '邮箱未注册')]);
        }
        
        $check = Email::check($email,$code,'forget');
        if(empty($check)){
            return json(['code' => 0, 'msg' => self::t($request, '验证码错误')]);
        }

        $res = Db::name('users')->update(['id'=>$older['id'],'password'=>Util::passwordHash($password),'token'=>'']);
        return json(['code' => 1, 'msg' => '','data'=>$res]);
    }
    //修改密码
    public function change_pwd(Request $request){
        $password = $request->post('password');
        $new_password = $request->post('new_password');
        $step = $request->post('step');
        
        $user = $request->input('user_info');
        $older = Db::name('users')->where('id',$user['id'])->find();
        if(empty($older)){
            return json(['code' => 0, 'msg' => self::t($request, '邮箱未注册')]);
        }
        if($step==1||$step==2){
            if (!Util::passwordVerify($password, $older['password'])) {
                return json(['code' => 0, 'msg' => self::t($request, '旧密码错误')]);
            }
        }
        
        if($step==2){
            $res = Db::name('users')->update(['id'=>$older['id'],'password'=>Util::passwordHash($new_password)]);
            return json(['code' => 1, 'msg' => '','data'=>$res]);
        }
        return json(['code' => 1, 'msg' => '']);
    }
    //修改邮箱
    public function change_email(Request $request){
        //$password = $request->post('password');
        $email = $request->post('email');
        $code = $request->post('code');
        if(empty($email)||empty($code)){
            return json(['code' => 0, 'msg' => self::t($request, '参数错误')]);
        }
        $older = Db::name('users')->where('email',$email)->find();
        if(!empty($older)){
            return json(['code' => 0, 'msg' => self::t($request, '邮箱已注册')]);
        }
        
        $check = Email::check($email,$code,'change');
        if(empty($check)){
            return json(['code' => 0, 'msg' => self::t($request, '验证码错误')]);
        }
        $user = $request->input('user_info');
        $res = Db::name('users')->update(['id'=>$user['id'],'email'=>$email]);
        return json(['code' => 1, 'msg' => '','data'=>$res]);
    }
    
    
    
    
    
    //个人信息
    public function userinfo(Request $request){
        
        $user = $request->input('user_info');
        return json(['code' => 1, 'msg' => '','data'=>$user]);
    }
    //编辑信息
    public function useredit(Request $request){
        $nickname = $request->input('nickname');
        $avatar = $request->input('avatar');
        $user = $request->input('user_info');
        $distance = $request->input('distance');
        $speed= $request->input('speed');
        $up = [];
        $up['id'] = $user['id'];
        if($nickname)$up['nickname'] = $nickname;
        if($avatar)$up['avatar'] = $avatar;
        if($distance)$up['distance'] = $distance;
        if($speed)$up['speed'] = $speed;
        
        Db::name('users')->update($up);
        return json(['code' => 1, 'msg' => self::t($request, '保存成功')]);
    }
    public function equipment_check(Request $request){
        $uuid  = $request->input('uuid');
        $up = [];
        $up['uuid'] = $uuid;
        $log = Db::name('equipment')->where($up)->find();
        if($log)return json(['code' => 0, 'msg' => self::t($request, '设备已被其他用户绑定，解绑后在使用')]);
        return json(['code' => 1, 'msg' => self::t($request, '新设备')]);
    }
    //绑定设备
    public function equipment_add(Request $request){
        $title = $request->input('title');
        $uuid  = $request->input('uuid');
        $user = $request->input('user_info');
        $up = [];
        $up['uuid'] = $uuid;
        $log = Db::name('equipment')->where($up)->find();
        
        if(!$log){
            $up['user_id'] = $user['id'];
            $up['title'] = $title;
            $up['createtime'] = time();
            Db::name('equipment')->insert($up);
            return json(['code' => 1, 'msg' => self::t($request, '添加成功')]);
        }
        return json(['code' => 0, 'msg' => self::t($request, '设备已被其他用户绑定，解绑后在使用')]);
       
    }
    
     public function equipment_del(Request $request){
        $id = $request->input('id');
        $user = $request->input('user_info');
        $up = [];
        $up['user_id'] = $user['id'];
        $up['id'] = $id;
        $log = Db::name('equipment')->where($up)->find();
        if(!$log){
            return json(['code' => 0, 'msg' => self::t($request, '设备不存在')]);
        }
        Db::name('equipment')->where('id',$id)->delete();
        return json(['code' => 1, 'msg' => self::t($request, '删除成功')]);
    }
    //绑定记录
    public function equipment_list(Request $request){
        $user = $request->input('user_info');
        $up = [];
        $up['user_id'] = $user['id'];
        $list = Db::name('equipment')->where($up)->select();
        return json(['code' => 1,'data'=>$list]);
    }
    //设备参数设置
    public function equipment_init(Request $request){
        $user = $request->input('user_info');
        $up = $request->post();
        $id = $up['id']??0;
        unset($up['id']);
        Db::name('equipment')->where('user_id',$user['id'])->where('id',$id)->update($up);
        return json(['code' => 1,'msg'=>self::t($request, '设置成功')]);
    }
    
    
    
    
    
    //添加地址
    public function address_add(Request $request){
        $user = $request->input('user_info');
        $lat = $request->input('lat');
        $long = $request->input('long');
        $address = $request->input('address');
        $type = $request->input('type');
        $title= $request->input('title');
        $place_id = $request->input('place_id');
        //$is_default= $request->input('is_default');
        $up = [];
        if($lat)$up['lat'] = $lat;
        if($long)$up['long'] = $long;
        if($address)$up['address'] = $address;
        if($type)$up['type'] = $type;
        if($title)$up['title'] = $title;
        if($place_id)$up['place_id'] = $place_id;
        // if($is_default){
        //     $up['is_default'] = $is_default;
        //     Db::name('address')->where('user_id',$user['id'])->where('type',$type)->update(['is_default'=>0]);
        // }
        $up['createtime'] = time();
        $up['user_id'] = $user['id'];
        Db::name('address')->insert($up);
        return json(['code' => 1,'msg'=>self::t($request, '添加成功')]);
    }
    //地址列表
    public function address_list(Request $request){
        $user = $request->input('user_info');
        $type = $request->input('type');
        $map = [];
        $map['user_id'] = $user['id'];
        $map['type'] = $type;
        $list = Db::name('address')->where($map)->order('is_default desc')->select();
        
        return json(['code' => 1,'msg'=>'','data'=>$list]);
    }
    
    
    //地址列表
    public function address_default_list(Request $request){
        $user = $request->input('user_info');
        
        $map = [];
        $map['user_id'] = $user['id'];
        //$map['is_default'] = 1;
        $data = [];
        //1家2公司3喜欢
        $map['type'] = 1;
        $data['home'] = Db::name('address')->where($map)->find();
        $map['type'] = 2;
        $data['company'] = Db::name('address')->where($map)->find();
        $map['type'] = 3;
        $data['favorite'] = Db::name('address')->where($map)->find();
        
        return json(['code' => 1,'msg'=>'','data'=>$data]);
    }
    
    
    
    //删除地址
    public function address_del(Request $request){
        $user = $request->input('user_info');
        $id = $request->input('id');
        $map = [];
        $map['user_id'] = $user['id'];
        $map['id'] = $id;
        Db::name('address')->where($map)->delete();
        return json(['code' => 1,'msg'=>self::t($request, '操作成功')]);
    }
    //默认地址
    public function address_default(Request $request){
        $user = $request->input('user_info');
        $id = $request->input('id');
        $map = [];
        $map['user_id'] = $user['id'];
        $log = Db::name('address')->where($map)->where('id',$id)->find();
        
        Db::name('address')->where($map)->where('type',$log['type'])->update(['is_default'=>0]);
        Db::name('address')->where($map)->where('id',$id)->update(['is_default'=>1]);
        return json(['code' => 1,'msg'=>self::t($request, '操作成功')]);
    }
    
    
    
    
    
    

  
   
    
    
   

    
    
    /**
     * 获取设备（摄像头）列表
     * 根据用户位置、方向，返回前方指定距离内的摄像头
     * 
     * 数据库索引建议：
     *   ALTER TABLE wa_device ADD INDEX idx_lng_lat (longitude, latitude);
     *   ALTER TABLE wa_device ADD INDEX idx_status (status);
     */
    public function devices(Request $request){
        // 1. 输入参数获取与类型转换（float 转换防 SQL 注入）
        $lat = (float) $request->input('lat');
        $long = (float) $request->input('long');
        $direction = (float) $request->input('direction', 0);
        //$maxDistance = (float) $request->input('distance', 1000);
        $route = $request->input('route');
        // 2. 参数合法性校验
        if ($lat < -90 || $lat > 90 || $long < -180 || $long > 180) {
            return json(['code' => 0, 'msg' => self::t($request, '经纬度参数无效')]);
        }
        $maxDistance = 30000;
        // if ($maxDistance <= 0 || $maxDistance > 30000) {
        //     $maxDistance = 1000; // 限制最大搜索距离 30 公里
        // }

        //Log::info('ipdevices:'.$request->getRealIp().'/'.$lat.'/'.$long.'/'.$direction.'/'.$maxDistance);

        // 3. 根据实际搜索距离动态计算经纬度边界（避免固定 1 度 ≈ 111km 导致粗筛无效）
        // 1 度纬度 ≈ 111320 米；1 度经度 ≈ 111320 × cos(纬度) 米
        $metersPerDegLat = 111320.0;
        $metersPerDegLng = 111320.0 * cos(deg2rad($lat));
        $latRadius = ($maxDistance / $metersPerDegLat) * 1.2;  // 乘 1.2 作为安全边界
        $lngRadius = ($maxDistance / max($metersPerDegLng, 0.001)) * 1.2;

        // 4. 数据库查询：经纬度粗筛（略放大以覆盖附加坐标预警点）→ 距离过滤 → 排序
        $sqlMaxDistance = $maxDistance * 1.5;
        $list = Db::name('device')
            ->field("id, type, speed, longitude, latitude, direction, dirtype, camera_id, city, street, additionalcoordinate,
                     ST_Distance_Sphere(point({$long}, {$lat}), point(longitude, latitude)) AS distance")
            
            ->where("longitude BETWEEN ? AND ?", [$long - $lngRadius, $long + $lngRadius])
            ->where("latitude BETWEEN ? AND ?", [$lat - $latRadius, $lat + $latRadius])
            ->where("ST_Distance_Sphere(point({$long}, {$lat}), point(longitude, latitude)) < ?", [$sqlMaxDistance])
            ->order('distance ASC')
            ->limit(200)
            ->select();
        
        // 5. SCDB 方向匹配 + 附加坐标预警点：仅保留车辆前方且行驶方向符合监控方向的设备
        $vehicle = ['lat' => $lat, 'lng' => $long, 'bearing' => $direction];
        
        if($route){
            if(!is_array($route))$route = json_decode($route,1);
            $return = Location::filterCamerasBySide($vehicle, $list, $route,'right',$maxDistance);
            //$return = Location::filterCamerasByRoute($vehicle, $list, $route, $maxDistance);
        }else{
            $return = Location::filterCamerasInFront($vehicle, $list, $maxDistance);
        }
        
        return json(['code' => 1, 'msg' => self::t($request, '成功'), 'data' => $return['result']??[] ]);
    }
   
    
    
    /**
     * 获取事件列表
     * 根据用户位置、方向，返回前方指定距离内的事件
     */
    public function events(Request $request){
        // 1. 输入参数获取与类型转换（float 转换防 SQL 注入）
        $lat = (float) $request->input('lat');
        $long = (float) $request->input('long');
        $direction = (float) $request->input('direction', 0);
        //$maxDistance = (float) $request->input('distance', 1000);
        $route = $request->input('route');
        // 2. 参数合法性校验
        if ($lat < -90 || $lat > 90 || $long < -180 || $long > 180) {
            return json(['code' => 0, 'msg' => self::t($request, '经纬度参数无效')]);
        }
        $maxDistance = 30000;
        // 1 度纬度 ≈ 111320 米；1 度经度 ≈ 111320 × cos(纬度) 米
        $metersPerDegLat = 111320.0;
        $metersPerDegLng = 111320.0 * cos(deg2rad($lat));
        $latRadius = ($maxDistance / $metersPerDegLat) * 1.2;  // 乘 1.2 作为安全边界
        $lngRadius = ($maxDistance / max($metersPerDegLng, 0.001)) * 1.2;

        // 4. 数据库查询：经纬度范围粗筛 → 精确距离过滤 → 排序
        $list = Db::name('device_event')
            ->field("id, type, speed, longitude, latitude, direction, directiontype as dirtype,city,street, 
                     ST_Distance_Sphere(point({$long}, {$lat}), point(longitude, latitude)) AS distance")
            ->where("longitude BETWEEN ? AND ?", [$long - $lngRadius, $long + $lngRadius])
            ->where("latitude BETWEEN ? AND ?", [$lat - $latRadius, $lat + $latRadius])
            ->where("ST_Distance_Sphere(point({$long}, {$lat}), point(longitude, latitude)) < ?", [$maxDistance])
            ->order('distance ASC')
            ->limit(200)  // 防止极端情况返回过多数据
            ->select();

        // 5. 方向匹配：仅保留车辆前方且行驶方向符合监控方向的事件
        $vehicle = ['lat' => $lat, 'lng' => $long, 'bearing' => $direction];
        
        if($route){
            if(!is_array($route))$route = json_decode($route,1);
            $return = Location::filterCamerasBySide($vehicle, $list, $route, 'right', $maxDistance);
            //$return = Location::filterCamerasByRoute($vehicle, $list, $route, $maxDistance);
        }else{
            $return = Location::filterCamerasInFront($vehicle, $list, $maxDistance);
        }
        
        
        //$return = Location::filterCamerasInFront($vehicle, $list, $maxDistance);
        return json(['code' => 1, 'msg' => self::t($request, '成功'), 'data' => $return['result']??[]]);
    }   
    
    
    
    
    public function feedtype(Request $request){
        $name = 'system_config';
        $config = Db::name('options')->where('name', $name)->value('value');
        $data =  json_decode($config, true);
        $feedtype = $data['logo']['feedtype'];
        return json(['code' => 1, 'msg' => self::t($request, '成功'),'data'=>$feedtype]);
    }
    public function feedback(Request $request){
        $input = $request->post();
        $input['createtime'] = time();
        $input['created_at'] = date('Y-m-d H:i:s');
        $input['updated_at'] = date('Y-m-d H:i:s');
        $res = Db::name('feedback2')->insert($input);
        return json(['code' => 1, 'msg' => self::t($request, '成功'),'data'=>$res]);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}
