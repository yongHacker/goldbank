<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class AStatusModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }
    public function get_all_field($table){
        $data = $this->query("SHOW COLUMNS FROM $table");
        return $data;
    }
    //获得所有的table
    public function get_table(){
        return $this->list_tables();
    }
    //值是否存在
    public function value_exsit($s_id,$insert){
        $value_info=$this->get_value_info("s_id=".$s_id." and value='".$insert['value']."'");
        if(empty($value_info)){
            return false;
        }else{
            return true;
        }
    }
    //获取status_value记录
    public function get_value_info($condition){
        $result = D("AStatusValue")->where($condition)->find();
        return $result;
    }
    //状态是否存在
    public function status_exsit($insert){
        $table=$insert['table'];
        $field=$insert['field'];
        $status_info=$this->getInfo("`table`='".$table."' and `field`='".$field."'");
        if(empty($status_info)){
            return false;
        }else{
            return $status_info['id'];
        }
    }
    //获取status列表
    public function get_status_list($condition,$limit=null,$order=null){
        $result = $this->where($condition)->order($order)->limit($limit)->select();
        return $result;
    }
    //获取status记录
    public function get_status_info($condition){
        $result = $this->where($condition)->find();
        return $result;
    }
//获取b_status_value列表
    public function get_value_list($condition,$limit=null,$order=null,$field='*',$join=null){
        $result = M('a_status_value')->alias("astatusvalue")->field($field)->join($join)->where($condition)->order($order)->limit($limit)->select();
        return $result;
    }
    //获得一个状态的所有信息;
    public function _getStatusInfo($condition,$value_condition=array('status'=>array('in',"0,1"))){
        $status_info=$this->get_status_info($condition);
        if(!empty($status_info)){
            $status_id=$status_info['id'];
            $value_list=$this->get_value_list(array('s_id'=>$status_id,'status'=>$value_condition["status"]));
            $status_info['value_list']=$value_list;
            $data=array();
            if(!empty($value_list)){
                foreach($value_list as $key=>$val){
                    $data[$val['value']]=$val['comment'];
                }
            }
            $status_info['data']=$data;
        }
        return $status_info;
    }
    //获取多个状态的所有信息
    public function _getStatusList($condition,$limit=null,$order=null,$value_condition=array('status'=>array('in',"0,1"))){
        $status_list=$this->get_status_list($condition,$limit,$order);
        if(!empty($status_list)){
            foreach($status_list as $key => $val){
                $status_id=$val['id'];
                $value_list=$this->get_value_list(array('s_id'=>$status_id,'status'=>$value_condition["status"]));
                $status_list[$key]['value_list']=$value_list;
                $data=array();
                if(!empty($value_list)){
                    foreach($value_list as $key=>$val){
                        $data[$val['value']]=$val['comment'];
                    }
                }
                $status_list[$key]['data']=$data;
            }
        }
        return $status_list;
    }
    //列表形式获取单个status信息
    public function getStatusInfo($condition,$value_condition=array('status'=>array('in',"0,1"))){
        $status_info=$this->get_status_info($condition);
        if(!empty($status_info)){
            $status_id=$status_info['id'];
            $value_list=$this->get_value_list(array('s_id'=>$status_id,'status'=>$value_condition["status"]));
            foreach($value_list as $key=>$val){
                $value_list[$key]=array_merge($val,$status_info);
            }
            $status_info=array();
            $status_info=$value_list;
        }
        return $status_info;
    }
    //获取指定表的指定字段所有值
    public function getFieldValue($condition){
        $status_info=$this->get_status_info($condition);
        $s_id=$status_info['id'];
        $value_list=$this->get_value_list(array('s_id'=>$s_id));
        $list=array();
        foreach($value_list as $key => $val){
            $list[$val['value']]=$val['comment'];
        }
        return $list;
    }
    //获取指定表的指定字段指定条件注释
    public function getFidldComment($condition){
        $condition['status'] = 0;
        $fidld = 'asv.comment';
        $join = 'LEFT JOIN __A_STATUS_VALUE__ asv ON astatus.id = asv.s_id';
        $info = $this->alias('astatus')->getInfo($condition, $fidld, $join);
        return $info['comment'];
    }
}
