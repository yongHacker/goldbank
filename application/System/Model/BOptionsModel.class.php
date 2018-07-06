<?php
namespace System\Model;

// use Business\Model\BCommonModel;
use System\Model\ACommonModel;

class BOptionsModel extends ACommonModel{
    
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('option_name', 'require', '键名不能为空！', 1, 'regex', ACommonModel:: MODEL_INSERT  ),
        array('option_value', 'require', '键值不能为空！', 1, 'regex', ACommonModel:: MODEL_INSERT ),
        array('option_name', 'require', '键名不能为空！', 1, 'regex', ACommonModel:: MODEL_UPDATE  ),
        array('option_value', 'require', '键值不能为空！', 1, 'regex', ACommonModel:: MODEL_UPDATE ),
    );

    public function __construct() {
        parent::__construct();
    }
}
