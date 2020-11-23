<?php

namespace App\Http\Controllers;

use App\Model\GoodsModel;
use App\Model\IndexModel;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function test1(){
        $goods_info=[
            'goods_id'=>'1111',
            'goods_name'=>'iphone',
            'price'=>'1411',
        ];
        echo json_encode($goods_info);
    }

    public function goods(Request $request){
        $page_size=$request->get('size');
        $g=IndexModel::select("goods_id","goods_name","goods_price","goods_img","goods_number")
            ->paginate($page_size);


        $response=[
            'errno'=>0,
            'msg'=>'ok',
            'data'=>[
                'list'=>$g->items()
            ]
        ];
        return $response;
    }
}
