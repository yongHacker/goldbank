<?php
/**
 * 后台首页
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BGoodsShelvesController extends BusinessbaseController {
	public function _initialize() {
		$this->bgoodscommon_model=D("BGoodsCommon");
		$this->bgoodsclass_model=D("BGoodsClass");
		$this->bgoodsshelves_model=D("BGoodsShelves");
		parent::_initialize();
		$this->b_show_status('b_goods_shelves');
	}
	// 处理列表表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL){
		$getdata=I("");
		$condition=array();
		if($getdata["search_name"]){
			$condition["buy_musers.user_nicename"]=array("like","%".$getdata["search_name"]."%");
		}
		$status=$getdata['status'];
		if(isset($status)&&$status>-2){
			$condition=array("bgoodsshelves.status"=>$status);
		}

		$ex_where = array_merge($condition, $ex_where);
		$request_data = $_REQUEST;
		$this->assign('request_data', $request_data);
	}
	/**
	 * 上架审核列表展示
	 */
	public function _getlist($where=array()){
		$getdata=I("");
		$condition=array("bgoodsshelves.company_id"=>$this->MUser['company_id'],"bgoodsshelves.deleted"=>0);
		if(!empty($where)){
			$condition=array_merge($condition,$where);
		}
		$this->handleSearch($condition);
		$join=" left join ".DB_PRE."m_users create_musers on create_musers.id=bgoodsshelves.user_id";
		$join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bgoodsshelves.check_id";
		$join.=" left join ".DB_PRE."b_goods_common gc on gc.id=bgoodsshelves.goods_common_id";
		$field="bgoodsshelves.*,gc.goods_name";
		$field.=",create_musers.user_nicename,check_musers.user_nicename check_name";
		$count=$this->bgoodsshelves_model->alias("bgoodsshelves")->countList($condition,$field,$join,$order='bgoodsshelves.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bgoodsshelves_model->alias("bgoodsshelves")->getList($condition,$field,$limit,$join,$order='bgoodsshelves.id desc');

		$status_model = D ( 'b_status' );
		$condition=array();
		$condition ["table"] = DB_PRE.'b_goods_shelves';
		$condition ["field"] = 'status';
		$status_list = $status_model->getStatusInfo ($condition );
		$this->assign("status_list",$status_list);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
	}
	//是否上架微信
	public function add() {
		$postdata=I("");
		$goods=$this->bgoodscommon_model->getInfo(array("id"=>$postdata["id"]));
		$mobile_show=empty($postdata['mobile_show'])?1:0;
		if(empty($postdata["id"])&&$goods['mobile_show']==$mobile_show){
			$this->error("上架失败！");
		}else{
			//$condition=array("company_id"=>$this->MUser['company_id'],'id'=>$postdata["id"]);
			$data=array('company_id'=>get_company_id(),'goods_common_id'=>$postdata["id"],'user_id'=>get_user_id());
			$data["mobile_show"]=empty($postdata['mobile_show'])?1:0;
			$data["create_time"]=time();
			$bgoodscommon=D("BGoodsShelves")->insert($data);
			//$bgoodscommon=$this->bgoodscommon_model->update($condition,$data);
		}
		if ($bgoodscommon!==false) {
			$this->success("上架待审核！", U("BGoodsShelves/index"));
		} else {
			$this->error("上架失败！");
		}
	}
	// 销售列表
	public function index(){
		$this->_getlist();
		$this->display();
	}
	// 审核列表
	public function check(){
		$getdata=I("");
		$condition=array("bgoodsshelves.status"=>0);
		$this->_getlist($condition);
		$this->assign("empty_info","没有找到信息");
		$this->display();
	}
	// 审核列表
	public function check_post(){
		$postdata=I("");
		$goods=$this->bgoodscommon_model->getInfo(array("id"=>$postdata["id"]));
		$bgoodsshelves=D("BGoodsShelves")->getInfo(array("id"=>$postdata["id"]));
		if(empty($postdata["id"])&&$goods['mobile_show']==$bgoodsshelves['mobile_show']){
			$this->error("上架失败！");
		}else{
			$data=array('status'=>1,'check_id'=>get_user_id());
			$data["check_time"]=time();
			$update=D("BGoodsShelves")->update(array("id"=>$postdata["id"]),$data);
			$data=array();
			$data["mobile_show"]=$bgoodsshelves['mobile_show'];
			$data["update_time"]=time();
			$bgoodscommon=$this->bgoodscommon_model->update(array('id'=>$bgoodsshelves['goods_common_id']),$data);
		}
		if ($bgoodscommon!==false&&$update!==false) {
			$this->success("上架待审核！", U("BGoodsShelves/index"));
		} else {
			$this->error("上架失败！");
		}
	}
}

