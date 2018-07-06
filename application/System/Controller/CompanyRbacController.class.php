<?php
namespace System\Controller;

use Common\Controller\SystembaseController;

class CompanyRbacController extends SystembaseController {

	protected $role_model, $auth_access_model;
	//当前类需要的模型
	const MUSERS='m_users',BCOMPANY='b_company', BAUTHACCESS='b_auth_access',
		BROLE='b_role',BROLEUSER='b_role_user', BMENU='b_menu';
	public function _initialize() {
		parent::_initialize();
		$this->role_model = D(self::BROLE);
	}
	/**
	 *  商户角色角色
	 */
	public function index() {
		session('company_rbac_index','CompanyRabc/index');
		$adminid =get_current_system_id();
		$where['gb_b_role.company_id']=0;
		$array1 = $this->role_model->where($where)->field("id,name,status,remark")->select();
		foreach($array1 as $k=>$v){
			$array1[$k]["parentid"]=0;
		}
		$join=" join ".C('DB_PREFIX').'b_role_user as ru on gb_b_role.id=ru.role_id ';
		$join.=" join ".C('DB_PREFIX').'b_company as c on c.company_uid=ru.user_id and c.deleted = 0 ';
		$field="c.company_name as name,role_id as parentid,ru.user_id";
		$array2 = $this->role_model->getList($where,$field,"",$join);
		$rolenum = $this->role_model->order("id desc")->field("id")->find();
		$rolenum = $rolenum["id"];
		foreach($array2 as $k=>$v){
			$rolenum =$rolenum + 1;
			$array2[$k]["id"]=$rolenum;
		}
		$data=array_merge($array1, $array2);
		$result=$data;
		$uid = $this->role_model->alias('r')->join(C('DB_PREFIX').'b_role_user as ru on r.id=ru.role_id')->where('r.company_id = 0 ')->select();
		$this->assign("id", $uid[0]['id']);
		$this->assign("adminid", $adminid);
		$this->assign("roles", $data);

		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';

		$newmenus=array();
		foreach ($result as $m){
			$newmenus[$m['id']]=$m;

		}
		foreach ($result as $n=> $r) {

			//$result[$n]['level'] = $this->_get_level($r['id'], $newmenus);
			$result[$n]['parentid_node'] = ($r['parentid']) ? ' class="child-of-node-' . $r['parentid'] . '"' : '';
			$str_manage="";
			$str_manage .= '<a class="edit fa fa-edit" title="编辑角色" href="' . U('CompanyRbac/roleedit', array('id' => $r['id'])) . '"></a>';
			$str_manage .= '<a class="js-ajax-delete delete fa fa-trash" title="删除角色" href="' . U('CompanyRbac/roledelete', array('id' => $r['id'])) . '"></a>';
			$str_manage .= '<a class="copy fa fa-files-o" title="复制角色权限" href="' . U('CompanyRbac/role_copy', array('id' => $r['id'])) . '"></a>';
			$str_manage2='<a class="js-ajax-delete delete fa fa-trash" href="'.U('CompanyRbac/role_userdelete',array('user_id'=>$r["user_id"],'role_id'=>$r["parentid"])).'"></a>';
			if($r["parentid"]==0){
				$result[$n]['str_manage'] = $str_manage;
				$result[$n]['status'] = $r['status'] ? '<font color="red">√</font>':'<font color="red">╳</font>';
			}else{
				$result[$n]['str_manage'] = $str_manage2;
			}

		}
		$tree->init($result);
		$str = "<tr id='node-\$id' \$parentid_node>
					<td style='padding-left:20px;'></td>
					<td>\$id</td>
        			<td>\$spacer\$name</td>
					<td>\$remark</td>
				    <td>\$status</td>
					<td class='text-center'>\$str_manage</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		$this->assign("categorys", $categorys);
		$this->display();
	}
	/**
	 * 添加角色
	 */
	public function roleadd() {
		if (IS_POST) {
			$Ba = D(self::BAUTHACCESS);
			if(!I("name")){
				$this->error("请输入角色名称！");
			}
			$where['name']=trim(I("name"));
			$binfo=D(self::BROLE)->getInfo($where);
			if($binfo){
				$this->error("角色名称重复！");
			}
			M("")->startTrans();
			$data['name']=trim(I("name"));
			$data['remark']=trim(I("remark"));
			$data['status']=trim(I("status"));
			$id=D(self::BROLE)->insert($data);
			if($id){
				if (is_array($_POST['menuid']) && count($_POST['menuid'])>0) {
					$menu_model=M("BMenu");
					$Ba->where(array("role_id"=>$id,'type'=>'admin_url'))->delete();
					foreach ($_POST['menuid'] as $menuid) {
						$menu=$menu_model->where(array("id"=>$menuid))->field("app,model,action")->find();
						if($menu){
							$app=$menu['app'];
							$model=$menu['model'];
							$action=$menu['action'];
							$name=strtolower("$app/$model/$action");
							$Ba->add(array("role_id"=>$id,"rule_name"=>$name,'type'=>'admin_url'));
						}
					}
					M("")->commit();
					$this->success("添加成功！", U('CompanyRbac/index'));
				}
			}else{
				M("")->rollback();
				$this->error("添加失败！");
			}
		}else{
			$menus=D(self::BMENU)->get_menu_tree();
			$this->assign("menus",$menus);
			$this->display();
		}
	}

	/**
	 * 编辑角色
	 */
	public function roleedit() {
		$Ba = D(self::BAUTHACCESS);
		if(IS_POST){
			if(!I("name")){
				$this->error("请输入角色名称！");
			}
			$id=trim(I("id"));
			$where['name']=trim(I("name"));
			$where['id']=array("neq",$id);
			$binfo=D(self::BROLE)->getInfo($where);
			if($binfo){
				$this->error("角色名称重复！");
			}
			M("")->startTrans();
			$data['name']=trim(I("name"));
			$data['remark']=trim(I("remark"));
			$data['status']=trim(I("status"));
			$wd['id']=trim(I("id"));
			$re=D(self::BROLE)->update($wd,$data);
			if($re!==false){
				//获取该角色下的所有菜单权限
				$old_rule_name=$Ba->getList(array("role_id"=>$id),'rule_name');
				$old_rule_name=array_column($old_rule_name, 'rule_name');
				if (is_array($_POST['menuid']) && count($_POST['menuid'])>0) {
					$menu_model=M("BMenu");
					//$Ba->where(array("role_id"=>$id))->delete();
					$new_authaccess=array();
					foreach ($_POST['menuid'] as $menuid) {
						$menu=$menu_model->where(array("id"=>$menuid))->field("app,model,action")->find();
						$app=$menu['app'];
						$model=$menu['model'];
						$action=$menu['action'];
						$name=strtolower("$app/$model/$action");
						//获取新的菜单权限
						array_push($new_authaccess,$name);
						//修改的权限时，不存在的才添加
						if($menu&&!in_array($name,$old_rule_name)){
							$Ba->add(array("role_id"=>$id,"rule_name"=>$name,'type'=>'business_url'));
						}
					}
					//获取旧的菜单权限存在，但新的菜单权限不存在的菜单权限，并进行删除
					$del_authaccess=array_diff($old_rule_name,$new_authaccess);
					$del_authaccess=implode(',',$del_authaccess);
					$Ba->where(array("role_id"=>$id,'rule_name'=>array('in',$del_authaccess)))->delete();
					$this->success("编辑成功！", U('CompanyRbac/index'));
				}
			}else{
				$this->error("编辑失败！");
			}
		}else{
			$id = I("get.id",0,'intval');
			if ($id == 1) {
				$this->error("超级管理员角色不能被修改！");
			}
			$data = $this->role_model->where(array("id" => $id))->find();
			if (!$data) {
				$this->error("该角色不存在！");
			}
			$menus=D(self::BMENU)->get_menu_tree();
			$this->assign("menus",$menus);
			$list=$Ba->getList(array("role_id"=>$id));
			$m_list=D(self::BMENU)->getList();
			$h_menus=array();
			if($list){
				foreach ($m_list as $k=>$v){
					$app=$v['app'];
					$model=$v['model'];
					$action=$v['action'];
					$name=strtolower("$app/$model/$action");
					foreach ($list as $key=>$val){
						if($name==$val['rule_name']){
							array_push($h_menus,$v['id']);
						}
					}
				}
			}
			$this->assign("h_menus",$h_menus);
			$this->assign("data", $data);
			$this->display();
		}
	}
	/**
	 * 删除用户与角色关联信息
	 */
	public function roledelete() {
		$id = I("get.id",0,'intval');
		if ($id == 1) {
			$this->error("超级管理员角色不能被删除！");
		}
		$role_user_model=M("BRoleUser");
		$count=$role_user_model->join("right join ".C('DB_PREFIX')."m_users as ".C('DB_PREFIX')."m_users on ".C('DB_PREFIX')."m_users.id=".C('DB_PREFIX')."b_role_user.user_id")->where(array('role_id'=>$id))->count();
		if($count>0){
			$this->error("该角色已经有用户！");
		}else{
			$status = $this->role_model->delete($id);
			if ($status!==false) {
				D(self::BAUTHACCESS)->where(array('role_id'=>$id))->delete();
				$this->success("删除成功！", U('CompanyRbac/index'));
			} else {
				$this->error("删除失败！");
			}
		}

	}

	/**
	 * 删除角色
	 */
	public function role_userdelete() {
		$getdata = I("");
		if(!empty($getdata["user_id"])&&!empty($getdata["role_id"])){
			$map["user_id"]=$getdata["user_id"];
			$map["role_id"]=$getdata["role_id"];
			if ($map["role_id"] == 1||$map["user_id"] == 1) {
				$this->error("超级管理员角色不能被删除！");
			}
			$role_user_model=M("BRoleUser");
			$count=$role_user_model->where($map)->count();
			if($count>0){
				$status = $role_user_model->where($map)->delete();
				if ($status!==false) {
					if($getdata['type']=="set_role"){
						$this->success("删除成功！", U('CompanyRbac/set_role'));
					}else{
						$this->success("删除成功！", U('CompanyRbac/index'));
					}
				} else {
					$this->error("删除失败！");
				}
			}
		}

	}

	/**
	 * 复制角色
	 */
	public function role_copy()
	{
		C('TOKEN_ON', false);
		$id = I("request.id", 0, 'intval');
		if ($id == 1) {
			$this->error("超级管理员角色不能被复制！");
		}
		$condition = array();
		$condition['id'] = $id;
		$role = $this->role_model->where($condition)->find();
		$condition = array();
		$condition['name'] = array('like', $role['name'] . '复制%');
		$count = $this->role_model->where($condition)->count();
		$str = '复制';
		if ($count) {
			for ($i = 0; $i < $count; $i++) {
				$str .= '复制';
			}
		}
		if (get_current_admin_id() == 1) {
			$roleuser['role_id'] = 1;
		} else {
			$Role_user = M("Role_user");
			$roleuser = $Role_user->where(array('user_id' => get_current_admin_id()))->Field("role_id")->find();
		}

		$_POST['name'] = $role['name'] . $str;
		$_POST['pid'] = $roleuser['role_id'];
		$_POST['status'] = 1;
		$_POST['remark'] = '复制生成';
		$_POST['listorder'] = 0;
		if ($this->role_model->create() !== false) {
			$roleid = $this->role_model->add();
			if ($roleid !== false) {
				$auth_access_model = D(self::BAUTHACCESS);
				$condition = array();
				$condition['role_id'] = $id;
				$auth_access = $auth_access_model->where($condition)->select();
				if ($auth_access) {
					$data = array();
					foreach ($auth_access as $k => $v) {
						$data2['role_id'] = $roleid;
						$data2['rule_name'] = $v['rule_name'];
						$data2['type'] = $v['type'];
						$data[] = $data2;
					}
					if (!empty($data)) {
						$auth_access_model->addAll($data);
					}

				}
				$this->success("复制角色成功", U("CompanyRbac/index"));
			} else {
				$this->error("复制角色失败！");
			}
		} else {
			$this->error($this->role_model->getError());
		}
	}
	/**
	 *  检查指定菜单是否有权限
	 * @param array $menu menu表中数组
	 * @param int $roleid 需要检查的角色ID
	 */
	private function _is_checked($menu, $roleid, $priv_data)
	{

		$app = $menu['app'];
		$model = $menu['model'];
		$action = $menu['action'];
		$name = strtolower("$app/$model/$action");
		if ($priv_data) {
			if (in_array($name, $priv_data)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

	/**
	 * 获取菜单深度
	 * @param $id
	 * @param $array
	 * @param $i
	 */
	protected function _get_level($id, $array = array(), $i = 0)
	{
		if ($array[$id]['parentid'] == 0 || empty($array[$array[$id]['parentid']]) || $array[$id]['parentid'] == $id) {
			return $i;
		} else {
			$i++;
			return $this->_get_level($array[$id]['parentid'], $array, $i);
		}
	}

	public function role_company(){
		$Ba = D(self::BAUTHACCESS);
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$where=array();
		$where['company_status']=1;
		$where['deleted']=0;
		$field="company_id as id,company_name as name";
		$order="company_id asc";
		$companys=D(self::BCOMPANY)->getList($where,$field,"","",$order);
		$where=array();
		$where['gb_b_company.company_status']=1;
		$where['gb_b_company.deleted']=0;
		$join="join gb_b_role_user as ru on ru.user_id = gb_b_company.company_uid";
		$join.=" join gb_b_role as r on r.id = ru.role_id";
		$field=" gb_b_company.company_id as cid,r.name,r.id";
		$order="gb_b_company.company_id asc";
		$roles=D(self::BCOMPANY)->getList($where,$field,"",$join,$order);
		foreach($companys as $k=>$v){
			$companys[$k]["parentid"]=0;
			$companys[$k]["rid"]=$v['id'];
			$companys[$k]["id"]=-1*$v['id'];
		}
		foreach ($roles as $k=>$v){
			$roles[$k]["rid"]=$v['id'];
			$roles[$k]["parentid"]=-1*$v['cid'];
		}

		$result=array_merge($companys,$roles);
		foreach ($result as $n=> $r) {
			$result[$n]['parentid_node'] = ($r['parentid']) ? ' class="child-of-node-' . $r['parentid'] . '"' : '';
			if($r['parentid']){
				$result[$n]['name']="<a href='javaScript:void(0);' class='show_cr' data-role='".$r['id']."' data-company='".$r['cid']."' data-url='/index.php?g=System&m=CompanyRbac&a=show'>".$result[$n]['name']."</a>";
			}
		}
		$tree->init($result);
		$str = "<tr id='node-\$id' \$parentid_node>
					<td style='text-align:center;'>\$rid</td>
        			<td style='padding-left: 10px;'>\$spacer\$name</td>
				</tr>";
		$companys = $tree->get_tree(0, $str);
		$this->assign("companys", $companys);
		if($roles){
			$id=$roles[0]['id'];
			$data = $this->role_model->where(array("id" => $id))->find();
			$list=$Ba->getList(array("role_id"=>$id));
			$m_list=D(self::BMENU)->getList();
			$h_menus=array();
			if($list){
				foreach ($m_list as $k=>$v){
					$app=$v['app'];
					$model=$v['model'];
					$action=$v['action'];
					$name=strtolower("$app/$model/$action");
					foreach ($list as $key=>$val){
						if($name==$val['rule_name']){
							array_push($h_menus,$v['id']);
						}
					}
				}
			}
			$where=array();
			$where['company_id']=$roles[0]['cid'];
			$cinfo=D(self::BCOMPANY)->getInfo($where);
			$this->assign("cinfo",$cinfo);
			$where=array();
			$where['id']=$roles[0]['id'];
			$crole=D(self::BROLE)->getInfo($where);
			$this->assign("crole",$crole);
		}else{
			$data=array();
			$h_menus=array();
		}
		$menus=D(self::BMENU)->get_menu_tree();
		$this->assign("menus",$menus);
		$shop_menus=D(self::BMENU)->get_menu_tree(0, 1);
		$this->assign("shop_menus",$shop_menus);
		$this->assign("h_menus",$h_menus);
		$this->assign("data", $data);
		$this->display();
	}
	public function set_role(){
		if($_POST){
			$type=I("type");
			$role_id=I("role_id");
			$user_id=I("company_id");
			if($type=="getrc"){
				$role_id=I("role_id");
				$list= D(self::BAUTHACCESS)->getList(array("role_id"=>$role_id));
				$m_list=D(self::BMENU)->getList();
				$h_menus=array();
				if($list){
					foreach ($m_list as $k=>$v){
						$app=$v['app'];
						$model=$v['model'];
						$action=$v['action'];
						$name=strtolower("$app/$model/$action");
						foreach ($list as $key=>$val){
							if($name==$val['rule_name']){
								array_push($h_menus,$v['id']);
							}
						}
					}
				}
				$this->ajaxReturn(array("data"=>$h_menus));
			}else{
				$where['role_id']=$role_id;
				$where['user_id']=$user_id;
				$cinfo=D(self::BROLEUSER)->getInfo($where);
				if(!$cinfo){
					$data=array();
					$data['role_id']=$role_id;
					$data['user_id']=$user_id;
					$da=D(self::BROLEUSER)->insert($data);
					$this->success("添加角色关联成功！", U("CompanyRbac/set_role"));
				}else{
					$this->error("商户已经绑定了该角色请勿重复绑定！");
				}
			}
		}else{
			$Ba = D(self::BAUTHACCESS);
			$tree = new \Tree();
			$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
			$where=array();
			$where['company_status']=1;
			$where['deleted']=0;
			$field="company_id as id,company_name as name,company_uid as uid";
			$order="company_id asc";
			$companys=D(self::BCOMPANY)->getList($where,$field,"","",$order);
			$this->assign("c_company",$companys);
			$where=array();
			$where['gb_b_company.company_status']=1;
			$where['gb_b_company.deleted']=0;
			$join="join gb_b_role_user as ru on ru.user_id = gb_b_company.company_uid";
			$join.=" join gb_b_role as r on r.id = ru.role_id";
			$field=" gb_b_company.company_id as cid,gb_b_company.company_uid as uid,r.name,r.id";
			$order="gb_b_company.company_id asc";
			$roles=D(self::BCOMPANY)->getList($where,$field,"",$join,$order);
			foreach($companys as $k=>$v){
				$companys[$k]["parentid"]=0;
			}
			foreach ($roles as $k=>$v){
				$roles[$k]["parentid"]=$v['cid'];
			}
			$result=array_merge($companys,$roles);
			foreach ($result as $n=> $r) {
				$result[$n]['parentid_node'] = ($r['parentid']) ? ' class="child-of-node-' . $r['parentid'] . '"' : '';
				if($r['parentid']){
					$result[$n]["name"]="<a href='javaScript:void(0);' class='show_cr' data-role='".$r['id']."' data-company='".$r['cid']."' data-url='/index.php?g=System&m=CompanyRbac&a=show'>".$result[$n]['name']."</a>";
					$result[$n]["name"].=$str_manage2='<a class="js-ajax-delete delete fa fa-trash" style="margin-left:10px;" href="'.U('CompanyRbac/role_userdelete',array('type'=>"set_role",'user_id'=>$r["uid"],'role_id'=>$r["id"])).'"></a>';
				}
			}

			$tree->init($result);
			$str = "<tr id='node-\$id' \$parentid_node>
					<td style='text-align:center;'>\$id</td>
        			<td style='padding-left: 10px;'>\$spacer\$name</td>
				</tr>";
			$companys = $tree->get_tree(0, $str);
			$this->assign("companys", $companys);
			if($roles){
				$id=$roles['id'];
				$data = $this->role_model->where(array("id" => $id))->find();
				$list=$Ba->getList(array("role_id"=>$id));
				$m_list=D(self::BMENU)->getList();
				$h_menus=array();
				if($list){
					foreach ($m_list as $k=>$v){
						$app=$v['app'];
						$model=$v['model'];
						$action=$v['action'];
						$name=strtolower("$app/$model/$action");
						foreach ($list as $key=>$val){
							if($name==$val['rule_name']){
								array_push($h_menus,$v['id']);
							}
						}
					}
				}
			}else{
				$data=array();
				$h_menus=array();
			}
			$menus=D(self::BMENU)->get_menu_tree();
			$where=array();
			$where['company_id']=0;
			$roles=D(self::BROLE)->getList($where);
			$this->assign("roles",$roles);
			$this->assign("menus",$menus);
			$this->assign("h_menus",$h_menus);
			$this->assign("data", $data);
			$this->display();
		}
	}
	public function show()
	{
		$role_id = I("role_id");
		if(empty($role_id)){
			die();
		}
		$company_id = I("company_id");
		if(empty($company_id)){
			die();
		}
		$where = array();
		$where['company_id'] = $company_id;
		$cinfo = D(self::BCOMPANY)->getInfo($where);
		$data['cinfo'] = $cinfo;
		$where = array();
		$where['id'] = $role_id;
		$crole = D(self::BROLE)->getInfo($where);
		$data['crole'] = $crole;
		/*change by alam 2018/05/10 start*/
		// 门店角色显示门店权限点
		if (!empty($crole) && $crole['type'] == 1)
		{
			$menus = D(self::BMENU)->get_menu_tree(0, 1);
			$data['menus'] = $menus;
		}
		/*change by alam 2018/05/10 start*/

		$list = D(self::BAUTHACCESS)->getList(array("role_id" => $role_id));
		$m_list = D(self::BMENU)->getList();
		$h_menus = array();
		if ($list) {
			foreach ($m_list as $k => $v) {
				$app = $v['app'];
				$model = $v['model'];
				$action = $v['action'];
				$name = strtolower("$app/$model/$action");
				foreach ($list as $key => $val) {
					if ($name == $val['rule_name']) {
						array_push($h_menus, $v['id']);
					}
				}
			}
		}
		$data['h_menus']=$h_menus;
		$this->ajaxReturn($data);
	}
}

