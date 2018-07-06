<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BMetalTypeController extends BusinessbaseController {

	function _initialize() {
		parent::_initialize();
		$this->bmetaltype_model=D("BMetalType");
	}
	
	// 后台贵金属分类列表
    public function index(){
		$taxonomys=D("BMetalType")->get_metaltype_treetable();
		$this->assign("taxonomys", $taxonomys);
		$this->display();
	}
	
	// 贵金属分类添加
	public function add(){
		if (IS_POST) {
			$postdata=I("post.");
			$_POST["company_id"]=$this->MUser["company_id"];
			if ($this->bmetaltype_model->create()!==false) {
				if ($this->bmetaltype_model->add()!==false) {
					F('all_terms',null);
					$this->success("添加成功！",U("BMetalType/index"));
				} else {
					$this->error("添加失败！");
				}
			} else {
				$this->error($this->bmetaltype_model->getError());
			}
		}else{
			$parentid = I("get.parent",0,'intval');
			$tree=$this->bmetaltype_model->get_metaltype_tree($parentid);
			$cate_list=$this->bmetaltype_model->get_a_gold_category_tree();
			$this->assign("cate_list",$cate_list);
			$this->assign("terms_tree",$tree);
			$this->assign("parent",$parentid);
			$this->assign("level",$parentid);
			$this->display();
		}

	}
	// 贵金属分类编辑
	public function edit(){
		if (IS_POST) {
			if ($this->bmetaltype_model->create()!==false) {
				if ($this->bmetaltype_model->save()!==false) {
					F('all_terms',null);
					$this->success("修改成功！");
				} else {
					$this->error("修改失败！");
				}
			} else {
				$this->error($this->bmetaltype_model->getError());
			}
		}else{
			$id = I("get.id",0,'intval');
			$data=$this->bmetaltype_model->where(array("id" => $id))->find();
			$tree=$this->bmetaltype_model->get_edit_metaltype_tree($id,$data);
			$cate_list=$this->bmetaltype_model->get_a_gold_category_tree();
			$this->assign("cate_list",$cate_list);
			$this->assign("terms_tree",$tree);
			$this->assign("data",$data);
			$this->display();
		}
	}

	// 贵金属分类排序
	public function listorders() {
		$status = parent::_listorders($this->bmetaltype_model);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}
	
	// 删除贵金属分类
	public function delete() {
		$id = I("get.id",0,'intval');
		$count = $this->bmetaltype_model->where(array("pid" => $id))->count();
		if ($count > 0) {
			$this->error("该类型下还有子类，无法删除！");
		}
		$count = D("BBankGoldType")->where(array("b_metal_type_id" => $id))->count();
		if ($count > 0) {
			$this->error("该金属类型下还有金价类型，无法删除！");
		}
		$data["deleted"]=1;
		$data["id"]=$id;
		if ($this->bmetaltype_model->save($data)!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
}