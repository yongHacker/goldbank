<?php
// author：czy--仓库管理
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Business\Model\BBillOpRecordModel;
use Business\Model\BAllotRproductModel;

class BAllotRproductController extends BusinessbaseController {

    public function __construct() {
        parent::__construct();
        $this->bwarehouse_model=D("BWarehouse");
        $this->ballotdetail_model=D("BAllotRproductDetail");
        $this->ballot_model=D("BAllotRproduct");
        $this->bproduct_model = D('BRecoveryProduct');
        $this->b_show_status('b_allot');
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        if($getdata["search_name"]){
            $condition["ballot.batch"]=array("like","%".$getdata["search_name"]."%");
        }
        $status=$getdata['status'];
        if(isset($status)&&$status>-2){
            $condition=array("ballot.status"=>$status);
        }
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }
    //获取调拨列表数据
    public function _getlist($where){
        $getdata=I("");
        $condition=array("ballot.company_id"=>$this->MUser['company_id'],"ballot.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }

        $this->handleSearch($condition);
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on ballot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on ballot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=ballot.creator_id and create_musers.deleted=0 and create_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=ballot.check_id  and check_musers.deleted=0 and check_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  outbound_musers on outbound_musers.user_id=ballot.outbound_id  and outbound_musers.deleted=0 and outbound_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  receipt_musers on receipt_musers.user_id=ballot.receipt_id  and receipt_musers.deleted=0 and receipt_musers.company_id=ballot.company_id";
        $field="ballot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,outbound_musers.employee_name outbound_name,receipt_musers.employee_name receipt_name";
        $count=$this->ballot_model->alias("ballot")->countList($condition,$field,$join,$order='ballot.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $data=$this->ballot_model->alias("ballot")->getList($condition,$field,$limit,$join,$order='ballot.id desc');
        foreach($data as $k=>$v){
            $data[$k]["count"]=$this->ballotdetail_model->countList($condition=array("deleted"=>0,"allot_id"=>$v["id"]));
        }
        $status_model = D ( 'b_status' );
        $condition = array();
        $condition["table"] = DB_PRE.'b_allot';
        $condition["field"] = 'status';

        $status_list = $status_model->getStatusInfo($condition );
        $this->assign("status_list", $status_list );
        $this->assign("page", $page->show('Admin'));
        $this->assign("list", $data);
    }
    //通过调拨单id获取调拨明细与其详细信息
    public function getList_detail($where){
        //调拨单信息
        $data=$this->ballot_model->getInfo_detail();
        $this->assign("allocation",$data);
        //调拨明细
        $condition=array("ballotdetail.deleted"=>0,"ballotdetail.allot_id"=>$data["id"]);
        if($where){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."b_recovery_product rproduct on rproduct.id=ballotdetail.p_id";
        $field="ballotdetail.*,rproduct.rproduct_code,rproduct.recovery_name,rproduct.total_weight,rproduct.purity
        ,rproduct.gold_weight,rproduct.recovery_price,rproduct.cost_price";
        $count=$this->ballotdetail_model->alias("ballotdetail")->countList($condition,$field,$join,$order='ballotdetail.id desc');
        $page = $this->page($count, 500);
        $limit=$page->firstRow.",".$page->listRows;
        $detail_data=$this->ballotdetail_model->alias("ballotdetail")->getList($condition,$field,$limit,$join,$order='ballotdetail.id desc');
        /*表单的操作记录 add by lzy 2018.5.26 start*/
        $operate_record=$this->ballot_model->getOperateRecord($condition["ballotdetail.allot_id"]);
        $this->assign('operate_record', $operate_record);
        //表单的操作流程
        $operate_process=$this->ballot_model->getProcess($condition["ballotdetail.allot_id"]);
        $this->assign('process_list', $operate_process);//var_dump($operate_process);
        /*表单的操作记录 add by lzy 2018.5.26 end*/
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$detail_data);
    }
/*************************************调拨******************************************************/
    // 添加调拨单
    public function add() {
        $postdata=I("");
        if(empty($postdata)){
            $data=$this->bwarehouse_model->getList_detail();
           if(get_shop_id()>0){
               $this->assign("shop_id", get_shop_id());
           }
            $url = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_ADDR'].$_SERVER['SERVER_PORT']);
            $example_excel = 'http://'.$url.'/Uploads/excel/allot_example.xlsx';
            $this->assign("example_excel", $example_excel);
            $this->assign("data", $data);
            $today = date('Y-m-d', time());
            $this->assign("today", $today);
            $this->display();
        }else{
            if (IS_POST) {
                //order所有选中货品id//store收货仓id//mystore发货仓id//comment备注//trance_time开单时间//$orderid单号
                $info=$this->ballot_model->add_post();
                $this->ajaxReturn($info);
            }
        }
    }

    // 调拨查询获取货品
    public function rproduct_list() {
        $where=array("rproduct.status"=>2,"rproduct.wh_id"=>I("request.mystore",0,'intval'));
        A("BRecoveryProduct")->_getlist($where,1);
        if(empty(I("request.mystore"))){
            $this->assign("empty_info", "请先选择发货仓库");
        }
        $this->display();
    }
    
    // 采购清单
    public function procure_list() {
        $batch = trim(I("batch"));
        $warehouse_id = I("mystore",0,'intval');
        if($batch){
            $where['gb_b_procurement.batch']=array("like","%$batch%");
        }
        
        $join = " join gb_b_procure_storage as ps on ps.procurement_id = gb_b_procurement.id";
        $join .=" join gb_b_product as p on p.storage_id= ps.id and p.status=2 and p.deleted = 0 and p.warehouse_id =".$warehouse_id." and p.company_id = ".$this->MUser['company_id'];
        $join .=" join gb_m_users as u on u.id = gb_b_procurement.creator_id";
        $join .=" join gb_b_supplier as s on s.id= gb_b_procurement.supplier_id";
        
        $field = "gb_b_procurement.*,u.user_nicename,s.company_name";
        $group = "gb_b_procurement.id";
        $order = "gb_b_procurement.id desc";

        $list = D("BProcurement")->getList($where, $field, "", $join, $order, $group);//die(var_dump($warehouse_id));

        $count = $list ? count($list) : 0;
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.",".$page->listRows;
        $procure_list = D("BProcurement")->getList($where, $field, $limit, $join, $order, $group);
        if(empty($procure_list)){
            $this->assign("empty_info","未查询到采购清单");
            $this->display();
        }else{
            $this->assign("procure_list",$procure_list);
            $this->display();
        }
    }
    //调拨清单
    public function allot_list(){
        $batch = trim(I("batch"));
        $warehouse_id = I("mystore",0,'intval');
        $condition=array(
            'gb_b_allot.company_id'=>get_company_id(),
            'gb_b_allot.`to_id`'=>$warehouse_id,
            'gb_b_allot.status'=>7,
            'gb_b_product.status'=>2
        );
        if($batch){
            $condition['gb_b_allot.batch']=array("like","%$batch%");
        }
        
        $join=' JOIN gb_b_allot_detail ON gb_b_allot_detail.allot_id=gb_b_allot.id AND gb_b_allot_detail.`deleted`=0 ';
        $join.=' JOIN gb_b_product ON gb_b_allot_detail.p_id=gb_b_product.`id` AND gb_b_product.`warehouse_id`=gb_b_allot.`to_id` ';
        $join.=' JOIN gb_m_users ON gb_m_users.`id`=gb_b_allot.`creator_id` ';
        $join.=' JOIN gb_b_warehouse ON gb_b_warehouse.`id`=gb_b_allot.`from_id` ';
	    $join.=' JOIN gb_b_warehouse wh ON wh.`id`=gb_b_allot.`to_id` ';
        $field='gb_b_allot.*,gb_b_warehouse.wh_name ,wh.wh_name as fwh_name,COUNT(*) AS allot_num1,gb_m_users.user_nicename';
        $group='gb_b_allot.id';
        $order = "gb_b_allot.id desc";
        
        $list = D("BAllot")->getList($condition, $field,'', $join, $order, $group);//不用countList，用countList无法统计，因为涉及group
        $count = $list ? count($list) : 0;
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.",".$page->listRows;
        $allot_list = D("BAllot")->getList($condition, $field, $limit, $join, $order, $group);//die((D("BAllot")->getLastSql()));
        
        if(empty($allot_list)){
            $this->assign("empty_info","未查询到调拨清单");
            $this->display();
        }else{
            $this->assign("allot_list",$allot_list);
            $this->assign('page',$page->show('Admin'));
            $this->display();
        }
    }

    // 选中采购单
    public function get_pro_product() {
        
        $wh_id = I('mystore/d', 0);

        $ids = I('post.ids');
        $model_wh = D('Business/BWarehouse');
        $product = $model_wh->get_procure_product($ids, $wh_id);

        $text = "";
        $product_ids = "";
        $i = 0;
        $is_check_product_codes=empty(I('post.product_codes'))?array():explode(',',I('post.product_codes'));
        foreach ($product as $key => $val) {
            if(in_array($val['product_code'],$is_check_product_codes)){//避免前端已经选择的货品重复选择
                continue;
            }else{//避免调拨单的货品多次调拨导致货品重复
                array_push($is_check_product_codes,$val['product_code']);
            }
            $product_detail=D("BProduct")->get_product_detail_html($val,"&nbsp;&nbsp;");
            $text .= "<tr>";
            $text .= '<td class="text-center"></td>';
            $text .= ' <td style="padding-left:10px;">' . $val['product_code'] . '</td>';
            $text .= ' <td style="padding-left:10px;">' . $val['goods_name'] . '</td>';
            $text .= ' <td class="text-left">'.$product_detail.'</td>';
            $text .= '<td class="text-center">';
            $text .= '<a href="javascript:void(0);" name="' . $val['id'] . '" class="del" role="button" data-toggle="modal">删除</i></a>';
            $text .= '</td>';
            $text .= ' <td hidden=hidden class="product_id">' . $val['id'] . '</td>';
            $text .= '</tr>';
            if ($i == 0) {
                $product_ids .= "ck" . $val['id'] . "ck";
            } else {
                $product_ids .= ",ck" . $val['id'] . "ck";
            }
            $i = $i + 1;
        }
        $data['text'] = $text;
        $data['product_ids'] = $product_ids;
        output_data($data);
    }
    
    //调拨清单
    public function get_allot_product(){
        $wh_id = I('mystore/d', 0);

        $ids = I('post.ids');
        $model_wh = D('Business/BWarehouse');
        $product = $model_wh->getAllotProduct($ids, $wh_id);

        $text = "";
        $product_ids = "";
        $i = 0;
        $is_check_product_codes=empty(I('post.product_codes'))?array():explode(',',I('post.product_codes'));
        foreach ($product as $key => $val) {
            if(in_array($val['product_code'],$is_check_product_codes)){
                continue;
            }
            $product_detail=D("BProduct")->get_product_detail_html($val,"&nbsp;&nbsp;");
            $text .= "<tr>";
            $text .= '<td class="text-center"></td>';
            $text .= ' <td style="padding-left:10px;">' . $val['product_code'] . '</td>';
            $text .= ' <td style="padding-left:10px;">' . $val['goods_name'] . '</td>';
            $text .= ' <td class="text-left">'.$product_detail.'</td>';
            $text .= '<td class="text-center">';
            $text .= '<a href="javascript:void(0);" name="' . $val['id'] . '" class="del" role="button" data-toggle="modal">删除</i></a>';
            $text .= '</td>';
            $text .= ' <td hidden=hidden class="product_id">' . $val['id'] . '</td>';
            $text .= '</tr>';
            if ($i == 0) {
                $product_ids .= "ck" . $val['id'] . "ck";
            } else {
                $product_ids .= ",ck" . $val['id'] . "ck";
            }
            $i = $i + 1;
        }
        $data['text'] = $text;
        $data['product_ids'] = $product_ids;
        output_data($data);
    }
    
    
    // 调拨记录
    public function allot_index() {
        $this->_getlist();
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 调拨记录详情
    public function allot_index_detail() {
        $this->getList_detail();
        $this->display();
    }
    // 调拨编辑
    public function edit() {
        if(IS_POST){
            $info = $this->ballot_model->edit_post();
            $this->ajaxReturn($info);
        }else{
            $data['ballot_data']=$this->ballot_model->getInfo_detail();
            $condition=array("ballotdetail.allot_id"=>$data['ballot_data']["id"]);
            $data['detail_data']=$this->ballotdetail_model->getList_detail($condition);
            $data['wh_data']=$this->bwarehouse_model->getList_detail();
            $this->assign("wh_data",$data['wh_data']);
            $this->assign("list",$data['detail_data']);
            $this->assign("allocation",$data['ballot_data']);
            $this->display();
        }
    }

    // 调拨编辑,删除单条调拨货品
    public function detail_delete() {
        $data=$this->ballotdetail_model->detail_delete();
        $this->ajaxReturn($data);
    }

    // 调拨审核列表
    public function allot_check() {
        $getdata=I("");
        $condition=array("ballot.status"=>0);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 调拨审核明细
    public function allot_check_detail() {
        $this->getList_detail();
        $this->display();
    }
    // 调拨审核
    public function allot_check_post() {
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $data["id"] = $allot_id;
        $data["status"] =$getdata["type"];
        $data["check_time"] = time();
        $data["check_id"] = $this->MUser['id'];
        $data["check_memo"] = I('post.check_memo');
        $condition=array("id"=>$allot_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        $check_info=$this->ballot_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["msg"] = "审核失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $ballot_save=$this->ballot_model->update($condition,$data);
        if($getdata["type"]==2){//审核不通过
            $data=array("status"=>2);
            $product_save=$this->update_product_status($allot_id,$data);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::ALLOT_CHECK_DENY);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }elseif($getdata["type"]==1){//审核通过
            $product_save=true;
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::ALLOT_CHECK_PASS);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }elseif($getdata["type"]==-2){ //驳回
            $data=array("status"=>2);
            $product_save=$this->update_product_status($allot_id,$data);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::ALLOT_CHECK_REJECT);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }
        
        //$product_save=true;
        if ($ballot_save!==false&&$product_save!==false&&$record_result) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "审核成功";
            $result["url"] = U("allot_check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "审核失败";
            $result["test"] = $ballot_save."//".$product_save.'//'.$record_result;
            $this->ajaxReturn($result);
        }
    }
    // 调拨撤销
    public function allot_delete() {
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $data["id"] = $allot_id;
        $data["status"] =3;
        $condition=array("id"=>$allot_id,"status"=>0,"company_id"=>$this->MUser["company_id"],'creator_id'=>get_user_id());
        $check_info=$this->ballot_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["info"] = "需要开单人撤销或订单不存在";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $ballot_save=$this->ballot_model->update($condition,$data);
        /*添加表单操作记录 add by lzy 2018.5.30 start*/
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::REVOKE);
        /*添加表单操作记录 add by lzy 2018.5.30 end*/
        $data=array("status"=>2);
        $product_save=$this->update_product_status($allot_id,$data);
        if ($ballot_save!==false&&$product_save!==false&&$record_result) {
       // if ($ballot_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["info"] = "成功";
            $result["url"] = U("allot_index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["info"] = "失败";
            $result["test"] = $ballot_save.'//'.$product_save.'//'.$record_result;
            $this->ajaxReturn($result);
        }
    }

    // 调拨删除
    public function deleted() {
        $getdata = I("");
        $allot_id = I("id/d", 0);

        $data["deleted"] = 1;
        $condition = array(
            "id"=> $allot_id,
            "creator_id"=> get_user_id(),
            'company_id'=> get_company_id(),
            "status"=> array('in', '-1,2,3,4,6')
        );
        M()->startTrans();
        $rs = $this->ballot_model->update($condition, $data);

        // if($rs !== false){ 
        //     $where = array(
        //         'allot_id'=> $allot_id
        //     );
        //     $field = 'GROUP_CONCAT(p_id SEPARATOR ",") as p_id_s';
        //     $p_id_arr = $this->ballotdetail_model->getInfo($where, $field);

        //     $where = array(
        //         'company_id'=> get_company_id(),
        //         'id'=> array('in', $p_id_arr['p_id_s'])
        //     );
        //     $update_data = array(
        //         'status'=> 2
        //     );
        //     $rs = $this->bproduct_model->update($where, $update_data);
        // }

        if ($rs !== false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("allot_index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }

/*************************************出库******************************************************/
    // 出库审核列表
    public function outbound_check() {
        $getdata=I("");
        $condition=array("ballot.status"=>1);
        /*if(!check_company_uid()){
            $condition['from_bwarehouse.wh_uid']=get_user_id();
        }*/
        $condition['from_bwarehouse.wh_uid']=get_user_id();
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 出库审核明细
    public function outbound_check_detail() {
        $this->getList_detail();
        $this->display();
    }
    // 出库审核
    public function outbound_check_post() {
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $data["id"] = $allot_id;
        $data["status"] =$getdata["type"]==-2?-2:($getdata["type"]==1?5:4);
        $data["outbound_time"] = time();
        $data["outbound_id"] = $this->MUser['id'];
        $data["outbound_memo"] = I('post.check_memo');
        $condition=array("id"=>$allot_id,"status"=>1,"company_id"=>$this->MUser["company_id"]);
        $check_info=$this->ballot_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["msg"] = "审核失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $ballot_save=$this->ballot_model->update($condition,$data);
        if($getdata["type"]==2){
            $data=array("status"=>2);
            $product_save=$this->update_product_status($allot_id,$data);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::OUT_CHECK_DENY);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }elseif($getdata["type"]==1){
            $product_save=true;
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::OUT_CHECK_PASS);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }elseif($getdata["type"]==-2){
            $data=array("status"=>2);
            $product_save=$this->update_product_status($allot_id,$data);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::OUT_CHECK_REJECT);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }
        //$product_save=true;
        if ($ballot_save!==false&&$product_save!==false&&$record_result) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "审核成功";
            $result["url"] = U("outbound_check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "审核失败";
            $result["test"] = $ballot_save."//".$product_save;
            $this->ajaxReturn($result);
        }
    }
/*************************************入库******************************************************/
    // 入库审核列表
    public function receipt_check() {
        $getdata=I("");
        $condition=array("ballot.status"=>5);
        $condition['to_bwarehouse.wh_uid']=get_user_id();
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 入库审核明细
    public function receipt_check_detail() {
        $this->getList_detail();
        $this->display();
    }
    // 入库审核
    public function receipt_check_post() {
        // 货品入库审核
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $productid=$this->ballot_model->get_pruductids($allot_id);
        $condition=array("id"=>$allot_id,"status"=>5,"company_id"=>$this->MUser['company_id'],"deleted"=>0);
        $ballot=$this->ballot_model->getInfo($condition);
        if(empty($ballot)){
            $result["status"] = 0;
            $result["msg"] = "调拨单不存在";
            $this->ajaxReturn($result);
        }
        if ($productid[0]) {
            M()->startTrans();
            $data["id"] = $getdata;
            $data["status"] = $getdata["type"]==1?7:6;
            $data["receipt_time"] = time();
            $data["receipt_id"] = $this->MUser['id'];
            $data["receipt_memo"] = I('post.check_memo');
            $condition=array("id"=>$allot_id,"status"=>5,"company_id"=>$this->MUser["company_id"]);
            $ballot_save=$this->ballot_model->update($condition,$data);
            if($getdata["type"]==2){
                $data=array("status"=>2);
                /*添加表单操作记录 add by lzy 2018.5.30 start*/
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::IN_CHECK_DENY);
                /*添加表单操作记录 add by lzy 2018.5.30 end*/
            }else{
                $data=array("status"=>2,"wh_id"=>$ballot["to_id"]);
                /*添加表单操作记录 add by lzy 2018.5.30 start*/
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,BAllotRproductModel::IN_CHECK_PASS);
                /*添加表单操作记录 add by lzy 2018.5.30 end*/
            }
            $product_save=$this->update_product_status($allot_id,$data);

            if ($ballot_save!==false&&$product_save!==false&&$record_result) {
                M()->commit();
                S('session_menu' . get_user_id(), null);
                $result["status"] = 1;
                $result["msg"] = "审核成功";
                $result["url"] = U("receipt_check");
                $this->ajaxReturn($result);
            } else {
                M()->rollback();
                $result["status"] = 0;
                $result["msg"] = "审核失败";
                $result["test"] = $ballot_save."//".$product_save;
                $this->ajaxReturn($result);
            }
        } else {
            $result["status"] = 0;
            $result["msg"] = "审核失败";
            $this->ajaxReturn($result);
        }
    }
    //更新货品状态
    public function update_product_status($allot_id,$data){
        $productid=$this->ballot_model->get_pruductids($allot_id);
        if($productid[0]>0){
            if (count($productid) > 1) {
                $a_c_map["id"] = array("in", $productid);
            } else {
                $a_c_map["id"] = $productid[0];
            }
            $product_save=$this->bproduct_model->update($a_c_map,$data);
            return $product_save;
        }else{
            return false;
        }
    }

    // 导出列表数据
    function export_excel($page=1){
        $condition=array("ballot.company_id"=>$this->MUser['company_id'],"ballot.deleted"=>0);
        $this->handleSearch($condition);
        $this->ballot_model->excel_out($condition);
    }

    function excel_input(){
        $file_name = $_FILES['excel_file']['name'];
        $tmp_name = $_FILES['excel_file']['tmp_name'];
        $warehouse_id = I("get.mystore",0,"intval");
        $is_check_product_codes =empty(I('get.product_codes'))?array():explode(',',I('get.product_codes'));
        if(empty($warehouse_id)){
            $datas = array('status'=> 0, 'msg'=> '请先选择仓库');
            output_data($datas);
        }
        $info = $this->uploadExcel($file_name, $tmp_name);
        $datas = array('status'=> 0, 'msg'=> '上传失败');
        if ($info['status'] == 1&&count($info['data'])>0) {
            $product_codes="0";
            $is_check_count=0;
            $is_new_count=0;
            foreach($info['data'] as $k=>$v){
                if(!in_array($v[0],$is_check_product_codes)){
                    $product_codes.=",".$v[0];
                    $is_new_count+=1;
                }else{
                    $is_check_count+=1;
                }
            }
            $product=D("BProduct")->get_product_by_excel($product_codes,$warehouse_id);
            $text = "";
            $product_ids = "";
            $i = 0;
            foreach ($product as $key => $val) {
                $product_detail=D("BProduct")->get_product_detail_html($val,"&nbsp;&nbsp;");
                $text .= "<tr>";
                $text .= '<td class="text-center"></td>';
                $text .= ' <td style="padding-left:10px;">' . $val['product_code'] . '</td>';
                $text .= ' <td style="padding-left:10px;">' . $val['goods_name'] . '</td>';
                $text .= ' <td class="text-left">'.$product_detail.'</td>';
                $text .= '<td class="text-center">';
                $text .= '<a href="javascript:void(0);" name="' . $val['id'] . '" class="del" role="button" data-toggle="modal">删除</i></a>';
                $text .= '</td>';
                $text .= ' <td hidden=hidden class="product_id">' . $val['id'] . '</td>';
                $text .= '</tr>';
                if ($i == 0) {
                    $product_ids .= "ck" . $val['id'] . "ck";
                } else {
                    $product_ids .= ",ck" . $val['id'] . "ck";
                }
                $i = $i + 1;
            }
            $datas=array("status"=>1);
            $datas['text'] = $text;
            $datas['product_ids'] = $product_ids;
            $c=$is_new_count-count($product);
            if($c>0){
                $datas['msg'] = "excel中有".$c."条数据未能导入，请检查";
            }else{
                $datas['msg'] = "完全导入成功";
            }

        }

        if(empty($product_ids)&&$is_check_count==0){
            $datas = array('status'=> 0, 'msg'=> '没有符合的货品信息，请检查excel');
            output_data($datas);
        }
        output_data($datas);
    }
    // 读取excel文档并查询数据
    private function uploadExcel($file_name, $tmp_name){
        $filePath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/excel/';
        if(!is_dir($filePath)){
            // 递归创建多级文件夹
            mkDirs($filePath);
        }

        require_once VENDOR_PATH.'PHPExcel/PHPExcel.php';
        require_once VENDOR_PATH.'/PHPExcel/PHPExcel/IOFactory.php';
        require_once VENDOR_PATH.'/PHPExcel/PHPExcel/Reader/Excel5.php';

        $time = time();
        $extend = strrchr($file_name, '.');
        $name = $time . $extend;
        $uploadfile = $filePath . $name;
        $result = move_uploaded_file($tmp_name, $uploadfile);
        if($result){
            $data = excel_to_array($extend, $uploadfile);
        }

        @unlink($file_name);
        $info = array();
        if(!empty($data)){
            $info['data'] = $data;
            $info['status'] = 1;
        }else{
            $info['status'] = 0;
        }

        return $info;
    }
}