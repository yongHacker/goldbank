<?php
/**
 * 员工管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BEmployeeController extends BusinessbaseController {

	public function _initialize() {
		$this->bshop_model=D("BShop");
		$this->role_model=D("BRole");
		$this->bemployee_model=D("BEmployee");
		$this->bjobs_model=D("BJobs");
		$this->bsectors_model=D("BSectors");
		$this->b_show_status('BEmployee');
		parent::_initialize();
	}
	public function ts(){
		$this->success("添加成功！初始密码为<span style='color: blue'>123456</span>", U("BEmployee/index"),true,10);
	}
    /**
     * 员工列表
     */
    public function index() {
		$getdata=I("");
		$condition=array("bemployee.deleted"=>0,'bemployee.company_id'=>$this->MUser["company_id"]);
		/*if ($getdata["employee_name"]) {
			$condition["bemployee.employee_name"] = array("like", "%" . $getdata["employee_name"] . "%");
		}*/
		if ($getdata["mobile"]) {
			$condition["bemployee.employee_mobile|bemployee.employee_name"] = array("like", "%" . trim($getdata["mobile"]) . "%");
		}
		if ($getdata["sector_id"]>0) {
			$condition["bemployee.sector_id"] =$getdata["sector_id"];
		}
		if ($getdata["role_id"]>0) {
			$condition["roleuser.role_id"] =$getdata["role_id"];
		}
		if (isset($getdata["status"])&&$getdata["status"]>-1) {
			$condition["bemployee.status"] =$getdata["status"];
		}
		if(I('begin_time')){
			$begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
			$condition['bemployee.create_time'] = array('gt', $begin_time);
		}

		if(I('end_time')){
			$end_time = I('end_time') ? strtotime(I('end_time')) : time();
			if(isset($begin_time)){
				$p1 = $condition['bemployee.create_time'];
				unset($condition['bemployee.create_time']);
				$condition['bemployee.create_time'] = array($p1, array('lt', $end_time));
			}else{
				$condition['bemployee.create_time'] = array('lt', $end_time);
			}
		}
		$condition['bemployee.user_id']=array("neq",1);
		$condition['musers.user_type']=array("neq",4);
		$field='bemployee.*,bjobs.job_name,bsector.sector_name,musers.user_status,musers.mobile,musers.last_login_time';
		$join=" join ".DB_PRE."m_users musers on bemployee.user_id=musers.id";
		$join.=" left join ".DB_PRE."b_jobs bjobs on bemployee.job_id=bjobs.id";
		$join.=" left join ".DB_PRE."b_sectors bsector on bemployee.sector_id=bsector.id";
		if ($getdata["role_id"]>0) {
			$join.=" left join ".DB_PRE."b_role_user roleuser on roleuser.user_id=bemployee.user_id";
		}
		$count = $this->bemployee_model->alias("bemployee")->countList($condition,$field,$join,$order='bemployee.create_time desc',$group='');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data = $this->bemployee_model->alias("bemployee")->getList($condition,$field,$limit,$join,$order='bemployee.create_time desc',$group='');
		foreach($data as $k=>$v){
			$condition=array("roleuser.user_id"=>$v["user_id"],'role.company_id'=>get_company_id());
			$join=' join  '.DB_PRE.'b_role role on role.id=roleuser.role_id ';
			$field='role.name';
			$roles=D('BRoleUser')->alias('roleuser')->getList($condition,$field,$limit,$join);
			$data[$k]["role_name"]=empty($roles)?'':implode(',',array_column($roles, 'name'));
			$field="bshop.shop_name";
			$condition=array("bshopemployee.user_id"=>$v["user_id"],"bshopemployee.employee_id"=>$v["id"]);
			$join="left join ".DB_PRE."b_shop bshop on bshopemployee.shop_id=bshop.id";
			$shop_name=D("BShopEmployee")->alias("bshopemployee")->getList($condition,$field,$limit="",$join,$order='',$group='');
			$data[$k]["shop_name"]=empty($shop_name)?'':implode(',',array_column($shop_name, 'shop_name'));;
		}
		//获取角色
		$roles=$this->role_model->where(array('shop_id'=>array('gt',-1),'status' => 1,'company_id'=>$this->MUser['company_id']))->order("id DESC")->select();
		$this->assign("roles",$roles);
		//部门数据
		$select_categorys=$this->bsectors_model->get_bsectors_tree($getdata["sector_id"]);
		$this->assign("select_categorys", $select_categorys);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
    }
	// 员工添加
	public function add(){
		$postdata=I("post.");
		if(empty($postdata)){
			$this->get_edit_add_info($employee_id=0,$type=1);
			$this->display();
		}else{
			if (IS_POST) {
				$role_ids = I('post.role_id/a');
				$shop_ids = I('post.shop_id/a');
				unset($postdata['role_id']);
				$_POST['user_login']=$postdata["mobile"];
				
				$condition=array("mobile"=>$postdata["mobile"]);
				if(empty($postdata["mobile"])){
					$this->error("手机号不能为空！");
				}

				if(!check_mobile($postdata["mobile"], $postdata["mobile_area"])){
					$this->error("手机号格式有误！");
				}
				M()->startTrans();

				$userinfo=$this->users_model->getInfo($condition,$field="id",$join="");
				$bemployee_data=array();
				$bemployee_data['employee_name']=$postdata["user_nicename"];
				$bemployee_data['employee_mobile']=$postdata["mobile"];
				$bemployee_data['mobile_area']=$postdata["mobile_area"];
				$bemployee_data['company_id']=$this->MUser['company_id'];
				$bemployee_data['shop_id']=$this->MUser['shop_id'];
				$bemployee_data['sector_id']=$postdata["sector_id"];
				$bemployee_data['job_id']=$postdata["job_id"];
				$bemployee_data['status']=$postdata["status"];
				$bemployee_data['deleted']=0;
				$bemployee_data['creator_id']=$this->MUser['id'];
				$bemployee_data['create_time']=time();
				$bemployee_data['updater_id']=$this->MUser['id'];
				$bemployee_data['update_time']=time();
				unset($_POST['shop_id']);
				unset($_POST['sector_id']);
				unset($_POST['job_id']);
				if(empty($userinfo)){
					$_POST['user_pass']=$this->default_password;
					if ($this->users_model->create()!==false) {
						$data=array();
						$data['company_id']=$this->MUser['company_id'];
						$data["user_login"]=$postdata["mobile"];
						$data["mobile"]=$postdata["mobile"];
						$data["mobile_area"]=$postdata["mobile_area"];
						$data["user_pass"]=$this->default_password;
						$data["user_nicename"]=$postdata["user_nicename"];
						$data["create_time"]=time();
						$data["sex"]=$postdata["sex"];
						$data["operate_user_id"]=$this->MUser["id"];
						$data["operate_ip"]=get_client_ip(0,true);
						$result=$this->users_model->insert($data);
						if ($result!==false) {
							$bemployee_data['user_id']=$result;
							$bemployee=$this->bemployee_model->insert($bemployee_data);
							if($bemployee!==false){
								M()->commit();
								$this->add_role_shop($role_ids,$shop_ids,$bemployee,$result,$type=1);
								$this->success("添加成功！初始密码为<span style='color: red'>".$this->default_password."</span>", U("BEmployee/index"),true,5);
							}else{
								M()->rollback();
								$this->error("添加失败！");
							}
						} else {
							$this->error("添加失败！");
						}
					} else {
						$this->error($this->users_model->getError());
					}
				}else{
					$condition=array("user_id"=>$userinfo['id'],"company_id"=>$this->MUser["company_id"],"deleted"=>0);
					$bemployeeinfo = $this->bemployee_model->alias("bemployee")->getInfo($condition,$field="id");
					if(!empty($bemployeeinfo)){
						$this->error("员工信息已经存在,请勿重复添加！");
					}
					if ($this->bemployee_model->create()!==false) {
						$bemployee_data['user_id']=$userinfo['id'];
						$bemployee=$this->bemployee_model->insert($bemployee_data);
						if($bemployee!==false){
							M()->commit();
							//指派角色和门店
							$this->add_role_shop($role_ids,$shop_ids,$bemployee,$userinfo['id'],$type=1);
							$this->success("添加成功！密码为该手机号在金行家云掌柜系统密码", U("BEmployee/index"),true,5);
						}else{
							M()->rollback();
							$this->error("保存失败！");
						}
					} else {
						$this->error($this->bemployee_model->getError());
					}
				}
			}else{
				$this->error("保存失败！");
			}
		}
	}
	// 员工编辑
	public function edit(){
		$postdata=I("post.");
		if(empty($postdata)){
			$employee_id = I('get.id',0,'intval');
			$this->get_edit_add_info($employee_id,$type=0);
			$this->display();
		}else{
			if (IS_POST) {

					$employee_id=I('post.id',0,'intval');
					$uid = I('post.user_id',0,'intval');
					$role_ids = I('post.role_id/a');
					$shop_ids = I('post.shop_id/a');
					unset($_POST['role_id']);
					M()->startTrans();
					$condition=array("id"=>$employee_id);
					$data=array("employee_name"=>$postdata["employee_name"]);
					$data["sector_id"]=$postdata["sector_id"];
					$data['status']=$postdata["status"];
					$data["job_id"]=$postdata["job_id"];
					$data["updater_id"]=$this->MUser["id"];
					$data["update_time"]=time();
					$result=$this->bemployee_model->update($condition,$data);
					if ($result!==false) {
						M()->commit();
						//指派角色和门店
						if(!empty($postdata['role_id'])&&!empty($postdata['id'])&& is_array($postdata['role_id'])) {
							$this->add_role_shop($role_ids, $shop_ids, $employee_id, $uid, $type = 0);
						}
						$this->success("保存成功！", U("BEmployee/index"));
					} else {
						M()->rollback();
						$this->error("保存失败！");
					}
			}else{
				$this->error("添加失败！");
			}
		}

	}
	//删除员工
	public function delete() {
		$postdata=I("");
		$data=array();
		$data["deleted"]=1;
		$condition=array("bemployee.id"=>$postdata["id"],"bemployee.company_id"=>get_company_id());
		$field='bemployee.*,musers.user_status,musers.mobile,musers.last_login_time';
		$join="join ".DB_PRE."m_users musers on bemployee.user_id=musers.id";
		$employee_info=$this->bemployee_model->alias("bemployee")->getInfo($condition,$field,$join,$order="");
		if($employee_info['employee_login_time']>0){
			$this->error("已经登录过的员工无法删除！");
		}
		$condition=array("id"=>$postdata["id"],"company_id"=>get_company_id());
		$bgoodsclass=$this->bemployee_model->update($condition,$data);
		if ($bgoodsclass) {
			$this->success("删除成功！", U("BEmployee/index"));
		} else {
			$this->error("删除失败！");
		}
	}
	//指派角色和门店
	/**
	 * @param $role_ids 门店角色组
	 * @param $shop_ids 门店组
	 * @param $employee_id  员工id
	 * @param $uid         用户id
	 * @param int $type   是否删除用户角色和门店员工信息
	 * @return bool
	 */
	public function add_role_shop($role_ids,$shop_ids,$employee_id,$uid,$type=1) {
		//指派角色
		$role_user_model=D("BRoleUser");
		if($type!=1){
			$role_user_model->where(array("user_id"=>$uid,'company_id'=>get_company_id()))->delete();
		}
		foreach ($role_ids as $role_id){
			if(get_user_id() != 1 && $role_id == 1){
				$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
			}
			$role_user_model->add(array("role_id"=>$role_id,"user_id"=>$uid,"company_id"=>get_company_id()));
		}
		//指派门店
		$shop_employee_model=D("BShopEmployee");
		if($type!=1){
			$shop_employee_model->where(array("user_id"=>$uid,"employee_id"=>$employee_id))->delete();
		}
		foreach ($shop_ids as $shop_id){
			$shop_employee_model->add(array("shop_id"=>$shop_id,"employee_id"=>$employee_id,"user_id"=>$uid));
		}
		return true;
	}
	//获取员工添加编辑需要的信息
	public function get_edit_add_info($employee_id,$type=1) {
		//门店
		$condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"]);
		$shop=$this->bshop_model->alias("bshop")->getList($condition,$field='bshop.*',$limit=null,$join="");
		$this->assign("shop", $shop);
		if($type!=1&&$employee_id>0){
			//员工信息
			$condition=array("bemployee.id"=>$employee_id);
			$field='bemployee.*,musers.mobile';
			$join="left join ".DB_PRE."m_users musers on bemployee.user_id=musers.id";
			$bemployee = $this->bemployee_model->alias("bemployee")->getInfo($condition,$field,$join,$order="");
			$this->assign("bemployee",$bemployee);
			$condition=array("user_id"=>$bemployee["user_id"]);
			//获取已经拥有的角色
			$role_user_model=M("BRoleUser");
			$role_ids=$role_user_model->where($condition)->getField("role_id",true);
			$this->assign("role_ids",$role_ids);
			//判断是否商户负责人
			if (check_company_uid($bemployee["user_id"])) {
				$this->error("指定商户负责人不能修改");
			}
		}else{
			$condition=array();
		}
		//获取门店id
		$shop_employee_model=M("BShopEmployee");
		$shop_ids=$shop_employee_model->where($condition)->getField("shop_id",true);
		$this->assign("shop_ids",$shop_ids);
		//获取总部角色
		$roles=$this->role_model->where(array('type'=>0,'shop_id'=>array('gt',-1),'status' => 1,'company_id'=>$this->MUser['company_id']))->order("id DESC")->select();
		$this->assign("roles",$roles);
		//获取门店角色
		$shop_roles=$this->role_model->where(array('type'=>1,'shop_id'=>array('gt',-1),'status' => 1,'company_id'=>$this->MUser['company_id']))->order("id DESC")->select();
		$this->assign("shop_roles",$shop_roles);
		//部门数据
		$select_categorys=$this->bsectors_model->get_bsectors_tree($bemployee["sector_id"]);
		$this->assign("select_categorys", $select_categorys);
		////岗位数据
		$condition=array('deleted'=>0,'company_id'=>$this->MUser["company_id"]);
		$bjobs=$this->bjobs_model->getList($condition,$field="*",$limit=null,$join="",$order='',$group='');
		$bjobs=json_encode($bjobs);
		$this->assign("bjobs",$bjobs);
	}
	public function info(){		//员工信息页面
		if(I('get.user_id')){
			$status=array('离职','在职');
			$join='right join '.C('DB_PREFIX').'m_users as u on bemployee.user_id=u.id ';
			$join.=' left join '.C('DB_PREFIX').'b_sectors as s on bemployee.sector_id=s.id ';
			$join.=' left join '.C('DB_PREFIX').'b_jobs as j on bemployee.job_id=j.id ';
			$field="bemployee.*,u.id,u.user_nicename,u.avatar,s.sector_name,j.job_name";
			$where='bemployee.user_id ='.I('get.user_id').' and bemployee.deleted=0';
			$where=array("bemployee.user_id"=>I('get.user_id'),"bemployee.deleted"=>0,"bemployee.company_id"=>get_company_id());
			$userdetail = D("BEmployee")->alias("bemployee")->getInfo($where,$field,$join);
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
}

