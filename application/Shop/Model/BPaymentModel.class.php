<?php

namespace Shop\Model;

use Shop\Model\BCommonModel;

class BPaymentModel extends BCommonModel {
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('pay_name', 'require', '收款方式名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('pay_name',"require","收款方式名称不能为空", 1, 'regex', BCommonModel:: MODEL_UPDATE ),
    );
}