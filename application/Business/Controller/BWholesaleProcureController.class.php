<?php
/**
 * 批发商品采购管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BWholesaleProcureController extends BusinessbaseController {
	
	public function _initialize() {
		$this->bwprocure_model=D("b_wholesale_procure");
		$this->bwprocuredetail_model=D("b_wprocure_detail");
		$this->bwarehouse_model=D("BWarehouse");
		$this->bsupplier_model = D("BSupplier");
		$this->bgoodscommon_model=D("BGoodsCommon");
		$this->bwgoods_model=D("BWgoods");
		$this->bgoodsclass_model=D("BGoodsClass");
		parent::_initialize();
		$this->b_show_status("b_wholesale_procure");
	}
	// 处理列表表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL){
		$getdata=I("");
		$condition=array();
		if($getdata["search_name"]){
			$condition["bwprocure.batch|bsupplier.company_name"]=array("like","%".trim($getdata["search_name"])."%");
		}
		if(I('begin_time')){
			$begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
			$begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
			$condition['bwprocure.create_time'] = array('gt', $begin_time);
		}

		if(I('end_time')){
			$end_time = I('end_time') ? strtotime(I('end_time')) : time();
			$end_time = strtotime(date('Y-m-d 23:59:59', $end_time));
			if(isset($begin_time)){
				$p1 = $condition['bwprocure.create_time'];
				unset($condition['bwprocure.create_time']);
				$condition['bwprocure.create_time'] = array($p1, array('lt', $end_time));
			}else{
				$condition['bwprocure.create_time'] = array('lt', $end_time);
			}
		}
		$ex_where = array_merge($condition, $ex_where);
		$request_data = $_REQUEST;
		$this->assign('request_data', $request_data);
	}
	//获取销售列表数据
	public function _getlist($where=array()){
		$getdata=I("");
		$condition=array("bwprocure.company_id"=>$this->MUser['company_id'],"bwprocure.deleted"=>0);
		if(!empty($where)){
			$condition=array_merge($condition,$where);
		}
		$this->handleSearch($condition);
		$join=" left join ".DB_PRE."b_supplier bsupplier  on bsupplier.id=bwprocure.supplier_id";
		$join.=" left join ".DB_PRE."m_users check_musers on check_musers.id=bwprocure.check_id";
		$join.=" left join ".DB_PRE."m_users creator_musers on creator_musers.id=bwprocure.creator_id";
		$field="bwprocure.*";
		$field.=",bsupplier.company_name,check_musers.user_nicename check_name,creator_musers.user_nicename creator_name";
		$count=D("BWholesaleProcure")->alias("bwprocure")->countList($condition,$field,$join,$order='bwprocure.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bwprocure_model->alias("bwprocure")->getList($condition,$field,$limit,$join,$order='bwprocure.id desc');
		foreach($data as $k=>$v){
			$data[$k]["count"]=$this->bwprocuredetail_model->countList($condition=array("deleted"=>0,"procure_id"=>$v["id"]));
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
	}
	// 销售货品列表
	public function goods_list() {
		$getdata=I("");
		$condition=array("bgoodscommon.company_id"=>get_company_id(),"bgoodscommon.deleted"=>0,"bwgoods.status"=>1);
		if($getdata["class_id"]){
			$condition["bgoodscommon.class_id"]=$getdata["class_id"];
		}
		if($getdata["type"]){
			$condition["bgoodsclass.agc_id"]=$getdata["type"];
		}
		if(isset($getdata["pricemode"])){
			$condition["bwgoods.price_mode"]=$getdata["pricemode"];
		}
		$join=" left  join ".DB_PRE."b_wgoods bwgoods on bwgoods.goods_common_id=bgoodscommon.id";
		$join.=" left join ".DB_PRE."b_goods_class bgoodsclass on bgoodscommon.class_id=bgoodsclass.id";
		$field="bwgoods.*";
		$count=$this->bgoodscommon_model->alias("bgoodscommon")->countList($condition,$field,$join,$order='bgoodscommon.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bgoodscommon_model->alias("bgoodscommon")->getList($condition,$field,$limit,$join,$order='bgoodscommon.id desc',$group='');
		foreach($data as $k=>$v){
			$data[$k]["product_pic"]=$this->bwgoods_model->get_goods_pic($v['id']);
		}
		$sid=empty($getdata["class_id"])?0:$getdata["class_id"];
		$select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid);
		$this->assign("select_categorys", $select_categorys);
		$this->assign("page", $page->show('Admin'));
		$this->assign("data",$data);
		$this->assign("empty_info","暂无商品库存");
		$this->assign("today", date('Y-m-d', time()));
		$this->display();
	}
	/*********************************************************************************************************************/
	// 采购列表 - 普通列表
	public function index(){
		$this->_getlist();
		$this->display();
	}
	// 记录详情
	public function index_detail() {
		$data=$this->bwprocure_model->getInfo_detail();
		$where=array("bwprocuredetail.procure_id"=>$data["id"]);
		$detail_data=$this->bwprocuredetail_model->getList_detail($where);
		$this->assign("list",$detail_data);
		$this->assign("bwprocure",$data);
		$this->display();
	}
	// 采购审核列表 - 普通列表
	public function check(){
		$condition=array("bwprocure.status"=>0);
		$this->_getlist($condition);
		$this->assign("empty_info","暂无审核内容");
		$this->display();
	}
	// 审核记录详情
	public function check_detail() {
		$data=$this->bwprocure_model->getInfo_detail();
		$where=array("bwprocuredetail.procure_id"=>$data["id"]);
		$detail_data=$this->bwprocuredetail_model->getList_detail($where);
		$this->assign("list",$detail_data);
		$this->assign("bwprocure",$data);
		$this->display();
	}
	// 采购开单 - 计件
	public function add() {
		if(IS_POST){
			$info=$this->bwprocure_model->add_post();
			$this->ajaxReturn($info);
		}else{
			/*$filePath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/excel/'.get_company_id()."/";
			$files=$this->getFileName($filePath);
			var_dump($files);
			var_dump($filePath.$files[0]['filename']);
			unlink($filePath.$files[0]['filename']);*/
			$today = date('Y-m-d', time());

			$this->assign('today', $today);

			$this->bsupplier_model->get_list_assign($this->view);
			//获取A分类
			$select_categorys=$this->bgoodsclass_model->get_a_goodsclass();
			$this->assign("select_categorys", $select_categorys);
			$this->getExcelUrl(ACTION_NAME);

			$this->display();
		}
	}
	// 采购开单 编辑
	public function edit() {
		if(IS_POST){
			$info=$this->bwprocure_model->edit_post();
			$this->ajaxReturn($info);
		}else{
			$data=$this->bwprocure_model->getInfo_detail();
			$data['detail']=$this->bwprocuredetail_model->getList_detail(array('procure_id'=>$data["id"]));
			$this->assign("data", $data);
			$this->assign("list",$data['detail']);
			$today = date('Y-m-d', $data['procure_time']);
			$this->assign('today', $today);
			$this->bsupplier_model->get_list_assign($this->view);
			//获取A分类
			$select_categorys=D('AGoodsClass')->getInfo(array("id"=>$data['type']));
			$this->assign("select_categorys", $select_categorys);
			$this->getExcelUrl(ACTION_NAME);

			$this->display();
		}
	}
	// 批发采购撤销
	public function wprocure_delete() {
		$getdata = I("");
		$wprocure_id= I("post.id",0,'intval');
		$data["id"] = $wprocure_id;
		$data["status"] =3;
		$condition=array("id"=>$wprocure_id,"status"=>0,"company_id"=>get_company_id());
		M()->startTrans();
		$ballot_save=$this->bwprocure_model->update($condition,$data);
		if ($ballot_save!==false) {
			M()->commit();
			S('session_menu' . get_user_id(), null);
			$result["status"] = 1;
			$result["msg"] = "成功";
			$result["url"] = U("BWholesaleProcure/index");
			$this->ajaxReturn($result);
		} else {
			M()->rollback();
			$result["status"] = 0;
			$result["msg"] = "失败";
			$this->ajaxReturn($result);
		}
	}
	// 批发采购审核
	public function wprocure_check_post() {
		$getdata = I("");
		$procure_id= I("post.id",0,'intval');
		$data["id"] = $procure_id;
		$data["status"] =$getdata["type"]==1?1:2;
		$data["check_time"] = time();
		$data["check_id"] = $this->MUser['id'];
		$data["check_memo"] = I('post.check_memo');
		$condition=array("id"=>$procure_id,"status"=>0,"company_id"=>$this->MUser["company_id"]);
		M()->startTrans();
		$bwprocure_save=$this->bwprocure_model->update($condition,$data);
		if($getdata["type"]==2){
			$product_save=true;
		}else{
			//增加库存
			$condition=array("procure_id"=>$procure_id,"deleted"=>0);
			$bwprocuredetail=$this->bwprocuredetail_model->getList($condition);
			foreach($bwprocuredetail as $k=>$val){
				$condition=array("goods_id"=>$val["wgoods_id"],"warehouse_id"=>get_current_warehouse_id());
				$old_stock=M("BWgoodsStock")->lock(true)->where($condition)->find();
				$new_stock=bcadd($old_stock['goods_stock'],$val['goods_stock'],4);
				$new_income_stock=bcadd($old_stock['income_stock'],$val['goods_stock'],4);
				$new_income_price=bcadd($old_stock['income_price'],$val['price'],2);
				$stock_price=bcadd($old_stock['stock_price'],$val['price'],2);
				$data=array('goods_stock'=>$new_stock,'income_stock'=>$new_income_stock,'income_price'=>$new_income_price,'stock_price'=>$stock_price);
				$stock=D("BWgoodsStock")->update($condition,$data);
				if($stock===false){
					M()->rollback();
					$result["status"] =0;
					$result["msg"] = "库存更新失败";
					$this->ajaxReturn($result);
				}
			}
			$product_save=true;
		}
		if ($bwprocure_save!==false&&$product_save!==false) {
			M()->commit();
			S('session_menu' . get_user_id(), null);
			$result["status"] = 1;
			$result["msg"] = "成功";
			$result["url"] = U("BWholesaleProcure/check");
			$this->ajaxReturn($result);
		} else {
			M()->rollback();
			$result["status"] = 0;
			$result["msg"] = "失败";
			$result["test"] = $bwprocure_save."//".$product_save;
			$this->ajaxReturn($result);
		}
	}
	// excel 批量导入货品数据 - 计重 - ajax
	public function excel_input(){

		$file_name = $_FILES['excel_file']['name'];
		$tmp_name = $_FILES['excel_file']['tmp_name'];
		//$extend = strrchr($file_name, '.');
		//$data = excel_to_array_page($extend, I("upload_file"),I("p"),2);
		$info = $this->uploadExcel($file_name, $tmp_name,I("p"),2);
		$datas = array('status'=> 0, 'msg'=> '上传失败');

		if ($info['status'] == 1) {
			$datas['status'] = '1';
			$text = '';
			foreach ($info['data'] as $key => $value) {
				$condition=array("bgoodscommon.company_id"=>get_company_id(),"bgoodscommon.deleted"=>0,"bwgoods.status"=>1);
				$condition['bwgoods.goods_code']=$value[0];
				$join=" left  join ".DB_PRE."b_wgoods bwgoods on bwgoods.goods_common_id=bgoodscommon.id";
				$field="bwgoods.*";
				$goods=$this->bgoodscommon_model->alias("bgoodscommon")->getInfo($condition,$field,$join);
				if(empty($goods)){
					$datas['status']=0;
					$datas['data']=$value;
					$datas['msg']="该商品编码(".$value[0].")不存在";
					$datas['file']=$info['upload_file'];
					@unlink($info['upload_file']);
					die(json_encode($datas));
					return;
				}
				$text .= '<tr>
        		<td class="text-center goods_order" style="vertical-align: inherit;"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
        		<td class=" goods_code" style="vertical-align: inherit;">'.$value[0].'</td>
        		<td class="goods_name" style="vertical-align: inherit;">'.$goods['goods_name'].'</td>
        		<td class="goods_spec" style="vertical-align: inherit;">'.$goods['goods_spec'].'</td>
        		<td class="text-center goods_unit_price" style="vertical-align: inherit;"><input type="text" autocomplete="off"  name="goods_unit_price" value="'.number_format($value[1], 2).'" readonly="readonly"></td>
	            <td class="text-center num_stock" style="vertical-align: inherit;"><input type="text" autocomplete="off" name="num_stock" value="'.number_format($value[2], 2).'" readonly="readonly"></td>
	            <td class="goods_unit" style="vertical-align: inherit;">'.$goods['goods_unit'].'</td>
	            <td class="text-center price2" style="vertical-align: inherit;"><input type="text" autocomplete="off" class="price2" name="price2" value="'.number_format($value[3], 2).'" readonly="readonly"></td>
	            <td class="text-center goods_id" hidden ="hidden"><input type="text" autocomplete="off" id="pid"  value="'.$goods['id'].'"></td>
	            <td class="text-center" style="vertical-align: inherit;"></td>
	            </tr>';
			}

			$datas['data'] = $text;
			//展示分页
			$page = $this->page($info['info']['count'], $info['info']['limit'],1);
			$datas['page']=$page->show('Admin');
		}
		die(json_encode($datas));
	}
	// 获取excel模板
	private function getExcelUrl($type = 'add'){

		$type = strtolower($type);

		$filename = array(
			// 计重
			'add'=> 'wgoods_example.xlsx',
			// 计件
			'num_add'=> 'example1.xlsx'
		);

		// 默认 - add
		if(!isset($filename[$type])){
			$type = 'add';
		}

		$url = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_ADDR'].$_SERVER['SERVER_PORT']);

		$example_excel = 'http://'.$url.'/Uploads/excel/'. $filename[$type];

		$this->assign('example_excel', $example_excel);
	}

	// 读取excel文档并查询数据
	private function uploadExcel($file_name, $tmp_name,$p,$limit){
		$filePath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/excel/'.get_company_id()."/";
		if(!is_dir($filePath)){
			// 递归创建多级文件夹
			mkDirs($filePath);
		}

		require_once VENDOR_PATH.'PHPExcel/PHPExcel.php';
		require_once VENDOR_PATH.'/PHPExcel/PHPExcel/IOFactory.php';
		require_once VENDOR_PATH.'/PHPExcel/PHPExcel/Reader/Excel5.php';

		$time = time();
		$extend = strrchr($file_name, '.');
		$name = $time . $extend;
		$uploadfile = $filePath . $name;
		$result = move_uploaded_file($tmp_name, $uploadfile);
		if($result){
			$data = excel_to_array_page($extend, $uploadfile,$p,$limit);
		}
		//@unlink($file_name);
		$info = array();
		if(!empty($data['data'])){
			$info['data'] = $data['data'];
			$info['info'] = $data['info'];
			$info['upload_file'] = $uploadfile;
			$info['status'] = '1';
		}else{
			@unlink($uploadfile);
			$info['status'] = '0';
		}

		return $info;
	}
	// 采购发票上传 - ajax
	public function upload_bill(){
		$p_user = get_user_id();

		$dirpath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/BWholesaleProcure/Bill/'.$p_user.'/';
		if(!is_dir($dirpath)){
			mkDirs($dirpath);
		}

		// 如果有旧发票图片则删除
		if(I('post.del_bill_pic')){
			$dir_path = str_replace("http://".$_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT'], I('post.del_bill_pic'));
			@unlink($dir_path);
		}

		$type = explode('/', $_FILES['upload_bill']['type']);
		$file_name = $dirpath.time().'.'.$type[1];
		move_uploaded_file($_FILES["upload_bill"]["tmp_name"], $file_name);
		$result = file_exists($file_name);
		$file_name = str_replace($_SERVER['DOCUMENT_ROOT'], "http://".$_SERVER['HTTP_HOST'], $file_name);

		if($result !== false){
			$info["status"] = "1";
			$info["bill_pic"] = $file_name;
		}else{
			$info["status"] = "0";
		}

		die(json_encode($info));
	}
	//导出列表数据
	function export_excel($page=1){
		$condition=array("bwprocure.company_id"=>$this->MUser['company_id'],"bwprocure.deleted"=>0);
		$this->handleSearch($condition);
		$this->bwprocure_model->excel_out($condition);
	}
}

