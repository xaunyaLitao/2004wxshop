<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\PrizeModel;

class PrizeController extends Controller
{

    public function index()
    {
        return view('prize.index');
    }


    /**
     * 开始抽奖
     */
    public function add()
    {

        $uid = session()->get('uid');

        //检查用户当天是否已有抽奖记录
        $time1 = strtotime(date("Y-m-d"));
        $res = PrizeModel::where(['uid'=>$uid])->where("add_time",">=",$time1)->first();

        $rand = mt_rand(1,1000);

        $level = 0;
        if($rand>=1 && $rand<=10)
        {
            //一等奖
            $level = 1;
        }elseif ($rand >=11 && $rand <=30){            //二等奖
            $level = 2;
        }elseif($rand>=31 && $rand<=60){
            // 三等奖
            $level = 3;
        }


        //记录抽奖信息
        $prize_data = [
            'uid'   => $uid,
            'level' => $level,
            'add_time'  => time()
        ];

        $pid = PrizeModel::insertGetId($prize_data);

        //是否记录成功
        if($pid>0)
        {
            $data = [
                'errno' => 0,
                'msg'   => "一等奖",
                'data'  => [
                    'level' => $level,       //中奖等级
                ]
            ];

        }else{
            //异常
            $data = [
                'errno' => 500008,
                'msg'   => "数据异常，请重试"
            ];
        }


        return $data;

    }
}
