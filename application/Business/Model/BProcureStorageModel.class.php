<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BProcureStorageModel extends BCommonModel {
    
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//分称完成
    const REVOKE              =  4;//撤销
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝
	const CHECK_REJECT        =  7;//分称驳回
    
    /*操作状态名*/
    const CREATE_NAME         =  '创建表单';
    const SAVE_NAME           =  '保存表单';
    const COMMIT_NAME         =  '分称完成';
    const REVOKE_NMAE         =  '撤销表单';
    const CHECK_PASS_NMAE     =  '审批通过';
    const CHECK_DENY_NAME     =  '审批拒绝';
	const CHECK_REJECT_NAME   =  '分称驳回';
    
    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const COMMIT_PROKEY       =  2;//分称表单流程键值
    const CHECK_PROKEY        =  3;//审核表单流程键值
    const CREATE_PROCESS      =  '创建表单';
    const COMMIT_PROCESS      =  '提交分称';
    const CHECK_PROCESS       =  '审核表单';
    
    /*操作函数名*/
    const COMMIT_FUNCTION     =  'business/bstorage/split_done';
    const CHECK_FUNCTION      =  'business/bstorage/check';

	// 更改procure_storage.status 状态
	public function set_status_by_where($where = array(), $status = 1, $from_status = 0,$check_memo=null){
		$info = array('status'=> 1, 'msg'=> '');
		
		$status = intval($status);
		$from_status = intval($from_status);

		if(!empty($where)){
			$datalist = $this->getList($where);

			foreach ($datalist as $key => $value) {
				if($value['status'] != $from_status){
					$info['status'] = 0;
					$info['msg'] .= '[入库记录'.$value['batch'].']状态异常！';
				}
			}

			if($info['status'] == 1){
				if($status==2||$status==-2){
					$update_data = array(
						'check_id'=>get_user_id(),
						'check_time'=>time(),
						'status'=> $status,
						'check_memo'=>$check_memo,
						'storage_status'=>0
					);
				}else{
					$update_data = array(
						'check_id'=>get_user_id(),
						'check_time'=>time(),
						'status'=> $status,
						'check_memo'=>$check_memo
					);
				}
				$rs = $this->update($where, $update_data);

				if($rs === false){
					$info['status'] = 0;
					$info['msg'] = '更改状态失败';
				}
			}
		}else{
			$info['status'] = 0;
			$info['msg'] = '非法参数！';
		}

		return $info;
	}

	// 删除采购单下面的 storage 信息 - deleted = 1
	public function delete_storage_by_procure_id($procurement_id = 0){
		$info = array('status'=> 1, 'msg'=> '');

		$procurement_id = intval($procurement_id);

		// $this->startTrans();

		$where = array('procurement_id'=> $procurement_id);
		$update_data = array('deleted'=> 1);

		$rs = $this->update($where, $update_data);

		if($rs !== false){

			$storage_info = $this->getInfo($where);

			if($storage_info['type'] == 2){

				$where = array('storage_id'=> $storage_info['id']);
				$update_data = array('deleted'=> 1);
				$rs = D('BRecoveryProduct')->update($where, $update_data);

				$rsData['status'] = ($rs === false) ? 0 : 1;
			}else{
				$rsData = D('BProduct')->delete_with_procure_id($procurement_id);
			}
			

			if($rsData['status'] == 1){
				// $this->commit();
			}else{
				// $this->rollback();

				$info = $rsData;
			}
		}else{
			// $this->rollback();

			$info['status'] = 0;
			$info['msg'] = '删除入库数据失败！';
		}

		return $info;
	}

	// 删除该包裹下的所有product数据 - deleted = 1
	public function delete_with_product(){
		$info = array('status'=> 0, 'msg'=> '操作失败！');

		$id = I('get.id/d', 0);

		$where = array(
			'id' => $id,
			'company_id' => get_company_id()
		);
		$storage_info = $this->getInfo($where);

		if ( !empty($storage_info) ) {
			$update_data = array('deleted'=> 1);

			$this->startTrans();

			$result = $this->update($where, $update_data);

			if($result !== false){
				$product_model = D('BProduct');
				$product_model->startTrans();

				$where_product = array('storage_id'=> $storage_info['id']);
				$update_product = array('deleted'=> 1);

				$result = $product_model->update($where_product, $update_product);
			}

			if($result !== false){
				$field = 'IFNULL(SUM(num), 0)as total_num';
				$tmp = $this->getInfo('procurement_id = '.$storage_info['procurement_id'], $field);
	
				if($tmp['total_num'] > 0){

					$where = array('id'=> $storage_info['procurement_id']);
					$update_data = array('num'=> $tmp['total_num']);

					$result = D('BProcurement')->update($where, $update_data);
					if($result == false){
						$info['status'] = 0;
						$info['msg'] = '更新procurement.num数据失败！';
					}
				}
			}

			if($result !== false){
				$product_model->commit();
				$this->commit();

				$info['status'] = 1;
				$info['msg'] = '';
			}else{
				$product_model->rollback();
				$this->rollback();
			}
		}

		return $info;
	}

	// 分称入库新增产品记录
	public function add_product(){
		$info = array('status'=> 1, 'msg'=> '');

		$postdata = I('post.');

		if(!empty($postdata)){

			$wh_id = get_current_warehouse_id();
			$nowtime = time();

			$storage_where = array('id'=> $postdata['storage_id']);
			$storage_info = $this->getInfo($storage_where);
			if(!empty($storage_info)&&$storage_info['storage_status']==0){

				$where = array('id'=> $storage_info['procurement_id']);
				$procure_info = D('BProcurement')->getInfo($where);

				if (!is_array($postdata['data'])) {
					$product_list = json_decode(htmlspecialchars_decode($postdata['data']), true);
				}

				// 查核对应货品
				$res = D('BProduct')->product_check($product_list);
				if($res['status'] == 0){
	                return $res;
				}else{
					$product_model = D('BProduct');

					M()->startTrans();

					$total_weight = 0.00;
					$total_price = 0.00;

					$old_data_where = array('storage_id'=> $storage_info['id'], 'deleted'=> 0);
					$old_data_list = $product_model->getList($old_data_where);
					$type= I("type");
					foreach($product_list as $key => $value){
						// 自动添加货品编号
						// $value['product_code'] = b_order_number('b_product', 'product_code');
						
						if($info['status']!=1){
							break;
						}

						if(!empty($old_data_list) && $value['is_old'] > 0){
							foreach ($old_data_list as $k => $v) {
								if($v['id'] == $value['is_old']){
									unset($old_data_list[$k]);
								}
							}
						}

						$total_weight += decimalsformat($value['weight'], 2);
						// $total_price += ($storage_info['gold_price']+$storage_info['fee']) * decimalsformat($value['weight'], 2);
						$total_price += decimalsformat($storage_info['fee'], 2) * decimalsformat($value['weight'], 2);
                        
						if($type==1){
						    $value['cost_price']=bcmul(bcadd($storage_info['gold_price'],$storage_info['fee'],2),$value['weight'],2);
						}
						$handle_data = array(
							'company_id'=> $procure_info['company_id'],
							'goods_id'=> $value['goods_id'],
							'warehouse_id'=> $wh_id,
							'qc_code'=> $value['qc_code'],
							'isd_num'=> $value['isd_num'],
							'product_code'=> $value['product_code'],
							'sub_product_code'=> $value['sub_product_code'],
							'storage_id'=> $storage_info['id'],
							'buy_time'=> $nowtime,
							'cost_price'=>$value['cost_price']?$value['cost_price']:0,
							'extras'=>$value['extras'],
							'sell_price'=>$value['sell_price']?$value['sell_price']:0,
							'type'=> $type,
							'certify_type'=>$value['certify_type'],
							'certify_code'=>$value['certify_code'],
							'certify_price'=>$value['certify_price'],
							'status'=> 1,
							'memo'=> $value['memo']
						);
						if($value['is_old'] > 0){
							$where_product = array('id'=> $value['is_old']);
							$product_info = $product_model->getInfo($where_product);
							if(!empty($product_info)){
								$update_data = $handle_data;

								unset($update_data['product_code']);

								$rs = $product_model->update($where_product, $update_data);
								if($rs === false){
									$info['status'] = 0;
									$info['msg'] .= '创建数据['.$value['product_code'].']失败！<br>';
									break;
								}
							}

						}else{
							$insert_data = $handle_data;

							// 审核不通过,驳回的分称入库单，新加货品然后保存
							if($storage_info['status'] == 2||$storage_info['status'] == -2){
								$insert_data['unpass'] = 1;
							}

							$rs = $product_model->insert($insert_data);
							if(!$rs){
								$info['status'] = 0;
								$info['msg'] .= '创建数据['.$value['product_code'].']失败！<br>';
								break;
							}
						}
						if($info['status']==1){
							switch ($type){
								case 1:
									$p_detail=array(
										'design'=>$value['design'],
										'weight'=>$value['weight'],
										'ring_size'=>$value['ring_size'],
										'bracelet_size'=>$value['bracelet_size'],
										'buy_price'=>$storage_info['gold_price'],
										'buy_m_fee'=>decimalsformat($storage_info['fee'], 2),
										'sell_fee'=>$value['sell_fee'],
									);
									$where=array();
									$where['product_id']=$value['is_old'] > 0?$value['is_old']:$rs;
									$p_info=M("b_product_gold")->where($where)->find();
									if($p_info){
										//$re=M("b_product_gold")->where($where)->save($p_detail);
										$re=D("b_product_gold")->update($where,$p_detail);
										if($re===false){
											$info['status'] = 0;
											$info['msg'] .= '货品详情修改失败！<br>';
										}
									}else{
										$p_detail['product_id']=$where['product_id'];
										$re=D("b_product_gold")->insert($p_detail);
										if(!$re){
											$info['status'] = 0;
											$info['msg'] .= '货品详情添加失败！<br>';
										}
									}
									break;
								case 2:
									$p_detail=array(
										'weight'=>$value['weight'],
										'd_weight'=>$value['d_weight'],
										'buy_price'=>$storage_info['gold_price'],
										'type'=>1
									);
									$where=array();
									$where['product_id']=$value['is_old'] > 0?$value['is_old']:$rs;
									$p_info=M("b_product_goldm")->where($where)->find();
									if($p_info){
										//$re=M("b_product_goldm")->where($where)->save($p_detail);
										$re=D("b_product_goldm")->update($where,$p_detail);
										if($re===false){
											$info['status'] = 0;
											$info['msg'] .= '货品详情修改失败！<br>';
										}
									}else{
										$p_detail['product_id']=$where['product_id'];
										$re=D("b_product_goldm")->insert($p_detail);
										if(!$re){
											$info['status'] = 0;
											$info['msg'] .= '货品详情添加失败！<br>';
										}
									}
									break;
								case 3:
									$p_detail=array(
										'shape'=>$value['shape'],
										'caratage'=>$value['caratage'],
										'color'=>$value['color'],
										'clarity'=>$value['clarity'],
										'cut'=>$value['cut'],
										'fluorescent'=>$value['fluorescent'],
										'polish'=>$value['polish'],
										'symmetric'=>$value['symmetric'],
									);
									$where=array();
									$where['product_id']=$value['is_old'] > 0?$value['is_old']:$rs;
									$p_info=M("b_product_diamond")->where($where)->find();
									if($p_info){
										//$re=M("b_product_diamond")->where($where)->save($p_detail);
										$re=D("b_product_diamond")->update($where,$p_detail);
										if($re===false){
											$info['status'] = 0;
											$info['msg'] .= '货品详情修改失败！<br>';
										}
									}else{
										$p_detail['product_id']=$where['product_id'];
										$re=D("b_product_diamond")->insert($p_detail);
										if(!$re){
											$info['status'] = 0;
											$info['msg'] .= '货品详情添加失败！<br>';
										}
									}
									break;
								case 4:
									$p_detail=array(
										'design'=>$value['design'],
										'material'=>$value['material'],
										'material_color'=>$value['material_color'],
										'ring_size'=>$value['ring_size'],
										'total_weight'=>$value['total_weight'],
										'gold_weight'=>$value['weight'],
										'main_stone_num'=>$value['main_stone_num'],
										'main_stone_caratage'=>$value['main_stone_caratage'],
										'main_stone_price'=>$value['main_stone_price'],
										'color'=>$value['color'],
										'clarity'=>$value['clarity'],
										'cut'=>$value['cut'],
										'side_stone_num'=>$value['side_stone_num'],
										'side_stone_caratage'=>$value['side_stone_caratage'],
										'side_stone_price'=>$value['side_stone_price'],
										'process_cost'=>$value['process_cost'],
										'cost_price'=>$value['cost_price'],
										'certify_type'=>$value['certify_type'],
										'certify_code'=>$value['certify_code'],
									);
									$where=array();
									$where['product_id']=$value['is_old'] > 0?$value['is_old']:$rs;
									$p_info=M("b_product_inlay")->where($where)->find();
									if($p_info){
										//$re=M("b_product_inlay")->where($where)->save($p_detail);
										$re=D("b_product_inlay")->update($where,$p_detail);
										if($re===false){
											$info['status'] = 0;
											$info['msg'] .= '货品详情修改失败！<br>';
										}
									}else{
										$p_detail['product_id']=$where['product_id'];
										$re=D("b_product_inlay")->insert($p_detail);
										if(!$re){
											$info['status'] = 0;
											$info['msg'] .= '货品详情添加失败！<br>';
										}
									}
									break;
								case 5:
									$p_detail=array(
										'ring_size'=>$value['ring_size'],
										'p_weight'=>$value['p_weight'],
										'stone_weight'=>$value['stone_weight'],
										'stone_num'=>$value['stone_num'],
										'stone_price'=>$value['stone_price'],
									);
									$where=array();
									$where['product_id']=$value['is_old'] > 0?$value['is_old']:$rs;
									$p_info=M("b_product_jade")->where($where)->find();
									if($p_info){
										//$re=M("b_product_jade")->where($where)->save($p_detail);
										$re=D("b_product_jade")->update($where,$p_detail);
										if($re===false){
											$info['status'] = 0;
											$info['msg'] .= '货品详情修改失败！<br>';
										}
									}else{
										$p_detail['product_id']=$where['product_id'];
										$re=D("b_product_jade")->insert($p_detail);
										if(!$re){
											$info['status'] = 0;
											$info['msg'] .= '货品详情添加失败！<br>';
										}
									}
									break;
							}
						}
					}

					// 旧product删除
					if($info['status'] == 1){
						
						if(!empty($old_data_list)){
							foreach ($old_data_list as $key => $value) {
								// jk 因为 product_code 数据库为唯一索引，所以不能重复，删除的product_code改为 _rm_{id}_{product_code}
								// $rm_product_code = '_rm_'.$value['id'].'_'.$value['product_code'];

								$update_data = array(
									'deleted' => 1,
									// 'product_code'=> $rm_product_code
									'product_code'=> array('exp', 'CONCAT("_rm_",id, "_", product_code)')
								);
								$rs = $product_model->update('id='.$value['id'], $update_data);
								if($rs === false){
									$info['status'] = 0;
									$info['msg'] .= '删除旧数据失败！<br>';
								}
							}
						}
					}
					
					// 更新 storage
					if($info['status'] == 1){
						$update_data = array(
							//'price'=> decimalsformat($total_price, 2),
							'num'=> count($product_list),
							'real_weight'=> decimalsformat($total_weight, 2),
							'storager_id'=> get_user_id(),
							'memo'=> $postdata['memo'],
							// 由已完成分称触发改变
							// 'storage_status'=> 1,
							'storage_time'=> $nowtime
						);
						$rs = $this->update($storage_where, $update_data);
						/*添加表单操作记录 add by lzy 2018.5.26 start*/
						$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$postdata['storage_id'],self::SAVE);
						/*添加表单操作记录 add by lzy 2018.5.26 end*/
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '更新数据失败！<br>';
						}
					}

					// 更新 procurement 的 num
					if($info['status'] == 1){

						$field = 'IFNULL(SUM(num), 0)as total_num';
						$tmp = $this->getInfo('procurement_id = '.$storage_info['procurement_id'], $field);
			
						if($tmp['total_num'] > 0){

							$where = array('id'=> $storage_info['procurement_id']);
							$update_data = array('num'=> $tmp['total_num']);
							$b_info=D('BProcurement')->where($where)->find();
							if($b_info['num']!=$tmp['total_num']){
								$rs = D('BProcurement')->update($where, $update_data);
								if($rs == false){
									$info['status'] = 0;
									$info['msg'] = '更新procurement.num数据失败！';
								}
							}
						}
					}
					
					// 判断回滚
					if($info['status'] == 1){
						// $product_model->commit();
						// $this->commit();

						M()->commit();
					}else{
						// $product_model->rollback();
						// $this->rollback();

						M()->rollback();
					}
				}
			}else{
				$info['status'] = 0;
				$info['msg'] = '已经分称完成！';
			}
		}else{
			$info['status'] = 0;
			$info['msg'] = '操作失败！';
		}

		return $info;
	}

	// 分称入库新增金料产品记录
	public function add_product_gold(){
		$info = array('status'=> 1, 'msg'=> '');

		$postdata = I('post.');
		$product_gold_mode=D("BProductGold");
		if(!empty($postdata)){

			$wh_id = get_current_warehouse_id();
			$nowtime = time();

			$storage_where = array('id'=> $postdata['storage_id']);
			$storage_info = $this->getInfo($storage_where);
			if(!empty($storage_info)){
				$where = array('id'=> $storage_info['procurement_id']);
				$procure_info = D('BProcurement')->getInfo($where);

				$product_list = $postdata['data'];

				// 查核对应货品
				$res = $product_gold_mode->product_check($product_list);
				if($res['status'] == 0){
					return $res;
				}else{
					$product_gold_mode->startTrans();

					$this->startTrans();

					$total_weight = 0.00;
					$total_price = 0.00;

					$old_data_where = array('storage_id'=> $storage_info['id'], 'deleted'=> 0);
					$old_data_list = $product_gold_mode->getList($old_data_where);

					foreach($product_list as $key => $value){

						if(!empty($old_data_list) && $value['is_old'] > 0){
							foreach ($old_data_list as $k => $v) {
								if($v['id'] == $value['is_old']){
									unset($old_data_list[$k]);
								}
							}
						}

						$total_weight += decimalsformat($value['weight'], 2);
						// $total_price += ($storage_info['gold_price']+$storage_info['fee']) * decimalsformat($value['weight'], 2);
						$total_price += decimalsformat($storage_info['fee'], 2) * decimalsformat($value['weight'], 2);

						$handle_data = array(
							'company_id'=> $procure_info['company_id'],
							'goods_id'=> $value['goods_id'],
							'warehouse_id'=> $wh_id,
							'product_code'=> $value['product_code'],
							'storage_id'=> $storage_info['id'],
							'buy_price'=> $storage_info['gold_price'],
							'buy_time'=> $nowtime,
							'weight'=> decimalsformat($value['weight'], 2),
							'd_weight'=>decimalsformat($value['d_weight'],2),
							'source'=>'采购',
							'status'=> 1,
							'buy_m_fee'=> decimalsformat($storage_info['fee'], 2),
						);

						if($value['is_old'] > 0){
							$where_product = array('id'=> $value['is_old']);
							$product_info = $product_gold_mode->getInfo($where_product);
							if(!empty($product_info)){
								$update_data = $handle_data;
								$rs = $product_gold_mode->update($where_product, $update_data);
							}
						}else{
							$insert_data = $handle_data;
							$rs = $product_gold_mode->insert($insert_data);
						}

						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '创建数据['.$value['product_code'].']失败！<br>';
						}
					}
					// 旧product删除
					if($info['status'] == 1){
						if(!empty($old_data_list)){
							foreach ($old_data_list as $key => $value) {
								$update_data = array('deleted'=> 1);
								$rs = $product_gold_mode->update('id='.$value['id'], $update_data);
								if($rs === false){
									$info['status'] = 0;
									$info['msg'] .= '删除旧数据失败！<br>';
								}
							}
						}
					}

					// 更新 storage
					if($info['status'] == 1){
						$update_data = array(
							//'price'=> decimalsformat($total_price, 2),
							'num'=> count($product_list),
							'real_weight'=> decimalsformat($total_weight, 2),
							'storager_id'=> get_user_id(),
							// 由已完成分称触发改变
							// 'storage_status'=> 1,
							'storage_time'=> $nowtime
						);
						$rs = $this->update($storage_where, $update_data);
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '更新数据失败！<br>';
						}
					}

					// 更新 procurement 的 num
					if($info['status'] == 1){

						$field = 'IFNULL(SUM(num), 0)as total_num';
						$tmp = $this->getInfo('procurement_id = '.$storage_info['procurement_id'], $field);
						if($tmp['total_num'] > 0){

							$where = array('id'=> $storage_info['procurement_id']);
							$update_data = array('num'=> $tmp['total_num']);

							$rs = D('BProcurement')->update($where, $update_data);
							if($rs == false){
								$info['status'] = 0;
								$info['msg'] = '更新procurement.num数据失败！';
							}
						}
					}

					// 判断回滚
					if($info['status'] == 1){
						$product_gold_mode->commit();
						$this->commit();
					}else{
						$product_gold_mode->rollback();
						$this->rollback();
					}
				}
			}
		}else{
			$info['status'] = 0;
			$info['msg'] = '操作失败！';
		}

		return $info;
	}

	// 获取连表数据
	public function get_info($user, $param_id = null){
		$id = I('id/d', 0);
		if(empty($id)){
			$id = ! empty($param_id) ? $param_id : $id;
		}
		$main_tbl = C('DB_PREFIX').'b_procure_storage';
		
		$where = array(
			$main_tbl.'.id'=> $id,
			$main_tbl.'.company_id' => get_company_id()
		);

		// $field = $main_tbl.'.*, s.company_name, p.batch as procure_batch,p.pricemode,gc.class_name,gc.type,p.status as p_status';
		$field = $main_tbl.'.*, s.company_name, p.batch as procure_batch,p.pricemode,p.status as p_status';
		$field .= ', agc.class_name';
		/*$field .= ', (
			CASE '.$main_tbl.'.type
			WHEN 1 THEN "素金"
			WHEN 2 THEN "金料"
			WHEN 3 THEN "裸钻"
			WHEN 4 THEN "镶嵌"
			WHEN 5 THEN "玉石"
			WHEN 6 THEN "摆件"
			ELSE "" END
		) as show_type';*/
		$field .= ', ('.$main_tbl.'.real_weight - '.$main_tbl.'.weight)as diff_weight';
		$field .= ', ifnull(cu.user_nicename,cu.mobile)as creator_name';
		$field .= ', IF('.$main_tbl.'.storager_id, ifnull(su.user_nicename,su.mobile),"-") as storager_name';
		$field .= ', IF('.$main_tbl.'.storage_status, (
			CASE '.$main_tbl.'.status 
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		), "待分称")as show_status';
		$field .= ', (CASE p.status 
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		)as show_procure_status';

		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_procurement as p ON (p.id = '.$main_tbl.'.procurement_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_supplier as s ON (s.id = p.supplier_id)'; 
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'m_users as cu ON (cu.id = '.$main_tbl.'.creator_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'m_users as su ON (su.id = '.$main_tbl.'.storager_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'a_goods_class as agc ON (agc.id = '.$main_tbl.'.agc_id)';
		// $join .= ' LEFT JOIN '.C('DB_PREFIX').'b_goods_class as gc ON (gc.id = '.$main_tbl.'.goods_class_id)';

		$info = $this->getInfo($where, $field, $join);

		if($info['storage_status'] == 0){
			$info['storager_name'] = $user['is_real_name'] ? $user['realname'] : $user['user_nicename'];
		}

		return $info;
	}

	// 保存伴随新增产品记录 - 计重
	public function save_with_not_create_product($datalist = array(), $procure_info = array()){
		$info = array('status'=> 1, 'msg'=> '');

		if(!empty($datalist)){

			$nowtime = time();

			$where = array(
				'procurement_id'=> $procure_info['id'], 
				'deleted'=> 0
			);
			$old_data_list = $this->getList($where, 'id, batch');
			foreach ($datalist as $key => $value) {

				// 与旧记录对比
				if(!empty($old_data_list)){
					foreach ($old_data_list as $k => $v) {
						if($v['batch'] == $value['batch']){
							unset($old_data_list[$k]);
						}
					}
				}

				$where = array('batch' => $value['batch'], 'deleted' => 0);

				$storage_info = $this->getInfo($where);

				// 入库分称再计算 工费及一口价
				if($value['weight'] > 0){
					$total_other_price = bcmul($value['buy_m_fee'], $value['weight'], 4);
				}else{
					$total_other_price = 0;
				}

				if($value['gold_price'] <=0){
					$info['status'] = 0;
					$info['msg'] .= '[编号:'. $value['batch'] .']第'. ($key+1) .'行商品采购金价不能小于等于0！<br>';

					break;
				}
				if($value['weight'] <= 0){
					$info['status'] = 0;
					$info['msg'] .= '[编号:'. $value['batch'] .']第'. ($key+1) .'行商品采购克重不能小于等于0！<br>';

					break;
				}

				if(!empty($storage_info)){
					// 存在旧数据则更新
					$update_data = array(
						'company_id' => $procure_info['company_id'],
						'procurement_id' => $procure_info['id'],
						'gold_price' => numberformat($value['gold_price'], 2,'.',''),
						'fee' => numberformat($value['buy_m_fee'], 2,'.',''),
						'weight' => numberformat($value['weight'], 2,'.',''),
						'gold_weight' => numberformat($value['gold_weight'], 2,'.',''),
						'type' => $value['type'],
						'agc_id' => $value['agc_id'],
						'price' => numberformat($total_other_price, 2,'.',''),
						// 入库状态 - 计重直接状态为0
						'storage_status' => 0,
						'status' => 0
					);
					$rs = $this->update($where, $update_data);
					/*添加表单操作记录 add by lzy 2018.5.26 start*/
					$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$storage_info['id'],self::SAVE);
					/*添加表单操作记录 add by lzy 2018.5.26 end*/
				}else{
					// 不存在则创建
					$insert_data = array(
						'company_id' => $procure_info['company_id'],
						'batch' => $value['batch'],
						'procurement_id' => $procure_info['id'],
						'gold_price' => numberformat($value['gold_price'], 2,'.',''),
						'fee' => numberformat($value['buy_m_fee'], 2,'.',''),
						'weight' => numberformat($value['weight'], 2,'.',''),
						'gold_weight' => numberformat($value['gold_weight'], 2,'.',''),
						'type' => $value['type'],
						'agc_id' => $value['agc_id'],
						'price' => numberformat($total_other_price, 2,'.',''),
						// 入库状态 - 计重直接状态为0
						'storage_status' => 0,
						'status' => 0,
						'creator_id' => get_user_id(),
						'create_time' => $nowtime
					);

					$rs = $this->insert($insert_data);
					if($rs){
					    /*添加表单操作记录 add by lzy 2018.5.26 start*/
					    $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$rs,self::CREATE);
					    /*添加表单操作记录 add by lzy 2018.5.26 end*/
					}
					
				}

				if($rs === false){
					$info['status'] = 0;
					$info['msg'] .= '[编号:'. $value['batch'] .']第'. $key .'行商品数据处理中断！<br>';
				}
			}

			if($info['status'] == 1){

				if(count($old_data_list) > 0){
					foreach ($old_data_list as $key => $value) {
						// 删除 storage
						$where = array('batch'=> $value['batch']);
						$update_data = array('deleted'=> 1);

						$rs = $this->update($where, $update_data);
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '删除[编号:'. $value['batch'] .']数据处理中断！<br>';
						}

						// 删除 storage 对应的 product
						if($rs !== false){
							$rs = D('BProduct')->update_status_with_storage_id($value['id'], -1);
							if($rs === false){
								$info['status'] = 0;
								$info['msg'] .= '删除旧数据失败！<br>';
							}
						}
					}
				}
			}
		}else{
			$info['status'] = 0;
			$info['msg'] = '参数有误！';
		}

		return $info;
	}

	// 保存伴随新增旧金产品记录 - 计件
	public function save_with_recovery_product($datalist = array(), $procure_info = array()){
		$info = array('status'=> 1, 'msg'=> '');

		if(!empty($datalist)){

			$wh_id = get_current_warehouse_id();
			$nowtime = time();

			$insert_data = array(
				'type'=> I('type/d', 0),
				'agc_id'=> I('agc_id/d', 0),
				'company_id'=> $procure_info['company_id'],
				'batch'=> b_order_number('BProcureStorage', 'batch'),
				'procurement_id'=> $procure_info['id'],
				'num'=> $procure_info['num'],
				'price'=> $procure_info['price'],
				'storage_status'=> 1,
				'storager_id'=> get_user_id(),
				'storage_time'=> $nowtime,
				'creator_id'=> get_user_id(),
				'create_time'=> $nowtime
			);
			$where = array('procurement_id'=> $procure_info['id']);
			$storage_info = $this->getInfo($where);
			if(!empty($storage_info)){
				// 已经存在sotrage数据，则为更新
				$update_data = $insert_data;

				unset($update_data['batch']);
				unset($update_data['procurement_id']);
				unset($update_data['creator_id']);
				unset($update_data['create_time']);

				$rs = $this->update($where, $update_data);
				if($rs !== false){
					$new_storage_id = $storage_info['id'];
				}else{
					$new_storage_id = false;
				}
			}else{
				$new_storage_id = $this->insert($insert_data);
			}

			if($new_storage_id !== false){
				$product_model = D('BRecoveryProduct');

				if(!empty($storage_info)){
					// 获取旧product数据列表
					$where = array(
						'order_id'=> $new_storage_id,
						'type'=> 2,
						'deleted'=> 0
					);
					$old_data_list = $product_model->getList($where, 'id, rproduct_code');
				}
				$gold_price=D('BOptions')->get_current_gold_price();
				$temp_product_code = array();
				foreach ($datalist as $key => $value) {
					//$datalist[$key]['rproduct_code']=$value['rproduct_code']=D('BRecoveryProduct')->get_rproduct_code();
					if(!empty($old_data_list)){
						foreach ($old_data_list as $k => $v) {
							if($v['id'] == $value['id']  || $v['rproduct_code'] == $value['rproduct_code']){
								unset($old_data_list[$k]);
							}
						}
					}

					$insert_product_data = array(
						'recovery_name'=> $value['recovery_name'],
						'company_id'=> $procure_info['company_id'],
						// 'rproduct_code'=> b_order_number('b_recovery_product', 'rproduct_code'),
						'rproduct_code'=> 'temp_' . $value['rproduct_code'],
						'order_id'=> $new_storage_id,
						'buy_time'=> $nowtime,
						'gold_price'=> $gold_price,//decimalsformat($value['gold_price'], 2),
						'recovery_price'=> decimalsformat($value['gold_price'], 2),
						'gold_weight'=> decimalsformat($value['gold_weight'], 2),
						'total_weight'=> decimalsformat($value['total_weight'], 2),
						'purity'=> decimalsformat($value['purity'], 6),
						'service_fee'=> 0,
						'cost_price'=> decimalsformat($value['cost_price'], 2),
						'type'=> 2,
						'status'=> 1,
						'create_time'=> $nowtime,
						'wh_id'=>$wh_id,
						'memo'=>$value['memo'],
						'material'=> $value['material'],
						'color'=> $value['color'],
					);

					$where = array(
						'id'=> $value['id']
					);

					$product_info = $product_model->getInfo($where);
					if(!empty($product_info)){
						// 存在旧数据则更新
						$update_product_data = $insert_product_data;
						// unset($update_product_data['rproduct_code']);

						$rs = $product_model->update($where, $update_product_data);
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '更新数据失败！<br>';
						}
						$temp_product_code[$value['id']] = $value['rproduct_code'];
					}else{
						// 不存在则创建
						$new_product_id = $product_model->insert($insert_product_data);
						if($new_product_id === false){
							$info['status'] = 0;
							$info['msg'] .= '创建数据失败！<br>';
						}
						$temp_product_code[$new_product_id] = $value['rproduct_code'];
					}
				}
			}

			if($info['status'] == 1){

				if(!empty($old_data_list)){
					foreach($old_data_list as $key => $value){
						$where = array(
							'id'=> $value['id']
						);
						$update_data = array('deleted'=> 1);

						$rs = $product_model->update($where, $update_data);
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '删除数据处理中断！<br>';
						}
					}
				}
			}

			if($info['status'] == 1){
				$where = array(
					'order_id'=> $new_storage_id,
					'deleted'=> 0
				);
				$total_weight = $product_model->where($where)->sum('total_weight');

				// 更新 storage 的总重量
				$where = array('id'=> $new_storage_id);
				$update_data = array('weight'=> $total_weight);
				$rs = $this->update($where, $update_data);
				if($rs === false){
					$info['status'] = 0;
				}

				// 更新 procurement 的总重量
				if($info['status'] == 1){
					$where = array('id'=> $storage_info['procurement_id']);
					$update_data = array('weight'=> $total_weight);
					$rs = D('Business/BProcurement')->update($where, $update_data);
					if($rs === false){
						$info['status'] = 0;
					}
				}
			}

	        /*add by alam 2016/06/04 start*/
	        // 为了防止product_code重复，将货品编码集成为一列，所有数据操作成功之后再做回溯，在方法前已做了货品编码存在性验证，该操作为实现同一单据内上下货品编码颠倒顺序
	        if ($info['status'] == 1) {
	        	foreach ($temp_product_code as $key => $value) {
	        		if (!empty($key) && $info['status']) {
						$rs = $product_model->update(array('id' => $key), array('rproduct_code' => $value));
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '回溯[编号:'. $value['rproduct_code'] .']数据处理中断！<br>';
						}
	        		}
	        	}
	        }
	        /*add by alam 2016/06/04 end*/
		}else{
			$info['status'] = 0;
			$info['msg'] = '参数有误！';
		}

		return $info;
	}

	// 保存伴随新增产品记录 - 计件
	public function save_with_create_product($datalist = array(), $procure_info = array()){
		$info = array('status'=> 1, 'msg'=> '');

		if(!empty($datalist)){

			$wh_id = get_current_warehouse_id();
			$nowtime = time();

			$insert_data = array(
				'type'=> I('type/d', 0),
				'agc_id'=> I('agc_id/d', 0),
				'company_id'=> $procure_info['company_id'],
				'batch'=> b_order_number('BProcureStorage', 'batch'),
				'procurement_id'=> $procure_info['id'],
				'num'=> $procure_info['num'],
			    'weight'=> $procure_info['weight'],
				'price'=> $procure_info['price'],
				'storage_status'=> 1,
				'storager_id'=> get_user_id(),
				'storage_time'=> $nowtime,
				'creator_id'=> get_user_id(),
				'create_time'=> $nowtime
			);

			$where = array('procurement_id'=> $procure_info['id']);
			$storage_info = $this->getInfo($where);
			if(!empty($storage_info)){
				// 已经存在sotrage数据，则为更新
				$update_data = $insert_data;

				unset($update_data['batch']);
				unset($update_data['procurement_id']);
				unset($update_data['creator_id']);
				unset($update_data['create_time']);

				$rs = $this->update($where, $update_data);
				if($rs !== false){
					$new_storage_id = $storage_info['id'];
				}else{
					$new_storage_id = false;
				}
			}else{
				$new_storage_id = $this->insert($insert_data);
			}

			if($new_storage_id !== false){
				$product_model = D('BProduct');

				if(!empty($storage_info)){
					// 获取旧product数据列表
					$where = array(
						'storage_id'=> $storage_info['id'],
						'deleted'=> 0
					);
					$old_data_list = $product_model->getList($where, 'id, goods_id, product_code');
				}
				$type = I("type");
				$temp_product_code = array();
				foreach ($datalist as $key => $value) {

					if(!empty($old_data_list)){
						foreach ($old_data_list as $k => $v) {
							if($v['goods_id'] == $value['goods_id'] && $v['product_code'] == $value['product_code']){
								unset($old_data_list[$k]);
							}
						}
					}

					$insert_product_data = array(
						'company_id'=> $procure_info['company_id'],
						'goods_id'=> $value['goods_id'],
						'warehouse_id'=> $wh_id,
						'qc_code'=> $value['qc_code'],
						'isd_num'=> $value['isd_num'],
						'product_code'=> 'temp_' . $value['product_code'],
						'sub_product_code'=> $value['sub_product_code'],
						'product_age'=> $value['product_age'],
						'storage_id'=> $new_storage_id,
						'buy_time'=> $nowtime,
						'cost_price'=> decimalsformat($value['cost_price'], 2),
						'sell_price'=> decimalsformat($value['sell_price'], 2),
						'extras'=> decimalsformat($value['extras'], 2),
						'type'=> $type,
						'certify_type'=>$value['certify_type'],
						'certify_code'=>$value['certify_code'],
						'certify_price'=>$value['certify_price'],
						'status'=> 1,
						'memo'=>$value['memo']
					);

					/*change by alam 2018/06/02 使用货品的id进行更新 start*/
					if ($value['is_old'] > 0) {
						$where = array('id' => $value['is_old']);
						// 存在旧数据则更新
						$update_product_data = $insert_product_data;
						$rs = $product_model->update($where, $update_product_data);
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '更新数据['.$value['product_code'].']失败！<br>';
						}
						$temp_product_code[$value['is_old']] = $value['product_code'];
					}else{
						// 不存在则创建
						$new_product_id = $product_model->insert($insert_product_data);
						if($new_product_id === false){
							$info['status'] = 0;
							$info['msg'] .= '创建数据['.$value['product_code'].']失败！<br>';
						}
						$temp_product_code[$new_product_id] = $value['product_code'];
					}
					/*change by alam 2018/06/02 end*/

					if($info['status']==1){
						switch ($type){
						    case 1:
						        $p_detail=array(
						            'design'=>$value['design'],
						            'weight'=>$value['weight'],
						            'ring_size'=>$value['ring_size'],
						            'buy_price'=>$value['gold_price'],
						            'buy_m_fee'=>0,
						            'company_id'=>get_company_id(),
						        );
						        $where=array();
						        $where['product_id']=$value['is_old'] > 0?$value['is_old']:$new_product_id;
						        $p_info=M("b_product_gold")->where($where)->find();
						        if($p_info){
						            //$re=M("b_product_diamond")->where($where)->save($p_detail);
						            $re=D("b_product_gold")->update($where,$p_detail);
						            if($re===false){
						                $info['status'] = 0;
						                $info['msg'] .= '货品详情修改失败！<br>';
						            }
						        }else{
						            $p_detail['product_id']=$where['product_id'];
						            $re=D("b_product_gold")->insert($p_detail);//die(var_dump(D("b_product_gold")->getLastSql()));
						            if(!$re){
						                $info['status'] = 0;
						                $info['msg'] .= '货品详情添加失败！<br>';
						            }
						        }
						        break;
							case 3:
								$p_detail=array(
									'shape'=>$value['shape'],
									'caratage'=>$value['caratage'],
									'color'=>$value['color'],
									'clarity'=>$value['clarity'],
									'cut'=>$value['cut'],
									'fluorescent'=>$value['fluorescent'],
									'polish'=>$value['polish'],
									'symmetric'=>$value['symmetric'],
									'company_id'=>get_company_id(),
								);
								$where=array();
								$where['product_id']=$value['is_old'] > 0?$value['is_old']:$new_product_id;
								$p_info=M("b_product_diamond")->where($where)->find();
								if($p_info){
									$re=D("b_product_diamond")->update($where,$p_detail);
									if($re===false){
										$info['status'] = 0;
										$info['msg'] .= '货品详情修改失败！<br>';
									}
								}else{
									$p_detail['product_id']=$where['product_id'];
									$re=D("b_product_diamond")->insert($p_detail);
									if(!$re){
										$info['status'] = 0;
										$info['msg'] .= '货品详情添加失败！<br>';
									}
								}
								break;
							case 4:
								$p_detail=array(
									'design'=>$value['design'],
									'shape' =>$value['shape'],
									'bracelet_size' =>$value['bracelet_size'],
									'material'=>$value['material'],
									'material_color'=>$value['material_color'],
									'ring_size'=>$value['ring_size'],
									'total_weight'=>$value['total_weight'],
									'gold_weight'=>$value['weight'],
									'main_stone_num'=>$value['main_stone_num'],
									'main_stone_caratage'=>$value['main_stone_caratage'],
									'main_stone_price'=>$value['main_stone_price'],
									'color'=>$value['color'],
									'clarity'=>$value['clarity'],
									'cut'=>$value['cut'],
									'side_stone_num'=>$value['side_stone_num'],
									'side_stone_caratage'=>$value['side_stone_caratage'],
									'side_stone_price'=>$value['side_stone_price'],
									'process_cost'=>$value['process_cost'],
									'cost_price'=>$value['cost_price'],
									'certify_type'=>$value['certify_type'],
									'certify_code'=>$value['certify_code'],
									'company_id'=>get_company_id(),
								);
								$where=array();
								$where['product_id']=$value['is_old'] > 0?$value['is_old']:$new_product_id;
								$p_info=M("b_product_inlay")->where($where)->find();
								if($p_info){
									$re=D("b_product_inlay")->update($where,$p_detail);
									if($re===false){
										$info['status'] = 0;
										$info['msg'] .= '货品详情修改失败！<br>';
									}
								}else{
									$p_detail['product_id']=$where['product_id'];
									$re=D("b_product_inlay")->insert($p_detail);
									if(!$re){
										$info['status'] = 0;
										$info['msg'] .= '货品详情添加失败！<br>';
									}
								}
								break;
							case 5:
								$p_detail=array(
									'ring_size'=>$value['ring_size'],
									'bracelet_size'=>$value['bracelet_size'],
									'p_weight'=>$value['p_weight'],
									'stone_weight'=>$value['stone_weight'],
									'stone_num'=>$value['stone_num'],
									'stone_price'=>$value['stone_price'],
									'company_id'=>get_company_id(),
								);
								$where=array();
								$where['product_id']=$value['is_old'] > 0?$value['is_old']:$new_product_id;
								$p_info=M("b_product_jade")->where($where)->find();
								if($p_info){
									$re=D("b_product_jade")->update($where,$p_detail);
									if($re===false){
										$info['status'] = 0;
										$info['msg'] .= '货品详情修改失败！<br>';
									}
								}else{
									$p_detail['product_id']=$where['product_id'];
									$re=D("b_product_jade")->insert($p_detail);
									if(!$re){
										$info['status'] = 0;
										$info['msg'] .= '货品详情添加失败！<br>';
									}
								}
								break;
						}
					}
				}
			}

			if($info['status'] == 1){

				if(!empty($old_data_list)){
					foreach($old_data_list as $key => $value){

						$where = array(
							'goods_id'=> $value['goods_id'],
							'product_code'=> $value['product_code']
						);
						$update_data = array(
							'deleted'=> 1,
							// add by alam 2018/06/02 product_code 唯一索引
							'product_code'=> array('exp', 'CONCAT("_rm_",id, "_", product_code)')
						);

						$rs = $product_model->update($where, $update_data);
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '删除[编号:'. $value['product_code'] .']数据处理中断！<br>';
						}
					}
				}
	        }

	        /*add by alam 2016/06/04 start*/
	        // 为了防止product_code重复，将货品编码集成为一列，所有数据操作成功之后再做回溯，在方法前已做了货品编码存在性验证，该操作为实现同一单据内上下货品编码颠倒顺序
	        if ($info['status'] == 1) {
	        	foreach ($temp_product_code as $key => $value) {
	        		if (!empty($key) && $info['status']) {
						$rs = $product_model->update(array('id' => $key), array('product_code' => $value));
						if($rs === false){
							$info['status'] = 0;
							$info['msg'] .= '回溯[编号:'. $value['product_code'] .']数据处理中断！<br>';
						}
	        		}
	        	}
	        }
	        /*add by alam 2016/06/04 end*/
		}else{
			$info['status'] = 0;
			$info['msg'] = '参数有误！';
		}

		return $info;
	}

	// 导出 excel
    public function excel_out($where, $field, $join, $page = 1){

    	$num = 1000;
    	$limit = ($page - 1) * $num .','.$num;
		$order = 'gb_b_procure_storage.id DESC';
    	$data_list = $this->getList($where, $field, $limit, $join,$order);

        if($data_list){

            register_shutdown_function(array(&$this, 'excel_out'), $where, $field, $join, $page + 1);

            $title_arr = array(
            	'序'=> '',
				// '货品分类'=>'class_name',
				'货品类型'=>'show_type',
            	'包裹编号'=> 'batch',
            	'采购编号'=> 'procure_batch',
            	'供应商'=> 'company_name',
            	'包裹件数'=> 'num',
            	'克重'=> 'weight',
            	'实际克重'=> 'real_weight',
            	'秤差'=> 'diff_weight',
            	'采购人员'=> 'creator_name',
            	'分称人员'=> 'storager_name',
            	'包裹状态'=> 'show_status',
            	'创建时间'=> 'show_create_time',
            	'备注'=> 'memo'
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

            header("Content-Disposition: attachment; filename=分称入库打印.csv");

            echo $content;
        }        
    }
    /**
     * @author lzy 2018.5.26
     * @param int $storage_id 分称单id
     * @return 操作记录列表
     */
    public function getOperateRecord($storage_id){
        $condition=array(
            'operate.company_id'=>get_company_id(),
            'operate.type'=>BBillOpRecordModel::PROCURE_STORAGE,
            'operate.sn_id'=>$storage_id,
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
     * @author lzy 2018.5.26
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
			self::CHECK_REJECT=>self::CHECK_REJECT_NAME,
        );
    }
    /**
     * @author lzy 2018.5.26
     * 获取流程数组
     */
    public function getProcess($storage_id){
        $process_list=$this->_groupProcess();
        if(!empty($storage_id)){
            $condition=array(
                'storage.id'=>$storage_id,
            );
            $field='storage.*,create_employee.employee_name as creator_name,commit_employee.employee_name as commit_name,check_employee.employee_name as check_name';
            $join='left join gb_b_employee create_employee on storage.creator_id=create_employee.user_id and create_employee.company_id='.get_company_id();
            $join.=' left join gb_b_employee commit_employee on storage.storager_id=commit_employee.user_id and commit_employee.company_id='.get_company_id();
            $join.=' left join gb_b_employee check_employee on storage.check_id=check_employee.user_id and check_employee.company_id='.get_company_id();
            $storage_info=$this->alias("storage")->getInfo($condition,$field,$join);
            $process_list[self::CREATE_PROKEY]['is_done']=1;
            $process_list[self::CREATE_PROKEY]['user_name']=$storage_info['creator_name'];
            $process_list[self::CREATE_PROKEY]['time']=$storage_info['create_time'];
            
            /*检查是否分称提交,审核必分称*/
            if($storage_info['storage_status']==1||$storage_info['status']==1||$storage_info['status']==2){
                $process_list[self::COMMIT_PROKEY]['is_done']=1;
                $process_list[self::COMMIT_PROKEY]['user_name']=$storage_info['commit_name'];
                $process_list[self::COMMIT_PROKEY]['time']=$storage_info['storage_time'];
            }else if($storage_info['storage_status']==0){
                $process_list[self::COMMIT_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::COMMIT_FUNCTION);
                $process_list[self::COMMIT_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
            }
            
            /*检查是否审核*/
            if($storage_info['check_id']>0&&($storage_info['status']==1||$storage_info['status']==2)){
                $process_list[self::CHECK_PROKEY]['is_done']=1;
                $process_list[self::CHECK_PROKEY]['user_name']=$storage_info['check_name'];
                $process_list[self::CHECK_PROKEY]['time']=$storage_info['check_time'];
            }else{
                $process_list[self::CHECK_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::CHECK_FUNCTION);
                $process_list[self::CHECK_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
            }
        }
        return $process_list;
         
    }
    /**
     * @author lzy 2018.5.26
     * 将所有的流程组合起来
     */
    private function _groupProcess(){
        return array(
            self::CREATE_PROKEY=>array(
                'process_name'=>self::CREATE_PROCESS,
            ),
            self::COMMIT_PROKEY=>array(
                'process_name'=>self::COMMIT_PROCESS,
            ),
            self::CHECK_PROKEY=>array(
                'process_name'=>self::CHECK_PROCESS,
            )
        );
    }
	//获取审核条数
	function get_check_count($where,$name,$url){
		$main_tbl = C('DB_PREFIX') . 'b_procure_storage';
		$condition = array(
			'p.pricemode' => 1,
			'p.deleted' => 0,
			$main_tbl . '.deleted' => 0,
			$main_tbl . '.company_id' => get_company_id()
		);
		$condition[C('DB_PREFIX') . 'b_procure_storage.storage_status'] = 1;
		$condition[C('DB_PREFIX') . 'b_procure_storage.status'] = 0;
		$condition['p.status'] = array(
			"neq",
			"2,3"
		);
		if(!empty($where)){
			$condition=array_merge($condition,$where);
		}
		$join = ' LEFT JOIN ' . C('DB_PREFIX') . 'b_procurement as p ON (p.id = ' . $main_tbl . '.procurement_id)';
		$count = $this->countList($condition,$file="*",$join);
		$result=array('name'=>$name,'url'=>$url,'count'=>$count);
		return $result;
	}
}
