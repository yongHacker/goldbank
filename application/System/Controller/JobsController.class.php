<?php
namespace System\Controller;
use Common\Controller\SystembaseController;
class JobsController extends SystembaseController {
	public function index(){    //获取所有部门信息
		$where=array();
		$where['deleted']=0;
		$where['sector_id']=I('get.id');
		$field="id,job_name name,job_duty";
		$order="create_time desc";
		$array1 = D("System/AJobs")->getlist($where,$field,"","",$order);
		foreach ($array1 as $k => $v) {
			$array1[$k]["job_pid"] = 0;
		}
		$where=array();
		$where['gb_a_jobs.deleted']=0;
		$where['gb_a_jobs.sector_id']=I('get.id');
		$join="left join gb_a_employee on gb_a_employee.job_id=gb_a_jobs.id and gb_a_employee.deleted = 0";
		$join.=" join gb_m_users u on gb_a_employee.user_id=u.id";
		$field="CONCAT(u.user_nicename,'(',u.mobile,')') as name,gb_a_jobs.id as parentid,user_id";
		$array2 =D("System/AJobs")->getList($where,$field,"",$join);
		$rolenum = M('a_jobs')->where('deleted=0 and sector_id=' . I('get.id'))->order("id desc")->field("id")->find();
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
			$result[$n]['parentid_node'] = ($r['parentid']) ? ' class="child-of-node-' . $r['parentid'] . '"' : '';
			$str_manage = "";
			$str_manage .= '<a class="edit fa fa-edit" title="编辑岗位" href="' . U('Jobs/edit', array('id' => $r['id'])) . '"></a>';
			$str_manage .= '<a class="js-ajax-delete delete fa fa-trash" title="删除岗位" href="' . U('Jobs/delete', array('id' => $r['id'])) . '"></a>';
				$str_manage2 = '<font color="#cccccc" class="delete fa fa-trash"></font>';;
			if ($r["job_pid"] == 0) {
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
		$this->assign("id",I("id"));
		$this->display();
    }
	public function add(){
		$id = I("request.id");
		$param['job_name']=$_POST['job_name'];
		$param['job_duty']=$_POST['job_duty'];
		if($_POST) {
			//插入数据库
			$wh['id'] = $id;
			$info = M("ASectors")->where($wh)->find();
			$whe['job_pid'] = $info['job_pid'];
			$whe['id'] = $info['sector_id'];
			$whe['job_name'] = $_POST['job_name'];
			$whe['deleted'] = 0;
			$gw = M("AJobs")->where($whe)->find();
			if ($gw) {
				$arr['status'] = 0;
				$arr['info'] = "添加失败，岗位名称重复";
				$this->ajaxReturn($arr);
			} else {
				$param['job_pid'] = $info['id'];
				$param['sector_id'] = $info['id'];
				$param['create_time'] = time();
				$param['deleted'] = 0;
				$param['update_time'] = time();
				$g = D("System/AJobs")->insert($param);
				if ($g) {
					$arr['status'] = 1;
					$arr['info'] = "添加成功！";
					$arr['url']="/index.php?g=System&m=Jobs&a=index&id=".$id;
					$this->ajaxReturn($arr);
				} else {
					$arr['status'] = 0;
					$arr['info'] = "添加失败！";
					$this->ajaxReturn($arr);
				}
			}
		}else{
			if($id){
				$this->assign("id",$id);
				$this->display();
			}else{
				$this->error("系统错误！");
			}
		}
	}
    public function edit(){
		C('TOKEN_ON', false);
		$id = I("request.id");
        $param['job_name']=$_POST['job_name'];
        $param['job_duty']=$_POST['job_duty'];
        if($id){
			if ($_POST['job_name']) {
				$data = M('AJobs')->where('id='.$id)->field('id,job_name,job_duty,sector_id')->find();
				$obj = M("AJobs");
				$bool = $obj->where('id=' . $id)->save($param);
				if ($bool !== false) {
					$arr['status'] = 1;
					$arr['url']="/index.php?g=System&m=Jobs&a=index&id=".$data['sector_id'];
					$arr['info'] = '修改成功';
					$this->ajaxReturn($arr);
				} else {
					$arr['status'] = 0;
					$arr['info'] = '修改失败';
					$this->ajaxReturn($arr);
				}
			} else {
				if (I("post.id")) {
					if (!$_POST['job_name']) {
						$arr['status'] = 0;
						$arr['info'] = '岗位名称不能为空';
						$this->ajaxReturn($arr);
					}
				} else {
					$condition = array();
					$condition['id'] = $id;
					$data = M('AJobs')->where($condition)->field('id,job_name,job_duty,sector_id')->find();
					$this->assign('data', $data);
					$this->display("edit");
				}
			}
		} else {
			$arr['status'] = 0;
			$arr['info'] = '修改失败';
			$this->ajaxReturn($arr);
		}
    }

	public function delete(){    //删除岗位（并非真的删除，只是修改删除标识）
		if (I('post.id') || I('get.id')) {
			$condition = array();
			$condition['job_id'] = I('request.id');
			$condition['status'] = 1;
			$condition['deleted'] = 0;
			$re = M('AEmployee')->where($condition)->field('id')->find();
			$condition = array();
			$condition['id'] = I('request.id');
			$gw = M('AJobs')->where($condition)->field('job_name,sector_id')->find();
			if(!empty($re)){
				$del['status'] = 0;
				$del['msg']='该岗位下还有员工，不能删除该岗位';
				$del['info'] = '该岗位下还有员工，不能删除该岗位';
				$del['state'] = 'fail';
			}
			else{
				$delete['deleted']=1;
				$delete['update_time']=time();
				$d = M('AJobs')->where('id=' . I('request.id'))->save($delete);
				if($d!==false){
					$del['status'] = 1;
					$del['msg']='删除成功';
					$del['info'] = '删除成功';
					$del['state'] = 'success';
					$del['url'] = U('Jobs/index',array('id' => $gw['sector_id']));
				}
				else{
					$del['status'] = 0;
					$del['msg']='删除失败';
					$del['info'] = '删除失败';
					$del['state'] = 'fail';
				}
			}
			$this->ajaxReturn($del);
		}
    }

	//部门下面的人员
	public function user(){
		$id=I("id"); //部门id
		if(empty($id)){
			$this->error("信息错误！");
		}
		$join="join gb_a_jobs as aj on aj.id = gb_a_employee.job_id";
		$join.=" join gb_m_users as mu on mu.id = gb_a_employee.user_id";
		$where['gb_a_employee.sector_id']=$id;
		$field="gb_a_employee.*,aj.job_name,mu.user_nicename";
		$order="gb_a_employee.update_time desc";
		$count=D("System/AEmployee")->countList($where,$field,$join,$order);
		$page=$this->page($count,$this->pagenum);
		$show= $page->show('Admin');
		$limit=$page->firstRow.','.$page->listRows;
		$list=D("System/AEmployee")->getList($where,$field,$limit,$join,$order);
		$this->assign('list',$list);
		$this->assign('page',$show);
		$this->assign("id",$id);
		$this->assign("numpage",$this->pagenum);
		$this->display();
	}
}