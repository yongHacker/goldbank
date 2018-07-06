<?php
/**
 * 操作记录首页
 */
namespace Business\Controller;
use Business\Controller\BusinessbaseController;
class OperateLogController extends BusinessbaseController {
    protected $model_operatelog,$operate_type,$status;
    public function __construct(){
        parent::__construct();
        $this->model_operatelog=D('BOperateLog');
        $this->operate_type=array(1=>"新增",2=>"修改",3=>"删除");
        $this->status=array(0=>"成功",1=>"失败");
        $this->assign("operate_type",$this->operate_type);
        $this->assign("status",$this->status);
    }
    //获取操作记录列表
    public function index(){
        $condition=get_user_id()==1?array("1"=>"1"):array('company_id'=>get_company_id());
        if(!empty(I('request.search_name'))){
            $condition['user_name|user_mobile']=array('like','%'.I('request.search_name').'%');
        }
        if(!empty(I('request.operate_type'))){
            $condition['operate_type']=I('request.operate_type');
        }
        if(I('request.status')>-1){
            $condition['status']=array('like','%'.I('request.status').'%');
        }

        $condition['company_id']=$this->MUser['company_id'];
        $all_table=$this->model_operatelog->get_tables_arr();
        $table_names=array();
        foreach($all_table as $k=>$v){
            array_push($table_names,C("DB_PREFIX").$v['table_name']);
        }
        $table_names=empty($table_names)?"null":$table_names;
        $this->assign('all_table',$all_table);
        $condition['table_name']=array("in",$table_names);
        if(!empty(I('request.table_name'))){
            $condition['table_name']=array('like','%'.I('request.table_name').'%');
        }
        $count=$this->model_operatelog->countLogList($condition);
        $page = $this->page($count, $this->pagenum);
        $log_list=$this->model_operatelog->getLogList($condition,'*',$page->firstRow.','.$page->listRows,'','id desc');
        foreach ($log_list as $key => $val){
            $table_info=$this->model_operatelog->get_table_name($val['table_name']);
            $log_list[$key]['tablename']=$table_info[0]['table_comment'];
        }
        $this->assign('log_list',$log_list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
        //die(var_dump($log_list));
    }
    //获取操作记录详情
    public function detail(){
        $id=I('get.id');
        $log_info=$this->model_operatelog->getLogDetail(array('id'=>$id));
        $this->assign('detail',$log_info);
        $this->display();
    }
    
}