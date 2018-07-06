<?php
/**
 * 键值管理
 */
namespace System\Controller;
use Common\Controller\SystembaseController;

class BOptionsController extends SystembaseController {

    private $boptions_model,$bcompany_model;
	public function _initialize() {
		parent::_initialize();
		$this->boptions_model=D("BOptions");
		$this->bcompany_model=D("BCompany");
	}
    /**
     * 键值列表
     */
    public function index() {
		$getdata=I("");
		//$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
		$condition=array("gb_b_options.deleted"=>0);
		if($getdata["search_name"]){
			$condition["gb_b_options.option_name|gb_b_options.option_value|gb_b_options.memo|gb_b_company.company_name"]=array("like","%".$getdata["search_name"]."%");
		}
		$count=$this->boptions_model->countList($condition,$field='*',$join='',$order='option_id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$field='gb_b_options.*,gb_b_company.company_name,gb_m_users.user_nicename,gb_m_users.realname,gb_m_users.user_login';
		$join='join gb_b_company on gb_b_company.company_id=gb_b_options.company_id join gb_m_users on gb_m_users.id=gb_b_options.user_id';
		$data=$this->boptions_model->getList($condition,$field,$limit,$join,$order='option_id desc');
		foreach ($data as $key =>$val){
		    $data[$key]['username']=!empty($val['realname'])?$val['realname']:(!empty($val['user_nicename'])?$val['user_nicename']:$val['user_login']);
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
       	$this->display();
    }

	//键值添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
		    $condition=array(
		        'company_status'=>1,
		        'deleted'=>0
		    );
		    $company_list=$this->bcompany_model->getList($condition);
		    $this->assign('company_list',$company_list);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->boptions_model->create()!==false) {
					$data=array();
					$data["company_id"]=$postdata["company_id"];
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
			$condition=array("option_id"=>I("get.option_id",0,'intval'));
			$data=$this->boptions_model->getInfo($condition,$field='*',$join='',$order='option_id desc');
			$this->assign("data",$data);
			
			$condition=array(
			    'company_status'=>1,
			    'deleted'=>0
			);
			$company_list=$this->bcompany_model->getList($condition);
			$this->assign('company_list',$company_list);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->boptions_model->create()!==false) {
					$data=array();
					$data["option_name"]=$postdata["option_name"];
					$data["option_value"]=$postdata["option_value"];
					$data["memo"]=$postdata["memo"];
					$data["status"]=$postdata["status"];
					$data["user_id"]=get_current_system_id();
					$data["update_time"]=time();
					$where['option_name']=$postdata["option_name"];
					$where['option_value']=$postdata["option_value"];
					$where['option_id']=array("neq",$postdata["option_id"]);
					$where["company_id"]=$postdata["company_id"];
					$where["deleted"]=0;
					$info=$this->boptions_model->getInfo($where);
// 					if($info){
// 						$this->error("添加失败，该键名下的键值存在！");
// 					}
					$condition=array("option_id"=>$postdata["option_id"],"company_id"=>$postdata["company_id"]);
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
		$condition = array("option_id" => $postdata["option_id"]);
		$BSectors = $this->boptions_model->update($condition, $data);
		if ($BSectors !== false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}


}

