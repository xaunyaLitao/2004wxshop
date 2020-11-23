<?php

namespace App\Http\Controllers;

use App\Model\IndexModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\XcxModel;
class XcxController extends Controller
{
    public function Homelogin(Request $request){
        //接收code
        $code = $request->get('code');

        //使用code
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . env('WX_XCX_APPID') . '&secret=' . env('WX_XCX_SECET') . '&js_code=' . $code . '&grant_type=authorization_code';

        $data = json_decode(file_get_contents($url), true);

        //自定义登录状态
        if (isset($data['errcode']))     //有错误
        {
            $response = [
                'errno' => 50001,
                'msg' => '登录失败',
            ];

        } else {              //成功
            $openid = $data['openid'];          //用户OpenID
            //判断新用户 老用户
            $u = XcxModel::where(['openid' => $openid])->first();
            if ($u) {
                // TODO 老用户
                $uid = $u->id;
                //更新用户信息

            } else {
                // TODO 新用户
                $u_info = [
                    'openid' => $openid,
                    'add_time' => time(),
                    'type' => 3        //小程序
                ];

                $uid = XcxModel::insertGetId($u_info);
            }

            //生成token
            $token = sha1($data['openid'] . $data['session_key'] . mt_rand(0, 999999));
            //保存token
            $redis_login_hash = 'h:xcx:login:' . $token;

            $login_info = [
                'uid' => $uid,
                'user_name' => "",
                'login_time' => date('Y-m-d H:i:s'),
                'login_ip' => $request->getClientIp(),
                'token' => $token,
                'openid'    => $openid
            ];

            //保存登录信息
            Redis::hMset($redis_login_hash, $login_info);
            // 设置过期时间
            Redis::expire($redis_login_hash, 7200);

            $response = [
                'errno' => 0,
                'msg' => 'ok',
                'data' => [
                    'token' => $token
                ]
            ];
        }

        return $response;
    }


    public function userLogin(Request $request)
    {
        //接收code
        //$code = $request->get('code');
        $token = $request->get('token');

        //获取用户信息
        $userinfo = json_decode(file_get_contents("php://input"), true);

        $redis_login_hash = 'h:xcx:login:' . $token;
        $openid = Redis::hget($redis_login_hash, 'openid');          //用户OpenID

        $u0 = XcxModel::where(['openid' => $openid])->first();
//        var_dump($u0);die;
        if($u0->update_time == 0){     // 未更新过资料
            //因为用户已经在首页登录过 所以只需更新用户信息表
            $u_info = [
                'nickname' => $userinfo['u']['nickName'],
                'sex' => $userinfo['u']['gender'],
                'language' => $userinfo['u']['language'],
                'city' => $userinfo['u']['city'],
                'province' => $userinfo['u']['province'],
                'country' => $userinfo['u']['country'],
                'headimgurl' => $userinfo['u']['avatarUrl'],
                'update_time'   => time()
            ];
            XcxModel::where(['openid' => $openid])->update($u_info);
        }

        $response = [
            'errno' => 0,
            'msg' => 'ok',
        ];

        return $response;

    }




    public function tests(){
        return view('xcx.test');
    }

    public function detail(){
        $goods_id=Request()->get("goods_id");
        $detail=IndexModel::select("goods_img","goods_name","goods_price","goods_imgs","goods_id")->where("goods_id",$goods_id)->first()->toArray();

        $array = [
            "goods_name"=>$detail['goods_name'],
            "goods_price"=>$detail['goods_price'],
            "goods_img"=>explode(",",$detail['goods_imgs']),
            "goods_id"=>$detail['goods_id']
        ];
        return $array;
    }


    public function cart(){
        $goods_id=Request()->get("goods_id");
        $cart=IndexModel::select("goods_img","goods_name","goods_price","goods_id")->where("goods_id",$goods_id)->first()->toArray();

        $arrays=[
            "goods_name"=>$cart['goods_name'],
            "goods_price"=>$cart["goods_price"],
            "goods_img"=>$cart['goods_img'],
            "goods_id"=>$cart["goods_id"]
        ];
        return $arrays;
    }

}
