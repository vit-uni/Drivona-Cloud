<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\Feedback2;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 用户管理 
 */
class Feedback2Controller extends Crud
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
        $this->model = new Feedback2;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('feedback2/index');
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
        return raw_view('feedback2/insert');
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
        return raw_view('feedback2/update');
    }

}
