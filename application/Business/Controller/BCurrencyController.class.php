<?php
/**
 * 币种管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BCurrencyController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bcurrency_model=D("BCurrency");
	}
    /**
     * 币种列表
     */
    public function index() {
		$getdata=I("");
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
		if($getdata["search_name"]){
			$condition["currency_name|exchange_rate|unit"]=array("like","%".$getdata["search_name"]."%");
		}
		$count=$this->bcurrency_model->countList($condition,$field='*',$join='',$order='id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bcurrency_model->getList($condition,$field='*',$limit,$join='',$order='id desc');
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
       	$this->display();
    }

	//币种添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$this->display();
		}else{
			if (IS_POST) {
				if($postdata["exchange_rate"]<=0){
					$this->error("汇率需要大于0！");
				}
				if ($this->bcurrency_model->create()!==false) {
					M()->startTrans();
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];
					$data["currency_name"]=$postdata["currency_name"];
					$data["exchange_rate"]=$postdata["exchange_rate"];
					$data["unit"]=$postdata["unit"];
					$data["is_main"]=$postdata["is_main"];
					$data["status"]=$postdata["status"];
					$data["deleted"]=0;
					if($postdata["is_main"]==1){
						$updata=array("is_main"=>0);
						$this->bcurrency_model->update(array("is_main"=>1,'company_id'=>get_company_id()),$updata);
					}
					$bcurrency=$this->bcurrency_model->insert($data);
					if ($bcurrency!==false) {
						M()->commit();
						$this->success("添加成功！", U("BCurrency/index"));
					} else {
						M()->rollback();
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bcurrency_model->getError());
				}
			}
		}
	}


	//币种编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("company_id"=>$this->MUser['company_id'],"id"=>I("get.id",0,'intval'));
			$data=$this->bcurrency_model->getInfo($condition,$field='*',$join='',$order='id desc');
			$this->assign("data",$data);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bcurrency_model->create()!==false) {
					$data=array();
					$data["currency_name"]=$postdata["currency_name"];
					$data["exchange_rate"]=$postdata["exchange_rate"];
					$data["unit"]=$postdata["unit"];
					$data["is_main"]=$postdata["is_main"];
					$data["status"]=$postdata["status"];
					$condition=array("id"=>$postdata["id"],"company_id"=>$this->MUser["company_id"]);
					if($postdata["is_main"]==1){
						$updata=array("is_main"=>0);
						$this->bcurrency_model->update(array("is_main"=>1,"company_id"=>$this->MUser["company_id"]),$updata);
					}
					$BSectors=$this->bcurrency_model->update($condition,$data);
					if ($BSectors!==false) {
						$this->success("编辑成功！", U("BCurrency/index"));
					} else {
						$this->error("编辑失败！");
					}
				} else {
					$this->error($this->bcurrency_model->getError());
				}
			}
		}
	}
	//币种编辑
	public function deleted() {
		$postdata = I("");
		$data = array();
		$data["deleted"] = 1;
		$condition = array("id" => $postdata["id"], "company_id" => $this->MUser["company_id"]);
		$BSectors = $this->bcurrency_model->update($condition, $data);
		if ($BSectors !== false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}


}

