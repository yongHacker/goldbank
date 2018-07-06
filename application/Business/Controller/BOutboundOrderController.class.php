<?php
// author：czy--仓库管理
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Business\Model\BBillOpRecordModel;
use Business\Model\BOutboundOrderModel;

class BOutboundOrderController extends BusinessbaseController {

    public function __construct() {
        parent::__construct();
        $this->bwarehouse_model=D("BWarehouse");
        $this->boutbounddetail_model=D("BOutboundDetail");
        $this->boutboundorder_model=D("BOutboundOrder");
        $this->bproduct_model = D('BProduct');
        $this->b_show_status('b_outbound_order');
    }
    //获取操作记录和流程
    private function get_operate_info($id){
        /*表单的操作记录 add by lzy 2018.5.26 start*/
        $operate_record = $this->boutboundorder_model->getOperateRecord($id);
        $this->assign('operate_record', $operate_record);
        //表单的操作流程
        $operate_process = $this->boutboundorder_model->getProcess($id);
        $this->assign('process_list', $operate_process);
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        if($getdata["search_name"]){
            $condition["boutbound.batch|create_musers.employee_name|b_client.client_name"]=array("like","%".trim($getdata["search_name"])."%");
        }
        $status=$getdata['status'];
        if(is_numeric($status)&&$status!='all'){
            $condition=array("boutbound.status"=>$status);
        }
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }
    //获取出库列表数据
    public function _getlist($where){
        $getdata=I("");
        $condition=array("boutbound.company_id"=>$this->MUser['company_id'],"boutbound.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $this->handleSearch($condition);
        $join=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=boutbound.user_id and create_musers.deleted=0 and create_musers.company_id=boutbound.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=boutbound.check_id  and check_musers.deleted=0 and check_musers.company_id=boutbound.company_id";
        /*$join=" left join ".DB_PRE."m_users create_musers on create_musers.id=boutbound.user_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=boutbound.check_id";*/
        $join.=" left join ".DB_PRE."b_warehouse wh on wh.id=boutbound.warehouse_id";
        $join.=" left join ".DB_PRE."b_client b_client on b_client.id=boutbound.client_id";
        $field="boutbound.*,create_musers.employee_name user_nicename,check_musers.employee_name check_name,wh.wh_name,b_client.client_name";
        $count=$this->boutboundorder_model->alias("boutbound")->countList($condition,$field,$join,$order='boutbound.id desc');
       // $Page_Size=empty(I("limit"))?10:I("limit");
        $page = $this->page($count,$this->pagenum);
       // $page = $this->page($count, $Page_Size, I("page"), $List_Page = 6, $PageParam = 'page');
        $limit=$page->firstRow.",".$page->listRows;
        $order=empty(I('field'))?"boutbound.id desc":"boutbound.".I('field')."  ".I("order");
        $data=$this->boutboundorder_model->alias("boutbound")->getList($condition,$field,$limit,$join,$order);
        foreach($data as $k=>$v){
            $data[$k]["count"]=$this->boutbounddetail_model->countList($condition=array("deleted"=>0,"outbound_id"=>$v["id"]));
        }
        $status_model = D ( 'b_status' );
        $condition=array();
        $condition ["table"] = DB_PRE.'b_outbound_order';
        $condition ["field"] = 'status';
        $status_list = $status_model->getStatusInfo ( $condition );

        $this->assign ( "status_list", $status_list );
        $this->assign("page", $page->show('Admin'));
        $this->assign("list", $data);
    }
    //通过出库单id获取出库明细并分页
    public function getList_detail($out_id){
        if($out_id){
            $condition=array("outbound_id"=>$out_id,"boutbounddetail.deleted"=>0);
        }else{
            $condition=array("boutbounddetail.deleted"=>0);;
        }
        $join="left join ".DB_PRE."b_product bproduct on bproduct.id=boutbounddetail.p_id";
        $join.=$this->bproduct_model->get_product_join_str();
        $field="boutbounddetail.*,bproduct.goods_id,bproduct.type,bproduct.product_code";
        $field.=$this->bproduct_model->get_product_field_str(3);
        $count=$this->boutbounddetail_model->alias("boutbounddetail")->countList($condition,$field,$join,$order='boutbounddetail.id desc');
        $page = $this->page($count,$this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $detail_data=$this->boutbounddetail_model->alias("boutbounddetail")->getList($condition,$field,$limit,$join,$order='boutbounddetail.id desc');
        foreach($detail_data as $k=>$v){
            $detail_data[$k]["product_detail"]=strip_tags($this->bproduct_model->get_product_detail_html($v));
            $detail_data[$k]["product_pic"]=$this->bproduct_model->get_goods_pic($v['photo_switch'],$v['goods_id'],$v['p_id']);
        }
        //金料信息
        $recovery_product_list = D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$out_id,'brecoverydetail.type'=>5));
        $this->assign("recovery_product_list",$recovery_product_list);
        $this->assign("page", $page->show('Admin'));
        $this->assign("list", $detail_data);
    }
/*************************************出库******************************************************/
    // 添加出库单
    public function add() {
        $postdata=I("");
        if(empty($postdata)){
            $url = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_ADDR'].$_SERVER['SERVER_PORT']);
            $example_excel = 'http://'.$url.'/Uploads/excel/out_example.xlsx';
            $this->assign("example_excel", $example_excel);
            $shop_id=$this->MUser['shop_id'];
            $condition=array("bwarehouse.deleted"=>0,"bwarehouse.company_id"=>$this->MUser['company_id'],"bwarehouse.shop_id"=>$shop_id);
            $join="left join ".DB_PRE."m_users muser on muser.id=bwarehouse.wh_uid";
            $field='bwarehouse.*,muser.user_nicename';
            $data=$this->bwarehouse_model->alias("bwarehouse")->getList($condition,$field,$limit="",$join,$order='bwarehouse.id desc');
            $wh_data=D("BWarehouse")->get_wh_list();
            $this->assign ( "warehouse", $wh_data );
            $this->assign("data", $data);
            $today = date('Y-m-d', time());
            $this->assign("today", $today);
            $this->display();
        }else{
            if (IS_POST) {
                // 判断是否内部员工，是则添加为客户
                $order = I('post.order');
                if (empty($order['client_id']) && !empty($order['employee_id'])) {
                    A('BClient')->_addClientByEmployee($order['employee_id'], $order['client_id']);
                    $_POST['order'] = $order;
                }
                $info=$this->boutboundorder_model->add_post();
                $this->ajaxReturn($info);
            }
        }
    }
    // 出库查询获取货品
    public function product_list() {
        if(I("mystore")){
            $where=array("bproduct.status"=>2);
            $where["bproduct.warehouse_id"]=I("mystore");
            if(I('type')){
                $where["bproduct.type"]=1;
            }
            A("Business/BProduct")->product_list($where,3);
            $this->display();
        }else{
            $this->assign("empty_info","请选择仓库");
            $this->display(":default_empty");
        }

    }
    //采购清单
    public function procure_list() {
        $this->display();
    }

    // 出库记录
    public function index() {
        $this->_getlist();
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 出库记录详情
    public function index_detail() {
        $data = $this->boutboundorder_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        $this->get_operate_info($data["id"]);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 出库编辑
    public function edit() {
        if(IS_POST){
            // 判断是否内部员工，是则添加为客户
            $order = I('post.order');
            if (empty($order['client_id']) && !empty($order['employee_id'])) {
                A('BClient')->_addClientByEmployee($order['employee_id'], $order['client_id']);
                $_POST['order'] = $order;
            }
            $info=$this->boutboundorder_model->edit_post();
            $this->ajaxReturn($info);
        }else{
            $data['boutbound_data']=$this->boutboundorder_model->getInfo_detail();
            $data['detail_data']=$this->boutbounddetail_model->getList_detail($data['boutbound_data']["id"]);
            $data['wh_data']=$this->bwarehouse_model->getList_detail();
            //金料信息
            $recovery_product_list = D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$data['boutbound_data']["id"],'brecoverydetail.type'=>5));
            $this->assign("recovery_product_list",$recovery_product_list);
            $this->assign("wh_data",$data['wh_data']);
            $this->assign("list",$data['detail_data']);
            $this->assign("boutbound_data",$data['boutbound_data']);
            $this->display();
        }
    }
    // 出库审核列表
    public function check() {
        $getdata=I("");
        $condition=array("boutbound.status"=>0);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 出库审核明细
    public function check_detail() {
        $data=$this->boutboundorder_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        $this->get_operate_info($data["id"]);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 出库审核
    public function check_post() {
        $getdata = I("");
        $outbound_id= I("post.id",0,'intval');
        $data["id"] = $outbound_id;
        $data["status"] =$getdata["type"];
        $data["check_time"] = time();
        $data["check_id"] = $this->MUser['id'];
        $data["check_memo"] = I('post.check_memo');
        $condition=array("id"=>$outbound_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        $boutboundorder=$this->boutboundorder_model->getInfo($condition);
        if($boutboundorder['status']!=0||empty($boutboundorder)){
            $result["status"] = 0;
            $result["msg"] = "审核失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $boutbound_save=$this->boutboundorder_model->update($condition,$data);
        if($getdata["type"]==2||$getdata["type"]==-2){//审核不通过，审核驳回，货品还原为在库状态
            $data=array("status"=>2);
            $product_save=$this->update_product_status($outbound_id,$data);
        }else{
            $data=array();
            $data["status"]=$boutboundorder['type']==2?8:($boutboundorder['type']==4?11:12);//1，6销售出库 2,8异常出库 3，-1采购退货出库 4，10赠送出库 5，11转料出库
            $product_save=$this->update_product_status($outbound_id,$data);
        }
        $recovery_detail=true;
        //审核并且类型为转料才更改金料状态，驳回不改变
        if($getdata["type"]>0&&$boutboundorder['type']==5) {
            $status = $getdata["type"] == 1 ? 2 :0;//审核通过则为2，审核不通过则为0
            $recovery_detail = D('BRecoveryProduct')->update_status($outbound_id,5,$status);
        }
        //销售审核的操作记录
        $record_status=$getdata["type"]==1?BOutboundOrderModel::CHECK_PASS:($getdata["type"]==2?BOutboundOrderModel::CHECK_DENY:BOutboundOrderModel::CHECK_REJECT);
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::OUTBOUND,$outbound_id,$record_status);//审核通过或不通过或驳回
        if ($recovery_detail!==false&&$record_result&&$boutbound_save!==false&&$product_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BOutboundOrder/check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $result["test"] = $boutbound_save."//".$product_save;
            $this->ajaxReturn($result);
        }
    }
    // 出库撤销
    public function delete() {
        $getdata = I("");
        $outbound_id= I("post.id/d", 0);
        $data["id"] = $outbound_id;
        $data["status"] =3;
        $condition=array("id"=>$outbound_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        $boutboundorder=$this->boutboundorder_model->getInfo($condition);
        if($boutboundorder['status']!=0||empty($boutboundorder)){
            $result["status"] = 0;
            $result["msg"] = "撤销失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $boutbound_save=$this->boutboundorder_model->update($condition,$data);
        $data=array("status"=>2);
        $product_save=$this->update_product_status($outbound_id,$data);
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::OUTBOUND,$outbound_id,BOutboundOrderModel::REVOKE);//撤销
        if ($record_result&&$boutbound_save !== false && $product_save !== false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BOutboundOrder/index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $result["test"] = $boutbound_save."//".$product_save;
            $this->ajaxReturn($result);
        }
    }
 /*****************************************************************************************/
    //更新货品状态
    public function update_product_status($outbound_id,$data){
        $productid=$this->boutboundorder_model->get_pruductids($outbound_id);
        if($productid[0]>0){
            if (count($productid) > 1) {
                $a_c_map["id"] = array("in", $productid);
            } else {
                $a_c_map["id"] = $productid[0];
            }
            //$data=array("status"=>2,"warehouse_id"=>$boutbound["to_id"]);
            $product_save=$this->bproduct_model->update($a_c_map,$data);
            return $product_save;
        }else{
            return false;
        }

    }
    //导出列表数据
    function export_excel($page=1){
        $condition=array("boutbound.company_id"=>$this->MUser['company_id'],"boutbound.deleted"=>0);
        $this->handleSearch($condition);
        $this->boutboundorder_model->excel_out($condition);
    }
    //出库关联客户列表
    function client_list(){
        A('BClient')->_getClient();
        $client_list_tpl=$this->fetch('BClient/client_list');
        $this->assign('client_list_tpl', $client_list_tpl);
        $this->display();
    }
    //添加客户
    public function add_client(){
        if(IS_POST){
            $shop_id=$_POST['shop_id'];
            $status=D('BClient')->add_post();
            if ($status['status'] == 1) {
                $this->success($status['msg'], U("BOutboundOrder/client_list",array('shop_id'=>$shop_id)));
            } else {
                $this->error($status['msg']);
            }
        }else{
            $condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]);
            if(get_shop_id()){
                $condition["id"]=get_shop_id();
                $this->assign('shop_id', get_shop_id());
            }
            $shopdata = D("BShop")->getList($condition,$field='*',$limit=null,$join='',$order='',$group='');
            $this->assign('shopdata', $shopdata);
            $add_tpl=$this->fetch('BClient/add_tpl');
            $this->assign('add_tpl', $add_tpl);
            $this->display();
        }
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
        $info = uploadExcel($file_name, $tmp_name);
        $datas = array('status'=> 0, 'msg'=> '上传失败');
        if ($info['status'] == 1&&count($info['data'])>0) {
            $product_codes="0";
            $is_check_count=0;
            $is_new_count=0;
            foreach($info['data'] as $k=>$v){
                if(!in_array($v[0],$is_check_product_codes)){
                    $product_codes.=",".trim($v[0]);
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
}