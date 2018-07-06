<?php
/**
 * 后台首页
 */
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class IndexController extends ShopbaseController {
	
	public function _initialize() {
	    empty($_GET['upw'])?"":session("__SP_UPW__",$_GET['upw']);//设置后台登录加密码	    
		parent::_initialize();
		if(get_shop_id()>0){
			$this->initShopMenu();
		}else{
			$this->initMenu();
		}

	}
	
    /**
     * 后台框架首页
     */
    public function index() {
        $this->load_menu_lang();
		$condition = array("c.company_id"=> get_company_id());
		$company = D("BCompany")->alias('c')->getInfo($condition);
		$shop = D("BShop")->getInfo(array("id"=>get_shop_id()));
		$this->assign("shop_name", $company['company_short_name'].'-'.$shop['shop_name']);
        $this->assign("menus", D("BMenu")->menu_json());
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

}

