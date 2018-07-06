<?php
/**
 * 后台Controller
 */
namespace Common\Controller;
use Common\Controller\AdminbaseController;
class BaseController extends AdminbaseController {
    public $numpage=10;
  /* public function _initialize() {
	    empty($_GET['upw'])?"":session("__SP_UPW__",$_GET['upw']);//设置后台登录加密码	    
		parent::_initialize();
		$this->initMenu();
		$this->load_menu_lang();
    	
        $this->assign("menus", D("Common/Menu")->menu_json());
	}
	*/
  
    
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
   
   
	function getpage($count, $pagesize = 10) {		//修改默认分页样式
		$p = new \Think\Page($count, $pagesize);
		$p->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条记录&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
		$p->setConfig('prev', '上一页');
		$p->setConfig('next', '下一页');
		$p->setConfig('last', '末页');
		$p->setConfig('first', '首页');
		$p->setConfig('theme', '%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%');
		$p->lastSuffix = false;//最后一页不显示为总页数
		return $p;
	}
	
	
}