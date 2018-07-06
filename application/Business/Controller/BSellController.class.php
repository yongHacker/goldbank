<?php
// author：czy--仓库管理
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Business\Model\BBillOpRecordModel;
use Business\Model\BSellModel;

class BSellController extends BusinessbaseController {

    public function __construct() {
        parent::__construct();
        $this->bwarehouse_model=D("BWarehouse");
        $this->bsaccountrecord_model=D("BSaccountRecord");
        $this->bcurrency_model=D("BCurrency");
        $this->bpayment_model=D("BPayment");
        $this->bselldetail_model=D("BSellDetail");
        $this->bsell_model=D("BSell");
        $this->bproduct_model = D('BProduct');
        $this->bshop_model=D("BShop");
        $this->bclient_model=D("BClient");
        $this->bexpence_model=D("BExpence");
        $this->bexpencesub_model=D("BExpenceSub");
        $this->b_show_status('b_sell');
    }
    //获取操作记录和流程
    private function get_operate_info($id){
        /*表单的操作记录 add by lzy 2018.5.26 start*/
        $operate_record=$this->bsell_model->getOperateRecord($id);
        $this->assign('operate_record', $operate_record);
        //表单的操作流程
        $operate_process=$this->bsell_model->getProcess($id);
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
            $condition["bsell.order_id|bsell.sn_id|b_client.client_name|b_client.client_moblie|create_musers.employee_name"]=array("like","%".$getdata["search_name"]."%");
        }
        $status=$getdata['status'];
        if(is_numeric($status)&&$status!=='all'){
            $condition['bsell.status']=$status;
        }
        $sell_type=$getdata['sell_type'];
        if(!empty($sell_type)){
            $condition['bsell.sell_type']=$sell_type;
        }
        $begin_time=strtotime(I('begin_time'));
        $end_time=strtotime(I('end_time'));
        if(!empty($begin_time)&&!empty($end_time)){
            $condition['bsell.sell_time']=array('between',$begin_time.",".$end_time);
        }else if(!empty($begin_time)){
            $condition['bsell.sell_time']=array('gt',$begin_time);
        }else if(!empty($end_time)){
            $condition['bsell.sell_time']=array('lt',$end_time);
        }
        if($getdata['shop_id']>0){$condition['bsell.shop_id']=$getdata['shop_id'];}
        if($getdata['shop_id']==-1){$condition['bsell.shop_id']=0;}
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }
    //获取销售列表数据
    public function _getlist($where=array()){
        $getdata=I("");
        $condition=array("bsell.company_id"=>$this->MUser['company_id'],"bsell.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $this->handleSearch($condition);
        $shop_list=D("BShop")->getShopList();
        $this->assign("shop_list", $shop_list);
        /*$join=" left join ".DB_PRE."m_users create_musers on create_musers.id=bsell.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bsell.check_id";
        $join.=" left join ".DB_PRE."m_users buy_musers on buy_musers.id=bsell.buyer_id";
        $join.=" left join ".DB_PRE."b_shop b_shop on b_shop.id=bsell.shop_id";*/
        $join=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=bsell.creator_id and create_musers.deleted=0 and create_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=bsell.check_id  and check_musers.deleted=0 and check_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_employee  buy_musers on buy_musers.user_id=bsell.buyer_id  and buy_musers.deleted=0 and buy_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_shop b_shop on b_shop.id=bsell.shop_id";
        $join.=" left join ".DB_PRE."b_client b_client on b_client.id=bsell.client_id";
        $field="bsell.*";
       // $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,b_client.client_name buy_name,b_shop.shop_name";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,b_client.client_name buy_name,b_shop.shop_name";
        $count=D("BSell")->alias("bsell")->countList($condition,$field,$join,$order='bsell.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $data=$this->bsell_model->alias("bsell")->getList($condition,$field,$limit,$join,$order='bsell.id desc');

        $status_model = D ( 'b_status' );
        $condition=array();
        $condition ["table"] = DB_PRE.'b_sell';
        $condition ["field"] = 'status';
        $status_list = $status_model->getStatusInfo ($condition );
        $this->assign("status_list",$status_list);
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$data);
    }
/*************************************销售******************************************************/
    // 添加销售单
    public function add() {
        $postdata=I("");
        if(empty($postdata)){
            $info=D("BOptions")->get_recover_setting();
            if($info){
                $price=json_decode($info["option_value"],true);
                $price["cut_gold_price"]=D("BBankGold")->get_price_by_bgt_id($price['cut_bgt_id']);
                $price["recovery_price"]=D("BBankGold")->get_price_by_bgt_id($price['recovery_bgt_id']);
                $price["gold_price"]=D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);
                $this->assign("price", $price);
            }
            $data=$this->bwarehouse_model->getList_detail();
            $join=" left join ".DB_PRE."b_currency bcurrency on bshop.currency_id=bcurrency.id";
            $condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"],"bshop.enable"=>1);
            if(get_shop_id()>0){
                $condition["bshop.id"]=get_shop_id();
                $this->assign("shop_id", get_shop_id());
            }
            //仓库数据
            $wh_data=D("BWarehouse")->get_wh_list();
            $this->assign ( "warehouse", json_encode($wh_data,true));
            //门店数据
            $shop=$this->bshop_model->alias("bshop")->getList($condition,$field='bshop.*,bcurrency.id currency_id,bcurrency.exchange_rate,bcurrency.unit',$limit=null,$join);
            $this->assign("shop", $shop);

            // 输出其它费用类目
            A('BExpence')->_expenceList(1);

            $where = array(
                'company_id'=> get_company_id(),
                'deleted'=> 0,
                'status'=> 1
            );
            $payment = $this->bpayment_model->getList($where);
            $currency = $this->bcurrency_model->getList($where, $field='*', $limit=null, $join='', $order='is_main desc');

            $payment=json_encode($payment);
            $this->assign("payment", $payment);
            $this->assign("currency", $currency);

            $this->assign("user_nicename", $this->MUser["employee_name"]);
            $this->assign("data", $data);
            $today = date('Y-m-d', time());
            $this->assign("today", $today);
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
                $info=$this->bsell_model->add_post();
                $this->ajaxReturn($info);
            }
        }
    }
    // 销售货品列表
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

        $goods_class_id = I('search_goods_class/d', 0);
        if($goods_class_id){
            $where['g_common.class_id'] = $goods_class_id;
        }
        if(!empty(I('goods_weight'))){
            $where['gold.weight'] = I('goods_weight');
        }
        
        A("Business/BProduct")->product_list($where,3);
        $wh_data=$this->bwarehouse_model->getList_detail(array("shop_id"=>$shop_id));

        $tree = new \Tree();
        $tree->icon = array('│ ', '├─ ', '└─ ');
        $tree->nbsp = '&nbsp;';

        $condition = array(
            "company_id"=> get_company_id(),
            "deleted"=> 0
        );
        $categories = D('Business/BGoodsClass')->getList($condition, $field='*,class_name as name');

        $new_categories = array();
        foreach ($categories as $r) {
            $r['id']=$r['id'];
            $r['parentid']=$r['pid'];
            $r['selected']= (!empty($parentid) && $r['id']==$parentid)? "selected":"";
            $new_categories[] = $r;
        }
        $tree->init($new_categories);
        $tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
        $tree = $tree->get_tree(0,$tree_tpl,$goods_class_id);
        $this->assign('goods_class_tree', $tree);

        $this->assign("wh_data", $wh_data);
        $this->assign("today", date('Y-m-d', time()));
        $this->display();
    }
    // 销售客户列表
    public function client_list() {
        if(empty($_POST['shop_id'])){
            $_POST['shop_id']=0;
        }
        // A("BClient")->_getList();
        A('BClient')->_getClient();
        $this->display();
    }
    // 添加客户
    public function add_client(){
        if(IS_POST){
            $shop_id=$_POST['shop_id'];
            $status=$this->bclient_model->add_post();
            if ($status['status'] == 1) {
                $this->success($status['msg'], U("BSell/client_list",array('shop_id'=>$shop_id)));
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
    // 销售列表
    public function index(){
        $this->_getlist();
        $this->display();
    }
    // 销售记录详情
    public function index_detail() {
        // 销售单信息
        $data = $this->bsell_model->getInfo_detail();
        if (!empty($data)) {
            // 销售货品明细
            $detail_data = $this->bselldetail_model->getList_detail($data['id']);
            // 销售截金明细
            $sell_cut_product_list = D('BRecoveryProduct')->getList_detail(array('brecoverydetail.order_id' => $data['id'], 'brecoverydetail.type' => 1));
            // 销售收款明细
            $saccount_list = $this->bsell_model->getsaccount_list($data['id']); 
            // 销售其他费用明细
            $sell_sub = $this->bexpencesub_model->getSublist(array('ticket_id' => $data['id'], 'ticket_type' => 1));
            // 操作流程和日志
            $this->get_operate_info($data['id']);
            // 以旧换新信息
            $sell_recovery_product_list = D('BRecoveryProduct')->getList_detail(array('brecoverydetail.order_id' => $data['id'],'brecoverydetail.type' => 4));
            
            $this->b_show_status('b_sell_detail');
            $this->assign('sell_recovery_product_list', $sell_recovery_product_list);
            $this->assign('sell_sub', $sell_sub);
            $this->assign('saccount_list', $saccount_list);
            $this->assign('sell_cut_product_list', $sell_cut_product_list);
            $this->assign('sell_recovery_product_list', $sell_recovery_product_list);
            $this->assign('list', $detail_data);
            $this->assign('data', $data);
        }

        $this->display();
    }
    // 销售撤销
    public function revoke() {
        $getdata = I("");
        $sell_id = I("post.id",0,'intval');
        $data["id"] = $sell_id;
        $data["status"] = 2;
        $condition = array("id"=>$sell_id,"status"=>0, "company_id"=>$this->MUser["company_id"],'creator_id'=>get_user_id());
        $check_info=$this->bsell_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $bsell_save = $this->bsell_model->update($condition, $data);

        if($bsell_save != false){
            $where = array(
                'company_id'=> get_company_id(),
                'sn_id'=> $sell_id,
                'status'=> '-1'
            );
            $update_data = array('status'=> 3);
            $saccount_unpass = $this->bsaccountrecord_model->update($where, $update_data);
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL,$sell_id,BSellModel::REVOKE);//撤销
            if($record_result===false||$saccount_unpass === false){
                M()->rollback();
                $result["status"] = 0;
                $result["msg"] = "失败";
                $result["test"] = 'saccount_revoke';
                $this->ajaxReturn($result);
            }
        }

        //回滚货品状态为在库
        $pruductids = $this->bsell_model->get_pruductids($sell_id, 'product_id');
        $product_save = true;
        if (!empty($pruductids)) {
            $condition = array('id' => array('in', $pruductids));
            $data = array('status' => 2, 'sell_time' => 0);
            $product_save = $this->bproduct_model->update($condition, $data);
        }

        if ($bsell_save!==false && $product_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BSell/index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }
    // 销售编辑
    public function edit() {
        if(IS_POST){
            // 判断是否内部员工，是则添加为客户
            $order = I('post.order');
            if (empty($order['buyer_id']) && !empty($order['employee_id'])) {
                A('BClient')->_addClientByEmployee($order['employee_id'], $order['buyer_id']);
                $_POST['order'] = $order;
            }
            $info=$this->bsell_model->edit_post();
            $this->ajaxReturn($info);
        }else{
            //截金金价
            $info=D("BOptions")->get_recover_setting();
            if($info){
                $price=json_decode($info["option_value"],true);
                $price["cut_gold_price"]=D("BBankGold")->get_price_by_bgt_id($price['cut_bgt_id']);
                $price["recovery_price"]=D("BBankGold")->get_price_by_bgt_id($price['recovery_bgt_id']);
                $price["gold_price"]=D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);
                $this->assign("price", $price);
            }
            //门店与门店币种
            $join=" left join ".DB_PRE."b_currency bcurrency on bshop.currency_id=bcurrency.id";
            $condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"],"bshop.enable"=>1);
            if(get_shop_id()>0){
                $condition["bshop.id"]=get_shop_id();
            }
            //仓库数据
            $wh_data=D("BWarehouse")->get_wh_list();
            $this->assign ( "warehouse", json_encode($wh_data,true));
            //门店数据
            $shop=$this->bshop_model->alias("bshop")->getList($condition,$field='bshop.*,bcurrency.id currency_id,bcurrency.exchange_rate,bcurrency.unit',$limit=null,$join);
            $this->assign("shop", $shop);
            //币种
            $where = array(
                'company_id'=> get_company_id(),
                'deleted'=> 0,
                'status'=> 1
            );
            $currency = $this->bcurrency_model->getList($where, $field='*', $limit=null, $join='', $order='is_main desc');
            //其他费用类型
            $condition = array(
                'deleted' => 0,
                'company_id' => get_company_id(),
                'type' => 1
            );
            $expence_list = $this->bexpence_model->getList($condition);
            //销售单信息
            $data=$this->bsell_model->getInfo_detail();
            //销售单货品信息
            $detail_data=$this->bselldetail_model->getList_detail($data["id"]);
            //销售单截金信息
            $sell_cut_product_list = D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$data["id"],'brecoverydetail.type'=>1));
            //D('BSellCutProduct')->getSellProductCutList($data["id"]);
            //销售单收款信息
            $saccount_list = $this->bsell_model->getsaccount_list($data["id"]);
            //销售其他费用信息
            $sell_sub = $this->bexpencesub_model->getSublist(array('ticket_id' => $data["id"], 'ticket_type' => 1));
            //以旧换新信息
            $sell_recovery_product_list = D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$data["id"],'brecoverydetail.type'=>4));
            //D('BSellRecoveryProduct')->getProductList($data["id"]);
            //收款方式
            if($data['show_common_payment']==0||$data['shop_id']==0){
                $shopids=$data['shop_id'];
            }else{
                $shopids=array('in','0,'.$data['shop_id']);
            }
            $payment = $this->bpayment_model->getList(array('company_id'=> get_company_id(), 'deleted'=> 0, 'status'=> 1,'shop_id'=>$shopids));
            $this->assign('expence_list', $expence_list);
            $this->assign("payment", $payment);
            $this->assign("currency", $currency);
            $this->assign('sell_sub', $sell_sub);
            $this->assign('saccount_list', $saccount_list);
            $this->assign('sell_cut_product_list', $sell_cut_product_list);
            $this->assign('sell_recovery_product_list', $sell_recovery_product_list);
            $this->assign("list",$detail_data);
            $this->assign("data",$data);
            $this->display();
        }
    }
    // 销售审核列表
    public function check() {
        $getdata=I("");
        $condition=array("bsell.status"=>0);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 销售审核列表
    public function check_record() {
        $getdata=I("");
        $condition=array("bsell.status"=>array("in","1,3"));
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 销售审核明细
    public function check_record_detail() {
        $data=$this->bsell_model->getInfo_detail();
        $detail_data=$this->bselldetail_model->getList_detail($data["id"]);
        $sell_cut_product_list = D('BSellCutProduct')->getSellProductCutList($data["id"]);
        $saccount_list = $this->bsell_model->getsaccount_list($data["id"]);
        $this->get_operate_info($data["id"]);
        $this->assign('saccount_list', $saccount_list);
        $this->assign('sell_cut_product_list', $sell_cut_product_list);
        $this->assign("list",$detail_data);
        $this->assign("data",$data);

        $this->display();
    }
    
    // 销售审核明细
    public function check_detail() {
        $data=$this->bsell_model->getInfo_detail();
        $detail_data=$this->bselldetail_model->getList_detail($data["id"]);
        $sell_cut_product_list =D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$data["id"],'brecoverydetail.type'=>1));
        $saccount_list = $this->bsell_model->getsaccount_list($data["id"]);
        $sell_sub = $this->bexpencesub_model->getSublist(array('ticket_id' => $data["id"], 'ticket_type' => 1));
        $this->get_operate_info($data["id"]);
        $sell_recovery_product_list =D('BRecoveryProduct')->getList_detail(array("brecoverydetail.order_id"=>$data["id"],'brecoverydetail.type'=>4));
        $this->assign('sell_recovery_product_list', $sell_recovery_product_list);
        $this->assign('sell_sub', $sell_sub);
        $this->assign('saccount_list', $saccount_list);
        $this->assign('sell_cut_product_list', $sell_cut_product_list);
        $this->assign("list", $detail_data);
        $this->assign("data", $data);

        $this->display();
    }

    // 销售审核
    public function check_post() {
        $getdata = I("");
        $sell_id = I("post.id",0,'intval');

        $data["id"] = $sell_id;
        $data["status"] = $getdata["type"]==1 ? 1 : 3;
        $data["check_time"] = time();
        $data["check_id"] = $this->MUser['id'];
        $data["check_memo"] = I('post.check_memo');

        $condition = array("id"=>$sell_id, "status"=>0, "company_id"=>$this->MUser["company_id"]);
        $check_info=$this->bsell_model->getInfo($condition);
        if(empty($check_info)){
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
        M()->startTrans();
        $bsell_save = $this->bsell_model->update($condition, $data);
        $outbound_add = true;
        $bsaccountrecord = true;
        $detail_update = true;

        // 审核不通过
        if($getdata["type"] == 2){
            $pruductids = $this->bsell_model->get_pruductids($sell_id, 'product_id');
            // product 恢复正常在库状态
            $product_save = true;
            if (!empty($pruductids)) {
                $data = array('status' => 2, 'sell_time'=> 0);
                $condition = array('id' => array('in', $pruductids));
                $product_save = $this->bproduct_model->update($condition, $data);
            }

            // 付款明细记录
            $where = array(
                'company_id'=> get_company_id(),
                'sn_id'=> $sell_id,
                'status'=> '-1',
                'deleted'=> '0'
            );
            $update_data = array('status'=> 2);
            $bsaccountrecord = $this->bsaccountrecord_model->update($where, $update_data);
            $BRecovery['status'] = 1;
        }else{
            // 审核通过
            $pruductids = $this->bsell_model->get_pruductids($sell_id, 'product_id');
            $product_save = true;
            if (!empty($pruductids)) {
                $data = array('status' => 6,'order_id'=>$sell_id);
                $condition = array('id' => array('in', $pruductids));
                $product_save = $this->bproduct_model->update($condition, $data);
            }
            // 付款明细记录
            $where = array(
                'company_id'=> get_company_id(),
                'sn_id'=> $sell_id,
                'deleted'=>0,
            );
            $update_data = array('status'=> 0);
            $bsaccountrecord = D('BSaccountRecord')->update($where, $update_data);
            //销售审核成功更新归属，存在则不更新
            D("MUserSource")->update_belong($check_info["buyer_id"],$check_info["shop_id"]);
            // 销售审核成功更新销售明细状态为1
            $condition = array(
                'deleted' => 0,
                'sell_id' => $sell_id
            );
            $update = array('status' => 1);
            $detail_update = $this->bselldetail_model->update($condition, $update);
        }
        $recovery_detail=true;
        //审核并且类型为截金或以旧换新才更改金料状态，驳回不改变
        if($getdata["type"]>0) {
            $status = $getdata["type"] == 1 ? 2 :0;//审核通过则为2，审核不通过则为0
            $recovery_detail = D('BRecoveryProduct')->update_status($sell_id,array('in','1,4'),$status);
        }
        //销售审核的操作记录
        $record_status=$getdata["type"]==1?BSellModel::CHECK_PASS:BSellModel::CHECK_DENY;
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL,$sell_id,$record_status);//审核通过或不通过
        if ($recovery_detail!==false&&$record_result&&$bsell_save!==false && $product_save!==false && $outbound_add!==false && $bsaccountrecord!==false && $detail_update!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BSell/check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $result["test"] = $recovery_detail.'//'.$record_result.'//'.$bsell_save."//".$product_save."//".$outbound_add."//".$bsaccountrecord."//".$BRecovery['status'];
            $this->ajaxReturn($result);
        }
    }
    //删除销售单
    function deleted(){
        $getdata = I("");
        $id = I("id/d", 0);

        $data["deleted"] = 1;
        $condition = array(
            "id"=> $id,
            "creator_id"=> get_user_id(),
            'company_id'=> get_company_id(),
            "status"=> array('in', '-1')
        );
        M()->startTrans();
        $rs = $this->bsell_model->update($condition, $data);
        if ($rs !== false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BSell/index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }
    //导出列表数据
    function export_excel($page=1){
        $condition=array("bsell.company_id"=>$this->MUser['company_id'],"bsell.deleted"=>0);
        $this->handleSearch($condition);
        $this->bsell_model->excel_out($condition);
    }
    //销售日结报表
    function sell_day_report(){
        $shop_list=D("BShop")->getShopList();
        $this->assign("shop_list", $shop_list);
        $condition=array("bsell.company_id"=>$this->MUser['company_id'],"bsell.deleted"=>0,"bsell.status"=>1);
        $getdata=I("");
        if($getdata['shop_id']>0){$condition['bsell.shop_id']=$getdata['shop_id'];}
        if($getdata['shop_id']==-1){$condition['bsell.shop_id']=0;}
        $sell_type=$getdata['sell_type'];
        if(!empty($sell_type)){
            $condition['bsell.sell_type']=$sell_type;
        }
        $sell_info=M('BSell')->alias('bsell')->where($condition)->field('sell_time')->order('sell_time asc')->find();
        $now_day=strtotime(date('Y-m-d'));
        $last_day=strtotime(date('Y-m-d',$sell_info['sell_time']));
        if(I('begin_time')){
            $last_day=strtotime(I('begin_time'))>$last_day?strtotime(I('begin_time')):$last_day;
        }
        $last_day=$last_day-86400;
        if(I('end_time')){
            $now_day=strtotime(I('end_time'))>=$now_day?$now_day:strtotime(I('end_time'));
        }
        $count_day=$now_day<$last_day?0:($now_day-$last_day)/86400;
        $first=I('request.p')<2?0:(I('request.p')-1)*$this->pagenum;
        $last=I('request.p')<2?$this->pagenum:I('request.p')*$this->pagenum;
        $last=$count_day<$last?$count_day:$last;
        $day_report=array();
        $field="sum(c.weight) weight";
        $join="left join ".DB_PRE."b_sell_detail b ON bsell.id = b.sell_id";
        $join.=" left join ".DB_PRE."b_product p ON b.product_id = p.id";
        $join.=" left join ".DB_PRE."b_product_gold c on p.id=c.product_id";
        $field2="sum(real_sell_price) price,sum(count) count";
        for ($i=$first; $i<$last;$i++) {
            $day_report[$i]['days']=date('Y-m-d', strtotime('-'.$i.' days',$now_day));
            $condition['bsell.sell_time']=strtotime($day_report[$i]['days']);
            $sell_weight=M('BSell')->alias('bsell')->join($join)->where($condition)->field($field)->find();
            $sell_price=M('BSell')->alias('bsell')->where($condition)->field($field2)->find();
            $day_report[$i]['weight']=$sell_weight['weight'];
            $day_report[$i]['price']=$sell_price['price'];
            $day_report[$i]['count']=$sell_price['count'];
        }
        $condition['bsell.sell_time']=array('between',$last_day.",".$now_day);
        $sell_weight=M('BSell')->alias('bsell')->join($join)->where($condition)->field($field)->find();
        $sell_price=M('BSell')->alias('bsell')->where($condition)->field($field2)->find();
        $count_info['weight']=$sell_weight['weight'];
        $count_info['price']=$sell_price['price'];
        $count_info['count']=$sell_price['count'];
        $this->assign("count_info", $count_info);
        $page = $this->page($count_day, $this->pagenum);
        $this->assign("list", $day_report);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
}