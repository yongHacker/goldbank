<?php
namespace Common\Controller;
use Common\Controller\HomebaseController;
class WXBaseController extends HomebaseController {
    public $api;
    public $wechat;
    public $is_weixin;
    public $appId;
    public $appSecret;
    public $token;
    public $encodingAESKey;
    public $numpage=10;
    public $user_info;
    public $wx_user_info;
    public function __construct(){
        parent::__construct();
        $this->_initialize();
        //@$this->_do_access_log();
    }
    public function _do_access_log(){
        $key=!empty(I('get.key'))?I('get.key'):I('post.key');
        $key=!empty($key)?$key:$_COOKIE['key'];
        $this->user_info=$this->get_userinfo_bytoken($key);
        if(empty($this->user_info)){
            $this->wx_user_info=$this->get_userconnet_bytoken($key);
        }
        $insert=array(
            'user_id'=>!empty($this->user_info['id'])?$this->user_info['id']:(!empty($this->wx_user_info['id'])?$this->wx_user_info['id']:0),
            'user_nicename'=>!empty($this->user_info['user_nicename'])?$this->user_info['user_nicename']:(!empty($this->wx_user_info['access_name'])?$this->wx_user_info['access_name']:''),
            'realname'=>!empty($this->user_info['realname'])?$this->user_info['realname']:'',
            'access_path'=>MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME,
            'user_type'=>!empty($this->user_info['id'])?1:2,
            'access_type'=>$this->is_weixin?1:2
        );
        if(!in_array($_SERVER['HTTP_HOST'],C('LOG_SERVER'))){
            return false;
        }
        if(!empty($insert)){
           // D('Common/Log')->addLog($insert);
        }
    }
    //初始化
    public function _initialize(){
        $wx_path=C("SP_WX_TMPL_PATH").C("SP_ADMIN_DEFAULT_THEME")."/";
        $this->is_weixin=is_Weixin();
        $this->appId = C('APPID');
        $this->appSecret = C('APPSECRET');
        $this->token = C('TOKEN');
        $this->encodingAESKey = C('ENCODEINGAESKEY');
        // wechat模块 - 处理用户发送的消息和回复消息
        $this->wechat = new \Org\WechatPhpSdk\Wechat(array(
            'appId' => $this->appId,
            'token' => 	$this->token,
            'encodingAESKey' =>	$this->encodingAESKey //可选
        ));
        // api模块 - 包含各种系统主动发起的功能
        $this->api = new \Org\WechatPhpSdk\Api(
            array(
                'appId' => $this->appId,
                'appSecret'	=> $this->appSecret,
                'get_access_token' => function(){
                    return S('wechat_token');// 用户需要自己实现access_token的返回
                },
                'save_access_token' => function($token) {
                    S('wechat_token', $token);// 用户需要自己实现access_token的保存
                },
                'get_jsapi_ticket' => function () {
                    $jsapi_ticket = file_get_contents(SITE_PATH . "zhifu/unionpay/jsapi_ticket.txt");
                    return $jsapi_ticket;// 用户需要自己实现jsapi_ticket的返回
                },
                'save_jsapi_ticket' => function ($jsapi_ticket) {
                    file_put_contents(SITE_PATH . "zhifu/unionpay/jsapi_ticket.txt", print_r($jsapi_ticket, true));// 用户需要自己实现jsapi_ticket的保存
                }
            )
        );
        if ($this->is_weixin) {
            $token = trim($_COOKIE['key']);
            $type = trim($_COOKIE['type']);
            $openid = $_COOKIE['openid'];
            $testrefurl = $_COOKIE['refurl'];
            $is_authorize_url = array('Index/callback','Index/search_product');
            $is_authorize = in_array(CONTROLLER_NAME . '/' . ACTION_NAME, $is_authorize_url);
            if (empty($openid) && !$is_authorize) {
                //授权回调callback后最终回调页面的所有链接配置$is_callback_url
                $is_callback_url = array('Index/friend_invitation');
                if (in_array(CONTROLLER_NAME . '/' . ACTION_NAME, $is_callback_url)) {
                    setcookie('refurl', (C('SITE_URL') . $_SERVER["REQUEST_URI"]), time() + 30);
                }
                $this->authorize();
                die();
            } else if ($openid) {
                $user_info = D('Common/Users')->getUserInfo(array('openid' => $openid));
                if (empty($user_info)) {
                    $uc = D('Common/UserConnect')->getOne(array('access_id' => $openid));
                    setcookie('key', $uc['access_token']);
                    setcookie('type', "");
                }else{
                    if (empty($type) && empty($token)) {
                        $this->_get_token($user_info['user_nicename'], $user_info['id']);
                    }
                }
            }
        }
    }
    public function authorize($str = "")
    {
        header('Content-type: text/html; charset=utf-8');
        $url = C('SITE_URL') . '/index.php?g=Wechat&m=Index&a=callback';
        $authorize_url = $this->api->get_authorize_url('snsapi_userinfo', $url);
        //		echo '<a href="' . $authorize_url . '">' .$authorize_url. '</a>';
        header('Location:' . $authorize_url . $str);
        //return $authorize_url;
    }
    //授权步骤开始

    public function _get_token($user_name, $user_id, $model_user = '')
    {
        $token = md5($user_name . strval(time()) . strval(rand(0, 999999)));
        $condition = array('id' => $user_id);
        if (empty($model_user)) {
            $model_user = D('Common/Users');
            $update = array('token' => $token);
            $user_info = $model_user->getUserInfo($condition);
            if(empty($user_info)){
                return false;
            }
            if ($user_info['user_type'] == 4) {
                return false;
            }
            $update['last_login_ip'] = get_client_ip(0, true);
            $update['last_login_time'] = date("Y-m-d H:i:s");
            $result = $model_user->update($condition, $update);
            if ($result) {
                setcookie("key", $token, time() + 3600 * 24);
                setcookie("type", 'user', time() + 3600 * 24);
                return $token;
            } else {
                return false;
            }
        } else {
            $model_user = D('Common/UserConnect');
            $update = array('access_token' => $token,'last_login_time'=>date('Y-m-d H:i:s',time()));
            $result = $model_user->update($condition, $update);
            if ($result) {
                setcookie("key", $token, time() + 3600 * 24);
                return $token;
            } else {
                return false;
            }
        }

    }
    //微信登录

    public function response_user($user, $data)
    {
        $this->api->send($user, $data);
    }
    //回调函数

    public function get_wxinfo($str=""){
        header('Content-type: text/html; charset=utf-8');
        $url=C('SITE_URL').'/index.php?g=Wechat&m=Index&a=wx_login';
        $authorize_url = $this->api->get_authorize_url('snsapi_userinfo', $url);
        //		echo '<a href="' . $authorize_url . '">' .$authorize_url. '</a>';
        header('Location:'.$authorize_url.$str);
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
    //获得登录返回的token值

    public function replay($data)
    {
        //$data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($data->MsgType == "event") {
            if ($data->Event == "subscribe") {
                $this->api->send($data->FromUserName, "欢迎关注永坤金行家");
            }
        }else{
            $this->api->send($data->FromUserName, "自动回复");
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
        $collect_price=M('lease_data')->join('jb_lease_product p on p.product_id=jb_lease_data.product_id')->where(array("is_hedging"=>1,"lease_uid"=> $user_info['id'],'status'=>0))->sum('lease_weight*gold_price');

        $gold = D('Common/gold')->getNewGold();
        $gold_price =(float)$user_gold['weight'] * (float)$gold['price'];
        if (!empty($user_info)) {
            //$user_info['total_amount']=$user_pay['total_recharge']-$user_pay['withdraw_price']+$user_gold['trade_earn']+$user_gold['lease_interest_price']+($gold['price']-$user_gold['avg_price'])*($user_gold['weight']+$user_gold['lease_current_weight'])+($user_gold['put_weight']-$user_gold['out_weight'])*$user_gold['avg_price']-$user_pay["consume"];
            $user_info['total_amount']=$user_pay['price']+($user_gold['weight']*$gold['price'])+(/*$user_gold['lease_current_interest_price']+*/$collect_price+$user_gold['lease_current_weight']*$gold['price']);
            $user_info['total_amount']=decimalsformat($user_info['total_amount'],2);
            $user_info['price'] = $user_pay['price'] ?decimalsformat($user_pay['price'],2) : '0.00';
            //$user_info['total_amount'] = $user_pay['price'] + $gold_price;
            $user_info['total_earn'] = decimalsformat($user_pay['total_earn'],2);
            $user_info['weight'] = decimalsformat($user_gold['weight'],2);
        }
        return $user_info;
    }
    //从token中获取用户全部信息

    public function get_userinfo_bytoken($token)
    {
        $model_user = D('Common/Users');
        $user_info = $model_user->getUserInfo(array('token' => $token));
        if(!empty($user_info)){
            setcookie("key", $user_info['token'], time() + 3600 * 24);
            setcookie("type", 'user', time() + 3600 * 24);
            $real_name_info = D('Common/user_realname')->getRealNameInfo(array('user_id' => $user_info['id']));
            $user_info['real_name'] = $real_name_info['real_name'];
            $user_info['idno'] = $real_name_info['idno'];
        }
        return $user_info;
    }

    //获得微信端分页

    public function getPage($count){
        $numpage=I('post.numpage');
        if($numpage==0||empty($numpage)){
            $numpage=$this->numpage;
        }
        $total_page=ceil($count/$numpage);
        $cur_page=I('post.cur_page');
        if(empty($cur_page)){
            $cur_page=0;
        }
        if($cur_page>$total_page){
            $cur_page=$total_page;
        }
        $page=$cur_page*$numpage.",".$numpage;
        $this->assign('cur_page',$cur_page);
        $this->assign('total_page',$total_page);
        $res=array();
        $res['hasmore']=$cur_page==$total_page?0:1;
        $res['page']=$page;
        $res['total_page']=$total_page;
        $res['cur_page']=$cur_page;
        return $res;
    }

    public function getPage_new($count,$numpage){
        $numpage=$numpage;
        if($numpage==0||empty($numpage)){
            $numpage=$this->numpage;
        }
        $total_page=ceil($count/$numpage);
        $cur_page=I('post.cur_page');
        if(empty($cur_page)){
            $cur_page=0;
        }
        if($cur_page>$total_page){
            $cur_page=$total_page;
        }
        $page=$cur_page*$numpage.",".$numpage;
        $this->assign('cur_page',$cur_page);
        $this->assign('total_page',$total_page);
        $res=array();
        $res['hasmore']=$cur_page==$total_page?0:1;
        $res['page']=$page;
        $res['total_page']=$total_page;
        $res['cur_page']=$cur_page;
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
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}
