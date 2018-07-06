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

class BArticleController extends BusinessbaseController {
    
	protected $posts_model;
	protected $term_relationships_model;
	protected $terms_model;
	
	function _initialize() {
		parent::_initialize();
		$this->posts_model = D("BArticle");
		$this->terms_model = D("BArticleClass");
		//$this->term_relationships_model = D("BTermRelationships");
	}
	
	// 后台文章管理列表
	public function index(){
		$ac_id=I('request.term',0,'intval');
		$this->_lists(array("a.article_status"=>array('neq',3),'a.company_id'=>get_company_id()));
		$this->_getTree($ac_id);
		$this->display();
	}
	// 后台文章展示
	public function show(){
		//C('SP_DEFAULT_THEME','simplebootx');
		$article_id=I('get.id',0,'intval');
		$term_id=I('get.cid',0,'intval');
		$article=$this->posts_model
			->alias("a")
			//->field('a.*,c.user_login,c.user_nicename')
			->field('a.*,c.user_login,b_employee.employee_name user_nicename')
			->join("left join ".DB_PRE."b_article_class b ON a.ac_id = b.ac_id")
			->join("left join ".DB_PRE."b_employee b_employee ON b_employee.user_id = a.article_author and  b_employee.deleted=0 and b_employee.company_id=".get_company_id())
			->join("left join __M_USERS__ c ON a.article_author = c.id")
			->where(array('a.id'=>$article_id,'a.ac_id'=>$term_id))
			->find();
		if(empty($article)){
			header('HTTP/1.1 404 Not Found');
			header('Status:404 Not Found');
			if(sp_template_file_exists(MODULE_NAME."/404")){
				$this->display(":404");
			}
			return;
		}
		$this->posts_model->where(array('id'=>$article_id))->setInc('article_hits');
		$article_date=$article['create_time'];
		$next=$this->posts_model
			->alias("a")
			->where(array("create_time"=>array("egt",$article_date), "id"=>array('neq',$article_id), "a.deleted"=>0,'a.ac_id'=>$term_id,'article_status'=>1))
			->order("sort asc,create_time asc")
			->find();

		$prev=$this->posts_model
			->alias("a")
			->where(array("create_time"=>array("elt",$article_date), "id"=>array('neq',$article_id), "a.deleted"=>0,'a.ac_id'=>$term_id,'article_status'=>1))
			->order("sort desc,create_time desc")
			->find();

		$this->assign("next",$next);
		$this->assign("prev",$prev);

		$smeta=json_decode($article['article_pic'],true);
		$content_data=sp_content_page($article['article_content']);
		$article['article_content']=$content_data['content'];

		$this->assign("page",$content_data['page']);
		$this->assign($article);
		$this->assign("smeta",$smeta);
		$this->assign("article_id",$article_id);
		$this->display();
	}
	// 文章添加
	public function add(){
		if (IS_POST) {
			if(empty($_POST["post"]['ac_id'])){
				$this->error("请至少选择一个分类！");
			}
			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			 
			$_POST['post']['update_time']=time();
			$_POST['post']['create_time']=time();
			$_POST['post']['article_author']=get_user_id();
			$article=I("post.post");
			$article['article_pic']=json_encode($_POST['smeta']);
			$article['article_content']=htmlspecialchars_decode($article['article_content']);
			$result=$this->posts_model->add($article);
			if ($result) {
				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
		}else{
			$terms = $this->terms_model->order(array("listorder"=>"asc"))->select();
			$ac_id = I("get.term",0,'intval');
			$this->_getTree($ac_id);
			$term=$this->terms_model->where(array('ac_id'=>$ac_id))->find();
			$this->assign("term",$term);
			$this->assign("terms",$terms);
			$this->display();
		}
	}
	
	// 文章编辑
	public function edit(){
		if (IS_POST) {
			if(empty($_POST["post"]['ac_id'])){
				$this->error("请至少选择一个分类！");
			}
			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			unset($_POST['post']['article_author']);
			$_POST['post']['update_time']=time();
			//$_POST['post']['create_time']=time();
			$article=I("post.post");
			$article['article_pic']=json_encode($_POST['smeta']);
			$article['article_content']=htmlspecialchars_decode($article['article_content']);
			$result=$this->posts_model->save($article);
			if ($result!==false) {
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}else{
			$id=  I("get.id",0,'intval');
			$post=$this->posts_model->where("id=$id")->find();
			$this->_getTree($post["ac_id"]);
			$terms=$this->terms_model->select();
			$this->assign("post",$post);
			$this->assign("smeta",json_decode($post['article_pic'],true));
			$this->assign("terms",$terms);
			$this->display();
		}
	}
	
	// 文章排序
	public function listorders() {
		$status = parent::_listorders($this->posts_model,'sort');
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}
	
	/**
	 * 文章列表处理方法,根据不同条件显示不同的列表
	 * @param array $where 查询条件
	 */
	private function _lists($where=array()){
		$ac_id=I('request.term',0,'intval');

		if(!empty($ac_id)){
		    $where['b.ac_id']=$ac_id;
			$term=$this->terms_model->where(array('ac_id'=>$ac_id))->find();
			$this->assign("term",$term);
		}
		
		$start_time=I('request.start_time');
		if(!empty($start_time)){
		    $where['a.create_time']=array(
		        array('EGT',strtotime($start_time))
		    );
		}
		
		$end_time=I('request.end_time');
		if(!empty($end_time)){
		    if(empty($where['a.create_time'])){
		        $where['a.create_time']=array();
		    }
		    array_push($where['a.create_time'], array('ELT',strtotime($end_time)));
		}
		
		$keyword=I('request.keyword');
		$keyword=trim($keyword);
		if(!empty($keyword)){
		    $where['article_title']=array('like',"%$keyword%");
		}
			
		$this->posts_model
		->alias("a")
		->where($where);
		
		if(!empty($ac_id)){
		    $this->posts_model->join(DB_PRE."b_article_class b ON a.ac_id = b.ac_id");
		}
		
		$count=$this->posts_model->count();
			
		$page = $this->page($count, 20);
			
		$this->posts_model
		->alias("a")
		->join(DB_PRE."m_users c ON a.article_author = c.id")
		->join("left join ".DB_PRE."b_employee b_employee ON b_employee.user_id = a.article_author and  b_employee.deleted=0 and b_employee.company_id=".get_company_id())
		->where($where)
		->limit($page->firstRow , $page->listRows)
		->order("a.create_time DESC");
		if(empty($ac_id)){
		    $this->posts_model->field('a.*,c.user_login,b_employee.employee_name user_nicename');
		}else{
		    $this->posts_model->field('a.*,c.user_login,b_employee.employee_name user_nicename,b.listorder');
		    $this->posts_model->join(DB_PRE."b_article_class b ON a.ac_id = b.ac_id");
		}
		$posts=$this->posts_model->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign("formget",array_merge($_GET,$_POST));
		$this->assign("posts",$posts);
	}
	
	// 获取文章分类树结构 select 形式
	private function _getTree($term=0){
		$parentid = I("get.parent",0,'intval');
		$term=empty($parentid)?$term:$parentid;
		$tree=$this->terms_model->get_articleclass_tree($term);
		$this->assign("taxonomys", $tree);
	}

	// 文章删除
	public function delete(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->posts_model->where(array('id'=>$id))->save(array('article_status'=>3)) !==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->posts_model->where(array('id'=>array('in',$ids)))->save(array('article_status'=>3))!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}
	
	// 文章审核
	public function check(){
		if(isset($_POST['ids']) && $_GET["check"]){
		    $ids = I('post.ids/a');
			
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('article_status'=>1)) !== false ) {
				$this->success("审核成功！");
			} else {
				$this->error("审核失败！");
			}
		}
		if(isset($_POST['ids']) && $_GET["uncheck"]){
		    $ids = I('post.ids/a');
		    
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('article_status'=>0)) !== false) {
				$this->success("取消审核成功！");
			} else {
				$this->error("取消审核失败！");
			}
		}
	}
	
	// 文章批量移动
	public function move(){
		if(IS_POST){
			if(isset($_GET['ids']) && $_GET['old_ac_id'] && isset($_POST['ac_id'])){
			    $old_ac_id=I('get.old_ac_id',0,'intval');
			    $ac_id=I('post.ac_id',0,'intval');
			    if($old_ac_id!=$ac_id){
			        $ids=explode(',', I('get.ids/s'));
			        $ids=array_map('intval', $ids);
			         
			        foreach ($ids as $id){
						$savedata["ac_id"]=$ac_id;
			            $this->posts_model->where(array('id'=>$id,'ac_id'=>$old_ac_id))->save($savedata);
			        }
			    }
			    $this->success("移动成功！");
			}
		}else{
			$this->_getTree(I("old_ac_id"));
			$this->display();
		}
	}
	

	
	// 文章回收站列表
	public function recyclebin(){
		$this->_lists(array('article_status'=>array('eq',3)));
		$this->_getTree();
		$this->display();
	}
	
	// 清除已经删除的文章
	public function clean(){
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			$ids = array_map('intval', $ids);
			$status=$this->posts_model->where(array("id"=>array('in',$ids),'article_status'=>3))->delete();
			//$this->term_relationships_model->where(array('object_id'=>array('in',$ids)))->delete();
			
			if ($status!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}else{
			if(isset($_GET['id'])){
				$id = I("get.id",0,'intval');
				$status=$this->posts_model->where(array("id"=>$id,'article_status'=>3))->delete();
				//$this->term_relationships_model->where(array('object_id'=>$id))->delete();
				
				if ($status!==false) {
					$this->success("删除成功！");
				} else {
					$this->error("删除失败！");
				}
			}
		}
	}
	
	// 文章还原
	public function restore(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->posts_model->where(array("id"=>$id,'article_status'=>3))->save(array("article_status"=>"1"))) {
				$this->success("还原成功！");
			} else {
				$this->error("还原失败！");
			}
		}
	}
	
}