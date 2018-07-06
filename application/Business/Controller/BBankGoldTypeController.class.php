<?php
/**
 * 金子类型管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BBankGoldTypeController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bbankgoldtype_model=D("BBankGoldType");
		$this->bmetaltype_model=D("BMetalType");
	}
	/**
	 * 金子类型展示
	 */
	public function index() {
		$getdata=I("");
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
		if($getdata["search_name"]){
			$condition["name"]=array("like","%".$getdata["search_name"]."%");
		}
		if(!empty($getdata["b_metal_type_id"])){
			$condition["b_metal_type_id"]=$getdata["b_metal_type_id"];
		}

		$count=$this->bbankgoldtype_model->countList($condition,$field='*',$join='',$order='bgt_id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bbankgoldtype_model->getList($condition,$field='*',$limit,$join='',$order='bgt_id desc');
		$parentid = empty($getdata["b_metal_type_id"])?0:$getdata["b_metal_type_id"];
		$tree=$this->bmetaltype_model->get_metaltype_tree($parentid);
		$metaltypes=$this->bmetaltype_model->getList(array("deleted"=>"0"),"id,name");
		$mt_list=array();
		foreach ($metaltypes as $k=>$v){
			$mt_list[$v['id'].""]=$v['name'];
		}
		$this->assign("terms_tree",$tree);
		$this->assign("page", $page->show('Admin'));
		$this->assign("mt_list",$mt_list);
		$this->assign("list",$data);
		$this->display();
	}
	//金子类型添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$parentid = I("get.parent",0,'intval');
			$tree=$this->bmetaltype_model->get_metaltype_tree($parentid);
			$this->assign("terms_tree",$tree);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bbankgoldtype_model->create()!==false) {
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
					$data["name"]=$postdata["name"];
					$data["status"]=$postdata["status"];
					$data["b_metal_type_id"]=$postdata["b_metal_type_id"];
					$data["update_time"]=time();
					$data["deleted"]=0;
					if(empty($postdata["name"])){
						$this->error("价格类型名称不能为空！");
					}
					$info=$this->bbankgoldtype_model->getInfo(array("name"=>$postdata["name"],'company_id'=>get_company_id()));
					if($info){
						$this->error("添加失败,价格类型名称不能重复！");
					}
					$bgoodsclass=$this->bbankgoldtype_model->insert($data);
					if ($bgoodsclass!==false) {
						$this->success("添加成功！", U("BBankGoldType/index"));
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bbankgoldtype_model->getError());
				}
			}
		}
	}

	//金子类型编辑
	public function edit() {
		$postdata=I("post.");
		$getdata=I("get.");
		if(empty($postdata)){
			$condition=array("bgt_id"=>$getdata["bgt_id"]);
			$data=$this->bbankgoldtype_model->getInfo($condition,$field='*',$join='');
			$tree=$this->bmetaltype_model->get_metaltype_tree($data["b_metal_type_id"]);
			$this->assign("terms_tree",$tree);
			$this->assign("data",$data);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bbankgoldtype_model->create()!==false) {
					$data=array();
					$data["name"]=$postdata["name"];
					$data["status"]=$postdata["status"];
					$data["b_metal_type_id"]=$postdata["b_metal_type_id"];
					$data["update_time"]=time();
					$conditon=array("bgt_id"=>$postdata["bgt_id"]);
					$where['name']=$postdata["name"];
					$where['company_id']=get_company_id();
					$where['bgt_id']=array("neq",$postdata['bgt_id']);
					if(empty($postdata["name"])){
						$this->error("价格类型名称不能为空！");
					}
					$info=$this->bbankgoldtype_model->getInfo($where);
					if($info){
						$this->error("编辑失败,价格类型名称不能重复！");
						die();
					}
					$bgoodsclass=$this->bbankgoldtype_model->update($conditon,$data);
					if ($bgoodsclass!==false) {
						$this->success("编辑成功！", U("BBankGoldType/index"));
					} else {
						$this->error("编辑失败！");
					}
				} else {
					$this->error($this->bbankgoldtype_model->getError());
				}
			}
		}
	}

	//金子类型删除
	public function deleted() {
		$postdata=I("");
		$count=D("BGoldgoodsDetail")->where(array('bank_gold_type'=>$postdata["bgt_id"]))->count();
		if ($count > 0) {
			$this->error("该金价类型已经使用，只能编辑禁用！");
		}
		$data=array();
		$data["update_time"]=time();
		$data["deleted"]=1;
		$conditon=array("bgt_id"=>$postdata["bgt_id"]);
		$bgoodsclass=$this->bbankgoldtype_model->update($conditon,$data);
		if ($bgoodsclass!==false) {
			$this->success("删除成功！", U("BBankGoldType/index"));
		} else {
			$this->error("删除失败！");
		}
	}

}

