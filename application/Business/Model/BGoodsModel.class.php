<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BGoodsModel extends BCommonModel{
	public function _initialize() {
		parent::_initialize();
	}

	// 获取商品信息
	public function getGoodsInfo($condition, $field = '*', $join=""){

		$goods_info = $this->where($condition)->field($field)->join($join)->find();

		return $goods_info;
	}

	// 判断商品是否存在
	public function goods_is_exsit($condition, $join) {

		$goods_info = $this->getGoodsInfo($condition, $field = '*', $join);

		return (!empty($goods_info));
	}

	// 批量将从excel中导入的数据返回 - 原 计重
	// public function excel_return($data){
	// 	$result = array();

	// 	foreach ($data as $key => $val) {
	// 		$re = array();

	// 		$where = array(
	// 			'goods_code' => array('like','%' . $val [0] . '%')
	// 		);
	// 		$field = 'id, goods_name, goods_code, price_mode, purity';

	// 		$goods_info = $this->getGoodsInfo($where, $field);
			
	// 		if(empty($goods_info)) continue;

	// 		$re['price_mode'] = $goods_info['price_mode'];
 //            $re['purity'] = $goods_info['purity'];

	// 		$re['goods_code'] = $goods_info['goods_code'];
	// 		$re['id'] = $goods_info['id'];
	// 		$re['goods_name'] = $goods_info['goods_name'];
	// 		$re['product_code'] = $val[2];
	// 		$re['qc_code'] = $val[3];
	// 		$re['isd_num'] = $val[4];
	// 		$re['weight'] = $val[5] ? $val[5] : 0;
	// 		$re['buy_price'] = $val[6] ? $val[6] : 0;
	// 		$re['buy_m_fee'] = $val[7] ? $val[7] : 0;
	// 		$re['cost_price'] = $re['weight'] * ($re['buy_price'] + $re['buy_m_fee']);
	// 		$re['sell_price'] = $val[9] ? $val[9] : 0;
	// 		$re['extras'] = $val[8] ? $val[8] : 0;

	// 		$result[] = $re;
	// 	}
	// 	return $result;
	// }

	private function get_info_by_col($col = 0){
		$where = array(
			'goods_code' => array('like', '%' .$col . '%')
		);
		$field = 'gb_b_goods.id, gb_b_goods.goods_name,gb_b_goods.goods_code, gd.purity, gd.weight';
		$join = 'left join gb_b_goldgoods_detail as gd on gd.goods_id = gb_b_goods.id';
		$goods_info = $this->getInfo($where, $field,$join);
		return $goods_info;
	}

	// 批量将从excel中导入的数据返回
	public function excel_return($data, $map = array()){
		$result = array();
		foreach ($data as $key => $val) {
			$re = array();

			$goods_info = $this->get_info_by_col($val[0]);

			if(empty($goods_info)) continue;

			$re['id'] = $goods_info['id'];
			$re['goods_code'] = $goods_info['goods_code'];
			$re['goods_id'] = $goods_info['id'];
			$re['purity'] = $goods_info['purity'];
			$re['weight'] = $goods_info['weight'];
			$re['goods_name'] = $goods_info['goods_name'];

			foreach ($map as $k => $v) {
				if(isset($v['is_key']) && $v['is_key'] == 1){
					continue;
				}

				$re[$k] = $val[$v['col']];
			}

			$result[] = $re;
		}
		return $result;
	}
	public function excel_return_gold($data, $map = array()){
		$result = array();
		foreach ($data as $key => $val) {
			$re = array();

			$goods_info = $this->get_info_by_col(0);

			if(empty($goods_info)) continue;

			$re['id'] = $goods_info['id'];
			$re['goods_code'] = $goods_info['goods_code'];
			$re['goods_id'] = $goods_info['id'];
			$re['purity'] = $goods_info['purity'];
			$re['weight'] = $goods_info['weight'];
			$re['goods_name'] = $goods_info['goods_name'];

			foreach ($map as $k => $v) {
				if(isset($v['is_key']) && $v['is_key'] == 1){
					continue;
				}

				$re[$k] = $val[$v['col']];
			}

			$result[] = $re;
		}
		return $result;
	}
	public function goods_distinct($condition, $type = "select", $limit = "", $field = "*")
	{
		if ($type == "select") {
			$data = $this->join("left join " . C('DB_PREFIX') . "b_product  as bp on " . C('DB_PREFIX') . "b_goods.id= bp.goods_id")->join("left join ".C('DB_PREFIX')."b_goods_common as gc on ".C('DB_PREFIX')."b_goods.goods_common_id = gc.id")
				->where($condition)->group(C('DB_PREFIX') . "b_goods.id")->order(C('DB_PREFIX') . "b_goods.goods_code")->limit($limit)->field($field.",gc.goods_name as goodsname")->select();
			return $data;
		} elseif ($type == "count") {
			$count = $this->join("left join " . C('DB_PREFIX') . "b_product as bp on " . C('DB_PREFIX') . "b_goods.id= bp.goods_id")->where($condition)->count("distinct(" . C('DB_PREFIX') . "b_goods.id)");
			return $count;
		}
	}
}
