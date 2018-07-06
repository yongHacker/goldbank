<?php
namespace System\Model;
use System\Model\ACommonModel;
class GoldMarketNewModel extends ACommonModel{
	protected $kjjb_market,$kjjb;
	public function __construct(){
		$tablename="gold_market_".date("Ym");
		$this->kjjb_market=M($tablename,"gb_","GOLDBANK_MARKET_DB");
		$tablename="gb_".$tablename;
		$this->is_have_table($tablename);

	}
	public function is_have_table($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `".$tablename."` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增id',
		  `cat_id` int(11) NOT NULL COMMENT '分类id',
		  `latestprice` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '最新价格',
		  `openprice` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '今日开盘价',
		  `maxprice` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '今日最高价',
		  `minprice` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '今日最低价',
		  `rose` varchar(20) NOT NULL DEFAULT '0' COMMENT '涨幅%',
		  `yesprice` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '昨日收盘价',
		  `totalvol` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '总成交量',
		  `time` varchar(12) NOT NULL COMMENT '金价更新时间',
		  `insert_time` varchar(12) NOT NULL DEFAULT '0' COMMENT '插入时间',
		  `api_type` tinyint(1) DEFAULT '0' COMMENT '接口类型: 0.聚合接口 1.nowapi接口',
		  PRIMARY KEY (`id`),
		  KEY `cat_id` (`cat_id`,`time`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='金价行情表';";
		M("","gb_","GOLDBANK_MARKET_DB")->execute($sql);
	}

   	public function insert($insert){
		return $this->kjjb_market->add($insert);
    }
    //查询最新插入时间

	public function getnewlist()
	{
		$insert_time = $this->newsTime();
		$list1=array();
		$list2=array();
		if(!empty($insert_time['0'])){
			$tablename=$insert_time['table_name0'];
			$where=" agm.insert_time = ".$insert_time['0']." and agm.api_type=0 and c.status =1";
			$list1 = M($tablename,"gb_","GOLDBANK_MARKET_DB")->alias("agm")->join('left join gb_gold_category c on c.id = agm.cat_id')->where($where)->order('agm.insert_time desc')->group("agm.cat_id")->select();

		}
		if(!empty($insert_time['1'])){
			$tablename=$insert_time['table_name1'];
			$where=" agm.insert_time = ".$insert_time['1']." and api_type = 1 and c.status =1";
			$list2 =M($tablename,"gb_","GOLDBANK_MARKET_DB")->alias("agm")->join('left join gb_gold_category c on c.id = agm.cat_id')->where($where)->order('agm.insert_time desc')->group("agm.cat_id")->select();
		}

		foreach ($list1 as $k=>$v){
			foreach ($list2 as $key=>$val){
				if($v['cat_id']==$val['cat_id']){
					if($v['insert_time']>$val['insert_time']){
						unset($list2[$key]);
					}else{
						unset($list1[$k]);
					}
				}
			}
		}
		$list=array_merge($list1,$list2);
		return $list;
	}
	//查询最新插入的记录

	public function newsTime()
	{
		$i=0;
		$record=array();
		$record1=array();
		$tablename0="";
		$tablename1="";
		while(true){
			if($i==0){
				$tablename="gold_market_".date("Ym");
			}else{
				$tablename="gold_market_".date("Ym",strtotime("-".$i." month"));
			}
			$sql="SHOW COLUMNS FROM gb_".$tablename;
			$res=M("","gb_","GOLDBANK_MARKET_DB")->query($sql);
			if(empty($res)){
				break;
			}
			$i++;
			if(empty($record)){
				$record =  M($tablename,"gb_","GOLDBANK_MARKET_DB")->order('insert_time desc')->where("api_type=0")->find();
				$tablename0=$tablename;
			}
			if(empty($record1)){
				$record1= M($tablename,"gb_","GOLDBANK_MARKET_DB")->order("insert_time desc")->where("api_type=1")->find();
				$tablename1=$tablename;
			}
			if($record && $record1){
				break;
			}
		}
		$data["0"]=$record['insert_time'];
		$data["1"]=$record1['insert_time'];
		$data['table_name0']=$tablename0;
		$data['table_name1']=$tablename1;
		return $data;
	}
	public function get_tablename($end_time,$begin_time){
		$tables=array("gb_gold_market_".date("Ym",$end_time));
		$i=0;
		$time_b=date("Ym",$begin_time);
		while (true){
			if($i==0){
				$table="gb_gold_market_".date("Ym",$end_time);
			}else{
				$time=date("Ym",strtotime("-".$i." month",$end_time));
				if($time<$time_b){
					break;
				}
				$table="gb_gold_market_".$time;
			}
			$sql="SHOW COLUMNS FROM ".$table;
			$res=M("","gb_","GOLDBANK_MARKET_DB")->query($sql);
			if($res){
				if(!in_array($table,$tables)){
					array_push($tables,$table);
				}
			}
			$i++;
		}
		return $tables;
	}
	public function getList($where,$limit,$order){
		$end_time=$where['end_time'];
		$begin_time=$where['begin_time'];
		$id = $where['cat_id'];
		$tables=$this->get_tablename($end_time,$begin_time);
		$list=array();
		if($tables) {
			$sql = "";
			$where = " cat_id= " . $id . " and  insert_time  between " . $begin_time . " and " . $end_time;
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
			$list=M("","gb_","GOLDBANK_MARKET_DB")->query($all_sql);
		}
		return $list;
	}
}

