<?php

namespace App\Http\Controllers;

use App\Model\FavGoodsModel;
use Illuminate\Http\Request;
use App\Model\GoodsModel;
use App\Model\HistoryModel;
use Illuminate\Support\Facades\Redis;
class GoodsController extends Controller
{




    /**
     * 商品详情
     */
    public function detail(Request $request)
    {

        $uid=session()->get('uid');
//        dd($uid);
        $goods_id = $request->get('id');
        //echo "goods_id: ". $goods_id;die;
        $goods = GoodsModel::find($goods_id);

//        $goods = GoodsModel::find($goods_id);
        //缓存
        $key = 'goods_id:'.$goods_id;

        $goods = Redis::hgetAll($key);
        if(empty($goods)){
            //无缓存
            echo '无缓存';
            $goods = GoodsModel::find($goods_id);
            $goods = $goods->toArray();
            Redis::hMset($key,$goods);
            Redis::expire($key,60);

        }



        //用户浏览历史记录
        if(!empty($uid)){
            $data=[
                'uid'=>$uid,
                'goods_id'=>$goods_id,
                'history_time'=>time(),
            ];
           $res=HistoryModel::insert($data);
        }
        //验证商品是否有效（是否存在、是否下架、是否删除）
        if(empty($goods))
        {
            return view('goods.404');       //商品不存在
        }

        //是否下架
        if($goods['is_delete']==1)
        {
            return view('goods.delete');       //商品已删除
        }


        $data = [
            'g' => $goods,
        ];



        //商品浏览量 +1
        GoodsModel::where(['goods_id'=>$goods_id])->increment('click_count');

        return view('goods.detail',$data);
    }


    /**
     * 商品列表
     */
    public function goodsList()
    {
        $list = GoodsModel::limit(10)->get();

        return view('goods.list',['list'=>$list]);
    }

    /**
     * 收藏商品
     */
    public function fav(Request  $request)
    {
        $uid = session()->get('uid');
        if(empty($uid))
        {
            $res = [
                'errno' => 400003,
                'msg'   => "请先登录"
            ];

            return $res;
        }

        $id = $request->get('id');

        $data = [
            'goods_id'  => $id,
            'uid'       => $uid,
            'add_time'  => time()
        ];

        FavGoodsModel::insertGetId($data);
        $res = [
            'errno' => 0,
            'msg'   => 'ok'
        ];


        //收藏数 +1
        GoodsModel::where(['goods_id'=>$id])->increment('fav_count');
        return  $res;
    }
}
