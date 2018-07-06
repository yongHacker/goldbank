<?php
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class MainController extends BusinessbaseController {
	
    public function index(){
    	
    	$mysql= M()->query("select VERSION() as version");
    	$mysql=$mysql[0]['version'];
    	$mysql=empty($mysql)?L('UNKNOWN'):$mysql;
    	
    	//server infomaions
    	$info = array(
    			L('OPERATING_SYSTEM') => PHP_OS,
    			L('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
    	        L('PHP_VERSION') => PHP_VERSION,
    			L('PHP_RUN_MODE') => php_sapi_name(),
				L('PHP_VERSION') => phpversion(),
    			L('MYSQL_VERSION') =>$mysql,
    			L('PROGRAM_VERSION') => THINKCMF_VERSION . "&nbsp;&nbsp;&nbsp; [<a href='http://mt.gold-banker.cn' target='_blank'>GOLD_BANKER</a>]",
    			L('UPLOAD_MAX_FILESIZE') => ini_get('upload_max_filesize'),
    			L('MAX_EXECUTION_TIME') => ini_get('max_execution_time') . "s",
    			L('DISK_FREE_SPACE') => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
    	);
		$condition = array("c.company_id"=> get_company_id());
        $field = 'c.*, u.realname, u.user_nicename';
        $join = 'LEFT JOIN '.C('DB_PREFIX').'m_users as u ON (u.id = c.user_id)';
		$company = D("BCompany")->alias('c')->getInfo($condition, $field, $join);
		//$company = D("BEmployee")->alias('c')->getInfo(array());

		/*$bemployee=D("BEmployee")->getInfo(array("user_id"=>get_user_id(),"company_id"=>get_company_id()));
		$bshopemployee=D("BShopEmployee")->getInfo(array("user_id"=>get_user_id(),"employee_id"=>$bemployee["id"]));
		$shop=D("BShop")->getInfo(array("id"=>$bshopemployee["shop_id"]));*/
		$shop = D("BShop")->getInfo(array("id"=>get_shop_id()));
		$this->get_check_info();
        $this->assign('company', $company);
        $this->assign('company_name', $company["company_name"]);
		$this->assign('shop_name', $shop["shop_name"]);
    	$this->assign('server_info', $info);
    	$this->display();
    }
	//获取待审核数据
	function get_check_info(){
		$check_info=array();
		//调拨
		$allot_info=array();
		if(sp_auth_check(get_user_id(),$name="Business/BAllot/allot_check","or","b")){
			$condition=array("ballot.status"=>0);
			$result=D("BAllot")->get_check_count($condition,'调拨审核',U('BAllot/allot_check'));
			array_push($allot_info,$result);
		}
		if(sp_auth_check(get_user_id(),$name="Business/BAllot/outbound_check","or","b")){
			$condition=array("ballot.status"=>1);
			$condition['from_bwarehouse.wh_uid']=get_user_id();
			$result=D("BAllot")->get_check_count($condition,'调拨出库审核',U('BAllot/outbound_check'));
			array_push($allot_info,$result);
		}
		if(sp_auth_check(get_user_id(),$name="Business/BAllot/receipt_check","or","b")){
			$condition=array("ballot.status"=>5);
			$condition['to_bwarehouse.wh_uid']=get_user_id();
			$result=D("BAllot")->get_check_count($condition,'调拨入库审核',U('BAllot/receipt_check'));
			array_push($allot_info,$result);
		}
		//采购
		$procure_info=array();
		if(sp_auth_check(get_user_id(),$name="Business/BProcure/check","or","b")){
			$condition=array("status"=>0);
			$result=D("BProcurement")->get_check_count($condition,'采购审核',U('BProcure/check'));
			array_push($procure_info,$result);
		}
		if(sp_auth_check(get_user_id(),$name="Business/BStorage/check_list","or","b")){
			$condition=array();
			$result=D("BProcureStorage")->get_check_count($condition,'入库审核',U('BStorage/check_list'));
			array_push($procure_info,$result);
		}
		//回购
		$recovery_info=array();
		if(sp_auth_check(get_user_id(),$name="Business/BRecovery/check","or","b")){
			$condition=array("status"=>0);
			$result=D("BRecovery")->get_check_count($condition,'回购审核',U('BRecovery/check'));
			array_push($recovery_info,$result);
		}
		//销售
		$sell_info=array();
		if(sp_auth_check(get_user_id(),$name="Business/BSell/check","or","b")){
			$condition=array("status"=>0);
			$result=D("BSell")->get_check_count($condition,'销售审核',U('BSell/check'));
			array_push($sell_info,$result);
		}
		//出库
		$outbound_info=array();
		if(sp_auth_check(get_user_id(),$name="Business/BOutboundOrder/check","or","b")){
			$condition=array("status"=>0);
			$result=D("BOutboundOrder")->get_check_count($condition,'出库审核',U('BOutboundOrder/check'));
			array_push($outbound_info,$result);
		}
		//结算
		$settle_info=array();
		if(sp_auth_check(get_user_id(),$name="Business/BSettlement/check","or","b")){
			$condition=array("status"=>0);
			$result=D("BProcureSettle")->get_check_count($condition,'结算审核',U('BSettlement/check'));
			array_push($settle_info,$result);
		}

		$check_info['调拨']=$allot_info;
		$check_info['采购']=$procure_info;
		$check_info['回购']=$recovery_info;
		$check_info['销售']=$sell_info;
		$check_info['出库']=$outbound_info;
		$check_info['结算']=$settle_info;
		//var_dump($check_info);
		$this->assign('check_info', $check_info);

	}
}