<?php
/**
 * 结算流程
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BSettlementController extends BusinessbaseController {

	public function _initialize() {
		parent::_initialize();
		$this->bprocuresettle_model = D('BProcureSettle');
		$this->bprocurement_model = D('BProcurement');
		$this->bcompanyaccount_model = D('BCompanyAccount');
		$this->bsupplier_model = D("BSupplier");
		$this->bwprocurement_model=D("BWholesaleProcure");
		$this->bprocuresettlegold_model=D("BProcureSettleGold");
		$this->bwsell_model=D("BWsell");
	}

	// 获取详细资料及商品列表
	private function get_detail(){
		$this->b_show_status("b_product_goldm");
		$info = $this->bprocuresettle_model->get_info();
		if($info['payment_pic']){
			$info['payment_pic_list'] = explode(',', $info['payment_pic']);
		}
		$this->assign('info', $info);

		/*表单的操作记录 add by lzy 2018.5.26 start*/
		$operate_record=$this->bprocuresettle_model->getOperateRecord(I('get.id'));
		$this->assign('operate_record', $operate_record);
		//表单的操作流程
		$operate_process=$this->bprocuresettle_model->getProcess(I('get.id'));
		$this->assign('process_list', $operate_process);
		/*表单的操作记录 add by lzy 2018.5.26 end*/

		return $info;
	}

	// 处理表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL){

		$main_tbl = C('DB_PREFIX').'b_procure_settle';

		$where = array();

		if(I('status') !== ''){
			$where[$main_tbl.'.status'] = I('status');
		}

		if(I('batch/s')){
			$where[$main_tbl.'.batch'] = array('LIKE', '%'. trim(I('batch/s')) .'%');
		}

		if(I('procure_batch/s')){
			$where['s1.procure_batch'] = array('LIKE', '%'. trim(I('procure_batch/s')) .'%');
		}

		if(I('suppiler_name/s')){
			$where['s.company_name'] = array('LIKE', '%'. trim(I('suppiler_name/s')) .'%');
		}

		if(I('creator_name/s')){
			$where['cu.user_nicename|cu.realname'] = array('LIKE', '%'. trim(I('creator_name/s')) .'%');
		}

		if(I('search_value/s')){
			$search_value = trim(I('search_value/s'));
			$where[$main_tbl.'.batch|cu.user_nicename|cu.realname|s.company_name'] = array('LIKE', '%'. $search_value .'%');
		}

		if(I('begin_time')){
			$begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
			$begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
			$where[$main_tbl.'.create_time'] = array('gt', $begin_time);
		}

		if(I('end_time')){
			$end_time = I('end_time') ? strtotime(I('end_time')) : time();
			$end_time = strtotime(date('Y-m-d 23:59:59', $end_time));

			if(isset($begin_time)){
				$p1 = $where[$main_tbl.'.create_time'];
				unset($where[$main_tbl.'.create_time']);

				$where[$main_tbl.'.create_time'] = array($p1, array('lt', $end_time));
			}else{
				$where[$main_tbl.'.create_time'] = array('lt', $end_time);
			}
		}

		if(I('settle_begin_time')){
			$p_begin_time = I('settle_begin_time') ? strtotime(I('settle_begin_time')) : time();
			$p_begin_time = strtotime(date('Y-m-d 00:00:00', $p_begin_time));
			$where[$main_tbl.'.settle_time'] = array('gt', $p_begin_time);
		}

		if(I('settle_end_time')){
			$p_end_time = I('settle_end_time') ? strtotime(I('settle_end_time')) : time();
			$p_end_time = strtotime(date('Y-m-d 23:59:59', $p_end_time));

			if(isset($p_begin_time)){
				$p1 = $where[$main_tbl.'.settle_time'];
				unset($where[$main_tbl.'.settle_time']);

				$where[$main_tbl.'.settle_time'] = array($p1, array('lt', $p_end_time));
			}else{
				$where[$main_tbl.'.settle_time'] = array('lt', $p_end_time);
			}
		}

		$ex_where = array_merge($where, $ex_where);

		$request_data = $_REQUEST;

		$this->assign('request_data', $request_data);
	}

	// 统一获取列表
	private function get_list($action_name = 'index'){
		if (I('weight_data_recall')) {
			$this->bprocuresettle_model->weightDataRecall();
			die();
		}
		$main_tbl = C('DB_PREFIX').'b_procure_settle';
		$where = array(
			$main_tbl.'.company_id'=> get_company_id(),
			$main_tbl.'.deleted'=> 0
		);

		$this->handleSearch($where);

		switch (strtolower($action_name)) {
			case 'index':break;
			case 'check_index':
				$where[$main_tbl.'.status'] = 0;
				break;
			case 'check_payment':
				$where[$main_tbl.'.status'] = 1;
				break;
		}

		$field = $main_tbl.'.*';
		$field .= ', s.company_name';
		$field .= ', ifnull(cu.user_nicename,cu.mobile)as creator_name';
		$field .= ', from_unixtime('.$main_tbl.'.settle_time, "%Y-%m-%d %H:%i:%s")as show_settle_time';
		$field .= ', from_unixtime('.$main_tbl.'.create_time, "%Y-%m-%d %H:%i:%s")as show_create_time';
		$field .= ', IFNULL(ca_r.price, 0) as ca_r_price, IFNULL(ca_r.extra_price, 0) as ca_r_extra_price';
		$field .= ', IFNULL(mr.weight, 0) as mr_weight';
		$field .= ', IFNULL(mr2.weight, 0) as mr2_weight';
		$field .= ', IFNULL(mo.weight, 0) as mo_weight, mo.mgold_price';

		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_supplier as s ON (s.id = '.$main_tbl.'.supplier_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'m_users as cu ON (cu.id = '.$main_tbl.'.creator_id)';
		// 来往钱记录表
		$join .= 'LEFT JOIN '.C('DB_PREFIX').'b_caccount_record as ca_r ON (ca_r.sn_id = '.$main_tbl.'.id AND ca_r.type = 1 AND ca_r.deleted = 0)';
		// 往料记录表
		$join .= 'LEFT JOIN '.C('DB_PREFIX').'b_material_record as mr ON (mr.sn_id = '.$main_tbl.'.id AND mr.type = 1 AND mr.deleted = 0 AND mr.weight < 0)';
		// 来料记录表
		$join .= 'LEFT JOIN '.C('DB_PREFIX').'b_material_record as mr2 ON (mr2.sn_id = '.$main_tbl.'.id AND mr2.type = 1 AND mr2.deleted = 0 AND mr2.weight > 0)';
		// 买卖料记录表
		$join .= 'LEFT JOIN '.C('DB_PREFIX').'b_material_order as mo ON (mo.sn_id = '.$main_tbl.'.id AND mo.type = 1 AND mo.deleted = 0)';

		$order = 'id DESC';

		if(IS_POST){
			if(I('post.submit') == 'excel_out'){
				$this->bprocuresettle_model->excel_out($where, $field, $join);
				die();
			}
		}
		$count = $this->bprocuresettle_model->countList($where, $field, $join);
		$page = $this->page($count,10);
		$limit = $page->firstRow.",".$page->listRows;
		$settle_list = $this->bprocuresettle_model->getList($where, $field, $limit, $join, $order);

		$this->assign('settle_list', $settle_list);
		$this->assign('numpage', 10);
		$this->assign('page', $page->show('Admin'));
		$this->assign('action_name', $action_name);

		$this->b_show_status('b_procure_settle');
		$search_html = $this->fetch('index_search');

		$this->assign('search_html', $search_html);
	}

	// 修改页面 - 保存提交
	public function edit(){

		if(IS_POST){
			$this->handlePostData(ACTION_NAME);
		}else{
			$info = $this->get_detail();

			$p_id_s = '';
			foreach($info['material_record']['mproduct_list'] as $key => $val){
				$p_id_s .= $val['product_mid'] . ',';
			}
			$this->assign('p_id_s', $p_id_s);

			$price = '';
			$info = D("BOptions")->get_recover_setting();
			if($info){
				$price = json_decode($info["option_value"], true);
			}

			$price["recovery_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['recovery_bgt_id']);
			$price["gold_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);

			$this->assign('price', $price);

			$this->display();
		}
	}

	// 结算单列表
	public function index(){

		/* add by alam 2018/06/11 回溯三大记录表中的sn_id以及来往料记录表中的account_id */
		if (I('get.recallRecordTableSettleId')) {
			$this->bprocuresettle_model->recallRecordTableSettleId();
			die();
		}

		$this->get_list(ACTION_NAME);

		$this->display('index');
	}
	/**
	 * @ author lzy 2018-5-3
	 */
	public function index1(){
		$this->index();
	}
	/**
	 * @ author lzy 2018-5-21
	 */
	public function detail1(){
		$this->detail();
	}


	// 结算单审核列表
	public function check_index(){

		$this->get_list(ACTION_NAME);

		$this->display();
	}

	// 详情 - I('get.id/d')
	public function detail(){

		$info = $this->get_detail();

		$view = "";
		if($info["type"] == 3){
			$view = "bwsell_detail";
		}

		$this->display($view);
	}

	// 结算单撤销 status = 3 - I('post.id/d')
	public function cancel(){
		$result = $this->bprocuresettle_model->check_cancel();
		if($result['status'] == 1){
			$info["status"] = 1;
			$info["url"] = U('BSettlement/index');
		}else{
			$info["status"] = 0;
			$info['msg'] = $result['msg'];
		}

		$this->ajaxReturn($info);
	}

	// 审核 - I('get.id/d')
	public function check(){

		if(IS_POST){

			$result = $this->bprocuresettle_model->check_settle_record();

			if($result['status'] == 1){
				$info["status"] = "success";
				$info["url"] = U('BSettlement/check_index');
			}else{
				$info["status"] = 'fail';
				$info['msg'] = $result['msg'];
			}

			$this->ajaxReturn($info);

		}else{

			$this->get_detail();

			$this->display();
		}
	}

	// 开结算单
	public function add(){

		if(IS_POST){
			$this->handlePostData(ACTION_NAME);
		}else{

			$today = date('Y-m-d', time());

			$this->assign('today', $today);

			$this->bsupplier_model->get_list_assign($this->view);

			// $procure_list = $this->bprocurement_model->get_list_by_supplier();

			// $this->assign('procure_list', $procure_list);

			$price = '';
			$info = D("BOptions")->get_recover_setting();
			if($info){
				$price = json_decode($info["option_value"], true);
			}

			$price["recovery_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['recovery_bgt_id']);
			$price["gold_price"] = empty($price) ? 0 : D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);

			$this->assign('price', $price);

			$this->display();
		}
	}

	// 获取应供应商的结欠账户信息 - AJAX
	public function get_company_account_info(){
		if(IS_POST){

			$account_info = $this->bcompanyaccount_model->get_price_and_weight_by_supplier();

			$data['account_info'] = $account_info;

			die(json_encode($data));
		}
	}

	// 获取对应供应商的采购单列表 - AJAX - 废弃
	public function get_procure_list(){

		die();

		if(IS_POST){

			$account_info = $this->bcompanyaccount_model->get_price_and_weight_by_supplier();

			$procure_list = $this->bprocurement_model->get_list_by_supplier();
			$this->assign('procure_list', $procure_list);

			$total_weight = 0;
			$total_price = 0;

			foreach ($procure_list as $k =>$v){
				$total_weight += $v["weight"];
				$total_price += $v["price"];
			}

			$data['content'] = $this->fetch('procure_list');
			$data['account_info'] = $account_info;

			$data['all_weight']=numberformat($total_weight, 2, '.', ',');
			$data['all_price']=numberformat($total_price, 2, '.', ',');

			die(json_encode($data));
		}
	}

	// 获取金料列表
	public function get_product_gold(){
		// $main_tbl = C('DB_PREFIX').'b_product';
		// $this->b_show_status("b_product_goldm");
		// $postdata = I('post.');
		// $where = array(
		// 	$main_tbl.'.company_id'=> get_company_id(),
		// 	$main_tbl.'.status'=> 2,
		// 	$main_tbl.'.deleted'=> 0,
		// 	$main_tbl.'.type'=> 2,
		// );
		// if(!empty($postdata['search_name']) || $postdata['search_name']=='0'){
		// 	$where['gcl.class_name |'.$main_tbl.'.product_code | g.goods_name'] = array('like','%'.trim($postdata['search_name']).'%');
		// }
		// $field = $main_tbl.'.*,g.goods_name,gcl.class_name,bg.weight,bg.d_weight,bg.type';
		// $join = ' JOIN '.C('DB_PREFIX').'b_goods as g ON (g.id = '.$main_tbl.'.goods_id) ';
		// $join .= ' JOIN '.C("DB_PREFIX").'b_goods_common as gc ON (gc.id = g.goods_common_id) ';
		// $join .=' JOIN '.C("DB_PREFIX").'b_goods_class as gcl ON (gcl.id = gc.class_id)' ;
		// $join .=' left join '.C("DB_PREFIX").'b_product_goldm as bg on bg.product_id  = '.$main_tbl.'.id';

		// $count = D("Business/BProduct")->countList($where,$field,$join);
		// $page = $this->page($count, $this->pagenum);
		// $limit = $page->firstRow.",".$page->listRows;

		// $product_list = D("Business/BProduct")->getList($where, $field, $limit, $join);

		$where = array(
			'company_id'=> get_company_id(),
			'status'=> 2,
			'deleted'=> 0,
		);
		$count = D("Business/BRecoveryProduct")->countList($where);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.",".$page->listRows;

		$field = '*, (CASE type
			WHEN "0" THEN "回购"
			WHEN "1" THEN "销售截金"
			WHEN "2" THEN "采购金料"
			WHEN "3" THEN "结算来料"
			WHEN "4" THEN "以旧换新"
			WHEN "5" THEN "出库转料"
			ELSE "-" END
		)as show_source';

		$product_list = D("Business/BRecoveryProduct")->getList($where, $field, $limit, $join='', $order='id DESC');

		$this->assign('product_list', $product_list);
		$this->assign('numpage', $this->pagenum);
		$this->assign('page', $page->show('Admin'));

		$this->display();
	}

	// 处理提交表单数据
	private function handlePostData($action_name = 'add'){

		if($action_name == 'add'){
			// 增加零售1 或 批发2结算类型判断
			$settle_type = I('settle_type/d', 1);
			$result = $this->bprocuresettle_model->add_settle_record($settle_type);
			$info["url"] = U('BSettlement/index1');
		}

		if($action_name == 'edit'){
			$result = $this->bprocuresettle_model->edit_settle_record();
		}

		if($result['status'] == 1){

			$info["status"] = "success";
			$info["url"] =empty($info["url"])? U('BSettlement/index'):$info["url"];
		}elseif ($result['status'] == 0){
			$info["status"] = "fail";
			$info["msg"] = $result['msg'];
		}

		$this->ajaxReturn($info);
	}
	/************************批发采购**************************************/
	// 批发采购开结算单
	public function num_add(){

		if(IS_POST){
			$_POST['settle_type']=2;
			$this->handlePostData(ACTION_NAME);

		}else{

			$today = date('Y-m-d', time());

			$this->assign('today', $today);

			$this->bsupplier_model->get_list_assign($this->view);

			// $procure_list = $this->bprocurement_model->get_list_by_supplier();

			// $this->assign('procure_list', $procure_list);

			$this->display();
		}
	}
	// 获取对应供应商的批发采购单列表 - AJAX
	public function get_num_procure_list(){

		if(IS_POST){

			$account_info = $this->bcompanyaccount_model->get_price_and_weight_by_supplier();
			$procure_list = $this->bwprocurement_model->get_list_by_supplier();
			$this->assign('procure_list', $procure_list);
			$total_weight=0;
			$total_price=0;
			foreach ($procure_list as $k =>$v){
				$total_weight+=$v["weight"];
				$total_price+=$v["total_fee_price"];
			}
			$data['content'] = $this->fetch('num_procure_list');
			$data['account_info'] = $account_info;
			$data['all_weight']=numberformat($total_weight, 2, '.', ',');
			$data['all_price']=numberformat($total_price, 2, '.', ',');
			die(json_encode($data));
		}
	}
	/************************批发销售**************************************/
	// 批发销售开结算单
	public function sell_num_add(){

		if(IS_POST){
			$_POST['settle_type']=3;
			$this->handlePostData(ACTION_NAME);

		}else{

			$today = date('Y-m-d', time());

			$this->assign('today', $today);

			$this->bsupplier_model->get_list_assign($this->view);

			// $procure_list = $this->bprocurement_model->get_list_by_supplier();

			// $this->assign('procure_list', $procure_list);

			$this->display();
		}
	}
	// 获取对应供应商的批发采购单列表 - AJAX
	public function get_sell_num_procure_list(){

		if(IS_POST){

			$account_info = $this->bcompanyaccount_model->get_price_and_weight_by_supplier();
			$procure_list = $this->bwsell_model->get_list_by_supplier();
			$total_weight=0;
			$total_price=0;
			foreach ($procure_list as $k =>$v){
				$total_weight+=$v["weight"];
				$total_price+=$v["total_fee_price"];
				$procure_list[$k]["weight"]=0;
			}
			$this->assign('procure_list', $procure_list);
			$data['content'] = $this->fetch('sell_num_procure_list');
			$data['account_info'] = $account_info;
			$data['all_weight']=numberformat($total_weight, 2, '.', ',');
			$data['all_price']=numberformat($total_price, 2, '.', ',');
			die(json_encode($data));
		}
	}
	// 采购发票上传 - ajax
	public function upload_pic(){
		$year=date("Y",time());
		$dir_path ='Payment/'.$year.'/';
		$info = upload_pic_by_count($dir_path,$_FILES['upload_payment_pic'],"image",I('post.del_upload_pic'));
		die(json_encode($info));
	}
	/**
	 * @author lzy 2018.5.28
	 * 上传凭证
	 */
	public function change_pic(){
		$condition=array(
			'id'=>I('post.settle_id'),
		);
		$update=array(
			'payment_time'=>empty(I('post.payment_time'))?'':strtotime(I('post.payment_time')),
			'payop_time'=>time(),
			'upimg_id'=>get_user_id(),
			'status'=>4,
		);
		$result=$this->bprocuresettle_model->changeImg($condition,$update);
		output_data(array('msg'=>'操作成功！'));
	}
// 上传凭证 - I('get.id/d')
	public function check_payment(){
		if(empty(I('get.id/d'))){
			$this->get_list(ACTION_NAME);
			$this->display();
		}else{
			$this->get_detail();
			$this->display('check_payment_detail');
		}
	}
}