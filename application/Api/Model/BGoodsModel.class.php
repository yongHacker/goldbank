<?php
namespace Api\Model;
use Api\Model\ApiCommonModel;
class BGoodsModel extends ApiCommonModel {
    
    public function __construct() {
        parent::__construct();
    }
	
	// 获取规格商品详情列表
	public function getGoodsDetailList($condition, $field = '', $warehouse_id) {
	    $field = empty($field) ? 'g.*, gco.goods_name AS goods_commong_name' : $field;
	    $condition['g.deleted'] = 0;
	    $condition['g.status'] = 1;
	    $join = 'LEFT JOIN __B_GOODS_COMMON__ AS gco on g.goods_common_id = gco.id AND gco.deleted = 0 ';
	    $join .= 'LEFT JOIN __B_GOODS_PIC__ AS gp on g.id = gp.goods_id AND gp.type = 0 ';
        $join .= 'LEFT JOIN __B_PRODUCT__ AS p on p.goods_id = g.id AND p.status = 2 AND warehouse_id = ' . $warehouse_id . ' AND p.deleted = 0 ';
        $join .= 'LEFT JOIN __B_GOLDGOODS_DETAIL__ AS ggd on ggd.goods_id = g.id';
	    $group = 'g.id';
		$goods_list = $this->alias('g')->getList($condition, $field, $limit = '', $join, $order = '', $group);
		
		return $goods_list;
	}

	// 获取规格商品列表
	public function getGoodsList($condition = array(), $field = 'g.*', $limit = '', $order = null) {
	    $join = 'LEFT JOIN __B_GOODS_COMMON__ as gco on g.goods_common_id = gco.id';
        $goods_list = $this->alias('g')->getList($condition, $field . ',gco.goods_name as goodsname', $limit, $join, $order, $group = '');
        return $goods_list;
	}

	// 获取规格商品信息
	public function getGoodsInfo($condition, $field = '*') {
	    $condition['g.deleted'] = 0;
	    $condition['g.status'] = 1;
		$goods_info = $this->alias('g')->getInfo($condition, $field);
		return $goods_info;
	}
}