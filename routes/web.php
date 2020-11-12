<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/info',function(){
    phpinfo();
});
Route::get('/', function () {
    //echo date('Y-m-d H:i:s');die;
    return view('welcome');
});

Route::get('/',"IndexController@home");         //网站首页

Route::get('/test/upload1',"TestController@uploadImg");
Route::post('/test/upload2',"TestController@upload2");
Route::get('/test/md5',"TestController@testMd5");
Route::get('/test/goods',"TestController@goods");
Route::get('/test/weather',"TestController@weather");
Route::get('/test/curl1',"TestController@curl1");
Route::get('/test/guzzle1',"TestController@guzzleTest1");
Route::get('/test/seat',"Test\SeatController@index");
Route::get('/test/stu',"TestController@stu");          //随机点名


Route::get('/hello','TestController@hello');
Route::get('/sql1','TestController@sql1');
Route::get('/u','TestController@u');

//Redis
Route::get('/redis1','TestController@redis1');
Route::get('/redis2','TestController@redis2');


//商品
Route::get('/goods/detail','GoodsController@detail');       //商品详情
Route::get('/goods/list','GoodsController@goodsList');       //商品列表
Route::get('/goods/fav','GoodsController@fav');       //商品收藏

//用户
Route::get('/user/regist','UserController@regist');         //注册 前台
Route::post('/user/regist','UserController@registDo');         //注册 后台
Route::get('/user/login','UserController@login');         //登录 前台
Route::get('/user/quit','UserController@quit');         //退出登录
Route::post('/user/login','UserController@loginDo');         //登录 后台
Route::get('/user/active','UserController@active');         //激活用户
Route::get('/user/center','UserController@center')->middleware('check.login');         //个人中心

Route::get('/cart','CartController@index')->middleware('check.login','log.page-view');     //购物车
Route::get('/cart/add','CartController@add')->middleware('check.login');               //加入购物车


Route::get('/order/create','OrderController@add')->middleware('check.login');          //生成订单

Route::get('/pay/ali','PayController@aliPay')->middleware('check.login');                   //订单支付(支付宝)


Route::get('/github/callback','UserController@githubLogin');               //GITHUB登录


Route::get('/prize','PrizeController@index');           //抽奖
Route::get('/prize/start','PrizeController@add')->middleware('check.login');           //开始抽奖

//优惠券
Route::get('/coupon','CouponController@index');                  //领券页面
Route::get('/coupon/get','CouponController@getCoupon')->middleware('check.login');         //领券
Route::post('/coupon/test','CouponController@test');


//微信
Route::prefix('/wx')->group(function(){
    Route::get('/','WxController@index');       //接入
    Route::post('/','WxController@wxEvent');
    Route::get('/token','WxController@getAccessToken');        //获取access_token
    Route::get('/create_menu','WxController@createMenu');        //创建菜单
    Route::get('/upload_media','WxController@uploadMedia');        //上传素材
    Route::get('/send_all','WxController@sendAll');         //群发消息

});

