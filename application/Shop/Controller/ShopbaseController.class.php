<?php

/**
 * 后台Controller
 */
namespace Shop\Controller;
use Common\Controller\AppframeController;

class ShopbaseController extends AppframeController {
	protected $MUser,$pagenum;
	public function __construct() {
	    hook('admin_begin');
		$admintpl_path=C("SP_ADMIN_TMPL_PATH").C("SP_ADMIN_DEFAULT_THEME")."/";
		C("TMPL_ACTION_SUCCESS",$admintpl_path.C("SP_ADMIN_TMPL_ACTION_SUCCESS"));
		C("TMPL_ACTION_ERROR",$admintpl_path.C("SP_ADMIN_TMPL_ACTION_ERROR"));
		parent::__construct();
		$this->new_construct();
		$this->pagenum=empty(I("limit"))?15:I("limit");;
		$this->default_password=123456;
		$time=time();
		$this->assign("js_debug",APP_DEBUG?"?v=$time":"");
	}
	//初始构造定义参数
	public function new_construct() {
		define("DB_PRE",C("DB_PREFIX"));
		//B端模型定义
		$this->users_model=D("MUsers");
	}
	function _initialize(){
		parent::_initialize();
       define("TMPL_PATH", C("SP_ADMIN_TMPL_PATH"));
		$this->load_app_admin_menu_lang();//暂时取消后台多语言
       $session_admin_id=session('SHOP_USER_ID');
    	if(!empty($session_admin_id)){
    		$users_obj= M("MUsers");
			//$BEmployee=M("BEmployee");
    		$user=$users_obj->where(array('id'=>$session_admin_id))->find();
			$this->MUser=$user;
			$this->MUser["company_id"]=get_company_id();
			$BEmployee=D("BEmployee")->getInfo(array("company_id"=>get_company_id(),'user_id'=>$user['id'],'deleted'=>0));
			$this->MUser["employee_name"]=$BEmployee['employee_name'];
			$company=D("BCompany")->getInfo(array("company_id"=>get_company_id()));
			if($company["company_status"]!=1||$company["deleted"]==1){
				$this->error("商户已经被封，请联系客服！",U('Public/logout'));
			}
			if($company["end_time"]<time()){
				$this->error("服务已经到期，请联系客服！",U('Public/logout'));
			}
			$this->MUser["shop_id"]=get_shop_id();
    		if(!$this->check_access($session_admin_id)){
				if(IS_AJAX){
					$status['msg']="您没有权限！";
					$this->ajaxReturn($status);
				}
				else{
					$this->error("您没有访问权限！");
				}
			}
			$this->_do_access_log();
			if(!empty($BEmployee['employee_name'])){
				$user['user_nicename']=$BEmployee['employee_name'];
			}
			$this->assign("admin",$user);
		}else{
			//$this->error("您还没有登录！",U("admin/public/login"));
			if(IS_AJAX){
				$this->error("您还没有登录！",U("shop/public/login"));
			}else{
				//header("Location:".U("admin/public/login"));
				echo("<script>window.parent.location.href='".U("shop/public/login")."'</script>");
				exit();
			}

		}
	}

	/**
	 * 初始化后台菜单
	 */
	public function initMenu() {
		$Menu = F("BMenu");
		if (!$Menu) {
			$Menu=D("BMenu")->menu_cache();
		}
		return $Menu;
	}
	/**
	 * 初始化门店后台菜单
	 */
	public function initShopMenu($is_shop=0) {
		$Menu = F("BShopMenu");
		if (!$Menu) {
			$Menu=D("BMenu")->shop_menu_cache($is_shop);
		}
		return $Menu;
	}
	/**
	 * 消息提示
	 * @param type $message
	 * @param type $jumpUrl
	 * @param type $ajax
	 */
	public function success($message = '', $jumpUrl = '', $ajax = false,$referer_time) {
		parent::success($message, $jumpUrl, $ajax,$referer_time);
	}

	/**
	 * 模板显示
	 * @param type $templateFile 指定要调用的模板文件
	 * @param type $charset 输出编码
	 * @param type $contentType 输出类型
	 * @param string $content 输出内容
	 * 此方法作用在于实现后台模板直接存放在各自项目目录下。例如Admin项目的后台模板，直接存放在Admin/Tpl/目录下
	 */
	public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '') {
        parent::display($this->parseTemplate($templateFile), $charset, $contentType,$content,$prefix);
	}

	/**
	 * 获取输出页面内容
	 * 调用内置的模板引擎fetch方法，
	 * @access protected
	 * @param string $templateFile 指定要调用的模板文件
	 * 默认为空 由系统自动定位模板文件
	 * @param string $content 模板输出内容
	 * @param string $prefix 模板缓存前缀*
	 * @return string
	 */
	public function fetch($templateFile='',$content='',$prefix=''){
		$templateFile = empty($content)?$this->parseTemplate($templateFile):'';
		return parent::fetch($templateFile,$content,$prefix);
	}

	/**
	 * 自动定位模板文件
	 * @access protected
	 * @param string $template 模板文件规则
	 * @return string
	 */
	public function parseTemplate($template='') {
		$tmpl_path=C("SP_ADMIN_TMPL_PATH");
		define("SP_TMPL_PATH", $tmpl_path);
    	if($this->theme) { // 指定模板主题
    	    $theme = $this->theme;
    	}else{
    	    // 获取当前主题名称
    	    $theme      =    C('SP_ADMIN_DEFAULT_THEME');
    	}

		if(is_file($template)) {
			// 获取当前主题的模版路径
			define('THEME_PATH',   $tmpl_path.$theme."/");
			return $template;
		}
		$depr       =   C('TMPL_FILE_DEPR');
		$template   =   str_replace(':', $depr, $template);

		// 获取当前模块
		$module   =  MODULE_NAME."/";
		if(strpos($template,'@')){ // 跨模块调用模版文件
			list($module,$template)  =   explode('@',$template);
		}

		$module =$module."/";

		// 获取当前主题的模版路径
		define('THEME_PATH',   $tmpl_path.$theme."/");

		// 分析模板文件规则
		if('' == $template) {
			// 如果模板文件名为空 按照默认规则定位
			$template = CONTROLLER_NAME . $depr . ACTION_NAME;
		}elseif(false === strpos($template, '/')){
			$template = CONTROLLER_NAME . $depr . $template;
		}

		$cdn_settings=sp_get_option('cdn_settings');
		if(!empty($cdn_settings['cdn_static_root'])){
		    $cdn_static_root=rtrim($cdn_settings['cdn_static_root'],'/');
		    C("TMPL_PARSE_STRING.__TMPL__",$cdn_static_root."/".THEME_PATH);
		    C("TMPL_PARSE_STRING.__PUBLIC__",$cdn_static_root."/public");
		    C("TMPL_PARSE_STRING.__WEB_ROOT__",$cdn_static_root);
		}else{
		    C("TMPL_PARSE_STRING.__TMPL__",__ROOT__."/".THEME_PATH);
		}
		

		C('SP_VIEW_PATH',$tmpl_path);
		C('DEFAULT_THEME',$theme);
		define("SP_CURRENT_THEME", $theme);

		$file = sp_add_template_file_suffix(THEME_PATH.$module.$template);
		$file= str_replace("//",'/',$file);
		if(!file_exists_case($file)) E(L('_TEMPLATE_NOT_EXIST_').':'.$file);
		return $file;
    }

    /**
     *  排序 排序字段为listorders数组 POST 排序字段为：listorder或者自定义字段
     */
    protected function _listorders($model,$custom_field='') {
        if (!is_object($model)) {
            return false;
        }
        $field=empty($custom_field)&&is_string($custom_field)?'listorder':$custom_field;
        $pk = $model->getPk(); //获取主键名称
        $ids = $_POST['listorders'];
        foreach ($ids as $key => $r) {
            $data[$field] = $r;
            $model->where(array($pk => $key))->save($data);
        }
        return true;
    }
	/**
	 * 后台分页
	 *
	 */
	protected function page($total_size = 1, $page_size = 0, $current_page = 1, $listRows = 6, $pageParam = '', $pageLink = '', $static = FALSE) {
		if ($page_size == 0) {
			$page_size = C("PAGE_LISTROWS");
		}

		if (empty($pageParam)) {
			$pageParam = C("VAR_PAGE");
		}

		$page = new \Page($total_size, $page_size, $current_page, $listRows, $pageParam, $pageLink, $static);
		$page->SetPager('Admin', '{first}{prev}&nbsp;{liststart}{list}{listend}&nbsp;{next}{last}<span>共{recordcount}条数据</span>跳转到{jump}', array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "","jump" => "input"));
		return $page;
	}

	private function check_access($uid){
		//如果用户角色是1，则无需判断
		if($uid == 1){
			return true;
		}

		$rule=MODULE_NAME.CONTROLLER_NAME.ACTION_NAME;
		$no_need_check_rules=array("ShopIndexindex","ShopMainindex");

		if( !in_array($rule,$no_need_check_rules) ){
			return sp_auth_check($uid,$name=null,$relation='or',$model='b');
		}else{
			return true;
		}
	}

    private function load_app_admin_menu_lang(){
		$default_lang=C('DEFAULT_LANG');
		$langSet=C('ADMIN_LANG_SWITCH_ON',null,false)?LANG_SET:$default_lang;
		if($default_lang!=$langSet){
			$admin_menu_lang_file=SPAPP.MODULE_NAME."/Lang/".$langSet."/business_bmenu.php";
			$admin_menu_lang_file2=SPAPP."System"."/Lang/".$langSet."/system_amenu.php";
		}else{
			$admin_menu_lang_file=SITE_PATH."data/lang/".MODULE_NAME."/Lang/$langSet/business_bmenu.php";
			if(!file_exists_case($admin_menu_lang_file)){
				$admin_menu_lang_file=SPAPP.MODULE_NAME."/Lang/".$langSet."/business_bmenu.php";
			}
			$admin_menu_lang_file2=SITE_PATH."data/lang/"."System"."/Lang/$langSet/system_amenu.php";
			if(!file_exists_case($admin_menu_lang_file2)){
				$admin_menu_lang_file2=SPAPP."System"."/Lang/".$langSet."/system_amenu.php";
			}
		}
		if(is_file($admin_menu_lang_file)){
			$lang=include $admin_menu_lang_file;
			L($lang);
		}
		if(is_file($admin_menu_lang_file2)){
			$lang2=include $admin_menu_lang_file2;
			L($lang2);
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
	
	
	private function load_menu_lang(){
        $default_lang=C('DEFAULT_LANG');
        
        $langSet=C('ADMIN_LANG_SWITCH_ON',null,false)?LANG_SET:$default_lang;
        
	    $apps=sp_scan_dir(SPAPP."*",GLOB_ONLYDIR);
	    $error_menus=array();
	    foreach ($apps as $app){
	        if(is_dir(SPAPP.$app)){
	            if($default_lang!=$langSet){
	                $admin_menu_lang_file=SPAPP.$app."/Lang/".$langSet."/business_bmenu.php";
	            }else{
	                $admin_menu_lang_file=SITE_PATH."data/lang/$app/Lang/".$langSet."/business_bmenu.php";
	                if(!file_exists_case($admin_menu_lang_file)){
	                    $admin_menu_lang_file=SPAPP.$app."/Lang/".$langSet."/business_bmenu.php";
	                }
	            }
	            
	            if(is_file($admin_menu_lang_file)){
	                $lang=include $admin_menu_lang_file;
	                L($lang);
	            }
	        }
	    }
    }
	/**
	 * 将所属表的所有参数输出
	 */
	public function b_show_status($table){
		$status_model=D("BStatus");
		$status_list=$status_model->get_status_list("`table`='".M($table)->getTableName()."'");
		if(!empty($status_list)){
			foreach($status_list as $key=>$val){
				$s_id=$val['id'];
				$field=$val['field'];
				$tpl=array();
				$value_list=$status_model->get_value_list(array('s_id'=>$s_id));
				if(!empty($value_list)){
					foreach($value_list as $k =>$v){
						$tpl[$v['value']]=$v['comment'];
					}
				}
				$this->assign($field,$tpl);
			}
		}
	}
	/****判断是否admin*************/
	public function is_admin(){
		if($this->MUser["id"]==1){
			return true;
		}else{
			return false;
		}
	}
	/****排序*************/
	public function order_table($table,$default_order){
		$default_order=empty($default_order)?$table.".id desc":$default_order;
		$order_field=$table.".".I('field')."  ".I("order");
		$order=empty(I('field'))?$default_order:$order_field;
		return $order;
	}
	/****分页*************/
	public function layui_page($count){
		$page = $this->page($count, $this->pagenum, I("page"), $List_Page = 6, $PageParam = 'page');
		return $page;
	}
	/****展示*************/
	public function layui_display($model,$data){
		$data["code"]=0;
		if(empty(I("data_type"))){
			$model->display();
		}else{
			$model->ajaxReturn($data);
		}
	}
	/**
	* 访问日志
	*/
	protected function _do_access_log(){
		$user_id=get_user_id();
		$user_info=D("MUsers")->getInfo(array("id"=>$user_id));
		if(!empty($user_info)){
			$insert=array(
				'company_id'=>get_company_id(),
				'user_id'=>$user_info['id'],
				'user_name'=>!empty($user_info['realname'])? $user_info['realname']:(!empty($user_info['user_nicename'])? $user_info['user_nicename']:(!empty($user_info['user_login'])? $user_info['user_login']:(!empty($user_info['mobile'])?$user_info['mobile']:''))),
			);
		}
		if(!empty($insert)){
			@D('BAccessLog')->addLog($insert);
		}

	}
}