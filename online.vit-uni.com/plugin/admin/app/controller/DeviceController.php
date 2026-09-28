<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\Device;


use plugin\admin\app\model\DeviceBook;
use plugin\admin\app\model\DeviceEvent;

use plugin\admin\app\model\User;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use support\think\Db;

use plugin\admin\app\common\Tree;

/**
 * 用户管理 
 */
class DeviceController extends Crud
{
    
    /**
     * @var User
     */
    protected $model = null;
    /**
     * 不需要验证权限的方法
     * @var string[]
     */
    protected $noNeedAuth = ['book','talk','message','doc','video','mp3','zip','images','acon'];
    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Device;
    }
    
    
    public function acon(Request $request): Response
    {
        $id = $request->get('id');
        return raw_view('device/acon',['id'=>$id]);
    }
    
    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('device/index');
    }

    public function select(Request $request): Response
    {
        // if($request->get('user_nickname')){
        //     $user_ids = Db::name('users')->where('nickname','like','%'.$request->get('user_nickname').'%')->column('id');
        //     $get = $request->get();
        //     $get['user_id'] = ['in',$user_ids];
        //     $request->setGet($get);
        // }
        return parent::select($request);
    }
    
    
    public function treelist(Request $request): Response
    {
        $user = Db::name('users')->select();

        $tree = [];
        foreach ($user as $k=>$v){
            $tree[] = ['id'=>$v['username'],'code'=>'','parameter'=>'','created_at'=>'','address'=>'','user_id'=>-1,'username'=>-1];
            $device = Db::name('device')->where('user_id',$v['id'])->select();
            foreach ($device as $k2=>$v2){
                $v2['username'] = $v['username'];
                $tree[] = $v2;
            }
        }

        return $this->json(0, 'ok',$tree);
    }
    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return raw_view('device/update');
    }
    
    
    
    
    
    
    
    
    //设备类型
    public function book(): Response
    {
        return raw_view('device/book');
    }
    public function book_ajax(Request $request): Response
    {
        $this->model = new DeviceBook;
        return parent::select($request);
    }
   
    public function book_add(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $this->model = new DeviceBook;
            return parent::insert($request);
        }
        return raw_view('device/book_add');
    }
    
    
    
    public function book_delete(Request $request): Response
    {
        $id = $request->post('id');
        Db::name('device_book')->delete($id);
        return $this->json(0, 'ok');
    }
    

  
    
     //设备类型
    public function mp3(): Response
    {
      
        return raw_view('device/mp3');
    }
    public function mp3_ajax(Request $request): Response
    {
        $this->model = new DeviceEvent;
        return parent::select($request);
    }

    

}
