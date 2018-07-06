<?php

namespace Business\Model;

use Business\Model\BCommonModel;

class BCompanyAccountModel extends BCommonModel {

	// 上锁/解锁
	public function toggleStatus($type = 'lock'){
		$info = array('status'=> 1, 'msg'=> '');

		$supplier_id = I('get.supplier_id/d', 0);

		$where = array(
			'company_id'=>  get_company_id(),
			'supplier_id'=> $supplier_id
		);

		$count = $this->countList($where);

		if($count > 0){
			switch (strtolower($type)) {
				case 'unlock': 
					$update_data = array(
						'status'=> 1
					);
				break;
				default:
					$update_data = array(
						'status'=> 0
					);
				break;
			}

			$result = $this->update($where, $update_data);
			if($result === false){
				$info['status'] = 0;
				$info['msg'] = '操作错误！';
	        }
		}else{
			$info['status'] = 0;
			$info['msg'] = '数据错误！';
		}

		return $info;
	}

	// 获取对应该供应商id的资金账户 - post.supplier_id
	public function get_price_and_weight_by_supplier(&$company_acount_where = NULL){
		$supplier_id = I('post.supplier_id/d', 0);

		$where = array(
			'company_id'=> get_company_id(),
			'supplier_id'=> $supplier_id,
			'deleted'=> 0,
		);

		$field = '*';

		$info = $this->getInfo($where, $field);

		$company_acount_where = $where;

		return $info;
	}

}