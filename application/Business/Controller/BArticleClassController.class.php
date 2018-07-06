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

class BArticleClassController extends BusinessbaseController {
	
	protected $barticleclass_model;
	protected $taxonomys=array("article"=>"文章","picture"=>"图片");
	
	function _initialize() {
		parent::_initialize();
		$this->barticleclass_model = D("BArticleClass");
		$this->assign("taxonomys",$this->taxonomys);
	}
	
	// 后台文章分类列表
    public function index(){
		$taxonomys=$this->barticleclass_model->get_articleclass_treetable();
		$this->assign("taxonomys", $taxonomys);
		$this->display();
	}
	
	// 文章分类添加
	public function add(){
		if (IS_POST) {
			$_POST["company_id"]=$this->MUser["company_id"];
			if ($this->barticleclass_model->create()!==false) {
				if ($this->barticleclass_model->add()!==false) {
					F('all_barticle_class',null);
					$this->success("添加成功！",U("BArticleClass/index"));
				} else {
					$this->error("添加失败！");
				}
			} else {
				$this->error($this->barticleclass_model->getError());
			}
		}else{
			$parentid = I("get.parent",0,'intval');
			$tree=$this->barticleclass_model->get_articleclass_tree($parentid);
			$this->assign("terms_tree",$tree);
			$this->assign("parent",$parentid);
			$this->display();
		}

	}
	// 文章分类编辑
	public function edit(){
		if (IS_POST) {
			if ($this->barticleclass_model->create()!==false) {
				if ($this->barticleclass_model->save()!==false) {
					F('all_barticle_class',null);
					$this->success("修改成功！",U("BArticleClass/index"));
				} else {
					$this->error("修改失败！");
				}
			} else {
				$this->error($this->barticleclass_model->getError());
			}
		}else{
			$id = I("get.id",0,'intval');
			$data=$this->barticleclass_model->where(array("ac_id" => $id,"company_id"=>get_company_id()))->find();
			$tree=$this->barticleclass_model->get_edit_articleclass_tree($id,$data);
			$this->assign("terms_tree",$tree);
			$this->assign("data",$data);
			$this->display();
		}
	}

	// 文章分类排序
	public function listorders() {
		$status = parent::_listorders($this->barticleclass_model);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}
	
	// 删除文章分类
	public function delete() {
		$id = I("get.id",0,'intval');
		$count = $this->barticleclass_model->where(array("parent" => $id))->count();
		
		if ($count > 0) {
			$this->error("该菜单下还有子类，无法删除！");
		}
		
		if ($this->barticleclass_model->delete($id)!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
}