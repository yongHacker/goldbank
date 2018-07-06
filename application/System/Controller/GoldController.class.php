<?php

namespace System\Controller;

use Common\Controller\SystembaseController;

class GoldController extends SystembaseController{

	public function __construct(){
		parent::__construct();
	}

    public function index(){
    	$gold2 = D('System/AGold')->getNewGold();

		$condition = array();
		$search_name = trim(I('search_name'));
		if(!empty($search_name)){
		    $condition['agc.name']=array('like','%'.$search_name.'%');
		}

		$begin_time = I('begin_time/s');
		$end_time = I('end_time/s');

		if(!empty($begin_time)){
			$condition['create_time'] = array('gt', strtotime($begin_time));
		}

		if(!empty($end_time)){
			if(!empty($begin_time)){
				$condition['create_time'] = array($condition['create_time'], array('lt', strtotime($end_time)));
			}else{
				$condition['create_time'] = array('lt', strtotime($end_time));
			}
		}

		$join = "join ".C('DB_PREFIX')."a_gold_category as agc on agc.id=".C('DB_PREFIX')."a_gold.cat_id";
		$field = C('DB_PREFIX')."a_gold.*,agc.*";
		$order = C('DB_PREFIX')."a_gold.id desc";

		$count = D('System/AGold')->countList($condition, $field, $join);
		$numpage = $this->pagenum;
		$page = $this->page($count,$numpage);
		$limit = $page->firstRow.','.$page->listRows;

		$gold = D('System/AGold')->getList($condition, $field, $limit, $join, $order);
		$gold_category = D('System/AGoldCategory')->getList(array('status'=>1));

		$this->assign('gold_category', $gold_category);
    	$this->assign('gold2', $gold2);
    	$this->assign('gold', $gold);
    	
    	$this->assign('numpage', $this->pagenum);
		$this->assign('page', $page->show("Admin"));

        $this->display();
    }

    public function add(){
    	if($_POST['price']){

    		$condition = array(
    		    'price'=> $_POST['price'],
    		    'create_time'=> time(),
    		    'user_id'=> get_current_system_id(),
    		    'cat_id'=> $_POST['cate']
    		);
			$list = D('System/AGold')->insert($condition);

			if($list){
				@D("GoldPrice")->get_business_gold_price($condition['cat_id'],$condition["price"]);
				echo $arr['status']=1;
			}else{
				echo $arr['status']=0;
			}
			
		}else{
			echo $arr['status']=0;
		}
     }
} 