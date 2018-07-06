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

class BProductController extends BusinessbaseController {

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
			$condition["bproduct.product_code|bgoods.goods_name"]=array("like","%".trim($getdata["search_name"])."%");
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
		//商品分类
        if($getdata["class_id"]){
            $class_id = $getdata["class_id"];
            $class_list = $this->bgoodsclass_model->getALLGoodsClass($class_id, array());
            $class_id_list = '0,' . $class_id;
            foreach ($class_list as $key => $val) {
                $class_id_list .= ',' . $val['id'];
            }
            $condition["g_common.class_id"]=array('in',$class_id_list);
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
		//商品分类类型

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
		$join.=$this->bproduct_model->get_product_join_str();
		//$join.=" left join ".DB_PRE."b_goods_class b_goods_class on b_goods_class.id=g_common.class_id";
		$field='bproduct.*,bwarehouse.wh_name,g_common.class_id,bgoods.goods_common_id';
		$field.=$this->bproduct_model->get_product_field_str();
		$count=$this->bproduct_model->alias("bproduct")->countList($condition,$field,$join,$order='bproduct.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bproduct_model->alias("bproduct")->getList($condition,$field,$limit,$join,$order='bproduct.id desc');
		$class_status = D('AGoodsClass')->_get_class_status();
		foreach ($data as $k => $v){
			$data[$k]['product_detail']=$this->bproduct_model->get_product_detail_html($v);
			$data[$k]['class_name'] = $this->bgoodsclass_model->classNav($v["class_id"]);
			// 商品大类列表
			$data[$k]['type_name'] = $class_status[$v['type']];
		}
        //获取B分类
        $sid=empty($getdata["class_id"])?0:$getdata["class_id"];
        $select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid);

		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->assign("select_categorys",$select_categorys);
		$this->display();
	}

	/**
	 * 用于获取 调拨/出库/销售/退货 的货品列表数据
	 * // 查询获取货品列表页面绑定的数据 通过A("Business/BProduct")->product_list($where);
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
		$join.=" left join ".DB_PRE."b_bank_gold bankgold on bankgold.bgt_id=g_detail.bank_gold_type and bankgold.parent_id=0 and bankgold.shop_id=0";
		$join.=" left join ".DB_PRE."b_metal_type metaltype on metaltype.id=g_detail.gold_type";
        $join.=" left join ".DB_PRE."b_procure_storage ps on ps.id=bproduct.storage_id";
        $join.=" left join ".DB_PRE."b_procurement pm on pm.id=ps.procurement_id";
		$field='bproduct.*,bwarehouse.wh_name,g_detail.sell_feemode';

		$field.=',bankgold.formula,metaltype.id metaltype_id';
		$field.=',pm.pricemode as procurement_pricemode';
		$field.=$this->bproduct_model->get_product_field_str($type);

		$count=$this->bproduct_model->alias("bproduct")->countList($condition,$field,$join,$order='bproduct.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bproduct_model->alias("bproduct")->getList($condition,$field,$limit,$join,$order='bproduct.id desc');
		foreach($data as $k=>$v){
			$data[$k]["metal_price"]=$v["metal_price"]=D("BMetalType")->get_metaltype_price(array("b_metal_type_id"=>$v["metaltype_id"]));
			$price=$v["metal_price"];
			$expression = str_replace("price", (float)$price, $v["formula"]);
			if(!empty($expression)){
				eval("\$price=" . $expression . ";");
				$data[$k]["gold_price"]=$price;
			}else{
				$data[$k]["gold_price"]=0;
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
	function product_detail(){
		$name=I("search_name");
		if($name) {
			$condition=array("bproduct.company_id"=>$this->MUser['company_id'],"bproduct.deleted"=>0,"bproduct.product_code"=>$name);
			$join="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
			$join.=" left join ".DB_PRE."b_product_sub sub on sub.id=bproduct.certify_type";
			$field='bproduct.*,bproduct.certify_code p_certify_code,bproduct.certify_type as p_certify_type_name,
			bwarehouse.wh_name,bproduct.certify_price p_certify_price,bproduct.sub_product_code p_sub_product_code';
			$join.=$this->bproduct_model->get_product_join_str();
			$field.=$this->bproduct_model->get_product_field_str();
			$join.=" left join ".DB_PRE."b_bank_gold_type b_g_type on b_g_type.bgt_id=g_detail.bank_gold_type";
			$join.=" left join ".DB_PRE."b_metal_type metaltype on metaltype.id=g_detail.gold_type";
			$field.=',g_common.class_id,metaltype.name metal_type_name,b_g_type.name bank_gold_type_name';
			$product=$this->bproduct_model->alias("bproduct")->getInfo($condition,$field,$join,$order='bproduct.id desc');
			$product_detail=$this->bproduct_model->get_product_info($product);//货品基本信息
			$this->assign("product_detail",$product_detail);
			$this->assign("data",$product);
			//调拨
			$status_model = D ( 'b_status' );
			$condition=array();
			$condition ["table"] = DB_PRE.'b_allot';
			$condition ["field"] = 'status';
			$allot_status_list = $status_model->getFieldValue ( $condition );
			$this->assign ( "allot_status", $allot_status_list );
			$allotmap["ballotdetail.p_id"]=$product["id"];
			$allotdata=D("BAllotDetail")->new_getList_detail($allotmap);
			$this->assign("allotdata",$allotdata);
			//出库
			$status_model = D ( 'b_status' );
			$condition=array();
			$condition ["table"] = DB_PRE.'b_outbound_order';
			$condition ["field"] = 'status';
			$outbound_status_list = $status_model->getFieldValue ( $condition );
			$this->assign ( "outbound_status", $outbound_status_list );
			$outboundmap["boutbounddetail.p_id"]=$product["id"];
			$outbounddetail=D("BOutboundDetail")->new_getList_detail($outboundmap);
			$this->assign("outbounddata",$outbounddetail);
		}else{
			$this->error("参数错误！");
		}
		//采购权限判断，并查看信息
		if(sp_auth_check(get_user_id(),$name="Business/BProduct/buy_detail","or","b")){
			$main_tbl = C('DB_PREFIX').'b_procure_storage';
			$procure_map["bproduct.id"]=$product["id"];
			$field = $main_tbl.'.*';
			$field .= ', p.batch as procure_batch, s.company_name, p.status as p_status';
			$field .= ', (CASE p.pricemode WHEN 1 THEN "计重" ELSE "计件" END)as show_pricemode,pricemode';
			$field .= ', from_unixtime(p.procure_time, "%Y-%m-%d")as show_procure_time';
			$field .= ', (
			CASE '.$main_tbl.'.type
			WHEN 1 THEN "素金"
			WHEN 2 THEN "金料"
			WHEN 3 THEN "裸钻"
			WHEN 4 THEN "镶嵌"
			WHEN 5 THEN "玉石"
			WHEN 6 THEN "摆件"
			ELSE "" END
		) as show_type';
			$field .= ', ('.$main_tbl.'.real_weight - '.$main_tbl.'.weight)as diff_weight';
			$field .= ', ifnull(su.user_nicename,su.mobile)as creator_name';
			$field .= ', IF('.$main_tbl.'.storager_id, ifnull(su.user_nicename,su.mobile),"-")as storager_name';
			$field .= ', from_unixtime('.$main_tbl.'.create_time, "%Y-%m-%d")as show_create_time';
			$field .= ', IF('.$main_tbl.'.storage_status, (
			CASE '.$main_tbl.'.status
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		), "待分称")as show_status';
			$join=' join  '.C('DB_PREFIX').'b_procure_storage on '.$main_tbl.'.id=bproduct.storage_id';
			$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_procurement as p ON p.id = '.$main_tbl.'.procurement_id';
			$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_supplier as s ON (s.id = p.supplier_id)';
			$join .= ' LEFT JOIN '.C('DB_PREFIX').'m_users as cu ON (cu.id = '.$main_tbl.'.creator_id)';
			$join .= ' LEFT JOIN '.C('DB_PREFIX').'m_users as su ON (su.id = '.$main_tbl.'.storager_id)';
			$procure_list=D("BProduct")->alias("bproduct")->getList($procure_map,$field,$limit='',$join);
			$this->assign("buydata",$procure_list);
		}else{
			//echo ":(，我没有权限";
		}
		//销售权限判断，并查看信息
		if(sp_auth_check(get_user_id(),$name="Business/BProduct/sell_detail","or","b")){
			$status_model = D ( 'b_status' );
			$condition=array();
			$condition ["table"] = DB_PRE.'b_sell';
			$condition ["field"] = 'status';
			$sell_status_list = $status_model->getFieldValue ( $condition );
			$this->assign ( "sell_status", $sell_status_list );
			$sellmap["bselldetail.product_id"]=$product["id"];
			$selldetail=D("BSellDetail")->new_getList_detail($sellmap);
			$this->assign("selldata",$selldetail);
			if($product['type']==1&&$product['is_cut']==1){
				$sell_cut_product_list = D('BRecoveryProduct')->getInfo(array('product_id'=>$product['id']));
				$this->assign("cutdata",$sell_cut_product_list);
			}

			//print_r($allotdata);
		}else{
			//echo ":(，我没有权限";
		}

		$this->display();
	}
	// 单个货品采购明细，无内容，只做权限标识，该方法删除不影响功能,看function goods_detail()
	public function buy_detail() {}
	// 单个货品调拨明细，无内容，只做权限标识，该方法删除不影响功能，看function goods_detail()
	public function allot_detail() {}
	// 单个货品销售明细，无内容，只做权限标识，该方法删除不影响功能,看function goods_detail()
	public function sell_detail() {}
	// 单个货品销售明细，无内容，只做权限标识，该方法删除不影响功能,看function goods_detail()
	public function outbound_detail() {}
	//导出货品列表数据
	function product_export($page=1){
		$condition=array("bproduct.company_id"=>$this->MUser['company_id'],"bproduct.deleted"=>0);
		$this->handleSearch($condition);
		$this->bproduct_model->excel_out($condition);
	}
	/**
	 * 货品统计
	 */
	public function product_statistic(){
	    if(I('o_type')=='ajax'){
	        $model_class=D('BGoodsClass');
	        $condition=array(
	            'goods_class.company_id'=>get_company_id(),
	            'goods_class.deleted'=>0,
	        );
	        $product_status='';
	        if(!empty(I('status'))&&I('status')!='all'){
	            $product_status=" and product.status=".trim(I('get.status'));
	        }
	        if(empty(I('status'))){
	            $product_status=" and product.status= 2";
	        }
	        $join=" left join gb_b_goods_common goods_common on goods_common.class_id=goods_class.id";
	        $join.=" left join gb_b_goods goods on goods.goods_common_id=goods_common.id";
	        $join.=" left join gb_b_product product on product.goods_id=goods.id and product.deleted=0".$product_status;
	        $join.=" left join gb_b_product_gold product_gold on product_gold.product_id=product.id";
	        $categories = $model_class->alias('goods_class')->getList($condition,$field='goods_class.*,goods_class.class_name as name,count(product.id) as product_count,if(sum(product_gold.weight)>0,sum(product_gold.weight),0) as product_weight',$limit=null,$join,$order='goods_class.id asc',$group='goods_class.id');
	        /* $count=0;
	        $data=$this->_get_attr($categories,0,$count); */
	        /*第二种方法计算每一层级的数值*/
	        $data=$model_class->get_attr($categories,0);
	        $this->_get_list($data);
 
	        echo json_encode($data,true);
	    }else{
	        $this->b_show_status('b_product');
	        $this->display();
	    }
	}
	private function _get_list(&$list){
	    foreach($list as $key => $val){
	        if(!empty($val['children'])){
	            $count=$this->_get_count($val['children'], $val['product_count']);
	            $list[$key]['product_count']=$count;
	            $weight=$this->_get_weight($val['children'], $val['product_weight']);
	            $list[$key]['product_weight']=$weight;
	            $this->_get_list($list[$key]['children']);
	        }
	    }
	}
	/**
	 * 获取下级的count总和
	 * @param unknown $list
	 * @param unknown $count
	 */
	private function _get_count(&$list,&$count){
	    foreach($list as $key =>$val){
	        $count+=$val['product_count'];
	        if(!empty($val['children'])){
	            $this->_get_count($list[$key]['children'],$count);
	        }
	    }
	    return $count;
	}
	/**
	 * 获取下级的weight总和
	 * @param unknown $list
	 * @param unknown $count
	 */
	private function _get_weight(&$list,&$weight){
	    foreach($list as $key =>$val){
	        $weight+=$val['product_weight'];
	        if(!empty($val['children'])){
	            $this->_get_count($list[$key]['children'],$weight);
	        }
	    }
	    return $weight;
	}
	/**
	 * 无效方法
	 * @param $a 数组
	 * @param $pid 父类id
	 * @return array //获取树形数组
	 */
	/* private function _get_attr($a,$pid,&$count){
	    $tree = array();                                //每次都声明一个新数组用来放子元素
	    foreach($a as $v){
	        if($v['pid'] == $pid){                      //匹配子记录
	            $v['children'] = $this->_get_attr($a,$v['id'],$count); //递归获取子记录
	            $count+=$v['product_count'];
	            $cur_count=$v['product_count'];
	            $v['product_count']=$count;
	            if($v['children'] == null){
	                unset($v['children']);             //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
	                $v['product_count']=$cur_count;
	            }
	            $tree[] = $v;                           //将记录存入新数组
	            if($v['pid']==0){
	                $count=0;
	            }
	        }
	    }
	    return $tree;                                  //返回新数组
	} */
	
	
}