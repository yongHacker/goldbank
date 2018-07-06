<?php
/**
 * 岗位管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BJobsController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bjobs_model=D("BJobs");
	}
    /**
     * 岗位列表
     */
    public function index() {
		$condition=array('bjobs.deleted'=>0,'bjobs.sector_id'=>I('get.id',0),'bjobs.company_id'=>$this->MUser["company_id"]);
		$array1 = $this->bjobs_model->alias("bjobs")->getList($condition,$field='id,job_name name,job_pid=0 as parentid,job_duty',$limit=null,$join='',$order='create_time desc',$group='');
		$join.="right join ".DB_PRE."b_employee bemployee on bemployee.job_id=bjobs.id";
		$field="employee_name as name,job_id as parentid,user_id";
		$array2=$this->bjobs_model->alias("bjobs")->getList($condition,$field,$limit=null,$join,$order='bemployee.create_time desc',$group='');
		$rolenum = $this->bjobs_model->alias("bjobs")->getInfo($condition,$field='id',$join="",$order="id desc");
		$rolenum = $rolenum["id"];
		foreach ($array2 as $k => $v) {
			$rolenum = $rolenum + 1;
			$array2[$k]["id"] = $rolenum;
		}
		$result = array_merge($array1, $array2);
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
			//判断是否超管，是否是当前岗位//操作内容展示
			$str_manage .= '<a class="edit fa fa-edit" title="编辑岗位" href="' . U('BJobs/edit', array('id' => $r['id'])) . '"></a>';
			$str_manage .= '<a class="js-ajax-delete delete fa fa-trash" title="删除岗位" href="' . U('BJobs/deleted', array('id' => $r['id'])) . '"></a>';
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
					<td>\$job_duty</td>

					<td class='text-center'>\$str_manage</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		$this->assign("categorys", $categorys);
		$this->assign("sector_id",I("id"));
		$this->display();
    }

	//岗位编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array('bjobs.deleted'=>0,'bjobs.id'=>I('get.id',0),'company_id'=>$this->MUser["company_id"]);
			$data = $this->bjobs_model->alias("bjobs")->getInfo($condition,$field='*',$join="",$order="id desc");
			$this->assign("list",$data);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bjobs_model->create()!==false) {
					$data=array();
					/*$data["company_id"]=1;//$postdata["company_id"];
					$data["job_pid"]=1;//$postdata["shop_id"];
					$data["sector_id"]=$postdata["sector_id"];*/
					$data["job_name"]=$postdata["job_name"];
					$data["job_duty"]=$postdata["job_duty"];
					$data["update_time"]=time();
					$condition=array("id"=>$postdata["id"]);
					$BSectors=$this->bjobs_model->update($condition,$data);
					if ($BSectors!==false) {
						$this->success("保存成功！", U("BJobs/index",array("id"=>$postdata["sector_id"])));
					} else {
						$this->error("保存失败！");
					}
				} else {
					$this->error($this->bjobs_model->getError());
				}
			}
		}
	}
	//岗位添加
	public function add() {
		$postdata=I("post.");
		if(empty($postdata)){
			$this->assign("sector_id",I("id"));
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bjobs_model->create()!==false) {
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
					$data["job_pid"]=1;//$postdata["shop_id"];
					$data["job_name"]=$postdata["job_name"];
					$data["sector_id"]=$postdata["sector_id"];
					$data["job_duty"]=$postdata["job_duty"];
					$data["create_time"]=time();
					$data["deleted"]=0;
					$data["update_time"]=time();
					$BSectors=$this->bjobs_model->insert($data);
					if ($BSectors!==false) {
						$this->success("添加成功！", U("BJobs/index",array("id"=>$postdata["sector_id"])));
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bjobs_model->getError());
				}
			}else{
				$this->error("添加失败！");
			}
		}
	}

	//岗位删除
	public function deleted() {
		$postdata=I("post.");
		if(empty($postdata)){
			$data["deleted"]=1;
			$data["update_time"]=time();
			$condition=array("id"=>I("id",0));
			$BSectors=$this->bjobs_model->update($condition,$data);
			if ($BSectors!==false) {
				$this->success("添加成功！", U("BJobs/index",array("id"=>$postdata["sector_id"])));
			} else {
				$this->error("添加失败！");
			}
		}else{
			$this->error("添加失败！");
		}
	}
}

