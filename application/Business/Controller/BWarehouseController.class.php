<?php
/**
 * 金子价格管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BWarehouseController extends BusinessbaseController {
	
	public function _initialize() {
		$this->bwarehouse_model=D("BWarehouse");
		$this->bshop_model=D("BShop");
		$this->bemployee_model=D("BEmployee");
		$this->goods_model = D("BGoods");
		$this->product_model=D("BProduct");
		$this->wgoodsstock_model=D("BWgoodsStock");
		parent::_initialize();
	}
	/**
	 * 仓库列表展示
	 */
	public function index() {
		$getdata = I("");
		$shop_id = $this->MUser['shop_id'];
		$condition = array("bwarehouse.deleted"=>0,"bwarehouse.company_id"=>$this->MUser['company_id']);
		if($getdata["search_name"]){
			$condition["bwarehouse.wh_name|bwarehouse.wh_code|b_employee.employee_name"]=array("like","%".$getdata["search_name"]."%");
		}
		$join = "left join ".DB_PRE."m_users muser on muser.id=bwarehouse.wh_uid";
		$join.= " left join ".DB_PRE."b_employee b_employee on b_employee.user_id=bwarehouse.wh_uid and b_employee.deleted=0 and b_employee.company_id=bwarehouse.company_id";
		$field = 'bwarehouse.*,b_employee.employee_name user_nicename';
		$count = $this->bwarehouse_model->alias("bwarehouse")->countList($condition,$field,$join,$order='bwarehouse.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.",".$page->listRows;
		$data = $this->bwarehouse_model->alias("bwarehouse")->getList($condition,$field,$limit,$join,$order='bwarehouse.id desc');
		$where = array(
			'company_id'=> get_company_id(),
			'option_name'=> 'b_procurement_warehouse'
		);
		$options = D('BOptions')->getInfo($where);
		foreach($data as $k => $v){
			$condition = array(
				"company_id"=>get_company_id(),
				"warehouse_id"=>$v["id"],
				"status"=>2,
				"deleted"=>0
			);
			// 零售库存
			$v["product_count"] = $this->product_model->countList($condition);
			$condition = array("warehouse_id"=>$v["id"]);
			// 批发库存
			$v["wgoods_count"] = $this->wgoodsstock_model->countList($condition);
			$options['option_value']=(int)$options['option_value'];
			if($options['option_value'] == $v['id']||$v['shop_id'] >0){
				$v['is_default'] = 1;
			}else{
				$v['is_default'] = 0;
			}

			$data[$k] = $v;
		}

		$this->assign("page", $page->show('Admin'));
		$this->assign("list", $data);
		$this->display();
	}
	//仓库添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$this->display();
		}else{
			if (IS_POST) {
				//if ($this->bwarehouse_model->create()!==false) {
					$data=array();
					$data["company_id"]=$this->MUser["company_id"];
					$data["shop_id"]=$this->MUser["shop_id"];
					$data["wh_name"]=$postdata["wh_name"];
					$data["wh_code"]=$postdata["wh_code"];
					$data["wh_uid"]=$postdata["user_id"];
					$data["status"]=$postdata["status"];
					$data["address"]=$postdata["address"];
					$data["created_time"]=time();
					$data["deleted"]=0;
					$where['wh_code']=$postdata["wh_code"];
					$where['company_id']=$this->MUser['company_id'];
					$where['deleted']=0;
					$info=$this->bwarehouse_model->getInfo($where);
					if($info){
						$this->error("添加失败，仓库的编码不能重复！");
					}
					$bwarehouse=$this->bwarehouse_model->insert($data);
					if ($bwarehouse!==false) {
						$this->success("保存成功！", U("BWarehouse/index"));
					} else {
						$this->error("保存失败！");
					}
				/*} else {
					$this->error($this->bshop_model->getError());
				}*/
			}
		}
	}
	//仓库编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("bwarehouse.id"=>I("get.ware_id",0,"intval"),"bwarehouse.company_id"=>$this->MUser['company_id']);
			$join="left join ".DB_PRE."m_users musers on musers.id=bwarehouse.wh_uid";
			$data=$this->bwarehouse_model->alias("bwarehouse")->getInfo($condition,$field="bwarehouse.*,musers.user_nicename",$join,$order='bwarehouse.id desc');
			$this->assign("data",$data);
			$this->display();
		}else{
			if (IS_POST) {
				//if ($this->bwarehouse_model->create()!==false) {
					$data=array();
					$data["id"]=$postdata["ware_id"];
					//$data["shop_id"]=$this->MUser["shop_id"];
					$data["wh_name"]=$postdata["wh_name"];
					$data["wh_code"]=$postdata["wh_code"];
					$wh_info=$this->bwarehouse_model->getInfo(array('company_id'=>get_company_id(),'wh_code'=>$postdata["wh_code"],'id'=>array('neq',$postdata["ware_id"])));
					if($wh_info){
						$this->error("仓库编码已经存在！");
					}
					if($postdata["user"]){
						$data["wh_uid"]=$postdata["user_id"];
					}else{
						$data["wh_uid"]=0;
					}
					$data["status"]=$postdata["status"];
					$data["address"]=$postdata["address"];
					$condition["id"]=I("post.ware_id",0,"intval");
					$bwarehouse=$this->bwarehouse_model->update($condition,$data);
					if ($bwarehouse!==false) {
						$this->success("保存成功！", U("BWarehouse/index"));
					} else {
						$this->error("保存失败！");
					}
				/*} else {
					$this->error($this->bshop_model->getError());
				}*/
			}
		}
	}

	//仓库删除
	public function deleted() {
		$data=array();
		$data["id"]=I("ware_id",0,"intval");
		$data["deleted"]=1;
		$condition["id"]=I("ware_id",0,"intval");
		$condition["shop_id"]=array('gt','-1');
		$condition["company_id"]=get_company_id();
		$count_p=D('BProduct')->countList(array('warehouse_id'=>$data["id"]));
		if($count_p>0){
			$this->error("仓库存在货品，不能删除！");
		}
		$count_a=D('BAllot')->countList(array('from_id|to_id'=>$data["id"]));
		if($count_a>0){
			$this->error("仓库存在调拨记录，不能删除！");
		}
		$bwarehouse=$this->bwarehouse_model->update($condition,$data);
		if ($bwarehouse!==false) {
			$this->success("删除成功！", U("BWarehouse/index"));
		} else {
			$this->error("删除失败！");
		}
	}
	//添加管理员
	public function add_manager() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("id"=>I("get.ware_id",0,"intval"),"company_id"=>$this->MUser['company_id'],"shop_id"=>$this->MUser['shop_id']);
			$data=$this->bwarehouse_model->getInfo($condition,$field="*",$join="",$order='id desc');
			$this->assign("data",$data);
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bwarehouse_model->create()!==false) {
					$data=array();
					$data["id"]=$postdata["ware_id"];
					$data["shop_id"]=$this->MUser["shop_id"];
					$data["wh_name"]=$postdata["wh_name"];
					$data["wh_code"]=$postdata["wh_code"];
					$data["status"]=$postdata["status"];
					$data["address"]=$postdata["address"];
					$condition["id"]=I("post.ware_id",0,"intval");
					$bwarehouse=$this->bwarehouse_model->update($condition,$data);
					if ($bwarehouse!==false) {
						$this->success("保存成功！", U("BWarehouse/index"));
					} else {
						$this->error("保存失败！");
					}
				} else {
					$this->error($this->bshop_model->getError());
				}
			}
		}
	}
//获取员工
	public function bemployee_list() {
		$postdata = I('post.');
		$condition = array("bemployee.deleted" => 0, 'bemployee.company_id' => $this->MUser["company_id"]);
		if ($postdata["employee_name"]) {
			$condition["bemployee.employee_name"] = array("like", "%" . $postdata["employee_name"] . "%");
		}
		if ($postdata["mobile"]) {
			$condition["musers.mobile"] = array("like", "%" . $postdata["mobile"] . "%");
		}
		if (I("get.shop_id")>0) {
			$condition["shop_employee.shop_id"] = I("get.shop_id");
		}
		$field = 'bemployee.*,musers.user_status,musers.mobile,bjobs.job_name';
		$join = "left join " . DB_PRE . "m_users musers on bemployee.user_id=musers.id";
		$join .= " left join " . DB_PRE . "b_jobs bjobs on bemployee.job_id=bjobs.id";
		if (I("get.shop_id")>0) {
			$join .= " join " . DB_PRE . "b_shop_employee shop_employee on shop_employee.employee_id=bemployee.id";
		}
		$count = $this->bemployee_model->alias("bemployee")->countList($condition, $field, $join, $order = 'bemployee.create_time desc', $group = '');
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow . "," . $page->listRows;
		$data = $this->bemployee_model->alias("bemployee")->getList($condition, $field, $limit, $join, $order = 'bemployee.create_time desc', $group = '');
		$this->assign("page", $page->show('Admin'));
		$this->assign("list", $data);
		$this->display();
	}

	//下载盘盈表
	public function  export_inventory_profit(){
		$time=date("Y-m-d H:i:s");
		$whe['u.id']=get_user_id();
		$users=M("m_users")->alias('u')->where($whe)->find();
		$name=$users['realname']?$users['realname']:$users['user_nicename'];
		$html='<h3 align="center" style="font-family:'.iconv('utf-8', 'gbk', "黑体;").'">'.iconv('utf-8', 'gbk', "盘盈表").'</h3>
        <table width="100%" align="left" border="0" style="font-size:14px;">
            <tr>
                <td width="30%">'.iconv('utf-8', 'gbk', "制单人：").iconv('utf-8', 'gbk',$name).'</td>
                <td width="40%">'.iconv('utf-8', 'gbk', "制单时间：").$time.'</td>
                <td>'.iconv('utf-8', 'gbk', "盘点时间：").'</td>
            </tr>
        </table>
        <table width="100%" cellspacing="1"cellspacing="1" style="border:1px solid #000;border-collapse:collapse;font-size:12px;padding-top:6px;padding-bottom:6px;">
            <tr>
                <th width="30" style="border:1px solid #000;">'.iconv('utf-8', 'gbk', "序").'</th>
                <th width="200" style="border:1px solid #000;">'.iconv('utf-8', 'gbk', "产品名称").'</th>
                <th width="150" style="border:1px solid #000;">'.iconv('utf-8', 'gbk', "货品名称").'</th>
            </tr>';
		$i=1;
		while ($i<=20){
			$html.='<tr>
                <td style="border:1px solid #000;text-align: center;" height="30px">'.$i.'</td>
                <td style="border:1px solid #000;"></td>
                <td style="border:1px solid #000;"></td>
            </tr>';
			$i++;
		}
		$html.='</table>
        <table width="100%" text-align="left" border="0" style="font-size:14px;">
            <tr>
                <td width="40%">'.iconv('utf-8', 'gbk', "盘点金行：").'</td>
                <td width="30%">'.iconv('utf-8', 'gbk', "盘点人：").'</td>
                <td>'.iconv('utf-8', 'gbk', "审核人：").'</td>
            </tr>
        </table>';
		header("Content-Type: application/msword");
		header("Content-Disposition: attachment; filename=盘盈表.doc");
		header("Pragma: no-cache");
		echo $html;
	}
	public function inventory(){
		$id=I("request.id");
		$type=I("type");
		if($type=="count"){
			$where['warehouse_id']=$id;
			$where['status']=2;
			$count=M("b_product")->where($where)->count();
			$result["status"] = 1;
			if(empty($count)){
				$count=0;
			}
			$result["msg"] = $count;
			$this->ajaxReturn($result);
		}else {
			$time=date("Y-m-d H:i:s");
			$whe['u.id']=get_user_id();
			$users=M("m_users")->alias('u')->where($whe)->find();
			$name=$users['realname']?$users['realname']:$users['user_nicename'];
			$where1['id'] = $id;
			$info=M("b_warehouse")->where($where1)->find();
			$where['bproduct.warehouse_id']=$id;
			$where['bproduct.status'] = 2;
			$count=M("b_product")->alias("bproduct")->where($where)->count();
			/*$list = M("b_product")
				->alias("p")
				->where($where)
				->join(DB_PRE."b_goods as g on g.id=p.goods_id")
				->field("g.goods_code,g.goods_name,p.product_code")->select();*/
			//$join="left join ".DB_PRE."b_goods as g on g.id=bproduct.goods_id";
			$join=D("BProduct")->get_product_join_str();
			$field='bproduct.*';
			$field.=D("BProduct")->get_product_field_str();
			$limit="";
			$list=D("BProduct")->alias("bproduct")->getList($where,$field,$limit,$join,$order='g_common.id desc');
			$html = '<h3 align="center" style="font-family:' . iconv('utf-8', 'gbk', "黑体;") . '">' . iconv('utf-8', 'gbk', "库存盘点") . '</h3>
        <table width="100%" align="left" border="0" style="font-size:14px;border-collapse:collapse;">
            <tr>
                <td width="30%">' . iconv('utf-8', 'gbk', "制单人：") .iconv('utf-8', 'gbk',$name). '</td>
                <td width="40%">' . iconv('utf-8', 'gbk', "制单时间：") .$time. '</td>
                <td>' . iconv('utf-8', 'gbk', "盘点时间：") . '</td>
            </tr>
        </table>
        <table width="100%" cellspacing="1"cellspacing="1" style="border:1px solid #000;border-collapse:collapse;font-size:12px;padding-top:6px;padding-bottom:6px;">
            <tr>
                <th width="30" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "序") . '</th>
                <th width="100" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "产品编码") . '</th>
                <th width="170" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "产品名称") . '</th>
                <th width="100" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "货品编号") . '</th>
                <th width="80" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "克重") . '</th>
                <th width="130" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "在库√ 不在库×") . '</th>
            </tr>';
			$i=1;
			if ($list) {
				foreach ($list as $v){
					$html .= '<tr>
                <td style="border:1px solid #000; text-align: center;"  height="30px">' . $i . '</td>
                <td style="border:1px solid #000; text-align: center;">'.$v['goods_code'].'</td>
                <td style="border:1px solid #000; padding-left:5px;">'. iconv('utf-8', 'gbk', $v['goods_name']) . '</td>
                <td style="border:1px solid #000; text-align: center;">'.$v['product_code'].'</td>
                <td style="border:1px solid #000;text-align: right;padding-right:5px;">'.($v['weight']*1).'</td>
                <td style="border:1px solid #000;"></td>
                </tr>';
					$i++;
				}
			}else{
				$html .= '<tr>
                    <td style="border:1px solid #000;text-align: center;"  height="30px">1</td>
                    <td style="border:1px solid #000;"></td>
                    <td style="border:1px solid #000;"></td>
                    <td style="border:1px solid #000;"></td>
                    <td style="border:1px solid #000;"></td>
                    <td style="border:1px solid #000;"></td>
                    </tr>';
			}
			$html.='</table><br/>';
			$html.='<table width="100%" cellspacing="1"cellspacing="1" style="border:1px solid #000;border-collapse:collapse;font-size:12px;padding-top:6px;padding-bottom:6px;">
            <tr>
                <th width="150" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "货品总数") . '</th>
                <th width="150" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "盘亏数") . '</th>
                <th width="150" style="border:1px solid #000;">' . iconv('utf-8', 'gbk', "盘盈数") . '</th>
            </tr>';
			$html .= '<tr>
                <td style="border:1px solid #000;text-align: right;padding-right: 5px;">'.$count.'</td>
                <td style="border:1px solid #000;"></td>
                <td style="border:1px solid #000;"></td>
                </tr></table>';
			$html.='<table width="100%" text-align="left" border="0" style="font-size:14px;border-collapse:collapse;">
            <tr>
                <td width="40%">'.iconv('utf-8', 'gbk', "盘点仓库：").iconv('utf-8', 'gbk',$info['wh_name']).'</td>
                <td width="30%">'.iconv('utf-8', 'gbk', "盘点人：").'</td>
                <td>'.iconv('utf-8', 'gbk', "审核人：").'</td>
            </tr>
        </table>';
			header("Content-Type: application/msword");
			header("Content-Disposition: attachment; filename=库存盘点.doc");
			header("Pragma: no-cache");
			echo $html;
			unset($html);
		}
	}

	// 仓库商品明细展示
	public function goods_index()
	{
		$where=array("wh_uid"=>get_user_id(),"company_id"=>$this->MUser['company_id']);
		$field="id";
		$from_store=D("Business/BWarehouse")->getInfo($where,$field);
		if(is_admin_business(get_user_id(),$this->MUser)){
			$this->assign("is_admin",true);
		}
		if ($from_store || is_admin_business(get_user_id(),$this->MUser)) {
			if ($from_store) {
				$map["bp.warehouse_id"] = $from_store["id"];
				$condition = array();
				$condition['wh_uid'] = get_user_id();
				$condition['company_id']=$this->MUser['company_id'];
				$wh_name = D("Business/BWarehouse")->getInfo($condition);
				$this->assign("wh_name", $wh_name);
			} else {
				$condition = array();
				$condition['deleted'] = 0;
				$condition["shop_id"] = array(
					"in",
					"-1,0"
				);
				$warehouse =  D("Business/BWarehouse")->getList($condition);
				$this->assign("warehouse", $warehouse);
				$wh_id = I("wh_id");
				if ($wh_id > 0) {
					$map["bp.warehouse_id"] = $wh_id;
				}
			}
			$map[C('DB_PREFIX') . "b_goods.deleted"] = 0;
			if (empty($_REQUEST["type"])) {
				$name = $_REQUEST["search_name"];
				if ($name) {
					$map[C('DB_PREFIX') . "b_goods.goods_name|" . C('DB_PREFIX') . "b_goods.goods_code"] = array(
						'like',
						'%' . $name . '%'
					);
				}
			}
			$name = trim($_REQUEST["search_name"]);
			if ($name) {
				$map[C('DB_PREFIX') . "b_goods.goods_name | " . C('DB_PREFIX') . "b_goods.goods_code"] = array(
					'like',
					'%' . $name . '%'
				);
			}
			$map["bp.goods_id"] = array(
				'gt',
				0
			);
			$map["bp.deleted"] = 0;
			$count = $this->goods_model->goods_distinct($map, $type = "count"); // 查询满足要求的总记录数
			$numpage = $this->pagenum; // 每页显示条数
			$page = $this->page($count, $numpage); // 实例化分页类 传入总记录数和每页显示的记录数(25)
			$show = $page->show("Admin"); // 分页显示输出
			$limit = $page->firstRow . ',' . $page->listRows;
			$data = $this->goods_model->goods_distinct($map, $type = "select", $limit, C('DB_PREFIX') . "b_goods.*");
			$this->assign("data", $data);
			$this->assign("page", $show);
			$this->display();
		} else {
			$this->display();
		}
	}
	// 仓库对应的每类商品的货品明细列表展示
	public function goods_index_detail()
	{
		$name = $_REQUEST["search_name"];
		if ($name) {
			$map["bproduct.product_code"] = array(
				'like',
				'%' . $name . '%'
			);
		}
		// 所属仓库
		$wh_id = $_REQUEST["wh_id"];
		if ($wh_id > 0 || $wh_id === '0') {
			$map["bproduct.warehouse_id"] = $wh_id;
		} else {
			$map[C('DB_PREFIX') . "b_warehouse.shop_id"] = array(
				"in",
				"-1,0"
			);
		}
		// 所属商品
		if (I("goods_id")) {
			$map["bproduct.goods_id"] = I("goods_id");
		}

		$field = "bproduct.*," . C('DB_PREFIX') . "b_warehouse.wh_name," . C('DB_PREFIX') . "b_goods.goods_code," . C('DB_PREFIX') . "b_goods.goods_name";
		$join = " ";
		$count = $this->product_model->get_product_list_count($map, $field, $join);
		//$count = count($product_list); // 查询满足要求的总记录数
		$numpage = $this->pagenum; // 每页显示条数
		$page = $this->page($count, $numpage); // 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show = $page->show("Admin"); // 分页显示输出
		$limit = $page->firstRow . ',' . $page->listRows;
		$data = $this->product_model->get_product_list($map, $field, $join, $limit);
		// print_r($data);
		$this->assign("data", $data);
		$this->assign("page", $show);
		// 获取所有的坤金仓库
		$condition = array();
		$condition['deleted'] = 0;
		$condition["shop_id"] = array(
			"in",
			"-1,0"
		);
		$condition["status"] = 1;
		$warehouse = D("Business/BWarehouse")->getList($condition);
		$this->assign("warehouse", $warehouse);
		$where=array("table"=>"gb_b_product","field"=>"status");
		$status=D("BStatus")->_getStatusInfo($where);
		$this->assign("status",$status);
		$this->display();
	}

	// 库存货品列表展示
	public function product_index()
	{
		$this->b_show_status('b_product');
		//货品状态下拉列表
		$status_model = D ( 'b_status' );
		$condition ["table"] = DB_PRE.'b_product';
		$condition ["field"] = 'status';
		$status_list = $status_model->getStatusInfo ( $condition );
		$this->assign ( "status_list", $status_list );
		$this->assign ( "is_hide", array(1,5));
		$condition = array();
		$condition['deleted'] = 0;
		$condition['company_id']=get_company_id();// add by lzy 2018-4-12
		$warehouse = D("Business/BWarehouse")->getList($condition);
		$this->assign("warehouse", $warehouse);

		$wh_id = I("wh_id");
		if ($wh_id > 0) {
			$map["bproduct.warehouse_id"] = $wh_id;
		}
		$map["bproduct.deleted"] = 0;
		$map['bwarehouse.company_id']=get_company_id();// add by lzy 2018-4-12
		$map["bproduct.status"] = array("not in",'1,5');
		// 货品状态
		$status = I("status");
		if ($status && $status != 'all') {
			$map ["bproduct.status"] = $status;
		}
		// 货品搜索
		$name = trim($_REQUEST["search_name"]);
		if ($name) {
			$map["bproduct.product_code | bgoods.goods_name| bgoods.goods_spec |  g_detail.weight"] = array(
				'like',
				'%' . $name . '%'
			);
		}
		if(trim(I('weight'))){
		    $map['gold.weight']=trim(I('weight'));
		}
		$join="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
		$join.=D('BProduct')->get_product_join_str();
		$field = "bproduct.*,g_common.class_id,bwarehouse.wh_name,bgoods.goods_code,bgoods.goods_spec,bgoods.goods_name product_name";
		$field.=D('BProduct')->get_product_field_str(2);
		$count=D('BProduct')->alias("bproduct")->countList($map,$field,$join,$order='bproduct.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		if(I('export')==1){
			$data = $this->excel_out($map);die();
		}
		$data=D('BProduct')->alias("bproduct")->getList($map,$field,$limit,$join,$order='bproduct.id desc');
		$this->assign("data", $data);
		$this->assign("page", $page->show('Admin'));
		$this->assign("data", $data);
		$this->display();
	}
	// 仓库货品列表
	public function product_list(){
	    //货品状态下拉列表
	    $this->b_show_status('b_product');
/* 	    $status_model = D ( 'b_status' );
	    $condition ["table"] = DB_PRE.'b_product';
	    $condition ["field"] = 'status';
	    $status_list = $status_model->getStatusInfo ( $condition );
	    $this->assign ( "status_list", $status_list ); */
	    //货品列表
	    $getdata=I("");
	    $condition=array("bproduct.company_id"=>$this->MUser['company_id'],"bproduct.deleted"=>0);
	    if($getdata["search_name"]){
	        $condition["bproduct.product_code|bgoods.goods_name"]=array("like","%".trim($getdata["search_name"])."%");
	    }
	    // 货品状态
	    $status = I("status");
	    if ($status && $status != 'all') {
	        $condition ["bproduct.status"] = $status;
	    }
	    if(trim(I('weight'))){
	        $map['gold.weight']=trim(I('weight'));
	    }
	    // 所属仓库
	    $wh_id = I("wh_id");
	    if ($wh_id > 0 || $wh_id === '0') {
	        $condition ["bproduct.warehouse_id"] = $wh_id;
	    }
	    
	    $join="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
	    //$join.=" left join ".DB_PRE."b_goods_common bgoodscommon on bgoodscommon.id=bgoods.goods_common_id";
	    $join.=$this->product_model->get_product_join_str();
	    $field='bproduct.*,bwarehouse.wh_name';
	    $field.=$this->product_model->get_product_field_str();
	    $count=$this->product_model->alias("bproduct")->countList($condition,$field,$join,$order='bproduct.id desc');
	    $page = $this->page($count, $this->pagenum);
	    $limit=$page->firstRow.",".$page->listRows;
	    $data=$this->product_model->alias("bproduct")->getList($condition,$field,$limit,$join,$order='bproduct.id desc');
	    foreach ($data as $k => $v){
	        $data[$k]['product_detail']=$this->product_model->get_product_detail_html($v);
	    }
	    $this->assign("page", $page->show('Admin'));
	    $this->assign("list",$data);
	    $this->display();
	}
	public function excel_out($excel_where,$min_id=0){
		static $row;
		if ($min_id==0) {
			$row = 0;
			$title=array('序','仓库','类型','商品分类','货品名称','规格','货品编号','含金量','克重','质检编号','销售指导价','货品状态');
		} else {
			$excel_where['bproduct.id'] = array('lt', $min_id);
		}
		$limit = '0,1000';
		$join="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
		$join.=D('BProduct')->get_product_join_str();
		$join.=" left join ".DB_PRE."b_goods_class b_goods_class on b_goods_class.id=g_common.class_id";
		$join.=" left join ".DB_PRE."b_goods_class b_goods_class2 on b_goods_class2.id=b_goods_class.pid";
		$field='bproduct.*,bwarehouse.wh_name,g_common.class_id,b_goods_class.pid class_pid';
		$field.=',if(b_goods_class2.class_name,b_goods_class.class_name,concat(b_goods_class.class_name,"/",b_goods_class2.class_name)) class_name';
		$field.=D('BProduct')->get_product_field_str();
		//$field='bproduct.*,bwarehouse.wh_name,g_common.class_id,b_goods_class.pid class_pid,b_goods_class.class_name,g_common.goods_name common_goods_name';
		//$field.='bgoods.goods_spec';
		//查询数据
		$data=D('BProduct')->alias("bproduct")->export($excel_where,$field,$limit,$join,$order='bproduct.id desc');
		if($data['data']){
			//过滤需要导出的数据，并按类型转换
			$filter_data=array('wh_name'=>'','type'=>'a_status_comment=a_goods_class,type','class_name'=>'','common_goods_name'=>'','goods_spec'=>'','product_code'=>''
			,'purity'=>'','weight'=>'','qc_code'=>'','sell_price'=>'','status'=>'status_comment=b_product,status');
			$export_data = D('BProduct')->alias("bproduct")->export_data_filter($data,$filter_data,$title,$row);
			//递归导出数据
			$export_data['filename']='product_list';
			D('BProduct')->alias("bproduct")->export_excel($export_data,array(&$this, 'excel_out'),$excel_where,$data['end_id']);
		}
	}
}

