<?php

namespace app\queue\redis;

use Webman\RedisQueue\Consumer;
use support\think\Db;
use support\Log;

class DbInsert implements Consumer
{
    // 要消费的队列名
    public $queue = 'db_insert';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $type = $data['type'];
        if($type=='data'){
            $inc = $data['batch'];
            Db::table('device_new')->insertAll($inc);
            
            Log::info(sprintf(
                    'DbInsert 进度: 成功 %d 条',
                    $data['count']
                ));
                
        }else{
            // 执行原子切换表
            //Db::execute("RENAME TABLE wa_device TO device_old,device_new TO wa_device");
            //Db::execute("DROP TABLE IF EXISTS device_old");

            //$xmlFile = runtime_path() . '/device.xml';
            //@unlink($xmlFile);
            
            Log::info('DbInsert 添加完成');
        }
        
    }
    // 消费失败回调
    /* 
    $package = [
        'id' => 1357277951, // 消息ID
        'time' => 1709170510, // 消息时间
        'delay' => 0, // 延迟时间
        'attempts' => 2, // 消费次数
        'queue' => 'send-mail', // 队列名
        'data' => ['to' => 'tom@gmail.com', 'content' => 'hello'], // 消息内容
        'max_attempts' => 5, // 最大重试次数
        'error' => '错误信息' // 错误信息
    ]
    */
    public function onConsumeFailure(\Throwable $e, $package)
    {
        echo "consume failure\n";
        echo $e->getMessage() . "\n";
        // 无需反序列化
        var_export($package); 
    }
}