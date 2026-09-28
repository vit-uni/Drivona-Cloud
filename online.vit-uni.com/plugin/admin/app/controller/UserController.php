<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\Equipment;
use plugin\admin\app\model\User;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use support\Cache;

/**
 * 用户管理 
 */
class UserController extends Crud
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
        $this->model = new User;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('user/index');
    }

    /**
     * 删除用户，并同步清除其关联的设备
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $ids = $this->deleteInput($request);
        if ($ids) {
            Equipment::whereIn('user_id', $ids)->delete();
        }
        $this->doDelete($ids);
        return $this->json(0);
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
        return raw_view('user/insert');
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
                $post['token'] = '';
                $request->setPost($post);
            }
            return parent::update($request);
        }
        return raw_view('user/update');
    }
    
    public function select2(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        if(isset($where['username'])){
            $where['username'] = ['like',$where['username']];
        }
        if(isset($where['nickname'])){
            $where['nickname'] = ['like',$where['nickname']];
        }
       
        $query = $this->doSelect($where, 'id', 'desc');
        $items = $query->get();
        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[] = [
                'name' => $item->username,
                'value' => $item->id
            ];
        }
        return  $this->json(0, 'ok', $formatted_items);
    }

}
