<?php
/**
 * @author lzy 2018-04-28
 * 商品列表管理
 */
namespace Business\Controller;
use Business\Controller\BusinessbaseController;

class BGoodsController extends BusinessbaseController {
	private $bgoods_model;
	public function _initialize() {
		$this->bgoods_model=D("BGoods");
		$this->bgoodsclass_model=D("BGoodsClass");
		$this->bmetaltype_model=D("BMetalType");
		$this->bbankgoldtype_model=D("BBankGoldType");
		$this->b_show_status('b_goods_common');
		parent::_initialize();
	}
	public function index(){
	    //输出分类
	    $sid=empty(I("class_id"))?0:I("class_id");
	    $select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid);
	    $this->assign("select_categorys", $select_categorys);
	    
	    $condition=array('gb_b_goods.deleted'=>0,'gb_b_goods.company_id'=>get_company_id());
	    if(I("class_id")){
	        $class_id = I("class_id");
	        $class_list = $this->bgoodsclass_model->getALLGoodsClass($class_id, array());
	        $class_id_list = '0,' . $class_id;
	        foreach ($class_list as $key => $val) {
	            $class_id_list .= ',' . $val['id'];
	        }
	        $condition["gb_b_goods_class.id"]=array('in',$class_id_list);
	    }
	    if(I('goods_common_id')){
	        $condition["gb_b_goods.goods_common_id"]=I('goods_common_id');
	    }
	    if(!empty(I('search_name'))){
	        $condition['gb_b_goods_class.`class_name`|gb_b_goods.`goods_code`|gb_b_goods.`goods_name`']=array("like","%".I("search_name")."%");
	    }
        if(!empty(I('status'))||I('status')==='0'){
            $status = I('status',1,'intval');
            $condition["gb_b_goods.status"]=array($status);
        }
	    $field='gb_b_goods_pic.`pic`,gb_b_goods.id,gb_b_goods_class.`class_name`,gb_b_goods_common.type,gb_b_goods_common.belong_type,gb_b_goods_common.`goods_code` AS goods_common_code,gb_b_goods.`goods_code`,gb_b_goods.`goods_name`,gb_b_goods.`goods_spec`,gb_b_goods.`status`';
	    $join='JOIN gb_b_goods_common ON gb_b_goods_common.`id`=gb_b_goods.`goods_common_id`
            	JOIN gb_b_goods_class ON gb_b_goods_class.id=gb_b_goods_common.`class_id`
            	left JOIN gb_b_goods_pic ON gb_b_goods.id=gb_b_goods_pic.`goods_id` AND gb_b_goods_pic.type=0';
	    $count=$this->bgoods_model->countList($condition,'',$join);
	    $page = $this->page($count, $this->pagenum);
	    $limit=$page->firstRow.",".$page->listRows;
	    $goods_list=$this->bgoods_model->getList($condition,$field,$limit,$join,'gb_b_goods.goods_code asc');
	    $this->assign("goods_list",$goods_list);
	    $this->assign('page',$page->show('Admin'));
	    $this->display();
	}

	// 用于选择商品规格对应货品的iframe
	public function goods_iframe($condition)
	{
		// 输出分类
	    $class_id = empty(I('class_id')) ? 0 : I('class_id');
	    $select_categorys = $this->bgoodsclass_model->get_b_goodsclass($class_id);
	    $this->assign('select_categorys', $select_categorys);
	    
	    $_condition = array(
	    	'gb_b_goods.deleted' => 0, 
	    	'gb_b_goods.company_id' => get_company_id(),
	    	'gb_b_product.id' => array('exp', 'IS NOT NULL')
	    );
	    if($class_id){
	        $class_list = $this->bgoodsclass_model->getALLGoodsClass($class_id);
	        $class_id_list = '0,' . $class_id;
	        foreach ($class_list as $key => $val) {
	            $class_id_list .= ',' . $val['id'];
	        }
	        $_condition['gb_b_goods_class.id'] = array('in', $class_id_list);
	    }
	    if(I('goods_common_id')){
	        $_condition['gb_b_goods.goods_common_id'] = I('goods_common_id');
	    }
	    if(!empty(I('search'))){
	        $_condition['gb_b_goods_class.`class_name`|gb_b_goods.`goods_code`|gb_b_goods.`goods_name`']=array('like', "%" . I("search") . "%");
	    }
        if(!empty(I('status')) || I('status') === '0'){
            $status = I('status/d', 1);
            $_condition['gb_b_goods.status'] = $status;
        }
        $condition = empty($condition) ? $_condition : array($_condition, $condition);

	    $field = 'DISTINCT gb_b_goods.`id`, gb_b_goods.`goods_code`, gb_b_goods.`goods_name`, gb_b_goods.`goods_spec`, gb_b_goods.`status`, ';
	    $field .= 'gb_b_goods_class.`class_name`, gb_b_goods_common.`type`, gb_b_goods_common.`goods_code` AS goods_common_code';
	    $join = ' LEFT JOIN gb_b_goods_common ON gb_b_goods_common.`id` = gb_b_goods.`goods_common_id`';
        $join .= ' LEFT JOIN gb_b_goods_class ON gb_b_goods_class.`id` = gb_b_goods_common.`class_id`';
        $join .= ' LEFT JOIN gb_b_product ON gb_b_product.`goods_id` = gb_b_goods.`id` AND gb_b_product.`deleted` = 0';

        $sql = $this->bgoods_model->field($field)->join($join)->where($condition)->buildSql();
	    $count = $this->bgoods_model->query("SELECT COUNT(*) AS tp_count FROM {$sql} a LIMIT 1");
	    $page = $this->page($count[0]['tp_count'], $this->pagenum);
	    $limit = $page->firstRow . ',' . $page->listRows;
	    $goods_list = $this->bgoods_model->getList($condition, $field, $limit, $join, 'gb_b_goods.goods_code asc');
	    $this->assign('goods_list', $goods_list);
	    $this->assign('page', $page->show('Admin'));
	}
}