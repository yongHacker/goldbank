<?php

/* * 
 * 公共模型
 */
namespace Api\Model;
use System\Model\CommonModel;

class ApiCommonModel extends CommonModel {
    public function __construct() {
        parent::__construct();
    }
    /**
     * 获得数据检索结果的列表
     * @param mixed $condition 检索条件
     * @param string $field 检索字段
     * @param string $limit 条数限制
     * @param string $join 链表
     * @param string $order 排序
     * @param string $group
     * @return array
     */
    public function getList($condition, $field = '*', $limit = null, $join = '', $order = '',$group = ''){
        return $this->join($join)->where($condition)->field($field)->limit($limit)->order($order)->group($group)->select();
    }
    /**
     * 统计数据检索结果的条数
     * @param mixed $condition 检索条件
     * @param string $field 检索字段
     * @param string $join 链表
     * @param string $order 排序
     * @param string $group
     * @return int
     */
    public function countList($condition, $field = '*',$join = '', $order = '', $group = ''){
        return $this->join($join)->where($condition)->field($field)->order($order)->count();
    }
    /**
     * 获取一条数据信息
     * @param mixed $condition 检索条件
     * @param string $field 检索字段
     * @param string $join 链表
     * @return array
     */
    public function getInfo($condition, $field = '*',$join = ""){
        return $this->where($condition)->field($field)->join($join)->find();
    }
    /**
     * 插入一条数据
     * @param array $insert 插入的数据
     * @return Ambigous <mixed, boolean, unknown, string>
     */
    public function insert($insert){
        return $this->add($insert);
    }
    /**
     * 修改数据
     * @param array $condition 检索条件
     * @param array $update 更改的值
     * @return boolean
     */
    public function update($condition, $update){
        return $this->where($condition)->save($update);
    }
}