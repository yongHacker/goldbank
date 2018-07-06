<?php
/**
 * 员工管理
 */
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BShopEmployeeController extends ShopbaseController {

	public function _initialize() {
		$this->bemployee_model=D("BEmployee");
		$this->bshopemployee_model=D("BShopEmployee");
		$this->bjobs_model=D("BJobs");
		$this->bsectors_model=D("BSectors");
		$this->b_show_status('BEmployee');
		parent::_initialize();
	}

    /**
     * 员工列表
     */
    public function index() {
		$getdata=I("");
		$condition=array("bshopemployee.shop_id"=>get_shop_id(),"bemployee.deleted"=>0,'bemployee.company_id'=>$this->MUser["company_id"]);
		if ($getdata["employee_name"]) {
			$condition["bemployee.employee_name"] = array("like", "%" . $getdata["employee_name"] . "%");
		}
		if ($getdata["mobile"]) {
			$condition["bemployee.employee_mobile|bemployee.employee_name"] = array("like", "%" . $getdata["mobile"] . "%");
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
		$field='bemployee.*,musers.user_status,musers.mobile,bjobs.job_name,bsector.sector_name';
		$join="  join ".DB_PRE."b_employee bemployee on bemployee.id=bshopemployee.employee_id";
		$join.="  join ".DB_PRE."m_users musers on bemployee.user_id=musers.id";
		$join.=" left join ".DB_PRE."b_jobs bjobs on bemployee.job_id=bjobs.id";
		$join.=" left join ".DB_PRE."b_sectors bsector on bemployee.sector_id=bsector.id";
		$count = $this->bshopemployee_model->alias("bshopemployee")->countList($condition,$field,$join,$order='bemployee.create_time desc',$group='');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data = $this->bshopemployee_model->alias("bshopemployee")->getList($condition,$field,$limit,$join,$order='bemployee.create_time desc',$group='');
		//部门数据
		$select_categorys=$this->bsectors_model->get_bsectors_tree($getdata["sector_id"]);
		$this->assign("select_categorys", $select_categorys);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
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

