<?php
/**
 * 采购流程
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Business\Model\BBillOpRecordModel;
use Business\Model\BProcurementModel;

class BProcureController extends BusinessbaseController {
    
	public function _initialize() {
		parent::_initialize();
		$this->bprocurement_model= D('BProcurement');
		$this->bproduct_model = D('BProduct');
		$this->bprocurestorage_model = D('BProcureStorage');
		$this->bsupplier_model = D('BSupplier');
		$this->bgoods_model = D('BGoods');
		$this->bgoodsclass_model = D('BGoodsClass');
        $this->bexpence_model = D('BExpence');
        $this->bexpencesub_model = D('BExpenceSub');
        $this->agoodsclass_model = D('AGoodsClass');
	}

	// 处理表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL){

		$main_tbl = C('DB_PREFIX').'b_procurement';

		$where = array();

		if(I('request.status') !== ''){
			if(I('post.status') === '0'){
				$where[$main_tbl.'.status'] = 0;
				$where['s2.split_done'] = 0;
			}else{
				$where[$main_tbl.'.status'] = I('request.status');
			}
		}
		if(I('request.search_name/s')){
			$where[$main_tbl.'.batch|s.company_name|b_employee.employee_name'] = array('LIKE', '%'. trim(I('request.search_name/s')) .'%');
		}
		if(I('request.pricemode') !== ''){
			$where[$main_tbl.'.pricemode'] = I('request.pricemode');
		}

		if(I('request.begin_time')){
			$begin_time = I('request.begin_time') ? strtotime(I('request.begin_time')) : time();
			$begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
			$where[$main_tbl.'.create_time'] = array('gt', $begin_time);
		}

		if(I('request.end_time')){
			$end_time = I('request.end_time') ? strtotime(I('request.end_time')) : time();
			$end_time = strtotime(date('Y-m-d 23:59:59', $end_time));

			if(isset($begin_time)){
				$p1 = $where[$main_tbl.'.create_time'];
				unset($where[$main_tbl.'.create_time']);

				$where[$main_tbl.'.create_time'] = array($p1, array('lt', $end_time));
			}else{
				$where[$main_tbl.'.create_time'] = array('lt', $end_time);
			}
		}

		if(I('request.prcure_begin_time')){
			$p_begin_time = I('request.prcure_begin_time') ? strtotime(I('request.prcure_begin_time')) : time();
			$p_begin_time = strtotime(date('Y-m-d 00:00:00', $p_begin_time));
			$where[$main_tbl.'.procure_time'] = array('gt', $p_begin_time);
		}

		if(I('request.procure_end_time')){
			$p_end_time = I('request.procure_end_time') ? strtotime(I('request.procure_end_time')) : time();
			$p_end_time = strtotime(date('Y-m-d 23:59:59', $p_end_time));

			if(isset($p_begin_time)){
				$p1 = $where[$main_tbl.'.procure_time'];
				unset($where[$main_tbl.'.procure_time']);

				$where[$main_tbl.'.procure_time'] = array($p1, array('lt', $p_end_time));
			}else{
				$where[$main_tbl.'.procure_time'] = array('lt', $p_end_time);
			}
		}

		$ex_where = array_merge($where, $ex_where);

		$request_data = $_REQUEST;

		$this->assign('request_data', $request_data);
	}

	// 列表数据统一处理
	private function get_list($action_name = 'index'){
		if (I('get.refresh_price/d', 0)) {
			$this->bprocurement_model->refresh_price();
		}
		$main_tbl = C('DB_PREFIX').'b_procurement';
		$where = array(
			$main_tbl.'.deleted'=> 0,
			$main_tbl.'.company_id'=>get_company_id()
		);
		$this->handleSearch($where);

		switch (strtolower($action_name)) {
			case 'index': break;
			case 'check_index':
				//$where['s2.split_done'] = 1;  //是否需要分称后才采购审核
				$where[$main_tbl.'.status'] = 0;
			break;
		}

		$field = $main_tbl.'.*';
		$field .= ', s.company_name';
		// 每个 storage 都完成分称 split_done = 1
		$field .= ', s2.split_done';	
		$field .= ', (CASE '.$main_tbl.'.pricemode WHEN 1 THEN "计重" ELSE "计件" END)as show_pricemode,pricemode';
		$field .= ', b_employee.employee_name as creator_name';
		$field .= ', from_unixtime('.$main_tbl.'.create_time, "%Y-%m-%d %H:%i:%s")as show_create_time ,s2.done_num  ';
		$field .= ', (CASE '.$main_tbl.'.status
			WHEN -2 THEN "已驳回"
		 	WHEN -1 THEN "待提交"
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		)as show_status';

		$join = ' LEFT JOIN '.C('DB_PREFIX').'m_users as u ON (u.id = '.$main_tbl.'.creator_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_employee as b_employee ON (b_employee.user_id = '.$main_tbl.'.creator_id and b_employee.deleted=0 and b_employee.company_id = '.$main_tbl.'.company_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_supplier as s ON (s.id = '.$main_tbl.'.supplier_id)';

		// 子查询 storage 的数量 与 storage.storage_status = 1 的数量相减， 0为子数量都分称完成，> 0 则未分称完成
		$sub_1 = M('BProcureStorage')->field('procurement_id, COUNT(*)as done_num')->where('storage_status = 1')->group('procurement_id')->select(false);
		$sub_2 = M('BProcureStorage')->alias('s')->field('s.procurement_id, count(*)as c, IFNULL(s1.done_num, 0)as done_num, IF(count(*) - IFNULL(s1.done_num, 0), 0, 1)as split_done')->join('LEFT JOIN ('.$sub_1.')as s1 ON (s1.procurement_id = s.procurement_id)')->group('s.procurement_id')->select(false);

		$join .= ' LEFT JOIN ('.$sub_2.')as s2 ON (s2.procurement_id = '.$main_tbl.'.id)';

		$order = $main_tbl.'.id DESC';

		if(IS_POST){
			if(I('post.submit') == 'excel_out'){
				$this->bprocurement_model->excel_out($where, $field, $join);
				die();
			}
		}

		$count = $this->bprocurement_model->countList($where, $field, $join);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.",".$page->listRows;
		$procure_list = $this->bprocurement_model->getList($where, $field, $limit, $join, $order);
		$this->assign('procure_list', $procure_list);
		$this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));

        $this->assign('action_name', $action_name);
        $this->b_show_status('b_procurement');
        $search_html = $this->fetch('index_search');

        $this->assign('search_html', $search_html);
	}

	// 采购列表 - 普通列表
	public function index(){

		$this->get_list(ACTION_NAME);

        $this->display();
	}

	// 采购列表 - 审核列表
	public function check_index(){

		$this->get_list(ACTION_NAME);

        $this->display();
	}

	// 获取详细资料并根据不同的 action_name 渲染不同模板
	private function get_info($action_name = 'detail'){
		$main_tbl = C('DB_PREFIX').'b_procurement';

		$id = I('get.id/d', 0);

		/*表单的操作记录 add by lzy 2018.5.26 start*/
		$operate_record=$this->bprocurement_model->getOperateRecord($id);
		$this->assign('operate_record', $operate_record);
		//表单的操作流程
		$operate_process=$this->bprocurement_model->getProcess($id);
		$this->assign('process_list', $operate_process);
		/*表单的操作记录 add by lzy 2018.5.26 end*/
        $sub_list = $this->bexpencesub_model->getSublist(array('ticket_id' => $id, 'ticket_type' => 2));
		$this->assign('sub_list', $sub_list);

		$where = array(
			$main_tbl.'.id'=> $id
		);

		$field = $main_tbl.'.*';
		$field .= ', s.company_name ';
		$field .= ', (CASE '.$main_tbl.'.pricemode WHEN 1 THEN "计重" ELSE "计件" END) as show_pricemode';
		$field .= ', ifnull(u.user_nicename,u.mobile) as creator_name';
		$field .= ', (CASE '.$main_tbl.'.status
		 	WHEN -1 THEN "待提交"
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		) as show_status';
		$join = ' LEFT JOIN '.C('DB_PREFIX').'m_users as u ON (u.id = '.$main_tbl.'.creator_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_supplier as s ON (s.id = '.$main_tbl.'.supplier_id)';

		$info = $this->bprocurement_model->getInfo($where, $field, $join);
		if($info['bill_pic']){
			$info['bill_pic_list'] = explode(',', $info['bill_pic']);
		}

		$this->assign('info', $info);
		if ($info['pricemode'] == '0') {
			$where = array('procurement_id'=> $info['id']);

			$storage_info = $this->bprocurestorage_model->getInfo($where, 'id, type');
			
			if($storage_info['type'] == 2){
				$where = array(
					'order_id'=> $storage_info['id'],
					'type'=> 2,
					'deleted'=> 0
				);
				$product_list = D('Business/BRecoveryProduct')->getList($where);
				foreach ($product_list as $key => $value) {
					$product_list[$key]['purity'] = bcmul($value['purity'], 1000, 3);
				}
			}else{
				$product_list = D("Business/BProduct")->get_list_by_storage_info($storage_info);
			}

			$this->assign('product_list', $product_list);
			$this->display('num_'.$action_name);
		} else {
			// 计重分称入库后的商品
			$where = array(
				'gb_b_procure_storage.procurement_id'=> $info['id'],
				'gb_b_procure_storage.deleted'=> 0
			);

			$join = 'left join gb_a_goods_class as agc on agc.id = gb_b_procure_storage.agc_id';
			$field = 'gb_b_procure_storage.*, agc.class_name';
			$stroage_list = $this->bprocurestorage_model->getList($where, $field, "", $join);

			$inSql = $this->bprocurestorage_model->where($where)->field('id')->select(false);

			$p_tbl = C('DB_PREFIX').'b_product';

			$where = $p_tbl.'.deleted = 0 AND ' . $p_tbl . '.storage_id IN (' . $inSql . ')';

			$field = $p_tbl.'.*, g.goods_code, g.goods_name, gd.purity, s.batch, s.gold_price, s.fee';
			$join = ' LEFT JOIN '.C('DB_PREFIX').'b_procure_storage as s ON (s.id = '.$p_tbl.'.storage_id)';
			$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_goods as g ON (g.id = '.$p_tbl.'.goods_id)';
			$join .= ' LEFT JOIN '.C("DB_PREFIX").'b_goldgoods_detail as gd on (gd.goods_id = g.id)';

			$product_list = $this->bproduct_model->getList($where, $field, null, $join);

			$this->assign('product_list', $product_list);

			$this->assign('stroage_list', $stroage_list);

			$this->display($action_name);
		}
	}

	// 采购单审核
	public function check(){

		if(IS_POST){

			$result = $this->bprocurement_model->check_procure();

			if($result['status'] == 1){
				$info["status"] = "success";
				$info["url"] = U('BProcure/check_index');
			}else{
				$info["status"] = 'fail';
				$info['msg'] = $result['msg'];
			}

			$this->ajaxReturn($info);

		}else{
			$this->get_info(ACTION_NAME);
		}
	}

	// 采购单详细 - get.id
	public function detail(){
		if(IS_POST){
			$id=I("id");
			M("")->startTrans();
			$where=array("id"=>$id,"status"=>0);
			$data=array("status"=>3);
			$procure_info = D("BProcurement")->getInfo($where);
			if(empty($procure_info)){
				$info['status'] = 0;
				$info['msg'] = '采购单状态出错！';
				$this->ajaxReturn($info);
			}
			$res=D("Business/BProcurement")->update($where,$data);
			/*添加表单操作记录 add by lzy 2018.5.26 start*/
			$record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE,$id,BProcurementModel::REVOKE);
			/*添加表单操作记录 add by lzy 2018.5.26 end*/
			if($res!==false){
				M("")->commit();
				$info['status'] = 1;
				$info['msg'] = "撤销成功！";
				$this->ajaxReturn($info);
			}else{
				$info['status']=0;
				$info['msg']="撤销失败！";
				$this->ajaxReturn($info);
			}
		}else{
			$this->get_info(ACTION_NAME);
		}
	}

	// 获取采购单信息并 assign
	private function get_procure_info(&$info = NULL){
		$id = I('get.id/d', 0);
			
		$where = array(
			'id'=> $id
		);

		$info = $this->bprocurement_model->getInfo($where);

		if($info['bill_pic']){
			$info['bill_pic_list'] = explode(',', $info['bill_pic']);
		}

		$this->assign('info', $info);
	}

	// 修改开单信息 - 计重
	public function edit() {
        if (IS_POST) {
            $this->handlePostProcureData(ACTION_NAME);
        } else {            
            $this->get_procure_info($info);
            
            $storage_list = $this->bprocurestorage_model->getList(array('procurement_id' => $info['id'], 'deleted' => 0));
            $this->assign('storage_list', $storage_list);
            
            A('BExpence')->_expenceList(2);

            // 其它费用
            $sub_list = $this->bexpencesub_model->getSublist(array('ticket_id' => $info['id'], 'ticket_type' => 2));
            $this->assign('sub_list', $sub_list);
            
            $this->_get_top_goods_class(1);
            
            $this->bsupplier_model->get_list_assign($this->view);
            
            $this->getExcelUrl(ACTION_NAME);
            
            $this->display();
        }
    }

	// 修改开单信息 - 计件
	public function num_edit(){
		if(IS_POST){
			$this->handlePostProcureData(ACTION_NAME);
		}else{
			$this->get_procure_info($info);
			$where = array(
				'procurement_id'=> $info['id'],
				'deleted'=> 0
			);
			$storage_info = $this->bprocurestorage_model->getInfo($where, 'id, type, agc_id');

			$where = array();
			$where['company_id'] = $this->MUser["company_id"];
			$where['deleted'] = 0;
			$where['sub_type'] = 1;
			$certifys = D('BProductSub')->get_prodcut_sub($where);
			$certify = $this->set_select($certifys, 'certify_type');
			$this->assign('certify_types', $certify);

			if ($storage_info['type'] == 2) {
				$where = array(
					'order_id'=> $storage_info['id'],
					'type'=> 2,
					'deleted'=> 0,
				);
				$product_list = D('Business/BRecoveryProduct')->getList($where);
				foreach ($product_list as $key => $value) {
					$product_list[$key]['purity'] = bcmul($value['purity'], 1000, 3);
				}

				$price = '';
				$recover_setting = D("BOptions")->get_recover_setting();
	            if($recover_setting){
	                $price = json_decode($recover_setting["option_value"], true);
	            }

	            $price["recovery_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['recovery_bgt_id']);
	            $price["gold_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);

	            $this->assign('price', $price);
			} else {
				$product_list = D("Business/BProduct")->get_list_by_storage_info($storage_info);
				foreach ($product_list as $k => $v){
					$product_list[$k]['certify_type']=$this->set_select($certifys,"certify_type",$v['certify_type']);
				}
				$where['sub_type']=2;
				$shapes=D("BProductSub")->get_prodcut_sub($where);
				$shape=$this->set_select($shapes,"shape");
				$this->assign("shapes",$shape);
				$where['sub_type']=3;
				$colors=D("BProductSub")->get_prodcut_sub($where);
				$color=$this->set_select($colors,"color");
				$this->assign("colors",$color);
				$where['sub_type']=4;
				$claritys=D("BProductSub")->get_prodcut_sub($where);
				$clarity=$this->set_select($claritys,"clarity");
				$this->assign("claritys",$clarity);
				$where['sub_type']=5;
				$cuts=D("BProductSub")->get_prodcut_sub($where);
				$cut=$this->set_select($cuts,"cut");
				$this->assign("cuts",$cut);
				$where['sub_type']=6;
				$fluorescents=D("BProductSub")->get_prodcut_sub($where);
				$fluorescent=$this->set_select($fluorescents,"fluorescent");
				$this->assign("fluorescents",$fluorescent);
				$where['sub_type']=7;
				$polishs=D("BProductSub")->get_prodcut_sub($where);
				$polish=$this->set_select($polishs,"polish");
				$this->assign("polishs",$polish);
				$where['sub_type']=8;
				$symmetrics=D("BProductSub")->get_prodcut_sub($where);
				$symmetric=$this->set_select($symmetrics,"symmetric");
				$this->assign("symmetrics",$symmetric);
				$where['sub_type']=9;
				$materials=D("BProductSub")->get_prodcut_sub($where);
				$material=$this->set_select($materials,"symmetrics");
				$this->assign("materials",$material);
				switch ($storage_info['type']){
					case 3:
						foreach ($product_list as $k => $v){
							$product_list[$k]['shape']=$this->set_select($shapes,"shape",$v['shape']);
							$product_list[$k]['color']=$this->set_select($colors,"color",$v['color']);
							$product_list[$k]['clarity']=$this->set_select($claritys,"clarity",$v['clarity']);
							$product_list[$k]['cut']=$this->set_select($cuts,"cut",$v['cut']);
							$product_list[$k]['fluorescent']=$this->set_select($fluorescents,"fluorescent",$v['fluorescent']);
							$product_list[$k]['polish']=$this->set_select($polishs,"polish",$v['polish']);
							$product_list[$k]['symmetric']=$this->set_select($symmetrics,"symmetric",$v['symmetric']);
						}
						$this->assign("product_list",$product_list);
						break;
					case 4:
						foreach ($product_list as $k => $v){
							$product_list[$k]['material']=$this->set_select($materials,"material",$v['material']);
						}
						$this->assign("product_list",$product_list);
						break;
					case 5:
						$this->assign("product_list",$product_list);
						break;
					case 6:
						$this->assign("product_list",$product_list);
						break;
				}
			}
            A('BExpence')->_expenceList(2);
	        $sub_list = $this->bexpencesub_model->getSublist(array('ticket_id' => $info['id'], 'ticket_type' => 2));
			$this->assign('sub_list', $sub_list);

            $this->_get_top_goods_class(0);
			$this->assign('product_list', $product_list);
			$this->assign('storage_info', $storage_info);
			$this->bsupplier_model->get_list_assign($this->view);
			$this->getExcelUrl(ACTION_NAME);
			$this->display();
		}
	}

	// 采购单删除 - deleted = 1
	public function delete(){

		$info = $this->bprocurement_model->deleted();

		if ($info['status'] == 1) {
            $info["url"] = U('BProcure/index');
		}

		$this->ajaxReturn($info);
	}

	// 采购单撤销 - status = 3
	public function cancel(){
		
		$info = $this->bprocurement_model->cancel();

		if ($info['status'] == 1) {
            $info["url"] = U('BProcure/index');
		}

		$this->ajaxReturn($info);
	}

	// 采购开单 - 计重
	public function add(){
        if (IS_POST) {
            $this->handlePostProcureData(ACTION_NAME);
        } else {
            A('BExpence')->_expenceList(2);
            
            $this->_get_top_goods_class(1);
            
            $this->bsupplier_model->get_list_assign($this->view);
            
            $this->getExcelUrl(ACTION_NAME);
            
            $this->display();
        }
    }

	// 采购开单 - 计件
	public function num_add() {
        if (IS_POST) {
            $this->handlePostProcureData(ACTION_NAME);
        } else {
            $this->get_prodcuts();
            
            A('BExpence')->_expenceList(2);
            
            $this->_get_top_goods_class(0);
            
            $this->bsupplier_model->get_list_assign($this->view);
            
            $this->getExcelUrl(ACTION_NAME);
            
            $price = '';
            $info = D("BOptions")->get_recover_setting();
            if ($info) {
                $price = json_decode($info["option_value"], true);
            }
            
            $price["recovery_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['recovery_bgt_id']);
            $price["gold_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);
            
            $this->assign('price', $price);
            
            $this->display();
        }
    }
	public function get_prodcuts(){
		$where=array();
		$where['company_id']=$this->MUser["company_id"];
		$where['deleted']=0;
		$where['sub_type']=1;
		$certifys=D("BProductSub")->get_prodcut_sub($where);
		$certify=$this->set_select($certifys,"certify_type");
		$this->assign("certify_types",$certify);
		$where['sub_type']=2;
		$shapes=D("BProductSub")->get_prodcut_sub($where);
		$shape=$this->set_select($shapes,"shape");
		$this->assign("shapes",$shape);
		$where['sub_type']=3;
		$colors=D("BProductSub")->get_prodcut_sub($where);
		$color=$this->set_select($colors,"color");
		$this->assign("colors",$color);
		$where['sub_type']=4;
		$claritys=D("BProductSub")->get_prodcut_sub($where);
		$clarity=$this->set_select($claritys,"clarity");
		$this->assign("claritys",$clarity);
		$where['sub_type']=5;
		$cuts=D("BProductSub")->get_prodcut_sub($where);
		$cut=$this->set_select($cuts,"cut");
		$this->assign("cuts",$cut);
		$where['sub_type']=6;
		$fluorescents=D("BProductSub")->get_prodcut_sub($where);
		$fluorescent=$this->set_select($fluorescents,"fluorescent");
		$this->assign("fluorescents",$fluorescent);
		$where['sub_type']=7;
		$polishs=D("BProductSub")->get_prodcut_sub($where);
		$polish=$this->set_select($polishs,"polish");
		$this->assign("polishs",$polish);
		$where['sub_type']=8;
		$symmetrics=D("BProductSub")->get_prodcut_sub($where);
		$symmetric=$this->set_select($symmetrics,"symmetric");
		$this->assign("symmetrics",$symmetric);
		$where['sub_type']=9;
		$materials=D("BProductSub")->get_prodcut_sub($where);
		$material=$this->set_select($materials,"material");
		$this->assign("materials",$material);
	}

	public function set_select($list,$name,$sub_note){
		$str="";
		if(!empty($list)){
			
			$str = "<select name='".$name."' style='width:100px;'>";
			
			$is_selected = false;

			foreach ($list as $k=>$v){
				// if($sub_note == $v){
				// 	$str.="<option value='".$v."' selected='true'>".$v."</option>";
				// }else{
				// 	$str.="<option value='".$v."'>".$v."</option>";
				// }

				// $k 为sub_value 附加属性值，sub_note 仅为注释用
				if($sub_note == $k){
					$str.="<option value='".$k."' selected='true'>".$v."</option>";
					$is_selected = true;
				}else{
					$str.="<option value='".$k."'>".$v."</option>";
				}
			}

			if($is_selected === false){
				$str.="<option value='' selected='selected'> - </option>";
			}else{
				$str.="<option value=''> - </option>";
			}

			$str.="</select>";
		}
		return $str;
	}
	// 货品列表
	public function goods_list(){
		$main_tbl = C('DB_PREFIX').'b_goods';
		$postdata = I('post.');
		$type=I("type");
		$this->assign("type",$type);
		$where = array(
			$main_tbl.'.status'=> 1,
			$main_tbl.'.deleted' => 0, 
			$main_tbl.'.price_mode'=> 0,
		);

		if(!empty($postdata['search_name']) || $postdata['search_name']=='0'){
            $where[$main_tbl.'.goods_code|'.$main_tbl.'.goods_name'] = array('like','%'.trim($postdata['search_name']).'%');
        }

		$count = $this->bgoods_model->countList($where);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.",".$page->listRows;

		$field = $main_tbl.'.id,'.$main_tbl.'.weight,'.$main_tbl.'.buy_fee,'.$main_tbl.'.sell_price,'.$main_tbl.'.procure_price,'.$main_tbl.'.pick_price,'.$main_tbl.'.price_mode';
		// $field .= ', bc.goods_name, bc.goods_code';
		$field .= ', '.$main_tbl.'.goods_name, '.$main_tbl.'.goods_code';
		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_goods_common as bc ON (bc.id = '.$main_tbl.'.goods_common_id)';

		$goods_list = $this->bgoods_model->getList($where, $field, $limit, $join);

		$this->assign('goods_list', $goods_list);
		$this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));

		$this->display();
	}
	// 获取商品大类顶级分类 并assign
	public function _get_top_goods_class($type = 0)
	{
		$class_list = $this->agoodsclass_model->getTopGoodsClass($type);
		$this->assign('class_list', $class_list);
	}

	// 获取批号 - ajax
	public function getBatchNo($add_num = NULL, $return = false){
		$batch = '';
		$tree = new \Tree();
		if(IS_POST || !is_null($add_num)){
			$batch = b_order_number('BProcureStorage', 'batch');
			
			$num = substr($batch, -3, 3);

			$preStr = substr($batch, 0, strlen($batch) - 3);

			$add_num = is_null($add_num) ? I('add_num/d', 0) : $add_num;

			$num += $add_num;

			$batch = $preStr . sprintf('%03d', $num);
		}

		$data['batch'] = $batch;
		
		if ($return) {
			return $batch;
		} else {
			$this->ajaxReturn($data);
		}
	}

	// 采购发票上传 - ajax
    public function upload_bill(){
        $p_user = get_user_id();

        $dirpath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/Procure/Bill/'.$p_user.'/';
        if(!is_dir($dirpath)){
            mkDirs($dirpath);
        }

        // 如果有旧发票图片则删除
        if(I('post.del_bill_pic')){
            $dir_path = str_replace("http://".$_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT'], I('post.del_bill_pic'));
            @unlink($dir_path);
        }
        
        $type = explode('/', $_FILES['upload_bill']['type']);
        $file_name = $dirpath.time().'.'.$type[1];
        move_uploaded_file($_FILES["upload_bill"]["tmp_name"], $file_name);
        $result = file_exists($file_name);
        $file_name = str_replace($_SERVER['DOCUMENT_ROOT'], "http://".$_SERVER['HTTP_HOST'], $file_name);
        
        if($result !== false){
            $info["status"] = "1";
            $info["bill_pic"] = $file_name;
        }else{
            $info["status"] = "0";
        }

        die(json_encode($info));
    }

    // excel 批量导入货品数据 - 计重 - ajax
    public function excel_input(){

		$tree = new \Tree();
		$file_name = $_FILES['excel_file']['name'];
        $tmp_name = $_FILES['excel_file']['tmp_name'];

        $info = $this->uploadExcel($file_name, $tmp_name);
        $datas = array('status'=> 0, 'msg'=> '上传失败');

        $class_list = $this->agoodsclass_model->getTopGoodsClass(1);
        $class_names = array();
        $type_list = '';
        foreach ($class_list as $key => $value) {
        	$class_names[$value['id']] = $value['class_name'];
        	$type_list .= '<option data-agcid="' . $value['id'] . '" value="' . $value['type'] . '">' . $value['class_name'] . '</option>';
        }

        if ($info['status'] == 1) {

        	$datas['status'] = 1;

        	$text = '';
        	$num = I('add_num/d', 0);

        	foreach ($info['data'] as $key => $value) {

        		if (! in_array($value['0'], $class_names) ) {
        			continue;
        		}

        		$batch = $this->getBatchNo($num++, true);

				$text .= '<tr><td class="text-center" style="vertical-align: inherit;"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
					<td class="text-center" style="vertical-align: inherit;"><select name="type_list" class="type_list">' . $type_list . '</select></td>
					<td class="text-center" style="vertical-align: inherit;"><input type="text" autocomplete="off" class="batch" name="batch" value="' . $batch . '" readonly="readonly"></td>
	        		<td class="text-center" style="vertical-align: inherit;"><input type="text" autocomplete="off" class="gold_price buy_price right" name="gold_price" value="' . number_format($value[1], 2, '.', '') . '"></td>
	        		<td class="text-center" style="vertical-align: inherit;"><input type="text" autocomplete="off" class="weight right" name="weight" value="' . number_format((float)$value[2], 2, '.', '') . '"></td>
	        		<td class="text-center" style="vertical-align: inherit;"><input type="text" autocomplete="off" class="gold_weight right" name="gold_weight" value="' . number_format((float)$value[3], 2, '.', '') . '"></td>
		            <td class="text-center" style="vertical-align: inherit;"><input type="text" autocomplete="off" class="buy_m_fee right" name="buy_m_fee" value="' . number_format((float)$value[4], 2, '.', '') . '"></td>
		            <td class="text-center" style="vertical-align: inherit;"><input type="text" autocomplete="off" class="all_price right" name="all_price" value="' . number_format(bcmul($value[2], $value[4], 2), 2, '.', '') . '"></td>
		            <td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除""></a></td>
	            </tr>';
        	}

        	$datas['data'] = $text;
        }

        die(json_encode($datas));
    }

	// excel 批量导入货品数据 - 计件 - ajax # 计件导入 - 废弃 转为 m=BStorage&a=excel_input&type={}
	public function num_excel_input(){
		return;

		$excel_map = array(
			'id'=> array('is_key'=> 1, 'col'=> 0),
			'product_code'=> array('col'=> 2),
			'qc_code'=> array('col'=> 3),
			'isd_num'=> array('col'=> 4),
			'cost_price'=> array('col'=> 5),
			'pick_price'=> array('col'=> 6),
			'sell_price'=> array('col'=> 7),
			'extras'=> array('col'=> 8)
		);

		$file_name = $_FILES['excel_file']['name'];
	    $tmp_name = $_FILES['excel_file']['tmp_name'];

	    $info = $this->uploadExcel($file_name, $tmp_name);
	    $datas = array('status'=> 0, 'msg'=> '上传失败');

	    $is_num_add = I('is_num_add/d', 0);

	    if ($info['status'] == 1) {
	        $msg = $this->bproduct_model->excel_check($info['data']);
	        if (empty($msg)) {
	            $datas['status'] = '1';
	            $datas['data'] = $this->bgoods_model->excel_return($info['data'], $excel_map);

	            // $datas['filename'] = $_SERVER['DOCUMENT_ROOT'] . __ROOT__ . '/Uploads/excel/' . time() . '.php';
	            // $tt = "<?php   return '" . json_encode($datas['data'], true) . "';";
	            // file_put_contents($datas['filename'], $tt, FILE_APPEND);

	            $text = "";

	            foreach ($datas['data'] as $key => $val) {

	            	$text .= '<tr id="'.$val["id"].'" goods_code="'.$val["goods_code"].'">
	                	<td class="text-center"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
	                	<td class="text-center">'.$val["goods_code"].'</td>
	                	<td class="text-center"><input class="product_code" type="text"style="padding:5px 3px;" value="'.$val["product_code"].'"></td>
	                	<td class="text-left" style="padding:8px 3px">'.$val["goods_name"].'</td>
	                	<td class="text-center"><input class="qc_code" type="text"style="padding:5px 3px;" value="'.$val["qc_code"].'"></td>
	                	<td class="text-center"><input class="isd_num" type="text"style="padding:5px 3px;" value="'.$val["isd_num"].'"></td>
	                	<td class="text-center"><input class="cost_price right" type="text"style="padding:5px 3px;" value="'.number_format(((float)$val["cost_price"]), 2).'"></td>
	                	<td class="text-center"><input class="pick_price right" type="text"style="padding:5px 3px;" value="'.number_format((float)$val["pick_price"], 2).'"></td>
	                	<td class="text-center"><input class="sell_price right" type="text"style="padding:5px 3px;" value="'.number_format((float)$val["sell_price"], 2).'"></td>
	                	<td class="text-center"><input class="extras right" type="text"style="padding:5px 3px;" value="'.number_format((float)$val["extras"], 2).'"></td>
	                	<td class="text-center"><a class="del fa fa-trash" title="删除"></a></td>
	                </tr>';

	            }
	            $datas['data'] = $text;
	        } else {
	            $datas['status'] = '0';
	            $datas['msg'] = $msg;
	        }

	    } else if ($info['status'] == 0) {
	        $datas['status'] = '0';
	        $datas['msg'] = "导入的excel表格中无数据";
	    }

	    die(json_encode($datas));
	}

	// 获取excel模板
	private function getExcelUrl($type = 'add'){

		$type = strtolower($type);

		$filename = array(
			// 计重添加
			'add'=> 'example.xlsx',
			// 计件添加
			'num_add'=> 'example1.xlsx',
			// 计重编辑
			'edit'=> 'example.xlsx',
			// 计件编辑
			'num_edit'=> 'example1.xlsx'
		);

		// 默认 - add
		if(!isset($filename[$type])){
			$type = 'add';
		}

		$url = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_ADDR'].$_SERVER['SERVER_PORT']);
        
        $example_excel = 'http://'.$url.'/Uploads/excel/'. $filename[$type] . '?t=' . time();

        $this->assign('example_excel', $example_excel);
	}
	
	// 读取excel文档并查询数据
	private function uploadExcel($file_name, $tmp_name){
        $filePath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/excel/';
        if(!is_dir($filePath)){
            // 递归创建多级文件夹
            mkDirs($filePath);
        }

        require_once VENDOR_PATH.'PHPExcel/PHPExcel.php';
        require_once VENDOR_PATH.'/PHPExcel/PHPExcel/IOFactory.php';
        require_once VENDOR_PATH.'/PHPExcel/PHPExcel/Reader/Excel5.php';

        $time = time();
        $extend = strrchr($file_name, '.');
        $name = $time . $extend;
        $uploadfile = $filePath . $name;
        $result = move_uploaded_file($tmp_name, $uploadfile);
        if($result){
            $data = excel_to_array($extend, $uploadfile);
        }

        @unlink($file_name);
        $info = array();
        if(!empty($data)){
            $info['data'] = $data;
            $info['status'] = 1;
        }else{
            $info['status'] = 0;
        }

        return $info;
    }

	// 统一处理提交数据 - 开单
	private function handlePostProcureData($action_name = ''){

		$redirect_url = U('BProcure/index');

		$supplier_id = I('supplier_id/d', 0);
		$where = array(
			'company_id' => get_company_id(),
			'supplier_id' => $supplier_id,
			'status' => 1,
			'deleted' => 0
		);
		$ca_info = D('Business/BCompanyAccount')->getInfo($where);
		if(empty($ca_info)){
			$result['status'] = 0;
			$result['msg'] = '该供应商结欠账户已被锁定！';
		}else{
			switch (strtolower($action_name)) {
				case 'add':
					$result = $this->bprocurement_model->add_procure();
				break;
				case 'edit':
					$result = $this->bprocurement_model->edit_procure();
				break;
				case 'num_add':
					$result = $this->bprocurement_model->add_num_procure();
				break;
				case 'num_edit':
					$result = $this->bprocurement_model->edit_num_procure();
				break;
			}
		}

		if($result['status'] == 1){
			S('session_menu' . get_user_id(), null);

			$info['status'] = 'success';
			$info['url'] = $redirect_url;
		}elseif ($result['status'] == 0){
			$info = $result;
			$info['status'] = 'fail';
		}

		$this->ajaxReturn($info);
	}
}