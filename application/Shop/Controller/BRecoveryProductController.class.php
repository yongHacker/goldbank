<?php
// author：czy--仓库管理
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BRecoveryProductController extends ShopbaseController {

    public function __construct() {
        parent::__construct();
        $this->b_show_status('b_recovery_product');
        $this->brecovery_product_model=D('BRecoveryProduct');
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        if($getdata["search_name"]){
            $condition["brecovery.batch|brecovery.name|recovery_musers.client_name|recovery_musers.client_moblie|create_musers.employee_name"]=array("like","%".$getdata["search_name"]."%");
        }
        // 状态
        $status = I("request.status");
        if ($status && $status != 'all') {
            $condition ["rproduct.status"] = $status;
        }
        if(I('begin_time')){
            $begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
           // $begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
            $condition['brecovery.create_time'] = array('gt', $begin_time);
        }

        if(I('end_time')){
            $end_time = I('end_time') ? strtotime(I('end_time')) : time();
           // $end_time = strtotime(date('Y-m-d 23:59:59', $end_time));
            if(isset($begin_time)){
                $p1 = $condition['brecovery.create_time'];
                unset($condition['brecovery.create_time']);
                $condition['brecovery.create_time'] = array($p1, array('lt', $end_time));
            }else{
                $condition['brecovery.create_time'] = array('lt', $end_time);
            }
        }
        if($getdata['shop_id']>0){$condition['brecovery.shop_id']=$getdata['shop_id'];}
        if($getdata['shop_id']==-1){$condition['brecovery.shop_id']=0;}
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }

    public function rproduct_list(){

        $where = array(
            'rproduct.company_id'=> get_company_id(),
            'rproduct.deleted'=> 0,
            'rproduct.status'=>array('neq',0),
            'b_warehouse.shop_id'=>get_shop_id()
        );

        $search_name = trim(I('search_name/s'));
        if(!empty($search_name)){
            $where['rproduct.recovery_name|rproduct.rproduct_code'] = array('like', '%' . $search_name . '%');
        }
        // 状态
        $type = I("request.type");
        if (is_numeric($type)&& $type != 'all') {
            $where ["rproduct.type"] = $type;
        }
        // 状态
        $status = I("request.status");
        if (!empty($status) && $status != 'all') {
            $where ["rproduct.status"] = $status;
        }else{
            $where ["rproduct.status"] = array('neq',0);
        }
        $join='left join '.DB_PRE.'b_warehouse b_warehouse on b_warehouse.id=rproduct.wh_id';
        $count = $this->brecovery_product_model->alias("rproduct")->countList($where,$field = 'rproduct.*', $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow . "," . $page->listRows;
        $data = $this->brecovery_product_model->alias("rproduct")->getList($where, $field = 'rproduct.*,b_warehouse.wh_name', $limit, $join, $order ='rproduct.id desc');
        $this->assign("page", $page->show('Admin'));
        $this->assign("list", $data);
        $this->display();
    }
    
    // 导出 excel
    public function rproduct_export(){
        $where = array(
            'rproduct.company_id'=> get_company_id(),
            'rproduct.deleted'=> 0
        );
        $search_name = trim(I('search_name/s'));
        if(!empty($search_name)){
            $where['rproduct.recovery_name|rproduct.rproduct_code'] = array('like', '%'. $search_name .'%');
        }
        $this->brecovery_product_model->excel_out($where);
    }

    //获取列表数据
    public function _getlist($where=array(),$type){
        $condition=array("rproduct.company_id"=>$this->MUser['company_id'],"rproduct.deleted"=>0,"rproduct.status"=>array('neq'=>0));
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $this->handleSearch($condition);
        $join="";
        $field="*";
        $count=$this->brecovery_product_model->alias("rproduct")->countList($condition,$field,$join,$order='rproduct.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $data=$this->brecovery_product_model->alias("rproduct")->getList($condition,$field,$limit,$join,$order='rproduct.id desc','rproduct.id');
        $this->assign("page", $page->show('Admin'));
        $this->assign("list", $data);
        if($type){
            $this->assign("checkbox_list", $type);
        }
    }
    function index(){

    }
}