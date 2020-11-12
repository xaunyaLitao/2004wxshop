<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\UserModel;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Model\GithubUserModel;

class UserController extends Controller
{

    /**
     * 注册 View
     */
    public function regist()
    {
        return view('user.regist');
    }

    /**
     * 注册 逻辑
     */
    public function registDo(Request $request)
    {

        //表单验证
        $validate = $request->validate([
            'user_name'     => 'required | min:5',
            'user_email'    => 'required | email',
            'user_mobile'   => 'required | digits:11',
            'pass'          => 'required | min:6 ',
            'pass_confirmation'    => 'required | min:6 | same:pass',
        ]);

        //生成密码
        $pass = password_hash($request->post('pass'),PASSWORD_BCRYPT);

        //入库
        $u = [
            'user_name' => $request->post('user_name'),
            'mobile'    => $request->post('user_mobile'),
            'email'     => $request->post('user_email'),
            'password'  => $pass
        ];

        $uid = UserModel::insertGetId($u);

        //生成激活码
        $active_code = Str::random(64);
        //保存激活码与用户的对应关系 使用有序集合
        $redis_active_key = 'ss:user:active';
        Redis::zAdd($redis_active_key,$uid,$active_code);


        $active_url = env('APP_URL').'/user/active?code='.$active_code;
        echo $active_url;die;

        //注册成功跳转登录
        if($uid)
        {
            return redirect('/user/login');
        }

        return redirect('/user/regist');

    }


    /**
     * 用户登录
     */
    public function login()
    {
        $data = [
            'login_url' => 'https://github.com/login/oauth/authorize?client_id=f84162d2f8c36be252c9'
        ];
        return view('user.login',$data);
    }

    /**
     * 用户登录 后台
     */
    public function loginDo(Request $request)
    {


        $user_name = $request->input('user_name');
        $user_pass = $request->input('user_pass1');

        $key = 'login:count:'.$user_name;
        //检测用户是否已被锁定
        $count = Redis::get($key);

        if($count>=5)
        {
            Redis::expire($key,3600);
            echo "输入密码错误次数太多，用户已被锁定1小时，请稍后再试";
            die;
        }


        $u = UserModel::where(['user_name'=>$user_name])
            ->orWhere(['email'=>$user_name])
            ->orWhere(['mobile'=>$user_name])->first();

        if(empty($u))   //用户不存在
        {
            die("用户不存在");
        }

        //验证密码
        $p = password_verify($user_pass,$u->password);
        if(!$p)
        {
            //密码不正确  记录错误次数
            Redis::incr($key);
            Redis::expire($key,600);            //10分钟
            echo "密码不正确";die;
        }

        //登录成功
        echo "登录成功，正在跳转至个人中心";
        // 记录登录信息
        $key = 'login:time:'.$u->user_id;
        Redis::rpush($key,time());

        //写入session
        session(['uid'=>$u->user_id]);


    }

    /**
     * 激活用户
     */
    public function active(Request  $request)
    {
        $active_code = $request->get('code');
        echo "激活码：".$active_code;echo '</br>';

        $redis_active_key = 'ss:user:active';
        $uid = Redis::zScore($redis_active_key,$active_code);
        if($uid){
            echo "uid: ". $uid;echo '</br>';

            //激活用户
            UserModel::where(['user_id'=>$uid])->update(['is_validated'=>1]);
            echo "激活成功";

            //删除集合中的激活码
            Redis::zRem($redis_active_key,$active_code);
        }else{
            echo "没有此用户";
        }

    }

    /**
     * GITHUB登录
     */

    public function githubLogin(Request $request)
    {

        // 接收code
        $code = $_GET['code'];

        //换取access_token
        $token = $this->getAccessToken($code);
        //获取用户信息
        $git_user = $this->getGithubUserInfo($token);

        //判断用户是否已存在，不存在则入库新用户
        $u = GithubUserModel::where(['guid'=>$git_user['id']])->first();
        if($u)          //存在
        {
            // TODO 登录逻辑
            $this->webLogin($u->uid);

        }else{          //不存在

            //在 用户主表中创建新用户  获取 uid
            $new_user = [
                'user_name' => Str::random(10)              //生成随机用户名，用户有一次修改机会
            ];
            $uid = UserModel::insertGetId($new_user);

            // 在 github 用户表中记录新用户
            $info = [
                'uid'                   => $uid,       //作为本站新用户
                'guid'                  => $git_user['id'],         //github用户id
                'avatar'                =>  $git_user['avatar_url'],
                'github_url'            =>  $git_user['html_url'],
                'github_username'       =>  $git_user['name'],
                'github_email'          =>  $git_user['email'],
                'add_time'              =>  time()
            ];

            $guid = GithubUserModel::insertGetId($info);        //插入新纪录

            // TODO 登录逻辑
            $this->webLogin($uid);
        }

        //将 token 返回给客户端
        return redirect('/user/center');       //登录成功 返回首页

    }

    /**
     * 根据code 换取 token
     */
    protected function getAccessToken($code)
    {
        $url = 'https://github.com/login/oauth/access_token';

        //post 接口  Guzzle or  curl
        $client = new Client();
        $response = $client->request('POST',$url,[
            'verify'    => false,
            'form_params'   => [
                'client_id'         => env('OAUTH_GITHUB_ID'),
                'client_secret'     => env('OAUTH_GITHUB_SEC'),
                'code'              => $code
            ]
        ]);
        parse_str($response->getBody(),$str); // 返回字符串 access_token=59a8a45407f1c01126f98b5db256f078e54f6d18&scope=&token_type=bearer
        return $str['access_token'];
    }

    /**
     * 获取github个人信息
     * @param $token
     */
    protected function getGithubUserInfo($token)
    {
        $url = 'https://api.github.com/user';
        //GET 请求接口
        $client = new Client();
        $response = $client->request('GET',$url,[
            'verify'    => false,
            'headers'   => [
                'Authorization' => "token $token"
            ]
        ]);
        return json_decode($response->getBody(),true);
    }

    /**
     * WEB登录逻辑
     */
    protected function webLogin($uid)
    {

        //将登录信息保存至session uid 与 token写入 seesion
        session(['uid'=>$uid]);

    }

    /**
     * 用户中心 登录状态才可访问
     */
    public function center()
    {
        $uid = session()->get('uid');

        return view('user.home-index');

    }
    public function quit(Request $request){
        $request ->session()->flush();

        return redirect('user/login');
    }
}
