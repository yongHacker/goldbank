<?php
/**
 * 商品列表管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BGoodsCommonController extends BusinessbaseController {

	public function _initialize() {
		parent::_initialize();
		$this->bgoodscommon_model = D("BGoodsCommon");
		$this->bgoodsclass_model = D("BGoodsClass");
		$this->bmetaltype_model = D("BMetalType");
		$this->bbankgoldtype_model = D("BBankGoldType");
		$this->astatus_model = D("AStatus");
		$this->bgoodspic = D("BGoodsPic");
	}
	// 处理列表表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL){
		$getdata=I("");
		$condition=array();
		if($getdata['id']){
			$getdata["class_id"]=$getdata["id"];
		}

		if($getdata["class_id"]){
			$class_id = $getdata["class_id"];
			$class_list = $this->bgoodsclass_model->getALLGoodsClass($class_id, array());
			$class_id_list = '0,' . $class_id;
			foreach ($class_list as $key => $val) {
				$class_id_list .= ',' . $val['id'];
			}
			$condition["bgoodscommon.class_id"]=array('in',$class_id_list);
		}
		if($getdata["search_name"]){
			$condition["bgoodscommon.goods_name|bgoodscommon.tag_name|bgoodscommon.goods_code"]=array("like","%".$getdata["search_name"]."%");
		}
		$ex_where = array_merge($condition, $ex_where);
		$request_data = $_REQUEST;
		$this->assign('request_data', $request_data);
	}
	/**
	 * 商品列表展示
	 */
	public function index() {

		$getdata=I("");
		$condition=array("bgoodscommon.company_id"=>$this->MUser['company_id'],"bgoodscommon.deleted"=>0);
		$this->handleSearch($condition);
		/*if($getdata['id']){
			$getdata["class_id"]=$getdata["id"];
		}
		if($getdata["class_id"]){
			$condition["bgoodscommon.class_id"]=$getdata["class_id"];
		}
		if($getdata["search_name"]){
			$condition["bgoodscommon.goods_name"]=array("like","%".$getdata["search_name"]."%");
		}*/
		$join="left join ".DB_PRE."b_goods_class bgoodsclass on bgoodscommon.class_id=bgoodsclass.id";
		$count=$this->bgoodscommon_model->alias("bgoodscommon")->countList($condition,$field='bgoodscommon.*,bgoodsclass.class_name',$join,$order='bgoodscommon.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$join.=" left join ".DB_PRE."b_goods bgoods on bgoodscommon.id=bgoods.goods_common_id and bgoods.deleted=0";
		$data=$this->bgoodscommon_model->alias("bgoodscommon")->getList($condition,$field='bgoodscommon.*,bgoodsclass.class_name,count(bgoods.id) as goods_num',$limit,$join,$order='bgoodscommon.id desc',$group="bgoodscommon.id");
		foreach($data as $k=>$v){
			$condition=array("goods_id"=>$v["id"],"type"=>1);
			$data[$k]["pic"]=M("b_goods_pic")->where($condition)->order("is_hot desc")->getField("pic");
		}
		$sid=empty($getdata["class_id"])?0:$getdata["class_id"];
		//获取B分类
		$select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid);
		$this->assign("select_categorys", $select_categorys);
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}
	//商品添加
	public function add_first() {
		$postdata = I("");
		if(empty($postdata)){
			//获取B分类
			$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0);
			$select_categorys=$this->bgoodsclass_model->getList($condition,$field='*',$limit="",$join='');
			$this->assign("bgoodsclass", json_encode($select_categorys));
			$info=$this->bgoodsclass_model->getInfo($condition,$field='*',$join="",$order="level desc");
			$width=810/($info['level']+1);
			$html='<ul id="sort1" style="width: '.$width.'px"></ul>';
			for($i=1;$i<=$info['level'];$i++){
				$num=$i+1;
				$html.='<ul id="sort'.$num.'" style="display: none;width: '.$width.'px"></ul>';
			}
			$this->assign("classhtml", $html);
			$this->assign("num", $info['level']);
			$this->display();
		}else{
			$this->error("参数错误");
		}
	}
	//商品添加
	public function add() {
		$postdata = I("post.");
		if(empty($postdata)){
			//获取B分类
			$select_categorys = $this->bgoodsclass_model->get_b_goodsclass();
			$this->assign("select_categorys", $select_categorys);
			//获取金属类型
			$parentid = I("get.parent",0,'intval');
			$tree = $this->bmetaltype_model->get_metaltype_tree($parentid);
			$this->assign("cate_list", $tree);
			//获取金价类型
			$condition = array("company_id" => $this->MUser['company_id'], "deleted" => 0, 'status' => 1);
			$bank_gold_type_list = $this->bbankgoldtype_model->getList($condition, $field='*', $limit="", $join='');
			$this->assign("bank_gold_type_list", $bank_gold_type_list);
			$this->assign("bank_gold_type_json", json_encode($bank_gold_type_list));

			// 分类树
			$class_nav = $this->bgoodsclass_model->classNav(I("get.class_id", 0, 'intval'));
			// 商品大类列表
			$class_status = $this->_get_class_status();
			$type = I("type");
			switch($type){
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					$this->assign('goods_class_name', $class_nav . ' (' . $class_status[$type] . '类)');
					$this->display("inlay_add");
					break;
				// 素金以及其它
				default:
					$this->assign('goods_class_name', $class_nav . ' (' . $class_status[1] . '类)');
					$this->display("gold_add");
			}
		} else {
			if (IS_POST) {
				$data=array();
				$goods_common_info=$postdata["goods_common_info"];
				$goods_list=$postdata["goods_list"];
				$goods_imgs=$postdata["goods_img"];
				$data["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
				$data["class_id"]=$goods_common_info["class_id"];
				$data["goods_code"]=trim($goods_common_info["goods_common_code"]);
				$data["goods_name"]=$goods_common_info["goods_common_name"];
				$data["tag_name"]=$goods_common_info["tag_name"];
				$data["moral"]=$goods_common_info["moral"];
				$data["description"]=$goods_common_info["description"];
				//$data["is_standard"]=$goods_common_info["is_standard"];
				//$data["mobile_show"]=$goods_common_info["mobile_show"];
				$data["update_time"]=time();
				$data["create_time"]=time();
				$data["type"]=$goods_common_info["type"];
				$data["sell_type"]=$goods_common_info["sell_type"];
				$data["deleted"]=0;
				$is_exsit=$this->bgoodscommon_model->goodscode_is_exsit(array('goods_code'=>$goods_common_info["goods_common_code"]));
				if($is_exsit){
					$this->error("商品编码已经存在！");
				}
				M()->startTrans();
				$goods_common_id=$this->bgoodscommon_model->insert($data);
				if ($goods_common_id!==false) {
					if(!empty($goods_imgs)){
						$bgoodspic=$this->bgoodscommon_model->add_common_img($goods_common_id,$goods_imgs,$goods_common_info['goods_default_img']);
						if ($bgoodspic==false) {
							M()->rollback();
							$this->error("图片添加失败！");
						}
					}
					if($goods_common_info["sell_type"]==1){
						$bgoods=$this->bgoodscommon_model->add_goods_list($goods_common_id,$goods_list,$goods_common_info);
					}else{
						$bgoods=$this->bgoodscommon_model->add_wgoods_list($goods_common_id,$goods_list,$goods_common_info);
					}

					if ($bgoods['status']!==false) {
						M()->commit();
						$this->success("添加成功！", U("BGoodsClass/index"));
					} else {
						M()->rollback();
						$this->error($bgoods['info']);
					}
				} else {
					M()->rollback();
					$this->error("添加失败！");
				}
			}
		}
	}
	//镶嵌商品添加
	/*public function inlay_add() {
		$postdata=I("");
		if(empty($postdata)){
			//获取B分类
			$select_categorys=$this->bgoodsclass_model->get_b_goodsclass();
			$this->assign("select_categorys", $select_categorys);
			//获取金属类型
			$parentid = I("get.parent",0,'intval');
			$tree=$this->bmetaltype_model->get_metaltype_tree($parentid);
			$this->assign("cate_list",$tree);
			//获取金价类型
			$condition=array("company_id"=>$this->MUser['company_id'],"deleted"=>0,'status'=>1);
			$bank_gold_type_list=$this->bbankgoldtype_model->getList($condition,$field='*',$limit="",$join='');
			$this->assign("bank_gold_type_list", $bank_gold_type_list);
			$this->assign("bank_gold_type_json", json_encode($bank_gold_type_list));
			$this->display();
		}else{
			if (IS_POST) {
				$data=array();
				$goods_common_info=$postdata["goods_common_info"];
				$goods_list=$postdata["goods_list"];
				$goods_imgs=$postdata["goods_img"];
				$data["company_id"]=$this->MUser["company_id"];//$postdata["company_id"];
				$data["class_id"]=$goods_common_info["class_id"];
				$data["goods_code"]=$goods_common_info["goods_common_code"];
				$data["goods_name"]=$goods_common_info["goods_common_code"];
				$data["moral"]=$goods_common_info["moral"];
				$data["description"]=$goods_common_info["description"];
				$data["is_standard"]=$goods_common_info["is_standard"];
				$data["mobile_show"]=$goods_common_info["mobile_show"];
				$data["update_time"]=time();
				$data["create_time"]=time();
				$data["deleted"]=0;
				$goods_common_id=$this->bgoodscommon_model->insert($data);
				if ($goods_common_id!==false) {
					if(!empty($goods_imgs)){
						$bgoodspic=$this->bgoodscommon_model->add_common_img($goods_common_id,$goods_imgs,$goods_common_info['goods_default_img']);
						if ($bgoodspic==false) {
							$this->error("图片添加失败！");
						}
					}
					$bgoods=$this->bgoodscommon_model->add_goods_list($goods_common_id,$goods_list,$goods_common_info['goods_common_name']);
					if ($bgoods!==false) {
						$this->success("添加成功！", U("BGoodsClass/index"));
					} else {
						$this->error("商品规格添加失败！");
					}
				} else {
					$this->error("添加失败！");
				}
			}
		}
	}*/
	//商品编辑
	public function edit() {
		$postdata=I("post.");
		$getdata=I("get.");
		if(empty($postdata)){
			//获取商品信息
			$condition=array("deleted"=>0,"id"=>$getdata["goods_common_id"]);
			$goods_common=D("BGoodsCommon")->getInfo($condition,$field='*',$join="");
			$data=$this->bgoodscommon_model->get_edit_info($getdata);

			//获取金属类型
			$this->assign("cate_list", $data["cate_list"]);
			//获取金价类型
			$this->assign("bank_gold_type_list",  $data["bank_gold_type_list"]);
			$this->assign("bank_gold_type_json",  json_encode($data["bank_gold_type_list"]));
			//获取商品信息和获取商品所有规格信息
			$this->assign("goods_detail",  $data["goods_detail"]);
			//获取B端商品分类
			$this->assign("select_categorys",  $data["select_categorys"]);
			//获取商品图片
			$this->assign("goods_img",  $data["goods_img"]);
			// 分类树
			$class_nav = $this->bgoodsclass_model->classNav($goods_common["class_id"]);
			// 商品大类列表
			$class_status = $this->_get_class_status();
			$type = $data['goods_detail']['type'];
			switch($type){
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					$this->assign('goods_class_name', $class_nav . ' (' . $class_status[$type] . '类)');
					$this->display("inlay_edit");
					break;
				// 素金以及其它
				default:
					$this->assign('goods_class_name', $class_nav . ' (' . $class_status[1] . '类)');
					$this->display("gold_edit");
			}
		}else{
			if (IS_POST) {
				// $type=I('get.type');
				$data = array ();
				$goods = I ( 'post.goods_common_info' );
				$goods ['goods_list'] = I ( 'post.goods_list' );
				$goods ['del_goods_id'] = I ( 'post.del_goods_id' );
				$goods['goods_common_code']=trim($goods['goods_common_code']);
				//获取商品信息
				$condition=array("deleted"=>0,"id"=>$goods["goods_common_id"]);
				$goods_common=D("BGoodsCommon")->getInfo($condition,$field='*',$join="");
				$goods['type']=$goods_common['type'];
				if($goods_common['type']!=$goods['class_type']&&$goods_common['class_id']!=$goods['class_id']){
					$data ['code'] = 0;
					$data['info']='不可选择其他商品大类的分类！';
					output_data ( $data );
				}
				$is_exsit=$this->bgoodscommon_model->goodscode_is_exsit(array("id"=>$goods["goods_common_id"],'goods_code'=>$goods['goods_common_code']));
				if($is_exsit){
					$data ['code'] = 0;
					$data['info']='商品编码已经存在！';
					output_data ( $data );
				}
				M()->startTrans();
				$result = D('b_goods_common')->editGoodsCommon($goods);
				if ($result['status']) {
					M()->commit();
					$data ['code'] = 1;
				} else {
					M()->rollback();
					$data ['code'] = 0;
				}
				$data ['info'] = $result['info'];
				output_data ( $data );
			}
		}
	}
	//商品详情
	public function detail() {
		$postdata=I("post.");
		$getdata=I("get.");
		if(empty($postdata)){
			//获取商品信息
			$condition=array("deleted"=>0,"id"=>$getdata["goods_common_id"]);
			$goods_common=D("BGoodsCommon")->getInfo($condition,$field='*',$join="");
			if($goods_common['sell_type']==1){
				$data=$this->bgoodscommon_model->get_edit_info($getdata);
			}else{
				$data=$this->bgoodscommon_model->get_wgoods_edit_info($getdata);
			}
			//获取金属类型
			$this->assign("cate_list", $data["cate_list"]);
			//获取金价类型
			$this->assign("bank_gold_type_list", $data["bank_gold_type_list"]);
			$this->assign("bank_gold_type_json", json_encode($data["bank_gold_type_list"]));
			//获取商品信息和获取商品所有规格信息
			$this->assign("goods_detail",  $data["goods_detail"]);
			//获取B端商品分类
			$this->assign("select_categorys",  $data["select_categorys"]);
			//获取商品图片
			$this->assign("goods_img",  $data["goods_img"]);
			// 分类树
			$class_nav = $this->bgoodsclass_model->classNav($goods_common["class_id"]);
			// 商品大类列表
			$class_status = $this->_get_class_status();
			$type=$data['goods_detail']['type'];
			switch($type){
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					$this->assign('goods_class_name', $class_nav . ' (' . $class_status[$type] . '类)');
					$this->display("inlay_detail");
					break;
				// 素金以及其它
				default:
					$this->assign('goods_class_name', $class_nav . ' (' . $class_status[1] . '类)');
					$this->display("gold_detail");
			}
		} else {
			$this->assign('goods_class_name',"素金类");
			$this->display("gold_detail");
		}
	}
	// 逻辑删除商品
	public function delGoodsCommon() {
		$goods_common_id = I ( 'goods_common_id',0,'intval' );
		$condition = array (
			'goods_common_id' => $goods_common_id,
			'deleted'=>0
		);
		$goods_info = D ( 'BGoods' )->getInfo( $condition);
		$wgoods_info = D ( 'BWgoods' )->getInfo( $condition);
		$data = array ();
		if (! empty ( $goods_info)||! empty ( $wgoods_info)) {
			$data ['status'] = 0;
			$data ['msg'] = '该商品公共下存在商品规格';
		} else {
			$condition=array ('id' => $goods_common_id,"company_id"=>get_company_id());
			$result = D ( 'BGoodsCommon' )->update( $condition,array("deleted"=>1));
		}
		if ($result > 0 || $result === 0) {
			$data ['status'] = 1;
			$data ['msg'] = '删除成功！';
			$data ['url'] = U("BGoodsCommon/index");
			$this->ajaxReturn($data );
		} else {
			$this->error($data['msg']) ;
		}
	}
	//商品公共图片
	function common_goods_img(){
		$rd['status'] = -1;
		$data['type'] = I('type');
		$data['id'] = (int)I('id');
		$data['goods_img'] = I('goods_img');
		$res = $this->bgoodscommon_model->common_goods_img($data);
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
            $data['type'] = trim($_POST['type']);
            $data['id'] = empty($_POST['id']) ? intval($_GET['goods_common_id']) : intval($_POST['id']);
            $data['pic'] = $result['filename'];
            $res = $this->bgoodscommon_model->common_goods_img($data);
            if($res){
                output_data ( array (
                    'file_name' => $result['filename'],
                    'id' => $res,
                    'code' => 1,
                    'msg' => '文件上传成功'
                ) );
            }else{
                output_data(array(
                    'code' => 0,
                    'msg' => '文件写入数据库失败！',
                ));
            }

	    } else {
	        output_data ( array (
	            'code' => 0,
	            'msg' => $result['msg'].'(文件写入服务器失败！)'
	        ) );
	    }
	}
	// 上传商品
	public function upload_goods_img_old() {
		$dirpath = $_SERVER ['DOCUMENT_ROOT'] . __ROOT__ . '/data/upload/bgoods_common/';
		if (! is_dir ( $dirpath )) {
			mkDirs ( $dirpath );
		}
		$dir_count = getdircount ( $dirpath );
		$dir_path = "";
		if ($dir_count > 0) {
			$dir_path = $dirpath . $dir_count . "/";
			$file_count = getfilecounts ( $dir_path );
			if ($file_count >= C ( 'MAX_FILE_COUNT' )) {
				$dir_path = $dirpath . ($dir_count + 1) . "/";
				//mkDirs ( $dir_path );
			}
		} else {
			$dir_path = $dirpath . "1/";
		}
		if (!file_exists($dir_path)) {
			mkDirs ( $dir_path );
		}
		$type = explode ( '/', $_FILES ['goods_pic'] ['type'] );
		$file_name = $dir_path . time () . '.' . $type [1];
		move_uploaded_file ( $_FILES ["goods_pic"] ["tmp_name"], $file_name );
		$result = file_exists ( $file_name );
		$file_name = str_replace ( $_SERVER ['DOCUMENT_ROOT'], ''/*"http://" . $_SERVER ['HTTP_HOST']*/, $file_name );
		if ($result) {
			output_data ( array (
				'file_name' => $file_name,
				'code' => 1
			) );
		} else {
			output_data ( array (
				'code' => 0,
				'msg' => '上传失败！'
			) );
		}
	}
	//是否上架微信
	public function weixin() {
		$postdata=I("");
		$data=array();
		$data["mobile_show"]=empty($postdata['mobile_show'])?1:0;
		$data["update_time"]=time();
		if(empty($postdata["id"])){
			$this->error("上架失败！");
		}else{
			$condition=array("company_id"=>$this->MUser['company_id'],'id'=>$postdata["id"]);
			$bgoodscommon=$this->bgoodscommon_model->update($condition,$data);
		}
		if ($bgoodscommon!==false) {
			$this->success("上架成功！", U("BGoodsCommon/index"));
		} else {
			$this->error("上架失败！");
		}
	}
	//导出货品列表数据
	function export_excel($page=1){
		$condition=array("bgoodscommon.company_id"=>get_company_id(),"bgoodscommon.deleted"=>0);
		$this->handleSearch($condition);
		$this->bgoodscommon_model->excel_out($condition);
	}
	// 获取商品大类类型
	private function _get_class_status()
	{

        $condition['table'] = C("DB_PREFIX") . 'a_goods_class';
        $condition['field'] = 'type';
        $value_condition['status'] = 0;
        $status_list = $this->astatus_model->getStatusInfo($condition, $value_condition);
        foreach ($status_list as $key => $value) {
        	$class_status[$value['value']] = $value['comment'];
        }
        return $class_status;
    }

    /**
     *产品介绍图片删除
     * @author dengzs @date 2018/7/6 14:38
     */
    public function del_pic(){
	    $filename = I('post.filename');
        if(b_del_pic($filename)){
            $this->ajaxReturn(['status'=>1,'msg'=>'图片文件删除成功']);
        }
        $this->ajaxReturn(['status'=>0,'msg'=>'图片文件删除失败']);
    }


}

