<?php

namespace App\Http\Middleware;

use Closure;

class LogPageview
{
    /**
     * Handle an incoming request.
     * 记录用户的访问记录
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //echo '<pre>';print_r($_SERVER);echo '</pre>';die;
        $request_uri = $_SERVER['REQUEST_URI'];         //当前访问的路径
        echo "当前访问的路径： ". $request_uri;die;
        //TODO 记录到数据库
        return $next($request);
    }
}
