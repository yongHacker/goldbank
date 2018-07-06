<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BOutboundOrderModel extends BCommonModel{
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//提交
    const REVOKE              =  4;//撤销
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝
    const CHECK_REJECT        =  7;//提交驳回

    /*操作状态名*/
    const CREATE_NAME         =  '创建表单';
    const SAVE_NAME           =  '保存表单';
    const COMMIT_NAME         =  '提交表单';
    const REVOKE_NMAE         =  '撤销表单';
    const CHECK_PASS_NMAE     =  '审批通过';
    const CHECK_DENY_NAME     =  '审批拒绝';
    const CHECK_REJECT_NAME   =  '提交驳回';

    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';

    /*操作函数名*/
    const CHECK_FUNCTION     =  'business/boutboundorder/check_post';
    // 添加出库单
    /**
     * @param $postdata
     * @param bool $is_check 是否检测货品是否在库或是否存在
     * @return mixed
     */
    public function add_outbound_order($postdata,$is_check=true) {
        $postArray = $postdata["order_detail"];
        $order = $postdata["order"];
        $recovery_products = $postdata["recovery_products"];
        $comment = $order["comment"];
        $warehouse_id = $order["mystore"];
        $trance_time =  empty($order["trance_time"])?date("Y-m-d H:i:s",time()):$order["trance_time"];
        $orderid = b_order_number("BOutboundOrder", "batch");
        if (count($postArray) < 1) {
            $error["status"] = 0;
            $error["msg"] = "请选择需要出库的货品";
            return $error;
        }
        $postArray = explode(',', $postArray[0]["id"]);
        if($is_check){
            $info=$this->check_product_status($postArray,2,$warehouse_id);
            if($info['status']!==1){
                return $info;
            }else{
                $product_codes=$info["product_codes"];
            }
            $repeat_info=$this->check_product_repeat($postArray,$product_codes);
            if($repeat_info['status']!==1){
                return $repeat_info;
            }
        }
        $a_data = $this->get_insert_data($order,$orderid,$warehouse_id,$comment,$trance_time);
        $outbound_id =$this->insert($a_data);
        $ad_data=array();//出库明细
        $product_ids=array();//更新货品
        foreach ($postArray as $key => $val) {
            $ad_data[$key]["p_id"] = $val;
            $ad_data[$key]["outbound_id"] = $outbound_id;
            $ad_data[$key]["deleted"] = 0;
            $product_ids[]= $val;
        }
        // 添加出库单明细
        $BOutboundOrderDetail=M("BOutboundDetail")->addAll($ad_data);
        //添加金料明细
        $info = D('BRecoveryProduct')->product_check($recovery_products);
        if($info['status'] == 0){
            return $info;
        }
        $recovery_detail=true;
        if($recovery_products != '' && count($recovery_products) > 0) {
            $recovery_detail = D('BRecoveryProduct')->add_product($recovery_products,$outbound_id,5,$warehouse_id);
        }
        // 添加出库单明细,修改货品状态为冻结
        if($order['add_type']==0){
            $condition=array("id"=>array("in",$product_ids));
            $BProduct=D("BProduct")->update($condition,array("status"=>7));
        }else{
            $BProduct=true;
        }
        //销售单创建记录或保存记录
        $create_record=D('BBillOpRecord')->addRecord(BBillOpRecordModel::OUTBOUND,$outbound_id,self::CREATE);//创建
        if($create_record){
            $record_status=$a_data['status']==0?self::COMMIT:self::SAVE;
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::OUTBOUND,$outbound_id,$record_status);//保存或提交
        }
        if ($recovery_detail&&$record_result&&$outbound_id!==false&&$BProduct!==false&&$BOutboundOrderDetail!==false) {
            $result["status"] = 1;
            $result["msg"] = "保存成功！";
            return $result;
        } else {
            $result["status"] = 0;
            $result["msg"] = "保存失败！";
            $result["test"] = $outbound_id."//".$BProduct."//".$BOutboundOrderDetail.'//'.$recovery_detail;
            return $result;
        }

    }

    /**
     * 获取出库单需要添加的数据
     * @param $order  出库单数据
     * @param $orderid  批次
     * @param $warehouse_id 出库仓库
     * @param $comment    评论
     * @param $trance_time 出库时间
     * @return mixed
     */
    function get_insert_data($order,$orderid,$warehouse_id,$comment,$trance_time){
        $a_data["company_id"] = get_company_id();
        $a_data["warehouse_id"] = $warehouse_id;
        $a_data["client_id"] = empty($order['client_id'])?0:$order['client_id'];//客户id
        $a_data["memo"] = $comment;
        $a_data["batch"] = $orderid;
        $a_data["type"] = $order["outbound_type"];
        $a_data["create_time"] = time();
        $a_data["outbound_time"] = strtotime($trance_time);
        $a_data["user_id"] = get_user_id();
        $a_data["status"] = $order['add_type'];//保存还是提交 -1，0
        $a_data["deleted"] = 0;
        return $a_data;
    }
    // 编辑出库单
    /**
     * @param $postdata
     * @param bool $is_check 是否检测货品是否在库或是否存在
     * @return mixed
     */
    public function edit_outbound_order($postdata,$is_check=true) {
        $postArray = $postdata["order_detail"];//需要添加的出库明细的货品id
        $order = $postdata["order"];//出库单信息
        $recovery_products = $postdata["recovery_products"];//金料货品
        $warehouse_id=$order["mystore"];
        $comment = $order["comment"];//备注
        $trance_time =  empty($order["trance_time"])?date("Y-m-d H:i:s",time()):$order["trance_time"];//出库时间
        $product_ids=$order['productids'];//该单下的所有货品id 用于更新货品状态
        $out_info=$this->getInfo(array('company_id'=>get_company_id(),'id'=>$order['id']));//获取出库单
        if(empty($out_info)){
            $result["status"] = 0;
            $result["msg"] = "出库单不存在！";
            return $result;
        }
        if (empty($product_ids)) {
            $error["status"] = 0;
            $error["msg"] = "请选择需要出库的货品";
            return $error;
        }
        //检测新增的出库货品是否在库
        if($is_check){
            $productids=explode(',', $product_ids);
            $info=$this->check_product_status($productids,2,$warehouse_id);
            if($info['status']!==1){
                return $info;
            }else{
                $product_codes=$info["product_codes"];
            }
            $repeat_info=$this->check_product_repeat($productids,$product_codes);
            if($repeat_info['status']!==1){
                return $repeat_info;
            }
           /* $productids=explode(',', $product_ids);
            $info=$this->check_out_product($productids);//检测货品是否在库，是否重复
            if($info['status']!==1){
                return $info;
            }*/
        }
        //添加出库明细
        $postArray = array_filter(explode(',', $postArray[0]["id"]));
        if(!empty($postArray)){
            $BOutboundOrderDetail=$this->add_out_detail($postArray,$out_info['id']);//添加出库明细
        }else{
            $BOutboundOrderDetail=true;
        }
        //更新出库单信息
        $a_data=$this->get_insert_data($order,$out_info['batch'],$warehouse_id,$comment,$trance_time);
        $outbound_id =$this->update(array('id'=>$out_info['id']),$a_data);//更新出库单
        //更新金料明细
        if($order['del_recovery_detail']){
            $del_products = D('BRecoveryProduct')
                ->update(array('id'=>array('in',$order['del_recovery_detail'])),array('deleted'=>1));
        }else{
            $del_products=true;
        }
        $info = D('BRecoveryProduct')->product_check($recovery_products);
        if($info['status'] == 0){
            return $info;
        }
        $recovery_detail = D('BRecoveryProduct')->update_product($recovery_products,$out_info['id'],5,$warehouse_id);
        // 删除出库明细
        $del_out_detail=true;
        if(!empty($order['del_out_detail'])){
            $del_out_detail=D("BOutboundDetail")->update(array('id'=>array('in',$order['del_out_detail'])),array('deleted'=>1));
        }
        // 提交出库单,修改货品状态为冻结
        if($order['add_type']==0){
            $condition=array("id"=>array("in",$product_ids));
            $BProduct=D("BProduct")->update($condition,array("status"=>7));
        }else{
            $BProduct=true;
        }
        //销售编辑的保存或提交的操作记录
        $record_status=$a_data['status']==0?self::COMMIT:self::SAVE;
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::OUTBOUND,$order['id'],$record_status);//保存或提交
        if ($del_products!==false&&$recovery_detail!==false&&$record_result&&$outbound_id!==false&&$BProduct!==false&&$BOutboundOrderDetail!==false&&$del_out_detail!==false) {
            $result["status"] = 1;
            $result["msg"] = "保存成功！";
            return $result;
        } else {
            $result["status"] = 0;
            $result["msg"] = "保存失败！";
            $result["test"] = $outbound_id."//".$BProduct."//".$BOutboundOrderDetail.'//'.$del_out_detail;
            return $result;
        }

    }
    //添加出库明细
    /**
     * @param $postArray   出库货品id数组
     * @param $outbound_id 出库单id
     * @return bool|string
     */
    function add_out_detail($postArray,$outbound_id){
        $ad_data=array();//出库明细
        foreach ($postArray as $key => $val) {
            if(!empty($val)){
                $ad_data[$key]["p_id"] = $val;
                $ad_data[$key]["outbound_id"] = $outbound_id;
                $ad_data[$key]["company_id"] =get_company_id();
                $ad_data[$key]["deleted"] = 0;
            }
        }
        // 添加出库单明细
        $BOutboundOrderDetail=D("BOutboundDetail")->addAll($ad_data);
        return $BOutboundOrderDetail;
    }
    //检测货品是否在库
    /**
     * @param $postArray 出库货品id 数组
     * @return mixed
     */
    function check_out_product($postArray){
        $info=$this->check_product_status($postArray);
        if($info['status']!==1){
            return $info;//$info['status']!==1 存在非在库货品
        }else{
            $product_codes=$info["product_codes"];
        }
        $repeat_info=$this->check_product_repeat($postArray,$product_codes);
        return $repeat_info;//$repeat_info['status']!==1 存在重复货品
    }
    //出库开单
    public function add_post(){
        $postdata=I("post.");
        $postdata["status"]=7;//用于更新货品状态 7损坏出库中
        if($postdata['order']['outbound_type']==3){
            $postdata["status"]=0;//用于更新货品状态 0采购退货中
        }
        M()->startTrans();
        $info=$this->add_outbound_order($postdata);
        if ($info["status"]==1) {
            M()->commit();
            S('session_business_menu' . get_user_id(), null);
            return $info;
        } else {
            M()->rollback();
           return $info;
        }
    }
    //出库单编辑
    public function edit_post(){
        $postdata=I("post.");
        $postdata["status"]=7;//用于更新货品状态 7损坏出库中
        if($postdata['outbound_type']==3){
            $postdata["status"]=0;//用于更新货品状态 0采购退货中
        }
        M()->startTrans();
        $info=$this->edit_outbound_order($postdata);
        if ($info["status"]==1) {
            M()->commit();
            S('session_business_menu' . get_user_id(), null);
            return $info;
        } else {
            M()->rollback();
            return $info;
        }
    }

    //判断出库数据是否已经是在库状态
    public function check_product_status($postArray,$status=2,$warehouse_id='empty'){
        $i=1;
        foreach ($postArray as $k => $v) {
            $productmap2["id"] = $v;
            $productmap2["status"] = $status;
            $allotproduct = M("BProduct")->where($productmap2)->find();
            $warehouse_id=$warehouse_id=='empty'?$allotproduct['warehouse_id']:$warehouse_id;
            if (empty($allotproduct)||$warehouse_id!=$allotproduct['warehouse_id']) {

                $info["status"] = 0;
                $info["msg"] = "第".$i."行为非在库的货品";
//                 $info["msg"] .= M()->getLastSql();
                return $info;
                // $this->ajaxReturn($info);
            }
            $i++;
            $product_codes[] = $v;
        }

        $info['status'] = 1;
        $info['msg'] .= '无重复!';
        $info['product_codes']=$product_codes;
        return $info;
        /*foreach ($postArray as $k => $v) {
            $productmap2["id"] = $v;
            $productmap2["status"] = $status;
            $allotproduct = M("BProduct")->where($productmap2)->count();
            if (empty($allotproduct)) {
                $info["status"] = 0;
                $info["msg"] = "存在非在库货品";
                return $info;
                //$this->ajaxReturn($info);
            }
            $product_codes[] = $v;
        }
        $info['status'] = 1;
        $info['msg'] .= '无重复!';
        $info['product_codes']=$product_codes;
        return $info;*/
    }
    //判断出库数据是否重复
    public function check_product_repeat($postArray,$product_codes){
        foreach ($postArray as $key => $val) {
            $product_code = $product_codes;
            unset($product_code[$key]);
            $repeat_key = array_search($val, $product_code); // print_r($repeat_key." ".$key.",,");
            $inarray = in_array($val, $product_code);
            if ($repeat_key != false || $inarray) {
                $info['status'] = '0';
                $info['msg'] .= '第' . ++ $key . '行数据货品编码与第' . ++ $repeat_key . '行数据货品编码重复!';
                return $info;
            }
        }
        $info['status'] = 1;
        $info['msg'] .= '无重复!';
        return $info;
    }
    //获取一条出库的所有信息
    public function getInfo_detail(){
        $condition=array("boutbound.company_id"=>get_company_id(),"boutbound.deleted"=>0,"boutbound.id"=>I("get.id",0,"intval"));
       /* $join=" left join ".DB_PRE."m_users create_musers on create_musers.id=boutbound.user_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=boutbound.check_id";*/
        $join=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=boutbound.user_id and create_musers.deleted=0 and create_musers.company_id=boutbound.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=boutbound.check_id  and check_musers.deleted=0 and check_musers.company_id=boutbound.company_id";
        $join.=" left join ".DB_PRE."b_client b_client on b_client.id=boutbound.client_id";
        $field="boutbound.*,create_musers.employee_name user_nicename,check_musers.employee_name check_name,b_client.client_name,b_client.client_moblie,b_client.client_name";
        $BOutboundOrder_data=$this->alias("boutbound")->getInfo($condition,$field,$join,$order='boutbound.id desc');
        return $BOutboundOrder_data;
    }
    //获取出库明细的货品id
    public function get_pruductids($outbound_id){
        $condition=array("boutbound.id"=>$outbound_id,"boutbound.company_id"=>get_company_id(),"boutbound.deleted"=>0,"boutbound.deleted"=>0);
        $join="left join ".DB_PRE."b_outbound_detail boutbounddetail on boutbound.id=boutbounddetail.outbound_id";
        $productid =$this->alias("boutbound")->where($condition)->join($join)->getField("p_id", true);
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
        $condition ["table"] = DB_PRE.'b_outbound_order';
        $condition ["field"] = 'status';
        $status_list = D ( 'b_status' )->getFieldValue($condition);
        $join=" left join ".DB_PRE."m_users create_musers on create_musers.id=boutbound.user_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=boutbound.check_id";
        $field="boutbound.*,create_musers.user_nicename,check_musers.user_nicename check_name";
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $data=$this->alias("boutbound")->getList($excel_where,$field,$limit,$join,"boutbound.id desc");
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['batch'] = $v['batch'];
                $expotdata[$k]['count'] =D('BOutboundDetail')->countList($condition=array("deleted"=>0,"outbound_id"=>$v["id"]));
                $expotdata[$k]['type'] = $v['type']==1?'销售':'损坏';
                $expotdata[$k]['user_nicename'] = $v['user_nicename'];
                $expotdata[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $expotdata[$k]['check_name'] = $v['check_name'];
                $expotdata[$k]['check_time'] = empty($v['check_time'])?'':date('Y-m-d H:i:s',$v['check_time']);
                $expotdata[$k]['status'] = $status_list[$v['status']];
            }
            register_shutdown_function(array(&$this, $action),$excel_where,$excel_field, $excel_join, $page + 1);
            $title=array('序','单号','数量','类型','制单人','制单时间','审核人','审核时间','状态');
            exportexcel($expotdata,$title,"出库记录列表");
        }
    }
    /**
     * @author chenzy
     * @param int $out_id 出库单id
     * @return 操作记录列表
     */
    public function getOperateRecord($out_id){
        $condition=array(
            'operate.company_id'=>get_company_id(),
            'operate.type'=>BBillOpRecordModel::OUTBOUND,
            'operate.sn_id'=>$out_id,
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
     * @author chenzy
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
            self::CHECK_REJECT=>self::CHECK_REJECT_NAME
        );
    }
    /**
     * @author chenzy
     * 获取流程数组
     */
    public function getProcess($out_id){
        $process_list=$this->_groupProcess();
        if(!empty($out_id)){
            $order_info=$this->getInfo_detail();
            $process_list[self::CREATE_PROKEY]['is_done']=1;
            $process_list[self::CREATE_PROKEY]['user_name']=$order_info['user_nicename'];
            $process_list[self::CREATE_PROKEY]['time']=$order_info['create_time'];
            /*检查是否审核*/
            if($order_info['check_id']>0&&($order_info['status']==1||$order_info['status']==2)){
                $process_list[self::CHECK_PROKEY]['is_done']=1;
                $process_list[self::CHECK_PROKEY]['user_name']=$order_info['check_name'];
                $process_list[self::CHECK_PROKEY]['time']=$order_info['check_time'];
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
     * @author chenzy
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
