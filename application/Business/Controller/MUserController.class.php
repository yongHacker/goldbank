<?php
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class MUserController extends BusinessbaseController{

	protected $users_model;

	public function __construct(){
		parent::__construct();
	}

	public function _initialize() {
		parent::_initialize();
		$this->users_model = D("MUsers");
	}

	// 管理员个人信息修改
	public function userinfo(){
		$id=get_user_id();
		$user=$this->users_model->where(array("id"=>$id))->find();
		$this->assign($user);
		$this->display();
	}

	// 管理员个人信息修改提交
	public function userinfo_post(){
		if (IS_POST) {
// 			$_POST['id']=sp_get_current_admin_id();
		    $_POST['id']=get_user_id();
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
            $uid=get_user_id();
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