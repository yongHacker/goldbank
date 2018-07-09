<?php

namespace Business\Model;

use Business\Model\BCommonModel;

class BCompanyAccountFlowModel extends BCommonModel {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 添加一行商户结欠账户流水记录
	 * @param integer $account_id 结欠账户id
	 * @param integer $sn_id      关联其他表的id
	 * @param float   $weight     克重
	 * @param float   $price      金额
	 * @param integer $type       类型 1-采购 2-来往钱 3-来往料 4-买卖料 5-采购退货
	 * @param integer $sn_type    单据类型 1-采购 1-结算 2-采购退货
	 */
	public function add_flow($account_id = 0, $sn_id = 0, $weight = 0.00, $price = 0.00, $type = 0, $sn_type = 0){
		$rs = false;

		$where = array(
			'id'=> $account_id,
			'company_id'=> get_company_id()
		);
		$ca_info = D('Business/BCompanyAccount')->getInfo($where);

		if(!empty($ca_info)){
			$insert_data = array(
				'sn_id' => $sn_id,
				'company_id' => get_company_id(),
				'account_id' => $account_id,
				'weight' => $weight,
				'price' => $price,
				'type' => $type,
				'sn_type' => $sn_type,
				'create_time' => time()
			);

			$rs = $this->insert($insert_data);
		}

		return $rs;
	}

}