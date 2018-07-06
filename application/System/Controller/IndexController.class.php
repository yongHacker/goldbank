<?php
/**
 * 后台首页
 */
namespace System\Controller;

use Common\Controller\SystembaseController;

class IndexController extends SystembaseController {
	
	public function _initialize() {
	    empty($_GET['upw'])?"":session("__SP_UPW__",$_GET['upw']);//设置后台登录加密码	    
		parent::_initialize();
		$this->initMenu();
	}
	
    /**
     * 后台框架首页
     */
    public function index() {
        $this->load_menu_lang();
        $this->assign("menus", D("AMenu")->menu_json());
       	$this->display();
    }
    private function load_menu_lang(){
        $default_lang=C('DEFAULT_LANG');
        
        $langSet=C('ADMIN_LANG_SWITCH_ON',null,false)?LANG_SET:$default_lang;
        
	    $apps=sp_scan_dir(SPAPP."*",GLOB_ONLYDIR);
	    $error_menus=array();
	    foreach ($apps as $app){
	        if(is_dir(SPAPP.$app)){
	            if($default_lang!=$langSet){
	                $admin_menu_lang_file=SPAPP.$app."/Lang/".$langSet."/admin_menu.php";
	            }else{
	                $admin_menu_lang_file=SITE_PATH."data/lang/$app/Lang/".$langSet."/admin_menu.php";
	                if(!file_exists_case($admin_menu_lang_file)){
	                    $admin_menu_lang_file=SPAPP.$app."/Lang/".$langSet."/admin_menu.php";
	                }
	            }
	            
	            if(is_file($admin_menu_lang_file)){
	                $lang=include $admin_menu_lang_file;
	                L($lang);
	            }
	        }
	    }
    }
	//测试短信接口
	public function test_send_msg(){
		@$SendMessage=new SendMessageController();
		
		/*@$arr['mobile']=$SendMessage->sendOrderSMS("15070410521","您的提现已经提交审核！",2);*/

		/*$content="1234(注册验证码)用户周军注册，有效时间为300秒";
		$ret=$SendMessage->sendOrderSMS(15070410521,$content,1);*/

	}
	public function gold_market_old(){
		return;
		set_time_limit(0);
		$now_id=0;
		$i=0;
		$t="";
		while(true){
			$i++;
			$where['id']=array("gt",$now_id);
			$info=M("a_gold_market")->where($where)->order("id asc")->find();
			if(empty($info)){
				echo "插入结束";
				break;
			}
			$id=$info['id'];
			$now_id=$id;
			unset($info['id']);
			$table="gold_market_".date("Ym",$info['insert_time']);
			if($t!=$table){
				$t=$table;
				$table_name="gb_".$table;
				D("System/GoldMarketNew")->is_have_table($table_name);
			}
			$id=M($table,"gb_","GOLDBANK_MARKET_DB")->add($info);
			echo $i."\n";
		}
	}
}