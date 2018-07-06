<?php
/**
 * 上游企业（供应商）管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BSupplierController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bcompanyaccount_model = D('BCompanyAccount');
		$this->bcompany_model = D('BCompany');
		$this->bsupplier_model = D("BSupplier");
	}

	/** 
	*	处理添加/修改所提交的数据
	*	@param string $action_name 判断是什么操作提交处理的数据 add 添加 | edit 编辑
	*	@return array 最终update|insert 所需要的数据
	*/
    private function handlePostData($action_name = 'add') {

    	$postdata = I('post.');

    	$main_data = array(
    		'company_id'=> get_company_id(),
    		'creator_id'=> get_user_id(),
			'create_time'=> time()
    	);

    	$c_id = 0;

    	if(isset($postdata['credit_code'])){
    		$c_id = $this->bcompany_model->getCompanyIdByCreditCode($postdata['credit_code']);
    	}

    	$data = array(
			// 'supplier_code'=> $postdata['supplier_code'],
			'company_name'=> $postdata['company_name'],
			'company_short_name'=> $postdata['company_short_name'],
			'contact_member'=> $postdata['contact_member'],
			'contact_phone'=> $postdata['contact_phone'],
			'legal_person'=> $postdata['legal_person'],
			'credit_code'=> $postdata['credit_code'],
			'phone'=> $postdata['phone'],
			'fax'=> $postdata['fax'],
			'business_area'=> $postdata['business_area'],
			'address'=> $postdata['address'],
			'comment'=> $postdata['comment'],
			'company_id'=> $c_id,
		);
		$data['attachment']=get_upload_pic($postdata['attachment'],$postdata['del_attachment_pic'],",");
		$data['supplier_phone']=$postdata['supplier_phone'];
		$data['supplier_email']=$postdata['supplier_email'];
		$data['supplier_licence']=get_upload_pic($postdata['supplier_licence'],$postdata['del_licence_pic'],",");
    	if(strtolower($action_name) == 'edit'){
    		// 去除编辑中不需要更改的字段
    		unset($main_data['company_id']);
    		unset($main_data['creator_id']);
    	}

    	$data = array_merge($data, $main_data);

    	return $data;
    }

    /** 
	*	锁定、解锁可复用通用update处理逻辑
	*	@param string type lock 锁定 | unlock 解锁
	*/
	private function toggleStatus($type = 'lock') {
		$info["status"] = "fail";

		$result = $this->bcompanyaccount_model->toggleStatus($type);
		if($result['status'] == 1){
			$info["status"] = "success";
            $info["url"] = U('BSupplier/index');

            $this->ajaxReturn($info);
        }else{
        	$this->error('操作失败！');
        }
	}

    // 上游企业 - 列表
    public function index() {
		$main_tbl = C('DB_PREFIX').'b_supplier';

		$searchdata = I("post.");

		$where = array(
			'ca.company_id'=> get_company_id(),
			'ca.deleted'=> 0
			// $main_tbl.".company_id"=> get_company_id(),
			// $main_tbl.".deleted"=> 0
		);

		// 搜索关键词
		if($searchdata["search_name"]){
			$where['ca.supplier_code|'.$main_tbl.'.company_name|ca.contact_member|ca.contact_phone'] = array("like", "%".trim($searchdata["search_name"])."%");
		}
		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_company_account as ca ON (ca.supplier_id = '.$main_tbl.'.id)';
		
		$field = $main_tbl.'.*, IFNULL(ca.price, 0.00) as debt_price, IFNULL(ca.weight, 0.00) as debt_weight';
		$field.= ', ca.contact_member, ca.contact_phone, ca.status, ca.price, ca.total_price, ca.settle_price, ca.weight, ca.total_weight, ca.settle_weight, ca.supplier_id, ca.supplier_code';

		$order_by = 'id DESC';

		$count = $this->bsupplier_model->countList($where, '*', $join);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.",".$page->listRows;

		$data = $this->bsupplier_model->getList($where, $field, $limit, $join, $order_by);

		foreach ($data as $k=>$v){
			$data[$k]["is_supply"] = $this->check_abled_to_remove($v) ? 0 : 1;
		}

		$this->assign("page", $page->show('Admin'));
		$this->assign("list", $data);

		// 是否管理员 - 暂时默认true
		// $is_admin = $this->is_admin();
		// $this->assign('is_admin', $is_admin);

       	$this->display();
    }

	// 上游企业 - 添加
	public function add() {
		$postdata = I("post.");
		// 点击提交处理
		if (!empty($postdata) && IS_POST) {

			$data = $this->handlePostData(ACTION_NAME);
            
            if(empty($postdata['supplier_code'])){
				$this->error("请填写该供应商的供应商编号！");
    			return;
			}
            if(empty($postdata['company_name'])){
                $this->error("请填写该供应商的公司名称！");
                return;
            }
            if(empty($postdata['contact_phone']) && empty($postdata['supplier_phone'])) {
                $this->error("公司电话和业务联系人电话必须选一个填！");
                return;
            }

            if (!empty($data['contact_phone'])){
                if(!check_mobile($data['contact_phone'])){
                    $this->error("错误的手机号格式！");
                    return;
                }
            }
            if (!empty($data['supplier_phone'])){
                if(!check_phone($data['supplier_phone'])){
                    $this->error("错误的电话号码格式！");
                    return;
                }
            }


			if(!empty($postdata['credit_code']) && $this->bsupplier_model->checkExistCreditCodeRecord($postdata['credit_code'])){

				$where = array(
					'credit_code'=> $postdata['credit_code']
				);
				$supplier_info = $this->bsupplier_model->getInfo($where);
    			 $this->error("您填写的社会信用代码已被使用！");
    			 return;
    		}
			if(!isset($supplier_info) && $this->bsupplier_model->checkExistSupplier_code($postdata['supplier_code'])){
				$this->error("您填写的供应商编号已被使用！");
			}

			$data['created_time'] = time();
			$new_supplier_id = isset($supplier_info) ? $supplier_info['id'] : $this->bsupplier_model->insert($data);

			if ($new_supplier_id !== false) {

				$where = array(
					'company_id'=> get_company_id(),
					'supplier_id'=> $new_supplier_id
				);
				$ca_info = $this->bcompanyaccount_model->getInfo($where);

				$update_state = false;
				if(!empty($ca_info)){
					if($ca_info['deleted'] == 0){
						$this->error("已存在与该供应商的结欠账户！");
					}else{
						$update_state = true;
					}
				}
				
				if($update_state){
					// 原有ca_info 记录，将 deleted = 1 改为 0
					$update_data = array(
						'supplier_code'=> $postdata['supplier_code'],
						'contact_member'=> $postdata['contact_member'],
						'contact_phone'=> $postdata['contact_phone'],
						'deleted'=> 0
					);

					$rs = $this->bcompanyaccount_model->update($where, $update_data);
				}else{
					// 创建新的 ca_info 记录
					$company_account_data = array(
						'company_id'=> $this->MUser["company_id"],
						'supplier_id'=> $new_supplier_id,
						'supplier_code'=> $postdata['supplier_code'],
						'contact_member'=> $postdata['contact_member'],
						'contact_phone'=> $postdata['contact_phone']
					);

					$rs = $this->bcompanyaccount_model->insert($company_account_data);

				}
				
				if($rs !== false){
					$this->success("添加成功！", U("BSupplier/index"));
				}
			} else {
				$this->error("添加失败！");
			}
		}else{
			$this->display();
		}
	}

	// 上游企业 - 编辑
	public function edit() {

		// die('关闭此功能');

		$postdata = I("post.");

		if(!empty($postdata) && IS_POST) {

			$where = array(
				"id"=> $postdata["id"]
			);

			$update_data = $this->handlePostData(ACTION_NAME);

            if(empty($postdata['supplier_code'])){
                $this->error("请填写该供应商的供应商编号！");
                return;
            }
            if(empty($postdata['company_name'])){
                $this->error("请填写该供应商的公司名称！");
                return;
            }
            if(empty($postdata['contact_phone']) && empty($postdata['supplier_phone'])) {
                $this->error("公司电话和业务联系人电话必须选一个填！");
                return;
            }

            if (!empty($update_data['contact_phone'])) {
                if (!check_mobile($update_data['contact_phone'])) {
                    $this->error("错误的手机号格式！");
                    return;
                }
            }
            if (!empty($update_data['supplier_phone'])){
                if(!check_phone($update_data['supplier_phone'])){
                    $this->error("错误的电话号码格式！");
                    return;
                }
            }

			$field = 'id, credit_code';
			$supplier_info = $this->bsupplier_model->getInfo($where, $field);
			if(!empty($postdata['credit_code']) && $this->bsupplier_model->checkExistCreditCodeRecord($postdata['credit_code'], $supplier_info['id'])){
    			$this->error("您填写的社会信用代码已被使用！");
    			return;
    		}

			M()->startTrans();

			$BSectors = $this->bsupplier_model->update($where, $update_data);
			$where = array(
				'company_id'=> get_company_id(),
				'supplier_id'=> $postdata["id"]
			);
			$update_data = array(
				'supplier_code'=> $postdata['supplier_code'],
				'contact_member'=> $postdata['contact_member'],
				'contact_phone'=> $postdata['contact_phone'],
			);
			$rs = $this->bcompanyaccount_model->update($where, $update_data);

			if ($BSectors !== false && $rs !== false) {
				M()->commit();
				$this->success("编辑成功！", U("BSupplier/index"));
			} else {
				M()->rollback();
				$this->error("编辑失败！");
			}
		}else{
			$main_tbl = C('DB_PREFIX').'b_supplier';

			$where = array(
				'ca.company_id'=> get_company_id(),
				$main_tbl.'.id'=> I("id/d", 0)
			);

			$join = ' LEFT JOIN '.C('DB_PREFIX').'b_company_account as ca ON (ca.supplier_id = '.$main_tbl.'.id)';
			
			$field = $main_tbl.'.*';
			$field.= ', ca.contact_member, ca.contact_phone, ca.status, ca.price, ca.total_price, ca.settle_price, ca.weight, ca.total_weight, ca.settle_weight, ca.supplier_id, ca.supplier_code';

			$data = $this->bsupplier_model->getInfo($where, $field, $join);
//            echo $this->bsupplier_model->getLastSql();

			$this->assign("data", $data);
			$this->display();
		}
	}

	// 查看该供应商是否能删除
	private function check_abled_to_remove($ca_info){
		$rs = false;

		$rs = !($ca_info['price'] > 0);

		$rs = $rs && !($ca_info['total_price'] > 0);
		$rs = $rs && !($ca_info['settle_price'] > 0);

		$rs = $rs && !($ca_info['weight'] > 0);
		$rs = $rs && !($ca_info['total_weight'] > 0);
		$rs = $rs && !($ca_info['settle_weight'] > 0);

		// 发生过采购关系
		$where = array(
			'company_id'=> $ca_info['company_id'],
			'supplier_id'=> $ca_info['supplier_id'],
			'deleted'=> 0
		);
		$cc = D("BProcurement")->where($where)->count();
		$rs = $rs && !($cc > 0);
		// if(!$rs){die('1');}

		$where = array(
			'company_id'=> get_company_id(),
			'account_id'=> $ca_info['id'],
			'deleted'=> 0
		);

		// 买卖金
		$cc = D('BMaterialOrder')->where($where)->count();
		$rs = $rs && !($cc > 0);
		// if(!$rs){die('2');}

		// 来往料
		$cc = D('BMaterialRecord')->where($where)->count();
		$rs = $rs && !($cc > 0);
		// if(!$rs){die('3');}

		// 来往钱
		$cc = D('BCaccountRecord')->where($where)->count();
		$rs = $rs && !($cc > 0);
		// if(!$rs){die('4');}

		// 结算单
		unset($where['account_id']);
		$where['supplier_id'] = $ca_info['supplier_id'];
		$cc = D('BProcureSettle')->where($where)->count();
		$rs = $rs && !($cc > 0);
		// if(!$rs){die('5');}

		return $rs;
	}

	// 上游企业 - 删除 deleted => 1
	public function deleted() {
		$rs = false;

		$postdata = I("");

		$update_data = array(
			'deleted'=> 1
		);

		// 解锁状态才可以删除，避免绕过前端判断status直接跑url进来
		$where = array(
			"supplier_id" => $postdata["id"], 
			"company_id" => get_company_id(),
			'status'=> 1
		);

		$ca_info = $this->bcompanyaccount_model->getInfo($where);
		if(!empty($ca_info)){
			$rs = $this->check_abled_to_remove($ca_info);
			if($rs === true){
				$rs = $this->bcompanyaccount_model->update($where, $update_data);	
			}
		}

		if($rs !== false){
			$this->success("删除成功！");
		}else{
			$this->error("删除失败！");
		}
	}

	// 上游企业 - 锁定 status => 0
	public function lock() {
		$this->toggleStatus(ACTION_NAME);
	}

	// 上游企业 - 解锁 status => 1
	public function unlock() {
		$this->toggleStatus(ACTION_NAME);
	}

	// 查看
	public function detail(){
		$main_tbl = C('DB_PREFIX').'b_supplier';

		$where = array(
			'ca.company_id'=> get_company_id(),
			$main_tbl.'.id'=> I("id/d", 0)
		);

		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_company_account as ca ON (ca.supplier_id = '.$main_tbl.'.id)';
		
		$field = $main_tbl.'.*, IFNULL(ca.price, 0.00) as debt_price, IFNULL(ca.weight, 0.00) as debt_weight';
		$field.= ', ca.contact_member, ca.contact_phone, ca.status, ca.price, ca.total_price, ca.settle_price, ca.weight, ca.total_weight, ca.settle_weight, ca.supplier_id, ca.supplier_code';

		$data = $this->bsupplier_model->getInfo($where, $field, $join);

		$this->assign("info", $data);
		$this->assign("supplier_id",$data['id']);
		$this->display();
	}
	// 附件上传 - ajax
	public function upload_attachment_pic(){
		$year=date("Y",time());
		$dir_path = 'attachment/'.$year.'/';
		$info = upload_pic_by_count($dir_path,$_FILES['upload_attachment_pic'],'file',I('post.del_attachment_pic'));
		die(json_encode($info));
	}
	// 营业执照上传 - ajax
	public function upload_licence_pic(){
		$year=date("Y",time());
		$dir_path = 'licence/'.$year.'/';
		$info = upload_pic_by_count($dir_path,$_FILES['upload_licence_pic'],'image',I('post.del_licence_pic'));
		die(json_encode($info));
	}
	public function account_info(){
	    $supplier_id=I('get.id');
	    if(!empty($supplier_id)){
	        $type=trim(I('type'));
	        switch($type){
	            case 'procure':
	                $this->_procure_list($supplier_id);
	                break;
	            case 'return':
	                $this->_return_list($supplier_id);
	                break;
	            case 'settle':
	                $this->_settle_list($supplier_id);
	                break;
	            case 'flow':
	                $this->_account_flow($supplier_id);
	                break;
	            default :
	                $this->_procure_list($supplier_id);
	                break;
	        }
	        $this->display();
	    }else{
	        $this->error('参数错误！');
	    }
	}
	/**
	 * @author lzy 2018-6-26
	 * 获取供应商的采购信息
	 * @param int $supplier_id 供应商id
	 */
	private function _procure_list($supplier_id){
        $model=D('BProcurement');
        $company_id=get_company_id();
        $condition=array(
            'procure.company_id'=>$company_id,
            'procure.supplier_id'=>$supplier_id,
            'procure.status'=>1,
            'procure.deleted'=>0
        );
        $field="procure.id,supplier.company_name,procure.id,procure.batch,procure.pricemode,procure.num,procure.weight,procure.fee,procure.price,procure.status,procure.procure_time,procure.create_time,employee.employee_name";
        $join=' join gb_b_employee employee on employee.user_id=procure.creator_id';
        $join.=' join gb_b_supplier supplier on supplier.id=procure.supplier_id';
        $count = $model->alias('procure')->countList($condition, $field, $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.",".$page->listRows;
        $procure_list = $model->alias('procure')->getList($condition, $field, $limit, $join, 'procure.id desc');
        $this->b_show_status('b_procurement');
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $this->assign('procure_list',!empty($procure_list)?$procure_list:null);
	}
	/**
	 * @author lzy 2018-6-26
	 * 获取供应商的退货信息
	 * @param int $supplier_id 供应商id
	 */
	private function _return_list($supplier_id){
        $model=D('BProcureReturn');
        $company_id=get_company_id();
        $condition=array(
            'returnd.company_id'=>$company_id,
            'returnd.supplier_id'=>$supplier_id,
            'returnd.status'=>1,
            'returnd.deleted'=>0
        );
        $field="returnd.id,supplier.company_name,returnd.batch,returnd.num,returnd.price,employee.employee_name,returnd.status,returnd.return_time,returnd.create_time";
        $join=' join gb_b_employee employee on employee.user_id=returnd.creator_id';
        $join.=' join gb_b_supplier supplier on supplier.id=returnd.supplier_id';
        $count = $model->alias('returnd')->countList($condition, $field, $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.",".$page->listRows;
        $return_list = $model->alias('returnd')->getList($condition, $field, $limit, $join, 'returnd.id desc');
        $this->b_show_status('b_procure_return');
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $this->assign('return_list',!empty($return_list)?$return_list:null);
	}
	/**
	 * @author lzy 2018-6-26
	 * 获取供应商的结算信息
	 * @param int $supplier_id 供应商id
	 */
	private function _settle_list($supplier_id){
        $model=D('BProcureSettle');
        $company_id=get_company_id();
        $condition=array(
            'settle.company_id'=>$company_id,
            'settle.supplier_id'=>$supplier_id,
            'settle.status'=>array('in','1,4'),
            'settle.deleted'=>0
        );
        $field="settle.id,supplier.company_name,settle.batch,ca_record.price,m_order.weight as o_weight,settle.after_weight,settle.after_price,settle.settle_time,settle.create_time,settle.status";
        $join=' join gb_b_employee employee on employee.user_id=settle.creator_id';
        $join.=' join gb_b_supplier supplier on supplier.id=settle.supplier_id';
        $join.=' left join gb_b_caccount_record ca_record on ca_record.sn_id=settle.id and ca_record.type=1';
        $join.=' left join gb_b_material_order m_order on m_order.sn_id=settle.id and m_order.type=1';
    //  $join.=' left join gb_b_material_record m_record on m_record.sn_id=settle.id and m_record.type=1';
        $count = $model->alias('settle')->countList($condition, $field, $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.",".$page->listRows;
        $settle_list = $model->alias('settle')->getList($condition, $field, $limit, $join, 'settle.id desc');
        if(!empty($settle_list)){
            foreach($settle_list as $key => $val){
                $settle_list[$key]['price']=$val['price']>0?$val['price']:0;
                $m_order_list=D('b_material_record')->getList(array('sn_id'=>$val['id'],'type'=>1));
                if(!empty($m_order_list)){
                    $settle_list[$key]['rf_weight']=0;
                    $settle_list[$key]['rt_weight']=0;
                    foreach ($m_order_list as $key =>$val){
                        if($val['weight']<0){
                            $settle_list[$key]['rf_weight']=$val['weight'];
                        }else{
                            $settle_list[$key]['rt_weight']=$val['weight'];
                        }
                        
                    }
                }
            }
        }
        $this->b_show_status('b_procure_settle');
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $this->assign('settle_list',!empty($settle_list)?$settle_list:null);
	}
	/**
	 * @author lzy 2018-6-26
	 * 获取供应商的结欠流水
	 * @param int $supplier_id 供应商id
	 */
	private function _account_flow($supplier_id){
        $model=D('BCompanyAccountFlow');
        $company_id=get_company_id();
        $condition=array(
            'flow.company_id'=>$company_id,
            'account.supplier_id'=>$supplier_id
        );
        $field="flow.*,procure.batch as pro_batch,procure.id as procure_id,procure.check_time as pro_check_time,
            returnd.batch as ret_batch,returnd.id as return_id,returnd.check_time as ret_check_time,
            ca_settle.batch as ca_batch,ca_settle.id as ca_settle_id,ca_settle.check_time as ca_check_time,
            mo_settle.batch as mo_batch,mo_settle.id as mo_settle_id,mo_settle.check_time as mo_check_time,
            mr_settle.batch as mr_batch,mr_settle.id as mr_settle_id,mr_settle.check_time as mr_check_time";
        $join=" join gb_b_company_account account on account.id=flow.account_id";
        $count = $model->alias('flow')->countList($condition,'*',$join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.",".$page->listRows;
        $join.=" left join gb_b_procurement procure on procure.id=flow.sn_id and flow.sn_type=1";
        $join.=" left join gb_b_procure_return returnd on returnd.id=flow.sn_id and flow.sn_type=3";
        $join.=" left join gb_b_caccount_record ca_record on ca_record.id=flow.sn_id and flow.type=2 and flow.sn_type=2";
        $join.=" left join gb_b_procure_settle ca_settle on ca_settle.id=ca_record.sn_id";
        $join.=" left join gb_b_material_order m_order on m_order.id=flow.sn_id and flow.type=4 and flow.sn_type=2";
        $join.=" left join gb_b_procure_settle mo_settle on mo_settle.id=m_order.sn_id";
        $join.=" left join gb_b_material_record m_record on m_record.id=flow.sn_id and flow.type=3 and flow.sn_type=2";
        $join.=" left join gb_b_procure_settle mr_settle on mr_settle.id=m_record.sn_id";
        $flow_list = $model->alias('flow')->getList($condition, $field, $limit, $join, 'flow.id desc');
        foreach($flow_list as $key => $val){
            if($val['sn_type']==1){
                $flow_list[$key]['batch']=$val['pro_batch'];
                $flow_list[$key]['jump_id']=$val['procure_id'];
                $flow_list[$key]['jump_url']=U('BProcure/detail',array('id'=>$val['procure_id']));
                $flow_list[$key]['create_time']=$val['pro_check_time'];
                $flow_list[$key]['type_name']='采购';
            }elseif($val['sn_type']==2){
                if($val['type']==2){
                    $flow_list[$key]['batch']=$val['ca_batch'];
                    $flow_list[$key]['jump_id']=$val['ca_settle_id'];
                    $flow_list[$key]['jump_url']=U('BSettlement/detail',array('id'=>$val['ca_settle_id']));
                    $flow_list[$key]['create_time']=$val['ca_check_time'];
                    if($val['price']>0){
                        $flow_list[$key]['type_name']='结算付款';
                    }else{
                        $flow_list[$key]['type_name']='结算收款';
                    }
                }elseif($val['type']==3){
                    $flow_list[$key]['batch']=$val['mr_batch'];
                    $flow_list[$key]['jump_id']=$val['mr_settle_id'];
                    $flow_list[$key]['jump_url']=U('BSettlement/detail',array('id'=>$val['mr_settle_id']));
                    $flow_list[$key]['create_time']=$val['mr_check_time'];
                    if($val['weight']>0){
                        $flow_list[$key]['type_name']='结算来料';
                    }else{
                        $flow_list[$key]['type_name']='结算去料';
                    }
                }elseif($val['type']==4){
                    $flow_list[$key]['batch']=$val['mo_batch'];
                    $flow_list[$key]['jump_id']=$val['mo_settle_id'];
                    $flow_list[$key]['jump_url']=U('BSettlement/detail',array('id'=>$val['mo_settle_id']));
                    $flow_list[$key]['create_time']=$val['mo_check_time'];
                    if($val['weight']>0){
                        $flow_list[$key]['type_name']='结算买料';
                    }else{
                        $flow_list[$key]['type_name']='结算卖料';
                    }
                }
            }elseif($val['sn_type']==3){
                $flow_list[$key]['batch']=$val['ret_batch'];
                $flow_list[$key]['jump_id']=$val['return_id'];
                $flow_list[$key]['jump_url']=U('BSellReturn/detail',array('return_id'=>$val['return_id']));
                
                $flow_list[$key]['type_name']='退货';
            }
        }
        $this->b_show_status('b_company_account_flow');
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $this->assign('flow_list',!empty($flow_list)?$flow_list:null);//die(var_dump($flow_list));
	}
}

