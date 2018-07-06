<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class AGoodsClassModel extends BCommonModel{
    //获取A端分类
    /**
     * @param int $sid 默认选择的id
     * @return string
     */
    public function get_a_goodsclass($sid = 0) {
        $tree = new \Tree();
        $parentid = I("get.aparentid",0,'intval');
        $result = D("AGoodsClass")->getList($condition="deleted=0",$field='*,pid as parentid,class_name as name',$limit=null,$join='',$order='id asc',$group='');
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option type='\$type' value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $Aselect_categorys = $tree->get_tree(0, $str,$sid);
        return $Aselect_categorys;
    }

    /**
     * 获取系统货品分类下商品顶级分类
     * @param  integer $type 系统货品分类
     */
    public function getTopGoodsClass($type = 0) {
        $condition = array(
            'deleted' => 0,
            'pid' => 0
        );
        if ($type) {
            $condition['type'] = $type;
        }
        $order = 'type asc, id asc';
        $list = $this->getList($condition, $field = '*', $limit = NULL, $join = '', $order);
        return $list;
    }
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
