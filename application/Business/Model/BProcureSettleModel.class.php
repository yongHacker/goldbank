<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BProcureSettleModel extends BCommonModel {
    
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//提交
    const REVOKE              =  4;//撤销
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝
    const UPLOAD_IMG          =  7;//上传凭证
	const CHECK_REJECT        =  8;//提交驳回
    
    /*操作状态名*/
    const CREATE_NAME         =  '创建表单';
    const SAVE_NAME           =  '保存表单';
    const COMMIT_NAME         =  '提交表单';
    const REVOKE_NMAE         =  '撤销表单';
    const CHECK_PASS_NMAE     =  '审批通过';
    const CHECK_DENY_NAME     =  '审批拒绝';
    const UPLOAD_IMG_NAME     =  '上传凭证';
	const CHECK_REJECT_NAME   =  '提交驳回';
    
    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const UPLOAD_PROKEY       =  3;//上传凭证流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';
    const UPLOAD_PROCESS      =  '上传凭证';
    
    /*操作函数名*/
    const CHECK_FUNCTION      =  'business/bsettlement/check';
    const UPLOAD_FUNCTION     =  'business/bsettlement/upload_pic';
    
    public function __construct() {
        parent::__construct();
    }
	// 结算单详情 - get.id 
	public function get_info(){
		$settle_id = I('get.id/d', 0);

		// 获取结算记录
		$where = array('id'=> $settle_id);

		$settle_info = $this->getInfo($where);
		
		// 来往料 来往钱 买卖料 查询条件
		$condition_common = array(
			'deleted' => 0,
			'sn_id' => $settle_info['id'],
			'type' => 1
		);

		// 去料记录表
		$material_record = D('BMaterialRecord')->getInfo(array_merge($condition_common, array('weight' => array('lt', 0))));

		$where = array(
			'mrd.mr_id'=> $material_record['id'],
			'mrd.deleted'=> 0
		);

		$field = 'mrd.mr_id, mrd.product_mid, rp.*, (CASE rp.type 
			WHEN "0" THEN "回购"
			WHEN "1" THEN "销售截金"
			WHEN "2" THEN "采购金料"
			WHEN "3" THEN "结算来料"
			ELSE "-" END
		)as show_source';
		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_recovery_product as rp ON (rp.id = mrd.product_mid)';

		$material_record['mproduct_list'] = D('BMaterialRecordDetail')->alias('mrd')->getList($where, $field, null, $join);

		$settle_info['material_record'] = $material_record;

		// 来料记录表
		$material_record_2 = D('BMaterialRecord')->getInfo(array_merge($condition_common, array('weight' => array('gt', 0))));
		$where = array(
			'mrd.mr_id'=> $material_record_2['id'],
			'mrd.deleted'=> 0
		);
		// 通过金料的 product_id 获取从哪个id金料截下的 金料编号
		$field .= ', frp.rproduct_code as f_rproduct_code';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_recovery_product as frp ON (frp.id = rp.product_id)';
		$material_record_2['mproduct_list'] = D('BMaterialRecordDetail')->alias('mrd')->getList($where, $field, null, $join);

		$settle_info['material_record_2'] = $material_record_2;

		// 买卖金记录表
		$settle_info['material_order'] = D('BMaterialOrder')->getInfo($condition_common);
		// 来往钱记录表
		$settle_info['caccount_record'] = D('BCaccountRecord')->getInfo($condition_common);

		$procure_tbl = $this->get_settle_model($settle_info['type']);

		// 增加批发数据（价格和克重）获取判断，
		if($settle_info['type'] == 2) {
			foreach ($settle_info['procure_list'] as $k => $val) {
				//判断计价计重pricemode=1计重
				if ($val['pricemode'] == 1) {
					$goods_stock = D('BWprocureDetail')->where(array('procure_id' => $val['id'], 'deleted' => 0))->sum("goods_stock");
					$settle_info['procure_list'][$k]['weight'] = $goods_stock;
					$settle_info['procure_list'][$k]['price'] = 0;
				}elseif($val['pricemode'] == 3){

				} else {
					$settle_info['procure_list'][$k]['weight'] = 0;
				}
			}
		}

		$where = array(
			's.id'=> $settle_info['supplier_id'],
			'ca.company_id'=> get_company_id()
		);	
		$field = 's.*, ca.*';
		$join = 'LEFT JOIN '.C('DB_PREFIX').'b_company_account as ca ON (ca.supplier_id = s.id)';

		$settle_info['supplier_info'] = D('BSupplier')->alias('s')->getInfo($where, $field, $join);

		return $settle_info;
	}

	// 审核结算单
	public function check_settle_record()
	{
		$info = array('status' => 0, 'msg' => '审核结算失败！');

		$postdata = I('post.');

		if (IS_POST && count($postdata) > 0) {
			$settle_id = $postdata['id'];
			$settle_status = (INT)$postdata['type'];
			//审核驳回，审核通过，审核不通过操作才可以继续运行
			if (!in_array($settle_status, array(-2, 1, 2))) {//审核驳回，审核通过，审核不通过操作才可以继续运行
				$info['msg'] = '操作参数异常！';
				return $info;
			}
			//判断待审核的该结算单是否存在
			$settle_where = array('id' => $settle_id, 'status' => 0, 'company_id' => get_company_id());
			$settle_info = $this->getInfo($settle_where);
			if(empty($settle_info)){//判断待审核的该结算单是否存在
				$info['msg'] = '结算单状态异常！';
				return $info;
			}
			// 来往料 来往钱 买卖料 查询条件
			$condition_common = array(
				'sn_id' => $settle_id,
				'deleted' => 0,
				'type' => 1
			);
			//判断供应商结欠账户是否存在
			$where = array(
				'company_id'=> get_company_id(),
				'supplier_id' => $settle_info['supplier_id'],
				'deleted'=> 0
			);
			$ca_info = D('Business/BCompanyAccount')->getInfo($where);
			if(empty($ca_info)){
				$info['msg'] = '供应商结欠账户异常！';
				return $info;
			}
			$settle_weight = $ca_info['settle_weight'];//供应商结欠账户已结算总克重
			$settle_price = $ca_info['settle_price'];//供应商结欠账户已结算总金额
			$price = $ca_info['price'];//供应商结欠账户欠款
			$weight = $ca_info['weight'];//供应商结欠账户欠黄金克重

			M()->startTrans();
			// 更改审核状态 pass 通过审核，unpass 审核不通过
			$update_data = array(
				'status' => $settle_status,
				'check_id' => get_user_id(),
				'check_time' => time(),
				'check_memo' => $postdata['check_memo']
			);
			$rs = $this->update($settle_where, $update_data);//更新采购结算单信息
			// 审核通过
			if ($settle_status == 1) {
				if($rs!==false){
				    /*添加表单操作记录 add by lzy 2018.5.28 start*/
				    $rs=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE,$settle_id,self::CHECK_PASS);
				    /*添加表单操作记录 add by lzy 2018.5.28 end*/
				}
				// 更新来往钱记录，并添加结欠账户流水
				if($rs !== false){
					$rs=$this->update_BCaccountRecord($ca_info,$condition_common,$price);//&$price 引用变量，改变变量的值
				}
				// 更新来往料记录，并添加结欠账户流水
				if($rs !== false){
					$rs=$this->update_BMaterialRecord($condition_common,$weight,$ca_info,$rs);//&$weight 引用变量，改变变量的值
				}
				// 更新买卖料记录，并添加结欠账户流水
				if($rs !== false){
					$rs=$this->update_BMaterialOrder($condition_common,$weight,$price,$ca_info);//&$price  &$weight 引用变量，改变变量的值
				}
				//更新供应商结欠账户
				if($rs !== false){
					$where = array(
						'id'=> $ca_info['id']
					);
					$update_data = array(
						'price' => decimalsformat($price, 2),
						'weight' => decimalsformat($weight, 2),
						'settle_weight' => decimalsformat($settle_weight, 2),
						'settle_price' => decimalsformat($settle_price, 2),
					);
					$rs = D('BCompanyAccount')->update($where, $update_data);
				}
				//更新结算单的还款还料前后的数据
				if($rs !== false){
					$update_data = array(
						'before_weight'=> $ca_info['weight'],
						'before_price'=> $ca_info['price'],
						'after_weight'=> decimalsformat($weight, 2),
						'after_price'=> decimalsformat($price, 2),
					);
					$where = array(
						'id'=> $settle_id
					);
					$rs = $this->update($where, $update_data);
				}
			} else {// 审核不通过,审核驳回
				if($rs!==false){
				    /*添加表单操作记录 add by lzy 2018.5.28 start*/
					$operate = $settle_status == 2 ? self::CHECK_DENY : self::CHECK_REJECT;
				    $rs = D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE, $settle_id, $operate);
				    /*添加表单操作记录 add by lzy 2018.5.28 end*/
				}
				if($rs !== false){
					// 更新来往钱记录
					$ca_record_info = D('BCaccountRecord')->getInfo($condition_common);
					if(!empty($ca_record_info)){
						$where = array('id' => $ca_record_info['id']);
						$update_data = array('status' => $settle_status);
						$rs = D("BCaccountRecord")->update($where, $update_data);
					}
				}
				if($rs !== false){
					// 更新买卖料
					$morder_info = D('BMaterialOrder')->getInfo($condition_common);
					if(!empty($morder_info)){
						$where = array('id' => $morder_info['id']);
						$update_data = array('status' => $settle_status);
						$rs = D('BMaterialOrder')->update($where, $update_data);
					}
				}
				// 更新来往料记录
				$mrecord_list = D('BMaterialRecord')->getList($condition_common);
				// 去料结算的变回在库状态
				if(!empty($mrecord_list)){
					foreach ($mrecord_list as $key => $value) {
						if ($rs !== false) {
							$where = array('id' => $value['id']);
							$update_data = array('status' => $settle_status);
							$rs = D('BMaterialRecord')->update($where, $update_data);
							if($rs !== false) {
								//来料去料的记录只能通过weight的正负判断，因此下面虽然有相同内容但不可共用
								if ($value['weight'] > 0) {
									// 来料
									$where = array('mr_id' => $value['id']);
									$sub_sql = D('BMaterialRecordDetail')->where($where)->field('product_mid')->select(false);
									$where = array(
										'company_id' => get_company_id(),
										'id'=> array('exp', 'IN ('. $sub_sql .')')
									);
									$update_data =$settle_status==2? array('status' => 0): array('status' => 1);
									$rs = D('BRecoveryProduct')->update($where, $update_data);//结算单审核不通过，更新来料的结算金料为未入库状态,结算单驳回，更新来料的结算金料为入库中
								} else {
									// 去料
									$where = array('mr_id' => $value['id']);
									$sub_sql = D('BMaterialRecordDetail')->where($where)->field('product_mid')->select(false);
									$where = array(
										'company_id' => get_company_id(),
										'id'=> array('exp', 'IN ('. $sub_sql .')')
									);
									$update_data = array('status' => 2);
									$rs = D('BRecoveryProduct')->update($where, $update_data);//结算单审核不通过，更新去料的结算中金料为在库状态
								}
							}
						}
					}
				}
			}
			if ($rs !== false) {
				M()->commit();

				$info['status'] = 1;
				$info['msg'] = '';
			} else {
				M()->rollback();
			}
		}

		return $info;
	}

	// 撤销结算单
	public function check_cancel()
	{
		$info = array('status' => 0, 'msg' => '审核撤销失败！');

		$settle_id = I('get.id/d', 0);

		// 来往料 来往钱 买卖料 查询条件
		$condition_common = array(
			'sn_id' => $settle_id,
			'deleted' => 0,
			'type' => 1,
			'status' => 0
		);
		if ($settle_id) {
			$settle_info = $this->getInfo(array('id' => $settle_id, 'status' => 0, 'company_id' => get_company_id()));
			if(empty($settle_info)){
				return $info;
			}
			$settle_where = array('id' => $settle_id);
			$settle_status = 3;

			$update_data = array(
				'status' => $settle_status,
			);
			$rs = $this->update($settle_where, $update_data);

			/*添加表单操作记录 add by lzy 2018.5.28 start*/
			$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE, $settle_id, self::REVOKE);
			/*添加表单操作记录 add by lzy 2018.5.28 end*/

			$bmaterialrecord_model = D('BMaterialRecord');
			$bmaterialrecorddetail_model = D('BMaterialRecordDetail');
			$brecoveryproduct_model = D('BRecoveryProduct');
			$bmaterialorder_model = D('BMaterialOrder');
			$bcaccountrecord_model = D('BCaccountRecord');
			if($rs !== false && $record_result) {
				// 来往料记录
				$mrecord_list = $bmaterialrecord_model->getList($condition_common);
				
				if (!empty($mrecord_list)) {
				    foreach ($mrecord_list as $key => $value) {
				        if ($value['weight'] > 0) {
                            // 来料
                            $update_data = array('status' => 3);
                            $where = array('id' => $value['id']);
                            $rs = $bmaterialrecord_model->update($where, $update_data);
                            if ($rs === false) {
                                $info['msg'] = 'update MR2';
                                return $info;
                            }
                            
                            if ($rs !== false) {
                                $where = array(
                                    'company_id' => get_company_id(),
                                    'mr_id' => $value['id']
                                );
                                $p_ids = $bmaterialrecorddetail_model->where($where)->field('product_mid')->select(false);
                                
                                $where = array(
                                    'company_id' => get_company_id(),
                                    'id' => array('exp', 'IN (' . $p_ids . ')')
                                );
                                $update_data = array('status' => 0);
                                $rs = $brecoveryproduct_model->update($where, $update_data);//撤销结算单，来料变为未入库
                                
                                if ($rs === false) {
                                    $info['msg'] = 'update rproduct status3';
                                    return $info;
                                }
                            }
                        } else {
                            // 去料
                            $update_data = array('status' => 3);
                            $where = array('id' => $value['id']);
                            $rs = $bmaterialrecord_model->update($where, $update_data);
                            if ($rs === false) {
                                $info['msg'] = 'update MR';
                                return $info;
                            }
                            
                            if ($rs !== false) {
                                $where = array(
                                    'company_id' => get_company_id(),
                                    'mr_id' => $value['id']
                                );
                                $p_ids = $bmaterialrecorddetail_model->where($where)->field('product_mid')->select(false);
                                
                                $where = array(
                                    'company_id' => get_company_id(),
                                    'id' => array('exp', 'IN (' . $p_ids . ')')
                                );
                                $update_data = array('status' => 2);
                                $rs = $brecoveryproduct_model->update($where, $update_data);//撤销结算单，去料变为在库
                                
                                if ($rs === false) {
                                    $info['msg'] = 'update product';
                                    return $info;
                                }
                            }
                        }
				    }
				}
			} else {
				$info['msg'] = 'update settlement';
				return $info;
			}

			if ($rs !== false) {
                // 买卖料
                $morder_info = $bmaterialorder_model->getInfo($condition_common);
                if (! empty($morder_info)) {
                    $where = array('id' => $morder_info['id']);
                    $update_data = array('status' => 3);
                    
                    $rs = $bmaterialorder_model->update($where, $update_data);
                    if ($rs === false) {
                        $info['msg'] = 'update MO';
                        return $info;
                    }
                }
            }

			if ($rs !== false) {
                // 来往钱记录
                $ca_record_info = $bcaccountrecord_model->getInfo($condition_common);
                if (! empty($ca_record_info)) {
                    $where = array('id' => $ca_record_info['id']);
                    $update_data = array('status' => 3);
                    
                    $rs = $bcaccountrecord_model->update($where, $update_data);
                    if ($rs === false) {
                        $info['msg'] = 'update CR';
                        return $info;
                    }
                }
            }

			if ($rs !== false) {
				M()->commit();

				$info['status'] = 1;
				$info['msg'] = '';
			} else {
				M()->rollback();
			}
		}

		return $info;
	}

	// 修改结算记录 - 保存转提交状态流程
	public function edit_settle_record(){
		$info = array('status' => 0, 'msg' => '提交失败！');

		$postdata = I('post.');
		
		$settle_id = I('settle_id/d', 0);

		$settle_info = D('BProcureSettle')->getInfo('id = '. $settle_id);

		if(empty($settle_info)){
			return $info;
		}

		$supplier_id = $settle_info['supplier_id'];

		// 买料克重
		$material_weight = decimalsformat(floatval($postdata['material_weight']), 2);
		// 买料金价
		$material_g_price = decimalsformat(floatval($postdata['material_g_price']), 2);
		// 卖料克重
		$sell_weight = decimalsformat(floatval($postdata['sell_weight']), 2);
		// 卖料金价
		$sell_price = decimalsformat(floatval($postdata['sell_price']), 2);
		// 来往料总重
		$mrecord_weight = decimalsformat(floatval($postdata['mrecord_weight']), 2);
		// 结算金额
		$price = decimalsformat(floatval($postdata['price']), 2);
		// 抹零金额
		$extra_price = decimalsformat(floatval($postdata['extra_price']), 2);

		$type = $postdata['type'];
		$p_id = $postdata['p_id'];

		$where = array(
			'company_id'=> get_company_id(),
			'supplier_id' => $supplier_id,
			'status'=> 1,
			'deleted'=> 0
		);
		$ca_info = D('Business/BCompanyAccount')->getInfo($where);
		if(empty($ca_info)){
			$info['msg'] = '该供应商结欠账户已被锁定！';
			return $info;
		}

		/*change by alam 9:58 2018/5/4 start*/
		// 结算开单限制仅一个未审核单据存在
		if ($postdata['submit_type'] != 'save') {
			$where = array(
				'company_id' => get_company_id(),
				'supplier_id' => $supplier_id,
				'status'=> 0,
				'deleted'=> 0
			);
			$other_settle = $this->getInfo($where);
			if(!empty($other_settle)){
				$info['msg'] = '该供应商存在未审核的结算单！';
				return $info;
			}
		}
		/*change by alam 9:58 2018/5/4 end*/

		$nowtime = time();

		if (IS_POST && count($postdata) > 0 && !empty($ca_info)) {
			$rs = true;
			$bprocuresettle_model = D('BProcureSettle');
			$bprocuresettlerelation_model = D('BProcureSettleRelation');
			$bmaterialrecord_model = D('BMaterialRecord');

			// 买料数据，确保数据为正数
			if ($type == "buy" && $material_weight > 0 && $material_g_price > 0) {
				$weight = abs($material_weight);
				$mgold_price = $material_g_price;
				$type = 1;
			}

			// 卖料数据，确保数据为负数
			if ($type == "sell" && $sell_weight > 0 && $sell_price > 0) {
				$weight = bcsub(0, abs($sell_weight), 2);
				$mgold_price = $sell_price;
				$type = 2;
			}

			M()->startTrans();

			$morder_id = 0;
			$bmaterialorder_model = D('BMaterialOrder');

			// 来往料 来往钱 买卖料 查询条件
			$condition_common = array(
				'deleted' => 0,
				'sn_id' => $settle_info['id'],
				'type' => 1
			);
			// 来往钱记录
			$ca_record_info = D('BCaccountRecord')->getInfo($condition_common);
			$settle_info['ca_record_id'] = empty($ca_record_info['id']) ? 0 : $ca_record_info['id'];
			// 去料记录
			$mrecord_list = D('BMaterialRecord')->getInfo(array($condition_common, array('weight' => array('lt', 0))));
			$settle_info['mrecord_id'] = empty($mrecord_list['id']) ? 0 : $mrecord_list['id'];
			// 来料记录
			$mrecord_list = D('BMaterialRecord')->getInfo(array($condition_common, array('weight' => array('gt', 0))));
			$settle_info['mrecord_id_2'] = empty($mrecord_list['id']) ? 0 : $mrecord_list['id'];
			// 买卖料
			$morder_info = D('BMaterialOrder')->getInfo($condition_common);
			$settle_info['morder_id'] = empty($morder_info['id']) ? 0 : $morder_info['id'];

			// 更新material_order 数据
			if ($type == 1 || $type == 2) {
				// 首次提交有买卖料则更新数据
				if($settle_info['morder_id'] > 0){
					$where = array(
						'id'=> $settle_info['morder_id']
					);

					$update_data = array(
						'weight' => $weight,
						'mgold_price' => $mgold_price
					);
					$rs = $bmaterialorder_model->update($where, $update_data);
				}else{
					// 首次提交没有买卖料、则新增数据
					$insert_m_record_data = array(
						'company_id' => get_company_id(),
						'account_id'=> $ca_info['id'],
						'weight' => $weight,
						'mgold_price' => $mgold_price,
						'creator_id' => get_user_id(),
						'status' => 0,
						'create_time' => $nowtime
					);

					$rs = $bmaterialorder_model->insert($insert_m_record_data);
					$new_morder_id = $rs;
				}
			}else{
				// 没有买卖料、清空数据、避免结算审核通过计算错误
				if($settle_info['morder_id'] > 0){
					$where = array(
						'id'=> $settle_info['morder_id']
					);
					$update_data = array(
						'weight' => 0,
						'mgold_price' => 0
					);
					$rs = $bmaterialorder_model->update($where, $update_data);
				}
			}

			if($rs === false){
				M()->rollback();

				$info['msg'] = ' BMaterialOrder update';
				return $info;
			}

			// 去料记录 - 金料货品ids
			$pid_arr = array_filter(explode(',', $p_id));
			if(!empty($pid_arr) && count($pid_arr) > 0){

				/* 
					1 计算数据库传过来的金料id总金重
					2 对比传过来的去料总重是否一致
					3 来往料表更新去料数据
					4 添加来往料表与新关联金料id信息
				*/
				
				//增加判断数据是否为空，是则金料克重为0，in内容为空会保存
				$where = array(
					'deleted'=> 0,
					'company_id'=> get_company_id(),
					'id'=> array('in', $pid_arr)
				);
				$mrecord_list = D('BRecoveryProduct')->where($where)->select();

				$mrecord_weight_2 = 0;
				foreach ($mrecord_list as $key => $value) {
						if ((INT)$value['status']!=2) {
							$info['msg'] = '存在非在库状态的去料:'.$value['rproduct_code'].'，请删除！';
							return $info;
						}
						$mrecord_weight_2 = bcadd($mrecord_weight_2, $value['gold_weight'], 2);
				}
				// 对比 传过来的来往料总和 与 货品id统计的d_weight总和
				/*if(bcsub($mrecord_weight, $mrecord_weight_2, 2) != 0){
					$rs = false;
					M()->rollback();

					$info['msg'] = '去料信息总克重与往期来料信息克重不对应！';
					return $info;
				}else{*/

					$where = array(
						'company_id'=> get_company_id(),
						'mr_id'=> $settle_info['mrecord_id']
					);
					// 每次保存，清掉旧的货品与来往料表关系，不是提交操作不会改变金料的状态
					$rs = D('BMaterialRecordDetail')->where($where)->delete();
					if($rs === false){
						M()->rollback();
						return $info;
					}

					// 该结算单有记录来往料 - update
					if($settle_info['mrecord_id'] > 0){
						$where = array(
							'id'=> $settle_info['mrecord_id'],
							'company_id' => get_company_id(),
							'account_id'=> $ca_info['id'],
						);
						$mr_update_data = array(
							'weight'=> bcsub(0, abs($mrecord_weight), 2),
							'memo'=> '更新去料 '.abs($mrecord_weight).'g'
						);
						$rs = $bmaterialrecord_model->update($where, $mr_update_data);
						if($rs === false){
							M()->rollback();

							$info['msg'] = 'BMaterialRecord update faild';
							return $info;
						}
					}

					// 首次没有来往料记录 - insert
					if($settle_info['mrecord_id'] == 0){
						$mr_data = array(
							'company_id' => get_company_id(),
							'account_id'=> $ca_info['id'],
							'weight'=> bcsub(0, abs($mrecord_weight), 2),
							'memo'=> '去料 '.abs($mrecord_weight).'g',
							'creator_id'=> get_user_id(),
							'create_time'=> $nowtime
						);
						$rs = $bmaterialrecord_model->insert($mr_data);
						if($rs === false){
							M()->rollback();

							$info['msg'] = 'BMaterialRecord insert faild';
							return $info;
						}

						$new_mrecord_id = $rs;
					}

					if($rs !== false){
						foreach ($pid_arr as $p_id) {
							$insert_data = array(
								'company_id'=> get_company_id(),
								'mr_id'=> isset($new_mrecord_id) ? $new_mrecord_id : $settle_info['mrecord_id'],
								'product_mid'=> $p_id
							);

							$rs = D('BMaterialRecordDetail')->insert($insert_data);
							if($rs === false){
								break;
							}
						}
					}else{
						$info['msg'] = ' BMaterialRecord';
						return $info;
					}
				/*}*/
			}else{
				// 该结算单以前有来往料记录，更新没有来往料数据，则清空
				if($settle_info['mrecord_id'] !== 0){
					$where = array(
						'id'=> $settle_info['mrecord_id']
					);
					$update_data = array('weight' => 0, 'memo' => '保存没有来往料记录，清0');
					$rs = $bmaterialrecord_model->update($where, $update_data);
					if($rs === false){
						return $info;
					}

					$where = array(
						'company_id'=> get_company_id(),
						'mr_id'=> $settle_info['mrecord_id']
					);
					$rs = D('BMaterialRecordDetail')->where($where)->delete();

					if($rs === false){
						return $info;
					}
				}
			}

			// 来料记录
			$rproduct_list = $postdata['rproduct_data'];
			if(!empty($rproduct_list)){
				/* 
					1 新增来料数据
					2 添加旧金数据，计算总重
					3 更新来料记录的总重
				*/

				$where = array(
					'company_id'=> get_company_id(),
					'mr_id'=> $settle_info['mrecord_id_2']
				);
				// 每次更改，清掉旧的货品与来往料表关系
				$rs = D('BMaterialRecordDetail')->where($where)->delete();
				if($rs === false){
					M()->rollback();
					return $info;
				}

				if($settle_info['mrecord_id_2'] == 0){
					$mr_data = array(
						'company_id' => get_company_id(),
						'account_id'=> $ca_info['id'],
						'weight'=> 0,
						'creator_id'=> get_user_id(),
						'create_time'=> $nowtime
					);
					$rs = $bmaterialrecord_model->insert($mr_data);
					$mr_id_2 = $rs;

					$new_mrecord_id_2 = $mr_id_2;
				}else{
					$rs = true;
					$mr_id_2 = $settle_info['mrecord_id_2'];
				}

				if($rs !== false){
					$recovery_gold_weight = 0;
					
					$new_rproduct_ids = array();
					$gold_price=D('BOptions')->get_current_gold_price();
					foreach($rproduct_list as $rproduct){

						if($rproduct['gold_weight'] > $rproduct['total_weight']){
							$info['msg'] = '来料金重大于总重！';
							return $info;
						}

						$recovery_gold_weight = bcadd($recovery_gold_weight, $rproduct['gold_weight'], 3);

						if(!empty($rproduct['from_rproduct_code'])){
							$where_f_rprodcut = array('rproduct_code'=> trim($rproduct['from_rproduct_code']),'company_id'=>get_company_id(),'deleted'=>0,'status'=>array('neq',0));
							$from_rproduct_info = D('BRecoveryProduct')->getInfo($where_f_rprodcut, 'id, rproduct_code');
						}

						$cost_price = $rproduct['recovery_price'] * $rproduct['gold_weight'];

						$insert_data = array(
							'company_id'=> get_company_id(),
							'wh_id'=>get_current_warehouse_id(),
							'order_id'=> $mr_id_2,
							'rproduct_code'=> D('BRecoveryProduct')->get_rproduct_code(),//b_order_number('b_recovery_product', 'rproduct_code'),
							'recovery_name'=> $rproduct['recovery_name'],
							'recovery_price'=> $rproduct['recovery_price'],
							'gold_price'=> $gold_price,//$rproduct['gold_price'],
							'gold_weight'=> $rproduct['gold_weight'],
							'total_weight'=> $rproduct['total_weight'],
							'cost_price'=> decimalsformat($cost_price, 2),
							'purity'=> $rproduct['purity'],
							'type'=> 3,
							'status'=> 1
						);
						if(!empty($from_rproduct_info)){
							$insert_data['product_id'] = $from_rproduct_info['id'];
							//判断来自金料的旧金料是否存在去料数据中
							if(!in_array($insert_data['product_id'],$pid_arr)){
								$info['msg'] = '来自金料编码必须是选择的去料中的编码或新的编码';
								return $info;
							}
						}
						if($rproduct['id'] > 0){
							$update_data = $insert_data;
							
							unset($update_data['rproduct_code']);

							$rs = D('BRecoveryProduct')->update(array('id'=> $rproduct['id']), $update_data);//编辑结算，更新已经添加的来料数据
						}else{
							$insert_data['create_time']=time();
							$rs = D('BRecoveryProduct')->insert($insert_data);//编辑结算，添加新增的来料数据
						}
						if($rs !== false){
							$rp_id = $rproduct['id'] > 0 ? $rproduct['id'] : $rs;

							$new_rproduct_ids[] = $rp_id;

							$insert_data = array(
								'company_id'=> get_company_id(),
								'mr_id'=> $mr_id_2,
								'product_mid'=> $rp_id
							);

							$rs = D('BMaterialRecordDetail')->insert($insert_data);
							if($rs === false){
								$info['msg']='来料记录添加失败';
								return $info;
							}
						}else{
							$info['msg']='来料金料记录添加失败';
							return $info;
						}
					}
				}

				if($rs !== false){
					if(empty($new_rproduct_ids)){
						$rs=true;
					}else{
						$where = array(
							'order_id'=> $mr_id_2,
							'id'=> array('not in', $new_rproduct_ids),
							'type'=>3
						);
						$update_data = array('deleted'=> 1);
						$rs = D('BRecoveryProduct')->update($where, $update_data);//编辑结算单，删除结算单中删除的来料
					}

				}

				if($rs !== false){
					$where = array('id'=> $mr_id_2);
					$update_data = array(
						'weight'=> $recovery_gold_weight,
						'memo'=> '更新来料 '.$recovery_gold_weight.'g'
					);
					$rs = $bmaterialrecord_model->update($where, $update_data);
				}
			}else{
				// 原来有数据、新提交没数据，把旧数据删掉
				if($settle_info['mrecord_id_2'] !== 0){
					/*
						1 更新来料记录id关联的来往料记录，清0
						2 查出所有关联就来料的id，清除与来料记录关系
						3 删除来料的金料数据
					*/
					$where = array(
						'id'=> $settle_info['mrecord_id_2']
					);
					$update_data = array('weight'=> 0, 'memo'=> '保存没有来料记录，清0');
					$rs = $bmaterialrecord_model->update($where, $update_data);
					if($rs === false){
						return $info;
					}

					$where = array(
						'company_id'=> get_company_id(),
						'mr_id'=> $settle_info['mrecord_id_2']
					);
					$product_mid_s = D('BMaterialRecordDetail')->where($where)->field('group_concat(product_mid) as pids')->find();
					$rs = D('BMaterialRecordDetail')->where($where)->delete();
					if($rs === false){
						return $info;
					}
					if(empty($product_mid_s['pids'])){
						$rs=true;
					}else{
						$where = array(
							'id'=> array('in', $product_mid_s['pids'])
						);
						$update_data = array('deleted'=> 1);
						$rs = D('BRecoveryProduct')->update($where, $update_data);//删除结算单中的所有来料数据
					}
					if($rs === false){
						return $info;
					}
				}
			}

			// 来往钱记录操作 - 更新/新增
			if($rs !== false){
				if($price){
					$bcaccountrecord_model = D('b_caccount_record');

					// 首次保存有结算金额时，更新来往钱记录的金额
					if($settle_info['ca_record_id'] > 0){
						$where = array('id'=> $settle_info['ca_record_id']);

						$c_a_r_data = array('price'=> $price,'extra_price'=> $extra_price);

						$rs = $bcaccountrecord_model->update($where, $c_a_r_data);
					}else{
						// 首次保存没有结算金额时，新增来往钱记录
						$c_a_r_data = array(
							'company_id' => get_company_id(),
							'account_id'=> $ca_info['id'],
							'price'=> $price,
							'extra_price'=> $extra_price,
							'creator_id'=> get_user_id(),
							'create_time'=> $nowtime
						);
						$rs = $bcaccountrecord_model->insert($c_a_r_data);
						$new_ca_record_id = $rs;
					}

				}
			}else{
				M()->rollback();
				$info['msg'] = ' caccount_record';
				return $info;
			}

			if ($rs !== false){
				// settle 状态 保存 -1, 提交 0
				$settle_status = ($postdata['submit_type'] == 'save' ? -1 : 0);

				$settle_update_data = array(
					'settle_time' => strtotime($postdata['settle_time']),
					'type'=> 1,
					'memo' => $postdata['memo'],
					'status'=> $settle_status,
					/*change by alam 11:00 2018/5/4 start*/
					'before_weight'=> $postdata['before_weight'],
					'before_price'=> $postdata['before_price'],
					'after_weight'=> $postdata['after_weight'],
					'after_price'=> $postdata['after_price']
					/*change by alam 11:00 2018/5/4 end*/
				);

				$where = array('id'=> $settle_info['id']);

				$rs = $bprocuresettle_model->update($where, $settle_update_data);

				if($settle_status==-1){
				    /*添加表单操作记录 add by lzy 2018.5.28 start*/
				    $rs=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE,$settle_id,self::SAVE);
				    /*添加表单操作记录 add by lzy 2018.5.28 end*/
				}else if($settle_status==0){
				    /*添加表单操作记录 add by lzy 2018.5.28 start*/
				    $rs=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE,$settle_id,self::COMMIT);
				    /*添加表单操作记录 add by lzy 2018.5.28 end*/
				}

				// 采购单填上 settle_id
				if ($rs) {
					// 提交操作时，去料记录的所有金料变更为结算中
					if(!empty($pid_arr) && $settle_update_data['status'] == 0){
						$where = array(
							'company_id'=> get_company_id(),
							'id'=> array('in', $pid_arr),
							'status'=> 2,
							'deleted'=> 0,
						);

						$data['status'] = 4;
						$rs = D("BRecoveryProduct")->update($where, $data);//编辑结算单，更新在库的去料金料为结算中
					}

					/* add by alam 2018/06/11 start */
					// 将买卖料、来往料、来往钱sn_id填入
					if ($rs) {
						$update_1 = $update_2 = $update_3 = $update_4 = true;
						// 首次保存没有去料数据、再保存有去料数据
						if(isset($new_mrecord_id) && $new_mrecord_id !== false){
							$update_3 = $bmaterialrecord_model->update(array('id' => $new_mrecord_id), array('sn_id' => $settle_id));
						}
						// 首次保存没有来料数据、再保存有来料数据
						if(isset($new_mrecord_id_2) && $new_mrecord_id_2 !== false){
							$update_4 = $bmaterialrecord_model->update(array('id' => $new_mrecord_id_2), array('sn_id' => $settle_id));
						}
						// 首次保存没有买卖料数据、再保存有买卖料数据
						if(isset($new_morder_id) && $new_morder_id !== false){
							$update_1 = $bmaterialorder_model->update(array('id' => $new_morder_id), array('sn_id' => $settle_id));
						}
						// 首次保存没有结算金额、再保存有结算金额
						if(isset($new_ca_record_id) && $new_ca_record_id !== false){
							$update_2 = $bcaccountrecord_model->update(array('id' => $new_ca_record_id), array('sn_id' => $settle_id));
						}
						if ($update_1 === false || $update_2 === false || $update_3 === false || $update_4 === false) {
							$rs = false;
						}
					}
					/* add by alam 2018/06/11 end */
				}
			}

			if ($rs !== false) {
				M()->commit();

				$info['status'] = 1;
				$info['msg'] = '';
			} else {
				M()->rollback();
			}

			return $info;
		}

	}

	// 添加结算记录
	/**
	 * @param int $settle_type 结算类型 1零售采购 2批发采购 3批发销售
	 * @return array
	 */
	public function add_settle_record($settle_type = 1)
	{
		$info = array('status' => 0, 'msg' => '添加结算单失败！');

		$postdata = I('post.');

		$supplier_id = I('post.supplier_id/d', 0);

		// 买料克重
		$material_weight = decimalsformat(floatval($postdata['material_weight']), 2);
		// 买料金价
		$material_g_price = decimalsformat(floatval($postdata['material_g_price']), 2);
		// 卖料克重
		$sell_weight = decimalsformat(floatval($postdata['sell_weight']), 2);
		// 卖料金价
		$sell_price = decimalsformat(floatval($postdata['sell_price']), 2);
		// 来往料总重
		$mrecord_weight = decimalsformat(floatval($postdata['mrecord_weight']), 2);
		// $settle_weight = decimalsformat(floatval($postdata['settle_weight']), 2);
		// $settle_price = decimalsformat(floatval($postdata['settle_price']), 2);
		// 结算金额
		$price = decimalsformat(floatval($postdata['price']), 2);
		// 抹零金额
		$extra_price = decimalsformat(floatval($postdata['extra_price']), 2);

		$type = $postdata['type'];
		$p_id = $postdata['p_id'];

		$where = array(
			'company_id'=> get_company_id(),
			'supplier_id' => $supplier_id,
			'status'=> 1,
			'deleted'=> 0
		);
		$ca_info = D('Business/BCompanyAccount')->getInfo($where);//判断供应商结欠账户
		if(empty($ca_info)){
			$info['msg'] = '该供应商结欠账户已被锁定！';
			return $info;
		}

		/*change by alam 9:58 2018/5/4 start*/
		// 结算开单限制仅一个未审核单据存在
		if ($postdata['submit_type'] != 'save') {
			$where = array(
				'company_id' => get_company_id(),
				'supplier_id' => $supplier_id,
				'status'=> 0,
				'deleted'=> 0
			);
			$other_settle = $this->getInfo($where);
			if(!empty($other_settle)){
				$info['msg'] = '该供应商存在未审核的结算单！';
				return $info;
			}
		}
		/*change by alam 11:00 2018/5/4 start*/

		$nowtime = time();

		if (IS_POST && count($postdata) > 0 && !empty($ca_info)) {
			$rs = true;
			$bprocuresettle_model = D('BProcureSettle');
			$bprocuresettlerelation_model = D('BProcureSettleRelation');
			$bmaterialorder_model = D('BMaterialOrder');//买卖料记录
			$bmaterialrecord_model = D('BMaterialRecord');//来往料记录

			// 买料数据，保证数据为正数
			if ($type == "buy" && $material_weight > 0 && $material_g_price > 0) {
				$weight = abs($material_weight);
				$mgold_price = $material_g_price;
				$type = 1;
			}
			// 卖料数据，保证数据为负数
			if ($type == "sell" && $sell_weight > 0 && $sell_price > 0) {
				$weight = bcsub(0, abs($sell_weight), 2);
				$mgold_price = $sell_price;
				$type = 2;
			}

			M()->startTrans();

			$morder_id = 0;
			// 添加买卖料记录 type=1 买料| type=2 卖料
			if ($type == 1 || $type == 2) {
				$insert_m_record_data = array(
					'company_id' => get_company_id(),
					'account_id'=> $ca_info['id'],
					'weight' => $weight,
					'mgold_price' => $mgold_price,
					'creator_id' => get_user_id(),
					'status' => 0,
					'create_time' => $nowtime
				);
				$rs = $bmaterialorder_model->insert($insert_m_record_data);
				$morder_id = $rs;
			}
			if($rs === false){
				M()->rollback();

				$info['msg'] = ' BMaterialOrder error';
				return $info;
			}

			// 去料记录 - 金料货品ids - 有去料数据时操作，否则mrecord_id=0
			$pid_arr = array_filter(explode(',', $p_id));
			if(!empty($pid_arr) && count($pid_arr) > 0){

				/* 
					0 查询是否存在已被使用的旧金
					1 计算数据库传过来的金料id总金重
					2 对比传过来的去料总重是否一致
					3 来往料表添加去料数据
					4 添加来往料表与金料id关联信息
				*/

				// 旧金表 b_recovery_product 
				$where = array(
					'deleted'=> 0,
					'company_id'=> get_company_id(),
					'id'=> array('in', $pid_arr)
				);
				$mrecord_list = D('BRecoveryProduct')->where($where)->select();
				$mrecord_weight_2 = 0;//统计来料总重
				foreach ($mrecord_list as $key => $value) {
					if ((INT)$value['status']!=2) {
						$info['msg'] = '存在非在库状态的去料:'.$value['rproduct_code'].'，请删除！';
						return $info;
					}
					$mrecord_weight_2 = bcadd($mrecord_weight_2, $value['gold_weight'], 2);
				}
				/*if(bcsub($mrecord_weight, $mrecord_weight_2, 2) != 0){
					$rs = false;
					M()->rollback();

					$info['msg'] = '去料信息总克重与往期来料信息克重不对应！';
					return $info;
				}else{*/
					$mr_data = array(
						'company_id' => get_company_id(),
						'account_id' => $ca_info['id'],
						'weight' => bcsub(0, abs($mrecord_weight), 2),
						'memo' => '去料 '.abs($mrecord_weight).'g',
						'creator_id' => get_user_id(),
						'create_time' => $nowtime
					);
					$rs = $bmaterialrecord_model->insert($mr_data);
					$mr_id = $rs;

					if($rs){
						foreach ($pid_arr as $p_id) {
							$insert_data = array(
								'company_id'=>get_company_id(),
								'mr_id'=> $mr_id,
								'product_mid'=> $p_id
							);

							$rs = D('BMaterialRecordDetail')->insert($insert_data);
							if($rs === false){
								break;
							}
						}
					}else{
						M()->rollback();

						$info['msg'] = ' BMaterialRecord 去料';
						return $info;
					}
				/*}*/
			}

			// 来料记录 - 有来料数据时操作，否则 mrecord_id_2=0
			$rproduct_list = $postdata['rproduct_data'];
			if(!empty($rproduct_list)){
				/* 
					1 新增来料数据
					2 添加旧金数据，计算总重
					3 更新来料记录的总重
				*/
				$mr_data = array(
					'company_id' => get_company_id(),
					'account_id'=> $ca_info['id'],
					'weight'=> 0,
					'creator_id'=> get_user_id(),
					'create_time'=> $nowtime
				);
				$rs = $bmaterialrecord_model->insert($mr_data);
				$mr_id_2 = $rs;

				if($rs !== false){
					$recovery_gold_weight = 0;
					$gold_price=D('BOptions')->get_current_gold_price();
					foreach($rproduct_list as $rproduct){

						if($rproduct['gold_weight'] > $rproduct['total_weight']){
							$info['msg'] = '来料金重大于总重！';
							break;
						}

						$recovery_gold_weight = bcadd($recovery_gold_weight, $rproduct['gold_weight'], 3);

						if(!empty($rproduct['from_rproduct_code'])){
							$where_f_rprodcut = array('rproduct_code'=> $rproduct['from_rproduct_code'],'company_id'=>get_company_id(),'deleted'=>0,'status'=>array('neq',0));
							$from_rproduct_info = D('BRecoveryProduct')->getInfo($where_f_rprodcut, 'id, rproduct_code');
						}

						$cost_price = $rproduct['recovery_price'] * $rproduct['gold_weight'];

						$insert_data = array(
							'company_id' => get_company_id(),
							'order_id' => $mr_id_2,
							'wh_id' => get_current_warehouse_id(),
							'rproduct_code' =>D('BRecoveryProduct')->get_rproduct_code(),
							'recovery_name' => $rproduct['recovery_name'],
							'recovery_price' => $rproduct['recovery_price'],
							'gold_price' =>$gold_price,
							'gold_weight' => $rproduct['gold_weight'],
							'total_weight' => $rproduct['total_weight'],
							'cost_price' => decimalsformat($cost_price, 2),
							'purity' => $rproduct['purity'],
							'create_time'=>time(),
							'type' => 3,
							'status' => 1
						);
						if(!empty($from_rproduct_info)){
							$insert_data['product_id'] = $from_rproduct_info['id'];
							//判断来自金料的旧金料是否存在去料数据中
							if(!in_array($insert_data['product_id'],$pid_arr)){
								$info['msg'] = '来自金料编码必须是选择的去料中的编码或新的编码';
								return $info;
							}
						}

						$rs = D('BRecoveryProduct')->insert($insert_data);//添加来料数据
						if($rs !== false){
							$rp_id = $rs;
							$insert_data = array(
								'company_id' => get_company_id(),
								'mr_id' => $mr_id_2,
								'product_mid' => $rp_id
							);

							$rs = D('BMaterialRecordDetail')->insert($insert_data);
							if($rs === false){
								break;
							}
						}else{
							break;
						}
					}
				}

				if($rs !== false){
					$where = array('id'=> $mr_id_2);
					$update_data = array(
						'weight'=> $recovery_gold_weight,
						'memo'=> '来料 '.abs($recovery_gold_weight).'g',
					);
					$rs = $bmaterialrecord_model->update($where, $update_data);
				}
			}

			// 来往钱记录操作
			if($rs !== false){
				if($price){
					$bcaccountrecord_model = D('b_caccount_record');
					$c_a_r_data = array(
						'company_id' => get_company_id(),
						'account_id'=> $ca_info['id'],
						'price'=> $price,
						'extra_price'=> $extra_price,
						'creator_id'=> get_user_id(),
						'create_time'=> $nowtime
					);
					$rs = $bcaccountrecord_model->insert($c_a_r_data);
					$c_a_r_id = $rs;
				}
			}else{
				M()->rollback();
				$info['msg'] = ' caccount_record';
				return $info;
			}

			// 添加 settle 记录
			$batch = b_order_number('BProcureSettle', 'batch');
			if ($rs !== false){
				/*
					1 添加结算记录数据 procure_settle
					2 去料的金料状态改为 4 结算中
				*/

				$settle_status = ($postdata['submit_type'] == 'save' ? -1 : 0);

				$settle_data = array(
					'company_id' => get_company_id(),
					'supplier_id'=> $supplier_id,
					'batch' => $batch,
					'settle_time' => strtotime($postdata['settle_time']),
					'creator_id' => get_user_id(),
					'create_time' => $nowtime,
					'type'=>$settle_type,
					'memo' => $postdata['memo'],
					'status'=> $settle_status,
					/*change by alam 11:00 2018/5/4 start*/
					'before_weight' => $postdata['before_weight'], 
					'before_price' => $postdata['before_price'],
					'after_weight' => $postdata['after_weight'],
					'after_price' => $postdata['after_price']
					/*change by alam 11:00 2018/5/4 end*/
				);

				$rs = $bprocuresettle_model->insert($settle_data);
				if ($rs) {
					$settle_id = $rs;
                    
					/*添加表单操作记录 add by lzy 2018.5.28 start*/
					$rs=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE,$settle_id,self::CREATE);
					/*添加表单操作记录 add by lzy 2018.5.28 end*/
					if($rs&&$settle_status==-1){
					    /*添加表单操作记录 add by lzy 2018.5.28 start*/
					    $rs=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE,$settle_id,self::SAVE);
					    /*添加表单操作记录 add by lzy 2018.5.28 end*/
					}else if($rs&&$settle_status==0){
					    /*添加表单操作记录 add by lzy 2018.5.28 start*/
					    $rs=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE,$settle_id,self::COMMIT);
					    /*添加表单操作记录 add by lzy 2018.5.28 end*/
					}
					
					if($rs){
						if(!empty($pid_arr) && $settle_data['status'] == 0){//提交，并存在去料数据
							$where = array(
								'company_id'=> get_company_id(),
								'id'=> array('in', $pid_arr),
								'status'=> 2,
								'deleted'=> 0,
							);
							$data['status'] = 4;
							$rs = D("BRecoveryProduct")->update($where, $data);//添加结算单，更新在库的去料金料为结算中
						}
					}
					/* add by alam 2018/06/11 start */
					// 将买卖料、来往料、来往钱sn_id填入
					if ($rs) {
						$update_1 = $update_2 = $update_3 = $update_4 = true;
						// 买卖料
						if (!empty($morder_id)) {
							$update_1 = $bmaterialorder_model->update(array('id' => $morder_id), array('sn_id' => $settle_id));//买卖料
						}
						if (!empty($c_a_r_id)) {
							$update_2 = $bcaccountrecord_model->update(array('id' => $c_a_r_id), array('sn_id' => $settle_id));//来往钱
						}
						if (!empty($mr_id)) {
							$update_3 = $bmaterialrecord_model->update(array('id' => $mr_id), array('sn_id' => $settle_id));//去料
						}
						if (!empty($mr_id_2)) {
							$update_4 = $bmaterialrecord_model->update(array('id' => $mr_id_2), array('sn_id' => $settle_id));//来料
						}
						if ($update_1 === false || $update_2 === false || $update_3 === false || $update_4 === false) {
							$info['msg'] = '买卖料，来往钱或来往料更新外部单号错误！';
							$rs = false;
						}
					}
					/* add by alam 2018/06/11 end */
				}
			}
			if ($rs !== false) {
				M()->commit();

				$info['status'] = 1;
				$info['msg'] = '';
			} else {
				M()->rollback();
			}

			return $info;
		}
	}

	// 导出 excel
    public function excel_out($where, $field, $join, $page = 1){

    	$num = 1000;
    	$limit = ($page - 1) * $num .','.$num;

    	$data_list = $this->getList($where, $field, $limit, $join);

        if($data_list){

            register_shutdown_function(array(&$this, 'excel_out'), $where, $field, $join, $page + 1);

            $title_arr = array(
            	'序' => '',
            	'结算单号' => 'batch',
            	'供应商' => 'company_name',
            	'来往钱' => 'ca_r_price',
            	'来往料' => 'mr_weight',
            	'买卖料' => 'mo_weight',
            	'买卖料金价' => 'mgold_price',
            	'结算人员' => 'creator_name',
            	'状态' => 'show_status',
            	'结欠克重' => 'after_weight',
            	'结欠金额' => 'after_price',
            	'结算时间' => 'show_settle_time',
            	'创建时间' => 'show_create_time',
            	'备注' => 'memo'
           	);

            if($page == 1) {
                $content = iconv('utf-8','gbk', implode(',', array_keys($title_arr)));
                $content = $content . "\n";
            }

            foreach ($data_list as $key=>$value){
                $row = array();
                
                $row['id'] = $key+1 + ($page - 1) * $num;

                foreach ($title_arr as $v) {
                	if($v){
                		$row[$v] = iconv('utf-8','gbk',$value[$v]);
                	}
                }

                $content .= implode(",", $row) . "\n";
            }

            header("Content-Disposition: attachment; filename=结算单打印.csv");

            echo $content;
        }        
    }

	function get_settle_model($type=1){
		switch($type){
			case 1:
				$procure_tbl ='b_procurement';
				break;
			case 2:
				$procure_tbl ='b_wholesale_procure';
				break;
			case 3:
				$procure_tbl = 'b_wsell';
				break;
			default:
				$procure_tbl ='b_procurement';
		}
		return $procure_tbl;
	}
	//获取结算审核的付款凭证
	private function get_payment_pic($postdata){
		$upload_img_arr = I('upload_img_arr/s');
		$upload_img_arr = explode('|', $upload_img_arr);

		$remove_img_arr = I('remove_img_arr/s');
		$remove_img_arr = explode('|', $remove_img_arr);

		$upload_img_arr = array_diff($upload_img_arr, $remove_img_arr);

		foreach($remove_img_arr as $img){
			$dir_path = str_replace("http://".$_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT'], $img);
			@unlink($dir_path);
		}

		$payment_pic= implode(',', $upload_img_arr);
		return $payment_pic;
	}
	/**
	 * @author alam
	 * 回溯结算错误上下结欠克重  - 废弃
	 */
	public function weightDataRecall()
	{
		// 需要回溯的结算单据
		$condition = array(
			'status' => array('neq', -1),
			'deleted' => 0
		);
		$settle_list = $this->getList($condition, $field = '*', $limit = null, $join = '', $order = 'create_time asc',$group = '');
		
		$supplier_settle = array();
		foreach ($settle_list as $key => $value) {
			$supplier_settle[$value['supplier_id']][] = $value;
		}

		foreach ($supplier_settle as $key => $value) {
			echo "-------------------------------商户ID（{$value[0]['company_id']}）供应商ID（{$value[0]['supplier_id']}） ------------------------------- <br><br>";
			// 已结清克重
			$clearing_weight = 0;
			for ($i = 0; $i < count($value); $i++) {

				// 审核前所有采购单总克重
				$end_time = empty($value[$i]['check_time']) ? $value[$i]['create_time'] : $value[$i]['check_time'];
				$condition = array(
					'check_time' => array('elt', $end_time),
					'deleted' => 0,
					'status' => 1,
					'conmany_id' => $value[$i]['company_id'],
					'supplier_id' => $value[$i]['supplier_id'],
					'pricemode' => 1
				);
				$field = 'SUM(gold_weight) AS total_weight';
				$procurement_list = D('BProcurement')->getInfo($condition, $field);
				// echo D('BProcurement')->getlastsql();echo '<br>';
				$tatol_weight = $procurement_list['total_weight'];

				// 上结欠
				$before_weight = bcsub($tatol_weight, $clearing_weight, 2);

				// 结算克重
				// 买卖料
				$settle_weight = 0;
				if (!empty($value[$i]['morder_id'])) {
					$material_order = D('BMaterialOrder')->getInfo('id = '. $value[$i]['morder_id']);
					$settle_weight += $material_order['weight'];
				} else {
					$material_order['weight'] = 0;
					$settle_weight = 0;
				}
				// 去料记录表
				$material_record = D('BMaterialRecord')->getInfo('id = '. $value[$i]['mrecord_id']);
				if (!empty($material_record)) {
					$settle_weight = bcsub($settle_weight, $material_record['weight']);
				}
				// 来料记录表
				$material_record_2 = D('BMaterialRecord')->getInfo('id = '. $value[$i]['mrecord_id_2']);
				if (!empty($material_record_2)) {
					$settle_weight = bcsub($settle_weight, $material_record_2['weight']);
				}

				// 下结欠
				$after_weight = bcsub($before_weight, $settle_weight, 2);
				echo "单据ID（{$value[$i]['id']}）审核状态（{$value[$i]['status']}）买卖料（{$material_order['weight']}） 去料（{$material_record['weight']}） 来料（{$material_record_2['weight']}） 结算克重（{$settle_weight}）<br>";
				echo "上结欠（{$before_weight}）[全部总结欠（{$tatol_weight}）- 已结清（{$clearing_weight}）] 单据结算克重（{$settle_weight}） 下结欠({$after_weight})";echo '<br><br>';
				$this->execute("UPDATE `gb_b_procure_settle` SET `before_weight`='{$before_weight}',`after_weight`='{$after_weight}' WHERE `id` = {$value[$i]['id']};");
				
				// 若审核通过 已结清克重+结算克重
				if ($value[$i]['status'] == 1 || $value[$i]['status'] == 4) {
					$clearing_weight = bcadd($clearing_weight, $settle_weight, 2);;
				}
			}
		}
	}
	/**
	 * @author lzy 2018.5.28
	 * @param int $procure_id 采购单id
	 * @return 操作记录列表
	 */
	public function getOperateRecord($procure_id){
	    $condition=array(
	        'operate.company_id'=>get_company_id(),
	        'operate.type'=>BBillOpRecordModel::PROCURE_SETTLE,
	        'operate.sn_id'=>$procure_id,
	        'employee.deleted'=>0,
	        'employee.company_id'=>get_company_id(),
	    );
	    $field="operate.operate_type,operate.operate_time,employee.employee_name";
	    $join="join gb_b_employee employee on employee.user_id=operate.operate_id";
	    $record_list=D('BBillOpRecord')->alias("operate")->getList($condition,$field,'',$join,'operate.id asc');
	    $type_list=$this->_groupType();
	    foreach ($record_list as $key => $val){
	        $record_list[$key]['operate_name']=$type_list[$val['operate_type']];
	    }
	    return $record_list;
	}
	/**
	 * @author lzy 2018.5.28
	 * 将所有的状态码组合起来
	 */
	private function _groupType(){
	    return array(
	        self::CREATE=>self::CREATE_NAME,
	        self::SAVE=>self::SAVE_NAME,
	        self::COMMIT=>self::COMMIT_NAME,
	        self::REVOKE=>self::REVOKE_NMAE,
	        self::CHECK_PASS=>self::CHECK_PASS_NMAE,
	        self::CHECK_DENY=>self::CHECK_DENY_NAME,
	        self::UPLOAD_IMG=>self::UPLOAD_IMG_NAME,
			self::CHECK_REJECT=>self::CHECK_REJECT_NAME,
	    );
	}
	/**
	 * @author lzy 2018.5.28
	 * 获取流程数组
	 */
	public function getProcess($procure_id){
	    $process_list=$this->_groupProcess();
	    if(!empty($procure_id)){
	        $condition=array(
	            'settle.id'=>$procure_id,
	        );
	        $field='settle.*,create_employee.employee_name as creator_name,check_employee.employee_name as check_name,upload_employee.employee_name as upload_name';
	        $join='left join gb_b_employee create_employee on settle.creator_id=create_employee.user_id and create_employee.company_id='.get_company_id();
	        $join.=' left join gb_b_employee check_employee on settle.check_id=check_employee.user_id and check_employee.company_id='.get_company_id();
	        $join.=' left join gb_b_employee upload_employee on settle.check_id=upload_employee.user_id and upload_employee.company_id='.get_company_id();
	        $settle_info=$this->alias("settle")->getInfo($condition,$field,$join);
	        $process_list[self::CREATE_PROKEY]['is_done']=1;
	        $process_list[self::CREATE_PROKEY]['user_name']=$settle_info['creator_name'];
	        $process_list[self::CREATE_PROKEY]['time']=$settle_info['create_time'];
	        /*检查是否审核*/
	        if($settle_info['check_id']>0&&($settle_info['status']>=1)){
	            $process_list[self::CHECK_PROKEY]['is_done']=1;
	            $process_list[self::CHECK_PROKEY]['user_name']=$settle_info['check_name'];
	            $process_list[self::CHECK_PROKEY]['time']=$settle_info['check_time'];
	        }else{
	            $process_list[self::CHECK_PROKEY]['is_done']=0;
	            //没有审核读取审核权限的员工
	            $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::CHECK_FUNCTION);
	            $process_list[self::CHECK_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
	        }
	        /*检查是否上传凭证*/
	        if($settle_info['status']==4){
	            $process_list[self::UPLOAD_PROKEY]['is_done']=1;
	            $process_list[self::UPLOAD_PROKEY]['user_name']=$settle_info['upload_name'];
	            $process_list[self::UPLOAD_PROKEY]['time']=$settle_info['payop_time'];
	        }else{
	            $process_list[self::UPLOAD_PROKEY]['is_done']=0;
	            //没有审核读取审核权限的员工
	            $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::UPLOAD_FUNCTION);
	            $process_list[self::UPLOAD_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
	        }
	        if($settle_info['status']==2){
	            unset($process_list[self::UPLOAD_PROKEY]);
	        }
	    }
	    return $process_list;
	     
	}
	/**
	 * @author lzy 2018.5.28
	 * 将所有的流程组合起来
	 */
	private function _groupProcess(){
	    return array(
	        self::CREATE_PROKEY=>array(
	            'process_name'=>self::CREATE_PROCESS,
	        ),
	        self::CHECK_PROKEY=>array(
	            'process_name'=>self::CHECK_PROCESS,
	        ),
	        self::UPLOAD_PROKEY=>array(
	            'process_name'=>self::UPLOAD_PROCESS,
	        )
	    );
	}
	/**
	 * @author lzy 2018.5.28
	 * 上传凭证
	 */
	public function changeImg($condition,$update){
	    $this->startTrans();
	    $update['payment_pic']=$this->get_payment_pic('');
	    $result=$this->update($condition, $update);
	    if($result){
	        /*添加表单操作记录 add by lzy 2018.5.28 start*/
		    $result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_SETTLE,$condition['id'],self::UPLOAD_IMG);
		    /*添加表单操作记录 add by lzy 2018.5.28 end*/
	    }
	    if($result){
	        $this->commit();
	    }else{
	        $this->rollback();
	    }
	    return result;
	}

	/**
	 * @author alam
	 * @time 14:56 2018/6/11
	 * 来往钱、来往料、买卖料 各增加了type、sn_id字段，回溯这2个字段的数据
	 * 同时，由于之前小麦改了gb_b_material_order数据结构，删除了flow_id、type、t_cid字段，增加了account_id字段
	 * 程序做了修改但没有改数据库结构，故而这一部分的account_id也需要回溯
	 */
	public function recallRecordTableSettleId() {
		$error_list = array();
		// 用于update的model不能使用D方法，会自动修改company_id为当前登录商户id
		$model_company_account = D('BCompanyAccount');
		$model_caccount_record = M('BCaccountRecord');
		$model_material_record = M('BMaterialRecord');
		$model_material_order = M('BMaterialOrder');
		// M()->startTrans();
		$settle_list = $this->getList();
		if (!empty($settle_list)) {
			foreach($settle_list as $key => $value) {
				// 获取该结算单的供应商结欠账户id
				$condition = array(
					'deleted' => 0,
					'company_id' => $value['company_id'],
					'supplier_id' => $value['supplier_id'],
				);
				$company_account = $model_company_account->getInfo($condition, 'id');
				$account_id = $company_account['id'];

				$data = array(
					'sn_id' => $value['id']
				);
				// 修改来往钱sn_id
				if ($value['ca_record_id'] != 0) {
					$condition = array('id' => $value['ca_record_id']);
					$update = $model_caccount_record->where($condition)->save($data);
					if ($update === false) {
						$error_list[$value['id']][] = array('ca_record_id' => $value['ca_record_id']);
					}
				}
				// 修改来料sn_id
				if ($value['mrecord_id_2'] != 0) {
					$condition = array('id' => $value['mrecord_id_2']);
					$update = $model_material_record->where($condition)->save($data);
					if ($update === false) {
						$error_list[$value['id']][] = array('mrecord_id_2' => $value['mrecord_id_2']);
					}
				}
				// 修改去料sn_id
				if ($value['mrecord_id'] != 0) {
					$condition = array('id' => $value['mrecord_id']);
					$update = $model_material_record->where($condition)->save($data);
					if ($update === false) {
						$error_list[$value['id']][] = array('mrecord_id' => $value['mrecord_id']);
					}
				}
				// 修改买卖料sn_id
				if ($value['morder_id'] != 0) {
					$data['account_id'] = $account_id;
					$condition = array('id' => $value['morder_id']);
					$update = $model_material_order->where($condition)->save($data);
					if ($update === false) {
						$error_list[$value['id']][] = array('morder_id' => $value['morder_id']);
						$error_list[$value['id']][] = array('account_id' => $value['account_id']);
					}
					// echo $model_material_order->getlastsql();echo '<br>';
				}
			}
		}
		// M()->rollback();
		die(var_dump($error_list));
	}

	//结算审核更新来往钱记录，并添加结欠账户流水
	function update_BCaccountRecord($ca_info,$condition_common,&$price){
		$ca_record_info = D('BCaccountRecord')->getInfo($condition_common);
		if(!empty($ca_record_info)){
			$price = bcsub($price, $ca_record_info['price'], 2);
			$price = bcsub($price, $ca_record_info['extra_price'], 2);//抹零金额
			//更新来往钱记录状态
			$update_data = array('status' => 1);
			$rs = D('BCaccountRecord')->update($condition_common, $update_data);
			// 结欠账户流水
			if($rs !== false){
				$flow_price = bcadd($ca_record_info['extra_price'], $ca_record_info['price'], 2);
				$rs = D('Business/BCompanyAccountFlow')->add_flow($ca_info['id'], $ca_record_info['id'], 0, $flow_price, 2, 2);
			}
		}
		return $rs;
	}
	//结算审核更新来往料记录，并添加结欠账户流水
	function update_BMaterialRecord($condition_common,&$weight,$ca_info,$rs){
		$mrecord_list = D('BMaterialRecord')->getList($condition_common);
		if (!empty($mrecord_list)) {
			foreach ($mrecord_list as $key => $value) {
				if($rs !== false){
					//更新金料信息
					$update_data = array('status' => 1);
					$rs = D('BMaterialRecord')->update($condition_common, $update_data);
					$weight = bcadd($weight, $value['weight'], 2);
					// 更新来料金料状态
					if ($value['weight'] > 0) {
						if($rs !== false){
							$where = array('mr_id'=> $value['id']);
							$sub_sql = D('BMaterialRecordDetail')->where($where)->field('product_mid')->select(false);
							$where = array(
								'company_id' => get_company_id(),
								'id'=> array('exp', 'IN ('. $sub_sql .')')
							);
							$update_data = array('status' => 2);
							$rs = D('BRecoveryProduct')->update($where, $update_data);//结算单审核通过，更新来料金料为在库状态
						}
					}
					// 更新去料金料状态
					if ($value['weight'] < 0) {
						if($rs !== false){
							$where = array('mr_id'=> $value['id']);
							$sub_sql = D('BMaterialRecordDetail')->where($where)->field('product_mid')->select(false);
							$where = array(
								'company_id' => get_company_id(),
								'id'=> array('exp', 'IN ('. $sub_sql .')')
							);
							$update_data = array('status' => 5);
							$update_data['end_gold_price']=D('BOptions')->get_current_gold_price();
							$rs = D('BRecoveryProduct')->update($where, $update_data);//结算单审核通过，更新去料的结算金料为已结算状态
						}
					}
					// 结欠账户流水
					if($rs !== false&&$value['weight']!=0){
						$flow_weight = $value['weight'];
						$rs = D('Business/BCompanyAccountFlow')->add_flow($ca_info['id'], $value['id'], $flow_weight, 0, 3, 2);
					}
				}
			}
		}
		return $rs;
	}
	//审核结算单更新买卖料记录，并添加结欠账户流水
	function update_BMaterialOrder($condition_common,&$weight,&$price,$ca_info){
		$morder_info = D('BMaterialOrder')->getInfo($condition_common);
		if(!empty($morder_info)){
			$update_data = array('status' => 1);
			$rs = D('BMaterialOrder')->update($condition_common, $update_data);
			$sell_gold_price = decimalsformat(abs($morder_info['weight']) * abs($morder_info['mgold_price']), 2);
			// 买料，以料结算
			if($morder_info['weight'] > 0){
				$weight = bcsub($weight, $morder_info['weight'], 2);
				$price = bcadd($price, $sell_gold_price, 2);
				// 结欠账户流水 - 扣钱买料
				if($rs !== false){
					$flow_weight = $morder_info['weight'];
					$flow_price = bcsub(0, $sell_gold_price, 2);
					$rs = D('BCompanyAccountFlow')->add_flow($ca_info['id'], $morder_info['id'], $flow_weight, $flow_price, 4, 2);
				}
			}
			// 卖料，用钱结算
			if($morder_info['weight'] < 0){
				$price = bcsub($price, $sell_gold_price, 2);
				$weight = bcadd($weight, abs($morder_info['weight']), 2);
				// 结欠账户流水 - 扣料加钱
				if($rs !== false){
					$flow_weight = bcsub(0, abs($morder_info['weight']), 2);
					$flow_price = $sell_gold_price;
					$rs = D('BCompanyAccountFlow')->add_flow($ca_info['id'], $morder_info['id'], $flow_weight, $flow_price, 4, 2);
				}
			}
		}
		return $rs;
	}
}