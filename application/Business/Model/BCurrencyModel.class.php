<?php

namespace Business\Model;

use Business\Model\BCommonModel;

class BCurrencyModel extends BCommonModel {
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('currency_name', 'require', '货币名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('currency_name',"require","货币名称不能为空", 1, 'regex', BCommonModel:: MODEL_UPDATE ),
        array('unit', 'require', '计算单位不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('unit',"require","计算单位不能为空", 1, 'regex', BCommonModel:: MODEL_UPDATE ),
     /*   array('exchange_rate',"check_rate","汇率需要大于0", 1, 'function', BCommonModel:: MODEL_INSERT ),
        array('exchange_rate',"check_rate","汇率需要大于0", 1, 'function', BCommonModel:: MODEL_UPDATE ),*/
    );
    public function __construct() {
        parent::__construct();
    }
   /* function check_rate($exchange_rate){
        $exchange_rate=I("exchange_rate");
        die("111");
        if($exchange_rate>0){
          return true;
        }else{
            return false;
        }
    }*/
    function is_use($currency_id){
        $BCurrency=$this->getInfo(array('id'=>$currency_id));
        if($BCurrency['is_user']!=1&&!empty($BCurrency)){
            $this->update(array('id'=>$currency_id),array('is_user'=>1));
        }
    }
}