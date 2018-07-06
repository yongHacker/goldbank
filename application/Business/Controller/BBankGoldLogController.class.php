<?php
/**
 * 金子价格设定记录
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BBankGoldLogController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bbankgoldlog_model=D("BBankGoldLog");
		$this->bbankgoldtype_model=D("BBankGoldType");
	}
	// 处理列表表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL) {
		$getdata = I("");
		$condition=array("bbankgoldlog.company_id"=>$this->MUser['company_id']);
		if($getdata["shop_id"]>-1){
			$condition["bbankgoldlog.shop_id"]=$getdata["shop_id"];
		}
		if($getdata["bgt_id"]){
			$condition["bbankgoldlog.bgt_id"]=$getdata["bgt_id"];
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
		$ex_where = array_merge($condition, $ex_where);
		return $ex_where;
	}
	/**
	 * 记录展示
	 */
	public function index() {
		$condition=array();
		$this->handleSearch($condition);
		$join="left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgoldlog.bgt_id";
		$join.=" left join ".DB_PRE."b_shop bshop on bshop.id=bbankgoldlog.shop_id";
		$count=$this->bbankgoldlog_model->alias("bbankgoldlog")->countList($condition,$field='bbankgoldlog.*,bbankgoldtype.name,bshop.shop_name',$join,$order='bbankgoldlog.create_time desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bbankgoldlog_model->alias("bbankgoldlog")->getList($condition,$field='bbankgoldlog.*,bbankgoldtype.name,bshop.shop_name',$limit,$join,$order='bbankgoldlog.create_time desc');
		$shop_list=D("BShop")->getShopList();
		$this->assign("shop_list", $shop_list);
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0,"status"=>"1");
		$gold_type=$this->bbankgoldtype_model->getList($condition,$field='*',$limit="",$join='',$order='bgt_id desc');
		$this->assign("gold_type", $gold_type);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}


}

