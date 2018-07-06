<?php
// author：czy--仓库管理
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BWsellController extends BusinessbaseController {

    public function __construct() {
        parent::__construct();
        $this->bwarehouse_model=D("BWarehouse");
        $this->bsaccountrecord_model=D("BSaccountRecord");
        $this->bcurrency_model=D("BCurrency");
        $this->bpayment_model=D("BPayment");
        $this->bwselldetail_model=D("BWsellDetail");
        $this->bwsell_model=D("BWsell");
        $this->bproduct_model = D('BProduct');
        $this->bwgoods_model = D('BWgoods');
        $this->bshop_model=D("BShop");
       $this->b_show_status('b_wsell');
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        if($getdata["search_name"]){
            $condition["bwsell.order_id|buy_musers.user_nicename"]=array("like","%".trim($getdata["search_name"])."%");
        }
        if(I('begin_time')){
            $begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
            $begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
            $condition['bwsell.create_time'] = array('gt', $begin_time);
        }

        if(I('end_time')){
            $end_time = I('end_time') ? strtotime(I('end_time')) : time();
            $end_time = strtotime(date('Y-m-d 23:59:59', $end_time));
            if(isset($begin_time)){
                $p1 = $condition['bwsell.create_time'];
                unset($condition['bwsell.create_time']);
                $condition['bwsell.create_time'] = array($p1, array('lt', $end_time));
            }else{
                $condition['bwsell.create_time'] = array('lt', $end_time);
            }
        }
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }
    //获取销售列表数据
    public function _getlist($where=array()){
        $getdata=I("");
        $condition=array("bwsell.company_id"=>$this->MUser['company_id'],"bwsell.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
       $this->handleSearch($condition);
        /*$join="left join ".DB_PRE."b_warehouse from_bwarehouse on bwsell.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on bwsell.to_id=to_bwarehouse.id";*/
        $join=" left join ".DB_PRE."m_users create_musers on create_musers.id=bwsell.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bwsell.check_id";
        $join.=" left join ".DB_PRE."m_users buy_musers on buy_musers.id=bwsell.dealer_id";
        $field="bwsell.*";
        $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,buy_musers.user_nicename buy_name";
        $count=D("BWsell")->alias("bwsell")->countList($condition,$field,$join,$order='bwsell.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $data=$this->bwsell_model->alias("bwsell")->getList($condition,$field,$limit,$join,$order='bwsell.id desc');
        foreach($data as $k=>$v){
            $data[$k]["count"]=$this->bwselldetail_model->countList($condition=array("deleted"=>0,"wsell_id"=>$v["id"]));
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$data);
    }
    //更加批发销售单id获取批发销售明细并分页
    public function getList_detail($sell_id){
        if($sell_id){
            $condition=array("wsell_id"=>$sell_id);
        }else{
            $condition="";
        }
        $join="left join ".DB_PRE."b_wgoods bwgoods on bwgoods.id=bwselldetail.wgoods_id";
        $field="bwselldetail.*,bwgoods.goods_code,bwgoods.goods_name,bwgoods.sell_pricemode";
        $count=$this->bwselldetail_model->alias("bwselldetail")->countList($condition,$field,$join,$order='bwselldetail.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $detail_data=$this->bwselldetail_model->alias("bwselldetail")->getList($condition,$field,$limit,$join,$order='bwselldetail.id desc');
        foreach($detail_data as $k=>$v){
            $detail_data[$k]["product_pic"]=$this->bwgoods_model->get_goods_pic($v['wgoods_id']);
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$detail_data);
    }
/*************************************批发销售******************************************************/
    // 添加销售单
    public function add() {
        $postdata=I("");
        if(empty($postdata)){
            $data=$this->bwarehouse_model->getList_detail();
            $this->assign("user_nicename", $this->MUser["user_nicename"]);
            $join=" left join ".DB_PRE."b_currency bcurrency on bshop.currency_id=bcurrency.id";
            $condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"]);
            $shop=$this->bshop_model->alias("bshop")->getList($condition,$field='bshop.*,bcurrency.exchange_rate,bcurrency.unit',$limit=null,$join);
            $this->assign("shop", $shop);
            $payment=$this->bpayment_model->getList($condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]));
            $this->assign("payment", $payment);
            $currency=$this->bcurrency_model->getList($condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]),$field='*',$limit=null,$join='',$order='is_main desc');
            $this->assign("currency", $currency);
            $this->assign("user_nicename", $this->MUser["user_nicename"]);
            $this->assign("data", $data);
            $today = date('Y-m-d', time());
            $this->assign("today", $today);
            $this->display();
        }else{
            if (IS_POST) {
                //order所有选中货品id//store收货仓id//mystore发货仓id//comment备注//trance_time开单时间//$orderid单号
                $info=$this->bwsell_model->add_post();
                $this->ajaxReturn($info);
            }
        }
    }
    // 销售货品列表
    public function product_list() {
        $wh_id=I("wh_id",0,'intval');
        $shop_id=I("get.shop_id",0,'intval');
        if($wh_id){
            $where=array("bwgoodsstock.warehouse_id"=>$wh_id,"bwgoods.status"=>1,"bwgoodsstock.goods_stock"=>array("gt",0));
        }else{
            $where=array("bwgoods.status"=>1,"bwgoodsstock.goods_stock"=>array("gt",0));
        }
        $where["bwarehouse.shop_id"]=$shop_id;
        A("Business/BWgoodsStock")->goods_list($where);
        $wh_data=$this->bwarehouse_model->getList_detail();
        $this->assign("wh_data", $wh_data);
        $this->assign("today", date('Y-m-d', time()));
        $this->display();
    }
    //销售客户列表
    public function client_list() {
        A("Business/BClient")->_getList();
        $this->display();
    }
    // 销售列表
    public function index(){
        $this->_getlist();
        $this->display();
    }
    // 销售记录详情
    public function index_detail() {
        $data=$this->bwsell_model->getInfo_detail();
        $detail_data=$this->bwselldetail_model->getList_detail($data["id"]);
        $this->assign("list",$detail_data);
        $this->assign("data",$data);
        $this->display();
    }
    // 销售撤销
    public function revoke() {
        $getdata = I("");
        $sell_id= I("post.id",0,'intval');
        $data["id"] = $sell_id;
        $data["status"] =2;
        $condition=array("id"=>$sell_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        M()->startTrans();
        $bwsell_save=$this->bwsell_model->update($condition,$data);
        //回滚货品状态为在库
        $pruductids=$this->bwsell_model->get_pruductids($sell_id);
        $condition=array("id"=>array("in",$pruductids));
        $data=array("status"=>2);
        $product_save=$this->bproduct_model->update($condition,$data);
        if ($bwsell_save!==false&&$product_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BWsell/index");
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

        }else{
            $data['bwsell_data']=$this->bwsell_model->getInfo_detail();
            $data['detail_data']=$this->bwselldetail_model->getList_detail($data['bwsell_data']["id"]);
            $data['wh_data']=$this->bwarehouse_model->getList_detail();
            $this->assign("wh_data",$data['wh_data']);
            $this->assign("list",$data['detail_data']);
            $this->assign("allocation",$data['bwsell_data']);
            $this->display();
        }
    }
    // 销售审核列表
    public function check() {
        $getdata=I("");
        $condition=array("bwsell.status"=>0);
        if($getdata["search_name"]){
            $condition["shop_name|code|mobile"]=array("like","%".$getdata["search_name"]."%");
        }
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 销售审核列表
    public function check_record() {
        $getdata=I("");
        $condition=array("bwsell.status"=>array("in","1,3"));
        if($getdata["search_name"]){
            $condition["shop_name|code|mobile"]=array("like","%".$getdata["search_name"]."%");
        }
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 销售审核明细
    public function check_record_detail() {
        $data=$this->bwsell_model->getInfo_detail();
        $detail_data=$this->bwselldetail_model->getList_detail($data["id"]);
        $this->assign("list",$detail_data);
        $this->assign("data",$data);
        $this->display();
    }
    // 销售审核明细
    public function check_detail() {
        $data=$this->bwsell_model->getInfo_detail();
        $detail_data=$this->bwselldetail_model->getList_detail($data["id"]);
        $this->assign("list",$detail_data);
        $this->assign("data",$data);
        $this->display();
    }
    // 销售审核
    public function check_post() {
        $getdata = I("");
        $sell_id= I("post.id",0,'intval');
        $data["id"] = $sell_id;
        $data["status"] =$getdata["type"]==1?1:3;
        $data["check_time"] = time();
        $data["check_id"] = $this->MUser['id'];
        $data["check_memo"] = I('post.check_memo');
        $condition=array("id"=>$sell_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        M()->startTrans();
        $bwsell_save=$this->bwsell_model->update($condition,$data);
        $bsaccountrecord=true;
        if($getdata["type"]==2){
            //恢复库存
            $condition=array("wsell_id"=>$sell_id);
            $bwselldetail=$this->bwselldetail_model->getList($condition);
            foreach($bwselldetail as $k=>$val){
                $condition=array("id"=>$val["wgoods_stock_id"]);
                $old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
                $new_stock=bcadd($old_stock['goods_stock'],$val['goods_stock'],4);
                $new_outcome_stock=bcsub($old_stock['outcome_stock'],$val['goods_stock'],4);
                $new_outcome_price=bcsub($old_stock['outcome_price'],$val['price'],2);
                $stock_price=bcadd($old_stock['stock_price'],$val['price'],2);
                $data=array('goods_stock'=>$new_stock,'outcome_stock'=>$new_outcome_stock,'outcome_price'=>$new_outcome_price,'stock_price'=>$stock_price);
                $stock=D("BWgoodsStock")->update($condition,$data);
                if($stock===false){
                    M()->rollback();
                    $result["status"] =0;
                    $result["msg"] = "库存更新失败";
                    $this->ajaxReturn($result);
                }
            }

        }else{
            $bsaccountrecord=$this->bsaccountrecord_model->update(array("sn_id"=>$sell_id,'status'=>'-1'),array("status"=>0));
        }
        if ($bwsell_save!==false&&$bsaccountrecord!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BWsell/check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $result["test"] = $bwsell_save."//".$bsaccountrecord;
            $this->ajaxReturn($result);
        }
    }
    //导出列表数据
    function export_excel($page=1){
        $condition=array("bwsell.company_id"=>$this->MUser['company_id'],"bwsell.deleted"=>0);
        $this->handleSearch($condition);
        $this->bwsell_model->excel_out($condition);
    }
}