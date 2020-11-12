<?php

namespace App\Http\Controllers;

use App\Model\WxUserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Model\UserModel;
use App\Model\HistoryModel;
class WxController extends Controller
{

    protected $xml_obj;

    public function echostr(Request $request)
    {
        $echostr = $request->echostr;
        $result = $this->checkSignature();
        if ($result) {
            echo $echostr;
            die;
        } else {
            return false;
        }
    }


    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 处理推送事件
     */
    public function wxEvent()
    {
        $str = file_get_contents("php://input");
        $obj = simplexml_load_string($str, "SimpleXMLElement", LIBXML_NOCDATA);
        // $obj=json_decode($obj, true);
        // file_put_contents("aaa.txt",$obj);
        // echo "ok";

        file_put_contents('wx_event.log', $str, FILE_APPEND);
        switch ($obj->MsgType) {
            //  关注
            case "event":
                if ($obj->Event == "subscribe") {
                    //用户扫码的 openID
                    $openid = $obj->FromUserName;//获取发送方的 openid
                    $access_token = $this->get_access_token();//获取token
                    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
                    //掉接口
                    $user = file_get_contents($url);
                    $user = json_decode($user, true);//跳方法 用get  方式调第三方类库
                    // $this->writeLog($fens);
                    if (isset($user["errcode"])) {
                        $this->writeLog("获取用户信息失败");
                    } else {
                        //查数据库有这个用户没有
                        $user_id = WxUserModel::where('openid', $openid)->first();
                        if ($user_id) {
                            $user_id->subscribe = 1;
                            $user_id->save();
                            $content = "谢谢再次回来！";
                        } else {
                            $res = [
                                "subscribe" => $user['subscribe'],
                                "openid" => $user["openid"],
                                "nickname" => $user["nickname"],
                                "sex" => $user["sex"],
                                "city" => $user["city"],
                                "country" => $user["country"],
                                "province" => $user["province"],
                                "language" => $user["language"],
                                "headimgurl" => $user["headimgurl"],
                                "subscribe_time" => $user["subscribe_time"],
                                "subscribe_scene" => $user["subscribe_scene"]
                            ];
                            WxUserModel::insert($res);
                            $content = "谢谢关注@！";
                        }
                    }
                }
                // 取消关注
                if ($obj->Event == "unsubscribe") {
                    $user_id->subscribe = 0;
                    $user_id->save();
                }
                echo $this->xiaoxi($obj, $content);
                break;

            case "text";
                //  天气
                $city = urlencode(str_replace("天气:", "", $obj->Content));   //城市
                $key = "50ad65400349c7a71553ab6b23b92acb";  //key
                $url = "http://apis.juhe.cn/simpleWeather/query?city=" . $city . "&key=" . $key;  //url地址
                $shuju = file_get_contents($url);
                $shuju = json_decode($shuju, true);
                if ($shuju["error_code"] == 0) {
                    $today = $shuju["result"]["realtime"];
                    $content = "查询天气的城市:" . $shuju["result"]["city"] . "当天天气" . "/n";  //查询的城市
                    $content .= "天气详细情况：" . $today["info"];
                    $content .= "温度：" . $today["temperature"] . "\n";
                    $content .= "湿度：" . $today["humidity"] . "\n";
                    $content .= "风向：" . $today["direct"] . "\n";
                    $content .= "风力：" . $today["power"] . "\n";
                    $content .= "空气质量指数：" . $today["aqi"] . "\n";
                    //获取一个星期的
                    $future = $shuju["result"]["future"];
                    foreach ($future as $k => $v) {
                        $content .= "日期:" . date("Y-m-d", strtotime($v["date"])) . $v['temperature'] . ",";
                        $content .= "天气:" . $v['weather'] . "\n";
                    }

                } else {
                    $content = "你的查询天气失败，你的格式是天气:城市,这个城市不属于中国";
                }

                echo $this->xiaoxi($obj, $content);
                break;


            //    图片
            case "image";
//                        file_put_contents('image.log',$str);
                $data = [
                    'tousername' => $obj->ToUserName,
                    'openid' => $obj->FromUserName,
                    'createtime' => $obj->CreateTime,
                    'msgtype' => $obj->MsgType,
                    'pricurl' => $obj->PicUrl,
                    'msgid' => $obj->MsgId,
                    'media_id' => $obj->MediaId
                ];
                HistoryModel::insert($data);


//                      下载图片
                $token = $this->get_access_token();
                $media_id = ($data['media_id']);
                $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $token . "&media_id=" . $media_id;
                $img = file_get_contents($url);
                file_put_contents("cat.jpg", $img);
                echo "";
                break;

            //   语音
            case "voice";
//                        file_put_contents("2004.txt",$str);
                $data = [
                    'tousername' => $obj->ToUserName,
                    'openid' => $obj->FromUserName,
                    'createtime' => $obj->CreateTime,
                    'msgtype' => $obj->MsgType,
                    'msgid' => $obj->MsgId,
                    'media_id' => $obj->MediaId
                ];
                HistoryModel::insert($data);


//                        下载语音
                $token = $this->get_access_token();
                $media_id = $data['media_id'];
                $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $token . "&media_id=" . $media_id;
                $voice = file_get_contents($url);
                file_put_contents("la.amr", $voice);
                echo "";
                break;

            //  视频
            case "video";
                $data = [
                    'tousername' => $obj->ToUserName,
                    'openid' => $obj->FromUserName,
                    'createtime' => $obj->CreateTime,
                    'msgtype' => $obj->MsgType,
                    'thumbmediaId' => $obj->thumbmediaId,
                    'msgid' => $obj->MsgId,
                    'media_id' => $obj->MediaId
                ];
                HistoryModel::insert($data);

//                        下载视频
                $token = $this->get_access_token();
                $media_id = $data['media_id'];
                $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $token . "&media_id=" . $media_id;
                $video = file_get_contents($url);
                file_put_contents("li.mp4", $video);
                echo "";
                break;



//                        case "签到";
//                        if($obj->Event=="CLICK") {
//                            if ($obj->EventKey == "Li") {
//                                $key = $obj->FromUserName;
//                                $times = date("Y-m-d", time());
//                                $date = Redis::zrange($key, 0, -1);
//                                if ($date) {
//                                    $date = $date[0];
//                                }
//
//                                if ($date == $times) {
//                                    $content = "您今日已经签到过了!";
//                                } else {
//                                    $zcard = Redis::zcard($key);
//                                    if ($zcard >= 1) {
//                                        Redis::zremrangebyrank($key, 0, 0);
//                                    }
//                                    $keys = array_xml($str);
//                                    $keys = $keys['FromUserName'];
//                                    $zincrby = Redis::zincrby($key, 1, $keys);
//                                    $zadd = Redis::zadd($key, $zincrby, $times);
//
//                                    $score = Redis::incrby($keys . "_score", 100);
//
//                                    $content = "签到成功您以积累签到" . $zincrby . "天!" . "您以积累获得" . $score . "积分";
//                                }
//                            }
//                        }
//
//                    break;
        }
}


    function xiaoxi($obj,$content){ //返回消息
        //我们可以恢复一个文本|图片|视图|音乐|图文列如文本
        //接收方账号
        $toUserName=$obj->FromUserName;
        //开发者微信号
        $fromUserName=$obj->ToUserName;
        //时间戳
        $time=time();
        //返回类型
        $msgType="text";

        $xml = "<xml>
                      <ToUserName><![CDATA[%s]]></ToUserName>
                      <FromUserName><![CDATA[%s]]></FromUserName>
                      <CreateTime>%s</CreateTime>
                      <MsgType><![CDATA[%s]]></MsgType>
                      <Content><![CDATA[%s]]></Content>
                    </xml>";
        //替换掉上面的参数用 sprintf
        echo sprintf($xml,$toUserName,$fromUserName,$time,$msgType,$content);

    }



    private function writeLog($data){
        if(is_object($data) || is_array($data)){   //不管是数据和对象都转json 格式
            $data=json_encode($data);
        }
        file_put_contents('2004.txt',$data);die;
    }

    /**
     * 处理文本消息
     */
    protected function textHandler()
    {
        echo '<pre>';print_r($this->xml_obj);echo '</pre>';
        $data = [
            'open_id'       => $this->xml_obj->FromUserName,
            'msg_type'      => $this->xml_obj->MsgType,
            'msg_id'        => $this->xml_obj->MsgId,
            'create_time'   => $this->xml_obj->CreateTime,
        ];

        //入库
        WxMediaModel::insertGetId($data);

    }

    /**
     * 处理图片消息
     */
    protected function imageHandler(){


        //下载素材
        $token = $this->getAccessToken();
        $media_id = $this->xml_obj->MediaId;
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        $media_path = 'upload/cat.jpg';
        $res = file_put_contents($media_path,$img);
        if($res)
        {
            // TODO 保存成功
        }else{
            // TODO 保存失败
        }

        //入库
        $info = [
            'media_id'  => $media_id,
            'open_id'   => $this->xml_obj->FromUserName,
            'msg_type'  => $this->xml_obj->MsgType,
            'msg_id'  => $this->xml_obj->MsgId,
            'create_time'  => $this->xml_obj->CreateTime,
            'media_path'    => $media_path
        ];
        WxMediaModel::insertGetId($info);

    }

    /**
     * 处理语音消息
     */
    protected function voiceHandler(){}


    /**
     * 处理视频消息
     */
    protected function videoHandler(){}


    /**
     * 处理菜单点击事件
     * click类型的菜单 创建时会有key，根据key做相应的逻辑处理
     */
    protected function clickHandler()
    {
        $event_key = $this->xml_obj->EventKey;      //菜单 click key
        echo $event_key;

        switch ($event_key){
            case 'checkin' :
                // TODO 签到逻辑
                break;

            case 'weather':
                // TODO 获取天气
                break;

            default:
                // TODO 默认
                break;
        }

        echo "";

    }


    /**
     * 获取access_token
     */
    public function getAccessToken()
    {

        $key = 'wx:access_token';

        //检查是否有 token
        $token = Redis::get($key);
        if($token)
        {
            return $token;
        }else{

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
            //使用guzzle发起get请求
            $client = new Client();         //实例化 客户端
            $response = $client->request('GET',$url,['verify'=>false]);       //发起请求并接收响应
            $json_str = $response->getBody();       //服务器的响应数据
            $data = json_decode($json_str,true);
            $token = $data['access_token'];

            //保存到Redis中 时间为 3600
            Redis::set($key,$token);
            Redis::expire($key,3600);
            return $token;
        }



    }



    /**
     * 回复扫码关注
     * @param $obj
     * @param $content
     * @return string
     */
    public function  subscribe(){

        $ToUserName=$this->xml_obj->FromUserName;       // openid
        $FromUserName=$this->xml_obj->ToUserName;
        //检查用户是否存在
        $u = WxUserModel::where(['openid'=>$ToUserName])->first();
        if($u)
        {
            // TODO 用户存在
            $content = "欢迎回来 现在时间是：" . date("Y-m-d H:i:s");
        }else{
            //获取用户信息，并入库
            $user_info = $this->getWxUserInfo();

            //入库
            unset($user_info['subscribe']);
            unset($user_info['remark']);
            unset($user_info['groupid']);
            unset($user_info['substagid_listcribe']);
            unset($user_info['qr_scene']);
            unset($user_info['qr_scene_str']);
            unset($user_info['tagid_list']);

            WxUserModel::insertGetId($user_info);
            $content = "欢迎关注 现在时间是：" . date("Y-m-d H:i:s");

        }

        $xml="<xml>
              <ToUserName><![CDATA[".$ToUserName."]]></ToUserName>
              <FromUserName><![CDATA[".$FromUserName."]]></FromUserName>
              <CreateTime>time()</CreateTime>
              <MsgType><![CDATA[text]]></MsgType>
              <Content><![CDATA[".$content."]]></Content>
       </xml>";

        return $xml;
    }

    /**
     * 创建自定义菜单
     */
    public function createMenu()
    {
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;

        $menu = [
            'button'    => [
                [
                    'type'  => 'view',
                    'name'  => '商城',
                    'url'   => 'http://2004shop.comcto.com'
                ],
                [
                    'name'          => '二级菜单',
                    'sub_button'    => [
                        [
                            'type'  => 'click',
                            'name'  => '签到',
                            'key'   => 'checkin'
                        ],
                        [
                            'type'  => 'pic_photo_or_album',
                            'name'  => '传图',
                            'key'   => 'uploadimg'
                        ],
                        [
                            'type'  => 'click',
                            'name'  => '天气',
                            'key'   => 'weather'
                        ]
                    ]
                ],

            ]
        ];

        //使用guzzle发起 POST 请求
        $client = new Client();         //实例化 客户端
        $response = $client->request('POST',$url,[
            'verify'    => false,
            'body'  => json_encode($menu,JSON_UNESCAPED_UNICODE)
        ]);

        $json_data = $response->getBody();

        //判断接口返回
        $info = json_decode($json_data,true);

        if($info['errcode'] > 0)        //判断错误码
        {
            // TODO 处理错误
            echo '<pre>';print_r($info);echo '</pre>';
        }else{
            // TODO 创建菜单成功逻辑
            echo date("Y-m-d H:i:s").  "创建菜单成功";
        }



    }


    /**
     * 下载媒体
     */
    public function dlMedia()
    {
        $token = $this->getAccessToken();
        $media_id = '2EOz5TyVOVA728B6cETWByk8_w33mS17Ye1e1C6AuAv2SMS7l4R4HoQFl9mmgprw';
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        echo $url;die;
        $img = file_get_contents($url);
        $res = file_put_contents('cat.jpg',$img);
        var_dump($res);

    }


    /**
     * 上传素材接口
     * 参考  https://developers.weixin.qq.com/doc/offiaccount/Asset_Management/New_temporary_materials.html
     */
    public function uploadMedia()
    {
        $access_token = $this->getAccessToken();
        $type = 'video';        //素材类型 image voice video thumb
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;

        $media = 'tmp/heshang.mp4';     //要上传的素材
        //使用guzzle发起get请求
        $client = new Client();         //实例化 客户端
        $response = $client->request('POST',$url,[
            'verify'    => false,
            'multipart' => [
                [
                    'name'  => 'media',
                    'contents'  => fopen($media,'r')
                ],         //上传的文件路径]
            ]
        ]);       //发起请求并接收响应

        $data = $response->getBody();
        echo $data;
    }

    /**
     * 群发消息
     */
    public function sendAll()
    {
        //根据openid 群发   https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Batch_Sends_and_Originality_Checks.html#3
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$access_token;

        //使用guzzle发起POST请求

        $data = [
            'filter'    => [
                'is_to_all' => false,
                'tag_id'    => 2
            ],
            'touser'    => [
                'oLreB1gfi87dPCO2gRiUecC5ZAbc',
                'oLreB1ruWsNCS-iMr_scTyVSUyY0',
                'oLreB1gnCH7es_CbLhRvM6yQO-kQ',
                'oLreB1mi55VwI2wai2y1uicTG5sk',
                'oLreB1hSqDSoz7VkTDin6J75ez4M',
                'oLreB1nsTnJSYPgmEUe1YW1xdAOw',
                'oLreB1i2Ig7OlI9YMI_nUBdGDmU8',
                'oLreB1qa7IVU3qpe0Tg1LShlzkww',
                'oLreB1kVep716f8n1i2Ace6r6UnA',
                'oLreB1kCnRGCqWu0Mur4A08usNRM',
                'oLreB1upyFz8UPNt5OTNLfP_9ciM',
                'oLreB1hfXdA_H-A-kJzXotMvlL1s',
                'oLreB1obDfuVfyBO8cBIH8FibAiA',
                'oLreB1m47p6J4mfY5Z6CQCMwFX4Q',
                'oLreB1hjx82-74x7qKxmkyeWbC7I',
                'oLreB1rcEhV6sMK9-X5Vgw_Sghqo',
                'oLreB1jG5XZ-F5QokhugIxdpe2lk',
                'oLreB1jAnJFzV_8AGWUZlfuaoQto',
                'oLreB1rTYjCsM8lp40yGky1fDcAQ',
                'oLreB1tqqKpg4n53ujarU47tQnSM',
                'oLreB1nGcCmNvEXScOpVNgfBifLA',
                'oLreB1inC1l0NjUy3Vz6rD5DoLDM',
                'oLreB1uh30YcGZGLDMPbm8cpu81E',
                'oLreB1qNMROnUTIbIAFSRoekMdfw',
                'oLreB1sehZ4x0N7T93-elf6f5hYg',
                'oLreB1tvM636Yof_F4WTh0nP6fOY',
                'oLreB1oWQYSQJUKL5i6kamigrj8g',
                'oLreB1oPHycqKR383DQtdhnHjP2U',
                'oLreB1ikgAe1kq2ES0M6SWQdGVqY',
            ],
            'images'    => [
                'media_ids' => [
                    '2EOz5TyVOVA728B6cETWByk8_w33mS17Ye1e1C6AuAv2SMS7l4R4HoQFl9mmgprw'
                ],
            ],
            'msgtype'   => 'image'
        ];

        $client = new Client();         //实例化 客户端
        $response = $client->request('POST',$url,[
            'verify'    => false,
            'body'      => json_encode($data,JSON_UNESCAPED_UNICODE)
        ]);       //发起请求并接收响应

        $data = $response->getBody();
        echo $data;

    }


    /**
     * 获取用户基本信息
     */
    public function getWxUserInfo()
    {

        $token = $this->getAccessToken();
        $openid = $this->xml_obj->FromUserName;
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid.'&lang=zh_CN';

        //请求接口
        $client = new Client();
        $response = $client->request('GET',$url,[
            'verify'    => false
        ]);
        return  json_decode($response->getBody(),true);
    }






}
