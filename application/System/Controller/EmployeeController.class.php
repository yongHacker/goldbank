<?php
namespace System\Controller;

use Common\Controller\SystembaseController;

class EmployeeController extends SystembaseController {
//当前类需要的模型
	const MUSERS='m_users',AEMPLOYEE='a_employee',AJOBS='a_jobs';
	public function __construct(){
		parent::__construct();
	}

	public function index()
	{
		// 员工状态
		$status_value = I('status_value/d', -2);
		if ($status_value >= 0) {
			$map["gb_a_employee.status"] = $status_value;
		}
		$this->assign("status_value",$status_value);

		$name = trim(I('search_name/s'));
		if (!empty($name)) {
			$map["u.realname|u.user_nicename|u.mobile|s.sector_name|j.job_name|gb_a_employee.workid"] = array('like', '%' . $name . '%');
		}

		$map["gb_a_employee.deleted"] = 0;
		$map["gb_a_employee.user_id"] = array('neq', 1);

		$join = 'right join '.C('DB_PREFIX').'m_users as u on gb_a_employee.user_id=u.id';
		$join .= ' left join '.C('DB_PREFIX').'a_sectors as s on gb_a_employee.sector_id=s.id ';
		$join .= ' left join '.C('DB_PREFIX').'a_jobs as j on gb_a_employee.job_id=j.id ';

		$field = "u.id,gb_a_employee.id as eid,gb_a_employee.status,gb_a_employee.workid,gb_a_employee.sector_id,IFNULL(u.realname,u.user_nicename)user_nicename,u.mobile,u.sex,u.user_email,s.sector_name,j.job_name ";
		$order = "gb_a_employee.update_time desc";
		$employee = D(self::AEMPLOYEE);

		$count = $employee->countList($map, $field, $join);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.','.$page->listRows;

		$sex = array('保密','男','女');
		$status = array('离职','在职');
		$yggldetail = $employee->getList($map, $field, $limit, $join, $order);

		foreach ($yggldetail as $k => $v) {
			$role = M('a_role_user')->alias('u')->where('u.user_id=' . $v['id'])->join('left join ' . C('DB_PREFIX') . 'a_role r on r.id =u.role_id')->field('r.name')->select();

			if (count($role) == 1) {
				$yggldetail[$k]['role_name'] = $role[0]['name'];
			} else {
				$role_name = '';
				foreach ($role as $k2 => $v2) {
					$role_name .= $v2['name'] . '、';
				}
				$yggldetail[$k]['role_name'] = $role_name;
			}
		}

		$this->assign('role', $role);
		$this->assign('yggldetail', $yggldetail);
		$this->assign('page', $page->show("Admin"));// 赋值分页输出
		$this->assign('status', $status);
		$this->assign('sex', $sex);

		$this->display();
    }
	
	// 员工添加页面
	public function add(){
		if(IS_POST){
			$arr['moblie'] = $_POST['mobile'];
			$mo = M('m_users')->field('id,mobile')->where('mobile ='.$_POST['mobile'])->find();
			$admin_id = get_current_system_id();

			if(!empty($mo)){

				$employee_info = M('AEmployee')->where('user_id='. $mo['id'])->find();

				if(empty($employee_info)){
					$arr['employee_name'] =$_POST['user_nicename'];
					$arr['mobile_area'] =$_POST['mobile_area'];
					$arr['employee_mobile'] =$_POST['mobile'];
					$arr['status'] =$_POST['status'];
					$arr['workid'] =$_POST['workid'];
					$arr['sector_id'] =$_POST['sector_id'];
					$arr['job_id'] =$_POST['job_id'];
					$arr['user_id'] =$mo['id'];
					$arr['creator_id']=$admin_id;
					$arr['create_time']=time();
					$arr['updater_id']=$admin_id;
					$arr['update_time']=time();
					$list = M('AEmployee')->add($arr);
					if($list){
						$this->success("添加成功,请给员工分配角色！",U("Employee/index"));
					}
				}else{
					$this->error('已存在该手机号的员工信息');
				}
			}else{
				$arr2['user_login'] =$_POST['mobile'];
				$arr2['mobile'] =$_POST['mobile'];
				$arr2['sex'] =$_POST['sex'];
				$arr2['user_nicename'] =$_POST['user_nicename'];
				$arr2['create_time'] =time();
				$arr2['user_pass'] =sp_password("123456");
				$li = D(self::MUSERS)->insert($arr2);
				if($li){
					$arr['employee_name'] =$_POST['user_nicename'];
					$arr['mobile_area'] =$_POST['mobile_area'];
					$arr['employee_mobile'] =$_POST['mobile'];
					$arr['status'] =$_POST['status'];
					$arr['workid'] =$_POST['workid'];
					$arr['sector_id'] =$_POST['sector_id'];
					$arr['job_id'] =$_POST['job_id'];
					$arr['user_id'] =$li;
					$arr['create_time'] =time();
					$arr['creator_id']=$admin_id;
					$arr['updater_id']=$admin_id;
					$arr['update_time']=time();
					if(M('AEmployee')->add($arr)){
						$this->success("添加成功,请给员工分配角色！",U("Yuangongguanli/index"));
					}
				}
			}
		}else{
			$bmlist = M('a_sectors')->where('gb_a_sectors.deleted=0')->join("gb_a_jobs as aj on aj.sector_id = gb_a_sectors.id and aj.deleted = 0")->Field('gb_a_sectors.id,gb_a_sectors.sector_name')->group("gb_a_sectors.id")->select();  //获取部门列表（添加用户需选择部门）
			$this->assign('bmlist',$bmlist);
			$this->display();
		}
    }

	public function set_role(){
		if (IS_POST) {
			if(empty($_POST['user_pass'])){
				unset($_POST['user_pass']);
			}else{
				D(self::MUSERS)->create()!==false;
				$result=D(self::MUSERS)->save();
			}
			if ($result!==true) {
				$uid = I('post.id',0,'intval');
				$role_user_model=M("ARoleUser");
				$role_user_model->where(array("user_id" => $uid))->delete();
				$role_ids = I('post.role_id/a');
				$days_array = explode(',', $role_ids[0]);
				unset($_POST['role_id']);
				foreach ($days_array as $role_id){
					if (sp_get_current_system_id() != 1 && $role_id == 1) {
						$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
					}
					if($role_id>0){
						$role_user_model->add(array("role_id"=>$role_id,"user_id"=>$uid));
					}
				}
				$data['status']='1';
				$data['url']=U('Employee/index');
			} else {
				$data['status']='0';
				$data['msg']='保存失败！';
			}
			die(json_encode($data));
		}else{
			$id = I('get.id',0,'intval');
			$adminid =get_current_system_id();
			if($adminid ==1){
				$roles = M('a_role')->where(array('status' => 1, 'shop_id' => 0))->order("id DESC")->select();
			}else{
				$roles = M('a_role')->join('gb_a_role_user on jb_a_role.pid = jb_a_role_user.role_id')->where(array('jb_a_role.status' => 1, 'jb_a_role_user.user_id' => $adminid, 'shop_id' => 0))->order("id DESC")->select();
			}
			$user=M('m_users')->where(array("id"=>$id))->find();
			$role_ids=M('a_role_user')->where(array("user_id"=>$id))->getField("role_id",true);
			$this->assign("role_ids",$role_ids);
			$this->assign("roles",$roles);
			$this->assign('user',$user);
			$this->display();
		}
	}

	// 员工编辑页面
	public function edit(){
		if(I('post.user_id')){
			$admin_id = get_current_system_id();
			$arr['status'] = I('status/d', 1);
			$arr['sector_id'] = I('sector_id/d', 0);
			$arr['job_id'] = I('job_id/d', 0);
			$arr['updater_id'] = $admin_id;
			$arr['update_time'] = time();

			$where['user_id']=I("post.user_id");
			$result = D(self::AEMPLOYEE)->update($where, $arr);

			$whe['id'] = I("user_id/d", 0);
			$user["sex"] = I('sex/d', 0);

			D(self::MUSERS)->update($whe,$user);
			if($result !== false){
				$update['status']=1;
				$num = M('AEmployee')->where('workid<='.I('post.page').' and deleted=0')->order('workid')->count();
				$update['page']=ceil($num/10);
			}

			$this->ajaxReturn($update);
		}else {
			if (I('get.u_id')) {
				$where = 'gb_a_employee.user_id=' . I('get.u_id') . ' and gb_a_employee.deleted=0';

				$join = 'right join ' . C('DB_PREFIX') . 'm_users as u on gb_a_employee.user_id=u.id';
				$join .= ' left join ' . C('DB_PREFIX') . 'a_sectors as s on gb_a_employee.sector_id=s.id ';
				$join .= ' left join ' . C('DB_PREFIX') . 'a_jobs as j on gb_a_employee.job_id=j.id ';

				$field = 'gb_a_employee.*,u.id,u.user_nicename,s.sector_name,j.job_name,u.sex';
				$result = D(self::AEMPLOYEE)->getInfo($where, $field, $join);

				// 获取部门列表（添加用户需选择部门）
				$bmlist = M('a_sectors')->where('gb_a_sectors.deleted=0')->join("gb_a_jobs as aj on aj.sector_id = gb_a_sectors.id and aj.deleted = 0")->Field('gb_a_sectors.id,gb_a_sectors.sector_name')->group("gb_a_sectors.id")->select();  

				// 获取岗位列表（添加用户需选择部门）
				$where = array(
					'job_pid'=> $result['sector_id'],
					'deleted'=> 0
				);

				if($result['sector_id'] == 0){
					$where['job_pid'] = empty($$bmlist) ? 0 : $bmlist[0]['id'];
				}

				$gwlist = D(self::AJOBS)->getList($where, "id, job_name");
				$ygzt = array('离职', '在职');

				$this->assign('result', $result);
				$this->assign('ygzt', $ygzt);
				$this->assign('gwlist', $gwlist);
				$this->assign('bmlist', $bmlist);

				$this->display();
			} else {
				$this->error('访问出错');
			}
		}
    }

	public function info(){		//员工信息页面
		if(I('get.user_id')){
			$status=array('离职','在职');
			$join='right join '.C('DB_PREFIX').'m_users as u on gb_a_employee.user_id=u.id ';
			$join.=' left join '.C('DB_PREFIX').'a_sectors as s on gb_a_employee.sector_id=s.id ';
			$join.=' left join '.C('DB_PREFIX').'a_jobs as j on gb_a_employee.job_id=j.id ';
			$field="gb_a_employee.*,u.id,u.user_nicename,u.avatar,s.sector_name,j.job_name";
			$where='gb_a_employee.user_id ='.I('get.user_id').' and gb_a_employee.deleted=0';
			$userdetail = D(self::AEMPLOYEE)->getInfo($where,$field,$join);
			foreach($userdetail as &$v){
				if($v=='0' ||$v=='0000-00-00'){
					$v='';
				}
			}
			$this->assign('userdetail',$userdetail);
			$this->assign('status',$status);
			$this->display();
		}
		else{
			$this->error('访问出错');
		}
    }

	public function getlastwork(){      
		// 获取最大工号+1
		if(I('post.getlastwkid')){
			$re = M('a_employee')->order('workid desc')->field('workid')->find();
			$this->ajaxReturn(($re['workid']+1));
		}
	}
	
	// ajax查询用户是否存在
	public function user_exist(){
		if (trim($_POST['mobile'])) {
			$where = array(
				'mobile' => trim($_POST['mobile']),
				'user_status' => 1
			);
			$role = M('a_employee as y')->join('__M_USERS__ as u on u.id = y.user_id')->where($where)->select();
			$list = M('m_users')->field('user_nicename')->where($where)->select();
			$arr = array(
				'status' => 0
			);
			if ($role) {
				$arr['status'] = 1;
			} else {
				$arr['user_nicename'] = $list[0]['user_nicename'];
			}
			$this->ajaxReturn($arr);
		}
	}

	// 部门、岗位两级联动
	public function bm_gw(){
		if(I('post.bm_id')){
			$result = M('a_jobs')->where('sector_id='.I('post.bm_id').' and deleted=0')->Field('id,job_name')->select();
			$update['data']=$result;
			$update['status']=1;
		}
		else{
			$update['status']=0;
		}
		$this->ajaxReturn($update);
    }

	public function upload()
	{
		import('Org.Util.CropAvatar');
		
		$src=$_POST['avatar_src'];
		$data=$_POST['avatar_data'];
		$file= $_FILES['avatar_file'];
		$src=$_POST['avatar_src'];
		$path = 'Uploads/Employee/';
		$crop = new \CropAvatar($src,$data,$file,time()."_b","b",$path);
		
		$time=time();  			
		$pic['avatar'] = '/'.$crop -> getResult().'?t='.$time;
		  
		if(empty($response['message'])&&$crop -> getResult()){
			$response = array(
				'state'  => 200,
				'message' => $crop -> getMsg(),
				'result' => $pic['avatar']
			);
		  
			//M('user')->where('id='.$_GET['user_id'])->save($pic['avatar']);
			//$image = new \Think\Image(); 
			//$image->open("./Uploads/User/image/".$_GET['username']."_b.jpeg");
			// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
			//$image->thumb(40,40)->save("./Uploads/User/image/".$_GET['username']."_s.jpeg");
		}else{
			$response = array(
				'state'  => 200,
				'message' => "上传失败",
				'result' => $pic['avatar']
			);
		}

		echo json_encode($response);
	}

}