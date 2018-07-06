<?php
/**
 * 商品分类管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BGoodsClassController extends BusinessbaseController {
	
    private $bgoodsclass_model,$bgoodscommon_model,$agoodsclass_model;
	public function _initialize() {
		$this->bgoodsclass_model=D("BGoodsClass");
		$this->bgoodscommon_model=D("BGoodsCommon");
		$this->agoodsclass_model=D("AGoodsClass");
		parent::_initialize();
	}
	/**
	 * 商品分类展示
	 */
	public function index() {
		$this->display();
	}
	//商品分类数据获取
	public function bgoodsclassData() {
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
		$categories = $this->bgoodsclass_model->getList($condition,$field='*,class_name as name',$limit=null,$join='',$order='id asc',$group='');
		$tree=$this->bgoodsclass_model->get_tree_arr($categories);
		$data=$this->bgoodsclass_model->get_attr($tree,0);
		echo json_encode($data);
	}

	//商品分类添加
	public function add() {
		$postdata = I("");
		if(empty($postdata)){
			//商品分类类型
			$statuscondition["table"] = DB_PRE."b_goods_class";
			$statuscondition["field"] = "type";
			$statusall = D("b_status")->getStatusInfo($statuscondition);
			$this->assign("statusall", $statusall);
			//获取B分类
			$select_categorys=$this->bgoodsclass_model->get_b_goodsclass();
			//获取A分类
			$Aselect_categorys=$this->bgoodsclass_model->get_a_goodsclass();
			$this->assign("select_categorys", $select_categorys);
			$this->assign("aselect_categorys", $Aselect_categorys);
			$this->display();
		}else{
			if (IS_POST) {
				// 默认选中的为素金
// 				$goods_type = I('post.type/d', 1);
				// if($postdata['type'] == 0 || $postdata['type'] == ''){
				// 	$this->error("请选择关联分类");
				// }
                if($this->_is_exsit($postdata["class_name"])){
                    $this->error("已存在相同名称的商品分类！");
                }
				if ($this->bgoodsclass_model->create()!==false) {
					$data = array();
					$data["company_id"] = $this->MUser["company_id"];//$postdata["company_id"];
					$data["agc_id"] = $postdata["agc_id"];
					$data["pid"] = $postdata["pid"];
					$data["class_name"] = $postdata["class_name"];
					$data["descript"] = $postdata["descript"];
					$data["photo"] = $postdata["photo"];
					$data["create_time"] = time();
					$data["deleted"] = 0;
					$agc_info=$this->agoodsclass_model->getInfo(array('id'=>$postdata["agc_id"]));
					$data['type'] = $agc_info['type'];
					$bgoodsclass = $this->bgoodsclass_model->insert($data);
					if ($bgoodsclass !== false) {
						$this->success("添加成功！", U("BGoodsClass/index"));
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->bgoodsclass_model->getError());
				}
			}
		}
	}
	private function _is_exsit($class_name){
	    $condition=array(
	        'company_id'=>$this->MUser['company_id'],
	        'class_name'=>$class_name,
	        'deleted'=>0,
	    );
	    $class_info=$this->bgoodsclass_model->getInfo($condition);
	    return !empty($class_info)?true:false;
	}
	//商品分类编辑
	public function edit() {
		$postdata=I("post.");
		$gettdata=I("get.");
		if(empty($postdata)){
			//商品分类类型
			$statuscondition["table"] = DB_PRE."b_goods_class";
			$statuscondition["field"] = "type";
			$statusall = D("b_status")->getStatusInfo($statuscondition);
			$this->assign("statusall", $statusall);
			//获取一条分类信息
			$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0,"id"=>$gettdata["id"]);
			$categories = $this->bgoodsclass_model->getInfo($condition,$field='*',$join='',$order='id asc');
			//获取B分类
			$select_categorys=$this->bgoodsclass_model->get_b_goodsclass($categories["pid"],$where=array("id"=>array("neq",$gettdata["id"])));
			//获取A分类
			$Aselect_categorys=$this->bgoodsclass_model->get_a_goodsclass($categories["agc_id"]);
			//判断是否已经存在商品
			$count_goodscommon=D('BGoodsCommon')->countList(array('class_id'=>$gettdata["id"],'deleted'=>0,'company_id'=>get_company_id()));
			$this->assign("count_goodscommon", $count_goodscommon);
			$this->assign("data", $categories);
			$this->assign("select_categorys", $select_categorys);
			$this->assign("aselect_categorys", $Aselect_categorys);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bgoodsclass_model->create()!==false) {
					$data=array();
					$data["agc_id"]=$postdata["agc_id"];
					$data["pid"]=$postdata["pid"];
					$data["class_name"]=$postdata["class_name"];
					$data["descript"]=$postdata["descript"];
					$data["photo"]=$postdata["photo"];
					$agc_info=$this->agoodsclass_model->getInfo(array('id'=>$postdata["agc_id"]));
					$data['type'] = $agc_info['type'];
					$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0,"id"=>$postdata["id"]);
					$bgoodsclass=$this->bgoodsclass_model->update($condition,$data);
					if ($bgoodsclass!==false) {
						$this->success("保存成功！", U("BGoodsClass/index"));
					} else {
						$this->error("保存失败！");
					}
				} else {
					$this->error($this->bgoodsclass_model->getError());
				}
			}
		}
	}
	//商品分类删除
	public function deleted() {
		$postdata=I("");
		//获取一条分类信息
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0,"pid"=>$postdata["id"]);
		$sub_categories = $this->bgoodsclass_model->getInfo($condition,$field='*',$join='',$order='id asc');
		if ($sub_categories) {
			$this->error("存在子分类，请先删除该类下的商品分类！");
		}
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0,"class_id"=>$postdata["id"]);
		$sub_categories = $this->bgoodscommon_model->getInfo($condition,$field='*',$join='',$order='id asc');
		if ($sub_categories) {
			$this->error("存在商品，请先删除该类下的商品！");
		}
		$data=array();
		$data["deleted"]=1;
		$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0,"id"=>$postdata["id"]);
		$bgoodsclass=$this->bgoodsclass_model->update($condition,$data);
		if ($bgoodsclass!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

    //商品公共图片
    function common_goods_img(){
        $rd['status'] = -1;
        $data['type'] = I('type');
        $data['id'] = (int)I('id');
        $data['goods_img'] = I('goods_img');

        $res = $this->bgoodsclass_model->common_goods_img($data);
        if($res){
            $rd['status'] = 1;
            $rd['id'] = $res;
        }else{
            $rd['msg'] = '失败';
        }
        output_data($rd);
    }
    /**
     * 上传商品图片
     * @author change by lzy 2018.6.30
     */
    public function upload_goods_img(){
        $type=explode('/',$_FILES['goods_pic']['type']);
        $result=b_upload_pic('goods',$_FILES['goods_pic']['tmp_name'],'thumb',$type[1]);
        if ($result['status']==1) {
            output_data ( array (
                'file_name' => $result['filename'],
                'code' => 1
            ) );
        } else {
            output_data ( array (
                'code' => 0,
                'msg' => $result['msg']
            ) );
        }
    }

}

