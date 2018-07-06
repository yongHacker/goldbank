<?php

/* * 
 * 公共模型
 */
namespace System\Model;
use System\Model\CommonModel;

class ACommonModel extends CommonModel {
    protected $model_operate;
    public function __construct() {
        parent::__construct();
        $this->model_operate=D('AOperateLog');
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
    public function getList($condition,$field='*',$limit=null,$join='',$order='',$group=''){
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
    public function countList($condition,$field='*',$join='',$order='',$group=''){
        return $this->join($join)->where($condition)->field($field)->order($order)->count();
    }
    /**
     * 获取一条数据信息
     * @param mixed $condition 检索条件
     * @param string $field 检索字段
     * @param string $join 链表
     * @return array
     */
    public function getInfo($condition,$field='*',$join=""){
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
    /*** @author lzy 2017-12-7
     * 修改数据之前插入操作记录
     */
    protected function _before_update(&$update,$condition){
        $table_name=$this->getTableName();
        $list=$this->where($condition['where'])->select();
        $result=$this->model_operate->do_adminlog($table_name,$list,$condition['where'],$update,parent::MODEL_UPDATE);
        return $result;
    }
    /*** @author lzy 2017-12-7
     * 没有修改成功改变操作记录状态
     * @see \Think\Model::_delupdate_write()
     */
    protected function _delupdate_write($ids){
        @$this->model_operate->update_log_status($ids);
    }
    /*** @author lzy 2017-12-7
     * 新增数据之前插入操作记录
     * @see \Think\Model::_before_insert()
     */
    protected function _before_insert(&$insert, $options){
        $table_name=$this->getTableName();
        $result= $this->model_operate->do_adminlog($table_name,array(),array(),$insert,parent::MODEL_INSERT);
        return $result;
    }
    /*** @author lzy 2017-12-7
     * 没有插入成功改变操作记录状态
     * @param string $ids
     */
    protected function _delinsert_write($ids){
        @$this->model_operate->update_log_status($ids);
    }
    /**
     * * @author lzy 2017-12-7
     * 删除之前的插入操作
     * @see \Think\Model::_before_delete()
     */
    protected function _before_delete($options) {
        $table_name=$this->getTableName();
        $list=$this->where($options['where'])->select();
        $result= $this->model_operate->do_adminlog($table_name,$list,array(),array(),parent::MODEL_DEL);
        return $result;
    }
    /**
     * * @author lzy 2017-12-7
     * 删除失败改变操作记录状态
     * @see \Think\Model::_deldelete_write()
     */
    protected function _deldelete_write($ids){
        @$this->model_operate->update_log_status($ids);
    }
    /**
     * * @author lzy 2017-12-7 15:00
     * @example
     * D('user')->field('id as user_id,create_time')->selectAdd('user_id,create_time','gp_user_pay');
     * 批量查询插入前的回调函数
     * @see \Think\Model::_before_selectAdd()
     */
    protected function _before_selectAdd($fields='',$table='',$options=array()){
        $sql=$this->db->buildSelectSql($options);
        $list=$this->db->query($sql);
        $result=true;
        $ids='0';
        foreach($list as $key => $val){
            if($result){
                $result= $this->model_operate->do_adminlog($table,array(),array(),$val,parent::MODEL_INSERT);
                $ids.=','.$result;
            }
        }
        if($result){
            return $ids;
        }else{
            return $result;
        }
    }
    /**
     * 批量查询插入失败后的回调函数
     * @see \Think\Model::_delselectAll_write()
     */
    protected function _delselectAll_write($ids){
        @$this->model_operate->update_log_status($ids);
    }
    /**
     * @author lzy 2017-12-7 15:00
     * @example
     * $condition=array(
     *     '0'=>array(
     *         'user_id' =>'1',
     *         'create_time' =>'1512092120'
     *     ),
     *     '1'=>array(
     *         'user_id' =>'2',
     *         'create_time' =>'1512092120'
     *     ),
     * );
     * D('user_pay')->addAll($condition);
     * 批量插入前的回调函数
     * @see \Think\Model::_before_addAll()
     */
    protected function _before_addAll($data,$options){
        $table_name=$this->getTableName();
        $list=$data;
        $result=true;
        $ids='0';
        foreach($list as $key => $val){
            if($result){
                $result= $this->model_operate->do_adminlog($table_name,array(),array(),$val,parent::MODEL_INSERT);
                $ids.=','.$result;
            }
        }
        if($result){
            return $ids;
        }else{
            return $result;
        }
    }
    /**
     * 批量插入失败后的回调函数
     * @see \Think\Model::_deladdAll_write()
     */
    protected function _deladdAll_write($ids){
        @$this->model_operate->update_log_status($ids);
    }
}

