<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BStatusModel extends BCommonModel{
    //增加status记录
    public function insert($insert){
        $result = $this->add($insert);
        return $result;
    }
    //增加b_status_value记录
    public function insert_value($insert){
        $result = M('b_status_value')->add($insert);
        return $result;
    }
    //获取status记录
    public function get_status_info($condition){
        $result = $this->where($condition)->find();
        return $result;
    }
    //获取b_status_value记录
    public function get_value_info($condition){
        $result = M('b_status_value')->where($condition)->find();
        return $result;
    }
    //获取status列表
    public function get_status_list($condition,$limit=null,$order=null){
        $result = $this->where($condition)->order($order)->limit($limit)->select();
        return $result;
    }
    //获取b_status_value列表
    public function get_value_list($condition,$limit=null,$order=null,$field='*',$join=null){
        $result = M('b_status_value')->alias("bstatusvalue")->field($field)->join($join)->where($condition)->order($order)->limit($limit)->select();
        return $result;
    }
    //获得b_status_value的键值对
    public function get_value_key_list($condition){
        $value_list=M('b_status_value')->where($condition)->select();
        $result=array();
        foreach($value_list as $key => $val){
            $result[$val['value']]=$val['comment'];
        }
        return $result;
    }
    //修改status记录
    public function updateStatus($condition,$update){
        $result = $this->where($condition)->save($update);
        return $result;
    }
    //修改b_status_value记录
    public function updateValue($condition,$update){
        $result=M('b_status_value')->where($condition)->save($update);
        return $result;
    }
    //删除status记录
    public function delete_status($condition){
        $result = $this->where($condition)->delete();
        return $result;
    }
    //删除b_status_value记录
    public function delete_value($condition){
        $result=M('b_status_value')->where($condition)->delete();
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
    //列表形式获取多个status信息
    public function getStatusList($condition,$limit=null,$order=null){
        $field='bstatusvalue.*,bstatus.table,bstatus.field';
        $join=' join '.C('DB_PREFIX').'b_status as bstatus  on bstatusvalue.s_id=bstatus.id';
        $value_list=$this->get_value_list($condition,$limit,$order,$field,$join);
        // var_dump($value_list);
        if(empty($value_list)){
            $value_list=null;
        }
        return $value_list;
    }
    //状态是否存在
    public function status_exsit($insert){
        $table=$insert['table'];
        $field=$insert['field'];
        $status_info=$this->get_status_info("`table`='".$table."' and `field`='".$field."'");
        if(empty($status_info)){
            return false;
        }else{
            return $status_info['id'];
        }
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
    //获得所有的table
    public function get_table(){
        return $this->list_b_tables();
    }
    //获得表中所有字段,带前缀
    public function get_all_field($table){
        $fields = array();
        //$table = C("DB_PREFIX") . $table;
        $data = $this->query("SHOW COLUMNS FROM $table");
        return $data;
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
        $fidld = 'bsv.comment';
        $join = 'LEFT JOIN __B_STATUS_VALUE__ bsv ON bs.id = bsv.s_id';
        $info = $this->alias('bs')->getInfo($condition, $fidld, $join);
        return $info['comment'];
    }

    /**
     * 读取全部b端的 gb_b_ 开头的表名
     */
    final public function list_b_tables() {
        $tables = array();
        $data = $this->query("SHOW TABLES");
        foreach ($data as $k => $v) {
            if(strpos($v['tables_in_' . strtolower(C("DB_NAME"))],C("DB_PREFIX")."b_")!==false){
                $tables[] = $v['tables_in_' . strtolower(C("DB_NAME"))];
            }
        }
        return $tables;
    }
}
