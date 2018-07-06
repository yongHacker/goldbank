<?php
// author：czy--仓库管理
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BWallotController extends BusinessbaseController {

    public function __construct() {
        parent::__construct();
        $this->bwarehouse_model=D("BWarehouse");
        $this->bwallotdetail_model=D("BWallotDetail");
        $this->bwallot_model=D("BWallot");
        $this->bproduct_model = D('BProduct');
        $this->bwgoods_model = D('BWgoods');
        $this->b_show_status('b_allot');
    }
    // 处理列表表单提交的搜索关键词
    private function handleSearch(&$ex_where = NULL){
        $getdata=I("");
        $condition=array();
        if($getdata["search_name"]){
            $condition["bwallot.batch"]=array("like","%".$getdata["search_name"]."%");
        }
        $status=$getdata['status'];
        if(isset($status)&&$status>-2){
            $condition=array("bwallot.status"=>$status);
        }
        $ex_where = array_merge($condition, $ex_where);
        $request_data = $_REQUEST;
        $this->assign('request_data', $request_data);
    }
    //获取调拨列表数据
    public function _getlist($where){
        $getdata=I("");
        $condition=array("bwallot.company_id"=>$this->MUser['company_id'],"bwallot.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
       $this->handleSearch($condition);
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on bwallot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on bwallot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."m_users create_musers on create_musers.id=bwallot.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bwallot.check_id";
        $join.=" left join ".DB_PRE."m_users outbound_musers on outbound_musers.id=bwallot.outbound_id";
        $join.=" left join ".DB_PRE."m_users receipt_musers on receipt_musers.id=bwallot.receipt_id";
        $field="bwallot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname";
        $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,outbound_musers.user_nicename outbound_name,receipt_musers.user_nicename receipt_name";
        $count=$this->bwallot_model->alias("bwallot")->countList($condition,$field,$join,$order='bwallot.id desc');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $data=$this->bwallot_model->alias("bwallot")->getList($condition,$field,$limit,$join,$order='bwallot.id desc');
        foreach($data as $k=>$v){
            $data[$k]["count"]=$this->bwallotdetail_model->countList($condition=array("deleted"=>0,"allot_id"=>$v["id"]));
        }
        $status_model = D ( 'b_status' );
        $condition=array();
        $condition ["table"] = DB_PRE.'b_wallot';
        $condition ["field"] = 'status';
        $status_list = $status_model->getStatusInfo ($condition );
        $this->assign ( "status_list", $status_list );
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$data);
    }
    //通过调拨单id获取调拨明细与其详细信息
    public function getList_detail($allot_id){
        if($allot_id){
            $condition=array("wallot_id"=>$allot_id,"bwallotdetail.deleted"=>0);
        }else{
            $condition=array("bwallotdetail.deleted"=>0);
        }
        $join="left join ".DB_PRE."b_wgoods bwgoods on bwgoods.id=bwallotdetail.goods_id";
        $field="bwallotdetail.*,bwgoods.goods_code,bwgoods.goods_name,bwgoods.sell_pricemode";
        $count=$this->bwallotdetail_model->alias("bwallotdetail")->countList($condition,$field,$join,$order='bwallotdetail.id desc');
        $page = $this->page($count, $this->pagenum); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $page->show("Admin"); // 分页显示输出
        $limit = $page->firstRow . ',' . $page->listRows;
        $detail_data=$this->bwallotdetail_model->alias("bwallotdetail")->getList($condition,$field,$limit,$join,$order='bwallotdetail.id desc');
        foreach($detail_data as $k=>$v){
            $detail_data[$k]["product_pic"]=$this->bwgoods_model->get_goods_pic($v['goods_id']);
        }
        //if($count>$this->pagenum)$detail_data['show']=$show;
        $this->assign("page", $show);
        $this->assign("list",$detail_data);
    }
/*************************************调拨******************************************************/
    // 添加调拨单
    public function add() {
        $postdata=I("");
        if(empty($postdata)){
            $data=$this->bwarehouse_model->getList_detail();
            $this->assign("data", $data);
            $today = date('Y-m-d', time());
            $this->assign("today", $today);
            $this->display();
        }else{
            if (IS_POST) {
                //order所有选中货品id//store收货仓id//mystore发货仓id//comment备注//trance_time开单时间//$orderid单号
                $info=$this->bwallot_model->add_post();
                $this->ajaxReturn($info);
            }
        }
    }
    // 调拨查询获取货品
    public function product_list() {
        $wh_id=I("mystore",0,'intval');
        if($wh_id){
            $where=array("bwgoodsstock.warehouse_id"=>$wh_id,"bwgoods.status"=>1,"bwgoodsstock.goods_stock"=>array("gt",0));
        }else{
            $where=array("bwgoods.status"=>1,"bwgoodsstock.goods_stock"=>array("gt",0));
        }
        if(empty(I("mystore"))){
            $this->assign("empty_info", "请先选择发货仓库");
        }else{
            A("Business/BWgoodsStock")->goods_list($where);
        }
        $this->display();
    }
    //采购清单
    public function procure_list() {
        $batch=trim(I("batch"));
        $warehouse_id=I("get.mystore",0,'intval');
        if($batch){
            $where['batch']=array("like","%$batch%");
        }
        $join = " join gb_b_procure_storage as ps on ps.procurement_id = gb_b_procurement.id";
        $join .=" join gb_b_product as p on p.storage_id= ps.id and p.status=2 and p.deleted = 0 and p.warehouse_id =".$warehouse_id." and p.company_id = ".$this->MUser['company_id'];
        $join .=" join gb_m_users as u on u.id = gb_b_procurement.creator_id";
        $join .=" join gb_b_supplier as s on s.id= gb_b_procurement.supplier_id";
        $field="gb_b_procurement.*,u.user_nicename,s.company_name";
        $group="gb_b_procurement.id";
        $order="gb_b_procurement.id desc";
        $list=D("BProcurement")->getList($where,$field,"",$join,$order,$group);
        $count=$list?count($list):0;
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $procure_list=D("BProcurement")->getList($where,$field,$limit,$join,$order,$group);
        $this->assign("procure_list",$procure_list);
        $this->display();
    }

    public function get_pro_product()
    {
        if ($_REQUEST["mystore"]) {
            $wh_id = $_REQUEST["mystore"];
        }
        $ids = I('post.ids');
        $model_wh = D('Business/BWarehouse');
        $product = $model_wh->get_procure_product($ids, $wh_id);
        $text = "";
        $product_ids = "";
        $i = 0;
        foreach ($product as $key => $val) {
            $text .= "<tr>";
            $text .= '<td class="text-center"></td>';
            $text .= ' <td style="padding-left:10px;">' . $val['product_code'] . '</td>';
            $text .= ' <td style="padding-left:10px;">' . $val['product_name'] . '</td>';
            $text .= ' <td class="text-right"></td>';
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
        $data=$this->bwallot_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        //$this->assign("list",$detail_data);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 调拨编辑
    public function edit() {
        if(IS_POST){
          //  if($this->bwallot_model->autoCheckToken($_POST)){
                $info=$this->bwallot_model->edit_post();
                $this->ajaxReturn($info);
           /* }else{
                $info['status']=0;
                $info['msg']=$this->bwallot_model->getError();
                $this->ajaxReturn($info);
            }*/
        }else{
            $data['bwallot_data']=$this->bwallot_model->getInfo_detail();
            $data['detail_data']=$this->bwallotdetail_model->getList_detail($data['bwallot_data']["id"]);
            $data['wh_data']=$this->bwarehouse_model->getList_detail();
            $this->assign("wh_data",$data['wh_data']);
            $this->assign("list",$data['detail_data']);
            $this->assign("allocation",$data['bwallot_data']);
            $this->display();
        }
    }
    // 调拨编辑,删除单条调拨货品
    public function detail_delete() {
        $data=$this->bwallotdetail_model->detail_delete();
        $this->ajaxReturn($data);
    }
    // 调拨审核列表
    public function allot_check() {
        $condition=array("bwallot.status"=>0);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 调拨审核明细
    public function allot_check_detail() {
        $data=$this->bwallot_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 调拨审核
    public function allot_check_post() {
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $data["id"] = $allot_id;
        $data["status"] =$getdata["type"]==1?1:2;
        $data["check_time"] = time();
        $data["check_id"] = $this->MUser['id'];
        $data["check_memo"] = I('post.check_memo');
        $condition=array("id"=>$allot_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        M()->startTrans();
        $bwallot_save=$this->bwallot_model->update($condition,$data);
        $product_save=true;
        if ($bwallot_save!==false&&$product_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BWallot/allot_check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $result["test"] = $bwallot_save."//".$product_save;
            $this->ajaxReturn($result);
        }
    }
    // 调拨撤销
    public function allot_delete() {
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $data["id"] = $allot_id;
        $data["status"] =3;
        $condition=array("id"=>$allot_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
        M()->startTrans();
        $bwallot_save=$this->bwallot_model->update($condition,$data);
        if ($bwallot_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BWallot/allot_index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }
    // 调拨删除
    public function deleted() {
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $data["id"] = $allot_id;
        $data["deleted"] =0;
        $condition=array("id"=>$allot_id,"status"=>array('in','-1,2,3,4,6'),"company_id"=>$this->MUser["company_id"]);
        M()->startTrans();
        $bwallot_save=$this->bwallot_model->update($condition,$data);
        if ($bwallot_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BWallot/allot_index");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }
 /*************************************出库******************************************************/
    // 出库记录
    public function outbound_index() {
        $condition=array("bwallot.status"=>array("gt","3"));
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 出库记录明细
    public function outbound_index_detail() {
        $data=$this->bwallot_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 出库审核列表
    public function outbound_check() {
        $condition=array("bwallot.status"=>1);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 出库审核明细
    public function outbound_check_detail() {
        $data=$this->bwallot_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 出库审核
    public function outbound_check_post() {
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $data["id"] = $allot_id;
        $data["status"] =$getdata["type"]==1?5:4;
        $data["outbound_time"] = time();
        $data["outbound_id"] = $this->MUser['id'];
        $data["outbound_memo"] = I('post.check_memo');
        $condition=array("id"=>$allot_id,"status"=>1,"company_id"=>$this->MUser["company_id"]);
        M()->startTrans();
        $bwallot_save=$this->bwallot_model->update($condition,$data);
        $product_save=true;
        if ($bwallot_save!==false&&$product_save!==false) {
            M()->commit();
            S('session_menu' . get_user_id(), null);
            $result["status"] = 1;
            $result["msg"] = "成功";
            $result["url"] = U("BWallot/outbound_check");
            $this->ajaxReturn($result);
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "失败";
            $result["test"] = $bwallot_save."//".$product_save;
            $this->ajaxReturn($result);
        }
    }
/*************************************入库******************************************************/
    // 入库记录
    public function receipt_index() {
        $getdata=I("");
        $condition=array("bwallot.status"=>array("gt",5));
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 入库记录明细
    public function receipt_index_detail() {
        $data=$this->bwallot_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 入库审核列表
    public function receipt_check() {
        $getdata=I("");
        $condition=array("bwallot.status"=>5);
        $this->_getlist($condition);
        $this->assign("empty_info","没有找到信息");
        $this->display();
    }
    // 入库审核明细
    public function receipt_check_detail() {
        $data=$this->bwallot_model->getInfo_detail();
        $this->getList_detail($data["id"]);
        $this->assign("allocation",$data);
        $this->display();
    }
    // 入库审核
    public function receipt_check_post() {
        // 货品入库审核
        $getdata = I("");
        $allot_id= I("post.id",0,'intval');
        $productid=$this->bwallot_model->get_wgoods($allot_id);
        $condition=array("id"=>$allot_id,"company_id"=>$this->MUser['company_id'],"deleted"=>0);
        $bwallot =$this->bwallot_model->getInfo($condition,$field="*",$join="",$order='id desc');
        if ($productid[0]) {
            M()->startTrans();
            $data["id"] = $getdata;
            $data["status"] = $getdata["type"]==1?7:6;
            $data["receipt_time"] = time();
            $data["receipt_id"] = $this->MUser['id'];
            $data["receipt_memo"] = I('post.check_memo');
            $condition=array("id"=>$allot_id,"status"=>5,"company_id"=>$this->MUser["company_id"]);
            $bwallot_save=$this->bwallot_model->update($condition,$data);
            if($getdata["type"]==2){
                $product_save=true;
            }else{
                //入库更新库存
                $condition=array("wallot_id"=>$allot_id);
                $bwallotdetail=$this->bwallotdetail_model->getList($condition);
                foreach($bwallotdetail as $k=>$val){
                    $condition=array("goods_id"=>$val["goods_id"],"warehouse_id"=>$bwallot["to_id"]);
                    $old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
                    //不存在库存信息增加默认库存
                    if(empty($old_stock)){
                        $comdition["company_id"]=get_company_id();
                        D("BWgoodsStock")->insert($condition);
                    }
                    $new_stock=bcadd($old_stock['goods_stock'],$val['goods_stock'],4);
                    $new_income_stock=bcadd($old_stock['income_stock'],$val['goods_stock'],4);
                    //$new_income_price=bcadd($old_stock['income_price'],$val['price'],2);
                    //$stock_price=bcadd($old_stock['stock_price'],$val['price'],2);
                    $data=array('goods_stock'=>$new_stock,'income_stock'=>$new_income_stock);
                    $stock=D("BWgoodsStock")->update($condition,$data);
                    if($stock===false){
                        M()->rollback();
                        $result["status"] =0;
                        $result["msg"] = "库存更新失败";
                        $this->ajaxReturn($result);
                    }
                }
            }
            if ($bwallot_save!==false&&$product_save!==false) {
                M()->commit();
                S('session_menu' . get_user_id(), null);
                $result["status"] = 1;
                $result["msg"] = "成功";
                $result["url"] = U("BWallot/receipt_check");
                $this->ajaxReturn($result);
            } else {
                M()->rollback();
                $result["status"] = 0;
                $result["msg"] = "失败";
                $result["test"] = $bwallot_save."//".$product_save;
                $this->ajaxReturn($result);
            }
        } else {
            $result["status"] = 0;
            $result["msg"] = "失败";
            $this->ajaxReturn($result);
        }
    }
    //调拨完成更新出库库存
    public function update_income_stock($allot_id,$warehouse_id){
        //恢复库存
        $condition=array("wallot_id"=>$allot_id);
        $bwallotdetail=$this->bwallotdetail_model->getList($condition);
        foreach($bwallotdetail as $k=>$val){
            $condition=array("goods_id"=>$val["wgoods_id"],"warehouse_id"=>$warehouse_id);
            $old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
            $new_stock=bcadd($old_stock['goods_stock'],$val['goods_stock'],4);
            $new_income_stock=bcadd($old_stock['income_stock'],$val['goods_stock'],4);
            $new_income_price=bcadd($old_stock['income_price'],$val['price'],2);
            $stock_price=bcadd($old_stock['stock_price'],$val['price'],2);
            $data=array('goods_stock'=>$new_stock,'income_stock'=>$new_income_stock,'income_price'=>$new_income_price,'stock_price'=>$stock_price);
            $stock=D("BWgoodsStock")->update($condition,$data);
            if($stock===false){
                $arr['status'] =0;
                $arr['msg']='更新库存失败！';
                return $arr;
            }
        }
        $arr['status'] =1;
        $arr['msg']='更新库存成功！';
        return $arr;

    }
    //调拨完成更新入库库存
    public function update_outcome_stock($allot_id,$warehouse_id){
        //恢复库存
        $condition=array("wallot_id"=>$allot_id);
        $bwallotdetail=$this->bwallotdetail_model->getList($condition);
        foreach($bwallotdetail as $k=>$val){
            $condition=array("goods_id"=>$val["id"],"warehouse_id"=>$warehouse_id);
            $old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
            $new_stock=bcsub($old_stock['goods_stock'],$val['num_stock'],4);
            $new_outcome_stock=bcadd($old_stock['outcome_stock'],$val['num_stock'],4);
            $data=array('goods_stock'=>$new_stock,'outcome_stock'=>$new_outcome_stock);
            $stock=D("BWgoodsStock")->update($condition,$data);
            if($stock===false){
                $arr['status'] =0;
                $arr['msg']='更新库存失败！';
                return $arr;
            }
        }
        $arr['status'] =1;
        $arr['msg']='更新库存成功！';
        return $arr;
    }
    //导出列表数据
    function export_excel($page=1){
        $condition=array("bwallot.company_id"=>$this->MUser['company_id'],"bwallot.deleted"=>0);
        $this->handleSearch($condition);
        $this->bwallot_model->excel_out($condition);
    }
}