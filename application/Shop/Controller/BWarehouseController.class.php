<?php
/**
 * 金子价格管理
 */
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BWarehouseController extends ShopbaseController {
	
	public function _initialize() {
		$this->bwarehouse_model=D("BWarehouse");
		$this->bshop_model=D("BShop");
		$this->bemployee_model=D("BEmployee");
		$this->goods_model = D("BGoods");
		$this->bgoodsclass_model = D("BGoodsClass");
		$this->product_model=D("BProduct");
		$this->wgoodsstock_model=D("BWgoodsStock");
		parent::_initialize();
	}
	/**
	 * 仓库列表展示
	 */
	public function index() {
		$getdata=I("");
		$shop_id=$this->MUser['shop_id'];
		$condition=array("bwarehouse.deleted"=>0,"bwarehouse.company_id"=>$this->MUser['company_id']);
		if($getdata["search_name"]){
			$condition["bwarehouse.wh_name|bwarehouse.wh_code|muser.user_nicename"]=array("like","%".$getdata["search_name"]."%");
		}
		$join="left join ".DB_PRE."m_users muser on muser.id=bwarehouse.wh_uid";
		$field='bwarehouse.*,muser.user_nicename';
		$count=$this->bwarehouse_model->alias("bwarehouse")->countList($condition,$field,$join,$order='bwarehouse.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		$data=$this->bwarehouse_model->alias("bwarehouse")->getList($condition,$field,$limit,$join,$order='bwarehouse.id desc');
		foreach($data as $k=>$v){
			$condition=array("company_id"=>get_company_id(),"warehouse_id"=>$v["id"],"status"=>2,"deleted"=>0);
			//零售库存
			$data[$k]["product_count"]=$this->product_model->countList($condition);
			$condition=array("warehouse_id"=>$v["id"]);
			//批发库存
			$data[$k]["wgoods_count"]=$this->wgoodsstock_model->countList($condition);
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("list",$data);
		$this->display();
	}
	//仓库添加
	public function add() {
		$postdata=I("");
		if(empty($postdata)){
			$this->display();
		}else{
			if (IS_POST) {
				if ($this->bwarehouse_model->create()!==false) {
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
				} else {
					$this->error($this->bshop_model->getError());
				}
			}
		}
	}
	//仓库编辑
	public function edit() {
		$postdata=I("post.");
		if(empty($postdata)){
			$condition=array("bwarehouse.id"=>I("get.ware_id",0,"intval"),"bwarehouse.company_id"=>$this->MUser['company_id'],"bwarehouse.shop_id"=>$this->MUser['shop_id']);
			$join="left join ".DB_PRE."m_users musers on musers.id=bwarehouse.wh_uid";
			$data=$this->bwarehouse_model->alias("bwarehouse")->getInfo($condition,$field="bwarehouse.*,musers.user_nicename",$join,$order='bwarehouse.id desc');
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
				} else {
					$this->error($this->bshop_model->getError());
				}
			}
		}
	}

	//仓库删除
	public function deleted() {
		$data=array();
		$data["id"]=I("ware_id",0,"intval");
		$data["deleted"]=1;
		$condition["id"]=I("post.ware_id",0,"intval");
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
		if(!empty($postdata)) {
			$condition = array("bemployee.deleted" => 0, 'bemployee.company_id' => $this->MUser["company_id"]);
			if ($postdata["employee_name"]) {
				$condition["bemployee.employee_name"] = array("like", "%" . $postdata["employee_name"] . "%");
			}
			if ($postdata["mobile"]) {
				$condition["musers.mobile"] = array("like", "%" . $postdata["mobile"] . "%");
			}
			$field = 'bemployee.*,musers.user_status,musers.mobile,bjobs.job_name';
			$join = "left join " . DB_PRE . "m_users musers on bemployee.user_id=musers.id";
			$join .= " left join " . DB_PRE . "b_jobs bjobs on bemployee.job_id=bjobs.id";
			$count = $this->bemployee_model->alias("bemployee")->countList($condition, $field, $join, $order = 'bemployee.create_time desc', $group = '');
			$page = $this->page($count, $this->pagenum);
			$limit = $page->firstRow . "," . $page->listRows;
			$data = $this->bemployee_model->alias("bemployee")->getList($condition, $field, $limit, $join, $order = 'bemployee.create_time desc', $group = '');
			$this->assign("page", $page->show('Admin'));
			$this->assign("list", $data);
		}
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
		$id=I("id");
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
			$where1['warehouse_id'] = $id;
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
			$list=D("BProduct")->alias("bproduct")->getList($where,$field,$limit,$join,$order='bproduct.id desc');
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
		$from_store=D("BWarehouse")->getInfo($where,$field);
		if(is_admin_business(get_user_id(),$this->MUser)){
			$this->assign("is_admin",true);
		}
		if ($from_store || is_admin_business(get_user_id(),$this->MUser)) {
			if ($from_store) {
				$map["bp.warehouse_id"] = $from_store["id"];
				$condition = array();
				$condition['wh_uid'] = get_user_id();
				$condition['company_id']=$this->MUser['company_id'];
				$wh_name = D("BWarehouse")->getInfo($condition);
				$this->assign("wh_name", $wh_name);
			} else {
				$condition = array();
				$condition['deleted'] = 0;
				$condition["shop_id"] = array(
					"in",
					"-1,0"
				);
				$warehouse =  D("BWarehouse")->getList($condition);
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
		$warehouse = D("BWarehouse")->getList($condition);
		$this->assign("warehouse", $warehouse);
		$where=array("table"=>"gb_b_product","field"=>"status");
		$status=D("BStatus")->_getStatusInfo($where);
		$this->assign("status",$status);
		$this->display();
	}

	// 库存货品列表展示
	public function product_index()
	{
		//货品状态下拉列表
		$status_model = D ( 'b_status' );
		$condition ["table"] = DB_PRE.'b_product';
		$condition ["field"] = 'status';
		$status_list = $status_model->getStatusInfo ( $condition );
		$this->assign ( "status_list", $status_list );
		$this->b_show_status('b_product');
		$this->assign ( "is_hide", array(1,5,9,10));
		$condition = array();
		$condition['deleted'] = 0;
		$condition['company_id']=get_company_id();// add by lzy 2018-4-12
		$condition["shop_id"] =get_shop_id();
		$warehouse = D("BWarehouse")->getList($condition);//die(var_dump(D("Business/BWarehouse")->getLastSql()));
		$this->assign("warehouse", $warehouse);

		$wh_id = I("wh_id");
		if ($wh_id > 0) {
			$map["bproduct.warehouse_id"] = $wh_id;
		}
		$map["bproduct.deleted"] = 0;
		$map['bwarehouse.company_id']=get_company_id();// add by lzy 2018-4-12
		$map["bwarehouse.shop_id"] = get_shop_id();
		//$map["bproduct.status"] = array("not in",get_out_wharehous_status("string"));
		// 货品状态
		$status = I("status");
		if ($status && $status != 'all') {
			$map ["bproduct.status"] = $status;
		}
		// 货品搜索
		$name = trim($_REQUEST["search_name"]);
		if ($name) {
			$map["bproduct.product_code | bgoods.goods_name | bgoods.goods_spec | g_detail.weight"] = array(
				'like',
				'%' . $name . '%'
			);
		}
		//货品克重
		if(trim(I('weight'))){
		    $map['gold.weight']=trim(I('weight'));
		}
        //商品分类
        if(I("class_id")){
            $class_id = I("class_id",0,'intval');
            $class_list = $this->bgoodsclass_model->getALLGoodsClass($class_id, array());
            $class_id_list = '0,' . $class_id;
            foreach ($class_list as $key => $val) {
                $class_id_list .= ',' . $val['id'];
            }
            $map["g_common.class_id"]=array('in',$class_id_list);
        }
		$join="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
		$join.=D('BProduct')->get_product_join_str();
		$field = "bproduct.*,g_common.class_id,bwarehouse.wh_name,bgoods.goods_code,bgoods.goods_spec,bgoods.goods_name";
		$field.=D('BProduct')->get_product_field_str(2);
		$count=D('BProduct')->alias("bproduct")->countList($map,$field,$join,$order='bproduct.id desc');
		$page = $this->page($count, $this->pagenum);
		$limit=$page->firstRow.",".$page->listRows;
		if(I('export')==1){
			$data = D('BProduct')->excel_out($map);die();
		}
		$data=D('BProduct')->alias("bproduct")->getList($map,$field,$limit,$join,$order='bproduct.id desc');
		$sid=I("class_id",0,'intval');
        $select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid);
		$class_status = D('AGoodsClass')->_get_class_status();
		foreach ($data as $k => $v){
			$data[$k]['class_name'] = D('BGoodsClass')->classNav($v["class_id"]);
			// 商品大类列表
			$data[$k]['type_name'] = $class_status[$v['type']];
			$data[$k]['product_detail']=D('BProduct')->get_product_detail_html($v,"&nbsp;&nbsp;");
		}
		$this->assign("data", $data);
		$this->assign("page", $page->show('Admin'));
		$this->assign("select_categorys",$select_categorys);
		$this->display();
	}
	/**
	 * 货品统计
	 */
	public function product_statistic(){
	    if(I('o_type')=='ajax'){
	        $model_class=D('BGoodsClass');
	        $condition=array(
	            'goods_class.company_id'=>get_company_id(),
	            'goods_class.deleted'=>0,
	        );
	       $product_status='';
	        if(!empty(I('status'))&&I('status')!='all'){
	            $product_status=" and product.status=".trim(I('get.status'));
	        }
	        if(empty(I('status'))){
	            $product_status=" and product.status= 2";
	        }
	        $join=" left join gb_b_goods_common goods_common on goods_common.class_id=goods_class.id";
	        $join.=" left join gb_b_goods goods on goods.goods_common_id=goods_common.id";
	        $join.=" left join gb_b_product product on product.goods_id=goods.id and product.deleted=0 ".$product_status." and product.warehouse_id=".get_shop_wh_id(2);
	        $join.=" left join gb_b_product_gold product_gold on product_gold.product_id=product.id";
	        $categories = $model_class->alias('goods_class')->getList($condition,$field='goods_class.*,goods_class.class_name as name,count(product.id) as product_count,if(sum(product_gold.weight)>0,sum(product_gold.weight),0) as product_weight',$limit=null,$join,$order='goods_class.id asc',$group='goods_class.id');
	        
	        $data=$model_class->get_attr($categories,0);
	        $this->_get_list($data);
	        echo json_encode($data,true);
	    }else{
	        $this->b_show_status('b_product');
	        $this->display();
	    }
	}
	private function _get_list(&$list){
	    foreach($list as $key => $val){
	        if(!empty($val['children'])){
	            $count=$this->_get_count($val['children'], $val['product_count']);
	            $list[$key]['product_count']=$count;
	            $weight=$this->_get_weight($val['children'], $val['product_weight']);
	            $list[$key]['product_weight']=$weight;
	            $this->_get_list($list[$key]['children']);
	        }
	    }
	}
	/**
	 * 获取下级的count总和
	 * @param unknown $list
	 * @param unknown $count
	 */
	private function _get_count(&$list,&$count){
	    foreach($list as $key =>$val){
	        $count+=$val['product_count'];
	        if(!empty($val['children'])){
	            $this->_get_count($list[$key]['children'],$count);
	        }
	    }
	    return $count;
	}
	/**
	 * 获取下级的weight总和
	 * @param unknown $list
	 * @param unknown $count
	 */
	private function _get_weight(&$list,&$weight){
	    foreach($list as $key =>$val){
	        $weight+=$val['product_weight'];
	        if(!empty($val['children'])){
	            $this->_get_count($list[$key]['children'],$weight);
	        }
	    }
	    return $weight;
	}
}

