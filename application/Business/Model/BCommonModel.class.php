<?php

/* * 
 * 公共模型
 */
namespace Business\Model;
use Common\Model\CommonModel;

class BCommonModel extends CommonModel {
    protected $model_operate,$model_access_log,$default_password;
    public function __construct() {
        parent::__construct();
        $this->model_operate=D('BOperateLog');
        $this->default_password=123456;
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
    public function getInfo($condition,$field='*',$join="",$order=""){
        return $this->where($condition)->field($field)->join($join)->order($order)->find();
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
     * 插入多条数据
     * @param array $insert 插入的数据
     * @return Ambigous <mixed, boolean, unknown, string>
     */
    public function insertAll($insert,$options,$replace){
        return $this->addAll($insert,$options,$replace);
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

    /**
     * @param $a 数组
     * @param $pid 父类id
     * @return array //获取树形数组
     */
    function get_attr($a,$pid){
        $tree = array();                                //每次都声明一个新数组用来放子元素
        foreach($a as $v){
            if($v['pid'] == $pid){                      //匹配子记录
                $v['children'] = $this->get_attr($a,$v['id']); //递归获取子记录
                if($v['children'] == null){
                    unset($v['children']);             //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
                }
                $tree[] = $v;                           //将记录存入新数组
            }
        }
        return $tree;                                  //返回新数组
    }

    /**
     * @param $categories 原始数组(必须拥有id和pid)
     * @return array 数组转换为get_attr方法中的的$a数组
     */
    function get_tree_arr($categories){
        /*======================非递归实现========================*/
        $tree = array();
        //第一步，将分类id作为数组key,并创建children单元
        foreach($categories as $category){
            $tree[$category['id']] = $category;
            $tree[$category['id']]['children'] = array();
        }
        //第二步，利用引用，将每个分类添加到父类children数组中，这样一次遍历即可形成树形结构。
        foreach($tree as $key=>$item){
            if($item['pid'] != 0){
                $tree[$item['pid']]['children'][] = &$tree[$key];//注意：此处必须传引用否则结果不对
                if($tree[$key]['children'] == null){
                    unset($tree[$key]['children']); //如果children为空，则删除该children元素（可选）
                }
            }
        }
        ////第三步，删除无用的非根节点数据
        foreach($tree as $key=>$category){
            if($category['pid'] != 0){
                unset($tree[$key]);
            }
        }
        //print_r($tree);
        /*======================递归实现========================*/
        $tree = $categories;
        //echo "<br/><br/><br/>";
        //print_r($this->get_attr($tree,0));
        return $tree;
    }

    /*** @author lzy 2017-12-7
     * 修改数据之前插入操作记录
     */
    protected function _before_update(&$update,$condition){
        $table_name=$this->getTableName();
        $list=$this->where($condition['where'])->select();
        /*每次操作加上company_id add by lzy 2018-3-23 start*/
        $fields=$this->fields;
        unset($fields['_type']);
        unset($fields['_pk']);
        if(in_array('company_id', $fields)&&empty($update['company_id'])){
            $update['company_id'] = get_company_id();
        }
        /*每次操作加上company_id add by lzy 2018-3-23 end*/
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
        /*每次操作加上company_id add by lzy 2018-3-23 start*/
        $fields=$this->fields;
        unset($fields['_type']);
        unset($fields['_pk']);
        if(in_array('company_id', $fields)&&empty($insert['company_id'])){
            $insert['company_id']=get_company_id();
        }
        /*每次操作加上company_id add by lzy 2018-3-23 end*/
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
    /**
     * 获得导出的数据检索结果的列表,必须按id降序排序
     * @param mixed $condition 检索条件
     * @param string $field 检索字段
     * @param string $limit 条数限制
     * @param string $join 链表
     * @param string $order 排序
     * @param string $group
     * @return array
     */
    public function export($condition,$field='*',$limit='0,1000',$join='',$order='id desc',$group=''){
        $result=array();
        $data=$this->join($join)->where($condition)->field($field)->limit($limit)->order($order)->group($group)->select();
        if($data){
            $end_data=end($data);
            $result['data']=$data;
            $result['end_id']=$end_data['id'];
        }
        return $result;
    }

    /**
     * @param $expotdata
     * @param $filter_data
     * @param $title
     * @return array
     */
    public function export_data_filter($expotdata,$filter_data,$title,&$row){
        $result=array();
        $new_data=array();
        foreach($expotdata['data'] as $key=>$value){
            if(is_numeric($row)&&$row>=0){$new_data[$key]['num_id'] = ++$row;}
            foreach($filter_data as $k=>$v){
                if(isset($expotdata['data'][$key][$k])||array_key_exists($k,$value)){
                    $varStr=empty($v)?$value[$k]:$value[$k].'|'.$v;
                    $new_data[$key][$k]=empty($value[$k])?'':parseVar($varStr);
                    if(strpos($new_data[$key][$k],',')){
                        $new_data[$key][$k]='"'.$new_data[$key][$k].'"';
                    }
                }
            }
        }

        $result['data']=$new_data;
        $result['title']=$title;
        return $result;
    }
    /**递归运行导出函数
     * @param $data
     * @param $action
     * @param $min_id
     * @param $excel_where
     * @param $excel_field
     * @param $excel_join
     */
    public function export_excel($data,$action,$excel_where,$min_id){
        C('SHOW_PAGE_TRACE', false);
        set_time_limit(0);
        register_shutdown_function($action,$excel_where,$min_id);
        exportcsv($data['data'],$data['title'],$data['filename']);
    }
//获取审核条数
    function get_check_count($where,$name,$url,$field='*',$join='',$order='',$group=''){
        $condition=array("company_id"=>get_company_id(),"deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $count=$this->countList($condition,$field='*',$join='',$order='',$group='');
        $result=array('name'=>$name,'url'=>$url,'count'=>$count);
        return $result;
    }
    /**
     * 判断是否存在重复的数据
     * @param $condition
     * @param int $id
     * @return bool   不存在则true 存在false
     */
    public function check_repeat($condition,$id=0){
        if($id>0){
            $condition[id]=array('neq',$id);
        }
        $info=$this->getInfo($condition);
        if(empty($info)){
            return true;
        }
        return false;
    }
}

