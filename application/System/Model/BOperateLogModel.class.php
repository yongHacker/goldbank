<?php
namespace System\Model;
use System\Model\CommonModel;
class BOperateLogModel extends CommonModel{
    protected $model_operate_table;
    protected $connection;
    protected $model_user;
    protected $user_id,$company_id;
    protected $table_suf;
    const MUSERS='m_users',BSTATUS='b_status',BSTATUSVALUE='b_status_value';
    public function __construct(){
        $this->model_user=D(self::MUSERS);
        if(I('request.operate_begin_time')){
            $this->table_suf=str_replace("-","",I('operate_begin_time'));
        }
        $this->_initialize();
    }
    public function _initialize(){
        if (!OPEN_LOG) {
            $this->model_operate_table = M('BOperateLog');
            return true;
        }
        $table_suf='_'.date('Ym',time());
        if($this->table_suf){
            $table_suf='_'.$this->table_suf;
        }
        $table='b_operate_log'.$table_suf;
        if(!$this->model_operate_table){
            if(!$this->table_is_exists($table)){
                $sql="CREATE TABLE IF NOT EXISTS ".$table." (
              `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增id',
              `company_id` int(20) NULL COMMENT '商户id',
              `table_name` varchar(255) NOT NULL COMMENT '表',
              `user_id` bigint(20) NOT NULL COMMENT '操作人id',
              `user_name` varchar(255) NOT NULL COMMENT '操作人姓名',
              `user_mobile` varchar(20) DEFAULT NULL COMMENT '操作人联系方式',
              `value_before` LONGTEXT NULL DEFAULT NULL COMMENT '操作前的值',
              `value_after` LONGTEXT NULL DEFAULT NULL COMMENT '操作后的值',
              `change_before` LONGTEXT NULL DEFAULT NULL COMMENT '改变的值在改变之前的值',
              `change_after` LONGTEXT NULL DEFAULT NULL COMMENT '改变的值在改变之后的值',
              `operate_ip` varchar(20) NOT NULL COMMENT '操作ip',
              `operate_time` varchar(12) NOT NULL COMMENT '操作时间',
              `operate_type` TINYINT(3) NOT NULL DEFAULT '1' COMMENT '操作类型 1新增 2修改 3删除',
              `status` tinyint(3) DEFAULT 0 COMMENT '状态 0成功 1失败',
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `company_id` (`company_id`),
              KEY `operate_time` (`operate_time`),
              KEY `user_mobile` (`user_mobile`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作记录表' AUTO_INCREMENT=1 ;";
                M('','goldpay_operate',C('DB_OPERATE_LOG'),1)->execute($sql);
            }
            $this->model_operate_table=M('log'.$table_suf,'b_operate_',C('DB_OPERATE_LOG'),1);
            $this->user_id  = session('SYSTEM_ID')>0?session('SYSTEM_ID'):0;
        }

    }
    public function insertLog($insert){
        if (!OPEN_LOG) {
            return true;
        }
        return $this->model_operate_table->add($insert);
    }
    public function countLogList($condition,$field='*',$join='',$order='',$group=''){
        return $this->model_operate_table->field($field)->where($condition)->join($join)->order($order)->group($group)->count();
    }
    public function getLogList($condition,$field='*',$limit='',$join='',$order='',$group=''){
        return $this->model_operate_table->field($field)->where($condition)->limit($limit)->join($join)->order($order)->group($group)->select();
    }
    public function updateLog($condition,$update){
        if (!OPEN_LOG) {
            return true;
        }
        return $this->model_operate_table->where($condition)->save($update);
    }
    public function getLogInfo($condition,$field='*'){
        return $this->model_operate_table->field($field)->where($condition)->find();
    }
    /**
     * 检查表是否存在
     * $table 不带表前缀
     */
    public function table_is_exists($table) {
        $tables = $this->get_list_tables();
        return in_array(C("DB_PREFIX") . $table, $tables) ? true : false;
    }
    /**
     * 读取全部表名
     */
    public function get_list_tables() {
        $tables = array();
        $data = M('','goldpay_operate',C('DB_OPERATE_LOG'),1)->query("SHOW TABLES");
        foreach ($data as $k => $v) {
            $tables[] = $v['tables_in_' . strtolower(C("DB_NAME"))];
        }
        return $tables;
    }
    /**
     * 添加一条记录
     * @param array $insert
     * @return boolean
     */
    public function _addLog($insert){
        /*if(!in_array($_SERVER['HTTP_HOST'],C('LOG_SERVER'))){
             return true;
         }*/
        $user_id=$this->user_id;
        $user_info=$this->model_user->getInfo(array('id'=>$user_id));
        $operate_ip=get_client_ip();
        $operate_time=time();
        $company_id = $insert['value_company_id']>0?$insert['value_company_id']:0;
        unset($insert['value_company_id']);
        $insert['company_id']=$company_id;
        $insert['user_id']=$user_id;
        $insert['user_name']=empty($user_info)?'系统自动':(!empty($user_info['realname'])?$user_info['realname']:(!empty($user_info['user_nicename'])?$user_info['user_nicename']:(!empty($user_info['user_login'])? $user_info['user_login']:(!empty($user_info['mobile'])?$user_info['mobile']:''))));
        $insert['user_mobile']=$user_info['mobile'];
        $insert['operate_ip']=$operate_ip;
        $insert['operate_time']=$operate_time;
        $insert['deleted']=0;
        $result=$this->insertLog($insert);
        return $result;
    }
    /**
     * 根据传递过来的数据处理操作日志
     * @param string $table_name
     * @param array $list
     * @param array $condition
     * @param array $update
     * @return string|boolean
     */
    public function do_adminlog($table_name,$list,$where,$data,$type){
        // 是否在指定的ip或域名下访问
        if (! in_array($_SERVER['HTTP_HOST'], C('LOG_SERVER'))) {
            return true;
        }
        if($this->is_check_ignore()){
            return true;
        }
        $flag=true;
        $result='0';
        switch ($type){
            case parent::MODEL_INSERT:
                $insert=array(
                    'value_after'=>json_encode($data,true),
                    'table_name'=>$table_name,
                    'operate_type'=>$type,
                    'change_after'=>json_encode($data,true),
                    'user_id'=>$this->user_id,
                );
                $insert['value_company_id']=$data['company_id'];
                $flag=$this->_addLog($insert);
                if($flag>0){
                    $result.=','.$flag;
                }
                break;
            case parent::MODEL_UPDATE:
                $this->model_operate_table->startTrans();
                if(!empty($list)){
                    foreach($list as $key => $val){
                        if($flag){
                            $value_before=$val;
                            $value_after=array_merge($val,$data);
                            $changed=array_udiff_uassoc($value_before,$value_after,"arrcompare_key","arrcompare_value");
                            //没有更改不插入记录
                            if(!empty($changed)){
                                $changed_before=array();
                                $changed_after=array();
                                foreach ($changed as $k => $v){
                                    $changed_before[$k]=$value_before[$k];
                                    $changed_after[$k]=$value_after[$k];
                                }
                                $user_id=$this->user_id;
                                $insert=array(
                                    'value_before'=>json_encode($value_before,true),
                                    'value_after'=>json_encode($value_after,true),
                                    'table_name'=>$table_name,
                                    'operate_type'=>$type,
                                    'change_before'=>json_encode($changed_before,true),
                                    'change_after'=>json_encode($changed_after,true),
                                    'user_id'=>$user_id,
                                );
                                $insert['value_company_id']=$changed_before['company_id']>0?$changed_before['company_id']:$changed_after['company_id'];
                                $flag=$this->_addLog($insert);
                                if($flag>0){
                                    $result.=','.$flag;
                                }
                            }
                        }
                    }
                }
                if($flag){
                    $this->model_operate_table->commit();
                    return $result;
                }else{
                    $this->model_operate_table->rollback();
                    return false;
                }
                break;
            case parent::MODEL_DEL:
                if(!empty($list)){
                    foreach($list as $key => $val){
                        if($flag){
                            $value_before=$val;
                            $changed_before=$val;
                            $insert=array(
                                'value_before'=>json_encode($value_before,true),
                                'table_name'=>$table_name,
                                'operate_type'=>$type,
                                'change_before'=>json_encode($changed_before,true),
                                'user_id'=>$this->user_id
                            );
                            $insert['value_company_id']=$changed_before['company_id'];
                            $flag=$this->_addLog($insert);
                            if($flag>0){
                                $result.=','.$flag;
                            }
                        }
                    }
                }
                break;
            default:
                break;
        }

        if($flag){
            return $result;
        }else{
            return false;
        }
    }
    /**
     * 删除更新失败的数据操作记录
     * @param string $str
     */
    public function update_log_status($str){
        $ids=explode(',', $str);
        if(is_array($ids)&&!empty($ids)){
            foreach ($ids as $key =>$val){
                $this->updateLog(array('id'=>$val), array('status'=>1));
            }
        }
    }

    public function get_table_name($table_name){
        $sql="SELECT table_name,table_comment FROM information_schema.tables  WHERE table_schema = '".C("DB_NAME")."' AND table_name = '".$table_name."'";
        $info=M()->query($sql);
        return $info;
    }

    public function get_tables(){
        $sql="SELECT table_name,table_comment FROM information_schema.tables  WHERE table_schema = '".C("DB_NAME")."'";
        $tables=M()->query($sql);
        foreach ($tables as $k=>$v){
            $tables[$k]['table_name']=str_replace(C('DB_PREFIX'),"",$v['table_name']);
        }
        return $tables;
    }

    public function get_table_note($table_name,$column_name){
        $sql="SELECT * FROM information_schema.columns WHERE table_schema = '".C("DB_NAME")."' AND table_name = '".$table_name."' and  column_name='".$column_name."'"; ;
        $info=M()->query($sql);
        return $info;
    }

    /**
     * 获取操作记录详情
     * @author lzy 2017-12-12
     * @param array $condition
     * @param string $field
     * @return Ambigous <mixed, boolean, NULL, string, unknown, multitype:, object>
     */
    public function getLogDetail($condition,$field='*'){
        $log_info=$this->getLogInfo($condition,$field);
        $table_info=$this->get_table_name($log_info['table_name']);
        $log_info['tablename']=$table_info[0]['table_comment'];
        //数据处理
        $log_info['valuebefore']=$this->_getValueDetail($log_info['table_name'], json_decode($log_info['value_before'],true));//!empty($log_info['value_before'])?json_decode($log_info['value_before'],true):null;
        $log_info['valueafter']=$this->_getValueDetail($log_info['table_name'], json_decode($log_info['value_after'],true));//!empty($log_info['value_after'])?json_decode($log_info['value_after'],true):null;
        $log_info['changebefore']=$this->_getValueDetail($log_info['table_name'], json_decode($log_info['change_before'],true));//!empty($log_info['change_before'])?json_decode($log_info['change_before'],true):null;
        $log_info['changeafter']=$this->_getValueDetail($log_info['table_name'], json_decode($log_info['change_after'],true));//!empty($log_info['change_after'])?json_decode($log_info['change_after'],true):null;
        return $log_info;
    }
    /**
     * 添加操作内容注释
     * @author lzy 2017-12-12
     * @param string $table_name
     * @param array $value_list
     * @return multitype:multitype:unknown Ambigous <NULL, unknown>  |NULL
     */
    public function _getValueDetail($table_name,$value_list){
        if(!empty($table_name)&&!empty($value_list)){
            $result=array();
            foreach ($value_list as $key => $val){
                $res=array();
                $res['field']=$key;
                $res['value']=$val;
                $condition=array(
                    'table_name'=>$table_name,
                    'field'=>$key
                );
                $field_info=D(self::BSTATUS)->getInfo($condition);
                $table_note=$this->get_table_note($table_name,$key);
                $res['field_note']=$table_note[0]['column_comment'];
                if(!empty($field_info)){
                    $is_exist=D(self::BSTATUSVALUE)->getInfo(array('s_id'=>$field_info['id'],'status'=>1));
                    if($is_exist){
                        $field_value_info=D(self::BSTATUSVALUE)->getInfo(array('s_id'=>$field_info['id'],'value'=>$val,'status'=>1));
                        $res['value_note']=!empty($field_value_info)?$field_value_info['comment']:null;
                    }
                }
                $result[]=$res;
            }
            return $result;
        }else{
            return null;
        }
    }

    public function get_tables_arr(){
        $tables=file_get_contents(APP_PATH."Business/operation_tables.json");
        $tables=json_decode($tables,true);
        return $tables;
    }
    //判断是否ignore
    function is_check_ignore(){
        $ignore_arr=include APP_PATH."System/operate_log_ignore.php";
        $m=$ignore_arr['CONTROLLER_NAME'][strtolower(CONTROLLER_NAME)];
        $a=$ignore_arr['CONTROLLER_NAME'][strtolower(CONTROLLER_NAME)]['ACTION_NAME'];
        if(!empty($m)){
            if($m=="all"){
                return true;
            }
            if(in_array(strtolower(ACTION_NAME),$a)){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}

