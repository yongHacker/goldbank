<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BAllotRproductModel extends BCommonModel{
    
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//提交
    const REVOKE              =  4;//撤销
    const ALLOT_CHECK_PASS    =  5;//审批通过
    const ALLOT_CHECK_DENY    =  6;//审批拒绝
    const ALLOT_CHECK_REJECT  =  7;//审批驳回
    const OUT_CHECK_PASS      =  8;//出库通过
    const OUT_CHECK_DENY      =  9;//出库拒绝
    const OUT_CHECK_REJECT    =  10;//出库驳回
    const IN_CHECK_PASS       =  11;//入库通过
    const IN_CHECK_DENY       =  12;//入库拒绝
    
    
    
    /*操作状态名*/
    const CREATE_NAME               =  '创建表单';
    const SAVE_NAME                 =  '保存表单';
    const COMMIT_NAME               =  '提交表单';
    const REVOKE_NMAE               =  '撤销表单';
    const ALLOT_CHECK_PASS_NMAE     =  '审批通过';
    const ALLOT_CHECK_DENY_NAME     =  '审批拒绝';
    const ALLOT_CHECK_REJECT_NAME   =  '审批驳回';
    const OUT_CHECK_PASS_NMAE       =  '出库通过';
    const OUT_CHECK_DENY_NAME       =  '出库拒绝';
    const OUT_CHECK_REJECT_NAME     =  '出库驳回';
    const IN_CHECK_PASS_NMAE        =  '入库通过';
    const IN_CHECK_DENY_NAME        =  '入库拒绝';
    
    
    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const OUT_PROKEY          =  3;//出库流程键值
    const IN_PROKEY           =  4;//入库流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';
    const OUT_PROCESS         =  '出库确认';
    const IN_PROCESS          =  '入库确认';
    
    /*操作函数名*/
    const CHECK_FUNCTION     =  'shop/ballotrproduct/allot_check_post';
    const OUT_FUNCTION       =  'shop/ballotrproduct/outbound_check_post';
    const IN_FUNCTION        =  'shop/ballotrproduct/receipt_check_post';
    
    
    //调拨开单
    public function add_post(){
        //order所有选中金料id//store收货仓id//mystore发货仓id//comment备注//trance_time开单时间//$orderid单号
        $order_info = I("post.order");
        $store = $order_info['store'];//收货仓库
        $mystore =  $order_info['mystore'];//发货仓库
        $comment =  $order_info['comment'];//评论
        $trance_time =empty($order_info['trance_time'])?date("Y-m-d H:i:s",time()):$order_info['trance_time']; //调拨时间
        $add_status =$order_info['add_status'];//保存或提交
        $add_status=$add_status=="add"?-1:0;//-1保存0提交
        $allot_pids =$order_info['allot_pids'];//调拨的金料id，用逗号隔开
        $orderid = b_order_number("BAllotRproduct", "batch");//单号
        //获取门店id
        $condition=array("id"=>$store);
        $to_sid=M("BWarehouse")->where($condition)->getField("shop_id");
        $condition=array("id"=>$mystore);
        $from_sid=M("BWarehouse")->where($condition)->getField("shop_id");
        if (empty($allot_pids)) {
            $error["status"] = 0;
            $error["msg"] = "请选择需要调拨的金料";
            return $error;
        }
        if (empty($store)) {
            $error["status"] = 0;
            $error["msg"] = "请选择收货仓库";
            return $error;
        }
        if ($store==$mystore) {
            $error["status"] = 0;
            $error["msg"] = "收货仓与发货仓不能相同";
            return $error;
        }
        $product_codes = array();
        $postArray = explode(',',$allot_pids);
        $info=D('BRecoveryProduct')->check_product_status($postArray,2,$mystore);
        if($info['status']!==1){
            return $info;
        }else{
            $product_codes=$info["product_codes"];
        }
        $info=D('BRecoveryProduct')->check_product_repeat($postArray,$product_codes);
        if($info['status']!==1){
            return $info;
        }
        M()->startTrans();
        $a_data=$this->get_insert_data($mystore,$store,$from_sid,$to_sid,$comment,$postArray,$orderid,$trance_time,$add_status);
        $allot_id = D("BAllotRproduct")->insert($a_data);
        /*添加表单操作记录 add by lzy 2018.5.30 start*/
        if($allot_id){
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,self::CREATE);
            if($add_status==0){
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,self::COMMIT);
            }else{
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$allot_id,self::SAVE);
            }
        }
        /*添加表单操作记录 add by lzy 2018.5.30 end*/
        $BAllotRproductDetail=$this->add_a_r_detail($postArray,$allot_id,$add_status);
        // 添加调拨单明细,修改金料状态为冻结
        if($add_status == 0){
            if($allot_pids){
                $condition=array("id"=>array("in",$allot_pids));
                $BProduct=D("BRecoveryProduct")->update($condition,array('status'=>3));
            }
        }else{
            $BProduct = true;
        }
        if ($record_result!==false&&$allot_id!==false&&$BProduct!==false&&$BAllotRproductDetail!==false) {
            M()->commit();
            $result["status"] = 1;
            $result["msg"] = "保存成功！";
            S('session_business_menu' . get_user_id(), null);
            return $info;
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "保存失败！";
            $result["test"] = $record_result."//".$allot_id."//".$BProduct."//".$BAllotRproductDetail;
            return $info;
            //$this->ajaxReturn($info);
        }
    }
    function add_a_r_detail($postArray,$allot_id){
        $ad_data=array();//调拨明细
        foreach ($postArray as $key => $val) {
            if(!empty($val)){
                $ad_data[$key]["p_id"] = $val;
                $ad_data[$key]["allot_id"] = $allot_id;
                $ad_data[$key]["company_id"]=get_company_id();
                $ad_data[$key]["deleted"] = 0;
            }
        }
        // 添加调拨单
        $BAllotRproductDetail=D("BAllotRproductDetail")->insertAll($ad_data);
        return $BAllotRproductDetail;
    }
    /**
     * 获取调拨单信息
     * @param $mystore           发货仓库
     * @param $store             收货仓库
     * @param $from_sid          发货门店
     * @param $to_sid            收货门店
     * @param $comment           评论
     * @param $allot_pids        调拨金料id
     * @param $orderid           调拨批次
     * @param $trance_time       调拨时间
     * @param $add_status        调拨保存或提交
     * @return mixed
     */
    function get_insert_data($mystore,$store,$from_sid,$to_sid,$comment,$allot_pids,$orderid,$trance_time,$add_status){
        $a_data["company_id"] = get_company_id();
        $a_data["from_id"] = $mystore;
        $a_data["to_id"] = $store;
        $a_data["from_sid"] = $from_sid;
        $a_data["to_sid"] = $to_sid;
        $a_data["memo"] = $comment;
        $a_data["allot_num"] = count(array_filter($allot_pids));
        $a_data["batch"] = $orderid;
        $a_data["type"] = $this->get_allot_type($from_sid,$to_sid);
        $a_data["create_time"] = time();
        $a_data["allot_time"] = strtotime($trance_time);
        $a_data["creator_id"] = get_user_id();
        $a_data["status"] = $add_status;
        $a_data["deleted"] = 0;
        return $a_data;
    }
    //编辑调拨单
    public function edit_post(){
        $orderid = I("post.allocationid",0);
        $postArray = I("post.order");
        $all_products= I("post.all_products");
        $all_products = array_filter(explode(',', $all_products[0]["id"]));
        $postArray = array_filter(explode(',', $postArray[0]["id"]));
        $store = I("post.store");
        $add_status = I("post.add_status");
        if($add_status == "add"){
            $add_status = -1;
        }else{
            $add_status = 0;
        }
        $map["id"] = $orderid;
        $map["deleted"] = 0;
        $map["creator_id"] = get_user_id();
        $map['status']=array("in",array(-1,-2,3));
        if (empty($orderid)) {
            $error["status"] = 0;
            $error["msg"] = "订单不存在，无法修改";
            return $error;
        }
        $allocations = $this->getInfo($map);
        if ($allocations) {
            if (count($all_products) < 1) {
                $error["status"] = 0;
                $error["msg"] = "请选择需要调拨的金料";
                return $error;
            }
            if (empty($store)) {
                $error["status"] = 0;
                $error["msg"] = "请选择收货仓库";
                return $error;
            }
            $product_codes = array();
            $info=D('BRecoveryProduct')->check_product_status($all_products,2,$allocations['from_id']);
            if($info['status'] !== 1){
                return $info;
                //$this->ajaxReturn($info);
            }else{
                $product_codes = $info["product_codes"];
            }
            $info = D('BRecoveryProduct')->check_product_repeat($all_products,$product_codes);
            if($info['status'] !== 1){
                return $info;
                //$this->ajaxReturn($info);
            }

            $data = array();
            $product_ids = array();//更新金料
            foreach($postArray as $k => $v) {
                if(empty($v)){
                    continue;
                }

                $data[] = array(
                    'p_id'=> $v,
                    'allot_id'=> $orderid,
                    'company_id'=>get_company_id()
                );
            }
            $product_ids = array_filter(array_merge($postArray, $all_products));
            M()->startTrans();
            // 添加调拨单明细

            if(!empty($data)){
                $addAll = D("BAllotRproductDetail")->addAll($data);
            }else{
                $addAll = true;
            }

            if(!empty($product_ids) && $add_status == 0){
                $condition = array("id"=>array("in", $product_ids));
                $update_product = D("BRecoveryProduct")->update($condition,array("status"=>3));
            }else{
                $update_product=true;
            }
            //获取门店id
            $condition=array("id"=>$store);
            $to_sid=M("BWarehouse")->where($condition)->getField("shop_id");
            $data2["to_id"] = $store;
            $data2["memo"] = I("post.comment");
            $data2["status"] = $add_status;
            $data2["allot_num"] = count($product_ids);
            $data2["allot_time"]=strtotime(I("post.trance_time",date("Y-m-d H:i:s",time())));//调拨开单暂时可改日期 add by lzy 2018.5.10
            $data2["type"] = $this->get_allot_type($allocations['from_sid'],$to_sid);
            $update_allot = $this->update($map, $data2);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            if($add_status==0){
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$map['id'],self::COMMIT);
            }else{
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RPRODUCT_ALLOT,$map['id'],self::SAVE);
            }
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
           if($update_product !== false && $addAll !==false && $update_allot !==false&&$record_result){
               M()->commit();
               $error["status"] = 1;
               $error["msg"] = "保存成功";
               return $error;
           }else{
               M()->rollback();
               $error["status"] = 0;
               $error["msg"] = "保存失败";
               $error["test"] = $update_product."//".$addAll."//".$update_allot;
               return $error;
           }

        } else {
            $error["status"] = 0;
            $error["msg"] = "订单无法修改";
            return $error;
        }
    }
    //判断调拨类型
    public function get_allot_type($from_sid,$to_sid){
        if($to_sid>0&&$from_sid>0){
            $type=2;
        }else{
            if($to_sid>0||$from_sid>0){
                $type=1;
            }else{
                $type=0;
            }
        }
        return $type;
    }

    //获取一条调拨的所有信息
    public function getInfo_detail(){
        $condition=array("ballot.company_id"=>get_company_id(),"ballot.deleted"=>0,"ballot.id"=>I("get.id",0,"intval"));
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on ballot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on ballot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=ballot.creator_id and create_musers.deleted=0 and create_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=ballot.check_id  and check_musers.deleted=0 and check_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  outbound_musers on outbound_musers.user_id=ballot.outbound_id  and outbound_musers.deleted=0 and outbound_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  receipt_musers on receipt_musers.user_id=ballot.receipt_id  and receipt_musers.deleted=0 and receipt_musers.company_id=ballot.company_id";
        $field="ballot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname,";
        $field.="create_musers.employee_name user_nicename,check_musers.employee_name check_name,outbound_musers.employee_name outbound_name,receipt_musers.employee_name receipt_name";
        $ballot_data=$this->alias("ballot")->getInfo($condition,$field,$join,$order='ballot.id desc');
        return $ballot_data;
    }
    //获取调拨明细的金料id
    public function get_pruductids($allot_id){
        $condition=array("ballot.id"=>$allot_id,"ballot.company_id"=>get_company_id(),"ballot.deleted"=>0,"ballotdetail.deleted"=>0);
        $join="left join ".DB_PRE."b_allot_rproduct_detail ballotdetail on ballot.id=ballotdetail.allot_id";
        $productid =$this->alias("ballot")->where($condition)->join($join)->getField("p_id", true);
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
        $condition ["table"] = DB_PRE.'b_allot';
        $condition ["field"] = 'status';
        $status_list = D ( 'b_status' )->getFieldValue($condition);
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on ballot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on ballot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=ballot.creator_id and create_musers.deleted=0 and create_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=ballot.check_id  and check_musers.deleted=0 and check_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  outbound_musers on outbound_musers.user_id=ballot.outbound_id  and outbound_musers.deleted=0 and outbound_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  receipt_musers on receipt_musers.user_id=ballot.receipt_id  and receipt_musers.deleted=0 and receipt_musers.company_id=ballot.company_id";
        $field="ballot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,outbound_musers.employee_name outbound_name,receipt_musers.employee_name receipt_name";
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $data=$this->alias("ballot")->getList($excel_where,$field,$limit,$join,$order='ballot.id desc');
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['batch'] = $v['batch'];
                $expotdata[$k]['to_whname'] = $v['to_whname'];
                $expotdata[$k]['from_whname'] = $v['from_whname'];
                $expotdata[$k]['count'] =D('BAllotRproductDetail')->countList($condition=array("deleted"=>0,"allot_id"=>$v["id"]));
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
            exportexcel($expotdata,$title,"调拨记录列表");
        }
    }
    //获取审核条数
    function get_check_count($where,$name,$url){
        $condition=array("ballot.company_id"=>get_company_id(),"ballot.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on ballot.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on ballot.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=ballot.creator_id and create_musers.deleted=0 and create_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=ballot.check_id  and check_musers.deleted=0 and check_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  outbound_musers on outbound_musers.user_id=ballot.outbound_id  and outbound_musers.deleted=0 and outbound_musers.company_id=ballot.company_id";
        $join.=" left join ".DB_PRE."b_employee  receipt_musers on receipt_musers.user_id=ballot.receipt_id  and receipt_musers.deleted=0 and receipt_musers.company_id=ballot.company_id";
        $field="ballot.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,outbound_musers.employee_name outbound_name,receipt_musers.employee_name receipt_name";
        $count=D("BAllotRproduct")->alias("ballot")->countList($condition,$field,$join,$order='ballot.id desc');
        $result=array('name'=>$name,'url'=>$url,'count'=>$count);
        return $result;
    }
    /**
     * @author lzy 2018.5.30
     * @param int $allot_id 调拨单id
     * @return 操作记录列表
     */
    public function getOperateRecord($allot_id){
        $condition=array(
            'operate.company_id'=>get_company_id(),
            'operate.type'=>BBillOpRecordModel::RPRODUCT_ALLOT,
            'operate.sn_id'=>$allot_id,
            'employee.deleted'=>0,
            'employee.company_id'=>get_company_id(),
        );
        $field="operate.operate_type,operate.operate_time,employee.employee_name";
        $join="join gb_b_employee employee on employee.user_id=operate.operate_id";
        $record_list=D('BBillOpRecord')->alias("operate")->getList($condition,$field,'',$join,'operate.id asc');
        $type_list=$this->_groupType();
        foreach ($record_list as $key => $val){
            $record_list[$key]['operate_name']=$type_list[$val['operate_type']];
        }
        return $record_list;
    }
    /**
     * @author lzy 2018.5.30
     * 将所有的状态码组合起来
     */
    private function _groupType(){
        return array(
            self::CREATE=>self::CREATE_NAME,
            self::SAVE=>self::SAVE_NAME,
            self::COMMIT=>self::COMMIT_NAME,
            self::REVOKE=>self::REVOKE_NMAE,
            self::ALLOT_CHECK_PASS=>self::ALLOT_CHECK_PASS_NMAE,
            self::ALLOT_CHECK_DENY=>self::ALLOT_CHECK_DENY_NAME,
            self::ALLOT_CHECK_REJECT=>self::ALLOT_CHECK_REJECT_NAME,
            self::OUT_CHECK_PASS=>self::OUT_CHECK_PASS_NMAE,
            self::OUT_CHECK_DENY=>self::OUT_CHECK_DENY_NAME,
            self::OUT_CHECK_REJECT=>self::OUT_CHECK_REJECT_NAME,
            self::IN_CHECK_PASS=>self::IN_CHECK_PASS_NMAE,
            self::IN_CHECK_DENY=>self::IN_CHECK_DENY_NAME,
        );
    }
    /**
     * @author lzy 2018.5.30
     * 获取流程数组
     */
    public function getProcess($allot_id){
        $process_list=$this->_groupProcess();
        if(!empty($allot_id)){
            $condition=array(
                'allot.id'=>$allot_id
            );
            $field='allot.*,create_employee.employee_name as creator_name,check_employee.employee_name as check_name,out_employee.employee_name as outbound_name,in_employee.employee_name as in_name';
            $join='left join gb_b_employee create_employee on allot.creator_id=create_employee.user_id and create_employee.company_id='.get_company_id();
	        $join.=' left join gb_b_employee check_employee on allot.check_id=check_employee.user_id and check_employee.company_id='.get_company_id();
	        $join.=' left join gb_b_employee out_employee on allot.outbound_id=out_employee.user_id and out_employee.company_id='.get_company_id();
	        $join.=' left join gb_b_employee in_employee on allot.receipt_id=in_employee.user_id and in_employee.company_id='.get_company_id();
            $allot_info=$this->alias("allot")->getInfo($condition,$field,$join);
            $process_list[self::CREATE_PROKEY]['is_done']=1;
            $process_list[self::CREATE_PROKEY]['user_name']=$allot_info['creator_name'];
            $process_list[self::CREATE_PROKEY]['time']=$allot_info['create_time'];
            /*检查是否审核*/
            if($allot_info['check_id']>0&&($allot_info['status']>=1)){
                $process_list[self::CHECK_PROKEY]['is_done']=1;
                $process_list[self::CHECK_PROKEY]['user_name']=$allot_info['check_name'];
                $process_list[self::CHECK_PROKEY]['time']=$allot_info['check_time'];
            }else{
                $process_list[self::CHECK_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::CHECK_FUNCTION);
                $process_list[self::CHECK_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
            }
            /*检查是否出库*/
            if(($allot_info['status']>=4)){
                $process_list[self::OUT_PROKEY]['is_done']=1;
                $process_list[self::OUT_PROKEY]['user_name']=$allot_info['outbound_name'];
                $process_list[self::OUT_PROKEY]['time']=$allot_info['outbound_time'];
            }else{
                $process_list[self::OUT_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByAllotRproductid(self::OUT_FUNCTION,$allot_id,1);
                $process_list[self::OUT_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
            }
            /*检查是否入库*/
            if(($allot_info['status']==6||$allot_info['status']==7)){
                $process_list[self::IN_PROKEY]['is_done']=1;
                $process_list[self::IN_PROKEY]['user_name']=$allot_info['in_name'];
                $process_list[self::IN_PROKEY]['time']=$allot_info['receipt_time'];
            }else{
                $process_list[self::IN_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByAllotRproductid(self::IN_FUNCTION,$allot_id,2);
                $process_list[self::IN_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
            }
        }
        return $process_list;
         
    }
    /**
     * @author lzy 2018.5.30
     * 将所有的流程组合起来
     */
    private function _groupProcess(){
        return array(
            self::CREATE_PROKEY=>array(
                'process_name'=>self::CREATE_PROCESS,
            ),
            self::CHECK_PROKEY=>array(
                'process_name'=>self::CHECK_PROCESS,
            ),
            self::OUT_PROKEY=>array(
                'process_name'=>self::OUT_PROCESS,
            ),
            self::IN_PROKEY=>array(
                'process_name'=>self::IN_PROCESS,
            ),
        );
    }
}
