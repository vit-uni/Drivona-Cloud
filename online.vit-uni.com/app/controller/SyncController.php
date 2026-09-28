<?php
namespace app\controller;

use support\Request;
use support\Response;
use support\think\Db;
use support\Log;
use app\process\XmlBlitzer;
use app\process\SyncBlitzer;
use Workerman\Timer;

class SyncController
{
    
    protected $noNeedLogin = ['*'];

    
    
    /**
     * 手动触发下载json文件
     */
    public function jsonStart(Request $request): Response
    {
        
        Timer::add(1, function () {
            try {
                $sync   = new SyncBlitzer();
                $result = $sync->run();
                Log::info('JSON 同步接口触发结果：' . json_encode($result, JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                Log::error('JSON 同步接口触发异常：' . $e->getMessage());
            }
        }, [], false);
        return json(['code' => 0, 'msg' => '同步任务已触发，后台执行中']);
    }
    /**
     * 手动触发json数据入库
     * 前提：blitzer_full.json 已由 jsonStart 下载完成
     */
    public function jsonInsert(Request $request): Response
    {
        Timer::add(1, function () {
            try {
                $sync   = new SyncBlitzer();
                $result = $sync->insertFromDownloadedFile();
                Log::info('JSON 入库接口触发结果：' . json_encode($result, JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                Log::error('JSON 入库接口触发异常：' . $e->getMessage());
            }
        }, [], false);

        return json(['code' => 0, 'msg' => '入库任务已触发，后台执行中']);
    }







    /**
     * 手动触发下载xml文件
     */
    public function xmlStart(Request $request){
        Timer::add(1, function () {
            try {
                $sync   = new XmlBlitzer();
                $result = $sync->run();
                Log::info('XML 同步接口触发结果：' . json_encode($result, JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                Log::error('XML 同步接口触发异常：' . $e->getMessage());
            }
        }, [], false);

        return json(['code' => 0, 'msg' => '同步任务已触发，后台执行中']);
    }
    
    public function xmlChange(Request $request){
        // 执行原子切换表
        Db::execute("RENAME TABLE wa_device TO device_old,device_new TO wa_device");
        Db::execute("DROP TABLE IF EXISTS device_old");

        $xmlFile = runtime_path() . '/device.xml';
        @unlink($xmlFile);
            
        Log::info('DbInsert 切换完成');
        return json(['code' => 0, 'msg' => 'DbInsert 切换完成']);
    }
    
    

    
}