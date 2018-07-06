<?php
/**
 * 后台首页
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
class AccessLogController extends BusinessbaseController {
    protected $model_access_log;
    public function __construct(){
        parent::__construct();
        @$this->model_access_log=D('BAccessLog');
    }

    // 访问记录首页
    public function index(){
        $condition=get_user_id()==1?array("1"=>"1"):array('company_id'=>get_company_id());
        if(!empty(I('request.search_name'))){
            $condition['user_name|access_path|access_ip']=array('like','%'.I('request.search_name').'%');
        }

        $access_type = I('access_type/d', -1);
        if($access_type >= 0){
            $condition['access_type'] = $access_type;
        }
        $this->assign('search_access_type', $access_type);

        $count=$this->model_access_log->countLogList($condition);
        $page = $this->page($count, $this->pagenum);
        $log_list=$this->model_access_log->getLogList($condition,'*',$page->firstRow.','.$page->listRows,'','id desc');

        $access_type_list = $this->model_access_log->getAccessTypeList();

        $this->assign('access_type', $access_type_list['data']);
        $this->assign('access_type_list', $access_type_list);
        $this->assign('log_list', $log_list);
        $this->assign("page", $page->show('Admin'));

        $this->display();
    }

    // 获取访问记录详情
    public function detail(){
        $id = I('get.id');

        $log_info = $this->model_access_log->getLogDetail(array('id'=>$id));
        $access_type_list = $this->model_access_log->getAccessTypeList();

        $this->assign('access_type', $access_type_list['data']);
        $this->assign('access_type_list', $access_type_list);
        $this->assign('detail', $log_info);

        $this->display();
    }
}