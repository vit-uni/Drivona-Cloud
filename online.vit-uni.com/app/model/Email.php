<?php
namespace app\model;

use support\think\Model;
use plugin\email\api\Email as PluginEmail;
class Email extends Model
{
    

    /**
     * 发送验证码
     *
     * @param   int    $email 手机号
     * @param   int    $code   验证码,为空时将自动生成4位数字
     * @param   string $event  事件
     * @return  boolean
     */
    public static function send($email,$event,$ip,$lang = 'zh')
    {
        $code = random('numeric',4);
        $time = time();
        
        $sms = self::create(['event' => $event, 'email' => $email, 'code' => $code, 'ip' => $ip, 'createtime' => $time]);
        
        // 根据语言选择邮件模板：zh => captcha / en => captcha_en / de => captcha_de
        $templateMap = ['en' => 'captcha_en', 'de' => 'captcha_de'];
        $templateName = $templateMap[$lang] ?? 'captcha';
        
        // 重置密码使用独立模板（主题与正文均与注册不同）
        if ($event === 'forget') {
            $templateName = str_replace('captcha', 'captcha_forget', $templateName);
        }
        
        PluginEmail::sendByTemplate($email, $templateName, [
            'code' => $code,
            'verification_code' => $code,
            'minutes' => 5,
        ]);
    }



    /**
     * 校验验证码
     *
     * @param   int    $email 手机号
     * @param   int    $code   验证码
     * @param   string $event  事件
     * @return  boolean
     */
    public static function check($email, $code, $event = 'default')
    {

        $expire = 300;
        $maxCheckNums = 10;
    
        //if($code==6666)return true;
        $time = time() - $expire;
        $sms = self::where(['email' => $email, 'event' => $event])
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
                self::flush($email, $event);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 清空指定手机号验证码
     *
     * @param   int    $email 手机号
     * @param   string $event  事件
     * @return  boolean
     */
    public static function flush($email, $event = 'default')
    {
        self::where(['email' => $email, 'event' => $event])
            ->delete();
        return true;
    }
    
    
    
}