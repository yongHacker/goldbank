<?php
/**
 * 店铺管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BShopController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bcurrency_model=D("BCurrency");
		$this->bshop_model=D("BShop");
		$this->bemployee_model=D("BEmployee");
		$this->bwarehouse_model=D("BWarehouse");
	}
    /**
     * 店铺列表
     */
    public function index() {
		$getdata=I("");
		$condition=array("gb_b_shop.company_id"=>$this->MUser['company_id'],"gb_b_shop.deleted"=>0);
		if($getdata["search_name"]){
			$condition["gb_b_shop.shop_name|gb_b_shop.code|gb_b_shop.mobile"]=array("like","%".$getdata["search_name"]."%");
		}
		$count=$this->bshop_model->countList($condition,$field='*',$join='',$order='id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$join = 'left join gb_b_employee as e on e.user_id = gb_b_shop.manger_id and e.deleted=0 and e.company_id='.get_company_id();
		$join .= ' left join gb_m_users as u on e.user_id = u.id ';
		$join .= ' left join '.DB_PRE.'b_currency as currency on currency.id = gb_b_shop.currency_id and currency.deleted=0 ';
		$field="gb_b_shop.*,u.user_nicename,e.employee_name,currency.currency_name";
		$data=$this->bshop_model->getList($condition,$field,$limit,$join,$order='id desc');
		$company=D("BCompany")->getInfo(array("company_id"=>get_company_id()));
		$this->assign("shop_num_html", "店铺授权数量为".$company["shop_num"]."个,已经创建店铺".$count."个，还可以创建".($company["shop_num"]-$count)."个。");
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
       	$this->display();
    }

	//店铺添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$condition=array("deleted"=>0,'company_id'=>$this->MUser["company_id"]);
			$currency=$this->bcurrency_model->getList($condition,$field='*',$limit='',$join='',$order='id desc');
			$this->assign("currency",$currency);
			$this->display();
		}else{
			if (IS_POST) {
				$company=D("BCompany")->getInfo(array("company_id"=>get_company_id()));
				$condition=array("deleted"=>0,'company_id'=>$this->MUser["company_id"]);
				$shop_count=$this->bshop_model->countList($condition);
				if($company["shop_num"]<=$shop_count){
					$this->error("授权店铺数已经使用完，如需增加，请联系客服！");
				}
				if ($this->bshop_model->create()!==false) {
					M()->startTrans();
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];
					$data["shop_name"]=$postdata["shop_name"];
					$data["code"]=$postdata["code"];
					$data["mobile"]=$postdata["mobile"];
					$data["enable"]=$postdata["enable"];
					$data["show_common_payment"]=$postdata["show_common_payment"];
					$data["manger_id"]=$postdata["user_id"];
					$data["currency_id"]=$postdata["currency_id"];
					$data["shop_pic"]=$postdata["shop_pic"];
					$data["address"]=$postdata["address"];
					$data["memo"]=$postdata["memo"];
					$data["creator_id"]=$this->MUser["id"];
					$data["create_time"]=time();
					$data["deleted"]=0;
					$shop_id=$this->bshop_model->insert($data);
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];
					$data["shop_id"]=$shop_id;
					$data["wh_name"]=$postdata["shop_name"];
					$data["wh_code"]=$postdata["code"];
					//$data["wh_uid"]=$postdata["user_id"];
					$data["status"]=1;
					$data["address"]=$postdata["address"];
					$data["created_time"]=time();
					$data["deleted"]=0;
					$bwarehouse=$this->bwarehouse_model->insert($data);
					if ($shop_id!==false&&$bwarehouse!==false) {
						M()->commit();
						$this->success("添加成功！", U("BShop/index"));
					} else {
						M()->rollback();
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bshop_model->getError());
				}
			}
		}
	}


	//店铺编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("bshop.company_id"=>$this->MUser['company_id'],"bshop.id"=>I("get.id",0,'intval'));
			$join="left join ".DB_PRE."m_users musers on bshop.manger_id=musers.id";
			$join .=' left join gb_b_employee as e on e.user_id = bshop.manger_id';
			$data=$this->bshop_model->alias("bshop")->getInfo($condition,$field='bshop.*,musers.user_nicename,e.employee_name',$join,$order='id desc');
			$condition=array("deleted"=>0,'company_id'=>$this->MUser["company_id"]);
			$currency=$this->bcurrency_model->getList($condition,$field='*',$limit='',$join='',$order='id desc');
			$this->assign("currency",$currency);
			$this->assign("data",$data);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bshop_model->create()!==false) {
					$data=array();
					//$data["company_id"]=$this->MUser["company_id"];
					$data["shop_name"]=$postdata["shop_name"];
					$data["mobile"]=$postdata["mobile"];
					$data["manger_id"]=$postdata["user_id"];
					$data["enable"]=$postdata["enable"];
					$data["show_common_payment"]=$postdata["show_common_payment"];
					$data["address"]=$postdata["address"];
					$data["memo"]=$postdata["memo"];
					$data["currency_id"]=$postdata["currency_id"];
					$data["shop_pic"]=$postdata["shop_pic"];
					$condition=array("id"=>$postdata["id"],"company_id"=>$this->MUser["company_id"]);
					$BSectors=$this->bshop_model->update($condition,$data);
					if ($BSectors!==false) {
						$condition=array("shop_id"=>$postdata["id"],"company_id"=>$this->MUser["company_id"]);
						$wh=$this->bwarehouse_model->update($condition,array("wh_name"=>$postdata["shop_name"]));
						$this->success("编辑成功！", U("BShop/index"));
					} else {
						$this->error("编辑失败！");
					}
				} else {
					$this->error($this->bshop_model->getError());
				}
			}
		}
	}
	//店铺编辑
	public function deleted() {
		$postdata = I("");
		$data = array();
		$data["deleted"] = 1;
		$condition = array("id" => $postdata["id"], "company_id" => $this->MUser["company_id"]);
		$BSectors = $this->bshop_model->update($condition, $data);
		if ($BSectors !== false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
//获取员工
	public function bemployee_list() {
		$postdata = I('post.');
		//if(!empty($postdata)) {
			$condition = array("bemployee.deleted" => 0, 'bemployee.company_id' => $this->MUser["company_id"]);
			if ($postdata["search_name"]) {
				$condition["bemployee.employee_name|musers.mobile"] = array("like", "%" . $postdata["search_name"] . "%");
			}
			$condition['bemployee.user_id']=array("neq",1);
			$condition['musers.user_type']=array("neq",4);
			$field = 'bemployee.*,musers.user_status,musers.mobile,bjobs.job_name';
			$join = " join " . DB_PRE . "m_users musers on bemployee.user_id=musers.id";
			$join .= " left join " . DB_PRE . "b_jobs bjobs on bemployee.job_id=bjobs.id";
			$count = $this->bemployee_model->alias("bemployee")->countList($condition, $field, $join, $order = 'bemployee.create_time desc', $group = '');
			$page = $this->page($count, $this->pagenum);
			$limit = $page->firstRow . "," . $page->listRows;
			$data = $this->bemployee_model->alias("bemployee")->getList($condition, $field, $limit, $join, $order = 'bemployee.create_time desc', $group = '');
			$this->assign("page", $page->show('Admin'));
			$this->assign("list", $data);
		//}
		$this->display();
	}

}

