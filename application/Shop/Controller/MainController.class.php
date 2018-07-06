<?php
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class MainController extends ShopbaseController {
	
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
		$condition=array("company_id"=>get_company_id());
		$company=D("BCompany")->getInfo($condition);
		/*$bemployee=D("BEmployee")->getInfo(array("user_id"=>get_user_id(),"company_id"=>get_company_id()));
		$bshopemployee=D("BShopEmployee")->getInfo(array("user_id"=>get_user_id(),"employee_id"=>$bemployee["id"]));
		$shop=D("BShop")->getInfo(array("id"=>$bshopemployee["shop_id"]));*/

        $where = array(
            's.id'=> get_shop_id(),
        );
        $field = 's.*, u.user_nicename, u.realname';
        $join = 'LEFT JOIN '.C('DB_PREFIX').'m_users as u ON (u.id = s.manger_id)';
		$shop = D("BShop")->alias('s')->getInfo($where, $field, $join);
$this->get_check_info();
        // echo '<pre>';
        // print_r($shop);die();

        $this->assign('shop', $shop);
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
		if(sp_auth_check(get_user_id(),$name="Shop/BAllot/allot_check","or","b")){
			$condition=array("ballot.status"=>0);
			$condition["from_bwarehouse.shop_id"]=get_shop_id();
			$result=D("BAllot")->get_check_count($condition,'调拨审核',U('BAllot/allot_check'));
			array_push($allot_info,$result);
		}
		if(sp_auth_check(get_user_id(),$name="Shop/BAllot/outbound_check","or","b")){
			$condition=array("ballot.status"=>1);
			$condition['from_bwarehouse.wh_uid']=get_user_id();
			$condition["from_bwarehouse.shop_id"]=get_shop_id();
			$result=D("BAllot")->get_check_count($condition,'调拨出库审核',U('BAllot/outbound_check'));
			array_push($allot_info,$result);
		}
		if(sp_auth_check(get_user_id(),$name="Shop/BAllot/receipt_check","or","b")){
			$condition=array("ballot.status"=>5);
			$condition["to_bwarehouse.shop_id"]=get_shop_id();
			$condition['to_bwarehouse.wh_uid']=get_user_id();
			$result=D("BAllot")->get_check_count($condition,'调拨入库审核',U('BAllot/receipt_check'));
			array_push($allot_info,$result);
		}
		//回购
		$recovery_info=array();
		if(sp_auth_check(get_user_id(),$name="Shop/BRecovery/check","or","b")){
			$condition=array("status"=>0);
			$result=D("BRecovery")->get_check_count($condition,'回购审核',U('BRecovery/check'));
			array_push($recovery_info,$result);
		}
		//销售
		$sell_info=array();
		if(sp_auth_check(get_user_id(),$name="Shop/BSell/check","or","b")){
			$condition=array("status"=>0);
			$result=D("BSell")->get_check_count($condition,'销售审核',U('BSell/check'));
			array_push($sell_info,$result);
		}
		$check_info['调拨']=$allot_info;
		$check_info['回购']=$recovery_info;
		$check_info['销售']=$sell_info;
		//var_dump($check_info);
		$this->assign('check_info', $check_info);

	}
}