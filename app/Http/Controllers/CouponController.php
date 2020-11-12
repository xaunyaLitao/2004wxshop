<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\CouponModel;

class CouponController extends Controller
{
    //领券页面
    public function index()
    {
        return view('coupon.index');
    }

    /**
     * 领券逻辑
     */
    public function getCoupon(Request $request)
    {

        //根据type领不同的券
        $type = $request->get('type');


//        if($type==1)
//        {
//            echo "111111";
//        }elseif($type==2)
//        {
//            echo '222222';
//        }elseif($type==3)
//        {
//            echo '333333';
//        }

        switch($type){
            case 1:             // 满 100 - 20
                $this->coupon100_20();
                break;
            case 2:
                echo "222222";
                $this->coupon100_40();      // 满 200 -40
                break;
            case 3:
                echo "333333";
                break;
            default:
                echo "参数错误";
                break;
        }


        //领券
        $response = [
            'errno' => 0,
            'msg'   => 'ok'
        ];

        return $response;
    }


    /**
     * 生成 满100-20的券
     */
    private function coupon100_20()
    {
        $uid = session()->get('uid');
        $begin_time = strtotime("2020-11-11");
        $expire_time = strtotime("2020-11-12");

        $data = [
            'uid'           => $uid,
            'add_time'      => time(),
            'begin_time'    => $begin_time,
            'expire_time'   => $expire_time,
            'type'          => 1,           // type=1  满100 - 20
        ];

        CouponModel::insertGetId($data);


    }


}
