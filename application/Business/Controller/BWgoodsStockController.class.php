<?php
/**
 * 批发商品库存管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BWgoodsStockController extends BusinessbaseController {
	
	public function _initialize() {
		$this->bgoodsclass_model=D("BGoodsClass");
		parent::_initialize();
	}
	public function goods_list($where) {
		$this->bwgoodsstock_model=D("b_wgoods_stock");
		$getdata=I("");
		$condition=array("bgoodscommon.company_id"=>$this->MUser['company_id'],"bgoodscommon.deleted"=>0);
		$condition=array_merge($condition,$where);
		if($getdata['id']){
			$getdata["class_id"]=$getdata["id"];
		}
		if($getdata["class_id"]){
			$condition["bgoodscommon.class_id"]=$getdata["class_id"];
		}
		if($getdata["search_name"]){
			$condition["bgoodscommon.goods_name"]=array("like","%".$getdata["search_name"]."%");
		}
		$join=" left  join ".DB_PRE."b_wgoods bwgoods on bwgoods.id=bwgoodsstock.goods_id";
		$join.=" left  join ".DB_PRE."b_goldgoods_wholesale bwgoodsdetail on bwgoods.id=bwgoodsdetail.goods_id";
		$join.=" left  join ".DB_PRE."b_goods_common bgoodscommon on bgoodscommon.id=bwgoods.goods_common_id";
		$join.=" left join ".DB_PRE."b_goods_class bgoodsclass on bgoodscommon.class_id=bgoodsclass.id";
		$join.=" left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bwgoodsstock.warehouse_id";
		$field='bwgoods.*,bwgoodsstock.id wgoods_stock_id,bwgoodsstock.warehouse_id,bwgoodsstock.goods_stock,bwgoodsstock.goods_num,bwarehouse.wh_name,bgoodsclass.class_name';
		$field.=',bwgoodsdetail.sale_fee,bwgoodsdetail.purity';
		$count=$this->bwgoodsstock_model->alias("bwgoodsstock")->countList($condition,$field,$join,$order='bgoodscommon.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bwgoodsstock_model->alias("bwgoodsstock")->getList($condition,$field,$limit,$join,$order='bgoodscommon.id desc');
		foreach($data as $k=>$v){
			$condition=array("goods_id"=>$v["id"],"type"=>2);
			$data[$k]["product_pic"]=M("b_goods_pic")->where($condition)->order("is_hot desc")->getField("pic");
		}
		$sid=empty($getdata["class_id"])?0:$getdata["class_id"];
		//获取B分类
		$select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid);
		$this->assign("select_categorys", $select_categorys);
		$this->assign("page", $page->show('Admin'));
		$this->assign("data",$data);
		$this->assign("empty_info","暂无商品库存");

	}
	public function index(){
		$this->goods_list();
		$this->display();
	}

}

