<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BWallotModel extends BCommonModel{
    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
       // array('sector_id', 'require', '部门不能为空！', 1, 'regex', BCommonModel:: MODEL_BOTH ),
    );
    //自动完成
    protected $_auto = array(
        //array(填充字段,填充内容,填充条件,附加规则)
    );
    //调拨开单
    public function add_post(){
        $postArray =I("order_detail");
        $order = I("post.order");
        $mystore=$order['mystore'];
        M()->startTrans();
        foreach($postArray as $k =>$val){
            $condition=array("goods_id"=>$val["id"],"warehouse_id"=>$val["warehouse_id"]);
            if(empty($val['num_stock'])){
                M()->rollback();
                $arr['status'] =0;
                $arr['msg']='第' . ++ $k . '行商品，调拨量不能为空！';
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
                //$price=(float)$val['sell_price'];
                $condition=array("goods_id"=>$val["id"],"warehouse_id"=>$mystore);
                $old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
                $new_stock=bcsub($old_stock['goods_stock'],$val['num_stock'],4);
                $new_outcome_stock=bcadd($old_stock['outcome_stock'],$val['num_stock'],4);
               /* $new_outcome_price=bcadd($old_stock['outcome_price'],$price,2);*/
                $data=array('goods_stock'=>$new_stock,'outcome_stock'=>$new_outcome_stock);
                $stock=D("BWgoodsStock")->update($condition,$data);
                if($stock===false){
                    M()->rollback();
                    $arr['status'] =0;
                    $arr['msg']='更新库存失败！';
                    return $arr;
                }
            }
        }
        $allot_id=$this->add_allot();
        $allot_detail=$this->add_allot_detail($postArray,$allot_id);
        if($allot_id!==false&&$allot_detail!==false){
            M()->commit();
            S('session_menu' . get_current_admin_id(), null);
            $arr['status'] =1;
            $arr['url'] =U("BWallot/allot_index");
        }else{
            M()->rollback();
            $arr['status'] =0;
            $arr['msg']='添加失败！';
            $arr['test']=$allot_id."//".$allot_detail."//";
        }
        return $arr;
    }
    //添加调拨明细
    public function add_allot(){
        $order = I("post.order");
        $store=$order['store'];
        $mystore=$order['mystore'];
        $comment = $order['remark'];
        $trance_time = empty($order['create_time'])?date("Y-m-d H:i:s",time()):$order['create_time'];
        $add_status = $order['add_status']=="save"?-1:0;
        $orderid = b_order_number("BWallot", "batch");
        //获取门店id
        $condition=array("id"=>$store);
        $to_sid=M("BWarehouse")->where($condition)->getField("shop_id",true);
        $condition=array("id"=>$mystore);
        $from_sid=M("BWarehouse")->where($condition)->getField("shop_id",true);
        $a_data=array();
        $a_data["company_id"] = get_company_id();
        //$a_data["from_sid"] = $from_sid;
        // $a_data["to_sid"] = $to_sid;
        $a_data["to_id"] = $store;
        $a_data["from_id"] = $mystore;
        $a_data["memo"] = $comment;
        $a_data["batch"] = $orderid;
        $a_data["type"] = $this->get_allot_type($from_sid,$to_sid);
        $a_data["create_time"] = time();
        $a_data["allot_time"] = strtotime($trance_time);
        $a_data["creator_id"] = get_user_id();
        $a_data["status"] = $add_status;
        $a_data["deleted"] = 0;
        $allot_id = D("BWallot")->insert($a_data);
        return $allot_id;
    }
    //添加调拨明细
    public function add_allot_detail($postArray,$allot_id){
        $sell_details=array();
        foreach($postArray as $k2=>$v){
            //调拨明细写入
            if(!$v['allot_detail_id']){
                $sell_detail["wallot_id"]=$allot_id;//订单id
                $sell_detail["goods_id"]=$v['id'];//货品id
                $sell_detail["wgoods_stock_id"]=$v['wgoods_stock_id'];//库存id
                $sell_detail['goods_stock']=$v['num_stock'];
                $sell_details[]=$sell_detail;
            }
        }
        $li2 = D("b_wallot_detail")->addAll($sell_details);
        return $li2;
    }
    //更新调拨明细
    public function update_allot_detail($postArray,$allot_id){
        $sell_details=array();
        foreach($postArray as $k2=>$v){
            //调拨明细写入
            if($v['allot_detail_id']) {
                $v['allot_detail_id'] = empty($v['allot_detail_id']) ? 0 : $v['allot_detail_id'];
                $sell_detail['goods_stock'] = $v['num_stock'];
                $li2 = D("b_wallot_detail")->update(array('id' => $v['allot_detail_id']), $sell_detail);
                if ($li2 === false) {
                    return false;
                }
            }
        }

        return true;
    }
    //编辑提交
    public function edit_post(){
        $postArray =I("order_detail");
        $order =I("order");
        $mystore=$order['mystore'];
        M()->startTrans();
        foreach($postArray as $k =>$val){
            $condition=array("goods_id"=>$val["id"],"warehouse_id"=>$val["warehouse_id"]);
            if(empty($val['num_stock'])){
                M()->rollback();
                $arr['status'] =0;
                $arr['msg']='第' . ++ $k . '行商品，调拨量不能为空！';
                return $arr;
            }
            //判断是编辑还是新增调拨明细
            if($val['allot_detail_id']>0){
                $allotDetail=D("b_wallot_detail")->getInfo(array('id'=>$val['allot_detail_id']));
                //判断调拨库存是增加还是减少，从而取前后的差值(num_stock增加为正，减少为负)进行更改库存
                $val['num_stock']=$val['num_stock']-$allotDetail['goods_stock'];//差值
                if($val['num_stock']<=0){
                    $stock_status=true;
                }else{
                    $stock_status=D("BWgoodsStock")->is_enough_stock($condition,$val['num_stock'],$val['sell_pricemode']);//判断是否
                }
            }else{
                $stock_status=D("BWgoodsStock")->is_enough_stock($condition,$val['num_stock'],$val['sell_pricemode']);//判断是否
            }
            if(!$stock_status){
                M()->rollback();
                $arr['status'] =0;
                $arr['msg']='第' . ++ $k . '行商品，库存不足！';
                $arr['test_msg']=$stock_status;
                return $arr;
            }else{
                //$price=(float)$val['sell_price'];
                $condition=array("goods_id"=>$val["id"],"warehouse_id"=>$mystore);
                $old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
                $new_stock=bcsub($old_stock['goods_stock'],$val['num_stock'],4);
                $new_outcome_stock=bcadd($old_stock['outcome_stock'],$val['num_stock'],4);
                /* $new_outcome_price=bcadd($old_stock['outcome_price'],$price,2);*/
                $data=array('goods_stock'=>$new_stock,'outcome_stock'=>$new_outcome_stock);
                $stock=D("BWgoodsStock")->update($condition,$data);
                if($stock===false){
                    M()->rollback();
                    $arr['status'] =0;
                    $arr['msg']='更新库存失败！';
                    return $arr;
                }
            }
        }

        $allot_id=$this->update_allot();
        //添加明细
        $allot_detail=$this->add_allot_detail($postArray,$order['allot_id']);
        //更新明细
        $update_allot_detail=$this->update_allot_detail($postArray,$order['allot_id']);
        if($allot_id!==false&&$allot_detail!==false&&$update_allot_detail!==false){
            M()->commit();
            S('session_menu' . get_current_admin_id(), null);
            $arr['status'] =1;
            $arr['url'] =U("BWallot/allot_index");
        }else{
            M()->rollback();
            $arr['status'] =0;
            $arr['msg']='添加失败！';
            $arr['test']=$allot_id."//".$allot_detail."//".$update_allot_detail;
        }
        return $arr;
    }
    //更新调拨单
    public function update_allot(){
        $order = I("post.order");
        $store=$order['store'];
        $mystore=$order['mystore'];
        $comment = $order['remark'];
        $trance_time = empty($order['create_time'])?date("Y-m-d H:i:s",time()):$order['create_time'];
        $add_status = $order['add_status']=="save"?-1:0;
        //获取门店id
        $condition=array("id"=>$store);
        $to_sid=M("BWarehouse")->where($condition)->getField("shop_id",true);
        $condition=array("id"=>$mystore);
        $from_sid=M("BWarehouse")->where($condition)->getField("shop_id",true);
        $a_data=array();
        //$a_data["company_id"] = get_company_id();
        $a_data["to_id"] = $store;
        //$a_data["from_id"] = $mystore;
        $a_data["memo"] = $comment;
        $a_data["type"] = $this->get_allot_type($from_sid,$to_sid);
       // $a_data["create_time"] = time();
        $a_data["allot_time"] = strtotime($trance_time);
        $a_data["creator_id"] = get_user_id();
        $a_data["status"] = $add_status;
        $a_data["deleted"] = 0;
        $condition=array("id"=>$order['allot_id']);
        $allot_id = D("BWallot")->update($condition,$a_data);
        return $allot_id;
    }

    //判断调拨类型
    public function get_allot_type($from_sid,$to_sid){
        if($to_sid>0&&$from_sid>0){
            $type=3;
        }else{
            if($to_sid>0||$from_sid>0){
                $type=2;
            }else{
                $type=1;
            }
        }
        return $type;
    }
    //获取一条调拨的所有信息
    public function getInfo_detail(){
        $condition=array("bwallot.company_id"=>get_company_id(),"bwallot.deleted"=>0,"bwallot.id"=>I("get.id",0,"intval"));
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on bwallot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on bwallot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."m_users create_musers on create_musers.id=bwallot.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bwallot.check_id";
        $join.=" left join ".DB_PRE."m_users outbound_musers on outbound_musers.id=bwallot.outbound_id";
        $join.=" left join ".DB_PRE."m_users receipt_musers on receipt_musers.id=bwallot.receipt_id";
        $field="bwallot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname,";
        $field.="create_musers.user_nicename,check_musers.user_nicename check_name,outbound_musers.user_nicename outbound_name,receipt_musers.user_nicename receipt_name";
        $bwallot_data=$this->alias("bwallot")->getInfo($condition,$field,$join,$order='bwallot.id desc');
        return $bwallot_data;
    }
    //获取调拨明细的商品id
    public function get_wgoods($allot_id){
        $condition=array("bwallot.id"=>$allot_id,"bwallot.company_id"=>get_company_id(),"bwallot.deleted"=>0,"bwallotdetail.deleted"=>0);
        $join="left join ".DB_PRE."b_wallot_detail bwallotdetail on bwallot.id=bwallotdetail.wallot_id";
        $productid =$this->alias("bwallot")->where($condition)->join($join)->getField("goods_id", true);
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
        $condition ["table"] = DB_PRE.'b_wallot';
        $condition ["field"] = 'status';
        $status_list = D ( 'b_status' )->getFieldValue($condition);
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on bwallot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on bwallot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."m_users create_musers on create_musers.id=bwallot.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bwallot.check_id";
        $join.=" left join ".DB_PRE."m_users outbound_musers on outbound_musers.id=bwallot.outbound_id";
        $join.=" left join ".DB_PRE."m_users receipt_musers on receipt_musers.id=bwallot.receipt_id";
        $field="bwallot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname";
        $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,outbound_musers.user_nicename outbound_name,receipt_musers.user_nicename receipt_name";
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $data=$this->alias("bwallot")->getList($excel_where,$field,$limit,$join,$order='bwallot.id desc');
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['batch'] = $v['batch'];
                $expotdata[$k]['to_whname'] = $v['to_whname'];
                $expotdata[$k]['from_whname'] = $v['from_whname'];
                $expotdata[$k]['count'] =D('BWallotDetail')->countList($condition=array("deleted"=>0,"allot_id"=>$v["id"]));
                $expotdata[$k]['user_nicename'] = $v['user_nicename'];
                $expotdata[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $expotdata[$k]['check_name'] = $v['check_name'];
                $expotdata[$k]['check_time'] = empty($v['check_time'])?'':date('Y-m-d H:i:s',$v['check_time']);
                $expotdata[$k]['outbound_name'] = $v['outbound_name'];
                $expotdata[$k]['outbound_time'] =empty($v['outbound_time'])?'':date('Y-m-d H:i:s',$v['outbound_time']);
                $expotdata[$k]['receipt_name'] = $v['receipt_name'];
                $expotdata[$k]['receipt_time'] =empty($v['receipt_time'])?'':date('Y-m-d H:i:s',$v['receipt_time']);
                $expotdata[$k]['status'] = $status_list[$v['status']];
            }
            register_shutdown_function(array(&$this, $action),$excel_where,$excel_field, $excel_join, $page + 1);
            $title=array('序','调拨单号','入库仓库','出库仓库','数量','调拨人','调拨时间','审核人','审核时间','出库人','出库时间','入库人','入库时间','状态');
            exportexcel($expotdata,$title,"批发调拨记录列表");
        }
    }
}
