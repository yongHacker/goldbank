<?php
/**
 * 部门管理（组织架构）
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BSectorsController extends BusinessbaseController {
	
	public function _initialize() {
		$this->bsectors_model = D("BSectors");
		parent::_initialize();
	}
    /**
     * 组织架构展示
     */
    public function index() {
       	$this->display();
    }

	// 组织架构数据获取
	public function bsectorsData() {
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
		$categories = $this->bsectors_model->getList($condition,$field='*,sector_pid as pid,sector_name as name',$limit=null,$join='',$order='id asc',$group='');
		$tree=$this->bsectors_model->get_tree_arr($categories);
		$data=$this->bsectors_model->get_attr($tree,0);
		echo json_encode($data);
	}
	
	// 部门列表
	public function lists() {
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
		$array1 = $this->bsectors_model->getList($condition,$field='*,sector_name as name',$limit=null,$join='',$order='create_time desc',$group='');
		foreach ($array1 as $k => $v) {
			$array1[$k]["parentid"] = 0;
		}
		$join='';
		$join.="left join ".DB_PRE."b_jobs bjobs on bsectors.id=bjobs.sector_id";
		$array2 =$this->bsectors_model->alias("bsectors")->getList($condition="",$field='*,sector_name as name,sector_id ',$limit=null,$join='',$order='id asc',$group='');
			M('gw')->join("left join jb_yggl on jb_yggl.gw_id=jb_gw.id and jb_yggl.deleted = 0")
			->join("join jb_users u on jb_yggl.u_id=u.id")
			->where('jb_gw.deleted=0 and jb_gw.bm_id=' . I('get.id'))
			->field("CONCAT(u.user_nicename,'(',u.mobile,')') as name,gw_id as parentid,u_id")
			->select();
		$rolenum = M('gw')->where('deleted=0 and bm_id=' . I('get.id'))->order("id desc")->field("id")->find();
		$rolenum = $rolenum["id"];
		foreach ($array2 as $k => $v) {
			$rolenum = $rolenum + 1;
			$array2[$k]["id"] = $rolenum;
		}
		$data = array_merge($array1, $array2);
		$result = $data;
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$newmenus = array();
		foreach ($result as $m) {
			$newmenus[$m['id']] = $m;
		}
		foreach ($result as $n => $r) {
			//$result[$n]['level'] = $this->_get_level($r['id'], $newmenus);
			$result[$n]['parentid_node'] = ($r['parentid']) ? ' class="child-of-node-' . $r['parentid'] . '"' : '';
			$str_manage = "";
			//判断是否超管，是否是当前部门//操作内容展示
			/*$str_manage .= '<font color="#cccccc" class="edit fa fa-gavel"></font>';
            $str_manage .= '<font color="#cccccc" class="edit fa fa-edit"></font>';
            $str_manage .= '<font color="#cccccc" class="delete fa fa-trash"></font>';*/
			$str_manage .= '<a class="edit fa fa-edit" title="编辑部门" href="' . U('Company/edit_gw', array('id' => $r['id'])) . '"></a>';
			$str_manage .= '<a class="js-ajax-delete delete fa fa-trash" title="删除部门" href="' . U('Company/delete_gw', array('gw_id' => $r['id'])) . '"></a>';
			$str_manage2 = '<font color="#cccccc" class="delete fa fa-trash"></font>';;
			if ($r["parentid"] == 0) {
				$result[$n]['str_manage'] = $str_manage;
				$result[$n]['status'] = $r['status'] ? '<font color="red">√</font>' : '<font color="red">╳</font>';
			} else {
				//$result[$n]['str_manage'] = $str_manage2;
			}
		}
		$tree->init($result);
		$str = "<tr id='node-\$id' \$parentid_node>
					<td style='padding-left:20px;'></td>
					<td>\$id</td>
        			<td>\$spacer\$name</td>
					<td>\$gwzz</td>

					<td class='text-center'>\$str_manage</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		$this->assign("categorys", $categorys);
		$this->assign("id",I("id"));
		$this->display();
	}

	// 部门添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$select_categorys=$this->bsectors_model->get_bsectors_tree();
			$this->assign("select_categorys", $select_categorys);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bsectors_model->create()!==false) {
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
					$data["shop_id"]=0;//$postdata["shop_id"];
					$data["sector_pid"]=$postdata["sector_pid"];
					$data["sector_name"]=$postdata["sector_name"];
					$data["sectors_des"]=$postdata["sectors_des"];
					$data["create_time"]=time();
					$data["deleted"]=0;
					$data["update_time"]=time();
					$BSectors=$this->bsectors_model->insert($data);
					if ($BSectors!==false) {
						$this->success("添加成功！", U("BSectors/index"));
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bsectors_model->getError());
				}
			}
		}
	}

	public function edit(){

		$id = I('id/d', 0);

		$where = array(
			'id'=> $id,
			'deleted'=> 0,
		);
		$sector_info = $this->bsectors_model->getInfo($where);

		if( !empty($sector_info) && IS_POST){

			$update_data = array(
				'sector_pid'=> I('sector_pid/d', 0),
				'sector_name'=> I('sector_name/s'),
				'sectors_des'=> I('sectors_des/s'),
				'update_time'=> time(),
			);

			$rs = $this->bsectors_model->update($where, $update_data);
			if($rs !== false){
				$info["status"] = "success";
				$info["info"] = '编辑成功';
				$info["url"] = U('BSectors/edit', array('id'=> $id));
			}else{
				$info["status"] = "fail";
				$info["info"] = '编辑失败';
			}

			$this->ajaxReturn($info);
		}else{
			$this->assign('sector_info', $sector_info);

			$select_categorys = $this->bsectors_model->get_bsectors_tree($sector_info['sector_pid']);
			$this->assign("select_categorys", $select_categorys);

			$this->display();
		}
	}
	public function deleted(){    //删除部门（并非真的删除，只是修改删除标识）
		$id = I('id/d', 0);

		if($id){
			$condition = array('sector_id'=> $id, 'deleted'=> 0);
			$re = D('BEmployee')->getInfo($condition);
			if(!empty($re)){
				$del['status']=0;
				$del['info']='该部门下还有员工，不能删除该部门';
				$del['msg']='该部门下还有员工，不能删除该部门';
			}else{
				$where = array('id'=> $id, 'deleted'=> 0);
				$delete['deleted']=1;
				$delete['update_time']=time();
				$d = D("BSectors")->update($where,$delete);
				if($d!==false){
					$del['status']=1;
					$del['info']='删除成功';
					$del['msg']='删除成功';
				}
				else{
					$del['status']=0;
					$del['info']='删除失败';
					$del['msg']='删除失败';
				}
			}
			$this->ajaxReturn($del);
		}
	}

}

