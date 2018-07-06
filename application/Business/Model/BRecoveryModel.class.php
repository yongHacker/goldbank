<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BRecoveryModel extends BCommonModel{
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
    const CHECK_FUNCTION     =  'business/brecovery/check_post';

    // 添加旧金回购
    public function add_post(){
        $post_data = I("");
        $recovery_order = $post_data['order'];// 回购单基本信息
        $recovery_products = $post_data['product_list'];// 回购货品列表数组
        $info = D('Business/BRecoveryProduct')->product_check($recovery_products);
        if($info['status'] == 0){
            return $info;
        }
        $condition = array('id'=>$recovery_order['buyer_id'],"company_id"=>get_company_id());
        $BClient = D("BClient")->getInfo($condition,"user_id");
        $recovery_order["user_id"] = $BClient["user_id"];
        $order_data = $this->get_insert_data($recovery_order);
        if($order_data==false){
            $result['status']=0;
            $result['msg']=$this->getError();
            return $result;
        }
        M()->startTrans();
        $recovery_id=D("Brecovery")->insert($order_data);
        if($recovery_id){
            $products = D('BRecoveryProduct')->add_product($recovery_products,$recovery_id,0,$recovery_order['wh_id']);
        }
        //回购单创建记录或保存记录
        $create_record=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RECOVERY,$recovery_id,self::CREATE);//创建
        if($create_record){
            $record_status=$order_data['status']==0?self::COMMIT:self::SAVE;
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RECOVERY,$recovery_id,$record_status);//保存或提交
        }
        if($products&&$record_result){
            M()->commit();
            $result['status']=1;
            $result['url']=U("BRecovery/index");
            $result['msg']='添加成功';
        }else{
            M()->rollback();
            $result['status']=0;
            $result['msg']='添加失败';
        }
        return $result;
    }
    // 修改旧金回购
    public function edit_post(){
        $post_data = I("");
        $recovery_order = $post_data['order'];// 回购单基本信息
        $recovery_products = $post_data['product_list'];// 回购货品列表数组
        $condition=array('id'=>$recovery_order['recovery_id'],"company_id"=>get_company_id()
        ,'creator_id'=>get_user_id(),'status'=>array('in','-1,-2,3'));//保存,驳回和撤销状态才可以修改
        $recovery_info=D("Brecovery")->getInfo($condition);
        if(empty($recovery_info)){
            $result['status']=0;
            $result['msg']='无法编辑该单！';
            return $result;
        }
        $info = D('Business/BRecoveryProduct')->product_check($recovery_products);
        if($info['status'] == 0){
            return $info;
        }
        $condition = array('id'=>$recovery_order['buyer_id'],"company_id"=>get_company_id());
        $BClient = D("BClient")->getInfo($condition,"user_id");
        $recovery_order["user_id"] = $BClient["user_id"];
        $order_data = $this->get_insert_data($recovery_order);
        if($order_data==false){
            $result['status']=0;
            $result['msg']=$this->getError();
            return $result;
        }
        M()->startTrans();
        $condition=array('id'=>$recovery_order['recovery_id'],"company_id"=>get_company_id());
        //更新金料回购单信息
        $recovery=D("Brecovery")->update($condition,$order_data);
        //删除和更新金料明细
        if($recovery!==false){
            if($recovery_order['remove_recovery_product_id']){
                $del_products = D('BRecoveryProduct')
                    ->update(array('id'=>array('in',$recovery_order['remove_recovery_product_id'])),array('deleted'=>1));
            }else{
                $del_products=true;
            }
            $products = D('BRecoveryProduct')->update_product($recovery_products,$recovery_order['recovery_id'],0,$recovery_order['wh_id']);
        }
        //回购编辑的保存或提交的操作记录
        $record_status=$order_data['status']==0?self::COMMIT:self::SAVE;
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::RECOVERY,$recovery_order['recovery_id'],$record_status);//保存或提交
        if($record_result&&$products&&$del_products){
            M()->commit();
            $result['status']=1;
            $result['url']=U("BRecovery/index");
            $result['msg']='成功';
        }else{
            M()->rollback();
            $result['status']=0;
            $result['msg']='编辑失败';
            $result['test']=$record_result.'|'.$products.'|'.$del_products;
        }
        return $result;
    }
    public function get_insert_data($recovery_order){
        $data=array();
        $data['company_id']=get_company_id();
        $data['shop_id']=empty($recovery_order['shop_id'])?get_shop_id():$recovery_order['shop_id'];
        $data['wh_id']=$recovery_order['wh_id'];
        $data['num']=$recovery_order['num'];
        $data['batch']=b_order_number("b_recovery","batch");
        $data['price']=$recovery_order['price'];
        $data['recovery_time']=empty($recovery_order['recovery_time'])?time():strtotime($recovery_order['recovery_time']);

        $data['f_uid']=$recovery_order['user_id'];
        $data['client_id']=$recovery_order['buyer_id'];
        $data['name']=$recovery_order['name'];
        $data['id_no']=$recovery_order['id_no'];
        $data['creator_id']=get_user_id();
        $data['create_time']=time();
        $data['memo']=$recovery_order['remark'];
        $data['status']=empty($recovery_order['status'])?0:$recovery_order['status'];
        return $data;
    }

    //添加截金明细
    public function add_cut_product($recovery_products,$order_id){
        $products = array();
        $gold_price=D("BOptions")->get_current_gold_price();
        // $orders = b_order_number("b_recovery_product","rproduct_code", count($recovery_products));
        // if(count($recovery_products) == 1){
        //     $orders = array($orders);
        // }

        $i=0;
        foreach($recovery_products as $k=>$v){

            $where = array(
                'p.id'=> $v['product_id']
            );
            $field = 'p.*, g.goods_name';
            $join = ' LEFT JOIN '.DB_PRE.'b_goods as g ON (g.id = p.goods_id)';
            $product_info = D('Business/BProduct')->alias('p')->getInfo($where, $field, $join);

            $data = array();
            $data['order_id']=$order_id;
            $data['company_id']=get_company_id();
            $data['recovery_name'] = $product_info['goods_name'] . '-截金';
            $data['recovery_price']=$v['cut_gold_price'];
            // $data['rproduct_code']=$orders[$i];
            $data['rproduct_code']= $v['rproduct_code'];
            $data['gold_price'] = $gold_price;
            $data['purity'] = isset($v['purity'])?$v['purity']:1;
            $data['service_fee']=$v['service_fee'];
            $data['total_weight']=$v['gold_weight'];
            $data['gold_weight']=bcmul($v['gold_weight'],$data['purity'],2);
            $data['cost_price']=$v['cost_price'];
            $data['product_id']=$v['product_id'];
            $data['type'] = 1;

            $products[]=$data;
            $i++;
        }
        $result=D("BRecoveryProduct")->insertAll($products);
        return $result;
    }

    //获取一条回购的所有信息
    public function getInfo_detail(){
        $condition=array("brecovery.company_id"=>get_company_id(),"brecovery.deleted"=>0,"brecovery.id"=>I("get.id",0,"intval"));
        /*$join=" left join ".DB_PRE."m_users create_musers on create_musers.id=brecovery.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=brecovery.check_id";
        $join.=" left join ".DB_PRE."m_users recovery_musers on recovery_musers.id=brecovery.f_uid";
        $field="brecovery.*";
        $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,recovery_musers.user_nicename recovery_name";*/
        $join=" left join ".DB_PRE."b_employee create_musers on create_musers.user_id=brecovery.creator_id  and create_musers.deleted=0 and create_musers.company_id=brecovery.company_id";
        $join.=" left join ".DB_PRE."b_employee check_musers on check_musers.user_id=brecovery.check_id  and create_musers.deleted=0  and check_musers.company_id=brecovery.company_id";
        $join.=" left join ".DB_PRE."b_client recovery_musers on recovery_musers.id=brecovery.client_id";
        $field="brecovery.*";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,recovery_musers.client_name recovery_name";
        $brecovery_data=$this->alias("brecovery")->getInfo($condition,$field,$join,$order='brecovery.id desc');
        $condition=array('deleted'=>0,'order_id'=>$brecovery_data['id'],'type'=>0);
        $field='sum(total_weight)total_weight,sum(gold_weight)gold_weight';
        $count_info=D('BRecoveryProduct')->getInfo($condition,$field,'');
        $brecovery_data['total_weight']=$count_info['total_weight'];
        $brecovery_data['gold_weight']=$count_info['gold_weight'];
        return $brecovery_data;
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
        $condition ["table"] = DB_PRE.'b_recovery';
        $condition ["field"] = 'status';
        $status_list = D ( 'b_status' )->getFieldValue($condition);
        $join=" left join ".DB_PRE."m_users create_musers on create_musers.id=brecovery.creator_id";
        $join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=brecovery.check_id";
        $join.=" left join ".DB_PRE."m_users recovery_musers on recovery_musers.id=brecovery.f_uid";
        $field="brecovery.*";
        $field.=",create_musers.user_nicename,check_musers.user_nicename check_name,recovery_musers.user_nicename recovery_name";
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $data=$this->alias("brecovery")->getList($excel_where,$field,$limit,$join,$order='brecovery.id desc');
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['batch'] = $v['batch'];
                $expotdata[$k]['user_nicename'] = $v['user_nicename'];
                $expotdata[$k]['recovery_name'] = $v['recovery_name'];
                $expotdata[$k]['price'] = $v['price'];
                $expotdata[$k]['count'] =$v['num'];
                $expotdata[$k]['recovery_time'] = empty($v['recovery_time'])?'':date('Y-m-d H:i:s',$v['recovery_time']);
                $expotdata[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $expotdata[$k]['status'] = $status_list[$v['status']];
            }
            register_shutdown_function(array(&$this, $action),$excel_where,$excel_field, $excel_join, $page + 1);
            $title=array('序','单号','回购员','客户','销售总价','数量','回购时间','制单时间','状态');
            exportexcel($expotdata,$title,"回购记录列表");
        }
    }
    /**
     * @author chenzy
     * @param int $recovery_id 回购单id
     * @return 操作记录列表
     */
    public function getOperateRecord($recovery_id){
        $condition=array(
            'operate.company_id'=>get_company_id(),
            'operate.type'=>BBillOpRecordModel::RECOVERY,
            'operate.sn_id'=>$recovery_id,
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
    public function getProcess($recovery_id){
        $process_list=$this->_groupProcess();
        if(!empty($recovery_id)){
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
