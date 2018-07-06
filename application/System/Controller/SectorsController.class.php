<?php
namespace System\Controller;
use Common\Controller\SystembaseController;
class SectorsController extends SystembaseController {
	public function index(){    //获取所有部门信息
		$this->display();
    }
	public function shuju(){
		//判断是否为ajax请求
		if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){ 
			 $tree = json_encode($this->gettrees());
			 echo $tree;exit;
		}	
	}
	public function add(){    //增加部门
		if(I('post.sector_pid')){
			if(I('post.sector_pid')=='top'){
				$sa['sector_pid']=0;
			}
			else{
				$sa['sector_pid']=I('post.sector_pid');
			}
			$sa['sector_name']=I('post.sector_name');
			$sa['create_time'] =time();
			$result = D('System/ASectors')->insert($sa);
			if($result !=false){
				$re['status']=1;
				$re['msg']='添加成功';
			}
			else{
				$re['status']=0;
				$re['msg']='添加失败';
			}
			$this->ajaxReturn($re);
		}
    }
	public function edit(){    //修改部门
		if(I('post.sector_pid')){
			$sa['sector_pid']=I('post.sector_pid');  //修改部门pid
			$sa['sector_name']=I('post.sector_name');
			$sa['update_time'] = time();
			$result = D('System/ASectors')->where('id='.I('post.id'))->save($sa);
			if($result !=false){
				$re['status']=1;
				$re['msg']='添加失败';
			}
			else{
				$re['status']=0;
				$re['msg']='添加失败';
			}
			$this->ajaxReturn($re);
		}
    }
	public function delete(){    //删除部门（并非真的删除，只是修改删除标识）
		if(I('post.id')){
			$re = D('System/AEmployee')->getInfo(array('sector_id='.I('post.id')));
			if(!empty($re)){
				$del['status']=0;
				$del['msg']='该部门下还有员工，不能删除该部门';
			}
			else{
				$delete['deleted']=1;
				$delete['update_time']=time();
				$d = D("System/ASectors")->update(array('id='.I('post.id')),$delete);
				if($d!==false){
					$del['status']=1;
					$del['msg']='删除成功';
				}
				else{
					$del['status']=0;
					$del['msg']='删除失败';
				}
			}
			$this->ajaxReturn($del);
		}
    }
	//遍历数组的子分类
    public function gettrees($pid=0){
        $bm = D('System/ASectors');
        $result = $bm->getList(array('sector_pid'=>$pid,'deleted'=>0));
        //遍历分类
        $arr = array();
        if($result){
	        foreach ($result as $key => $value) {
	            $temp['id'] = $value['id'];
	            $temp['text'] = $value['sector_name'];
	            $temp['bm_pid']=$value['sector_pid'];
	            //递归获取子集分类
	            $temp['nodes'] = $this->gettrees($temp['id']);
				
	            $arr[] = $temp; 
	        }
	        return $arr;      	
        }
    }
	
	public function sortOut($cate,$pid=0,$level=0,$html='--',$id,$optype){
	   $tree = array();
	   foreach($cate as $v){
		   if($optype=='edit'){
			   if($v['id']==$id){
				  continue;
			   }
			   else if($v['pid'] == $pid){
				   $v['level'] = $level + 1;
				   $v['html'] = str_repeat($html, $level);
				   $tree[] = $v;
				   $tree = array_merge($tree, $this->sortOut($cate,$v['id'],$level+1,$html,$id,$optype));
			   }
		   }
		   else{
				if($v['sector_pid'] == $pid){
				   $v['level'] = $level + 1;
				   $v['html'] = str_repeat($html, $level);
				   $tree[] = $v;
				   $tree = array_merge($tree, $this->sortOut($cate,$v['id'],$level+1,$html,$id,$optype));
				}
		   }
	   }
	   return $tree;
    }
	function gethtmltree(){
		if(I('post.optype')){
			$id = I('post.bm_id')?I('post.bm_id'):0;
			$bm = M('a_sectors');
			if(I('post.optype')=="edit"){
				$re=$this->gettrees($id);
				if($re){
					foreach ($re as $k=>$v){
						$ids=$v['id'].",";
					}
				}
			}
			$ids.=$id;
			if($ids === $id){
				$where['id']=array("neq",$ids);
			}else{
				$where['id']=array("not in",$ids);
			};
			$where['deleted']=0;
			$result = $bm->where($where)->Field('id,sector_name,sector_pid')->select();
			$tree = $this->sortOut($result,0,0,'&nbsp;&nbsp;&nbsp;&nbsp;',I('post.id'),I('post.optype'));
			print_r(json_encode($tree));exit;
		}
	}
}