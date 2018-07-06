<?php
/**
*	
*/
namespace Business\Model;

use Business\Model\BCommonModel;

class BProcurementModel extends BCommonModel {
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//提交
    const REVOKE              =  4;//撤销
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝
	const CHECK_REJECT        =  7;//提交驳回
    
    /*操作状态名*/
    const CREATE_NAME         =  '创建表单';
    const SAVE_NAME           =  '保存表单';
    const COMMIT_NAME         =  '提交表单';
    const REVOKE_NMAE         =  '撤销表单';
    const CHECK_PASS_NMAE     =  '审批通过';
    const CHECK_DENY_NAME     =  '审批拒绝';
	const CHECK_REJECT_NAME   =  '提交驳回';
    
    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';
    
    /*操作函数名*/
    const CHECK_FUNCTION     =  'business/bprocure/check';
    

	public $model_expence_sub;

	public function __construct() {
		parent::__construct();
	}

	public function _initialize() {
		parent::_initialize();
        $this->model_expence_sub = D('BExpenceSub');
	}

	protected $_validate = array(

		array('company_id', 'require', '商户id缺失！', 1, 'regex', BCommonModel:: MODEL_INSERT),

        array('supplier_id', 'require', '供应商id缺失！', 1, 'regex', BCommonModel:: MODEL_INSERT),
    );

	// 更改 procurement.status 已经不用
	public function set_status_by_where($where = array(), $status = 1, $from_status = 0){
		$info = array('status'=> 1, 'msg'=> '');
		$rs = false;
		$status = intval($status);
		$from_status = intval($from_status);
		if(!empty($where)){
			$datalist = $this->getList($where);
			$procure_id_arr = array();
			foreach ($datalist as $key => $value) {
				$procure_id_arr[] = $value['id'];

				if($value['status'] != $from_status){
					$info['status'] = 0;
					$info['msg'] .= '采购单['.$value['batch'].']状态异常！';
				}
			}
			if($info['status'] == 1){
				$this->startTrans();

				$update_data = array(
					'status'=> $status
				);
				$rs = $this->update($where, $update_data);
				if($rs === false){
					$info['status'] = 0;
					$info['msg'] = '更新状态失败！';

					$this->rollback();
				}
			}
		}else{
			$info['status'] = 0;
			$info['msg'] = '非法参数！';
		}
		return $info;
	}

	// 通过 batch 获取procurement 记录的工费一口价总额，
	public function get_info_with_total_storage_by_batch($batch = NULL){
		$main_tbl = C('DB_PREFIX').'b_procurement';

		$where = array('batch' => $batch);

		$field = $main_tbl.'.*, s1.total_fee_price';

		// $sub = D('BProcureStorage')->field('procurement_id, SUM(fee * weight) as total_fee_price')->group('procurement_id')->select(false);
		$sub = D('BProcureStorage')->field('procurement_id, SUM(price) as total_fee_price')->group('procurement_id')->select(false);

		$join = ' LEFT JOIN ('.$sub.')as s1 ON (s1.procurement_id = '.$main_tbl.'.id)';

		$info = $this->getInfo($where, $field, $join);

		return $info;
	}

	// 导出 excel
    public function excel_out($where, $field, $join, $page = 1){

    	$num = 1000;
    	$limit = ($page - 1) * $num .','.$num;

    	$data_list = $this->getList($where, $field, $limit, $join);

        if($data_list){

            register_shutdown_function(array(&$this, 'excel_out'), $where, $field, $join, $page + 1);

            $title_arr = array(
            	'序'=> '',
            	'采购单号'=> 'batch',
            	'供应商'=> 'company_name',
            	'采购类型'=> 'show_pricemode',
            	'采购数量/件'=> 'num',
            	'采购重量/克'=> 'weight',
            	'采购金额'=> 'price',
            	'采购人员'=> 'creator_name',
            	'状态'=> 'show_status',
            	'采购日期'=> 'show_create_time'
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
						if($value['pricemode']==1){
							if($v=="num"){
								$row[$v]="--";
							}
						}else{
							if($v=="weight"){
								$row[$v]="--";
							}
						}
                	}
                }

                $content .= implode(",", $row) . "\n";
            }

            header("Content-Disposition: attachment; filename=采购单打印.csv");

            echo $content;
        }        
    }

    // 操作审核采购单
    public function check_procure(){
    	$info = array('status'=> 1, 'msg'=> '');

    	$postdata = I('post.');

    	$id = I('post.id/d', 0);
    	$check_memo = I('post.check_memo/s');

    	$where = array('id'=> $id);
    	$procure_info = $this->getInfo($where);

    	if($procure_info['status'] != 0){
    		$info['status'] = 0;
    		$info['msg'] = '采购单状态出错！';
    	}else{

    		$update_data = array(
    			'check_id'=> get_user_id(),
    			'check_time'=> time(),
    			'check_memo'=> $check_memo,
    		);
			$update_data['status'] =$postdata['type'];

	    	M()->startTrans();

	    	$rs = $this->update($where, $update_data);
	    	if($update_data['status']==1){
	    	    /*添加表单操作记录 add by lzy 2018.5.26 start*/
	    	    $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$id,self::CHECK_PASS);
	    	    /*添加表单操作记录 add by lzy 2018.5.26 end*/
	    	}else if($update_data['status']==2){
	    	    /*添加表单操作记录 add by lzy 2018.5.26 start*/
	    	    $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$id,self::CHECK_DENY);
	    	    /*添加表单操作记录 add by lzy 2018.5.26 end*/
	    	}else if($update_data['status']==-2){
				/*添加表单驳回操作记录 add by chenzy 2018.5.31 start*/
				$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$id,self::CHECK_REJECT);
				/*添加表单驳回操作记录 add by chenzy 2018.5.31 end*/
			}
	    	
	    	if($rs === false&&$record_result){
	    		M()->rollback();

	    		$info['status'] = 0;
	    		$info['msg'] = '审核失败！';
	    	}else{
				$where = array(
					'procurement_id'=> $id,
					'deleted'=> 0,
				);
				$storage_info = D('Business/BProcureStorage')->getInfo($where);
	    		if($update_data['status'] == 1 ){
	    			// 审核通过
	    			if($procure_info['pricemode'] == 0){
						/*change by alam 2018/5/11 start*/
				        $sub_list = $this->model_expence_sub->getSublist(array('ticket_id' => $procure_info['id'], 'ticket_type' => 2));
						/*change by alam 2018/5/11 end*/
						$rs = true;
						if(empty($storage_info) && empty($sub_list)){
							M()->rollback();
							$info['status'] = 0;
							$info['msg'] = '采购单数据有误！';
						} elseif(!empty($storage_info)) {
							if($storage_info['type'] == 2){
								$where = array(
									'order_id'=> $storage_info['id'],
									'type'=> 2,
									'deleted'=> 0,
									'status'=> 1
								);
								$update_data = array('status'=> 2);
								$rs = D('Business/BRecoveryProduct')->update($where, $update_data);//采购审核通过，更新金料状态为在库
							}else{
								$where = array();
								$where['storage_id'] = $storage_info['id'];
								$where['deleted'] = 0;
								$where['status'] = 1;
								$p_list = D("Business/BProduct")->getList($where);
								if($p_list){
									$data = array("status"=> 2);
									$rs = D("Business/BProduct")->update($where, $data);
								}else{
									$rs = false;
								}
							}
						}
	    			}

	    			if($rs !== false){
						$ca_where = array(
							'company_id'=> get_company_id(),
							'supplier_id'=> $procure_info['supplier_id'],
							'deleted'=> 0
						);

						$ca_info = D('Business/BCompanyAccount')->getInfo($ca_where);
						if(empty($ca_info)){
							M()->rollback();

							$info['status'] = 0;
							$info['msg'] = '结欠账户数据有误！';
						}

						// 计重采购使用工费记入结欠，计价采购结欠只与金额有关
						$calc_price = ($procure_info['pricemode'] == 0) ? $procure_info['price'] : $procure_info['fee'];
						$calc_weight = ($procure_info['pricemode'] == 0) ? 0 : $procure_info['gold_weight'];
						$update_ca = array(
							'price'=> decimalsformat($ca_info['price'], 2) + $calc_price,
							'total_price'=> decimalsformat($ca_info['total_price'], 2) + $calc_price,
							'weight'=> decimalsformat($ca_info['weight'], 2) + $calc_weight,
							'total_weight'=> decimalsformat($ca_info['total_weight'], 2) + $calc_weight,
						);

						$rs = D('Business/BCompanyAccount')->update($ca_where, $update_ca);
					}
					
					if($rs !== false){
						// 计重采购使用工费记入结欠，计价采购结欠只与金额有关
						$flow_weight = bcsub(0, (($procure_info['pricemode'] == 0) ? 0 : $procure_info['gold_weight']), 2);
						$flow_price = bcsub(0, (($procure_info['pricemode'] == 0) ? $procure_info['price'] : $procure_info['fee']), 2);
						$rs = D('Business/BCompanyAccountFlow')->add_flow($ca_info['id'], $procure_info['id'], $flow_weight, $flow_price, 1, 1);
					}

					if($rs !== false){
						M()->commit();
					}else{
						M()->rollback();

						$info['status'] = 0;
						$info['msg'] = '审核失败！';
					}
	    		}else{
					// 审核不通过
					if($storage_info['type'] == 2){
						$where = array(
							'order_id'=> $storage_info['id'],
							'type'=> 2,
							'deleted'=> 0,
							'status'=> 1
						);
						$update_data = array('status'=>0);
						$rs = D('Business/BRecoveryProduct')->update($where, $update_data);//更改金料状态为未入库
					}
					if($rs !== false){
						M()->commit();
					}else{
						M()->rollback();
						$info['status'] = 0;
						$info['msg'] = '审核失败！';
					}
	    		}
	    	}
    	}
    	return $info;
    }

    // 采购单结算完成 - status = 4
    public function settle_finished_by_settle_id(){
    	$info = array('status'=> 1, 'msg'=> '');
    	
    	$postdata = I('post.');

    	$settle_id = $postdata['id'];

    	$where = array('procure_settle_id'=> $settle_id);
    	$update_data = array('status'=> 4);

    	$this->update($where, $update_data);

    }

    // 采购单删除  - deleted = 1
    public function deleted(){
    	$info = array('status'=> 1, 'msg'=> '');

    	$procure_id = I('get.id/d', 0);

    	$update_data = array('deleted'=> 1);

    	$where = array('id'=> $procure_id,'company_id'=>get_company_id(),'creator_id'=>get_user_id());
		$procure_detail = $this->getInfo($where);
		//状态为保存，撤销，驳回才可以编辑
		if(!empty($procure_detail)&&in_array($procure_detail['status'],array(-1,-2,3))) {
			M()->startTrans();

			$rs = $this->update($where, $update_data);
			if ($rs !== false) {
				$rsData = D('BProcureStorage')->delete_storage_by_procure_id($procure_id);
				if ($rsData['status'] == 1) {

				} else {
					$info = $rsData;
				}
			} else {
				$info['status'] = 0;
				$info['msg'] = '操作失败！';
				$info['info'] = '操作失败！';
			}

			if ($info['status'] == 1) {
				M()->commit();
			} else {
				M()->rollback();
			}
		}else{
			$info['status'] = 0;
			$info['msg'] = '操作失败！';
			$info['info'] = '操作失败！';
		}
    	return $info;
    }

	// 采购单撤销 - status = 3
	public function cancel(){
		$info = array('status'=> 1, 'msg'=> '');
		
		$procure_id = I('get.id/d', 0);

		$procure_storage_model = D('BProcureStorage');

		$where = array('id'=> $procure_id,'creator_id'=>get_user_id());
		$procure_info = D("BProcurement")->getInfo($where);
		if(empty($procure_info)||(!empty($procure_info)&&$procure_info['status']!=0)){
			$info['status'] = 0;
			$info['msg'] = '采购单状态出错！';
			return $info;
		}
		$this->startTrans();

		$update_data = array('status'=> 3);
		$rs = $this->update($where, $update_data);
		/*添加表单操作记录 add by lzy 2018.5.26 start*/
		$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$procure_id,self::REVOKE);
		/*添加表单操作记录 add by lzy 2018.5.26 end*/
		if($rs !== false&&$record_result){

			$where = array('procurement_id'=> $procure_id);
			$result = $procure_storage_model->set_status_by_where($where, 3);
			if($result['status'] == 1){
				$this->commit();
			}else{
				$info = $result;

				$this->rollback();
			}


			// $rsData = D('BProcureStorage')->delete_storage_by_procure_id($id);

			// if($rsData['status'] == 1){
			// 	$this->commit();
			// }else{
			// 	$this->rollback();

			// 	$info = $rsData;
			// }
		}else{
			$this->rollback();

			$info['status'] = 0;
			$info['msg'] = '操作失败！';
		}

		return $info;
	}

	// 获取对某供应商的采购单列表 - 结算
	public function get_list_by_supplier(){
		$main_tbl = C('DB_PREFIX').'b_procurement';

		$supplier_id = I('post.supplier_id/d', 0);

		$where = array (
			$main_tbl.'.supplier_id'=> $supplier_id,
			$main_tbl.'.status'=> 1,
			$main_tbl.'.procure_settle_id'=> 0,
			$main_tbl.'.company_id'=> get_company_id(),
			$main_tbl.'.deleted'=> 0
		);

		//$field = $main_tbl.'.*, s1.total_fee_price';//去除入库信息
		$field = $main_tbl.'.*';
		$field .= ', IF('.$main_tbl.'.pricemode, "计重", "计件")as show_pricemode';
		$field .= ', ifnull(cu.user_nicename,cu.mobile)as creator_name';
		$field .= ', (CASE '.$main_tbl.'.status 
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		)as show_status';

		$join = ' LEFT JOIN '.C('DB_PREFIX').'m_users as cu ON (cu.id = '.$main_tbl.'.creator_id)';

		//$sub = D('BProcureStorage')->field('procurement_id, SUM(price) as total_fee_price')->group('procurement_id')->select(false);//注释是为了去除入库信息
		//$join .= ' LEFT JOIN ('.$sub.')as s1 ON (s1.procurement_id = '.$main_tbl.'.id)';//注释是为了去除入库信息
		$datalist = $this->getList($where, $field, null, $join);
		return $datalist;
	}

	// 采购单修改 - 计重
	public function edit_procure(){
		$result = array('status' => '0', 'msg' => '修改采购单失败！');

		$postdata = I('post.');

		if(!empty($postdata)){
			$product_list = array();

			$where = array('id' => $postdata['procurement_id'], 'company_id' => get_company_id(), 'creator_id' => get_user_id());
			$procure_detail = $this->getInfo($where);
			// 状态为保存，撤销，审核不通过才可以编辑
			if(! empty($procure_detail) && in_array($procure_detail['status'], array(-1,-2,3))){
				$update_data = $this->get_update_data($product_list);

				$total_weight = 0.00;

				foreach ($product_list as $key => $value) {
					$total_weight += $value['weight'];
					$total_gold_weight += $value['gold_weight'];
				}

				$update_data_2 = array(
					// 'material_settle'=> $postdata['is_material'],
					// 'weight'=> $postdata['total_weight'],
					'weight' => decimalsformat($total_weight, 2),
					'gold_weight' => decimalsformat($total_gold_weight, 2)
				);

				$update_data = array_merge($update_data, $update_data_2);

				M()->startTrans();

				if ($postdata['type'] == "submit") {
					$update_data['status'] = 0;
					$rs = $this->update($where, $update_data);
					/*添加表单操作记录 add by lzy 2018.5.26 start*/
					$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$postdata['procurement_id'],self::COMMIT);
					/*添加表单操作记录 add by lzy 2018.5.26 end*/
				} else {
					$update_data['status'] =- 1;
					$rs = $this->update($where, $update_data);
					/*添加表单操作记录 add by lzy 2018.5.26 start*/
					$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$postdata['procurement_id'],self::SAVE);
					/*添加表单操作记录 add by lzy 2018.5.26 end*/
				}
				if($rs !== false&&$record_result) {
					/*change by alam 2018/5/11 start*/
			        // 处理其它费用
		            $insert_sub = $this->model_expence_sub->editList($postdata['procurement_id'], 2);
					$result['status'] = 1;
					if ($insert_sub !== false && !empty($product_list)) {
						$result = D('BProcureStorage')->save_with_not_create_product($product_list, $procure_detail);
					}
					/*change by alam 2018/5/11 end*/
				}
			}
        }

        if ($result['status'] == 1) {
        	M()->commit();
        } else {
        	M()->rollback();
        }

        return $result;
	}

	// 采购单添加 - 计重
	public function add_procure() {
		$result = array('status' => '0', 'msg' => '添加采购单失败！');

		$postdata = I('post.');
		if(!empty($postdata)){
			$product_list = array();
        	$insert_data = $this->get_insert_data($product_list);

			$total_weight = 0.00;
			foreach ($product_list as $key => $value) {
				$total_weight += $value['weight'];
				$total_gold_weight += $value['gold_weight'];
			}
			$insert_data_2 = array(
				'pricemode'=> 1,
				'weight'=> decimalsformat($total_weight, 2),
				'gold_weight'=> decimalsformat($total_gold_weight, 2)
			);
			$insert_data = array_merge($insert_data, $insert_data_2);

			M()->startTrans();

			$new_procure_id = $this->insert($insert_data);
			/*添加表单操作记录 add by lzy 2018.5.26 start*/
			$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$new_procure_id,self::CREATE);
			/*添加表单操作记录 add by lzy 2018.5.26 end*/
			if($new_procure_id&&$record_result) {
				if (!empty($product_list)) {
					$procure_detail = $this->find($new_procure_id);
					$result = D('BProcureStorage')->save_with_not_create_product($product_list, $procure_detail);
				} else {
					$result['status'] = 1;
				}
			}

	        // 处理其它费用
            $insert_sub = $this->model_expence_sub->addList($new_procure_id, 2);
        }

        if($result['status'] == 1 && $insert_sub !== false) {
        	M()->commit();
        	$result['new_procure_id'] = $new_procure_id;

        } else {
        	M()->rollback();
        }

        return $result;
	}

	// 采购单修改 - 计件
	public function edit_num_procure(){
		$result = array('status'=> '0', 'msg'=> '修改采购单失败！');

		$postdata = I('post.');
		if(!empty($postdata)){
        	$product_list = array();

        	$where = array('id'=> $postdata['procurement_id'],'company_id'=>get_company_id(),'creator_id'=>get_user_id());
			$procure_detail = $this->getInfo($where);
			//状态为保存，撤销，驳回才可以编辑
			if(!empty($procure_detail)&&in_array($procure_detail['status'],array(-1,-2,3))){
				$update_data = $this->get_update_data($product_list);
				
				// 查核对应货品
				if($postdata['type'] == 2){
					$res = D('BRecoveryProduct')->product_check($product_list, $postdata['procurement_id']);
				}else{
					$res = D('BProduct')->product_check($product_list, $postdata['procurement_id']);
				}
				if($res['status'] == 0){
	                return $res;
				}else{
					$update_data_2 = array(
						'num'=> $postdata['num'],
					    'weight'=>$postdata['weight']>0?$postdata['weight']:0,
						'pricemode'=> 0
					);

					$update_data = array_merge($update_data, $update_data_2);

					$this->startTrans();

					$rs = $this->update($where, $update_data);
					if($update_data['status']==-1){
					    /*添加表单操作记录 add by lzy 2018.5.26 start*/
					    $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$postdata['procurement_id'],self::SAVE);
					    /*添加表单操作记录 add by lzy 2018.5.26 end*/
					}else if($update_data['status']==0){
					    /*添加表单操作记录 add by lzy 2018.5.26 start*/
					    $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$postdata['procurement_id'],self::COMMIT);
					    /*添加表单操作记录 add by lzy 2018.5.26 end*/
					}
					if($rs !== false&&$record_result) {
						/*change by alam 2018/5/11 start*/
				        // 处理其它费用
			            $insert_sub = $this->model_expence_sub->editList($postdata['procurement_id'], 2);
						$result['status'] = 1;
						if ($insert_sub !== false && !empty($product_list)) {
							if($postdata['type'] == 2){
								$result = D('BProcureStorage')->save_with_recovery_product($product_list, $procure_detail);
							}else{
								$result = D('BProcureStorage')->save_with_create_product($product_list, $procure_detail);
							}
						}
						/*change by alam 2018/5/11 end*/
					}
				}

				if($result['status'] == 1){
		        	$this->commit();
		        }else{
		        	$this->rollback();
		        }
			}
        }

        return $result;
	}

	// 采购单添加 - 计件
	public function add_num_procure(){
		$result = array('status'=> '0', 'msg'=> '添加采购单失败！');

		$postdata = I('post.');
        if(!empty($postdata)){
        	$product_list = array();

        	$insert_data = $this->get_insert_data($product_list);

			// 查核对应货品
			if($postdata['type'] == 2){
				$res = D('BRecoveryProduct')->product_check($product_list);
			}else{
				$res = D('BProduct')->product_check($product_list);
			}

			if($res['status'] == 0){
                return $res;
			}else{

				$insert_data_2 = array(
					'num'=> $postdata['num'],
				    'weight'=>$postdata['weight']>0?$postdata['weight']:0,
					'pricemode'=> 0
				);

				$insert_data = array_merge($insert_data, $insert_data_2);
				
				$this->startTrans();

				$new_procure_id = $this->insert($insert_data);
				/*添加表单操作记录 add by lzy 2018.5.26 start*/
				$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$new_procure_id,self::CREATE);
				/*添加表单操作记录 add by lzy 2018.5.26 end*/
				if($new_procure_id&&$record_result) {
					if (!empty($product_list)) {
						$procure_detail = $this->find($new_procure_id);
						if ($postdata['type'] == 2) {
							$result = D('BProcureStorage')->save_with_recovery_product($product_list, $procure_detail);
						} else {
							$result = D('BProcureStorage')->save_with_create_product($product_list, $procure_detail);
						}
					} else {
						$result['status'] = 1;
					}
				}

				/*change by alam 2018/5/11 start*/
		        // 处理其它费用
	            $insert_sub = $this->model_expence_sub->addList($new_procure_id, 2);
				/*change by alam 2018/5/11 end*/
			}

			if($result['status'] == 1){
	        	$this->commit();

	        	$result['new_procure_id'] = $new_procure_id;
	        }else{
	        	$this->rollback();
	        }
        }

        return $result;
	}

	// 处理添加表单提交的数据，并返回插入数据数组
	private function get_insert_data(&$product_list = NULL){
		$postdata = I('post.');

		$product_list = $postdata['data'];
        if(empty($product_list)){
            $product_list = array();
        }

        $upload_img_arr = I('upload_img_arr/s');
		$upload_img_arr = explode('|', $upload_img_arr);

		$remove_img_arr = I('remove_img_arr/s');
		$remove_img_arr = explode('|', $remove_img_arr);

		$upload_img_arr = array_diff($upload_img_arr, $remove_img_arr);

		foreach($remove_img_arr as $img){
			$dir_path = str_replace('http://' . $_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT'], $img);
        	@unlink($dir_path);
		}

		$postdata['bill_pic'] = implode(',', $upload_img_arr);

        $nowtime = time();
		$insert_data = array(
			'company_id' => get_company_id(),
			'procure_time' => isset($postdata['procure_time']) ? strtotime($postdata['procure_time']) : $nowtime,
			'supplier_id' => $postdata['supplier_id'],
			'creator_id' => get_user_id(),
			'create_time' => $nowtime,
			'memo' => $postdata['memo'],
			'batch' => b_order_number('BProcurement', 'batch'),
			'bill_pic' => $postdata['bill_pic'],
			'fee' => decimalsformat($postdata['fee'], 2),
			'price' => decimalsformat($postdata['price'], 2),
		    'extra_price' => decimalsformat($postdata['extra_price'], 2),
			'status' => $postdata['submit_type'] == 'submit' ? 0 : -1
		);

		return $insert_data;
	}

	// 处理修改表单提交的数据，并返回插入数据数组
	private function get_update_data(&$product_list = NULL){
		$unset_field = array('batch', 'creator_id', 'create_time');

		$update_data = $this->get_insert_data($product_list);

		foreach ($unset_field as $value) {
			if(isset($update_data[$value])){
				unset($update_data[$value]);
			}
		}

		return $update_data;
	}

	public function getProcureInfo($condition){
		$procure_info=$this->alias('jb_procurement')->field('jb_procurement.*,jb_warehouse.wh_name,jb_supplier.company_name')
			->join('left join '.C('DB_PREFIX').'warehouse as jb_warehouse on jb_procurement.wh_id=jb_warehouse.id left join '.C('DB_PREFIX').'supplier as jb_supplier on jb_supplier.id=jb_procurement.supplier_id')
			->where($condition)->find();
		$procure_info['supplier_id']=(int)$procure_info['supplier_id'];
		$procure_info['wh_id']=(int)$procure_info['wh_id'];
		$user_info=M('users')->where('id='.$procure_info['p_user'])->find();
		$procure_info['user_nicename']=$user_info['user_nicename'];
		//计价信息
		$product_info=D('product')->getProductInfo(array('procurement_id'=>$procure_info['id']));
		$procure_info['price_mode']=$product_info['price_mode'];
		$procure_info['single_time']=date('Y-m-d H:i:s',$procure_info['single_time']);
		$procure_info['buy_price']=$product_info['buy_price'];
		if($procure_info['check_man']>0){
			$user_info=M('users')->where('id='.$procure_info['check_man'])->find();
			$procure_info['check_men']=$user_info['user_nicename'];
		}
		$procure_info['time']=date('Y-m-d',(int)$procure_info['time']);
		return $procure_info;
	}
	/**
	 * @author lzy 2018.5.26
	 * @param int $procure_id 采购单id
	 * @return 操作记录列表
	 */
	public function getOperateRecord($procure_id){
	    $condition=array(
	        'operate.company_id'=>get_company_id(),
	        'operate.type'=>BBillOpRecordModel::PROCURE,
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
			self::CHECK_REJECT=>self::CHECK_REJECT_NAME
	    );
	}
	/**
	 * @author lzy 2018.5.26
	 * 获取流程数组
	 */
	public function getProcess($procure_id){
	    $process_list=$this->_groupProcess();
	    if(!empty($procure_id)){
	        $condition=array(
	            'procure.id'=>$procure_id,
	        );
	        $field='procure.*,create_employee.employee_name as creator_name,check_employee.employee_name as check_name';
	        $join='left join gb_b_employee create_employee on procure.creator_id=create_employee.user_id and create_employee.company_id='.get_company_id();
	        $join.=' left join gb_b_employee check_employee on procure.check_id=check_employee.user_id and check_employee.company_id='.get_company_id();
	        $procure_info=$this->alias("procure")->getInfo($condition,$field,$join);
	        $process_list[self::CREATE_PROKEY]['is_done']=1;
	        $process_list[self::CREATE_PROKEY]['user_name']=$procure_info['creator_name'];
	        $process_list[self::CREATE_PROKEY]['time']=$procure_info['create_time'];
	        /*检查是否审核*/
	        if($procure_info['check_id']>0&&($procure_info['status']==1||$procure_info['status']==2)){
	            $process_list[self::CHECK_PROKEY]['is_done']=1;
	            $process_list[self::CHECK_PROKEY]['user_name']=$procure_info['check_name'];
	            $process_list[self::CHECK_PROKEY]['time']=$procure_info['check_time'];
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
	        self::CHECK_PROKEY=>array(
	            'process_name'=>self::CHECK_PROCESS,
	        )
	    );
	}

	// 回溯程序
	
	// 采购表数据结构增加fee（采购总工费）字段，将price字段数据copy的同时，修正price字段的值为 (金价乘以金重 + 工费) * n - 抹零金额 + 其它费用总和
	function refresh_price()
	{
		$model_procure_storage = D('BProcureStorage');
		// 计重采购订单
		$condition = array(
			'pricemode' => 1
		);
		$procure_list = $this->getList($condition);
		M()->startTrans();
		$res = true;
		foreach ($procure_list as $k => $v) {
			if ($res !== false) {
				$condition = array(
					'procurement_id' => $v['id'],
					'deleted' => 0
				);
				$storage_list = $model_procure_storage->getList($condition);
				$price = 0;
				foreach($storage_list as $key => $value) {
					$price = bcadd($price, bcmul(bcadd($value['fee'], $value['gold_price'], 4), $value['weight'], 4), 4);
				}

				// 其它费用总和
				$condition = array(
					'ticket_id' => $v['id'],
					'ticket_type' => 2,
					'deleted' => 0
				);
				$expence = D('BExpenceSub')->field('sum(cost) as cost')->where($condition)->find();
				$expence = empty($expence['cost']) ? 0 : $expence['cost'];
				$price = bcadd($price, $expence, 4);
				
				$price = round(bcsub($price, $v['extra_price'], 4), 2);

				$res = $this->execute("UPDATE `gb_b_procurement` SET `price`='{$price}' WHERE `id` = '{$v['id']}' AND `pricemode` = '1'");
			}
		}
		if ($res !== false) {
			M()->commit();
			die(':)');
		}
		M()->rollback();
		die(':(');
	}
}