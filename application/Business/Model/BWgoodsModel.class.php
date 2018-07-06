<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BWgoodsModel extends BCommonModel{
	public function _initialize() {
		parent::_initialize();
	}

	// 获取商品信息
	public function getGoodsInfo($condition, $field = '*'){

		$goods_info = $this->where($condition)->field($field)->find();

		return $goods_info;
	}

	// 判断商品是否存在
	public function goods_is_exsit($condition) {

		$goods_info = $this->getGoodsInfo($condition);

		return (!empty($goods_info));
	}
	//获取批发商品图片
	function get_goods_pic($goods_id){
		$pic="";
		$condition=array("deleted"=>0,"type"=>2,"goods_id"=>$goods_id);
		$goods_pic=D("BGoodsPic")->getInfo($condition,$field='*',$join='',$order='',$group='');
		$pic=$goods_pic['pic'];
		return $pic;
	}

}
