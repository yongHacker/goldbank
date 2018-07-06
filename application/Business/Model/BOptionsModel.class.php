<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BOptionsModel extends BCommonModel{
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('option_name', 'require', '键名不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('option_value', 'require', '键值不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT ),
        array('option_name', 'require', '键名不能为空！', 1, 'regex', BCommonModel:: MODEL_UPDATE  ),
        array('option_value', 'require', '键值不能为空！', 1, 'regex', BCommonModel:: MODEL_UPDATE ),
    );
    public function __construct() {
        parent::__construct();
    }
    /**
     * 初始化商户数据
     * @author lzy 2018-3-23
     * @return boolean 是否成功
     */
    public function initializeCompanyData(){
        $this->startTrans();
        $sql='select information_schema.tables.table_name from information_schema.tables 
            join information_schema.columns on information_schema.columns.table_schema=information_schema.tables.table_schema and information_schema.columns.table_name=information_schema.tables.table_name
            where information_schema.tables.table_schema="'.C('DB_NAME').'" and information_schema.tables.table_type="base table" and information_schema.tables.table_name like "'.C('DB_PREFIX').'b%" and information_schema.columns.column_name = "company_id"';
        $list=$this->db->query($sql);
        $result=true;
        foreach($list as $key => $val){
            if($result!==false&&!in_array($val['table_name'], C('INIT_IGNORE_TABLE'))){
//                     $sql='delete from '.$val['table_name'].' where company_id='.get_company_id();
//                     $result=$this->db->execute($sql);
                    $table=str_replace('gb_', '', $val['table_name']);
                    $result=D($table)->where(array('company_id'=>get_company_id()))->delete();
            }
        }
        if($result!==false){
            $result=D('BWarehouse')->_CreateDefaultWH();
        }
        $company_info=D('BCompany')->getInfo(array('company_id'=>get_company_id()));
        if($result!==false){
            $employee_insert=array(
                'company_id'=>get_company_id(),
                'shop_id'=>0,
                'sector_id'=>0,
                'job_id'=>0,
                'employee_name'=>$company_info['company_name'],
                'user_id'=>get_user_id(),
                'status'=>1,
                'deleted'=>0,
                'creator_id'=>1,
                'create_time'=>time(),
                'updater_id'=>1,
                'update_time'=>time()
            );
            $result=D('BEmployee')->insert($employee_insert);
        }
        if($result){
            $role_info=D('BRole')->where(array('company_id'=>get_company_id()))->order('id asc')->find();
            $ru_insert=array(
                'role_id'=>$role_info['id'],
                'user_id'=>get_user_id(),
                'company_id'=>get_company_id()
            );
            $result=D('b_role_user')->insert($ru_insert);
        }
        if($result){
            $this->commit();
            return true;
        }else{
            $this->rollback();
            return false;        
        }
    }
    //获取回购金价,截金金价，当前金价设置
    function get_recover_setting(){
        $option_name="recovery_gold_price";
        $where['option_name']=$option_name;
        $where["company_id"]=get_company_id();
        $where["deleted"]=0;
        $info=$this->getInfo($where);
        return $info;
    }
    //获取当前金价
    function get_current_gold_price(){
        $info=D("BOptions")->get_recover_setting();
        if($info){
            $price=json_decode($info["option_value"],true);
            $gold_price=D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);
        }else{
            $gold_price=0.00;
        }
        return $gold_price;
    }
}
