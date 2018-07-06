<?php
namespace System\Model;
use System\Model\ACommonModel;
class LondonGoldPriceModel extends ACommonModel{
	protected $kjjb_market,$kjjb,$table_pre;
	protected $connect;
	public function __construct(){
		$this->connect=C("JJH_DB");
		$this->table_pre="london_gold_price_";
		$tablename=$this->table_pre.date("Ym");
		$this->is_have_table("jb_".$tablename);
		$this->kjjb_market=M($tablename,"jb_",$this->connect);
	}
	public function is_have_table($tablename){
		if(!$this->kjjb_market) {
			$sql = "CREATE TABLE IF NOT EXISTS `" . $tablename . "` (
			 `id` int(11) NOT NULL AUTO_INCREMENT,
			  `price` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT '当前价格',
			  `prevclose` varchar(50) NOT NULL DEFAULT '0.00' COMMENT '昨日收价',
			  `open` varchar(50) NOT NULL DEFAULT '0.00' COMMENT '开盘价',
			  `hight` varchar(50) NOT NULL DEFAULT '0.00' COMMENT '最高价',
			  `low` varchar(50) NOT NULL DEFAULT '0.00' COMMENT '最低价',
			  `buy_price` varchar(50) NOT NULL DEFAULT '0.00' COMMENT '买入价',
			  `sell_price` varchar(50) NOT NULL DEFAULT '0.00' COMMENT '卖出价',
			  `pricedown_price` varchar(50) NOT NULL COMMENT '涨跌额',
			  `pricedown_precent` varchar(50) NOT NULL COMMENT '涨幅',
			  `status` varchar(50) NOT NULL DEFAULT '0' COMMENT '状态 100交易中',
			   `api_type` tinyint(1) DEFAULT '0' COMMENT '接口类型: 0.集结号 ',
			  `time` varchar(15) NOT NULL COMMENT '时间',
			  `create_time` varchar(12) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='伦敦金行情表';";
			$status=M("","jb_",$this->connect)->execute($sql);
			if($status){
				$this->create_view();
			}
		}
	}
	public function getNewInfo($date=0){
		if($date==0){
			$tablename=$this->table_pre.date("Ym");
		}else{
			$tablename=$this->table_pre.date("Ym",strtotime($date." month"));
		}
		$data=M($tablename,"jb_",$this->connect)->order("id desc")->find();
		/*if(empty($data)&&$date>-3){
			$month=$date-1;
			$data=$this->getNewInfo($month);
		}*/
		return $data;
	}
	public function getList($condition=array(),$field="*",$limit="",$join="",$order='id desc',$group){
		return $this->kjjb_market->join($join)->where($condition)->field($field)->limit($limit)->order($order)->group($group)->select();
	}
	public function countList($condition=array(),$field="*",$join="",$order='id desc',$group){
		return $this->kjjb_market->join($join)->where($condition)->field($field)->order($order)->group($group)->count();
	}
	public function getInfo($condition=array(),$field="*",$join="",$order='id desc',$group=""){
		return $this->kjjb_market->join($join)->where($condition)->field($field)->order($order)->find();
	}
	public function _getList($where,$field="*",$limit,$join="",$order='id desc'){
		$end_time=$where['end_time'];
		$begin_time=$where['begin_time'];
		$id = $where['cat_id'];
		$tables=$this->get_tablename($end_time,$begin_time);
		$list=array();
		if($tables) {
			$sql = "";
			$where = "create_time  between " . $begin_time . " and " . $end_time;
			$z = 1;
			foreach ($tables as $k => $v) {
				if ($z == 1) {
					$sql = "select *  from " . $v . " where " . $where;
				} else {
					$sql .= " union all select *  from " . $v . " where " . $where;
				}
				$z++;
			}
			$all_sql = "select c.* from (" . $sql . ") c ";
			if ($order) {
				$all_sql .= " order by " . $order;
			}
			if ($limit) {
				$all_sql .= " limit " . $limit;
			}
			$list=M("","jb_",$this->connect)->query($all_sql);
		}
		return $list;
	}
	public function get_tablename($end_time,$begin_time){
		$tables=array('jb_'.$this->table_pre.date("Ym",$end_time));
		$i=0;
		$time_b=date("Ym",$begin_time);
		while (true){
			if($i==0){
				$table='jb_'.$this->table_pre.date("Ym",$end_time);
			}else{
				$time=date("Ym",strtotime("-".$i." month",$end_time));
				if($time<$time_b){
					break;
				}
				$table='jb_'.$this->table_pre.$time;
			}
			$sql="SHOW COLUMNS FROM ".$table;
			try{
				$res=M("","jb_",$this->connect)->query($sql);
				if($res){
					if(!in_array($table,$tables)){
						array_push($tables,$table);
					}
				}
				$i++;
			}catch(\Exception $e) {
				break;
				echo $table."不存在";die();
			}
		}
		return $tables;
	}
	public function insert($data){
		$xhhj_key='JO_111';
		$o_data=$data['o_data'];
		//现货黄金
		$data=array();
		$data['price']=$o_data[$xhhj_key]['q63'];
		$data['prevclose']=$o_data[$xhhj_key]['q2'];
		$data['open']=$o_data[$xhhj_key]['q1'];
		$data['hight']=$o_data[$xhhj_key]['q3'];
		$data['low']=$o_data[$xhhj_key]['q4'];
		$data['buy_price']=$o_data[$xhhj_key]['q5'];
		$data['sell_price']=$o_data[$xhhj_key]['q6'];
		$data['pricedown_price']=$o_data[$xhhj_key]['q70'];
		$data['pricedown_precent']=$o_data[$xhhj_key]['q80'];
		$data['status']=$o_data[$xhhj_key]['status'];
		$data['time']=$o_data[$xhhj_key]['time']/1000;
		$data['create_time']=time();
		$result=$this->kjjb_market->add($data);
		return $result;
	}
//查询相关表，并创建或修改视图
	function create_view(){
		$tables=M("","jb_",$this->connect)->query("SHOW TABLES like 'jb_".$this->table_pre."2%'");
		if(empty($tables)){
			return false;
		}
		$view_sql="";
		$z = 1;
		foreach ($tables as $k => $v) {
			$key=array_keys($v);
			if ($z == 1) {
				$view_sql.= "select *  from"." ".$v[$key[0]];
			} else {
				$view_sql.= " union all select *  from " . $v[$key[0]];
			}
			$z++;
		}

		$sql='CREATE OR REPLACE VIEW `jb_'.$this->table_pre.'view` AS '.$view_sql.';';
		$view=M("","jb_",$this->connect)->execute($sql);
		if($view){
			return true;
		}else{
			return false;
		}
	}
	//通过视图获取数据
	public function getListByView($condition=array(),$field="*",$limit="",$join="",$order='id desc',$group){
		$tablename_view=$this->table_pre."view";
		$sql="SHOW COLUMNS FROM ".$tablename_view;
		try{
			M("","jb_",$this->connect)->query($sql);
		}catch(\Exception $e) {
			$this->create_view();
		}
		return M($tablename_view,"jb_",$this->connect)->join($join)->where($condition)->field($field)->limit($limit)->order($order)->group($group)->select();
	}
	//通过视图统计数据
	public function countListByView($condition=array(),$field="*",$join="",$order='id desc',$group){
		$tablename_view=$this->table_pre."view";
		$sql="SHOW COLUMNS FROM ".$tablename_view;
		try{
			M("","jb_",$this->connect)->query($sql);
		}catch(\Exception $e) {
			$this->create_view();
		}
		return M($tablename_view,"jb_",$this->connect)->join($join)->where($condition)->field($field)->order($order)->group($group)->count();
	}
	public function getInfoByView($condition=array(),$field="*",$join="",$order='id desc',$group=""){
		$tablename_view=$this->table_pre."view";
		$sql="SHOW COLUMNS FROM ".$tablename_view;
		try{
			M("","jb_",$this->connect)->query($sql);
		}catch(\Exception $e) {
			$result=$this->create_view();
		}
		return M($tablename_view,"jb_",$this->connect)->join($join)->where($condition)->field($field)->order($order)->find();
	}
	//导出
	public function export_csv($where, $field, $join, $page = 1){
		$num = 1000;
		$limit = ($page - 1) * $num .','.$num;

		$data_list = $this->getListByView($where, $field, $limit);

		if($data_list){

			register_shutdown_function(array(&$this, 'export_csv'), $where, $field, '',$page + 1);
			$title_arr = array(
				'序'=> '',
				'最新价'=> 'price',
				'开盘价'=> 'open',
				'最高价'=> 'hight',
				'最低价'=> 'low',
				'涨跌'=> 'pricedown_price',
				'浮动（%）'=> 'pricedown_precent',
				'昨收价'=> 'prevclose',
				'更新时间'=> 'time',
				'创建时间'=> 'create_time',
				'状态'=> 'status'
			);

			if($page == 1) {
				$content = iconv('utf-8','gbk', implode(',', array_keys($title_arr)));
				$content = $content . "\n";
			}

			foreach ($data_list as $key=>$value){
				$row = array();
				$row['id'] = $key+1 + ($page - 1) * $num;
				foreach ($title_arr as $v) {
					if($v){
						$row[$v] = iconv('utf-8','gbk',$value[$v]);
						if($v=='time'||$v=='create_time'){
							$row[$v] = date("Y-m-d H:i:s",$value[$v]);
						}
						if($v=='status'){
							$row[$v] = $value[$v]==100?'交易中':'闭市';
						}
					}
				}
				$content .= implode(",", $row) . "\n";
			}
			header("Content-Disposition: attachment; filename=伦敦金行情.csv");

			echo $content;
		}
	}
}