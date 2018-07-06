<?php
namespace System\Controller;
use Common\Controller\SystembaseController;
class GoldPriceController extends SystembaseController{

	public function index(){
		//$_POST['price_type']=$price_type;
		$goldprice=D("System/GoldPrice");
		$list =$goldprice->getNewList();
		$this->assign('list',$list);
		if(I("data_type")){
			//$this->ajaxReturn($list);die();
			echo json_encode($list,true);die();
		}
		$this->display();
	}
	//通过视图获取数据
	public function detail(){
		$price_type =I('get.price_type');
		if(empty($price_type)){
			$this->error("参数错误！");
		}
		$condition=array();
		if(I('begin_time')){
			$begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
			//$begin_time = strtotime(date('Y-m-d H:is', $begin_time));
			$condition['create_time'] = array('gt', $begin_time);
		}
		if(I('end_time')){
			$end_time = I('end_time') ? strtotime(I('end_time')) : time();
			//$end_time = strtotime(date('Y-m-d H:is', $end_time));
			if(isset($begin_time)){
				$p1 = $condition['create_time'];
				unset($condition['create_time']);
				$condition['create_time'] = array($p1, array('lt', $end_time));
			}else{
				$condition['create_time'] = array('lt', $end_time);
			}
		}
		if(I('price_status')){
			$condition['status'] = I('price_status');
		}
		$_POST['price_type']=$price_type;
		$goldprice=D("System/GoldPrice");
		$count =$goldprice->countListByView($condition);
		$numpage = 10;		//每页显示条数
		$page       =$this->page($count,$numpage);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show       = $page->show("Admin");// 分页显示输出
		$limit=$page->firstRow.','.$page->listRows;
		$list =$goldprice->getListByView($condition,"*",$limit);
		$this->assign('name',I('get.name'));
		$this->assign('page',$show);
		$this->assign('list',$list);
		$this->assign('numpage',$numpage);
		$this->display();
	}
	/*public function detail(){
		$price_type =I('get.price_type');
		if(empty($price_type)){
			$this->error("参数错误！");
		}
		//die($price_type);
		$_POST['price_type']=$price_type;
		$goldprice=D("System/GoldPrice");
		$count =$goldprice->countList();
		$numpage = 10;		//每页显示条数
		$page       =$this->page($count,$numpage);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show       = $page->show("Admin");// 分页显示输出
		$limit=$page->firstRow.','.$page->listRows;
		$list =$goldprice->getList(array(),"*",$limit);
		$this->assign('name',I('get.name'));
		$this->assign('page',$show);
		$this->assign('list',$list);
		$this->assign('numpage',$numpage);
		$this->display();
	}*/
	//线性图数据
	public function get_xauusd_data() {
		$price_type = I('type');
		if (empty($price_type)) {
			$price_type = "xauusd_price";
		}
		$_POST['price_type'] = $price_type;
		$goldprice = D("System/GoldPrice");
		/*$cache = S(array(
				'type' => 'Memcached',
				'prefix' => 'kjjb')
		);*/
		switch (I("show_time")) {
			case 1:
				$time = strtotime(date("Y-m-d"), time());
				$data_split = 20;
				break;
			case 2:
				$time = strtotime(date("Y-m"), time());
				$cache_key = $price_type . "_one_month";
				$conditon = "create_time>" . $time . " and (create_time-1514736000)%14400<60";//1514736000  2018-01-01  间隔四个小时 14400
				$field = "price,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:00') show_time";
				$data_split = 1500;
				break;
			case 3:
				$time = strtotime(date("Y", time()) . '-01-01');
				$cache_key = $price_type . "_one_year";
				$conditon = "create_time>" . $time . " and (create_time-1514736000)%86400<60";//1514736000  2018-01-01  间隔一天 14400
				$field = "price,FROM_UNIXTIME(create_time,'%Y-%m-%d') show_time";
				$data_split = 45000;
				break;
			default:
				$time = strtotime(date("Y-m-d"), time());
				$show_time = "H:i";
		}
		//$list_cache = $cache->get($cache_key);

		//echo base64_encode($list_cache);die();
		$list = empty($list_cache) ? '' : json_decode(gzinflate($list_cache), true);
		$conditon=empty($conditon)?array("create_time"=>array('gt',$time),"status"=>100):$conditon;
		$field=empty($field)?"price,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%S') show_time":$field;
		if (I("show_time") == 3) {
			if (empty($list)) {
				//一年的数据间隔一天时间取
				$list['data'] = $goldprice->getListByView($conditon, $field, $limit = '', $join = '', $order = 'create_time asc');
				$min_price = $goldprice->getInfoByView(array("create_time" => array('gt', $time), "status" => 100), "price", $join = '', $order = 'price');
				$list['min_price'] = $min_price['price'];
				$list['time'] = time();
			}
		}else{
			if(empty($list)){
				$list['data'] =$goldprice->getList($conditon,$field,$limit='',$join='',$order='create_time asc');
				$min_price =$goldprice->getInfo(array("create_time"=>array('gt',$time),"status"=>100),"price",$join='',$order='price');
				$list['min_price']=$min_price['price'];
				$list['time']=time();
			}
		}
		/*$cache->set($cache_key,null);
		if($cache_key&&empty($list_cache)){
			$dd=$cache->set($cache_key,gzdeflate(json_encode($list)),5000);
		}else{
			$list['cache']=$cache_key;
		}*/
		$name=explode("_price",$price_type);
		$type=$goldprice->getTypeInfo(array("status"=>1,"name"=>$name[0]));
		//print_r($list['data']);die();
		output_data(array('data_list'=> $list['data'],'min_price'=>$list['min_price'],'y_name'=>$type['unit'],'data_split'=>$data_split,'cache'=>$list['cache']));
	}
	//通过联合查询获取数据
	public function detail_new(){
		$id =I('id');
		$begin_time=strtotime($_REQUEST['begin_time']);
		$end_time=strtotime($_REQUEST['end_time']);
		$min_time=strtotime("-2 month",$end_time);
		$max_time=strtotime("+2 month",$begin_time);
		$status=false;
		if($begin_time && $end_time){
			if($end_time<$begin_time){
				$this->assign("error",'开始时间不能大于结束时间！');
				$status=true;
			}
			if($end_time>$max_time){
				$this->assign("error",'查询时间不能超过两个月！');
				$status=true;
			}
		}elseif($begin_time){
			if($max_time>time()){
				$end_time=time();
			}else{
				$end_time=$max_time;
			}
		}elseif($end_time){
			$begin_time=$min_time;
		}else{
			$end_time=time();
			$begin_time=strtotime("-2 month",$end_time);
		}
		if($status){
			$end_time=time();
			$begin_time=strtotime("-2 month",$end_time);
		}
		$where=array();
		$where['end_time']=$end_time;
		$where['begin_time']=$begin_time;
		$price_type =I('get.price_type');
		if(empty($price_type)){
			$this->error("参数错误！");
		}
		$_POST['price_type']=$price_type;
		$goldprice=D("System/GoldPrice");
		$count =$goldprice->countList($where);
		//$goldprice->create_view();
		//$count =count($c);
		$numpage = 10;		//每页显示条数
		$page       =$this->page($count,$numpage);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show       = $page->show("Admin");// 分页显示输出
		$limit=$page->firstRow.','.$page->listRows;
		$list =$goldprice->_getList($where,"*",$limit);
		$this->assign('name',I('get.name'));
		$this->assign('page',$show);
		$this->assign('list',$list);
		$this->assign('numpage',$numpage);
		$this->display();
	}
	//导出列表
	function export_csv(){
		$price_type =I('get.price_type');
		if(empty($price_type)){
			$this->error("参数错误！");
		}
		$condition=array();
		if(I('begin_time')){
			$begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
			//$begin_time = strtotime(date('Y-m-d H:is', $begin_time));
			$condition['create_time'] = array('gt', $begin_time);
		}
		if(I('end_time')){
			$end_time = I('end_time') ? strtotime(I('end_time')) : time();
			//$end_time = strtotime(date('Y-m-d H:is', $end_time));
			if(isset($begin_time)){
				$p1 = $condition['create_time'];
				unset($condition['create_time']);
				$condition['create_time'] = array($p1, array('lt', $end_time));
			}else{
				$condition['create_time'] = array('lt', $end_time);
			}
		}
		$_POST['price_type']=$price_type;
		$goldprice=D("System/GoldPrice");
		$goldprice->export_csv($condition);
	}
} 