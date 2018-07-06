<?php
namespace Common\Controller;

use Common\Controller\BaseController;

class WXAdminBaseController extends BaseController
{
    public $api;
    public $wechat;
    public $is_weixin;
    public $appId;
    public $appSecret;
    public $token;
    public $encodingAESKey;
    public $numpage = 10;

    public function __construct()
    {
        $wx_path = C("SP_WX_TMPL_PATH") . C("SP_ADMIN_DEFAULT_THEME") . "/";
//         if(CONTROLLER_NAME=='Index'&&(ACTION_NAME=='callback'||ACTION_NAME=='index')){

//         }else{
//             setcookie('refurl', (CONTROLLER_NAME.'/'.ACTION_NAME));
//         }
        $this->is_weixin = is_Weixin();
        parent::__construct();
        $this->appId = C('APPID');
        $this->appSecret = C('APPSECRET');
        $this->token = C('TOKEN');
        $this->encodingAESKey = C('ENCODEINGAESKEY');
        // wechat模块 - 处理用户发送的消息和回复消息
        $this->wechat = new \Org\WechatPhpSdk\Wechat(array(
            'appId' => $this->appId,
            'token' => $this->token,
            'encodingAESKey' => $this->encodingAESKey //可选
        ));
        // api模块 - 包含各种系统主动发起的功能
        $this->api = new \Org\WechatPhpSdk\Api(
            array(
                'appId' => $this->appId,
                'appSecret' => $this->appSecret,
                'get_access_token' => function () {
                    return S('wechat_token');// 用户需要自己实现access_token的返回
                },
                'save_access_token' => function ($token) {
                    S('wechat_token', $token);// 用户需要自己实现access_token的保存
                },
                'get_jsapi_ticket' => function () {
                    return S('jsapi_ticket');// 用户需要自己实现access_token的返回
                },
                'save_jsapi_ticket' => function ($jsapi_ticket) {
                    S('jsapi_ticket', $jsapi_ticket);// 用户需要自己实现access_token的保存
                }
            )
        );
    }

    public function response_user($user, $data)
    {
        $this->api->send($user, $data);
    }

    //授权步骤开始
    public function authorize($str = "")
    {
        header('Content-type: text/html; charset=utf-8');
        $url = C('SITE_URL') . '/index.php?g=Wechat&m=Index&a=callback';
        $authorize_url = $this->api->get_authorize_url('snsapi_userinfo', $url);
        //		echo '<a href="' . $authorize_url . '">' .$authorize_url. '</a>';
        header('Location:' . $authorize_url . $str);
        //return $authorize_url;    
    }

    //微信登录
    public function get_wxinfo($str = "")
    {
        header('Content-type: text/html; charset=utf-8');
        $url = C('SITE_URL') . '/index.php?g=Wechat&m=Index&a=wx_login';
        $authorize_url = $this->api->get_authorize_url('snsapi_userinfo', $url);
        //		echo '<a href="' . $authorize_url . '">' .$authorize_url. '</a>';
        header('Location:' . $authorize_url . $str);
    }

    //回调函数
    public function callback_snsapi_userinfo()
    {
        header('Content-type: text/html; charset=utf-8');
        list($err, $user_info) = $this->api->get_userinfo_by_authorize('snsapi_userinfo');
        if ($user_info !== null) {
            return $user_info;
        } else {
            return null;
        }
    }

    /*public function reply() {
        $msg=$this->wechat->serve();
        if($msg->MsgType=="event"){
            if($msg->Event=="subscribe"){
                $this->api->send($msg->FromUserName, '这是我主动发送的一条消息');
            }
        }
        // 回复文本消息
        if ($msg->MsgType == 'text' && $msg->Content == '你好') {
            $this->wechat->reply("你也好！ - 这是我回复的额！");
        } else {
            $this->wechat->reply("听不懂！ - 这是我回复的额！");
        }
        // 主动发送
        $this->api->send($msg->FromUserName, '这是我主动发送的一条消息');
    }*/
    public function replay($data)
    {
        //$data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($data->MsgType == "event") {
            if ($data->Event == "subscribe") {
                $this->api->send($data->FromUserName, "欢迎关注金行管家");
            }
        } else {
            $this->api->send($data->FromUserName, "自动回复");
        }
    }

    //获得登录返回的token值
    public function _get_token($user_name, $user_id, $model_user = '')
    {
        $token = md5($user_name . strval(time()) . strval(rand(0, 999999)));
        if (empty($model_user)) {
            $model_user = D('Common/Users');
            $update = array('token' => $token);
        } else {
            $update = array('access_token' => $token);
        }
        $condition = array('id' => $user_id);

        $result = $model_user->update($condition, $update);
        if ($result) {
            setcookie("key", $token, time() + 3600 * 24);
            setcookie("type", 'user', time() + 3600 * 24);
            return $token;
        } else {
            return false;
        }
    }

    //从token里面获得用户信息

    public function get_userconnet_bytoken($token)
    {
        $model_connet = D('Common/user_connect');
        $connet_info = $model_connet->getByToken($token);
        return $connet_info;
    }

    //从token里面获得第三方用户信息

    public function get_alluserinfo_bytoken($token)
    {
        $user_info = $this->get_userinfo_bytoken($token);
        $user_pay = D('Common/user_pay')->getone(array('user_id' => $user_info['id']));
        $user_gold = D('Common/user_gold')->getUserGoldInfo(array('user_id' => $user_info['id']));

        $gold = D('Common/gold')->getNewGold();
        $gold_price = cutNum((float)$user_gold['weight'] * (float)$gold['price'], 2);
        if (!empty($user_info)) {
            $user_info['price'] = $user_pay['price'] ? $user_pay['price'] : '0.00';
            $user_info['total_amount'] = cutNum(((float)$user_pay['price'] + (float)$gold_price), 2);
            $user_info['total_earn'] = cutNum($user_pay['total_earn'], 2);
            $user_info['weight'] = $user_gold['weight'];
        }
        return $user_info;
    }

    //从token中获取用户全部信息

    public function get_userinfo_bytoken($token)
    {
        $model_user = D('Common/Users');
        $user_info = $model_user->getUserInfo(array('token' => $token));
        if (!empty($user_info)) {
            setcookie("key", $user_info['token'], time() + 3600 * 24);
            setcookie("type", 'user', time() + 3600 * 24);
            $real_name_info = D('Common/user_realname')->getRealNameInfo(array('user_id' => $user_info['id']));
            $user_info['real_name'] = $real_name_info['real_name'];
            $user_info['idno'] = $real_name_info['idno'];
        }
        return $user_info;
    }

    //获得微信端分页

    public function getPage($count)
    {
        $numpage = I('post.numpage');
        if ($numpage == 0 || empty($numpage)) {
            $numpage = $this->numpage;
        }
        $total_page = ceil($count / $numpage);
        $cur_page = I('post.cur_page');
        if (empty($cur_page)) {
            $cur_page = 0;
        }
        if ($cur_page > $total_page) {
            $cur_page = $total_page;
        }
        $page = $cur_page * $numpage . "," . $numpage;
        $this->assign('cur_page', $cur_page);
        $this->assign('total_page', $total_page);
        $res = array();
        $res['hasmore'] = $cur_page == $total_page ? 0 : 1;
        $res['page'] = $page;
        $res['cur_page'] = $cur_page;
        return $res;
    }

    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            ob_clean();
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = C('TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}
