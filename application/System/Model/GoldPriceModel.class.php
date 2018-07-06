<?php
namespace System\Model;
use System\Model\ACommonModel;
class GoldPriceModel extends ACommonModel{
	protected $tablename,$price_model;
	protected $connect;
	public function __construct($price_type) {
		$this->connect=C("JJH_DB");
		/*array(
			'db_type'  => 'mysql',
			'db_user'  => 'root',
			'db_pwd'   => 'DSes7000',
			'db_host'  => '120.76.8.24',
			'db_port'  => '3306',
			'db_name'  => 'kjjb_goldprice'
		);*/
		$price_type=empty($_POST['price_type'])?$price_type:$_POST['price_type'];
		if($price_type&&$price_type!='GoldPrice'){
			$this->tablename=$price_type;
			$this->price_model=D('System/'.$price_type);
		}
	}
	public function getNewList(){
		$data=M('gold_category',"jb_",$this->connect)->where("status=1")->select();
		$result=array();
		foreach($data as $k=>$v){
			$key=$v['name']."_price";
			$result[$key]=D('System/'.$key)->getNewInfo();
			$result[$key]['time_date']=date("Y-m-d H:i:s",$result[$key]['time']);
			$result[$key]['create_time_date']=date("Y-m-d H:i:s",$result[$key]['create_time']);
			$result[$key]['price_type']=$key;
			$result[$key]['name']=$v['memo'];
			//D('System/'.$key)->create_view();
		}
		return $result;
	}
	public function getTypeInfo($condition){
		$data=M('gold_category',"jb_",$this->connect)->where($condition)->find();
		$key=$data[name]."_price";
		$result=D('System/'.$key)->getNewInfo();
		$result['price_type']=$key;
		$result['name']=$data['memo'];
		$result['unit']=$data['unit'];
		return $result;
	}
	public function _getList($condition,$field,$limit,$join,$order='id desc'){
		$result=$this->price_model->_getList($condition,$field,$limit,$join,$order);
		return $result;
	}
	public function getList($condition,$field,$limit,$join,$order='id desc'){
		$result=$this->price_model->getList($condition,$field,$limit,$join,$order);
		return $result;
	}
	public function countList($condition=array(),$field="*",$join="",$order='id desc'){
		$result=$this->price_model->countList($condition,$field,$join,$order);
		return $result;
	}
	public function getInfo($condition,$field,$join,$order='id desc'){
		$result=$this->price_model->getInfo($condition,$field,$join,$order);
		return $result;
	}
	public function insert($data){
		//$result=$this->price_model->insert($data);
		$result=1;
		if($result){
			$ret['status']=1;
			$ret['msg']='添加成功！';
		}else{
			$ret['status']=0;
			$ret['msg']='添加失败！';
		}
		return $ret;
	}
	/*function create_view(){
		$result=$this->price_model->create_view();
		if($result){
			$ret['status']=1;
			$ret['msg']='视图添加成功！';
		}else{
			$ret['status']=0;
			$ret['msg']='视图添加失败！';
		}
		return $ret;
	}*/
	//通过视图获取数据
	public function getListByView($condition=array(),$field="*",$limit="",$join="",$order='create_time desc,id desc',$group){
		return $this->price_model->getListByView($condition,$field,$limit,$join,$order,$group);
	}
	//通过视图统计数据
	public function countListByView($condition=array(),$field="*",$join="",$order='id desc',$group){
		return $this->price_model->countListByView($condition,$field,$join,$order,$group);
	}
	//通过视图获取一条数据
	public function getInfoByView($condition,$field,$join,$order='create_time desc,id desc'){
		$result=$this->price_model->getInfoByView($condition,$field,$join,$order);
		return $result;
	}
	//导出数据
	public function export_csv($condition,$field="*",$join="",$page=1){
		return $this->price_model->export_csv($condition,$field,$join,$page);
	}
//查询集金号金价写入系统金价
	public function insert_gold(){
		//$data=D('System/XauusdPrice')->getNewInfo();
		$data=$this->get_jjh_data();
		$day=date('Y-m-d',time());
		$time1=strtotime($day)+4*60*60+5;
		$time2=strtotime($day)+7*60*60-1;
		$weekday=I('get.weekday');
		if(empty($weekday)){
			$weekday = date('w');
		}
		$time=I('get.time');
		if(empty($time)){
			$time = time();
		}
		$condition=($weekday!='0'&&$weekday!='6'&&$weekday!='1')||($weekday=='6'&&$time<$time1)||($weekday=='1'&&$time>$time2);
		if($condition){
			$cur_min=date('i',time());
			if($cur_min%2!=0){
				return false;
			}
			$open_info = D('System/a_options')->getGoldSwitch();
			$is_open = $open_info['option_value'];//金价开关
			$api_type=D('System/a_options')->getGoldTypeSwitch();//金价选择
			if($is_open==1&&$api_type==2){
				$option_info=D('a_options')->getJJHGoldSetting();
				$option_value=json_decode($option_info['option_value'],true);
				$expression=$option_value[$option_value["is_open"]];
				$key=explode("/",$option_info["config"][$option_value["is_open"]]);var_dump($key);
				$price=$data[$key[0]][$key[1]]['q63'];
				$rate=$data['o_data']['JO_56382']['q63'];
				$expression = str_replace("price", (float)$price, $expression);
				$expression = str_replace("rate", (float)$rate, $expression);
				$price=0;
				eval("\$price=" . $expression . ";");
				$insert = array(
					'price' => $price,
					'create_time' => time(),
					'user_id' => 0,
					'cat_id' => 7,//现货黄金价格
					'api_type'=>2,
				);
				$result=D('System/a_gold')->insert($insert);
				@$this->get_business_gold_price(7,$price);
			}
			if($result){
				$ret['status']=1;
				$ret['msg']='添加成功！';
			}else{
				$ret['status']=0;
				$ret['msg']='添加失败或接口未开启！'."(".$is_open."//".$api_type.")";
			}
		}else{
			$ret['status']=0;
			$ret['msg']='条件不满足！';
		}
		return $ret;
	}
	function get_jjh_data(){
		$time = ceil((time() + microtime()) * 1000);
		$gram_data = array('codes' => 'JO_92233', 'isCalc' => 'true', '_' => $time);
		$GramPrice = new \GoldPrice('realtime', array(), '', $gram_data);
		$g_data = $GramPrice->getRealTimeData();
		if ($g_data['flag'] != 1) {
			die(var_dump($g_data['errorCode']));
		}
		$ounce_data = array('codes' => 'JO_92233,JO_111,JO_71,JO_56382', '_' => $time);
		$OuncePrice = new \GoldPrice('realtime', array(), '', $ounce_data);
		$o_data = $OuncePrice->getRealTimeData();
		if ($o_data['flag'] != 1) {
			die(var_dump($o_data['errorCode']));
		}
		$data['o_data'] = $o_data;
		$data['g_data'] = $g_data;
		return $data;
	}
	//b端获取关联金属价格
	public function get_business_gold_price($agc_id,$price){
		$condition=array("is_relation"=>1,"status"=>1,"deleted"=>0);
		$agc_ids=D("BMetalType")->where($condition)->getField("agc_id",true);
		if(I('debug')){
			var_dump(M()->getLastSql());
			var_dump($agc_ids);
			var_dump(in_array($agc_id,$agc_ids)||$agc_id==$agc_ids);
		}
		if(in_array($agc_id,$agc_ids)||$agc_id==$agc_ids){
			$condition=array("agc_id"=>$agc_id,"is_relation"=>1,"status"=>1,"deleted"=>0);
			$list=D("BMetalType")->getList($condition,$field='*',$limit=null,$join='',$order='',$group='');
			//D("Business/BMetalType")->update($condition,array("price"=>$price));
			if(I('debug')){
				var_dump($list);die();
			}
			foreach($list as $k=>$v){
				$insert = array(
					'company_id' => $v['company_id'],
					'price' => $price,
					'create_time' => time(),
					'user_id' => 0,
					'b_metal_type_id' => $v['id']
				);
				$list=D("BMetalPrice")->insert($insert);
			}
		}
	}
}