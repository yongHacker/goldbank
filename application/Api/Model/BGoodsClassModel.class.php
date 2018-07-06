<?php
namespace Api\Model;
use Api\Model\ApiCommonModel;
class BGoodsClassModel extends ApiCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取B端分类树
     * 
     * @param int $sid 分类树顶端
     * @return string
     */
    public function getGoodsClass($sid = 0, $where = array())
    {
        $parentid = I('get.parentid', 0, 'intval');
        $condition = array(
            'company_id' => 0,
            'deleted' => 0
        );
        if (! empty($where)) {
            $condition = array_merge($condition, $where);
        }
        $result = $this->getList($condition, $field = 'id, agc_id, pid as parentid, class_name as name, photo', $limit = null, $join = '', $order = 'id asc', $group = '');

        $tree = new \Tree();
        $tree->init($result);
        // 获取B分类
        $select_categorys = $tree->get_tree_array2($sid);
        return $select_categorys;
    }
    
    /**
     * 获得指定id的分类下所有的分类集合
     * 
     * @param unknown $class_id
     * @param unknown $class_list
     * @return multitype:unknown |multitype:
     */
    public function getALLGoodsClass($class_id, $company_id, $class_list = array())
    {
        global $class_list;
        if (empty($class_list)) {
            $class_list = array();
        }
        if (empty($class_id)) {
            $class_id = 0;
        }
        $condition = array(
            'deleted' => 0,
            'pid' => $class_id,
            'company_id' => $company_id
        );
        $goods_class = $this->getList($condition);
        if (! empty($goods_class)) {
            foreach ($goods_class as $key => $val) {
                $condition = array(
                    'deleted' => 0,
                    'pid' => $val['id'],
                    'company_id' => $company_id
                );
                $list = $goods_class = $this->getList($condition);
                $class_list[] = $val;
                if (! empty($list)) {
                    $list = $this->getALLGoodsClass($val['id'], $company_id, $class_list);
                }
            }
            return $class_list;
        }
        return array();
    }
}