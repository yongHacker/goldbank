<?php
/**
 * 金子价格管理
 */
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BBankGoldController extends ShopbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bbankgoldtype_model=D("BBankGoldType");
		$this->bbankgoldlog_model=D("BBankGoldLog");
		$this->bbankgold_model=D("BBankGold");
	}
	public function test(){
		for($i=0;$i<60;$i++){
			M()->startTrans();
			$sql="select * from gb_b_bank_gold where bg_id=37 FOR UPDATE";
			M()->execute($sql);
			sleep(1);
			echo $i."<br/>";
			M()->commit();
		}
	}
	public function test2(){
		M()->where()->find();
		M()->startTrans();
		$sql="select * from gb_b_bank_gold where bg_id=37 FOR UPDATE";
		$data=M()->execute($sql);
		var_dump($data);
		M()->commit();
	}
	/**
	 * 金子价格展示
	 */
	public function index() {
		$getdata=I("");
		$condition=array("bbankgoldtype.company_id"=>$this->MUser['company_id'],"bbankgoldtype.deleted"=>0,"bbankgoldtype.status"=>"1","shop_id"=>0);
		if($getdata["search_name"]){
			$condition["bbankgoldtype.name"]=array("like","%".$getdata["search_name"]."%");
		}
		$join="left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgold.bgt_id";
		$join.="  left join ".DB_PRE."b_metal_type metaltype on metaltype.id=bbankgoldtype.b_metal_type_id";
		$join.="  left join ".DB_PRE."b_shop bshop on bshop.id=bbankgold.shop_id";
		$field='bbankgoldtype.name,bbankgoldtype.bgt_id,metaltype.id bmt_id,metaltype.name bmt_name,bbankgold.*,bshop.shop_name';
		$count=$this->bbankgold_model->alias("bbankgold")->countList($condition,$field,$join,$order='bg_id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bbankgold_model->alias("bbankgold")->getList($condition,$field,$limit,$join,$order='bg_id desc');
		foreach($data as $k=>$v){
			$data[$k]["price"]=$this->bbankgold_model->get_bankgold_price($v);
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}
	/**
	 * 金子价格展示
	 */
	public function shop_index() {
		$getdata=I("");
		$condition=array("bbankgoldtype.company_id"=>$this->MUser['company_id'],"bbankgoldtype.deleted"=>0,"bbankgoldtype.status"=>"1","bbankgold.shop_id"=>get_shop_id());
		if($getdata["search_name"]){
			$condition["bbankgoldtype.name"]=array("like","%".$getdata["search_name"]."%");
		}
		$join="left join ".DB_PRE."b_bank_gold p_bank_gold on bbankgold.parent_id=p_bank_gold.bg_id";
		$join.=" left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=p_bank_gold.bgt_id";
		$join.="  left join ".DB_PRE."b_metal_type metaltype on metaltype.id=bbankgoldtype.b_metal_type_id";
		$join.="  left join ".DB_PRE."b_shop bshop on bshop.id=bbankgold.shop_id";
		$field='p_bank_gold.*,bbankgold.bg_id id,bbankgold.formula b_formula,bbankgoldtype.name,bbankgoldtype.bgt_id,metaltype.id bmt_id,metaltype.name bmt_name,bshop.shop_name';
		$count=$this->bbankgold_model->alias("bbankgold")->countList($condition,$field,$join,$order='bbankgold.bg_id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bbankgold_model->alias("bbankgold")->getList($condition,$field,$limit,$join,$order='bbankgold.bg_id desc');
		foreach($data as $k=>$v){
			$price=$this->bbankgold_model->get_bankgold_price($v);
			$expression = str_replace("price", (float)$price, $v["b_formula"]);
			if(!empty($expression)){
				eval("\$price=" . $expression . ";");
				$data[$k]["price"]=$price;
			}else{
				$data[$k]["price"]=0;
			}
		}
		/*$condition=array("bbankgoldtype.company_id"=>$this->MUser['company_id'],"bbankgoldtype.deleted"=>0,"bbankgoldtype.status"=>"1","bbankgold.shop_id"=>0);
		$join="left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgold.bgt_id";
		$data=$this->bbankgold_model->alias("bbankgold")->getList($condition,$field="*",$limit="",$join,$order='bg_id desc');
		$this->assign("gold_type", $data);*/
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}
	//金子价格添加
	public function add() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("bbankgoldtype.company_id"=>$this->MUser['company_id'],"bbankgoldtype.deleted"=>0,"bbankgoldtype.status"=>"1","shop_id"=>0);
			$join="left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgold.bgt_id";
			$join.="  left join ".DB_PRE."b_metal_type metaltype on metaltype.id=bbankgoldtype.b_metal_type_id";
			$join.="  left join ".DB_PRE."b_shop bshop on bshop.id=bbankgold.shop_id";
			$field='bbankgoldtype.name,bbankgoldtype.bgt_id,metaltype.id bmt_id,metaltype.name bmt_name,bbankgold.*,bshop.shop_name';
			$data=$this->bbankgold_model->alias("bbankgold")->getList($condition,$field,$limit="",$join,$order='bg_id desc');
			$this->assign("gold_type", $data);
			$condition=array("bshop.deleted"=>0,"bshop.company_id"=>$this->MUser["company_id"]);
			$shop=D("BShop")->alias("bshop")->getList($condition,$field='bshop.*',$limit=null,$join="");
			$this->assign("shop", $shop);
			$this->display();
		}else{
			if (IS_POST) {
				$_POST['formula']=$postdata["expression"];
				if ($this->bbankgold_model->create()!==false) {
					$data=array();
					$data["formula"]=$postdata["expression"];
					$data["update_time"]=time();
					$data["updater"]=$this->MUser["id"];
					$data["deleted"]=0;
					$condition=array("company_id"=>$this->MUser['company_id'],'parent_id'=>$postdata["bg_id"],'shop_id'=>get_shop_id());
					$info=$this->bbankgold_model->getInfo($condition);
					$condition=array("company_id"=>$this->MUser['company_id'],'bg_id'=>$postdata["bg_id"],'shop_id'=>0);
					$bankgold=$this->bbankgold_model->getInfo($condition);
					if($info){
						$this->error("该价格类型已经设定价格，可通过编辑修改");
						//$bbankgold=$this->bbankgold_model->update($condition,$data);
					}else{
						$data["create_time"]=time();
						$data["creator"]=$this->MUser["id"];
						$data["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
						$data["shop_id"]=get_shop_id();//$postdata["company_id"];
						$data["bgt_id"]=$bankgold["bgt_id"];
						$data["parent_id"]=$postdata["bg_id"];
						$bbankgold=$this->bbankgold_model->insert($data);
					}
					if ($bbankgold!==false) {
						$logdata=array();
						$logdata["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
						$logdata["shop_id"]=get_shop_id();//$postdata["shop_id"];
						$logdata["formula"]=$postdata["expression"];
						$logdata['old_formula']=$info['formula'];
						$logdata["bgt_id"]=$bankgold["bgt_id"];
						$logdata["create_time"]=time();
						$logdata["user_id"]=$this->MUser["id"];
						$bbankgoldlog=$this->bbankgoldlog_model->insert($logdata);
						if($bbankgoldlog!==false){
							$this->success("添加成功！", U("BBankGold/shop_index"));
						}else{
							$this->error("金价设置成功！记录添加失败", U("BBankGold/shop_index"));
						}
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bbankgold_model->getError());
				}
			}
		}
	}
	//金子价格编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$bg_id=I("bg_id");
			$condition=array("bbankgoldtype.company_id"=>$this->MUser['company_id'],"bbankgoldtype.deleted"=>0,"bbankgold.bg_id"=>$bg_id);
			$join="left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgold.bgt_id";
			$join.="  left join ".DB_PRE."b_metal_type metaltype on metaltype.id=bbankgoldtype.b_metal_type_id";
			$join.=" left join ".DB_PRE."b_shop bshop on bshop.id=bbankgold.shop_id";
			$bbankgold=$this->bbankgold_model->alias("bbankgold")->getInfo($condition,$field="bbankgold.*,bbankgoldtype.name,bshop.shop_name,metaltype.name bmt_name",$join);
			$this->assign("data", $bbankgold);
			$this->display();
		}else{
			if (IS_POST) {
				if(empty($postdata["expression"])){
					$this->error("添加失败！");
				}
				$_POST['formula']=$postdata["expression"];
				$data=array();
				$data["formula"]=$postdata["expression"];
				$data["update_time"]=time();
				$data["updater"]=$this->MUser["id"];
				/*$old_expression='';
				$shop_id=empty($postdata["shop_id"])?0:$postdata["shop_id"];//$postdata["shop_id"];
				if(empty($postdata["bg_id"])){
					$data["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
					$data["shop_id"]=$shop_id;
					$data["bgt_id"]=$postdata["bgt_id"];
					$data["create_time"]=time();
					$data["creator"]=$this->MUser["id"];
					$data["deleted"]=0;
					$bbankgold=$this->bbankgold_model->insert($data);
				}else{*/
					$condition=array("company_id"=>$this->MUser['company_id'],'bg_id'=>$postdata["bg_id"],'shop_id'=>get_shop_id());
					$info=$this->bbankgold_model->getInfo($condition);
					$old_expression=$info['formula'];
					$bbankgold=$this->bbankgold_model->update($condition,$data);
				//}
				if ($bbankgold!==false) {
					$logdata=array();
					$logdata["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
					$logdata["shop_id"]=get_shop_id();//$postdata["shop_id"];
					$logdata["formula"]=$postdata["expression"];
					$logdata['old_formula']=$old_expression;
					$logdata["bgt_id"]=$info["bgt_id"];
					$logdata["create_time"]=time();
					$logdata["user_id"]=$this->MUser["id"];
					$bbankgoldlog=$this->bbankgoldlog_model->insert($logdata);
					if($bbankgoldlog!==false){
						$this->success("添加成功！", U("BBankGold/shop_index"));
					}else{
						$this->error("金价设置成功！记录添加失败", U("BBankGold/shop_index"));
					}
				} else {
					$this->error("添加失败！");
				}
			}
		}
	}

	//金子价格编辑
	public function deleted() {
		$postdata=I("");
		$data=array();
		$data["deleted"]=empty($postdata['deleted'])?1:0;
		$data["update_time"]=time();
		$data["updater"]=$this->MUser["id"];
		if(empty($postdata["bg_id"])){
			$this->error("更新失败！");
		}else{
			$condition=array("company_id"=>$this->MUser['company_id'],"shop_id"=>$this->MUser['shop_id'],'bg_id'=>$postdata["bg_id"]);
			$bbankgold=$this->bbankgold_model->update($condition,$data);
		}
		if ($bbankgold!==false) {
			$this->success("更新成功！", U("BBankGold/shop_index"));
			/*$logdata=array();
			$logdata["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
			$logdata["shop_id"]=empty($this->MUser["shop_id"])?0:$this->MUser["shop_id"];//$postdata["company_id"];
			$logdata["formula"]=$postdata["expression"];
			$logdata["bg_type"]=$postdata["bgt_id"];
			$logdata["create_time"]=time();
			$logdata["user_id"]=$this->MUser["id"];
			$bbankgoldlog=$this->bbankgoldlog_model->insert($logdata);
			if($bbankgoldlog!==false){
				$this->success("更新成功！", U("BBankGold/index"));
			}else{
				$this->error("金价设置成功！记录添加失败", U("BBankGold/index"));
			}*/
		} else {
			$this->error("更新失败！");
		}
	}
}

