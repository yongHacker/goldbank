<?php
/**
 * 收款方式管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BPaymentController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bpayment_model=D("BPayment");
		$this->b_show_status('b_payment');
	}
    /**
     * 收款方式列表
     */
    public function index() {
		$getdata=I("");
		$condition=array("bpayment.company_id"=>$this->MUser['company_id'],"bpayment.deleted"=>0);
		if($getdata["search_name"]){
			$condition["pay_name"]=array("like","%".trim($getdata["search_name"])."%");
		}
		$join='left join '.DB_PRE.'b_shop bshop on bshop.id=bpayment.shop_id';
		$field="bpayment.*,bshop.shop_name";
		$count=$this->bpayment_model->alias('bpayment')->countList($condition,$field,$join);
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bpayment_model->alias('bpayment')->getList($condition,$field,$limit,$join,$order='bpayment.id desc');
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
       	$this->display();
    }

	//收款方式添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$shop_list=D("BShop")->getShopList();
			$this->assign("shop_list", $shop_list);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bpayment_model->create()!==false) {
					M()->startTrans();
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];
					$data["shop_id"]=$postdata['shop_id'];
					$data["pay_name"]=$postdata["pay_name"];
					$data["pay_fee"]=$postdata["pay_fee"];
					$data["pay_type"]=$postdata["pay_type"];
					$data["status"]=$postdata["status"];
					$data["deleted"]=0;
					$bpayment=$this->bpayment_model->insert($data);
					if ($bpayment!==false) {
						M()->commit();
						$this->success("添加成功！", U("BPayment/index"));
					} else {
						M()->rollback();
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bpayment_model->getError());
				}
			}
		}
	}


	//收款方式编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("company_id"=>$this->MUser['company_id'],"id"=>I("get.id",0,'intval'));
			$data=$this->bpayment_model->getInfo($condition,$field='*',$join='',$order='id desc');
			$shop_list=D("BShop")->getShopList();
			$this->assign("shop_list", $shop_list);
			$this->assign("data",$data);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bpayment_model->create()!==false) {
					$data=array();
					//$data["company_id"]=$this->MUser["company_id"];
					$data["pay_name"]=$postdata["pay_name"];
					$data["pay_fee"]=$postdata["pay_fee"];
					$data["pay_type"]=$postdata["pay_type"];
					$data["status"]=$postdata["status"];
					$condition=array("id"=>$postdata["id"],"company_id"=>$this->MUser["company_id"]);
					$BSectors=$this->bpayment_model->update($condition,$data);
					if ($BSectors!==false) {
						$this->success("编辑成功！", U("BPayment/index"));
					} else {
						$this->error("编辑失败！");
					}
				} else {
					$this->error($this->bpayment_model->getError());
				}
			}
		}
	}
	//收款方式编辑
	public function deleted() {
		$postdata = I("");
		$data = array();
		$data["deleted"] = 1;
		$condition = array("id" => $postdata["id"], "company_id" => $this->MUser["company_id"]);
		$BSectors = $this->bpayment_model->update($condition, $data);
		if ($BSectors !== false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}


}

