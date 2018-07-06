<?php
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class RbacController extends ShopbaseController {

    protected $role_model, $auth_access_model;

    public function _initialize() {
        parent::_initialize();
		$this->bshop_model=D("BShop");
        $this->role_model = D("BRole");
    }

    // 角色管理列表
    public function index() {
		if(!$this->is_admin()){
			$condition=array("company_id"=>$this->MUser['company_id']);
		}
		$condition["shop_id"]=0;
        $data = $this->role_model->where($condition)->order(array("listorder" => "ASC", "id" => "DESC"))->select();
        $this->assign("roles", $data);
        $this->display();
    }

	// 门店角色管理列表
	public function shop_index() {
		$getdata=I("");
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
		$this->display();
	}

    // 添加角色
    public function roleadd() {
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
    				$this->success("添加角色成功",U("rbac/index"));
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
        		$this->success("删除成功！", U('Rbac/index'));
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
    				$this->success("修改成功！", U('Rbac/index'));
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
		if($role["shop_id"]>0){
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
				$this->success("权限设置成功！", U('Rbac/index'));
			} else{
				M("")->rollback();
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
			$menus=D("BMenu")->get_new_menu_tree(0,0,$ad_priv_data);
			$this->assign("menus",$menus);
			$list=$Ba->getList(array("role_id"=>$role_id));
			$m_list=D("BMenu")->getList();
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
				$this->success("权限设置成功！", U('Rbac/index'));
			} else{
				M("")->rollback();
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
			$menus=D("BMenu")->get_new_menu_tree(0,1,$ad_priv_data);
			$this->assign("menus",$menus);
			$list=$Ba->getList(array("role_id"=>$role_id));
			$m_list=D("BMenu")->getList();
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

}

