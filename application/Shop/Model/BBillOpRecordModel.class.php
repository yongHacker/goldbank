<?php
/**
 * @author lzy 2018.5.26
 * 表单操作记录
 */
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BBillOpRecordModel extends BCommonModel{
    const PROCURE            =  1;  //采购表单
    const PROCURE_STORAGE    =  2;  //分称表单
    const PROCURE_SETTLE     =  3;  //采购结算表单
    const ALLOT              =  4;  //调拨单
    const SELL               =  5;  //销售单
    const RECOVERY           =  6;  //回购单
    const OUTBOUND           =  7;  //出库单
    const PROCURE_RETURN     =  8;  //采购退货单
    const RPRODUCT_ALLOT     =  9;  //金料调拨单
    const MERGE              =  10; //金料合并单
    const SELL_RETURN        =  11; //销售退货单
    const ADJUST             =  12; //货品调整单
    public function __construct() {
        parent::__construct();
    }
    /**
     * @author lzy 2018.5.26
     * 添加一条表单操作记录
     * @param int $type 操作表单类型
     * @param int $sn_id 操作表单id
     * @param int $operate_type 操作类型
     * @return mixed 成功返回插入id,失败返回false
     */
    public function addRecord($type,$sn_id,$operate_type){
        $insert=array(
            'type'=>$type,
            'sn_id'=>$sn_id,
            'company_id'=>get_company_id(),
            'operate_type'=>$operate_type,
            'operate_id'=>get_user_id(),
            'operate_time'=>time()
        );
        return $this->insert($insert);
    }
    
}
