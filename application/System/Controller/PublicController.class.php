<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
/**
 */
namespace System\Controller;
use Common\Controller\SystembaseController;
class PublicController extends SystembaseController {
	//当前类需要的模型
	const MUSERS='m_users';
    public function _initialize() {
        C(S('sp_dynamic_config'));//加载动态配置
    }
    
    //后台登陆界面
    public function login() {
        $admin_id=session('SYSTEM_ID');
    	if(!empty($admin_id)){//已经登录
    		redirect(U("system/index/index"));
    	}else{
    	    $site_admin_url_password =C("SP_SITE_ADMIN_URL_PASSWORD");
    	    $upw=session("__SP_UPW__");
    		if(!empty($site_admin_url_password) && $upw!=$site_admin_url_password){
    			redirect(__ROOT__."/");
    		}else{
    		    session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__",true);
    			$this->display(":login");
    		}
    	}
    }
    
    public function logout(){
    	session('SYSTEM_ID',null);
    	redirect(U("system/public/login"));
    }
    
    public function dologin(){
        $login_page_showed_success=session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__");
        if(!$login_page_showed_success){
            $this->error('login error!');
        }
    	$name = I("post.username");
    	if(empty($name)){
    		$this->error(L('USERNAME_OR_EMAIL_EMPTY'));
    	}
    	$pass = I("post.password");
    	if(empty($pass)){
    		$this->error(L('PASSWORD_REQUIRED'));
    	}
//     	$verrify = I("post.verify");
//     	if(empty($verrify)){
//     		$this->error(L('CAPTCHA_REQUIRED'));
//     	}
    	//验证码
//     	if(!sp_check_verify_code()){
//     		$this->error(L('CAPTCHA_NOT_RIGHT'));
//     	}else{
    		$user = D(self::MUSERS);
    		if(strpos($name,"@")>0){//邮箱登陆
    			$where['user_email']=$name;
    		}else{
    			$where['user_login|mobile']=$name;
    		}
    		$result = $user->where($where)->find();
		//	$allow_type = array(1,4);
			if(!empty($result)){
    			if(sp_compare_password($pass,$result['user_pass'])){
    				$role_user_model=M("ARoleUser");
    				
    				$role_user_join = C('DB_PREFIX').'a_role as b on a.role_id =b.id';
    				
    				$groups=$role_user_model->alias("a")->join($role_user_join)->where(array("user_id"=>$result["id"],"status"=>1))->getField("role_id",true);

					if( $result["id"]!=1 && ( empty($groups) || empty($result['user_status']) ) ){
						if(empty($groups)){
							$this->error("该用户无权访问！");
						}else{
							$this->error("改用户已经被拉黑，请联系客服");
						}
					}
    				//登入成功页面跳转
    				session('SYSTEM_ID',$result["id"]);
    				session('name',$result["user_login"]);
    				$result['last_login_ip']=get_client_ip(0,true);
    				$result['last_login_time']=time();
    				$user->save($result);
    				cookie("admin_username",$name,3600*24*30);
    				$this->success(L('LOGIN_SUCCESS'),U("Index/index"));
				}else{
					$this->error("密码错误！");
				}
			}else{
				$this->error("该用户不存在！");
			}
//     	}
    }
    public function icon_list(){
        $this->display(":icon_list");
    }

}