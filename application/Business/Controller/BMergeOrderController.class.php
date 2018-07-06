<?php
// author：czy--仓库管理
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Business\Model\BBillOpRecordModel;
use Business\Model\BMergeOrderModel;

class BMergeOrderController extends BusinessbaseController {

    public function __construct() {
        parent::__construct();
        $this->bwarehouse_model=D("BWarehouse");
        $this->bmerge_order_model=D("BMergeOrder");
        $this->bmerge_detail_model=D("BMergeDetail");
        $this->bproduct_model = D('BRecoveryProduct');
        $this->b_show_status('b_merge_order');
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        if($getdata["search_name"]){
            $condition["bmerge_order.batch|create_musers.employee_name"]=array("like","%".$getdata["search_name"]."%");
        }
        $status=$getdata['status'];
        if(is_numeric($status)&&$status!='all'){
            $condition["bmerge_order.status"]=$status;
        }
        // 所属仓库
        $wh_id = I("wh_id");
        if ($wh_id > 0 || $wh_id === '0') {
            $condition ["bmerge_order.warehouse_id"] = $wh_id;
        }
        if(I('begin_time')){
            $begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
            // $begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
            $condition['bmerge_order.create_time'] = array('gt', $begin_time);
        }

        if(I('end_time')){
            $end_time = I('end_time') ? strtotime(I('end_time')) : time();
            // $end_time = strtotime(date('Y-m-d 23:59:59', $end_time));
            if(isset($begin_time)){
                $p1 = $condition['bmerge_order.create_time'];
                unset($condition['bmerge_order.create_time']);
                $condition['bmerge_order.create_time'] = array($p1, array('lt', $end_time));
            }else{
                $condition['bmerge_order.create_time'] = array('lt', $end_time);
            }
        }
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }
    //获取调拨列表数据
    public function _getlist($where){
        $getdata=I("");
        $condition=array("bmerge_order.company_id"=>$this->MUser['company_id'],"bmerge_order.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }

        $this->handleSearch($condition);
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on bmerge_order.warehouse_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=bmerge_order.creator_id and create_musers.deleted=0 and create_musers.company_id=bmerge_order.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=bmerge_order.check_id  and check_musers.deleted=0 and check_musers.company_id=bmerge_order.company_id";
        $field="bmerge_order.*,from_bwarehouse.wh_name from_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name";
        $count=$this->bmerge_order_model->alias("bmerge_order")->countList($condition,$field,$join,$order='bmerge_order.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $data=$this->bmerge_order_model->alias("bmerge_order")->getList($condition,$field,$limit,$join,$order='bmerge_order.id desc');
        $warehouse=$this->bwarehouse_model->getList_detail();
        $this->assign("warehouse", $warehouse);
        $this->assign("page", $page->show('Admin'));
        $this->assign("list", $data);
    }
    //通过调拨单id获取调拨明细与其详细信息
    public function getList_detail($where){
        //调拨单信息
        $data=$this->bmerge_order_model->getInfo_detail();
        $this->assign("info",$data);
        //调拨明细
        $condition=array("bmerge_detail.deleted"=>0,"bmerge_detail.merge_id"=>$data["id"]);
        if($where){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."b_recovery_product rproduct on rproduct.id=bmerge_detail.p_id";
        $field="bmerge_detail.*,rproduct.rproduct_code,rproduct.recovery_name,rproduct.gold_weight,
        rproduct.total_weight,rproduct.purity*1000 purity,rproduct.recovery_price,rproduct.gold_price";
        $count=$this->bmerge_detail_model->alias("bmerge_detail")->countList($condition,$field,$join,$order='bmerge_detail.id desc');
        $page = $this->page($count, 500);
        $limit=$page->firstRow.",".$page->listRows;
        $detail_data=$this->bmerge_detail_model->alias("bmerge_detail")->getList($condition,$field,$limit,$join,$order='bmerge_detail.id desc');
        $recovery_product_list = D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$data["id"],'brecoverydetail.type'=>6));
        $this->assign('recovery_product_list', $recovery_product_list);
        /*表单的操作记录 add by lzy 2018.5.26 start*/
        $operate_record=$this->bmerge_order_model->getOperateRecord($condition["bmerge_detail.merge_id"]);
        $this->assign('operate_record', $operate_record);
        //表单的操作流程
        $operate_process=$this->bmerge_order_model->getProcess($condition["bmerge_detail.merge_id"]);
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
            $price=D("BBankGold")->get_setting_price('gold_price');
            $this->assign("price", $price);
            $this->assign("data", $data);
            $today = date('Y-m-d', time());
            $this->assign("today", $today);
            $this->display();
        }else{
            if (IS_POST) {
                //order所有选中货品id//store收货仓id//mystore发货仓id//comment备注//trance_time开单时间//$orderid单号
                $info=$this->bmerge_order_model->add_post();
                $this->ajaxReturn($info);
            }
        }
    }

    // 调拨查询获取货品
    public function rproduct_list() {
        $where=array("rproduct.status"=>2,"rproduct.wh_id"=>I("request.mystore",0,'intval'));
        A("BRecoveryProduct")->_getlist($where,1);
        if(empty(I("request.mystore"))){
            $this->assign("empty_info", "请先选择金料仓库");
        }
        $this->display();
    }
    // 调拨记录
    public function merge_index() {
        $this->_getlist();
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 调拨记录详情
    public function merge_index_detail() {
        $this->getList_detail();
        $this->display();
    }
    // 调拨编辑
    public function edit() {
        if(IS_POST){
            $info = $this->bmerge_order_model->edit_post();
            $this->ajaxReturn($info);
        }else{
            $data['bmerge_order_data']=$this->bmerge_order_model->getInfo_detail();
            $condition=array("bmerge_detail.merge_id"=>$data['bmerge_order_data']["id"]);
            $data['detail_data']=$this->bmerge_detail_model->getList_detail($condition);
            $data['wh_data']=$this->bwarehouse_model->getList_detail();
            $recovery_product_list = D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$data['bmerge_order_data']["id"],'brecoverydetail.type'=>6));
            $price=D("BBankGold")->get_setting_price('gold_price');
            $this->assign("price", $price);
            $this->assign('recovery_product_list', $recovery_product_list);
            $this->assign("wh_data",$data['wh_data']);
            $this->assign("list",$data['detail_data']);
            $this->assign("info",$data['bmerge_order_data']);
            $this->display();
        }
    }

    // 调拨编辑,删除单条调拨货品
    public function detail_delete() {
        $data=$this->bmerge_detail_model->detail_delete();
        $this->ajaxReturn($data);
    }

    // 调拨审核列表
    public function merge_check() {
        $getdata=I("");
        $condition=array("bmerge_order.status"=>0);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 调拨审核明细
    public function merge_check_detail() {
        $this->getList_detail();
        $this->display();
    }
    // 调拨审核
    public function merge_check_post() {
        $getdata = I("");
        $merge_id= I("post.id",0,'intval');
        $data["id"] = $merge_id;
        $data["status"] =$getdata["type"];
        $data["check_time"] = time();
        $data["check_id"] = $this->MUser['id'];
        $data["check_memo"] = I('post.check_memo');
        $condition=array("id"=>$merge_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        $check_info=$this->bmerge_order_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["msg"] = "订单不存在";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $bmerge_order_save=$this->bmerge_order_model->update($condition,$data);
        if($getdata["type"]==2){//审核不通过
            $data=array("status"=>2);
            $product_save=$this->update_product_status($merge_id,$data);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$merge_id,BMergeOrderModel::CHECK_DENY);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }elseif($getdata["type"]==1){//审核通过
            $data=array("status"=>7);
            $data['end_gold_price']=D('BOptions')->get_current_gold_price();
            $product_save=$this->update_product_status($merge_id,$data);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$merge_id,BMergeOrderModel::CHECK_PASS);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }elseif($getdata["type"]==-2){ //驳回
            $data=array("status"=>2);
            $product_save=$this->update_product_status($merge_id,$data);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$merge_id,BMergeOrderModel::CHECK_REJECT);
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
        }
        $recovery_detail=true;
        if($getdata["type"]>0) {
            $status = $getdata["type"] == 1 ? 2 :0;//审核通过则为2，审核不通过则为0
            $recovery_detail = D('BRecoveryProduct')->update_status($merge_id,6,$status);
        }
        //$product_save=true;
        if ($recovery_detail!=false&&$bmerge_order_save!==false&&$product_save!==false&&$record_result) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "审核成功";
            $result["url"] = U("merge_check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "审核失败";
            $result["test"] = $bmerge_order_save."//".$product_save.'//'.$record_result;
            $this->ajaxReturn($result);
        }
    }
    // 调拨撤销
    public function merge_delete() {
        $getdata = I("");
        $merge_id= I("post.id",0,'intval');
        $data["id"] = $merge_id;
        $data["status"] =3;
        $condition=array("id"=>$merge_id,"status"=>0,"company_id"=>$this->MUser["company_id"],'creator_id'=>get_user_id());
        $check_info=$this->bmerge_order_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["info"] = "需要开单人撤销或订单不存在";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $bmerge_order_save=$this->bmerge_order_model->update($condition,$data);
        /*添加表单操作记录 add by lzy 2018.5.30 start*/
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$merge_id,BMergeOrderModel::REVOKE);
        /*添加表单操作记录 add by lzy 2018.5.30 end*/
        $data=array("status"=>2);
        $product_save=$this->update_product_status($merge_id,$data);
        if ($bmerge_order_save!==false&&$product_save!==false&&$record_result) {
       // if ($bmerge_order_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["info"] = "成功";
            $result["url"] = U("merge_index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["info"] = "失败";
            $result["test"] = $bmerge_order_save.'//'.$product_save.'//'.$record_result;
            $this->ajaxReturn($result);
        }
    }

    // 调拨删除
    public function deleted() {
        $getdata = I("");
        $merge_id = I("id/d", 0);

        $data["deleted"] = 1;
        $condition = array(
            "id"=> $merge_id,
            "creator_id"=> get_user_id(),
            'company_id'=> get_company_id(),
            "status"=> array('in', '-1,2,3,4,6')
        );
        M()->startTrans();
        $rs = $this->bmerge_order_model->update($condition, $data);
        if ($rs !== false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("merge_index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }
    //更新货品状态
    public function update_product_status($merge_id,$data){
        $productid=$this->bmerge_order_model->get_pruductids($merge_id);
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
        $condition=array("bmerge_order.company_id"=>$this->MUser['company_id'],"bmerge_order.deleted"=>0);
        $this->handleSearch($condition);
        $this->bmerge_order_model->excel_out($condition);
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