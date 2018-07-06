<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BMergeOrderModel extends BCommonModel{
    
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//提交
    const REVOKE              =  4;//撤销
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝
    const CHECK_REJECT        =  7;//审批驳回

    /*操作状态名*/
    const CREATE_NAME               =  '创建表单';
    const SAVE_NAME                 =  '保存表单';
    const COMMIT_NAME               =  '提交表单';
    const REVOKE_NMAE               =  '撤销表单';
    const CHECK_PASS_NMAE           =  '审批通过';
    const CHECK_DENY_NAME           =  '审批拒绝';
    const CHECK_REJECT_NAME         =  '审批驳回';
    
    
    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';

    
    /*操作函数名*/
    const CHECK_FUNCTION     =  'business/bmergeorder/bmerge_order_check_post';
    const OUT_FUNCTION       =  'business/bmergeorder/outbound_check_post';
    const IN_FUNCTION        =  'business/bmergeorder/receipt_check_post';
    
    
    //合并开单
    public function add_post(){
        //order所有选中金料id//store收货仓id//mystore发货仓id//comment备注//trance_time开单时间//$orderid单号
        $order_info = I("post.order");
        $recovery_products = I("post.new_product_list");// 合并后金料列表数组
        $mystore =  $order_info['mystore'];//金料仓库
        $comment =  $order_info['comment'];//评论
        $trance_time =empty($order_info['trance_time'])?date("Y-m-d H:i:s",time()):$order_info['trance_time']; //合并时间
        $add_status =$order_info['add_status'];//保存或提交
        $add_status=$add_status=="add"?-1:0;//-1保存0提交
        $bmerge_order_pids =$order_info['allot_pids'];//合并的金料id，用逗号隔开
        $orderid = b_order_number("BMergeOrder", "batch");//单号
        //获取门店id
        $condition=array("id"=>$mystore);
        $shop_id=M("BWarehouse")->where($condition)->getField("shop_id");
        if (empty($bmerge_order_pids)) {
            $error["status"] = 0;
            $error["msg"] = "请选择需要合并的金料";
            return $error;
        }
        if (empty($recovery_products)) {
            $error["status"] = 0;
            $error["msg"] = "请选择填写合并后的金料";
            return $error;
        }
        $product_codes = array();
        $postArray = explode(',',$bmerge_order_pids);
        $info=D('BRecoveryProduct')->check_product_status($postArray);
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
        $a_data=$this->get_insert_data($shop_id,$comment,$postArray,$orderid,$trance_time,$add_status,$mystore,$recovery_products);
        $merge_id = $this->insert($a_data);
        /*添加表单操作记录 add by lzy 2018.5.30 start*/
        if($merge_id){
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$merge_id,self::CREATE);
            if($add_status==0){
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$merge_id,self::COMMIT);
            }else{
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$merge_id,self::SAVE);
            }
        }
        /*添加表单操作记录 add by lzy 2018.5.30 end*/
        $BMergeDetail=$this->add_merge_detail($postArray,$merge_id);
        // 添加合并单明细,修改金料状态为冻结
        if($add_status == 0){
            if($bmerge_order_pids){
                $condition=array("id"=>array("in",$bmerge_order_pids));
                $BProduct=D("BRecoveryProduct")->update($condition,array('status'=>6));
            }
        }else{
            $BProduct = true;
        }
       
        $info = D('BRecoveryProduct')->product_check($recovery_products);
        if($info['status'] == 0){
            return $info;
        }
        $new_products=true;
        if($merge_id){
            $new_products = D('BRecoveryProduct')->add_product($recovery_products,$merge_id,6,$mystore);
        }
        if ($new_products!==false&&$record_result!==false&&$merge_id!==false&&$BProduct!==false&&$BMergeDetail!==false) {
            M()->commit();
            $result["status"] = 1;
            $result["msg"] = "保存成功！";
            S('session_business_menu' . get_user_id(), null);
            return $info;
        } else {
            M()->rollback();
            $result["status"] = 0;
            $result["msg"] = "保存失败！";
            $result["test"] = $record_result."//".$merge_id."//".$BProduct."//".$BMergeDetail;
            return $info;
            //$this->ajaxReturn($info);
        }
    }
    function add_merge_detail($postArray,$merge_id,$del_merge_detail){
        $ad_data=array();//合并明细
        foreach ($postArray as $key => $val) {
            if(!empty($val)){
                $ad_data[$key]["p_id"] = $val;
                $ad_data[$key]["merge_id"] = $merge_id;
                $ad_data[$key]["company_id"]=get_company_id();
                $ad_data[$key]["deleted"] = 0;
            }
        }
        $del_detail=true;
        if($del_merge_detail){
            $del_detail=D("BMergeDetail")->update(array('id'=>array('in',$del_merge_detail)),array('deleted'=>1));
        }
        if($del_detail!==false){
            // 添加合并单
            $BMergeDetail=empty($ad_data)?true:D("BMergeDetail")->insertAll($ad_data);
            return $BMergeDetail;
        }else{
            return false;
        }

    }
    /**
     * 获取合并单信息
     * @param $shop_id           门店
     * @param $comment           评论
     * @param $bmerge_order_pids        合并金料id
     * @param $orderid           合并批次
     * @param $trance_time       合并时间
     * @param $add_status        合并保存或提交
     * @return mixed
     */
    function get_insert_data($shop_id,$comment,$bmerge_order_pids,$orderid,$trance_time,$add_status,$mystore,$recovery_products){
        $a_data["company_id"] = get_company_id();
        $a_data["shop_id"] = $shop_id;
        $a_data["warehouse_id"] = $mystore;
        $a_data["memo"] = $comment;
        $a_data["num"] = count(array_filter($bmerge_order_pids));
        $a_data["after_num"] = count(array_filter($recovery_products));
        $a_data["batch"] = $orderid;
        $a_data["create_time"] = time();
        $a_data["merge_time"] = strtotime($trance_time);
        $a_data["creator_id"] = get_user_id();
        $a_data["status"] = $add_status;
        $a_data["deleted"] = 0;
        return $a_data;
    }
    //编辑合并单
    public function edit_post(){
        $orderid = I("post.allocationid",0);
        $postArray = I("post.order");
        $recovery_products = I("post.new_product_list");// 合并后金料列表数组
        $del_old_product_detail = I("post.del_old_product_detail");// 合并后金删除列表数组
        $del_merge_detail = I("post.del_merge_detail");// 需要删除的合并明细
        $all_products= I("post.all_products");
        $all_products = explode(',', $all_products[0]["id"]);
        $postArray = explode(',', $postArray[0]["id"]);
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
                $error["msg"] = "请选择需要合并的金料";
                return $error;
            }
            if (empty($recovery_products)) {
                $error["status"] = 0;
                $error["msg"] = "请选择填写合并后的金料";
                return $error;
            }

            $product_codes = array();
            $info=D('BRecoveryProduct')->check_product_status($all_products);
            if($info['status'] !== 1){
                return $info;
                //$this->ajaxReturn($info);
            }else{
                $product_codes = $info["product_codes"];
            }
            $info = D('BRecoveryProduct')->check_product_repeat($all_products,$product_codes);
            if($info['status'] !== 1){
                return $info;
            }
            $product_ids = array_filter(array_merge($postArray, $all_products));//更新金料
            M()->startTrans();
            // 添加合并单明细
            if(!empty($postArray)){
                $addAll=$this->add_merge_detail($postArray,$orderid,$del_merge_detail);
            }else{
                $addAll = true;
            }
            //更新合并金料的状态
            if(!empty($product_ids) && $add_status == 0){
                $condition = array("id"=>array("in", $product_ids));
                $update_product = D("BRecoveryProduct")->update($condition,array("status"=>6));
            }else{
                $update_product=true;
            }
            //更新合并单信息
            $data2["memo"] = I("post.comment");
            $data2["status"] = $add_status;
            $data2["num"] = count(array_filter($product_ids));
            $data2["after_num"] = count(array_filter($recovery_products));
            $data2["merge_time"]=strtotime(I("post.trance_time",date("Y-m-d H:i:s",time())));//合并开单暂时可改日期 add by lzy 2018.5.10
            $update_bmerge_order = $this->update($map, $data2);
            /*添加表单操作记录 add by lzy 2018.5.30 start*/
            if($add_status==0){
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$map['id'],self::COMMIT);
            }else{
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::MERGE,$map['id'],self::SAVE);
            }
            /*添加表单操作记录 add by lzy 2018.5.30 end*/
            //检测合并后金料编码和增加金料
            $info = D('BRecoveryProduct')->product_check($recovery_products);
            if($info['status'] == 0){
                return $info;
            }
            $new_products=true;
            if($orderid){
                $new_products = D('BRecoveryProduct')->update_product($recovery_products,$orderid,6,$allocations['warehouse_id'],$del_old_product_detail);
            }
           if($new_products!==false&&$update_product !== false && $addAll !==false && $update_bmerge_order !==false&&$record_result){
               M()->commit();
               $error["status"] = 1;
               $error["msg"] = "保存成功";
               return $error;
           }else{
               M()->rollback();
               $error["status"] = 0;
               $error["msg"] = "保存失败";
               $error["test"] = $update_product."//".$addAll."//".$update_bmerge_order;
               return $error;
           }

        } else {
            $error["status"] = 0;
            $error["msg"] = "订单无法修改";
            return $error;
        }
    }
    //判断合并类型
    public function get_bmerge_order_type($from_sid,$to_sid){
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

    //获取一条合并的所有信息
    public function getInfo_detail(){
        $condition=array("bmerge_order.company_id"=>get_company_id(),"bmerge_order.deleted"=>0,"bmerge_order.id"=>I("get.id",0,"intval"));
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on bmerge_order.warehouse_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=bmerge_order.creator_id and create_musers.deleted=0 and create_musers.company_id=bmerge_order.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=bmerge_order.check_id  and check_musers.deleted=0 and check_musers.company_id=bmerge_order.company_id";
        $field="bmerge_order.*,from_bwarehouse.wh_name from_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name";
        $bmerge_order_data=$this->alias("bmerge_order")->getInfo($condition,$field,$join,$order='bmerge_order.id desc');
        return $bmerge_order_data;
    }
    //获取合并明细的金料id,用于更新金料状态
    public function get_pruductids($merge_id){
        $condition=array("bmerge_order.id"=>$merge_id,"bmerge_order.company_id"=>get_company_id(),"bmerge_order.deleted"=>0,"bmerge_detail.deleted"=>0);
        $join="left join ".DB_PRE."b_merge_detail bmerge_detail on bmerge_order.id=bmerge_detail.merge_id";
        $productid =$this->alias("bmerge_order")->where($condition)->join($join)->getField("p_id", true);
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
        $condition ["table"] = DB_PRE.'b_bmerge_order';
        $condition ["field"] = 'status';
        $status_list = D ( 'b_status' )->getFieldValue($condition);
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on bmerge_order.warehouse_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=bmerge_order.creator_id and create_musers.deleted=0 and create_musers.company_id=bmerge_order.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=bmerge_order.check_id  and check_musers.deleted=0 and check_musers.company_id=bmerge_order.company_id";
        $field="bmerge_order.*,from_bwarehouse.wh_name from_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name";
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $data=$this->alias("bmerge_order")->getList($excel_where,$field,$limit,$join,$order='bmerge_order.id desc');
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['batch'] = $v['batch'];
                $expotdata[$k]['to_whname'] = $v['from_whname'];
                $expotdata[$k]['num'] =$v['num'];
                $expotdata[$k]['after_num'] =$v['after_num'];
                $expotdata[$k]['user_nicename'] = $v['user_nicename'];
                $expotdata[$k]['merger_time'] = date('Y-m-d H:i:s',$v['merger_time']);
                $expotdata[$k]['check_name'] = $v['check_name'];
                $expotdata[$k]['check_time'] = empty($v['check_time'])?'':date('Y-m-d H:i:s',$v['check_time']);
                $expotdata[$k]['status'] = $status_list[$v['status']];
            }
            register_shutdown_function(array(&$this, $action),$excel_where,$excel_field, $excel_join, $page + 1);
            $title=array('序','合并单号','仓库','合并前数量','合并后数量','合并人','合并时间','审核人','审核时间','状态');
            exportexcel($expotdata,$title,"合并记录列表");
        }
    }
    //获取审核条数
    function get_check_count($where,$name,$url){
        $condition=array("bmerge_order.company_id"=>get_company_id(),"bmerge_order.deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."b_warehouse from_bwarehouse on bmerge_order.from_id=from_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_warehouse to_bwarehouse on bmerge_order.to_id=to_bwarehouse.id";
        $join.=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=bmerge_order.creator_id and create_musers.deleted=0 and create_musers.company_id=bmerge_order.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=bmerge_order.check_id  and check_musers.deleted=0 and check_musers.company_id=bmerge_order.company_id";
        $join.=" left join ".DB_PRE."b_employee  outbound_musers on outbound_musers.user_id=bmerge_order.outbound_id  and outbound_musers.deleted=0 and outbound_musers.company_id=bmerge_order.company_id";
        $join.=" left join ".DB_PRE."b_employee  receipt_musers on receipt_musers.user_id=bmerge_order.receipt_id  and receipt_musers.deleted=0 and receipt_musers.company_id=bmerge_order.company_id";
        $field="bmerge_order.*,from_bwarehouse.wh_name from_whname,to_bwarehouse.wh_name to_whname";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,outbound_musers.employee_name outbound_name,receipt_musers.employee_name receipt_name";
        $count=D("bmerge_orderRproduct")->alias("bmerge_order")->countList($condition,$field,$join,$order='bmerge_order.id desc');
        $result=array('name'=>$name,'url'=>$url,'count'=>$count);
        return $result;
    }
    /**
     * @author lzy 2018.5.30
     * @param int $merge_id 合并单id
     * @return 操作记录列表
     */
    public function getOperateRecord($merge_id){
        $condition=array(
            'operate.company_id'=>get_company_id(),
            'operate.type'=>BBillOpRecordModel::MERGE,
            'operate.sn_id'=>$merge_id,
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
            self::CHECK_PASS=>self::CHECK_PASS_NMAE,
            self::CHECK_DENY=>self::CHECK_DENY_NAME,
            self::CHECK_REJECT=>self::CHECK_REJECT_NAME,
        );
    }
    /**
     * @author lzy 2018.5.30
     * 获取流程数组
     */
    public function getProcess($merge_id){
        $process_list=$this->_groupProcess();
        if(!empty($merge_id)){
            $condition=array(
                'bmerge_order.id'=>$merge_id,
                'create_employee.company_id'=>get_company_id(),
                'check_employee.company_id'=>get_company_id(),
            );
            $field='bmerge_order.*,create_employee.employee_name as creator_name,check_employee.employee_name as check_name';
            $join='left join gb_b_employee create_employee on bmerge_order.creator_id=create_employee.user_id and create_employee.company_id='.get_company_id();
	        $join.=' left join gb_b_employee check_employee on bmerge_order.check_id=check_employee.user_id and check_employee.company_id='.get_company_id();
            $bmerge_order_info=$this->alias("bmerge_order")->getInfo($condition,$field,$join);
            $process_list[self::CREATE_PROKEY]['is_done']=1;
            $process_list[self::CREATE_PROKEY]['user_name']=$bmerge_order_info['creator_name'];
            $process_list[self::CREATE_PROKEY]['time']=$bmerge_order_info['create_time'];
            /*检查是否审核*/
            if($bmerge_order_info['check_id']>0&&($bmerge_order_info['status']>=1)){
                $process_list[self::CHECK_PROKEY]['is_done']=1;
                $process_list[self::CHECK_PROKEY]['user_name']=$bmerge_order_info['check_name'];
                $process_list[self::CHECK_PROKEY]['time']=$bmerge_order_info['check_time'];
            }else{
                $process_list[self::CHECK_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::CHECK_FUNCTION);
                $process_list[self::CHECK_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
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
            )
        );
    }
}
