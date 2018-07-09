<?php
// author：czy--仓库管理
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BRecoveryProductController extends BusinessbaseController {

    public function __construct() {
        parent::__construct();
        $this->b_show_status('b_recovery_product');
        $this->brecovery_product_model=D('BRecoveryProduct');
        $this->bwarehouse_model=D('BWarehouse');
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        $search_name = trim(I('search_name/s'));
        if(!empty($search_name)){
            $condition['rproduct.recovery_name|rproduct.rproduct_code|rproduct.sub_rproduct_code'] = array('like', '%' . trim($search_name) . '%');
        }
        //仓库
        $wh_id = I("wh_id");
        if ($wh_id > 0) {
            $condition["rproduct.wh_id"] = $wh_id;
        }
        // 状态
        $type = I("request.type");
        if (is_numeric($type) && $type != 'all') {
            $condition ["rproduct.type"] = $type;
        }
        // 状态
        $status = I("request.status");
        if (!empty($status) && $status != 'all') {
            $condition ["rproduct.status"] = $status;
        }else{
            $condition ["rproduct.status"] = array('neq',0);
        }
        if(I('begin_time')){
            $begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
           // $begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
            $condition['rproduct.create_time'] = array('gt', $begin_time);
        }

        if(I('end_time')){
            $end_time = I('end_time') ? strtotime(I('end_time')) : time();
           // $end_time = strtotime(date('Y-m-d 23:59:59', $end_time));
            if(isset($begin_time)){
                $p1 = $condition['rproduct.create_time'];
                unset($condition['rproduct.create_time']);
                $condition['rproduct.create_time'] = array($p1, array('lt', $end_time));
            }else{
                $condition['rproduct.create_time'] = array('lt', $end_time);
            }
        }
        $ex_where = array_merge($condition, $ex_where);
    }
    //金料列表
    public function rproduct_list(){
        $condition = array();
        $condition['deleted'] = 0;
        $condition['company_id']=get_company_id();
        $warehouse = D("Business/BWarehouse")->getList($condition);
        $this->assign("warehouse", $warehouse);
        $this->_getlist();
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
    /**
     * @param array $where
     * @param $type      是否显示复选框 $type不为空则为显示
     */
    public function _getlist($where=array(),$type){
        $condition=array("rproduct.company_id"=>$this->MUser['company_id'],"rproduct.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $this->handleSearch($condition);
        $join='left join '.DB_PRE.'b_warehouse b_warehouse on b_warehouse.id=rproduct.wh_id';
        $count = $this->brecovery_product_model->alias("rproduct")->countList($condition,$field = 'rproduct.*', $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow . "," . $page->listRows;
        $data = $this->brecovery_product_model->alias("rproduct")->getList($condition, $field = 'rproduct.*,b_warehouse.wh_name', $limit, $join, $order ='rproduct.id desc');
        $this->assign("page", $page->show('Admin'));
        $this->assign("list", $data);
        if($type){
            $this->assign("checkbox_list", $type);
        }
    }
}