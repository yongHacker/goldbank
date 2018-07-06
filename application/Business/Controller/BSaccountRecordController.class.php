<?php
/**
 * 记账管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BSaccountRecordController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bsaccountrecord_model=D("BSaccountRecord");
		$this->bshop_model=D("BShop");
		$this->bpayment_model=D("BPayment");
		$this->bcurrency_model=D("BCurrency");
	}
	//待审核所有数据(收款对应的单据审核通过，收款才是待审核)
	//和已经审核的数据（需要通过审核人或审核时间判断，因为收款对应的单据更改状态会更改收款的状态）
	public function index_list(){
		$getdata = I("");
		$condition = array(
			// "bsaccountrecord.status"=> 0,
			"bsaccountrecord.deleted"=> 0,
			'bsaccountrecord.company_id'=> get_company_id()
		);
		if ($getdata["keyword"]) {
			$condition["bsell.order_id|bsellreturn.order_id"] = array("like", "%" . $getdata["keyword"] . "%");
		}
		if ($getdata["shop_id"]>0||$getdata["shop_id"]==='0') {
			$condition["bsaccountrecord.shop_id"] =$getdata["shop_id"];
		}
		if ($getdata["pay_id"]) {
			$condition["bsaccountrecord.pay_id"] =$getdata["pay_id"];
		}
		if ($getdata["currency_id"]) {
			$condition["bsaccountrecord.currency_id"] =$getdata["currency_id"];
		}
		$start_time=strtotime(I('request.start_time'));
		if(!empty($start_time)){
			$condition['bsaccountrecord.create_time']=array(
				array('EGT',$start_time)
			);
		}

		$end_time=strtotime(I('request.end_time'));
		if(!empty($end_time)){
			if(empty($where['create_time'])){
				$condition['bsaccountrecord.create_time']=array();
			}
			array_push($condition['bsaccountrecord.create_time'], array('ELT',$end_time));
		}
		$where['bsaccountrecord.check_id']=array('gt',0);
		$where['bsaccountrecord.status']=0;
		$where['_logic'] = 'or';
		$condition['_complex'] = $where;
		$field = 'bsaccountrecord.*,bpayment.pay_name,bcurrency.currency_name,main_currency.currency_name main_currency_name,IFNULL(bsell.order_id, bsellreturn.order_id) AS order_id';
		$field .= ', (CASE bsaccountrecord.status
			WHEN "0" THEN "待审核"
			WHEN "1" THEN "审核通过"
			WHEN "2" THEN "审核不通过"
			WHEN "3" THEN "销售单撤销"
			ELSE "待审核" end
		)as show_status';
		$join = "left join ".DB_PRE."b_sell bsell on bsell.id=bsaccountrecord.sn_id";
		$join .= " left join ".DB_PRE."b_sell_return bsellreturn on bsellreturn.id=bsaccountrecord.sn_id";
		$join .= " left join ".DB_PRE."b_payment bpayment on bpayment.id=bsaccountrecord.pay_id";
		$join .= " left join ".DB_PRE."b_currency bcurrency on bcurrency.id=bsaccountrecord.currency_id";
		$join .= " left join ".DB_PRE."b_currency main_currency on main_currency.id=bsaccountrecord.main_currency_id";

		$count = $this->bsaccountrecord_model->alias("bsaccountrecord")->countList($condition,$field,$join,$order='bsaccountrecord.id desc',$group='');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data = $this->bsaccountrecord_model->alias("bsaccountrecord")->getList($condition,$field,$limit,$join,$order='bsaccountrecord.id desc');
		$count_info=array();
		$count_info['receipt_price']=array_sum(array_column($data,'receipt_price'));
		$count_info['pay_price']=array_sum(array_column($data,'pay_price'));
		$field='sum(bsaccountrecord.pay_price)pay_price,sum(bsaccountrecord.receipt_price)receipt_price,bcurrency.currency_name';
		$count_info['count_by_currency_id'] = $this->bsaccountrecord_model->alias("bsaccountrecord")->getList($condition,$field,$limit,$join,$order='bsaccountrecord.id desc',$group='bsaccountrecord.currency_id');
		$condition=array("deleted"=>0,'company_id'=>$this->MUser["company_id"]);
		$currency=$this->bcurrency_model->getList($condition,$field='*',$limit='',$join='',$order='id desc');
		$payment=$this->bpayment_model->getList($condition,$field='*',$limit='',$join='',$order='id desc');
		$shop=$this->bshop_model->getList($condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]));
		$this->assign("count_info", $count_info);
		$this->assign("shop", $shop);
		$this->assign("currency",$currency);
		$this->assign("payment",$payment);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list", $data);
		$this->display();
	}
	//待审核所有数据(收款对应的单据审核通过，收款才是待审核)
	public function check(){
		$getdata=I("");
		$condition=array("bsaccountrecord.status"=>0,"bsaccountrecord.deleted"=>0,'bsaccountrecord.company_id'=>$this->MUser["company_id"]);
		if ($getdata["keyword"]) {
			$condition["bsell.order_id|bsellreturn.order_id"] = array("like", "%" . $getdata["keyword"] . "%");
		}
		if ($getdata["shop_id"]>0||$getdata["shop_id"]==='0') {
			$condition["bsaccountrecord.shop_id"] =$getdata["shop_id"];
		}
		if ($getdata["pay_id"]) {
			$condition["bsaccountrecord.pay_id"] =$getdata["pay_id"];
		}
		if ($getdata["currency_id"]) {
			$condition["bsaccountrecord.currency_id"] =$getdata["currency_id"];
		}
		$start_time=strtotime(I('request.start_time'));
		if(!empty($start_time)){
			$condition['bsaccountrecord.create_time']=array(
				array('EGT',$start_time)
			);
		}

		$end_time=strtotime(I('request.end_time'));
		if(!empty($end_time)){
			if(empty($where['create_time'])){
				$condition['bsaccountrecord.create_time']=array();
			}
			array_push($condition['bsaccountrecord.create_time'], array('ELT',$end_time));
		}
		$field='bsaccountrecord.*,bpayment.pay_name,bcurrency.currency_name,main_currency.currency_name main_currency_name,IFNULL(bsell.order_id, bsellreturn.order_id) AS order_id';
		$join = "left join ".DB_PRE."b_sell bsell on bsell.id=bsaccountrecord.sn_id";
		$join .= " left join ".DB_PRE."b_sell_return bsellreturn on bsellreturn.id=bsaccountrecord.sn_id";
		$join .= " left join ".DB_PRE."b_payment bpayment on bpayment.id=bsaccountrecord.pay_id";
		$join .= " left join ".DB_PRE."b_currency bcurrency on bcurrency.id=bsaccountrecord.currency_id";
		$join .= " left join ".DB_PRE."b_currency main_currency on main_currency.id=bsaccountrecord.main_currency_id";
		$count = $this->bsaccountrecord_model->alias("bsaccountrecord")->countList($condition,$field,$join,$order='bsaccountrecord.id desc',$group='');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data = $this->bsaccountrecord_model->alias("bsaccountrecord")->getList($condition,$field,$limit,$join,$order='bsaccountrecord.id desc',$group='');
		$field='sum(bsaccountrecord.pay_price)pay_price,sum(bsaccountrecord.receipt_price)receipt_price';
		$count_info=$this->bsaccountrecord_model->alias("bsaccountrecord")->getInfo($condition,$field,$join,$order='bsaccountrecord.id desc');
		$condition=array("deleted"=>0,'company_id'=>$this->MUser["company_id"]);
		$currency=$this->bcurrency_model->getList($condition,$field='*',$limit='',$join='',$order='id desc');
		$payment=$this->bpayment_model->getList($condition,$field='*',$limit='',$join='',$order='id desc');
		$shop=$this->bshop_model->getList($condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]));
		$this->assign("count_info", $count_info);
		$this->assign("shop", $shop);
		$this->assign("currency",$currency);
		//$this->assign("payment",$payment);
		$payment=json_encode($payment);
		$this->assign("payment_json", $payment);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}
	public function check_post(){
		$ids = I('post.ids/a');
		$memo =trim(I('post.memo'));

		$accountrecord=$this->bsaccountrecord_model->getInfo(array('id'=>array('in',$ids),'status'=>array('gt',0)));
		if(!empty($accountrecord)){
			$this->error("存在已经审核项！");
		}
		if(isset($_POST['ids']) && $_GET["check"]){

			$data=array('status'=>1,'check_id'=>get_user_id(),"check_time"=>time(),"check_memo"=>$memo);
			if ( $this->bsaccountrecord_model->update(array('id'=>array('in',$ids)),$data) !== false ) {
				$this->success("审核成功！");
			} else {
				$this->error("审核失败！");
			}
		}
		if(isset($_POST['ids']) && $_GET["uncheck"]){
			$data=array('status'=>2,'check_id'=>get_user_id(),"check_time"=>time(),"check_memo"=>$memo);
			if ( $this->bsaccountrecord_model->update(array('id'=>array('in',$ids)),$data) !== false) {
				$this->success("审核成功！");
			} else {
				$this->error("审核失败！");
			}
		}
	}
}

