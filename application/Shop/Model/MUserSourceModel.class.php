<?php
namespace Shop\Model;

use Shop\Model\MCommonModel;

class MUserSourceModel extends MCommonModel
{
    public function __construct() {
        parent::__construct();
    }
    //更新归属
    public function update_belong($user_id){
        $condition['user_id']=$user_id;
        $condition['belong']=array("eq","");
        $m_source=$this->getInfo($condition);
        if($m_source){
            $data['belong']=get_company_id().",".get_shop_id();
            $data['belong_rule_name']=MODULE_NAME."/".CONTROLLER_NAME."/".ACTION_NAME;
            $this->update($condition,$data);
        }
    }
}
