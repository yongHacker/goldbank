<?php
namespace Shop\Model;

use Shop\Model\BCommonModel;

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
            if($result==true){
                    $sql='delete from '.$val['table_name'].' where company_id='.get_company_id();
                    $result=$this->db->execute($sql);
            }
        }
        if($result!==false){
            $result=D('BWarehouse')->_CreateDefaultWH();
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
        $option_name="recovery_gold_price_".get_shop_id();
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
            $gold_price=D("BBankGold")->get_price_by_bg_id($price['bg_id']);
        }else{
            $gold_price=0.00;
        }
        return $gold_price;
    }
}
