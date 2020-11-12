<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayController extends Controller
{

    /**
     * 订单支付（支付宝）
     */
    public function aliPay(Request $request)
    {
        $oid = $request->get('oid');
        echo "订单ID: ". $oid;

        //根据订单号，查询订单信息，验证订单是否有效（未支付、未删除、未过期）



        //组合参数，调用支付接口，支付

        // 1 请求参数
        $param2 = [
            'out_trade_no'      => $oid,     //商户订单号
            'product_code'      => 'FAST_INSTANT_TRADE_PAY',
            'total_amount'      => 0.01,    //订单总金额
            'subject'           => '2004-测试订单-'.Str::random(16),
        ];

        // 2 公共参数
        $param1 = [
            'app_id'        => env('ALIPAY_APP_ID'),
            'method'        => 'alipay.trade.page.pay',
            'return_url'    => 'https://1910liwei.comcto.com/pay/alireturn',   //同步通知地址 真实服务器URL
            'charset'       => 'utf-8',
            'sign_type'     => 'RSA2',
            'timestamp'     => date('Y-m-d H:i:s'),
            'version'       => '1.0',
            'notify_url'    => 'https://1910liwei.comcto.com/pay/alinotify',   // 异步通知 真实服务器URL
            'biz_content'   => json_encode($param2),
        ];



        // 计算签名
        ksort($param1);

        $str = "";
        foreach($param1 as $k=>$v)
        {
            $str .= $k . '=' . $v . '&';
        }
        $str = rtrim($str,'&');     // 拼接待签名的字符串
        $sign = $this->aliSign($str);

        //沙箱测试地址
        $url = 'https://openapi.alipaydev.com/gateway.do?'.$str.'&sign='.urlencode($sign);
        return redirect($url);
    }


    /**
     * 支付宝签名
     * @param $data
     * @return string
     */
    protected function aliSign($data)
    {
        $priKey = file_get_contents(storage_path('keys/priv.key'));
        $res = openssl_get_privatekey($priKey);

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }


    /**
     * 支付宝异步通知
     */
    public function aliNotify()
    {
        //TODO 验签

        // TODO 验证订单状态是否有效（未支付）

        // TODO 修改订单状态（更新支付时间 支付金额）

    }


    /**
     * 微信支付
     */
    public function wxPay(){}


    /**
     * 微信支付异步通知
     */
    public function wxNotify(){}
}
