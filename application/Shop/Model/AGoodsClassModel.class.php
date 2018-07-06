<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class AGoodsClassModel extends BCommonModel{
    // 获取商品大类类型
    public function _get_class_status()
    {

        $condition['table'] = C("DB_PREFIX") . 'a_goods_class';
        $condition['field'] = 'type';
        $value_condition['status'] = 0;
        $status_list =D('AStatus')->getStatusInfo($condition, $value_condition);
        foreach ($status_list as $key => $value) {
            $class_status[$value['value']] = $value['comment'];
        }
        return $class_status;
    }
}
