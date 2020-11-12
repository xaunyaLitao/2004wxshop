<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function http_get($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);//向那个url地址上面发送
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);//设置发送http请求时需不需要证书
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置发送成功后要不要输出1 不输出，0输出
        $output = curl_exec($ch);//执行
        curl_close($ch);    //关闭
        return $output;
    }

    public function http_post($url,$data){
        $curl = curl_init(); //初始化
        curl_setopt($curl, CURLOPT_URL, $url);//向那个url地址上面发送
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);//需不需要带证书
        curl_setopt($curl, CURLOPT_POST, 1); //是否是post方式 1是，0不是
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//需不需要输出
        $output = curl_exec($curl);//执行
        curl_close($curl); //关闭
        return $output;
    }

    //  微信平台 access_token接口
    public function get_access_token(){
        // 先查看 redis中 token是否过期，或者第一生成  就生成 token
        //不存在 或 超过7100秒重新获取

        if(!Redis::get("token")){
            //获取 access_token
            $appId="wxc8e73af28fb246ce";//唯一的,该公众号的ID
            $appsecret="e3b11750e1de175e6f94cde4ebdfed72";//唯一的,该公众号的 appsecret
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appsecret;
            $result=$this->http_get($url);
            //file_put_contents("ccc.txt",$result);//测试 入文件
            $result=json_decode($result,true);//

            //判断有没有获取 access_token 成功
            if(isset($result["access_token"])){
                //添加到数据库中
                Redis::setex("token",7100,$result["access_token"]);
                return $result["access_token"];//重新返回数据 access_token
//                file_put_contents("aaa.txt",Redis::get("token"));
            }else{
                return false;
            }

        }else{  //走该分支 说明 access_token存在并且没有过期
            return Redis::get("token");
        }
        //file_put_contents("text.txt",$result);//调试

    }
}
