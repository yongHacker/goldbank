<?php
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class RbacController extends BusinessbaseController {

    protected $role_model, $auth_access_model;

    public function _initialize() {
        parent::_initialize();
		$this->bshop_model=D("BShop");
        $this->role_model = D("BRole");
		$this->role_user_model=D("BRoleUser");

		$this->bshop_model=D("BShop");
		$this->role_model=D("BRole");
		$this->bemployee_model=D("BEmployee");
		$this->bjobs_model=D("BJobs");
		$this->bsectors_model=D("BSectors");
    }

    // 角色管理列表
    public function index() {
		session('admin_menu_index','Menu/index');
		$adminid =get_user_id();
		if(!check_company_uid()){
			$Role_user= M("b_role_user");
			$roleuser=$Role_user->where(array('user_id'=>$adminid,'company_id'=>get_company_id()))->getField("role_id",true);
			$map["role.company_id"] =get_company_id();
			$map["role.type"] = 0;
			$map["role.shop_id"] = array('gt',-1);

			$array1 = $this->role_model->alias('role')->where($map)->field("id,name,status,remark")->select();

			foreach($array1 as $k=>$v){
				$array1[$k]["parentid"]=0;
			}

			$array2 = $this->role_model->alias('role')
				->join(C('DB_PREFIX').'b_role_user as role_user on role.id=role_user.role_id')
				->join(C('DB_PREFIX').'m_users as musers on musers.id=role_user.user_id')
				->join(' left join '.C('DB_PREFIX').'b_employee as b_employee on b_employee.user_id=role_user.user_id and b_employee.deleted=0 and b_employee.company_id='.get_company_id())
				->where($map)
				->field("CONCAT(b_employee.employee_name,'(',b_employee.employee_mobile,')') as name1,role_id as parentid,role_user.user_id,musers.realname,musers.user_login,musers.user_nicename,musers.mobile")
				->select();
			$rolenum = $this->role_model->alias('role')->order("id desc")->field("id")->find();
			$rolenum=$rolenum["id"];
			foreach($array2 as $k=>$v){
				$array2[$k]['name']=$v['name1'];//($v['realname']?$v['realname']:$v['user_nicename']?$v['user_nicename']:$v['user_login'])."(".$v['mobile'].")";
				$rolenum =$rolenum + 1;
				$array2[$k]["id"]=$rolenum;
			}
			$result=array_merge($array1, $array2);

		}else{
			//获取当前商户所有角色
			$a_c_map['role.type'] = 0;
			$a_c_map['role.company_id'] = get_company_id();
			$a_c_map["role.shop_id"] = array('gt',-1);
			$array1 = $this->role_model->alias('role')->where($a_c_map)->field("id,name,status,remark")->select();
			foreach($array1 as $k=>$v){
				$array1[$k]["parentid"]=0;
			}
			//获取角色下的用户
			$array2 = $this->role_model
				->alias('role')
				->join(C('DB_PREFIX').'b_role_user as role_user on role.id=role_user.role_id')
				->join(C('DB_PREFIX').'m_users as musers on musers.id=role_user.user_id')
				->join(' left join '.C('DB_PREFIX').'b_employee as b_employee on b_employee.user_id=role_user.user_id and b_employee.deleted=0 and b_employee.company_id='.get_company_id())
				->where($a_c_map)
				->field("CONCAT(b_employee.employee_name,'(',b_employee.employee_mobile,')') as name1,role_id as parentid,role_user.user_id,musers.realname,musers.user_login,musers.user_nicename,musers.mobile")
				->select();
			$rolenum = $this->role_model->alias('role')->order("id desc")->field("id")->find();
			$rolenum=$rolenum["id"];
			//重构角色用户数组
			foreach($array2 as $k=>$v){
				$array2[$k]['name']=$v['name1'];//($v['realname']?$v['realname']:$v['user_nicename']?$v['user_nicename']:$v['user_login'])."(".$v['mobile'].")";
				$rolenum =$rolenum + 1;
				$array2[$k]["id"]=$rolenum;
			}
			$result=array_merge($array1, $array2);
		}
		$uid = $this->role_model->alias('role')->join(C('DB_PREFIX').'b_role_user as role_user on role.id=role_user.role_id')->where('role_user.user_id ='.$adminid)->select();

		$this->assign("id", $uid[0]['id']);
		$this->assign("adminid", $adminid);
		$this->assign("roles", $result);



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
			//判断是否超管，是否是当前角色//操作内容展示

			if($r['id']==1 || $r['id']==$uid[0]['id']){
				$str_manage.='<font color="#cccccc" class="edit fa fa-gavel"></font>';
				$str_manage .= '<font color="#cccccc" class="edit fa fa-edit"></font>';
				$str_manage .= '<font color="#cccccc" class="delete fa fa-trash"></font>';
				$str_manage2='<font color="#cccccc" class="delete fa fa-trash"></font>';;
			}else{
				//不是超管时，判断角色和角色下用户是否能删除，能操作
				if($r['id']==$uid[0]['id']||$r['parentid']==$uid[0]['id']){
					$str_manage.='<font color="#cccccc" class="edit fa fa-gavel"></font>';
					$str_manage .= '<font color="#cccccc" class="edit fa fa-edit"></font>';
					$str_manage .= '<font color="#cccccc" class="delete fa fa-trash"></font>';
					$str_manage2='<font color="#cccccc " class="delete fa fa-trash"></font>';;
				}else{
					$str_manage .='<a href="#myModal3" class="myModal3 leave" title="绑定员工"  data-toggle="modal" data-value="'.$r['id'].'" role="button"><span><i class="fa fa-plus normal"></i></span></a>';
					$str_manage .= '<a class="edit fa fa-gavel" title="角色授权" href="' . U('Rbac/set_role', array('id' => $r['id'])) . '"></a>';
					$str_manage .= '<a class="edit fa fa-edit" title="编辑角色" href="' . U('Rbac/roleedit', array('id' => $r['id'])) . '"></a>';
					$str_manage .= '<a class="js-ajax-delete delete fa fa-trash" title="删除角色" href="' . U('Rbac/roledelete', array('id' => $r['id'])) . '"></a>';
					/*$str_manage .= '<a class="copy fa fa-files-o" title="复制角色权限" href="' . U('Rbac/role_copy', array('id' => $r['id'])) . '"></a>';*/
					$str_manage2='<a class="js-ajax-delete delete fa fa-trash" href="'.U('Rbac/role_userdelete',array('user_id'=>$r["user_id"],'role_id'=>$r["parentid"])).'"></a>';

				}
			}
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
					<td class='text-center'>\$id</td>
        			<td>\$spacer\$name</td>
					<td>\$remark</td>
				    <td class='text-center'>\$status</td>
					<td class='text-center'>\$str_manage</td>
				</tr>";

		$categorys = $tree->get_tree(0, $str);
		$this->assign("categorys", $categorys);
		$this->display();

    }

	// 门店角色管理列表
	public function shop_index() {
		session('admin_menu_index','Menu/index');
		$adminid =get_user_id();
		if(!check_company_uid()){
			$Role_user= M("b_role_user");
			$roleuser=$Role_user->where(array('user_id'=>$adminid,'company_id'=>get_company_id()))->getField("role_id",true);
			$map["role.company_id"] =get_company_id();
			$map["role.type"] =1;
			$map["role.shop_id"] = array('gt',-1);

			$array1 = $this->role_model->alias('role')->where($map)->field("id,name,status,remark")->select();
			foreach($array1 as $k=>$v){
				$array1[$k]["parentid"]=0;
			}

			$array2 = $this->role_model->alias('role')
				->join(C('DB_PREFIX').'b_role_user as role_user on role.id=role_user.role_id')
				->join(C('DB_PREFIX').'m_users as musers on musers.id=role_user.user_id')
				->join(' left join '.C('DB_PREFIX').'b_employee as b_employee on b_employee.user_id=role_user.user_id and b_employee.deleted=0 and b_employee.company_id='.get_company_id())
				->where($map)
				->field("CONCAT(b_employee.employee_name,'(',b_employee.employee_mobile,')') as name1,role_id as parentid,role_user.user_id,musers.realname,musers.user_login,musers.user_nicename,musers.mobile")
				->select();
			$rolenum = $this->role_model->alias('role')->order("id desc")->field("id")->find();
			$rolenum=$rolenum["id"];
			foreach($array2 as $k=>$v){
				$array2[$k]['name']=$v['name1'];//($v['realname']?$v['realname']:$v['user_nicename']?$v['user_nicename']:$v['user_login'])."(".$v['mobile'].")";
				$rolenum =$rolenum + 1;
				$array2[$k]["id"]=$rolenum;
			}
			$result=array_merge($array1, $array2);

		}else{
			//获取当前商户所有角色
			$a_c_map["role.shop_id"] = array('gt',-1);
			$a_c_map['role.type'] = 1;
			$a_c_map['role.company_id'] = get_company_id();
			$array1 = $this->role_model->alias('role')
				->where($a_c_map)->field("role.id,role.name,role.status,role.remark")->select();
			foreach($array1 as $k=>$v){
				$array1[$k]["parentid"]=0;
			}
			//获取角色下的用户
			$array2 = $this->role_model
				->alias('role')
				->join(C('DB_PREFIX').'b_role_user as role_user on role.id=role_user.role_id')
				->join(C('DB_PREFIX').'m_users as musers on musers.id=role_user.user_id')
				->join(' left join '.C('DB_PREFIX').'b_employee as b_employee on b_employee.user_id=role_user.user_id and b_employee.deleted=0 and b_employee.company_id='.get_company_id())
				->where($a_c_map)
				->field("CONCAT(b_employee.employee_name,'(',b_employee.employee_mobile,')') as name1,role_id as parentid,role_user.user_id,musers.realname,musers.user_login,musers.user_nicename,musers.mobile")
				->select();
			$rolenum = $this->role_model->alias('role')->order("id desc")->field("id")->find();
			$rolenum=$rolenum["id"];
			//重构角色用户数组
			foreach($array2 as $k=>$v){
				$array2[$k]['name']=$v['name1'];//($v['realname']?$v['realname']:$v['user_nicename']?$v['user_nicename']:$v['user_login'])."(".$v['mobile'].")";
				$rolenum =$rolenum + 1;
				$array2[$k]["id"]=$rolenum;
			}
			$result=array_merge($array1, $array2);
		}
		$uid = $this->role_model->alias('role')->join(C('DB_PREFIX').'b_role_user as role_user on role.id=role_user.role_id')->where('role_user.user_id ='.$adminid)->select();

		$this->assign("id", $uid[0]['id']);
		$this->assign("adminid", $adminid);
		$this->assign("roles", $result);


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
			//判断是否超管，是否是当前角色//操作内容展示

			if($r['id']==1 || $r['id']==$uid[0]['id']){
				$str_manage.='<font color="#cccccc" class="edit fa fa-gavel"></font>';
				$str_manage .= '<font color="#cccccc" class="edit fa fa-edit"></font>';
				$str_manage .= '<font color="#cccccc" class="delete fa fa-trash"></font>';
				$str_manage2='<font color="#cccccc" class="delete fa fa-trash"></font>';;
			}else{
				//不是超管时，判断角色和角色下用户是否能删除，能操作
				if($r['id']==$uid[0]['id']||$r['parentid']==$uid[0]['id']){
					$str_manage.='<font color="#cccccc" class="edit fa fa-gavel"></font>|';
					$str_manage .= '<font color="#cccccc" class="edit fa fa-edit"></font>';
					$str_manage .= '<font color="#cccccc" class="delete fa fa-trash"></font>';
					$str_manage2='<font color="#cccccc " class="delete fa fa-trash"></font>';;
				}else{
					$str_manage .='<a href="#myModal3" class="myModal3 leave" title="绑定员工" data-toggle="modal" data-value="'.$r['id'].'" role="button"><span><i class="fa fa-plus normal"></i></span></a>';
					$str_manage .= '<a class="edit fa fa-gavel" title="角色授权" href="' . U('Rbac/set_shop_role', array('id' => $r['id'])) . '"></a>';
					$str_manage .= '<a class="edit fa fa-edit" title="编辑角色" href="' . U('Rbac/roleedit', array('id' => $r['id'])) . '"></a>';
					$str_manage .= '<a class="js-ajax-delete delete fa fa-trash" title="删除角色" href="' . U('Rbac/roledelete', array('id' => $r['id'])) . '"></a>';
					/*$str_manage .= '<a class="copy fa fa-files-o" title="复制角色权限" href="' . U('Rbac/role_copy', array('id' => $r['id'])) . '"></a>';*/
					$str_manage2='<a class="js-ajax-delete delete fa fa-trash" href="'.U('Rbac/role_userdelete',array('user_id'=>$r["user_id"],'role_id'=>$r["parentid"])).'"></a>';

				}
			}
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
					<td class='text-center'>\$id</td>
        			<td>\$spacer\$name</td>
					<td>\$remark</td>
				    <td class='text-center'>\$status</td>
					<td class='text-center'>\$str_manage</td>
				</tr>";

		$categorys = $tree->get_tree(0, $str);
		$this->assign("categorys", $categorys);
		$this->display();
		/*$getdata=I("");
		if(!$this->is_admin()){
			$condition=array(" gb_b_role.company_id"=>$this->MUser['company_id']);
		}
		if($getdata["search_name"]){
			$condition["s.shop_name"]=array("like","%".$getdata["search_name"]."%");
		}
		$condition["shop_id"]=array("neq",0);
		$join="left join gb_b_shop as s on s.id = gb_b_role.shop_id";
		$field="gb_b_role.*,s.shop_name";
		$count=$this->role_model->countList($condition,$field,$join,array("gb_b_role.listorder" => "ASC", "gb_b_role.id" => "DESC"));
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data = $this->role_model->where($condition)->field($field)->join($join)->limit($limit)->order(array("gb_b_role.listorder" => "ASC", "gb_b_role.id" => "DESC"))->select();
		$this->assign("pagenum",$this->pagenum);
		$this->assign("page", $page->show('Admin'));
		$this->assign("roles", $data);
		$this->display();*/
	}

    // 添加商户角色
    public function roleadd() {
		$condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"]);
		$shop=$this->bshop_model->alias("bshop")->getList($condition,$field='bshop.*',$limit=null,$join="");
		$this->assign("shop", $shop);
        $this->display();
    }
	// 添加门店角色
	public function shop_roleadd() {
		$condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"]);
		$shop=$this->bshop_model->alias("bshop")->getList($condition,$field='bshop.*',$limit=null,$join="");
		$this->assign("shop", $shop);
		$this->display();
	}
    
    // 添加角色提交
    public function roleadd_post() {
    	if (IS_POST) {
			$_POST["company_id"]=get_company_id();
    		if ($this->role_model->create()!==false) {
    			if ($this->role_model->add()!==false) {
					if($_POST["type"]>0){
						$this->success("添加角色成功",U("rbac/shop_index"));
					}else{
						$this->success("添加角色成功",U("rbac/index"));
					}
    			} else {
    				$this->error("添加失败！");
    			}
    		} else {
    			$this->error($this->role_model->getError());
    		}
    	}
    }

    // 删除角色
    public function roledelete() {
        $id = I("get.id",0,'intval');
        if ($id == 1) {
            $this->error("超级管理员角色不能被删除！");
        }
		$info=D("BRole")->getInfo(array("id"=>$id,"company_id"=>get_company_id()));
		if(empty($info)&&get_user_id()!=1){
			$this->error("不存在当前角色！");
		}
        $role_user_model=M("BRoleUser");
        $count=$role_user_model->where(array('role_id'=>$id))->count();
        if($count>0){
        	$this->error("该角色已经有用户！");
        }else{
        	$status = $this->role_model->delete($id);
        	if ($status!==false) {
				if($info['type']==1){
					$this->success("删除成功！", U('Rbac/shop_index'));
				}else{
					$this->success("删除成功！", U('Rbac/index'));
				}
        	} else {
        		$this->error("删除失败！");
        	}
        }
        
    }

    // 编辑角色
    public function roleedit() {
        $id = I("get.id",0,'intval');
        if ($id == 1) {
            $this->error("超级管理员角色不能被修改！");
        }
        $data = $this->role_model->where(array("id" => $id,"company_id"=>get_company_id()))->find();
        if (!$data) {
        	$this->error("该角色不存在！");
        }
        $this->assign("data", $data);
        $this->display();
    }
    
    // 编辑角色提交
    public function roleedit_post() {
    	$id = I("request.id",0,'intval');
    	if ($id == 1) {
    		$this->error("超级管理员角色不能被修改！");
    	}
    	if (IS_POST) {
    		if ($this->role_model->create()!==false) {
    			if ($this->role_model->save()!==false) {
					$role=$this->role_model->getInfo(array("id"=>$id));
					if($role['type']==1){
						$this->success("修改成功！", U('Rbac/shop_index'));
					}else{
						$this->success("修改成功！", U('Rbac/index'));
					}

    			} else {
    				$this->error("修改失败！");
    			}
    		} else {
    			$this->error($this->role_model->getError());
    		}
    	}
    }

    // 角色授权
    public function authorize() {
        $this->auth_access_model = D("BAuthAccess");
       //角色ID
        $roleid = I("get.id",0,'intval');
        if (empty($roleid)) {
        	$this->error("参数错误！");
        }
		$role=M("BRole")->where(array("id"=>$roleid))->find();
        import("Tree");
        $menu = new \Tree();
        $menu->icon = array('│ ', '├─ ', '└─ ');
        $menu->nbsp = '&nbsp;&nbsp;&nbsp;';
		if($role["shop_id"]>0||$role["type"]>0){
			$result = $this->initShopMenu();
		}else{
			$result = $this->initMenu();
		}
		if(get_user_id()!=1){
			//判断角色显示权限目录树
			$Role_user= M("b_role_user");
			$roleuser=$Role_user->where(array('user_id'=>get_user_id()))->getField("role_id",true);
			$a_c_map["role_id"]=array("in",$roleuser);
			$ad_priv_data=$this->auth_access_model->where($a_c_map)->getField("rule_name",true);//获取权限表数据
			$role_menu=array();
			$menus=$result;
			foreach ($menus as $n => $t) {
				if($this->_is_checked($t, $ad_roleid, $ad_priv_data)){
					$role_menu[$n]=$t;
				}

			}
			$result =$role_menu;
		}
        $newmenus=array();
        $priv_data=$this->auth_access_model->where(array("role_id"=>$roleid))->getField("rule_name",true);//获取权限表数据
        foreach ($result as $m){
        	$newmenus[$m['id']]=$m;
        }
        
        foreach ($result as $n => $t) {
        	$result[$n]['checked'] = ($this->_is_checked($t, $roleid, $priv_data)) ? ' checked' : '';
        	$result[$n]['level'] = $this->_get_level($t['id'], $newmenus);
        	$result[$n]['style'] = empty($t['parentid']) ? '' : 'display:none;';
        	$result[$n]['parentid_node'] = ($t['parentid']) ? ' class="child-of-node-' . $t['parentid'] . '"' : '';
        }
        $str = "<tr id='node-\$id' \$parentid_node  style='\$style'>
                   <td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
    			</tr>";
        $menu->init($result);
        $categorys = $menu->get_tree(0, $str);
        
        $this->assign("categorys", $categorys);
        $this->assign("roleid", $roleid);
        $this->display();
    }
    
    // 角色授权提交
    public function authorize_post() {
    	$this->auth_access_model = M("BAuthAccess");
    	if (IS_POST) {
    		$roleid = I("post.roleid",0,'intval');

    		if(!$roleid){
    			$this->error("需要授权的角色不存在！");
    		}

    		if (is_array($_POST['menuid']) && count($_POST['menuid'])>0) {
    			
    			$menu_model=M("BMenu");
    			$auth_rule_model=M("BAuthRule");
    			$this->auth_access_model->where(array("role_id"=>$roleid,'type'=>'business_url'))->delete();
    			foreach ($_POST['menuid'] as $menuid) {
    				$menu=$menu_model->where(array("id"=>$menuid))->field("app,model,action")->find();
    				if($menu){
    					$app=$menu['app'];
    					$model=$menu['model'];
    					$action=$menu['action'];
    					$name=strtolower("$app/$model/$action");
    					$this->auth_access_model->add(array("role_id"=>$roleid,"rule_name"=>$name,'type'=>'business_url'));
    				}
    			}
    
    			$this->success("授权成功！", U("Rbac/index"));
    		}else{
    			//当没有数据时，清除当前角色授权
    			$this->auth_access_model->where(array("role_id" => $roleid))->delete();
    			$this->error("没有接收到数据，执行清除授权成功！");
    		}
    	}
    }
    
    /**
     *  检查指定菜单是否有权限
     * @param array $menu menu表中数组
     * @param int $roleid 需要检查的角色ID
     */
    private function _is_checked($menu, $roleid, $priv_data) {
    	
    	$app=$menu['app'];
    	$model=$menu['model'];
    	$action=$menu['action'];
    	$name=strtolower("$app/$model/$action");
    	if($priv_data){
	    	if (in_array($name, $priv_data)) {
	    		return true;
	    	} else {
	    		return false;
	    	}
    	}else{
    		return false;
    	}
    	
    }

    /**
     * 获取菜单深度
     * @param $id
     * @param $array
     * @param $i
     */
    protected function _get_level($id, $array = array(), $i = 0) {
        
        	if ($array[$id]['parentid']==0 || empty($array[$array[$id]['parentid']]) || $array[$id]['parentid']==$id){
        		return  $i;
        	}else{
        		$i++;
        		return $this->_get_level($array[$id]['parentid'],$array,$i);
        	}
        		
    }
   //新增授权页面
	public function set_role() {
		// $Ba = D("Business/BAuthAccess");
		$Ba = D("BAuthAccess");
		if(IS_POST){
			$id=trim(I("id"));
			$info=D("BRole")->getInfo(array("id"=>$id,"company_id"=>get_company_id()));
			if(empty($info)&&get_user_id()!=1){
				$this->error("不存在当前角色！");
			}
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
						$Ba->add(array("role_id"=>$id,"rule_name"=>$name,'type'=>'system_url'));
					}
				}
				//获取旧的菜单权限存在，但新的菜单权限不存在的菜单权限，并进行删除
				$del_authaccess=array_diff($old_rule_name,$new_authaccess);
				$del_authaccess=implode(',',$del_authaccess);
				$Ba->where(array("role_id"=>$id,'rule_name'=>array('in',$del_authaccess)))->delete();
				$this->success("权限设置成功！", U('Rbac/index'));
			} else{
				$this->error("权限设置失败！");
			}
		}else{
			$role_id = I("get.id",0,'intval');
			$info=D("BRole")->getInfo(array("id"=>$role_id));
			if(empty($info)){
				$this->error("不存在当前角色！");
			}
			if(get_user_id()!=1){
				//判断角色显示权限目录树
				$Role_user= M("b_role_user");
				$roleuser=$Role_user->where(array('user_id'=>get_user_id()))->getField("role_id",true);
				$a_c_map["role_id"]=array("in",$roleuser);
				$ad_priv_data=$Ba->where($a_c_map)->getField("rule_name",true);//获取权限表数据
			}
			$menus=D("Business/BMenu")->get_new_menu_tree(0,0,$ad_priv_data);
			$this->assign("menus",$menus);
			$list=$Ba->getList(array("role_id"=>$role_id));
			$m_list=D("Business/BMenu")->getList();
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
			$info['role_id']=$role_id;
			$this->assign("h_menus",$h_menus);
			$this->assign("data", $info);
			$this->display();
		}
	}
	//新增门店授权页面
	public function set_shop_role() {
		// $Ba = D("Business/BAuthAccess");
		$Ba = D("BAuthAccess");
		if(IS_POST){
			$id=trim(I("id"));
			$info=D("BRole")->getInfo(array("id"=>$id,"company_id"=>get_company_id()));
			if(empty($info)&&get_user_id()!=1){
				$this->error("不存在当前角色！");
			}
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
						$Ba->add(array("role_id"=>$id,"rule_name"=>$name,'type'=>'shop_url'));
					}
				}
				//获取旧的菜单权限存在，但新的菜单权限不存在的菜单权限，并进行删除
				$del_authaccess=array_diff($old_rule_name,$new_authaccess);
				$del_authaccess=implode(',',$del_authaccess);
				$Ba->where(array("role_id"=>$id,'rule_name'=>array('in',$del_authaccess)))->delete();
				$this->success("权限设置成功！", U('Rbac/shop_index'));
			} else{
				$this->error("权限设置失败！");
			}
		}else{
			$role_id = I("get.id",0,'intval');
			$info=D("BRole")->getInfo(array("id"=>$role_id,"company_id"=>get_company_id()));
			if(empty($info)&&get_user_id()!=1){
				$this->error("不存在当前角色！");
			}
			if(get_user_id()!=1){
				//判断角色显示权限目录树
				$Role_user= M("b_role_user");
				$roleuser=$Role_user->where(array('user_id'=>get_user_id()))->getField("role_id",true);
				$a_c_map["role_id"]=array("in",$roleuser);
				$ad_priv_data=$Ba->where($a_c_map)->getField("rule_name",true);//获取权限表数据
			}
			$menus=D("Business/BMenu")->get_new_menu_tree(0,1,$ad_priv_data);
			$this->assign("menus",$menus);
			$list=$Ba->getList(array("role_id"=>$role_id));
			$m_list=D("Business/BMenu")->getList();
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
			$info['role_id']=$role_id;
			$this->assign("h_menus",$h_menus);
			$this->assign("data", $info);
			$this->display();
		}
	}
    //角色成员管理
    public function member(){
    	//TODO 添加角色成员管理
    	
    }
	//用户列表
	public function user_list(){
		$name=I('post.mobile')?I('post.mobile'):I('get.mobile');
		//if(!empty($name)){
			$getdata=I("");
			$condition=array("bemployee.deleted"=>0,'bemployee.company_id'=>$this->MUser["company_id"]);
			if ($name) {
				$condition["bemployee.employee_name|bemployee.employee_mobile"] = array("like", "%" . $name . "%");
			}
			$condition['bemployee.user_id']=array("neq",1);
			$condition['musers.user_type']=array("neq",4);
			$field='bemployee.*,musers.user_status,musers.mobile,bjobs.job_name,musers.user_nicename';
			$join=" join ".DB_PRE."m_users musers on bemployee.user_id=musers.id";
			$join.=" left join ".DB_PRE."b_jobs bjobs on bemployee.job_id=bjobs.id";
			$count =D('BEmployee')->alias("bemployee")->countList($condition,$field,$join,$order='bemployee.create_time desc',$group='');
			$page = $this->page($count, $this->pagenum);
			$limit=$page->firstRow.",".$page->listRows;
			$data = D('BEmployee')->alias("bemployee")->getList($condition,$field,$limit,$join,$order='bemployee.create_time desc',$group='');
			foreach($data as $k=>$v){
				$field="bshop.shop_name";
				$condition=array("bshopemployee.user_id"=>$v["user_id"],"bshopemployee.employee_id"=>$v["id"]);
				$join="left join ".DB_PRE."b_shop bshop on bshopemployee.shop_id=bshop.id";
				$shop_name=D("BShopEmployee")->alias("bshopemployee")->getList($condition,$field,$limit="",$join,$order='',$group='');
				$data[$k]["shop_name"]=$shop_name;
			}
			$this->assign("page", $page->show('Admin'));
			$this->assign("mobile",$name);
			$this->assign("user_list",$data);
		//}
		$this->display();
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
			$company_id = $this->role_model->where(array('id' => array('in', $role_ids)))->getField("company_id", true);
			if (in_array(0, $company_id)) {
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
		$roles=$this->role_model->where(array('type'=>0,'status' => 1,'company_id'=>$this->MUser['company_id']))->order("id DESC")->select();
		$this->assign("roles",$roles);
		//获取门店角色
		$shop_roles=$this->role_model->where(array('type'=>1,'status' => 1,'company_id'=>$this->MUser['company_id']))->order("id DESC")->select();
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
	// 员工添加
	public function add_employee(){
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
				M()->startTrans();
				$condition=array("mobile"=>$postdata["mobile"]);
				if(empty($postdata["mobile"])){
					$this->error("手机号不能为空！");
				}

				$userinfo=$this->users_model->getInfo($condition,$field="id",$join="");
				$bemployee_data=array();
				$bemployee_data['employee_name']=$postdata["user_nicename"];
				$bemployee_data['employee_mobile']=$postdata["mobile"];
				$bemployee_data['company_id']=$this->MUser['company_id'];
				$bemployee_data['shop_id']=$this->MUser['shop_id'];
				$bemployee_data['sector_id']=$postdata["sector_id"];
				$bemployee_data['job_id']=$postdata["job_id"];
				$bemployee_data['status']=1;
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
								/*$role_user_model=D("BRoleUser");
								foreach ($role_ids as $role_id){
									if(get_user_id() != 1 && $role_id == 1){
										$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
									}
									$role_user_model->insert(array("role_id"=>$role_id,"user_id"=>$result,"company_id"=>get_company_id()));
								}
								//指派门店
								$shop_employee_model=D("BShopEmployee");
								foreach ($shop_ids as $shop_id){
									$shop_employee_model->insert(array("shop_id"=>$shop_id,"employee_id"=>get_company_id(),"user_id"=>$result));
								}*/
								$this->success("添加成功！初始密码为<span style='color: red'>".$this->default_password."</span>", U("Rbac/user_list",array('mobile'=>$postdata["mobile"])),true,5);
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
							//$this->add_role_shop($role_ids,$shop_ids,$bemployee,$userinfo['id'],$type=1);
							$this->success("添加成功！密码为该手机号在金行家云掌柜系统密码", U("Rbac/user_list",array('mobile'=>$postdata["mobile"])),true,5);
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
	function add_user_role(){
		$user_id=I("user_id");
		$role_id=I("role_id");
		if(!empty($role_id)&& !empty($user_id)){
			$users=explode(",",$user_id);
			M()->startTrans();
			$data['status']=1;
			$data['msg']="添加成功！";
			$time=time();
			foreach ($users as $v){
				$where['user_id']=$v;
				$where['role_id']=$role_id;
				$where['company_id']=get_company_id();
				$info=$this->role_user_model->getInfo($where);
				if(!$info){
					$da['user_id']=$v;
					$da['role_id']=$role_id;
					$da['create_time']=$time;
					$da['company_id']=get_company_id();
					$res=$this->role_user_model->insert($da);
					if(!$res){
						M()->rollback();
						$data['status']=0;
						$data['msg']="添加失败！";
						break;
					}
				}
			}
		}else{
			$data['status']=0;
			$data['msg']="信息错误！";
		}
		if($data['status']==1){
			M()->commit();
		}
		$this->ajaxReturn($data);
	}
	/**
	 * 删除角色
	 */
	public function role_userdelete() {
		$getdata = I("");
		if(!empty($getdata["user_id"])&&!empty($getdata["role_id"])){
			$map["user_id"]=$getdata["user_id"];
			$map["role_id"]=$getdata["role_id"];
			if($map["role_id"]== 1){
				$this->error("删除失败！");
			}
			$map["company_id"]=get_company_id();
			$count=$this->role_user_model->countList($map);
			if($count>0){
				$status = $this->role_user_model->where($map)->delete();
				if ($status!==false) {
					$role=$this->role_model->getInfo(array("id"=>$getdata["role_id"]));
					if($role['type']==1){
						$this->success("删除成功！", U('Rbac/shop_index'));
					}else{
						$this->success("删除成功！", U('Rbac/index'));
					}
				} else {
					$this->error("删除失败！");
				}
				//}
			}
		}

	}
}

