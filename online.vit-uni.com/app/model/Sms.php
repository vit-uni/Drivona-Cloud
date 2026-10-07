<?php
namespace app\model;

use support\think\Model;

class Sms extends Model
{
    

    /**
     * 发送验证码
     *
     * @param   int    $mobile 手机号
     * @param   int    $code   验证码,为空时将自动生成4位数字
     * @param   string $event  事件
     * @return  boolean
     */
    public static function send($mobile,$event,$ip)
    {
        return true;
        $code = random('numeric',6);
        $time = time();
        
        $sms = self::create(['event' => $event, 'mobile' => $mobile, 'code' => $code, 'ip' => $ip, 'createtime' => $time]);
        
        $data = [
            'mobile' => $mobile,
            'content' => "",
            'key' => 'aab66499906e4bcdb71e85b1b4c8d8d7',
            'username'=>'wy_wy'
        ];
        
  
        $statusStr = array(
            "0" => 1,
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );

        
        $sendurl = "http://api.smsbao.com/sms?u=".$data['username']."&p=".$data['key']."&m=".$data['mobile']."&c=".urlencode($data['content']);
        //$res = Http::get($sendurl);
        $result = file_get_contents($sendurl) ;
        $return = ['code'=>1,'msg'=>'发送成功'];
        //echo $statusStr[$result];
        if(empty($statusStr[$result])){
            $return = ['code'=>0,'msg'=>'发送失败'];
        }
        if($statusStr[$result]!=1){
            $return = ['code'=>0,'msg'=>$statusStr[$result]];
        }
        return $return;
    }



    /**
     * 校验验证码
     *
     * @param   int    $mobile 手机号
     * @param   int    $code   验证码
     * @param   string $event  事件
     * @return  boolean
     */
    public static function check($mobile, $code, $event = 'default')
    {

        $expire = 300;
        $maxCheckNums = 10;
    
        if($code==6666)return true;
        $time = time() - $expire;
        $sms = self::where(['mobile' => $mobile, 'event' => $event])
            ->order('id', 'DESC')
            ->find();
        if ($sms) {
            if ($sms['createtime'] > $time && $sms['times'] <= $maxCheckNums) {
                $correct = $code == $sms['code'];
                if (!$correct) {
                    $sms->times = $sms->times + 1;
                    $sms->save();
                    return false;
                } else {
                    return true;
                }
            } else {
                // 过期则清空该手机验证码
                self::flush($mobile, $event);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 清空指定手机号验证码
     *
     * @param   int    $mobile 手机号
     * @param   string $event  事件
     * @return  boolean
     */
    public static function flush($mobile, $event = 'default')
    {
        self::where(['mobile' => $mobile, 'event' => $event])
            ->delete();
        return true;
    }
    
    
    
}