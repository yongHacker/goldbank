<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BMetalPriceController extends BusinessbaseController {

	function _initialize() {
		parent::_initialize();
		$this->bmetalprice_model=D("BMetalPrice");
		$this->bmetaltype_model=D("BMetalType");
	}
	
	// 后台贵金属价格列表
    public function index(){
		$getdata=I("");
		$condition=array("bmetalprice.company_id"=>$this->MUser['company_id'],"bmetalprice.deleted"=>0);
		if($getdata["search_name"]){
			$condition["bmetaltype.name"]=array("like","%".$getdata["search_name"]."%");
		}
		if(!empty($_REQUEST['begin_time'])&&!empty($_REQUEST['end_time'])){
			$condition['bmetalprice.create_time']=array('between',array(strtotime($_REQUEST['begin_time']),strtotime($_REQUEST['end_time'])));
		}else if(!empty($_REQUEST['begin_time'])){
			$condition['bmetalprice.create_time']=array('gt',strtotime($_REQUEST['begin_time']));
		}else if(!empty($_REQUEST['end_time'])){
			$condition['bmetalprice.create_time']=array('lt',strtotime($_REQUEST['end_time']));
		}
		$join="left join ".DB_PRE."b_metal_type bmetaltype on bmetaltype.id=bmetalprice.b_metal_type_id";
		$join.=" left join ".DB_PRE."m_users musers on musers.id=bmetalprice.user_id";
		$join.=" left join ".DB_PRE."b_employee b_employee on b_employee.user_id=musers.id and b_employee.deleted=0 and b_employee.company_id=".get_company_id();
		//$field="bmetalprice.*,bmetaltype.name,musers.user_nicename";
		$field="bmetalprice.*,bmetaltype.name,b_employee.employee_name user_nicename";
		$count=$this->bmetalprice_model->alias("bmetalprice")->countList($condition,$field,$join,$order='bmetalprice.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bmetalprice_model->alias("bmetalprice")->getList($condition,$field,$limit,$join,$order='bmetalprice.id desc');
		$parentid = I("get.parent",0,'intval');
		$tree=$this->bmetaltype_model->get_metaltype_tree($parentid);
		$this->assign("gold_category",$tree);
		$this->assign("page", $page->show('Admin'));
		$this->assign("cate_list",$data);
		$this->display();
	}
	
	// 贵金属价格添加
	public function add(){
		if (IS_POST) {
			$postdata=I("post.");
			$data["company_id"]=$this->MUser["company_id"];
			$data["user_id"]=$this->MUser["id"];
			$data["create_time"]=time();
			$data["price"]=$postdata["price"];
			$data["b_metal_type_id"]=$postdata["b_metal_type_id"];
			//if ($this->bmetalprice_model->create()!==false) {
				if ($this->bmetalprice_model->insert($data)!==false) {
					/*$condition=array("id"=>$postdata["b_metal_type_id"]);
					$data["price"]=$postdata["price"];
					$this->bmetaltype_model->update($condition,$data);*/
					$this->success("添加成功！",U("BMetalPrice/index"));
				} else {
					$this->error("添加失败！");
				}
			/*} else {
				$this->error($this->bmetalprice_model->getError());
			}*/
		}else{
			$parentid = I("get.parent",0,'intval');
			$tree=$this->bmetalprice_model->get_metaltype_tree($parentid);
			$this->assign("gold_category",$tree);
			$this->assign("parent",$parentid);
			$this->assign("level",$parentid);
			$this->display();
		}

	}

}