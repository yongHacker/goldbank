<?php
namespace System\Controller;
use Common\Controller\SystembaseController;
class AppVersionController extends SystembaseController {
	protected $av_model;
	public function __construct() {
        parent::__construct();
		$this->av_model=D("System/AAppVersion");
    }
	//记录列表
	public function index(){
		$name=$_REQUEST["search_value"];
		if($name){
			$a=substr_count("ios",strtolower($name));
			$b=substr_count("android",strtolower($name));
			if(!($a>0 && $b>0)){
				if($a>0){
					$condition1['app_type']=1;
				}
				if($b>0){
					$condition1['app_type']=2;
				}
			}
			$condition1['_logic']="or";
			$condition1["app_version|app_address|update_content"]=array("like","%$name%");
			$condition[]=$condition1;
		}
		$condition["deleted"]=0;
		$count      =$this->av_model->countList($condition);// 查询满足要求的总记录数
		$numpage = 10;		//每页显示条数
		$page       = $this->page($count,$numpage);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show       = $page->show("Admin");// 分页显示输出
		$limit=$page->firstRow.','.$page->listRows;
		$result=$this->av_model->getList($condition,"*",$limit,"","id desc");
		$this->assign('data',$result);
		$this->assign('page',$show);
		$this->assign("search_value",$name);
		$this->assign("setting_url","appversion_index");
		$this->display();
	}
	//添加
	public function add(){
		if($_POST){
			$getdata=I("");
			$data["app_type"]=$getdata["app_type"];
			$data["app_version"]=$getdata["app_version"];
			$data["app_address"]=$getdata["address"];
			$data["update_content"]=$getdata["update_content"];
			$data["update_time"]=time();
			$data["update_status"]=$getdata["update_status"];
			$data["status"]=$getdata["status"];
			$data["deleted"]=0;
			$result=$this->av_model->insert($data);
			if($result){
				$info['status']='1';
				$info['url']=U('AppVersion/index');
                $info['msg']='添加成功！';  
			}else{
				$info['status']='0';
                $info['msg']='添加失败！';  
            }
            die(json_encode($info,true));
			
		}else{
			$this->assign("setting_url","appversion_add");
			$this->display();
		}	
	}
	//修改
    public function edit(){
    	if($_POST){
    		$getdata=I("");
    		$condition["id"]=$getdata["id"];
			$data["app_type"]=$getdata["app_type"];
			$data["app_version"]=$getdata["app_version"];
			$data["app_address"]=$getdata["address"];
			$data["update_content"]=$getdata["update_content"];
			$data["update_time"]=time();
			$data["update_status"]=$getdata["update_status"];
			$data["status"]=$getdata["status"];
			$result=$this->av_model->update($condition,$data);
			if($result!==false){
				$info['status']='1';
				$info['url']=U('AppVersion/index');
                $info['msg']='更新成功！';  
			}else{
				$info['status']='0';
                $info['msg']='更新失败！';  
            }
            die(json_encode($info,true));
    	}else{
    		$getdata=I("");
	    	$condition["id"]=$getdata["id"];
			$condition["deleted"]=0;
	    	$result=$this->av_model->getInfo($condition);
			$this->assign('data',$result);
			$this->assign("setting_url","appversion_edit");
			$this->display();
    	}
    }
}