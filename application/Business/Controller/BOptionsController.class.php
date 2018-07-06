<?php
/**
 * 键值管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BOptionsController extends BusinessbaseController {

	public function _initialize() {
		parent::_initialize();
		$this->boptions_model=D("BOptions");
	}
    /**
     * 键值列表
     */
    public function index() {
		$getdata=I("");
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
		if($getdata["search_name"]){
			$condition["option_name|option_value|memo"]=array("like","%".$getdata["search_name"]."%");
		}
		$count=$this->boptions_model->countList($condition,$field='*',$join='',$order='option_id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->boptions_model->getList($condition,$field='*',$limit,$join='',$order='option_id desc');
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
       	$this->display();
    }

	//键值添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->boptions_model->create()!==false) {
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];
					$data["option_name"]=$postdata["option_name"];
					$data["option_value"]=$postdata["option_value"];
					$data["memo"]=$postdata["memo"];
					$data["status"]=$postdata["status"];
					$data["user_id"]=$this->MUser["id"];
					$data["update_time"]=time();
					$data["deleted"]=0;
					$where['option_name']=$postdata["option_name"];
					$where['option_value']=$postdata["option_value"];
					$where["company_id"]=$this->MUser["company_id"];
					$where["deleted"]=0;
					$info=$this->boptions_model->getInfo($where);
					if($info){
						$this->error("添加失败，该键名下的键值存在！");
					}
					$BSectors=$this->boptions_model->insert($data);
					if ($BSectors!==false) {
						$this->success("添加成功！", U("BOptions/index"));
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->boptions_model->getError());
				}
			}
		}
	}


	//键值编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("company_id"=>$this->MUser['company_id'],"option_id"=>I("get.option_id",0,'intval'));
			$data=$this->boptions_model->getInfo($condition,$field='*',$join='',$order='option_id desc');
			$this->assign("data",$data);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->boptions_model->create()!==false) {
					$data=array();
					$data["option_name"]=$postdata["option_name"];
					$data["option_value"]=$postdata["option_value"];
					$data["memo"]=$postdata["memo"];
					$data["status"]=$postdata["status"];
					$data["user_id"]=$this->MUser["id"];
					$data["update_time"]=time();
					$where['option_name']=$postdata["option_name"];
					$where['option_value']=$postdata["option_value"];
					$where['option_id']=array("neq",$postdata["option_id"]);
					$where["company_id"]=$this->MUser["company_id"];
					$where["deleted"]=0;
					$info=$this->boptions_model->getInfo($where);
					if($info){
						$this->error("添加失败，该键名下的键值存在！");
					}
					$condition=array("option_id"=>$postdata["option_id"],"company_id"=>$this->MUser["company_id"]);
					$BSectors=$this->boptions_model->update($condition,$data);
					if ($BSectors!==false) {
						$this->success("编辑成功！", U("BOptions/index"));
					} else {
						$this->error("编辑失败！");
					}
				} else {
					$this->error($this->boptions_model->getError());
				}
			}
		}
	}
	//键值删除
	public function deleted() {
		$postdata = I("");
		$data = array();
		$data["deleted"] = 1;
		$condition = array("option_id" => $postdata["option_id"], "company_id" => $this->MUser["company_id"]);
		$BSectors = $this->boptions_model->update($condition, $data);
		if ($BSectors !== false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}


}

