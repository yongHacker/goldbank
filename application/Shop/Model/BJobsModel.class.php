<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BJobsModel extends BCommonModel{
    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('sector_id', 'require', '部门不能为空！', 1, 'regex', BCommonModel:: MODEL_BOTH ),
        array('job_name', 'require', '岗位名称不能为空！', 1, 'regex', BCommonModel:: MODEL_BOTH ),
    );
    //自动完成
    protected $_auto = array(
        //array(填充字段,填充内容,填充条件,附加规则)
    );

}
