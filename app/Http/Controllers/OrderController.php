<?php

namespace App\Http\Controllers;

use App\Model\CartModel;
use App\Model\GoodsModel;
use App\Model\OrderGoodsModel;
use App\Model\OrderModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{

    /**
     * 生成订单
     */
    public function add()
    {
        //TODO 获取购物车中的商品（根据当前用户id）
        $uid = session()->get('uid');
        $cart_goods = CartModel::where(['uid'=>$uid])->get();

        if(empty($cart_goods))      //空购物车
        {

        }

        $cart_goods_arr = $cart_goods->toArray();
        //TODO 生成订单号 计算订单总价  记录订单信息（订单表orders）

        //echo '<pre>';print_r($cart_goods->toArray());echo '</pre>';
        $total = 0;
        foreach ($cart_goods_arr as $k=>$v)
        {
            //查询商品表的实时价格
            $g = GoodsModel::find($v['goods_id']);
            //echo '<pre>';print_r($g->toArray());echo '</pre>';die;
            $total += $g->shop_price;
            $v['goods_price'] = $g->shop_price;
            $v['goods_name'] = $g->goods_name;
            $order_goods[] = $v;

        }

        $order_data = [
            'order_sn'  => strtolower(Str::random(20)),     //订单唯一编号
            'user_id'   => $uid,
            'order_amount'  => $total,
            'add_time'  => time(),
            //...
        ];

        $oid = OrderModel::insertGetId($order_data);        //订单入库

        // 记录订单商品  （订单商品表orders_goods）
        foreach($order_goods as $k=>$v)
        {
            $goods = [
                'order_id'  => $oid,
                'goods_id'  => $v['goods_id'],
                'goods_name'    => $v['goods_name'],
                'goods_price'   => $v['goods_price']
            ];

            OrderGoodsModel::insertGetId($goods);
        }


        //TODO 清空购物车
        CartModel::where(['uid'=>$uid])->delete();

        //TODO 跳转至 支付页面
        return redirect('/pay/ali?oid='.$oid);

    }

}
