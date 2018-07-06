<?php
namespace Common\Controller;
use Common\Controller\WXBaseController;
class WXMemberController extends WXBaseController {
    public function __construct(){
        parent::__construct();
        $key=!empty(I('get.key'))?I('get.key'):I('post.key');
        $key=!empty($key)?$key:$_COOKIE['key'];
        if(empty($key)){
            header('location:'.U('Wechat/Index/login'));die();
        }
        $this->user_info=$this->get_userinfo_bytoken($key);
        if($this->user_info['user_type']==4){
            unset($_COOKIE['key']);
            header('location:'.U('Wechat/Index/login'));die();
        }
        if (empty($this->user_info)) {
            setcookie('refurl', (CONTROLLER_NAME . '/' . ACTION_NAME), 30);
            header('location:' . U('Wechat/Index/login'));
            die();
        }else{
            $this->user_info["is_user"] = 1;
            $this->user_info["user_id"] = $this->user_info["id"];
        }
    }
}