<?php
namespace Common\Model;
use Common\Model\CommonModel;

class BAccessLogModel extends CommonModel {
    protected $model_access_log;
    protected $connection;
    protected $model_user,$model_table_note,$model_field_value,$model_menu,$table_suf;
    public function __construct(){
        $this->model_user=D('Common/MUsers');
        $this->model_menu=D('Business/BMenu');
        if(I('request.access_begin_time')){
            $this->table_suf=str_replace("-","",I('access_begin_time'));
        }
        $this->_initialize();
    }
    public function _initialize(){
        if (!OPEN_LOG) {
            $this->model_access_log = M('BAccessLog');
            return true;
        }
        $table_suf='_'.date('Ym',time());
        if($this->table_suf){
            $table_suf='_'.$this->table_suf;
        }
        $table='b_access_log'.$table_suf;
        if(!$this->table_is_exists($table)){
            $sql="CREATE TABLE IF NOT EXISTS `".$table."` (
              `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主码id',
               `company_id` bigint(20) DEFAULT NULL COMMENT '商户id',
              `user_id` bigint(20) NOT NULL COMMENT '用户id',
              `user_name` varchar(255) DEFAULT NULL COMMENT '用户姓名',
              `access_path` varchar(255) NOT NULL COMMENT '访问路径',
              `access_name` varchar(255) DEFAULT NULL COMMENT '访问路径注释',
              `post_param` longtext DEFAULT NULL COMMENT 'Post方法提交的参数',
              `get_param` longtext DEFAULT NULL COMMENT 'get方法提交的参数',
              `access_time` varchar(12) NOT NULL COMMENT '访问时间',
              `access_type` tinyint(3) DEFAULT 0 COMMENT '访问类型  0PC 1android 2ios 3android版微信 4ios版微信',
              `access_ip` varchar(20) NOT NULL COMMENT '访问的ip', 
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `user_name` (`user_name`(191)),
              KEY `access_ip` (`access_ip`),
              KEY `access_time` (`access_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问记录表' AUTO_INCREMENT=1 ;";
            M('','b_access_',C('DB_OPERATE_LOG'),1)->execute($sql);
        }
        $this->model_access_log=M('log'.$table_suf,'b_access_',C('DB_OPERATE_LOG'),1);
    }

    public function getAccessTypeList(){
      // access_type 请严格按照注释格式 “访问类型  0PC 1android 2ios 3android版微信 4ios版微信 5xxx 6yyy 7zzz”
      $table_suf='_'.date('Ym',time());
      if($this->table_suf){
          $table_suf='_'.$this->table_suf;
      }
      $table = !OPEN_LOG ? 'gp_access_log' : 'b_access_log' . $table_suf;

      $sql = "SELECT COLUMN_COMMENT as comment
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE 
                table_name = '". $table ."' AND 
                column_name = 'access_type'";
      
      $rs = M()->query($sql);
      $tmp = array();

      if(!empty($rs)){

        $arr = explode('  ', $rs[0]['comment']);
        $comment_arr = array();
        foreach (explode(' ', $arr[1]) as $key => $value) {
          $comment_arr[substr($value, 0, 1)] = substr($value, 1);
        }
        $tmp['title'] = $arr[0];
        $tmp['data'] = $comment_arr;
      }

      return $tmp;
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
    public function insertLog($insert){
        if (!OPEN_LOG) {
            return true;
        }
        return $this->model_access_log->add($insert);
    }
    public function countLogList($condition,$field='*',$join='',$order='',$group=''){
        return $this->model_access_log->field($field)->where($condition)->join($join)->order($order)->group($group)->count();
    }
    public function getLogList($condition,$field='*',$limit='',$join='',$order='',$group=''){
        return $this->model_access_log->field($field)->where($condition)->limit($limit)->join($join)->order($order)->group($group)->select();
    }
    public function updateLog($condition,$update){
        if (!OPEN_LOG) {
            return true;
        }
        return $this->model_access_log->where($condition)->save($update);
    }
    public function getLogInfo($condition,$field='*'){
        return $this->model_access_log->field($field)->where($condition)->find();
    }
    /**
     * 插入一条访问日志数据
     * @author lzy 2017-12-13
     * @param array $insert
     * @return boolean|Ambigous <mixed, boolean, unknown, string>
     */
    public function addLog($insert){
       /* if(!in_array($_SERVER['HTTP_HOST'],C('LOG_SERVER'))){
            return true;
        }*/
        $access_path=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        $path=explode('/',$access_path);
        $menu_condition=array(
            'app'=>$path[0],
            'model'=>$path[1],
            'action'=>$path[2]
        );
        $menu_info=$this->model_menu->getInfo($menu_condition);
        $insert['access_path']=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;     //访问的路径
        $insert['access_name']=!empty($menu_info['remark'])?$menu_info['remark']:(!empty($menu_info['name'])?$menu_info['name']:'');
        $insert['post_param']=!empty($_POST)?json_encode($_POST,true):'';
        $insert['get_param']=!empty($_GET)?json_encode($_GET,true):'';
        $insert['access_time']=time();
        $insert['access_type']=0;
        $insert['access_ip']=get_client_ip(0, true);
        $result=$this->insertLog($insert);
        return $result;
    }
    /**
     * 获得日志详情
     * @author lzy 2017-12-13
     * @param array $condition
     * @param string $field
     */
    public function getLogDetail($condition,$field='*'){
        $log_info=$this->getLogInfo($condition,$field);
        //数据处理
        $log_info['postparam']=!empty($log_info['post_param'])?json_decode($log_info['post_param'],true):null;
        $log_info['getparam']=!empty($log_info['get_param'])?json_decode($log_info['get_param'],true):null;
        return $log_info;
    }
}

