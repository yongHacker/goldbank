<?php
namespace System\Controller;
use Common\Controller\SystembaseController;
class MarketController extends SystembaseController{
	public function market(){
		$appkey = "484256b6feecf2b398a243da89ac51b6";
		$url = "http://web.juhe.cn:8080/finance/gold/shgold";
		$params = array(
		      "key" => $appkey,//APP Key
		      "v" => "1",//JSON格式版本(0或1)默认为0
		);
		$weekday=date('w');//星期几
        $day =date('Y-m-d',time());
		$time =time();
		$day_9=  strtotime($day)+9*3600;//每天9点
		$day_11= strtotime($day)+11.55*3600;//每天11点半
		$day_13= strtotime($day)+13.5*3600;//每天13点半
		$day_15=  strtotime($day)+15.55*3600;//每天15点半
    	if((($time > $day_9 && $time < $day_11)||( $time < $day_15 && $time >$day_13))&&$weekday!='6'&&$weekday!='0'){
			$paramstring = http_build_query($params);
			$content = juhecurl($url,$paramstring);
			$result = json_decode($content,true);
			$model_gold_cat=D('System/a_gold_category');
			$model_gold_mak=D('System/a_gold_market');
			if($result){
			    if($result['error_code']=='0'){
			    	$g_list=$result['result'][0];
			    	foreach($g_list as $key=>$val){
						$gold_category=$model_gold_cat->getInfo(array('status'=>1,'name'=>$key));
						if(!empty($gold_category)){
							$gold_price=array();
							$gold_price['latestprice']= $val['latestpri']!="--"? $val['latestpri'] : '0.00';
							$gold_price['openprice']= $val['openpri']!="--"?$val['openpri'] : '0.00';
							$gold_price['maxprice']= $val['maxpri']!="--"?$val['maxpri'] : '0.00';
							$gold_price['minprice']= $val['minpri']!="--"?$val['minpri'] : '0.00';
							$val['limit']=str_replace('%','',$val['limit']);
							$gold_price['rose']= $val['limit']!="--"?$val['limit'] : '0.00';
							$gold_price['yesprice']= $val['yespri']!="--"?$val['yespri'] : '0.00';
							$gold_price['totalvol']=$val['totalvol']!="--"?$val['totalvol'] : '0.00';
							$gold_price['time']= strtotime($val['time']);
							$gold_price['cat_id']= $gold_category['id'];
							$gold_price['insert_time']= time();
							$model_gold_mak->insert($gold_price);
						}
					}
			    }else{
			        echo $result['error_code'].":".$result['reason'];
			    }
			}else{
			    echo "请求失败";
			}
		}
	}
	public function index_old(){
		$list =D('System/AGoldMarket')->getnewlist();
		$this->assign('list',$list);
		$this->display();
	}
	public function detail_old(){
		$id =I('get.id');
		$count =D('System/AGoldMarket')->countList(array('cat_id'=>$id));
		$numpage = 10;		//每页显示条数
		$page       =$this->page($count,$numpage);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show       = $page->show("Admin");// 分页显示输出
		$list = D('System/AGoldMarket')->getList(array('cat_id'=>$id),"*",$page->firstRow.','.$page->listRows,"","id desc");
		$this->assign('name',I('get.name'));
		$this->assign('page',$show);
		$this->assign('list',$list);
		$this->assign('numpage',$numpage);
		$this->display();
	}
	public function index(){
		$list =D("System/GoldMarketNew")->getnewlist();
		//var_dump($list);
		$this->assign('list',$list);
		$this->display();
	}
	public function detail(){
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
				$this->assign("error",'查询间隔时间不能超过两个月！');
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
			$begin_time=strtotime("-1 month",$end_time);
		}
		if($status){
			$end_time=time();
			$begin_time=strtotime("-1 month",$end_time);
		}
		$where['cat_id']=$id;
		$where['end_time']=$end_time;
		$where['begin_time']=$begin_time;
		//var_dump($where);die();
		$count =D("System/GoldMarketNew")->countList($where);
		//$count=count($list);
		$numpage = 10;		//每页显示条数
		$page       =$this->page($count,$numpage);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show       = $page->show("Admin");// 分页显示输出
		$list = D("System/GoldMarketNew")->getList($where,$page->firstRow.','.$page->listRows,"c.insert_time desc");
		$this->assign('name',I('name'));
		$this->assign('page',$show);
		$this->assign('list',$list);
		$this->assign("id",$id);
		$this->assign('numpage',$numpage);
		$this->display();
	}

} 