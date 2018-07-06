<?php

/**
*	采购结算、金料货品单关系列表
**/

namespace Business\Model;

use Business\Model\BCommonModel;

class BProcureSettleGoldModel extends BCommonModel {

	/** 通过 settle_id 添加 product 与settle_id 的关系
	*	I('post.product')
	**/
	public function insert_by_settle_id($settle_id = 0,$p_id,$nowtime,$settle_type=1){
		$p_id=explode(",",$p_id);
		if(count($p_id)>1){
			$datas=array();
			foreach($p_id as $k=>$v){
				if($v){
					$data=array();
					$data['procure_settle_id']=$settle_id;
					$data['product_id']=$v;
					$data['settle_time']=$nowtime;
					$data['type']=$settle_type;
				}
				$datas[]=$data;
			}
			$info=$this->addAll($datas);
		}else{
			$data=array();
			$data['procure_settle_id']=$settle_id;
			$data['product_id']=$p_id;
			$data['settle_time']=$nowtime;
			$data['type']=$settle_type;
			$info=$this->insert($data);
		}
		return $info;

	}

}