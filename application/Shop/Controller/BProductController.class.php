<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BProductController extends ShopbaseController {

	function _initialize() {
		parent::_initialize();
		$this->bwarehouse_model = D('BWarehouse');
		$this->bproduct_model = D('BProduct');
		$this->bgoodsclass_model=D("BGoodsClass");
		$this->b_show_status('b_product');
	}
	// 处理货品列表表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL){
		$getdata=I("");
		$condition=array();
		if($getdata["search_name"]){
			$condition["bproduct.product_code|bgoods.goods_name"]=array("like","%".$getdata["search_name"]."%");
		}
		// 货品状态
		$status = I("status");
		if ($status && $status != 'all') {
			$condition ["bproduct.status"] = $status;
		}
		// 所属仓库
		$wh_id = I("wh_id");
		if ($wh_id > 0 || $wh_id === '0') {
			$condition ["bproduct.warehouse_id"] = $wh_id;
		}
		$ex_where = array_merge($condition, $ex_where);
		$request_data = $_REQUEST;
		$this->assign('request_data', $request_data);
	}
	
	// 货品列表
    public function index(){
		//货品状态下拉列表
		$status_model = D ( 'b_status' );
		$condition ["table"] = DB_PRE.'b_product';
		$condition ["field"] = 'status';
		$status_list = $status_model->getStatusInfo ( $condition );
		$this->assign ( "status_list", $status_list );
		//仓库下拉列表
		$condition=array("bwarehouse.deleted"=>0,"bwarehouse.company_id"=>get_company_id());
		$field='bwarehouse.*';
		$wh_data=$this->bwarehouse_model->alias("bwarehouse")->getList($condition,$field,$limit="",$join="",$order='bwarehouse.id desc');
		$this->assign ( "warehouse", $wh_data );
		//货品列表
		$getdata=I("");
		$condition=array("bproduct.company_id"=>$this->MUser['company_id'],"bproduct.deleted"=>0);
		$this->handleSearch($condition);
		$join="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
		//$join.=" left join ".DB_PRE."b_goods_common bgoodscommon on bgoodscommon.id=bgoods.goods_common_id";
		$join.=$this->bproduct_model->get_product_join_str();
		$field='bproduct.*,bwarehouse.wh_name';
		$field.=$this->bproduct_model->get_product_field_str();
		$count=$this->bproduct_model->alias("bproduct")->countList($condition,$field,$join,$order='bproduct.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bproduct_model->alias("bproduct")->getList($condition,$field,$limit,$join,$order='bproduct.id desc');
		foreach ($data as $k => $v){
			$data[$k]['product_detail']=$this->bproduct_model->get_product_detail_html($v);
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}

	/**
	 * 用于获取 调拨/出库/销售的货品列表数据
	 * // 查询获取货品列表页面绑定的数据 通过A("Shop/BProduct")->product_list($where);
	 * @param array $where
	 */
	public function product_list($where=array(),$type=1) {
		$getdata=I("");
		$condition=array("bproduct.company_id"=>$this->MUser['company_id'],"bproduct.deleted"=>0);
		if(!empty($where)){
			$condition=array_merge($where,$condition);
		}
		if($getdata["search_name"]){
			$condition["bproduct.product_code|bgoods.goods_name|bgoods.goods_code|gold.weight"]=array("like","%".trim($getdata["search_name"])."%");
		}
		$join ="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
		$join.=$this->bproduct_model->get_product_join_str();
		$join.="  left join ".DB_PRE."b_bank_gold bankgold on bankgold.bgt_id=g_detail.bank_gold_type and bankgold.shop_id=".get_shop_id();
		$join.="  left join ".DB_PRE."b_bank_gold p_bankgold on p_bankgold.bg_id=g_detail.bank_gold_type and p_bankgold.shop_id=0";
		$join.="  left join ".DB_PRE."b_metal_type metaltype on metaltype.id=g_detail.gold_type";
		$field='bproduct.*,bwarehouse.wh_name,g_detail.sell_feemode';
		$field.=',p_bankgold.formula,metaltype.id metaltype_id,bankgold.bg_id';
		$field.=$this->bproduct_model->get_product_field_str($type);
		$count=$this->bproduct_model->alias("bproduct")->countList($condition,$field,$join,$order='bproduct.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bproduct_model->alias("bproduct")->getList($condition,$field,$limit,$join,$order='bproduct.id desc');
		foreach($data as $k=>$v){
			//判断门店是否设定金价，如果没有则取商户金价
			if(empty($v['bg_id'])){
				$where=array();
				$where["bmt_id"]=$v["metaltype_id"];
				$where["formula"]=$v["formula"];
				$data[$k]["gold_price"]=D("BBankGold")->get_bankgold_price($where);
			}else{
				$data[$k]["gold_price"]=D("BBankGold")->get_price_by_bg_id($v['bg_id']);
			}
			$data[$k]["product_detail"]=$this->bproduct_model->get_product_detail_html($v);
			$data[$k]["product_pic"]=$this->bproduct_model->get_goods_pic($v['photo_switch'],$v['goods_id'],$v['id']);
			$common_data=$this->bproduct_model->get_product_common_data($v);
			$data[$k]["p_gold_weight"]=is_numeric($common_data["p_gold_weight"])?$common_data["p_gold_weight"]:"--";
			$data[$k]["p_total_weight"]=is_numeric($common_data["p_total_weight"])?$common_data["p_total_weight"]:"--";
		    if($v['g_sell_pricemode']==1){
				$data[$k]["g_sell_fee"]=$data[$k]["g_sell_fee"]>0?$data[$k]["g_sell_fee"]:0;
			    if($v['sell_feemode']==1){
			        $data[$k]['g_sell_price2']=bcmul($data[$k]["p_gold_weight"],bcadd($data[$k]["gold_price"],$data[$k]["g_sell_fee"],2),2);
			    }else if($v['sell_feemode']==0){
			        $data[$k]['g_sell_price2']=bcadd(bcmul($data[$k]["p_gold_weight"],$data[$k]["gold_price"],2),$data[$k]["g_sell_fee"],2);
			    }
				
			}else{
			    $data[$k]["g_sell_price"]=$v['sell_price']>0?$v['sell_price']:$v['g_sell_price'];//如果有货品的标签价显示货品标签价
			}
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("data", $data);
		$this->assign("empty_info","没有找到货品信息");
		$gethtmltreedata = $this->bgoodsclass_model->get_b_goodsclass();
		$this->assign("product_variety", $gethtmltreedata);
		//$this->display();
	}
	//导出货品列表数据
	function product_export($page=1){
		$condition=array("bproduct.company_id"=>$this->MUser['company_id'],"bproduct.deleted"=>0);
		$this->handleSearch($condition);
		$this->bproduct_model->excel_out($condition);
	}

}