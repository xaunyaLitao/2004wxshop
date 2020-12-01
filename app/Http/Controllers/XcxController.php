<?php

namespace App\Http\Controllers;

use App\Model\IndexModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\XcxModel;
use App\Model\XcxCartModel;
use Illuminate\Support\Facades\DB;
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

//    小程序商品详情
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


//    小程序加入购物车
    public function addcart(Request $request){
        $goods_id = $request->post('goodsid');
//        dd($goods_id);die;
        $uid = $_SERVER['uid'];

        //查询商品的价格
        $price = IndexModel::find($goods_id)->goods_price;

        //判断购物车中商品你是否已存在
        $g = XcxCartModel::where(['goods_id'=>$goods_id])->first();
        if($g)      //增加商品数量
        {
            XcxCartModel::where(['goods_id'=>$goods_id])->increment('goods_number');
            $response = [
                'errno' => 0,
                'msg'   => 'ok'
            ];
        }else{
            //将商品存储购物车表 或 Redis
            $info = [
                'goods_id'  => $goods_id,
                'uid'       => $uid,
                'goods_number' => 1,
                'add_time'  => time(),
                'cart_price' => $price
            ];

            $id = XcxCartModel::insertGetId($info);
            if($id)
            {
                $response = [
                    'errno' => 0,
                    'msg'   => 'ok'
                ];
            }else{
                $response = [
                    'errno' => 50002,
                    'msg'   => '加入购物车失败'
                ];
            }
        }




        return $response;
    }


//    小程序购物车列表
    public function cartlist(Request $request){
//        接收uid
        $uid=$_SERVER['uid'];
//        查询购物车表中 是否有这个用户
        $goods = XcxCartModel::where('uid',$uid)->get();
        if($goods)      //购物车有商品
        {
            $goods = $goods->toArray();


            foreach($goods as $k=>&$v)
            {
                $g = IndexModel::select("goods_img","goods_name")->find($v['goods_id']);
                $v['goods_name'] = $g->goods_name;
                $v['goods_img']=explode(",",$g['goods_img']);
            }
        }else{          //购物车无商品
            $goods = [];
        }

        //echo '<pre>';print_r($goods);echo '</pre>';die;
        $response = [
            'errno' => 0,
            'msg'   => 'ok',
            'data'  => [
                'list'  => $goods
            ]
        ];

        return $response;
    }


//    商品收藏
public function addfav(Request $request){
    $goods_id = $request->get('id');
    //加入收藏 Redis有序集合
    $uid=$_SERVER['uid'];
    $redis_key = 'ss:goods:fav:'.$uid;      // 用户收藏的商品有序集合
    Redis::Zadd($redis_key,time(),$goods_id);       //将商品id加入有序集合，并给排序值

    $response = [
        'errno' => 0,
        'msg'   => 'ok'
    ];

    return $response;
}


    public function eve(){
        return view('test.eve');
    }


//    购物车商品删除
public function cartdel(Request $request){
    $goods_id = $request->post('goods');
    $goods_arr =  explode(',',$goods_id);

    $res = XcxCartModel::whereIn('goods_id',$goods_arr)->delete();
    if($res)        //删除成功
    {
        $response = [
            'errno' => 0,
            'msg'   => 'ok'
        ];
    }else{
        $response = [
            'errno' => 500002,
            'msg'   => '内部错误'
        ];
    }
    return $response;

}
}
