<?php

namespace App\Http\Middleware;

use Closure;

class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $uid = session()->get('uid');

        //判断是否是Ajax请求
        if(empty($uid))     // 0 [] {} false "" null
        {

            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
            {
                $response = [
                    'errno' => 400003,
                    'msg'   => "请先登录"
                ];
                die(json_encode($response));
            }

            return redirect('/user/login');
        }
        return $next($request);
    }
}
