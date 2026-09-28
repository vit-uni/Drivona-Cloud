<?php
namespace support;
use support\Request;
use Webman\RedisQueue\Redis as RedisModel;

class Redis
{
    public static function db_insert($data)
    {
        // 队列名
        $queue = 'db_insert';
        // 投递消息
        //Client::send($queue, $data);

        return RedisModel::send($queue, $data);
    }
    
    

}