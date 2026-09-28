<?php
namespace app\middleware;

use ReflectionClass;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\think\Db;

class AccessControl implements MiddlewareInterface
{

    public function process(Request $request, callable $handler): Response
    {
        $token = $request->header('token');

        
        $controller = new ReflectionClass($request->controller);
        $noNeedLogin = $controller->getDefaultProperties()['noNeedLogin'] ?? [];
        
        
        if(in_array('*', $noNeedLogin)){
            $response = $handler($request);
            return $response;
        }
        // 访问的方法需要登录
        if (!in_array($request->action, $noNeedLogin)) {
            
            if(empty($token)){
                return json(['code' => 401, 'msg' => '登录失效', 'data' => []]);
            }
        
        
            // 拦截请求，返回一个重定向响应，请求停止向洋葱芯穿越
            $user = Db::name('users')->field('id,nickname,avatar,token,status,email,distance,speed')->where('token',$token)->find();
            if(empty($user))return json(['code' => 401, 'msg' => '登录失效', 'data' => []]);
            $params =  $request->get(); 
            $params['user_id'] = $user['id'];
            $params['user_info'] = $user;
            $request->setGet($params);
        }
        
        

        $response = $handler($request);
        return $response;

    }

}
