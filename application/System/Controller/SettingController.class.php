<?php
//author：czy--设置
namespace System\Controller;
use Common\Controller\SystembaseController;
class SettingController extends SystembaseController {

	protected $options_model;

	public function __construct(){
		parent::__construct();
	}

	public function _initialize() {
		parent::_initialize();
		$this->options_model = D("System/AOptions");
	}

	// 短信设置展示
	public function mobile_setting()
	{
		$mobile_limit = $this->options_model->getMobileLimit(array("option_name"=>"mobile_limit"));
		$mobile_open = $this->options_model->getMobileOpen(array("option_name"=>"mobile_open"));
		$sms_class=M("a_sms")->where("deleted=0")->select();
		$this->assign("mobile_limit", $mobile_limit);
		$this->assign("mobile_open", $mobile_open);
		$this->assign("sms_class", $sms_class);
		$this->display();
	}

	// 短信设置修改
	public function mobile_setting_post() {

		$mobile_limit["option_value"] = intval($_POST["mobile_limit"]);
		$mobile_open["option_value"] = intval($_POST["mobile_open"]);

		$result1 = $this->options_model->update(array("option_name"=>"mobile_limit"), $mobile_limit);
		$result2 = $this->options_model->update(array("option_name"=>"mobile_open"), $mobile_open);

		if ($result1 !== false && $result2 !== false) {
			$this->success("保存成功！");
		} else {
			$this->error("保存失败！");
		}
	}

	// 短信黑白名单
	public function mobile_limit() {
		$name = trim(I("search_name"));
		if (!empty($name)) {
			$map["mobile"] = array('like', '%' . $name . '%');
		}
		// 默认是白名单
		$map["type"] = $show_type = I("type/d", 1);
		$this->assign('show_type', $show_type);
	
		$map["deleted"] = 0;
		$count = M("a_sms_mobile")->where($map)->count();

		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow . ',' . $page->listRows;

		$sms_mobile = M("a_sms_mobile")->where($map)->limit($limit)->select();

		$this->assign("sms_mobile", $sms_mobile);
		$this->assign('numpage', $this->pagenum);
		$this->assign("page", $page->show("Admin"));

		$this->display("mobile_index");
	}

	// 短信黑白名单添加
	public function mobile_limit_post() {
		// 默认是白名单
		$data["type"] = I("type/d", 1);
		$mobiles = I("mobile");
		$memo = I("memo");
		if (!is_array($mobiles)) {
			// 替换中文逗号
			$mobiles = str_replace('，', ',', $mobiles);

			// 分割英文逗号
			$mobiles = explode(",", $mobiles);
		}

		if (count($mobiles) > 0) {
			$dataAll = array();
			foreach ($mobiles as $k => $v) {

				if (!empty($v)) {
					$datamap["mobile"] = $v;
					$datamap["type"] = $data["type"];
					$sms_mobile = M("a_sms_mobile")->where($datamap)->find();
					if (empty($sms_mobile)) {
						$datamap["memo"] = $data["memo"];
						$dataAll[] = $datamap;
					}
				}
			}
			if (!empty($dataAll)) {
				$result = M("a_sms_mobile")->addAll($dataAll);
				if ($result !== false) {
					$this->success("保存成功！");
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error("已经存在！");
			}

		} else {

			$data["mobiles"] = $mobiles;
			$sms_mobile = M("a_sms_mobile")->where($data)->find();
			if ($sms_mobile) {
				$this->error("已经存在！");
			} else {
				$data["memo"] = $memo;
				$result = M("a_sms_mobile")->add($data);
				if ($result !== false) {
					$this->success("保存成功！");
				} else {
					$this->error("保存失败！");
				}
			}
		}


		//$this->display("mobile_add");
	}

	// 短信黑白名单添加
	public function mobile_limit_add() {
		$this->display("mobile_limit");
	}

	// 短信黑白名单删除
	public function mobile_limit_delete() {
		$data["id"] = I("id");
		$result = M("a_sms_mobile") -> delete($data["id"]);
		if ($result !== false) {
			$data["status"] = 1;
			$data["msg"] = "成功";
		} else {
			$data["status"] = 0;
			$data["msg"] = "删除失败！";
		}
		$this -> ajaxReturn($data);
	}

	// 短信黑白名单编辑
	public function mobile_limit_edit() {
		$map["id"] = I("id");

		$result = M("a_sms_mobile")->find($map["id"]);

		$this->assign("sms_mobile", $result);
		$this->display();
	}

	// 短信黑白名单编辑
	public function mobile_limit_edit_post() {
		$map["id"] = I("id");

		$data["mobile"] = I("mobile");
		$data["type"] = I("type");
		$data["memo"] = I("memo");

		$result = M("a_sms_mobile")->where($map)->save($data);

		if ($result !== false) {
			$this->success("保存成功！");
		} else {
			$this->error("保存失败！");
		}
	}

	// 短信列表
	public function smslog() {
		$sms_log = M('a_sms_log');
		// 实例化User对象
		$name = trim(I("search_name"));
		if ($name) {
			$map["mobile"] = array('like', '%' . $name . '%');
		}
		$map["deleted"] = 0;

		$count = $sms_log->where($map)->count();

		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow . ',' . $page->listRows;

		$sms_log = M('a_sms_log')->where($map)->order('id desc')->limit($limit)->select();

		$this->assign('sms_log', $sms_log);
		$this->assign('page', $page->show("Admin"));

		$this->display();
	}

	// 金价接口的设置
	public function gold_setting(){
		if(empty($_POST)){
			//获取开关信息
			$open_info=$this->options_model->getGoldSwitch();
			$this->assign('is_open',$open_info['option_value']);
			//获取接口类型
			$open_info=$this->options_model->getApiType();
			$this->assign('api_type',$open_info['option_value']);
			//获取每一种贵金属种类的详细信息
			$numpage = 30;
			$count = M('a_gold_category')->where(array('status'=>1))->count();
			$page = $this->page($count,$numpage);
			$show = $page->show('Admin');
			$cate_list = $this->options_model->getNewGoldList($page);
			//获取集金号金价
			$jjh_info = $this->jjh_gold_setting();
			$this->assign('jjh_info', $jjh_info);

			$this->assign('cate_list', $cate_list);
			$this->assign('numpage', $numpage);
			$this->assign('page', $show);

			$this->display('gold_setting');
		}else{
			$is_open=I('post.value');
			$type=I("post.type");
			if(empty($is_open)){
				$is_open=0;
			}
			if($type=="open"){
				$condition=array(
					'option_name'=>'gold_price_switch'
				);
			}else{
				$condition=array(
					'option_name'=>'gold_type_switch'
				);
			}
			$update=array(
				'option_value'=>$is_open
			);
			$result=$this->options_model->update($condition,$update);
			if($result!=false){
				output_data('success');
			}
		}
	}

	// 单个金价接口的开关
	public function gold_edit(){
		$operate=$_REQUEST['operate'];
		$expression=$_REQUEST['expression'];
		$is_show=I("is_show");
		$id=$_REQUEST['id'];
		if(!empty($operate)){
			switch ($operate){
				case "open":
					$result=$this->options_model->operateCate($id,"add");
					break;
				case "close":
					$result=$this->options_model->operateCate($id,"del");
					break;
				default:
					break;
			}
			if($result){
				output_data('success');
			}else{
				output_error('fail');
			}
		}else if(!empty($is_show)){
			$is_show=($is_show=="close")?0:1;
			$update_data=array('is_show'=>$is_show);
			$cate_info=D('System/a_gold_category')->update(array('id'=>$id),$update_data);
			if($cate_info){
				output_data('success');
			}else{
				output_error('fail');
			}
		}else if(!empty($expression)||!empty(I("memo"))){
			//$condition=array('option_name'=>'gold_cate'.$id);
			//$update=array('option_value'=>$expression);
			$update_data=array('memo'=>I("memo"));
			//$result=$this->options_model->update($condition,$update);
			$cate_info=D('System/a_gold_category')->update(array('id'=>$id),$update_data);
			if($cate_info){
				output_data('success');
			}else{
				output_error('fail');
			}
		}else{
			$condition=array('option_name'=>'gold_cate'.$id);
			$cate_info=D('System/a_gold_category')->getInfo(array('id'=>$id));
			$this->assign('cate_info',$cate_info);
			$option_info=$this->options_model->getInfo($condition);
			$this->assign('expression',$option_info['option_value']);
			$this->display();
		}
	}
	//集金号设置
	public function jjh_gold_setting(){
		$option_info=$this->options_model->getJJHGoldSetting();
		$option_info=json_decode($option_info["option_value"],true);
		$option_info['expression']=$option_info[$option_info['is_open']];
		$gold_list=D('GoldPrice')->getNewList();
		$xau_price=$gold_list[$option_info['is_open']]['price'];
		$rate=$gold_list['usdcny_price']['price'];
		$expression = str_replace("price", (float)$xau_price, $option_info['expression']);
		$expression = str_replace("rate", (float)$rate, $expression);
		eval("\$option_info['price']=" . $expression . ";");
		$option_info['price']=bcadd($option_info['price'],0,2);
		return $option_info;
	}
	public function jjh_gold_edit(){
		if(empty(I('post.'))){
			$option_info=$this->options_model->getJJHGoldSetting();
			$option_info=json_decode($option_info["option_value"],true);
			$option_info['expression']=$option_info[$option_info['is_open']];
			$gold_list=D('GoldPrice')->getNewList();
			$cate_info=D('System/a_gold_category')->getInfo(array('id'=>7));
			$this->assign('cate_info',$cate_info);
			$this->assign('price_type',$gold_list);
			$this->assign('option_info',$option_info);
			$this->display();
		}else{
			$expression=I('request.expression');
			$type=I('request.type');
			$result=$this->options_model->updateJJHGoldSetting($expression,$type);
			$update_data=array('memo'=>I("memo"));
			$cate_info=D('System/a_gold_category')->update(array('id'=>7),$update_data);
			if($result!==false){
				output_data('success');
			}else{
				output_error('fail');
			}
		}
	}

	// 设置采购仓库
	public function procure_setting(){
		if(empty($_POST)){
			$info=$this->options_model->getInfo("option_name='procurement_warehouse'");//获取采购仓库
			$warehouse=D('BWarehouse');
			$condition=array(
				'shop_id'=>array('in','-1,0'),
				'deleted'=>0
			);
			$store_list=$warehouse->getList($condition);
			$this->assign('info',$info);
			$this->assign('store_list',$store_list);
			$this->assign("setting_url","setting_procure_setting");
			$this->display();
		}else{
			$info=$this->options_model->getInfo("option_name='procurement_warehouse'");
			if(empty($info)){
				$insert=array();
				$insert['option_name']='procurement_warehouse';
				$insert['option_value']=$_POST['wh_id'];
				$result=$this->options_model->insert($insert);
				if($result>0){
					$res['state']='success';
					$res['info']='操作成功！';
				}else{
					$res['state']='fail';
					$res['info']='操作失败！';
				}
			}else{
				$condition="option_name='procurement_warehouse'";
				$update=array('option_value'=>$_POST['wh_id']);
				$result=$this->options_model->update($condition,$update);
				if($result!='false'||$result==0){
					$res['state']='success';
					$res['info']='操作成功！';
				}else{
					$res['state']='fail';
					$res['info']='操作失败！';
				}
			}
			die(json_encode($res,true));
		}
	}

	public function send_msgs(){
		if(IS_POST) {
			$type = I("type");
			if (empty($type)) {
				$result = array("status" => 0, "msg" => "系统错误！");
				$this->ajaxReturn($result);
			}
			if($type == 1){
				$mobile = I("mobile");
				$msg = I("msg");
				if (!preg_match("/^1[234578]\d{9}$/",$mobile)) {
					$result = array("status" => 0, "msg" => "发送人号码有误！");
					$this->ajaxReturn($result);
				}
				if(empty($msg)){
					$result = array("status" => 0, "msg" => "请输入发送内容！");
					$this->ajaxReturn($result);
				}
				@$SendMessage=new SendMessageController();
				$data=@$SendMessage->sendOrderSMS($mobile,"【金行家】".$msg,9,'',0);
				if($data['is_ok']==1){
					$result = array("status" => 1, "msg" => "短信发送成功！");
					$this->ajaxReturn($result);
				}else{
					$result = array("status" => 0, "msg" => "短信发送失败！(".$data["info"].")");
					$this->ajaxReturn($result);
				}
			}
			if($type==2){
				$mobile = I("mobile");
				if(empty($mobile)){
					$result = array("status" => 0, "msg" => "请输入手机号！");
					$this->ajaxReturn($result);
				}
				$msg = I("msg");
				if(empty($msg)){
					$result = array("status" => 0, "msg" => "请输入发送内容！");
					$this->ajaxReturn($result);
				}
				$mobiles=explode(",",$mobile);
				$str='';
				@$SendMessage=new SendMessageController();
				$i=0;
				foreach ($mobiles as $k=>$v){
					if (!preg_match("/^1[234578]\d{9}$/",$v)) {
						$i++;
						if($i%10==0){
							$str.="(格式错误)<br/>";
						}
						$str.=$v."(格式错误),";
					}else{
						$data=@$SendMessage->sendOrderSMS($v,"【金行家】".$msg,9,'【金行家】',0);
						if($data['is_ok']!=1){
							$i++;
							if($i%10==0){
								$str.="(".$data["info"].")<br/>";
							}
							$str.=$v."(".$data["info"]."),";
						}
					}
				}
				if($str !=''){
					$fhandler = fopen("Uploads/excel/sendmsg_error_mobile.html", 'w+');
					fwrite($fhandler,$str);
					fclose($fhandler);
					$result = array("status" => 0, "msg" => "部分号码发送失败","url"=>"/Uploads/excel/sendmsg_error_mobile.html");
					$this->ajaxReturn($result);
				}else{
					$result = array("status" => 1, "msg"=>"短信发送成功！");
					$this->ajaxReturn($result);
				}
			}
			if($type==3){
				$msg=I("msg");
				if(empty($msg)){
					$result = array("status" => 0, "msg" => "请输入发送内容！");
					$this->ajaxReturn($result);
				}
				$choose=I("choose");
				$where=array('user_status'=>1, 'user_type'=>array("neq","4"));
				if($choose==1){
					$where['is_real_name']=1;
				}
				$id=0;
				@$SendMessage=new SendMessageController();
				$str='';
				$i=0;
				$z=0;
				while(1){
					$where['id']=array("gt",$id);
					$where['mobile']=array("in","18079147093,180");
					$info=M("MUsers")->where($where)->field("id,mobile")->order("id asc")->find();
					if(empty($info)){
						break;
					}
					$id=$info['id'];
					$options= M('a_options')->where("option_name='mobile_limit'")->find();
					if($options["option_value"]>0){
						$map["mobile"]=$info['mobile'];
						$map["type"]=$options["option_value"];
						$sms_mobile= M('a_sms_mobile')->where($map)->find();
						if($map["type"]==2) {
							if ($sms_mobile) {//判断是否黑名单
								continue;
							}
						}
						if($map['type']==1){
							if(!$sms_mobile){
								continue;
							}
						}
					}
					$data=@$SendMessage->sendOrderSMS($info['mobile'],$msg,9);
					if($data['is_ok']!=1 && $data["info"]!="该手机号不在白名单" && $data["info"]!="黑名单手机号"){
						$i++;
						if($i%10==0){
							$str.="(".$data["info"].")<br/>";
						}
						$str.=$info['mobile']."(".$data["info"]."),";
					}
				}
				if($str !=''){
					$fhandler = fopen("Uploads/excel/sendmsg_error_mobile.html", 'w+');
					fwrite($fhandler,$str);
					fclose($fhandler);
					$result = array("status" => 0, "msg" => "部分号码发送失败","url"=>"/Uploads/excel/sendmsg_error_mobile.html");
					$this->ajaxReturn($result);
				}else{
					$result = array("status" => 1, "msg"=>"短信发送成功！");
					$this->ajaxReturn($result);
				}
			}
		}else{
			$this->display();
		}
	}
	public function user_list(){
		$where=array(
			'user_status'=>1,
			'user_type'=>array("neq","4")
		);
		$search_name=trim(I('search_name'));
		if(!empty($search_name)) {
			$where['user_nicename | realname | mobile'] = array("like", "%" . $search_name . "%");
		}
		$count=D("MUsers")->where($where)->count();
		$page = $this->page($count, 10);
		$users = D("MUsers")
			->where($where)
			->order("create_time DESC")
			->limit($page->firstRow, $page->listRows)
			->select();
		$show = $page -> show("Admin");
		$this->assign("numpage",10);
		$this->assign("list",$users);
		$this->assign("search_name",$search_name);
		$this->assign("page",$show);
		$this->display();
	}

}
