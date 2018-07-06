<?php
/**
 * 金子价格设定记录
 */
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BBankGoldLogController extends ShopbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bbankgoldlog_model=D("BBankGoldLog");
	}
	/**
	 * 记录展示
	 */
	public function index() {
		$getdata=I("");
		$condition=array("bbankgoldlog.company_id"=>$this->MUser['company_id'],"bbankgoldlog.shop_id"=>get_shop_id());
		if($getdata["search_name"]){
			$condition["bbankgoldtype.name"]=array("like","%".$getdata["search_name"]."%");
		}
		if(I('begin_time')){
			$begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
			$condition['bbankgoldlog.create_time'] = array('gt', $begin_time);
		}

		if(I('end_time')){
			$end_time = I('end_time') ? strtotime(I('end_time')) : time();
			if(isset($begin_time)){
				$p1 = $condition['bbankgoldlog.create_time'];
				unset($condition['bbankgoldlog.create_time']);
				$condition['bbankgoldlog.create_time'] = array($p1, array('lt', $end_time));
			}else{
				$condition['bbankgoldlog.create_time'] = array('lt', $end_time);
			}
		}
		$join="left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgoldlog.bgt_id";
		$join.=" left join ".DB_PRE."b_shop bshop on bshop.id=bbankgoldlog.shop_id";
		$count=$this->bbankgoldlog_model->alias("bbankgoldlog")->countList($condition,$field='bbankgoldlog.*,bbankgoldtype.name,bshop.shop_name',$join,$order='bbankgoldlog.create_time desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bbankgoldlog_model->alias("bbankgoldlog")->getList($condition,$field='bbankgoldlog.*,bbankgoldtype.name,bshop.shop_name',$limit,$join,$order='bbankgoldlog.create_time desc');
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}


}

