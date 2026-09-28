<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\Message;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use support\Cache;
/**
 * 用户管理 
 */
class MessageController extends Crud
{
    
    /**
     * @var User
     */
    protected $model = null;
    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Message;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('message/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        return raw_view('message/insert');
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
            if($request->post('status')==0){
                
                $post = $request->post();

                $request->setPost($post);
            }
            return parent::update($request);
        }
        return raw_view('message/update');
    }

}
