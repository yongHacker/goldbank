<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BAllotRproductDetailModel extends BCommonModel{
    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->bproduct_model=D("BRecoveryProduct");
    }
    //通过调拨单id获取调拨明细与其详细信息
    public function getList_detail($where){
        $condition=array("ballotdetail.deleted"=>0);
        if($where){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."b_recovery_product rproduct on rproduct.id=ballotdetail.p_id";
        $field="ballotdetail.*,rproduct.rproduct_code,rproduct.recovery_name,rproduct.total_weight,rproduct.purity
        ,rproduct.gold_weight,rproduct.recovery_price,rproduct.cost_price,rproduct.sub_rproduct_code";
        $detail_data=$this->alias("ballotdetail")->getList($condition,$field,$limit="",$join,$order='ballotdetail.id desc');

        return $detail_data;
    }
    // 删除调拨明细的单条调拨信息与货品，货品状态还原为在库
    public function detail_delete()
    {
        // 删除调拨明细的一条数据
        $orderid = I("post.allocationid",0);
        $id = I("post.id",0);
        $p_id = I("post.p_id",0);
        $map["id"] = $id;
        $map["allot_id"] = $orderid;
        $condition["id"] = $orderid;
        $condition["deleted"] = 0;
        $condition["creator_id"] = get_user_id();
        $condition['status']=array("in",array(-1,2,3,4,6));
        $allocations = $this->getInfo($map);
        if (empty($allocations)) {
            $error["status"] = 0;
            $error["msg"] = "无法删除";
            return $error;
        }
        $allocations_detail["deleted"] = 2;
        M()->startTrans();
        $detail =$this->update($map,$allocations_detail);
        // 货品还原为在库
      /*  $map2["id"] = $p_id;
        $product["status"] = 2;
        $productdata = D("BProduct")->update($map2,$product);
        if ($detail && $productdata) {*/
        if ($detail) {
            M()->commit();
            $error["status"] = 1;
            $error["msg"] = "成功";
            $error["id"] = $id;
        } else {
            M()->rollback();
            $error["status"] = 0;
            $error["msg"] = "订单无法修改";
            $error["id"] = $id."//".$orderid."//".$detail;
        }
        return $error;
    }
    function insert($insert) {
        $insert["company_id"]=get_company_id();
        return parent::insert($insert); // TODO: Change the autogenerated stub
    }
    //暂时用于生命周期
    public function new_getList_detail($where){
        $condition=array("ballotdetail.deleted"=>0);
        if($where){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."b_product bproduct on bproduct.id=ballotdetail.p_id";
        $join.=" left join ".DB_PRE."b_allot ballot on ballot.id=ballotdetail.allot_id";
        $join.=" left join ".DB_PRE."b_warehouse from_bwarehouse on ballot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on ballot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=ballot.creator_id and create_musers.deleted=0 and create_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=ballot.check_id  and check_musers.deleted=0 and check_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  outbound_musers on outbound_musers.user_id=ballot.outbound_id  and outbound_musers.deleted=0 and outbound_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  receipt_musers on receipt_musers.user_id=ballot.receipt_id  and receipt_musers.deleted=0 and receipt_musers.company_id=ballot.company_id";
        $field="ballot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,outbound_musers.employee_name outbound_name,receipt_musers.employee_name receipt_name";
       // $field.=$this->bproduct_model->get_product_field_str(2);
        $detail_data=$this->alias("ballotdetail")->getList($condition,$field,$limit="",$join,$order='ballotdetail.id desc');
        return $detail_data;
    }
}
