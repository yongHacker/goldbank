<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BWsellModel extends BCommonModel{

    //order.sell_price手填销售价 order.count 计算应总售价
    public function add_post(){
        $order_id =b_order_number('BWsell','order_id');
        $postArray =I("order_detail");
        $order=I('post.order');
        $shop_id=I('post.shop_id');
        $count=I('post.count');
        $saccout_record=I('post.saccout_record');
        $total_price=0;
        $total_onsale_price=0;
        M()->startTrans();
        foreach($postArray as $k =>$val){
            $product_ids[]=$val['id'];
            $total_price+=(float)$val['sell_price'];
            $total_onsale_price+=(float)$val['be_onsale_price'];
            $condition=array("goods_id"=>$val["id"],"warehouse_id"=>$val["warehouse_id"]);
            if(empty($val['num_stock'])){
                M()->rollback();
                $arr['status'] =0;
                $arr['msg']='第' . ++ $k . '行商品，销售量不能为空！';
                return $arr;
            }
            $stock_status=D("BWgoodsStock")->is_enough_stock($condition,$val['num_stock'],$val['sell_pricemode']);
            if(!$stock_status){
                M()->rollback();
                $arr['status'] =0;
                $arr['msg']='第' . ++ $k . '行商品，库存不足！';
                $arr['test_msg']=$stock_status;
                return $arr;
            }else{
                $price=(float)$val['sell_price'];
                $condition=array("goods_id"=>$val["id"],"warehouse_id"=>get_current_warehouse_id());
                $old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
                $new_stock=bcsub($old_stock['goods_stock'],$val['num_stock'],4);
                $new_outcome_stock=bcadd($old_stock['outcome_stock'],$val['num_stock'],4);
                $new_outcome_price=bcadd($old_stock['outcome_price'],$price,2);
                $stock_price=bcsub($old_stock['stock_price'],$price,2);
                $data=array('goods_stock'=>$new_stock,'outcome_stock'=>$new_outcome_stock,'outcome_price'=>$new_outcome_price,'stock_price'=>$stock_price);
                $stock=D("BWgoodsStock")->update($condition,$data);
                if($stock===false){
                    M()->rollback();
                    $arr['status'] =0;
                    $arr['msg']='更新库存失败！';
                    return $arr;
                }
            }
        }
       /* $condition=array("id"=>$order["buyer_id"]);
        $client=M("BClient")->where($condition)->find();*/
        $rate=(float)$order["count"]/$total_price;
        $param=array();
        $param["company_id"]=get_company_id();
        $param["shop_id"]=$shop_id;
        $param["creator_id"]=get_user_id();
        $param['status'] =0;
        $param['order_id'] =$order_id;
        $param['shop_id']=0;
        $param['count']=$count;
        $param["create_time"]= time();
        $param["sell_time"]= time();
        //$param["dealer_id"]=$client["user_id"];
        $param["dealer_id"]=$order["buyer_id"];
        $param["memo"]=$order["remark"];
        $param["price"] =$total_onsale_price;
        $param["real_sell_price"] =$order["count"];
        $sell_id =$this->insert($param);
        $li2=$this->add_sell_detail($postArray,$sell_id,$rate);
        //$saccout_detail=$this->add_saccount_record($saccout_record,$sell_id,$shop_id);
        //$stock=$this->update_stock($postArray);
        if($sell_id!==false&&$li2!==false){
            M()->commit();
            S('session_menu' . get_current_admin_id(), null);
            $arr['status'] =1;
            $arr['url'] =U("BWsell/index");
        }else{
            M()->rollback();
            $arr['status'] =0;
            $arr['msg']='添加失败！';
            $arr['test']=$sell_id."//".$li2."//";
        }
        return $arr;
    }
    //更新库存
    public function update_stock($postArray){
        $flag=true;
        foreach($postArray as $k=>$val){
            $condition=array("goods_id"=>$val["id"],"warehouse_id"=>$val["warehouse_id"]);
            $stock=D("BWgoodsStock")->lock(true)->where($condition)->setDec("goods_stock",$val['num_stock']);
            if($stock===false){
                $flag=false;
                return $flag;
            }
        }
        return $flag;
    }
    //批量添加收款明细
    public function add_saccount_record($saccout_record,$sell_id,$shop_id,$rate){
        $saccount_details=array();
        foreach($saccout_record as $k=>$v){
            $saccount_detail=array();
            $saccount_detail["company_id"]=get_company_id();
            $saccount_detail["shop_id"]=$shop_id;
            $saccount_detail["sn_id"]=$sell_id;
            $saccount_detail["flow_id"]=$v["flow_id"];
            $saccount_detail["pay_id"]=$v["pay_id"];
            $saccount_detail["currency_id"]=$v["currency"];
            $saccount_detail["pay_price"]=$v["pay_price"];
            $saccount_detail["receipt_price"]=$v["actual_price"];
            $saccount_detail["creator_id"]=get_user_id();
            $saccount_detail["create_time"]=time();
            $saccount_detail["type"]=2;
            $saccount_detail["status"]=-1;
            $saccount_detail["deleted"]=0;
            $saccount_details[]=$saccount_detail;
        }
        $b_saccout_record = D("b_saccount_record")->addAll($saccount_details);
        return $b_saccout_record;
    }
    //添加销售明细
    public function add_sell_detail($postArray,$sell_id,$rate){
        $sell_details=array();
        foreach($postArray as $k2=>$v){
            //销售明细写入
            $sell_detail["wsell_id"]=$sell_id;//订单id
            $sell_detail["cost_price"]=$v["sell_price"];//原本销售总价
            //$sell_detail["sell_fee"]=$v["sell_m_fee"];//工费
            $sell_detail["wgoods_id"]=$v['id'];//货品id
            $sell_detail["wgoods_stock_id"]=$v['wgoods_stock_id'];//库存id
            $sell_detail['goods_stock']=$v['num_stock'];
            $sell_detail["discount_price"]=$v["discount_price"];//单品优惠
            $sell_detail['price']=(float)$v['sell_price']*$rate;//实质销售总价
            $sell_detail["procure_price"]=$sell_detail['price']/$v['num_stock'];//销售单价
            $sell_details[]=$sell_detail;
        }
        $li2 = D("b_wsell_detail")->addAll($sell_details);
        return $li2;
    }
    //获取一条销售的所有信息
    public function getInfo_detail(){
        $condition=array("bwsell.company_id"=>get_company_id(),"bwsell.deleted"=>0,"bwsell.id"=>I("get.id",0,"intval"));
        $join=" left join ".DB_PRE."m_users create_musers on create_musers.id=bwsell.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bwsell.check_id";
        $join.=" left join ".DB_PRE."b_supplier b_supplier on b_supplier.id=bwsell.dealer_id";
        $field="bwsell.*";
        $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,b_supplier.company_name buy_name";
        $bwsell_data=$this->alias("bwsell")->getInfo($condition,$field,$join,$order='bwsell.id desc');
        return $bwsell_data;
    }

    // 获取对某供应商的批发单列表 - 结算
    public function get_list_by_supplier(){
        $main_tbl = DB_PRE.'b_wsell';

        $supplier_id = I('post.supplier_id/d', 0);

        $where = array (
            $main_tbl.'.dealer_id'=> $supplier_id,
            $main_tbl.'.status'=> 1,
            $main_tbl.'.procure_settle_id'=> 0,
            $main_tbl.'.company_id'=> get_company_id(),
            $main_tbl.'.deleted'=> 0
        );

        $field = $main_tbl.'.*';
       // $field .= ', IF('.$main_tbl.'.pricemode, "计重", "计件")as show_pricemode';
        $field .= ', ifnull(cu.user_nicename,cu.mobile)as creator_name';
        $field .= ', (CASE '.$main_tbl.'.status
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		)as show_status';

        $join = ' LEFT JOIN '.C('DB_PREFIX').'m_users as cu ON (cu.id = '.$main_tbl.'.creator_id)';
        /*
                $sub = D('BProcureStorage')->field('procurement_id, SUM(price) as total_fee_price')->group('procurement_id')->select(false);
                $join .= ' LEFT JOIN ('.$sub.')as s1 ON (s1.procurement_id = '.$main_tbl.'.id)';*/
        $datalist = $this->getList($where, $field, null, $join);

        foreach($datalist as $k=>$v){
            if($v['pricemode']==1){
                $datalist[$k]["weight"]=D('BWsellDetail')->where(array('wsell_id'=>$v['id'],'deleted'=>0))->sum("goods_stock");
                $datalist[$k]["total_fee_price"]=0;
            }else{
                $datalist[$k]["weight"]=0;
                $datalist[$k]["total_fee_price"]=$v['real_sell_price'];
            }
        }
        return $datalist;
    }
    //获取销售明细的货品id
    public function get_pruductids($sell_id,$field="product_id"){
        $condition=array("bwsell.id"=>$sell_id,"bwsell.company_id"=>get_company_id(),"bwsell.deleted"=>0,"bwselldetail.deleted"=>0);
        $join="left join ".DB_PRE."b_wsell_detail bwselldetail on bwsell.id=bwselldetail.wsell_id";
        $productid =$this->alias("bwsell")->where($condition)->join($join)->getField($field, true);
        return $productid;
    }
    // 导出 excel
    /**
     * @param $excel_where
     * @param string $excel_field
     * @param string $excel_join
     * @param int $page
     * @param string $action
     */
    public function excel_out($excel_where, $excel_field='', $excel_join='', $page = 1,$action='excel_out'){
        set_time_limit(0);
        $limit = (($page - 1) * intval(500)) . "," . (intval(500));
        $condition=array();
        $condition ["table"] = DB_PRE.'b_wsell';
        $condition ["field"] = 'status';
        $status_list = D ( 'b_status' )->getFieldValue($condition);
        $join=" left join ".DB_PRE."m_users create_musers on create_musers.id=bwsell.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bwsell.check_id";
        $join.=" left join ".DB_PRE."b_supplier b_supplier on b_supplier.id=bwsell.dealer_id";
        $field="bwsell.*";
        $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,b_supplier.company_name buy_name";
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $data=$this->alias("bwsell")->getList($excel_where,$field,$limit,$join,$order='bwsell.id desc');
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['batch'] = $v['order_id'];
                $expotdata[$k]['user_nicename'] = $v['user_nicename'];
                $expotdata[$k]['buy_name'] = $v['buy_name'];
                $expotdata[$k]['price'] = $v['price'];
                $expotdata[$k]['count'] =D('BWsellDetail')->countList($condition=array("deleted"=>0,"wsell_id"=>$v["id"]));
                $expotdata[$k]['sell_time'] = empty($v['sell_time'])?'':date('Y-m-d H:i:s',$v['sell_time']);
                $expotdata[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $expotdata[$k]['status'] = $status_list[$v['status']];
            }
            register_shutdown_function(array(&$this, $action),$excel_where,$excel_field, $excel_join, $page + 1);
            $title=array('序','单号','销售员','批发商','销售总价','数量','销售时间','制单时间','状态');
            exportexcel($expotdata,$title,"批发销售记录列表");
        }
    }
}
