<?php
// author：czy--回购管理
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;
use Shop\Model\BBillOpRecordModel;
use Shop\Model\BRecoveryModel;

class BRecoveryController extends ShopbaseController {

    public function __construct() {
        parent::__construct();
        $this->bwarehouse_model=D("BWarehouse");
        $this->bsaccountrecord_model=D("BSaccountRecord");
        $this->bcurrency_model=D("BCurrency");
        $this->bpayment_model=D("BPayment");
        $this->brecoverydetail_model=D("BRecoveryProduct");
        $this->brecovery_model=D("BRecovery");
        $this->bproduct_model = D('BProduct');
        $this->bshop_model=D("BShop");
        $this->bbankgold_model=D("BBankGold");
        $this->bbankgoldtype_model=D("BBankGoldType");
        $this->boptions_model=D("BOptions");
        $this->bclient_model=D("BClient");
       $this->b_show_status('b_recovery');
    }
    //获取操作记录和流程
    private function get_operate_info($id){
        /*表单的操作记录 add by lzy 2018.5.26 start*/
        $operate_record=$this->brecovery_model->getOperateRecord($id);
        $this->assign('operate_record', $operate_record);
        //表单的操作流程
        $operate_process=$this->brecovery_model->getProcess($id);
        $this->assign('process_list', $operate_process);//var_dump($operate_process);
        $process_tpl=$this->fetch('BBillOpRecord/process');
        $operate_tpl=$this->fetch('BBillOpRecord/operate');
        $this->assign('process_tpl', $process_tpl);
        $this->assign('operate_tpl', $operate_tpl);
        /*表单的操作记录 add by lzy 2018.5.26 end*/
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        if($getdata["search_name"]){
            $condition["brecovery.batch|brecovery.name|recovery_musers.client_name|recovery_musers.client_moblie"]=array("like","%".$getdata["search_name"]."%");
        }
        $status=$getdata['status'];
        if(isset($status)&&$status>-100){
            $condition["brecovery.status"]=$status;
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
        if(get_shop_id()>0){$condition['brecovery.shop_id']=get_shop_id();}
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }
    //获取回购列表数据
    public function _getlist($where=array()){
        $getdata=I("");
        $condition=array("brecovery.company_id"=>$this->MUser['company_id'],"brecovery.shop_id"=>$this->MUser['shop_id'],"brecovery.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $this->handleSearch($condition);
        $join=" left join ".DB_PRE."b_employee create_musers on create_musers.user_id=brecovery.creator_id  and create_musers.deleted=0 and create_musers.company_id=brecovery.company_id";
        $join.=" left join ".DB_PRE."b_employee check_musers on check_musers.user_id=brecovery.check_id  and create_musers.deleted=0  and check_musers.company_id=brecovery.company_id";
        $join.=" left join ".DB_PRE."b_client recovery_musers on recovery_musers.id=brecovery.client_id";
        $join.=" left join ".DB_PRE."b_shop b_shop on b_shop.id=brecovery.shop_id";
        $field="brecovery.*,b_shop.shop_name";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,recovery_musers.client_name recovery_name";
        $count=D("BRecovery")->alias("brecovery")->countList($condition,$field,$join,$order='brecovery.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $field=$field.',sum(brproduct.gold_weight) as gold_weight,brproduct.recovery_price';
        $join.=' left join '.DB_PRE.'b_recovery_product brproduct on brproduct.order_id=brecovery.id';
        $data=$this->brecovery_model->alias("brecovery")->getList($condition,$field,$limit,$join,$order='brecovery.id desc','brecovery.id');
//         $status_model = D ( 'b_status' );
//         $condition=array();
//         $condition ["table"] = DB_PRE.'b_recovery';
//         $condition ["field"] = 'status';
//         $status_list = $status_model->getStatusInfo ($condition );
//         $this->assign("status_list",$status_list);
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$data);
    }
/*************************************回购******************************************************/
    // 添加回购单
    public function add() {
        $postdata=I("");
        if(empty($postdata)){
            $info=$this->boptions_model->get_recover_setting();
            if($info){
                $price=json_decode($info["option_value"],true);
                $price["recovery_price"]=D("BBankGold")->get_price_by_bg_id($price['recovery_bg_id']);
                $price["gold_price"]=D("BBankGold")->get_price_by_bg_id($price['bg_id']);
                $this->assign("price", $price);
            }
            $join=" left join ".DB_PRE."b_currency bcurrency on bshop.currency_id=bcurrency.id";
            $condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"],"bshop.enable"=>1);
            if(get_shop_id()>0){
                $condition["bshop.id"]=get_shop_id();
                $this->assign("shop_id", get_shop_id());
            }
            $shop=$this->bshop_model->alias("bshop")->getList($condition,$field='bshop.*,bcurrency.exchange_rate,bcurrency.unit',$limit=null,$join);
            $this->assign("shop", $shop);
          /*  $payment=$this->bpayment_model->getList($condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]));
            $this->assign("payment", $payment);
            $currency=$this->bcurrency_model->getList($condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]),$field='*',$limit=null,$join='',$order='is_main desc');
            $this->assign("currency", $currency);*/
            $this->assign("user_nicename", $this->MUser["employee_name"]);
            $this->assign("today", date('Y-m-d', time()));
            $this->display();
        }else{
            if (IS_POST) {
                // 判断是否内部员工，是则添加为客户
                $order = I('post.order');
                if (empty($order['buyer_id']) && !empty($order['employee_id'])) {
                    A('BClient')->_addClientByEmployee($order['employee_id'], $order['buyer_id']);
                    $_POST['order'] = $order;
                }
                //order所有选中货品id//store收货仓id//mystore发货仓id//comment备注//trance_time开单时间//$orderid单号
                $info=$this->brecovery_model->add_post();
                $this->ajaxReturn($info);
            }
        }
    }
    // 回购货品列表
    public function product_list() {
        $wh_id=I("ck",0,'intval');
        $shop_id=I("get.shop_id",0,'intval');
        if($wh_id){
            $where=array("bproduct.warehouse_id"=>$wh_id,"bproduct.status"=>2);
        }else{
            $where=array("bproduct.status"=>2);
        }
        if(get_shop_id()>0){
            $shop_id=get_shop_id();
        }
        $where["bwarehouse.shop_id"]=$shop_id;
        A("BProduct")->product_list($where);
        $wh_data=$this->bwarehouse_model->getList_detail(array("shop_id"=>$shop_id));
        $this->assign("wh_data", $wh_data);
        $this->assign("today", date('Y-m-d', time()));
        $this->display();
    }
    //回购客户列表
    public function client_list() {
        // A("BClient")->_getList();
        A('BClient')->_getClient();
        $this->display();
    }
    //添加客户
    public function add_client(){
        if(IS_POST){
            $status=$this->bclient_model->add_post();
            if ($status['status'] == 1) {
                $this->success($status['msg'], U("BRecovery/client_list"));
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
    // 回购列表
    public function index(){
        $this->_getlist();
        $this->display();
    }
    // 回购记录详情
    public function index_detail() {
        $data=$this->brecovery_model->getInfo_detail();
        $detail_data=$this->brecoverydetail_model->getList_detail(array("brecoverydetail.order_id"=>$data["id"],'brecoverydetail.type'=>0));
        $this->get_operate_info($data["id"]);
        $this->assign("list",$detail_data);
        $this->assign("data",$data);
        $this->display();
    }
    // 回购撤销
    public function revoke() {
        $getdata = I("");
        $recovery_id= I("post.id",0,'intval');
        $data["id"] = $recovery_id;
        $data["status"] =3;
        $condition=array("id"=>$recovery_id,"status"=>0,"company_id"=>$this->MUser["company_id"],'creator_id'=>get_user_id());
        $check_info=$this->brecovery_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $brecovery_save=$this->brecovery_model->update($condition,$data);
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RECOVERY,$recovery_id,BRecoveryModel::REVOKE);//撤销
        if ($record_result&&$brecovery_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BRecovery/index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }
    // 回购编辑
    public function edit() {
        if(IS_POST){
            // 判断是否内部员工，是则添加为客户
            $order = I('post.order');
            if (empty($order['buyer_id']) && !empty($order['employee_id'])) {
                A('BClient')->_addClientByEmployee($order['employee_id'], $order['buyer_id']);
                $_POST['order'] = $order;
            }
            $info=$this->brecovery_model->edit_post();
            $this->ajaxReturn($info);
        }else{
            $data['brecovery_data']=$this->brecovery_model->getInfo_detail();
            if($data['brecovery_data']['creator_id']==get_user_id()){
                $data['detail_data']=$this->brecoverydetail_model->getList_detail(array("brecoverydetail.order_id"=>$data['brecovery_data']["id"],'brecoverydetail.type'=>0));
                $condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]);
                if(get_shop_id()){
                    $condition["id"]=get_shop_id();
                    $this->assign('shop_id', get_shop_id());
                }
                $shopdata = D("BShop")->getList($condition,$field='*',$limit=null,$join='',$order='',$group='');
                $this->assign('shop', $shopdata);
                $this->assign("list",$data['detail_data']);
                $this->assign("recovery",$data['brecovery_data']);
                $this->display();
            }else{
                $this->redirect(U("BRecovery/index_detail",array('id'=>I("get.id",0,"intval"))));
            }
        }
    }
    // 回购审核列表
    public function check() {
        $getdata=I("");
        $condition=array("brecovery.status"=>0);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 回购审核明细
    public function check_detail() {
        $data=$this->brecovery_model->getInfo_detail();
        $detail_data=$this->brecoverydetail_model->getList_detail(array("brecoverydetail.order_id"=>$data["id"],'brecoverydetail.type'=>0));
        $this->get_operate_info($data["id"]);
        $this->assign("list",$detail_data);
        $this->assign("data",$data);
        $this->display();
    }
    // 回购审核
    public function check_post() {
        $getdata = I("");
        $recovery_id= I("post.id",0,'intval');
        $data["id"] = $recovery_id;
        $data["status"] = $getdata["type"];//-1 驳回为-2状态，1审核通过，2审核不通过
        $data["check_time"] = time();
        $data["check_id"] = $this->MUser['id'];
        $data["check_memo"] = I('post.check_memo');
        $condition=array("id"=>$recovery_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        $check_info=$this->brecovery_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $brecovery_save=$this->brecovery_model->update($condition,$data);
        if($brecovery_save !== false){
            // 审核通过
            if($getdata["type"] == 1){
                //回购审核成功更新归属，存在则不更新
                D("MUserSource")->update_belong($check_info["f_uid"],$check_info["shop_id"]);
            }
            $brecoverydetail=true;
            //审核才更改金料状态，驳回不改变
            if($getdata["type"]>0) {
                $status = $getdata["type"] == 1 ? 2 :0;//审核通过则为2，审核不通过则为0
                $brecoverydetail = $this->brecoverydetail_model->update_status($recovery_id,0, $status);
            }
        }
        //回购审核的操作记录
        $record_status=$getdata["type"]==1?BRecoveryModel::CHECK_PASS:($getdata["type"]==2?BRecoveryModel::CHECK_DENY:BRecoveryModel::CHECK_REJECT);
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RECOVERY,$recovery_id,$record_status);//审核通过或不通过或驳回
        if ($record_result&&$brecovery_save !== false && $brecoverydetail !== false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BRecovery/check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $result["test"] = $brecovery_save;
            $this->ajaxReturn($result);
        }
    }
    //导出列表数据
    function export_excel($page=1){
        $condition=array("brecovery.company_id"=>$this->MUser['company_id'],"brecovery.deleted"=>0);
        $this->handleSearch($condition);
        $this->brecovery_model->excel_out($condition);
    }
    //设置
    function setting(){
        if (IS_POST) {
            $postdata=I("post.");
            $data=array();
            $option_name="recovery_gold_price_".get_shop_id();
            $option_value['recovery_bg_id']=$postdata["recovery_bg_id"];
            $option_value['bg_id']=$postdata["bg_id"];
            $option_value['cut_bg_id']=$postdata["cut_bg_id"];
            $option_value=json_encode($option_value);//'{"recovery_bgt_id":'.$postdata["recovery_bgt_id"].',"bgt_id":'.$postdata["bgt_id"].'}';
            $data["option_value"]=$option_value;
            $data["update_time"]=time();
            $where['option_name']=$option_name;
            $where["company_id"]=$this->MUser["company_id"];
            $where["deleted"]=0;
            $info=$this->boptions_model->getInfo($where);
            if($info){
                $BSectors=$this->boptions_model->update($where,$data);
                //$this->error("添加失败，该键名下的键值存在！");
            }else{
                $data["option_name"]=$option_name;
                $data["company_id"]=$this->MUser["company_id"];
                $data["memo"]="回购金价与当前金价设置";
                $data["status"]=1;
                $data["user_id"]=$this->MUser["id"];
                $data["deleted"]=0;
                $BSectors=$this->boptions_model->insert($data);
            }
            if ($BSectors!==false) {
                $this->success("添加成功！", U("BRecovery/setting"));
            } else {
                $this->error("添加失败！");
            }
        }else{
            $option_name="recovery_gold_price_".get_shop_id();
            $where['option_name']=$option_name;
            $where["company_id"]=$this->MUser["company_id"];
            $where["deleted"]=0;
            $info=$this->boptions_model->getInfo($where);
            $condition=array("bbankgoldtype.company_id"=>$this->MUser['company_id'],"bbankgoldtype.deleted"=>0,"bbankgoldtype.status"=>"1","shop_id"=>get_shop_id());
            $join="left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgold.bgt_id";
            $join.="  left join ".DB_PRE."b_metal_type metaltype on metaltype.id=bbankgoldtype.b_metal_type_id";
            $field='bbankgoldtype.name,bbankgoldtype.bgt_id,metaltype.id bmt_id,bbankgold.*';
            $data=$this->bbankgold_model->alias("bbankgold")->getList($condition,$field,$limit="",$join,$order='bg_id desc');
            $this->assign("gold_type", $data);
            $this->assign("data", json_decode($info["option_value"],true));
            $this->assign("gold_type", $data);
            $this->display();
        }
    }
    // 删除
    public function deleted() {
        $getdata = I("");
        $id = I("id/d", 0);

        $data["deleted"] = 1;
        $condition = array(
            "id"=> $id,
            "creator_id"=> get_user_id(),
            'company_id'=> get_company_id(),
            "status"=> array('in', '-1,2,3')
        );
        M()->startTrans();
        $rs = $this->brecovery_model->update($condition, $data);
        if ($rs !== false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["info"] = "成功";
            $result["url"] = U("BRecovery/index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["info"] = "失败";
            $this->ajaxReturn($result);
        }
    }
    //获取回购金价设置
    function get_setting(){
        $option_name="recovery_gold_price";
        $where['option_name']=$option_name;
        $where["company_id"]=$this->MUser["company_id"];
        $where["deleted"]=0;
        $info=$this->boptions_model->getInfo($where);
        return $info;
    }
}