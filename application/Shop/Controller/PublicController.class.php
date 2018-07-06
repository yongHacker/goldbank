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
namespace Shop\Controller;
use Shop\Controller\ShopbaseController;
class PublicController extends ShopbaseController {

    public function _initialize() {
		$this->bemployee_model=D("BEmployee");
        C(S('sp_dynamic_config'));//加载动态配置
    }
    
    //后台登陆界面
    public function login() {
        $admin_id=session('SHOP_USER_ID');
    	if(!empty($admin_id)){//已经登录
    		redirect(U("shop/index/index"));
    	}else{
    	    $site_admin_url_password =C("SP_SITE_ADMIN_URL_PASSWORD");
    	    $upw=session("__SP_UPW__");
    		if(!empty($site_admin_url_password) && $upw!=$site_admin_url_password){
    			redirect(__ROOT__."/");
    		}else{
				if(I("bemployee")){

					$this->assign("bemployee",I("bemployee"));
				}
    		    session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__",true);
    			$this->display(":login");
    		}
    	}
    }
    
    public function logout(){
    	session('SHOP_USER_ID',null);
		session('SHOP_COMPANY_ID',null);
		session('SHOP_SHOP_ID',null);
    	redirect(U("shop/Public/login"));
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
//     	//验证码
//     	if(!sp_check_verify_code()){
//     		$this->error(L('CAPTCHA_NOT_RIGHT'));
//     	}else{
    		$user = D("MUsers");
    		if(strpos($name,"@")>0){//邮箱登陆
    			$where['user_email']=$name;
    		}else{
    			$where['user_login|mobile']=$name;
    		}
    		$result = $user->where($where)->find();

            $allow_type = array(1,4);

    		if(!empty($result) /* && in_array($result['user_type'], $allow_type) */){
    			if(sp_compare_password($pass,$result['user_pass'])){
    				if(I("post.shop_id")){
						$shop=M("BShop")->where(array("id"=>I("post.shop_id")))->find();
						$role_condition=array("user_id"=>$result["id"],"status"=>1,"a.company_id"=>$shop["company_id"]);
					}else{
						$role_condition=array("user_id"=>$result["id"],"status"=>1);
					}
					$role_condition['b.type']=1;
    				$role_user_model=M("BRoleUser");
    				
    				$role_user_join = C('DB_PREFIX').'b_role as b on a.role_id =b.id';

    				$groups=$role_user_model->alias("a")->join($role_user_join)->where($role_condition)->getField("role_id",true);

					if( $result["id"]!=1 && ( empty($groups) || empty($result['user_status']) ) ){
						if(empty($groups)){
							$this->error("该用户无权访问！");
						}else{
							$this->error("改用户已经被拉黑，请联系客服");
						}
					}
					//通过员工信息获取商户和门店id并缓存
					$company_id=I("post.shop_id");
					$condition=array('bemployee.user_id'=>$result["id"],"bemployee.deleted"=>0,
						"bemployee.status"=>array('in','1,2'),"bcompany.company_status"=>1,"bcompany.deleted"=>0);
					if(!empty($company_id)){
						$condition['bshopemployee.shop_id']=I("post.shop_id");
					}
					cookie("shop_username",$name,3600*24*30);
					$this->is_shop($result["id"],$condition);
    				//登入成功页面跳转
    				session('SHOP_USER_ID',$result["id"]);
    				session('shopname',$result["user_login"]);
    				$result['last_login_ip']=get_client_ip(0,true);
    				$result['last_login_time']=time();
    				$user->save($result);
					unset($condition['bcompany.company_status']);
					unset($condition['bcompany.deleted']);
					unset($condition['bshopemployee.shop_id']);
					$condition['bemployee.company_id']=session('SHOP_COMPANY_ID');
					M('BEmployee')->alias('bemployee')->where($condition)->save(array('employee_login_time'=>time()));
    				cookie("shop_username",$name,3600*24*30);
    				$this->success(L('LOGIN_SUCCESS'),U("Index/index"));
    			}else{
    				$this->error(L('PASSWORD_NOT_RIGHT'));
    			}
    		}else{
    			$this->error(L('USERNAME_NOT_EXIST'));
    		}
//     	}
    }
	public function is_shop($user_id,$where){
		$join="right join ".DB_PRE."b_shop_employee bshopemployee on bshopemployee.employee_id=bemployee.id";
		$join.=" left join ".DB_PRE."b_shop bshop on bshop.id=bshopemployee.shop_id";
		$join.=" left join ".DB_PRE."b_company bcompany on bcompany.company_id=bemployee.company_id";
		$bemployee_count=M('BEmployee')->alias("bemployee")->join($join)->where($where)->count();
		if($bemployee_count>1){
			$condition=array("bemployee.deleted"=>0,'bemployee.user_id'=>$user_id);
			$field='bshopemployee.shop_id,bshop.shop_name';
			$bemployee = $this->bemployee_model->alias("bemployee")->getList($condition,$field,$limit="",$join,$order='bemployee.create_time desc',$group='');
			$this->error("请选择门店！",U("shop/public/login",array("bemployee"=>$bemployee)));
		}elseif($bemployee_count<1){
			$this->error("非门店人员");
		}else{
			$bemployee=M('BEmployee')->alias('bemployee')
				->join($join)
				->field("bemployee.company_id,bshopemployee.shop_id,bcompany.end_time,bemployee.id,bemployee.user_id")
				->where($where)
				->find();
			if($bemployee["end_time"]<time()){
				$this->error("服务已经到期，请联系客服");
			}

				session('SHOP_COMPANY_ID',$bemployee["company_id"]);
				session('SHOP_SHOP_ID',$bemployee["shop_id"]);
		}
	}

}