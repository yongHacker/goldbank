<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class AOptionsModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }
    //获得金价接口开关的数据
    public function getGoldRelationCatid(){
        $condition=array(
            'option_name'=>'gold_relation_catid'
        );
        $gold_relation_cate=$this->getInfo($condition);
        if(empty($gold_relation_cate)){
            $insert=array(
                'option_name'=>'gold_relation_catid',
                'option_value'=>'0'
            );
            $this->insert($insert);
            $gold_relation_cate=$this->getInfo($condition);
        }
        return $gold_relation_cate;
    }
}
