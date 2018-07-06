<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class BOptionsModel extends ApiCommonModel
{
    
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('option_name', 'require', '键名不能为空！', 1, 'regex', ApiCommonModel:: MODEL_INSERT  ),
        array('option_value', 'require', '键值不能为空！', 1, 'regex', ApiCommonModel:: MODEL_INSERT ),
        array('option_name', 'require', '键名不能为空！', 1, 'regex', ApiCommonModel:: MODEL_UPDATE  ),
        array('option_value', 'require', '键值不能为空！', 1, 'regex', ApiCommonModel:: MODEL_UPDATE ),
    );
    
    public function __construct() {
        parent::__construct();
    }
}
