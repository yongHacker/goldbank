<?php
/**
 * 批发商品管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BWgoodsController extends BusinessbaseController {
	
	public function _initialize() {
		$this->bgoodsclass_model=D("BGoodsClass");
		parent::_initialize();
	}
	public function goods_list($condition) {
		$this->bgoodscommon_model=D("b_goods_common");
		$getdata=I("");
		$condition=array("bgoodscommon.company_id"=>$this->MUser['company_id'],"bgoodscommon.deleted"=>0);
		if($getdata['id']){
			$getdata["class_id"]=$getdata["id"];
		}
		if($getdata["class_id"]){
			$condition["bgoodscommon.class_id"]=$getdata["class_id"];
		}
		if($getdata["search_name"]){
			$condition["bgoodscommon.goods_name"]=array("like","%".$getdata["search_name"]."%");
		}
		$join="left join ".DB_PRE."b_goods_class bgoodsclass on bgoodscommon.class_id=bgoodsclass.id";
		$join.="  join ".DB_PRE."b_wgoods bwgoods on bgoodscommon.id=bwgoods.goods_common_id";
		$count=$this->bgoodscommon_model->alias("bgoodscommon")
			->countList($condition,$field='bgoodscommon.*,bgoodsclass.class_name',$join,$order='bgoodscommon.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bgoodscommon_model->alias("bgoodscommon")
			->getList($condition,$field='bgoodscommon.*,bgoodsclass.class_name',$limit,$join,$order='bgoodscommon.id desc');
		foreach($data as $k=>$v){
			$condition=array("goods_id"=>$v["id"],"type"=>0);
			$data[$k]["pic"]=M("b_goods_pic")->where($condition)->order("is_hot desc")->getField("pic");
		}
		$sid=empty($getdata["class_id"])?0:$getdata["class_id"];
		//获取B分类
		$select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid);
		$this->assign("select_categorys", $select_categorys);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);

	}
	public function index(){
		$this->goods_list();
		$this->display();
	}

}

