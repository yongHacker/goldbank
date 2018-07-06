<?php
namespace System\Controller;

use Common\Controller\SystembaseController;

class MUserController extends SystembaseController{

	protected $users_model,$role_model;

	public function __construct(){
		parent::__construct();
	}

	public function _initialize() {
		parent::_initialize();
		$this->users_model = D("MUsers");
		$this->role_model = D("ARole");
	}

	// 管理员列表
	public function index(){
		$where = array(
			'user_type'=> 1,
			'company_id'=> 0
		);

		$user_login = I('request.user_login');
		if($user_login){
			$where['user_login|user_nicename|mobile|user_email'] = array('like', '%'. $user_login .'%');
		}

		$count = $this->users_model->where($where)->count();
		$page = $this->page($count, $this->pagenum);

        $users = $this->users_model
            ->where($where)
            ->order("last_login_time desc,create_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
		$roles_src = $this->role_model->select();
		$roles = array();
		foreach ($roles_src as $r){
			$roleid = $r['id'];
			$roles["$roleid"] = $r;
		}

		$this->assign("page", $page->show('Admin'));
		$this->assign("roles", $roles);
		$this->assign("users", $users);

		$this->display();
	}

	// 重置密码
	public function resetpass(){
		$user_id = I('user_id/d', 0);

		$pass = trim(I('pass/s'));
		$pass_2 = trim(I('pass_2/s'));

		if($pass == ''){
			$this->error("请设置一个密码！");
		}

		if($pass != $pass_2){
			$this->error("两次密码不正确！");
		}

		M()->startTrans();

		$where = array('id'=> $user_id);
		$update_data = array(
			'user_pass'=> sp_password($pass),
		);
		$rs = $this->users_model->update($where, $update_data);

		if($rs === false){
			M()->rollback();
			$this->error("重置失败！");
		}else{
			M()->commit();
			$this->success("修改成功！", U('MUser/user_list'));
		}
	}

	// 用户列表 user_type <> 4
	public function user_list(){
		$where = array(
			// 平台用户 和商户登录账户不显示
			'user_type'=> array('not in', '1,4'),
			// 'u.user_type'=> array('not in', '4'),
			'u.id'=> array('neq', 1)
		);

		$user_login = I('request.user_login');
		if($user_login){
			$where['u.user_login|u.user_nicename|u.mobile|c.company_name'] = array('like', '%'. trim($user_login) .'%');
		}

		$user_status = I('user_status/d', -1);
		if($user_status >= 0){
			$where['u.user_status'] = $user_status;
		}
		$this->assign('user_status', $user_status);

		$begin_time = I('begin_time/s');
		if($begin_time != ''){
			$where['u.create_time'] = array('egt', strtotime($begin_time));
		}

		$end_time = I('end_time/s');
		if($end_time != ''){
			if($begin_time != ''){
				$tmp = $where['u.create_time'];
				$where['u.create_time'] = array($tmp, array('elt', strtotime($end_time)));
			}else{
				$where['u.create_time'] = array('elt', strtotime($end_time));
			}
		}

		$field = 'u.*, (
			CASE u.user_type 
			WHEN "1" THEN "平台人员"
			WHEN "2" THEN "商户人员"
			WHEN "3" THEN "客户"
			WHEN "4" THEN "企业用户" END
		)as show_user_type';
		$field .= ', (CASE u.sex 
			WHEN "0" THEN "保密"
			WHEN "1" THEN "男"
			WHEN "2" THEN "女"
			ELSE "-" END
		) as show_sex';
		$field .= ', c.company_name';
		$order_by = 'u.id DESC, u.create_time DESC';

		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_company as c ON (c.user_id = u.id)';

		$count = $this->users_model->alias('u')->countList($where, $field, $join);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.','.$page->listRows;

        $users = $this->users_model->alias('u')->getList($where, $field, $limit, $join, $order_by);

		$this->assign("page", $page->show('Admin'));
		$this->assign("users", $users);

		$this->display();
	}

	public function common_edit(){
		$id = I('id/d', 0);
		$where = array(
			'id'=> $id
		);

		$user_info = $this->users_model->getInfo($where);

		$roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);

		$role_user_model=M("ARoleUser");
		$role_ids=$role_user_model->where(array("user_id"=>$id))->getField("role_id",true);
		$this->assign("role_ids",$role_ids);

		$this->assign('user_info', $user_info);
		$this->display();
	}

	public function common_edit_post(){
		$id = I('id/d', 0);
		if($id){

			$where = array('id'=> $id);
			$user_info = $this->users_model->getInfo($where);

			$user_nicename = I('post.user_nicename/s');
			$user_mobile = I('post.user_mobile/s');
			$mobile_area = I('post.mobile_area/d', 1);
			$user_email = I('post.user_email/s');
			$user_type = I('post.user_type/d', 2);
			$user_pass = I('post.user_pass/s');

			if(!empty($user_nicename)){
				$where = array(
					'user_nicename'=> $user_nicename, 
					'id'=> array('neq', $user_info['id'])
				);
				$exists_num = $this->users_model->countList($where);
				if($exists_num > 0){
					$this->error("该用户名不可用或者已存在！");
				}
			}else{
				$this->error("请填写用户名！");
			}

			if(!empty($user_mobile)){
				$where = array(
					'mobile'=> $user_mobile,
					'id'=> array('neq', $user_info['id'])
				);
				$exists_num = $this->users_model->countList($where);

				if($exists_num > 0){
					$this->error("该手机号已存在！");
				}
			}else{
				$this->error("请填写手机号！");
			}

			if(!check_mobile($user_mobile, $mobile_area)){
				$this->error('不支持的手机号码！');
	        }
			
			if(!empty($user_email)){
				$where = array(
					'user_email'=> $user_email,
					'id'=> array('neq', $user_info['id'])
				);
				$exists_num = $this->users_model->countList($where);
				if($exists_num > 0){
					$this->error("该邮箱已存在！");
				}
			}

			$user_pass = $user_pass == '' ? '' : $user_pass;

			M()->startTrans();

			$where = array('id'=> $id);
			$update_data = array(
				'user_nicename'=> $user_nicename,
				'mobile'=> $user_mobile,
				'mobile_area'=> $mobile_area,
				'user_email'=> $user_email,
				'user_type'=> $user_type,
				'sex'=> I('sex/d', 0),
				'operate_user_id'=> get_current_admin_id()
			);

			if($user_pass != ''){
				$update_data['user_pass'] = sp_password($user_pass);
			}

			$rs = $this->users_model->update($where, $update_data);

			// 不涉及修改平台用户，不用处理平台用户角色数据
			// if($user_type == 1 && $rs !== false){
			// 	$role_id = I('post.role_id');
			// 	$new_user_id = $rs;

			// 	if(!empty($role_id) && is_array($role_id)){
			// 		$role_ids = $role_id;
			// 		$uid = $id;

			// 		$role_user_model = D("ARoleUser");
			// 		$role_user_model->where(array("user_id"=> $uid))->delete();

			// 		foreach ($role_ids as $role_id){
			// 			if(sp_get_current_admin_id() != 1 && $role_id == 1){
			// 				M()->rollback();

			// 				$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
			// 			}

			// 			$insert_data = array(
			// 				"role_id"=> $role_id,
			// 				"user_id"=> $uid
			// 			);
			// 			$rs = D("ARoleUser")->insert($insert_data);
			// 			if($rs === false){
			// 				break;
			// 			}
			// 		}
					
			// 	}
			// }

			if($rs !== false){
				M()->commit();

				$this->success("修改成功！", U("MUser/user_list"));
			}else{
				M()->rollback();

				$this->error("修改失败！");
			}

		}else{
			$this->error('修改失败！');
		}
	}

	// 添加页面 普通用户
	public function common_add(){
		$roles = $this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);

		$this->display();
	}

	// 提交页面
	public function common_add_post(){
		// $post_data = I('post.');
		
		$user_nicename = I('post.user_nicename/s');
		$user_mobile = I('post.user_mobile/s');
		$mobile_area = I('post.mobile_area/d', 1);
		$user_email = I('post.user_email/s');
		$user_type = I('post.user_type/d', 2);
		$user_pass = I('post.user_pass/s');

		if(!empty($user_nicename)){
			$where = array('user_nicename'=> $user_nicename, 'deleted'=> 0);
			$exists_num = $this->users_model->countList($where);
			if($exists_num > 0){
				$this->error("该用户名不可用或者已存在！");
			}
		}else{
			$this->error("请填写用户名！");
		}

		if(!empty($user_mobile)){
			$where = array('mobile'=> $user_mobile);
			$exists_num = $this->users_model->countList($where);
			if($exists_num > 0){
				$this->error("该手机号已存在！");
			}
		}else{
			$this->error("请填写手机号！");
		}

		if(!check_mobile($user_mobile, $mobile_area)){
			$this->error('不支持的手机号码！');
        }

		if(!empty($user_email)){
			$where = array('user_email'=> $user_email);
			$exists_num = $this->users_model->countList($where);
			if($exists_num > 0){
				$this->error("该邮箱已存在！");
			}
		}
		$user_pass = $user_pass == '' ? '123456' : $user_pass;

		M()->startTrans();

		$insert_data = array(
			'user_nicename'=> $user_nicename,
			'mobile'=> $user_mobile,
			'mobile_area'=> $mobile_area,
			'user_email'=> $user_email,
			'user_type'=> $user_type,
			'create_time'=> time(),
			'sex'=> I('sex/d', 0),
			'user_pass'=> sp_password($user_pass),
			'operate_user_id'=> get_current_admin_id()
		);
		$rs = $this->users_model->insert($insert_data);

		// 不涉及平台用户类型，不用处理平台用户角色数据
		// if($user_type == 1 && $rs !== false){
		// 	$role_id = I('post.role_id');
		// 	$new_user_id = $rs;

		// 	if(!empty($role_id) && is_array($role_id)){
		// 		$role_ids = $role_id;

		// 		foreach ($role_ids as $role_id){
		// 			if(sp_get_current_admin_id() != 1 && $role_id == 1){
		// 				M()->rollback();

		// 				$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
		// 			}

		// 			$insert_data = array(
		// 				"role_id"=> $role_id,
		// 				"user_id"=> $new_user_id
		// 			);
		// 			$rs = D("ARoleUser")->insert($insert_data);
		// 			if($rs === false){
		// 				break;
		// 			}
		// 		}
				
		// 	}
		// }

		if($rs !== false){
			M()->commit();

			$this->success("添加成功！", U("MUser/user_list"));
		}else{
			M()->rollback();

			$this->error("添加失败！");
		}
	}

	// 禁用、开启
	public function common_toggle_ban(){
		$id = I('id/d', 0);
		$to_status = 0;

    	if ($id) {
    		$where = array(
    			'id'=> $id,
    			'user_type'=> array('not in', '1,4')
    		);
    		$user_info = $this->users_model->getInfo($where);
    		if($user_info['user_status'] == 0){
    			$to_status = 1;

    			$msg['success'] = '管理员启用成功！';
    			$msg['errot'] = '管理员启用失败！';
    		}

    		if($user_info['user_status'] == 1){
    			$to_status = 0;

    			$msg['success'] = '管理员停用成功！';
    			$msg['errot'] = '管理员停用失败！';
    		}

    		$result = $this->users_model->where($where)->setField('user_status', $to_status);
    		if ($result !== false) {
    			$this->success($msg['success'], U("MUser/user_list"));
    		} else {
    			$this->error($msg['error']);
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
	}

	// 管理员添加
	public function add(){

		$this->redirect('MUser/common_add');
		die();

		$roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);
		$this->display();
	}

	// 管理员添加提交
	public function add_post(){
		if(IS_POST){
			if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
				$role_ids=$_POST['role_id'];
				unset($_POST['role_id']);
				if ($this->users_model->create()!==false) {
					$result=$this->users_model->add();
					if ($result!==false) {
						$role_user_model=M("ARoleUser");
						foreach ($role_ids as $role_id){
							if(sp_get_current_admin_id() != 1 && $role_id == 1){
								$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
							}
							$role_user_model->add(array("role_id"=>$role_id,"user_id"=>$result));
						}
						$this->success("添加成功！", U("MUser/index"));
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->users_model->getError());
				}
			}else{
				$this->error("请为此用户指定角色！");
			}

		}
	}

	// 管理员编辑
	public function edit(){
	    $id = I('get.id',0,'intval');
		$roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);
		$role_user_model=M("ARoleUser");
		$role_ids=$role_user_model->where(array("user_id"=>$id))->getField("role_id",true);
		$this->assign("role_ids",$role_ids);

		$user=$this->users_model->where(array("id"=>$id))->find();
		$this->assign($user);
		$this->display();
	}

	// 管理员编辑提交
	public function edit_post(){
		if (IS_POST) {
			if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
				if(empty($_POST['user_pass'])){
					unset($_POST['user_pass']);
				}
				$role_ids = I('post.role_id/a');
				unset($_POST['role_id']);
				if ($this->users_model->create()!==false) {
					$result=$this->users_model->save();
					if ($result!==false) {
						$uid = I('post.id',0,'intval');
						$role_user_model=M("RoleUser");
						$role_user_model->where(array("user_id"=>$uid))->delete();
						foreach ($role_ids as $role_id){
							if(sp_get_current_system_id() != 1 && $role_id == 1){
								$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
							}
							$role_user_model->add(array("role_id"=>$role_id,"user_id"=>$uid));
						}
						$this->success("保存成功！",U("MUser/index"));
					} else {
						$this->error("保存失败！");
					}
				} else {
					$this->error($this->users_model->getError());
				}
			}else{
				$this->error("请为此用户指定角色！");
			}

		}
	}

	// 管理员删除
	public function delete(){
	    $id = I('get.id',0,'intval');
		if($id==1){
			$this->error("最高管理员不能删除！");
		}

		if ($this->users_model->delete($id)!==false) {
			M("ARoleUser")->where(array("user_id"=>$id))->delete();
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

	// 管理员个人信息修改
	public function userinfo(){
		$id=get_current_system_id();
		$user=$this->users_model->where(array("id"=>$id))->find();
		$this->assign($user);
		$this->display();
	}

	// 管理员个人信息修改提交
	public function userinfo_post(){
		if (IS_POST) {
// 			$_POST['id']=sp_get_current_admin_id();
		    $_POST['id']=get_current_system_id();
			$create_result=$this->users_model
			->field("id,user_nicename,sex,birthday,user_email,signature")
			->create();
			if ($create_result!==false) {
				if ($this->users_model->save()!==false) {
					$this->success("保存成功！");
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->users_model->getError());
			}
		}
	}

	// 停用管理员
    public function ban(){
        $id = I('get.id',0,'intval');
    	if (!empty($id)) {
    		$result = $this->users_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status','0');
    		if ($result!==false) {
    			$this->success("管理员停用成功！", U("MUser/index"));
    		} else {
    			$this->error('管理员停用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }

    // 启用管理员
    public function cancelban(){
    	$id = I('get.id',0,'intval');
    	if (!empty($id)) {
    		$result = $this->users_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status','1');
    		if ($result!==false) {
    			$this->success("管理员启用成功！", U("MUser/index"));
    		} else {
    			$this->error('管理员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    
    // 密码修改
    public function password(){
        if (IS_POST) {
            if(empty($_POST['old_password'])){
                $this->error("原始密码不能为空！");
            }
            if(empty($_POST['password'])){
                $this->error("新密码不能为空！");
            }
            $user_obj = $this->users_model;
            $uid=get_current_system_id();
            $admin=$user_obj->where(array("id"=>$uid))->find();
            $old_password=I('post.old_password');
            $password=I('post.password');
            if(sp_compare_password($old_password,$admin['user_pass'])){
                if($password==I('post.repassword')){
                    if(sp_compare_password($password,$admin['user_pass'])){
                        $this->error("新密码不能和原始密码相同！");
                    }else{
                        $data['user_pass']=sp_password($password);
                        $data['id']=$uid;
                        $r=$user_obj->save($data);
                        if ($r!==false) {
                            $this->success("修改成功！");
                        } else {
                            $this->error("修改失败！");
                        }
                    }
                }else{
                    $this->error("密码输入不一致！");
                }
    
            }else{
                $this->error("原始密码不正确！");
            }
        }else{
            $this->display();
        }
    }

}