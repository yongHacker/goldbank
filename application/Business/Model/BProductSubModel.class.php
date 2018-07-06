<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BProductSubModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }

    /**
     * @param $where
     *  获取每种类型的值
     */
    public function get_prodcut_sub($where){
        $list=$this->where($where)->select();
        $arr=array();
        if(!empty($list)){
            foreach ($list as $k => $v){
                $arr[$v['sub_value']]=$v['sub_note'];
            }
        }
        return $arr;
    }
}
