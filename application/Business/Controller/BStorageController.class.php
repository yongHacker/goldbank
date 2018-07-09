<?php
/**
 * 入库分称流程
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Symfony\Component\Yaml\Tests\ParserTest;
use Business\Model\BProcureStorageModel;
use Business\Model\BBillOpRecordModel;

class BStorageController extends BusinessbaseController {
	
	public function _initialize() {
		parent::_initialize();
		$this->bproduct_model = D('BProduct');
		$this->bprocurestorage_model = D('BProcureStorage');
		$this->bgoods_model=D("BGoods");
        $this->bgoodsclass_model = D("BGoodsClass");
	}

	// 入库待分称列表 - pricemode = 1 , storage_status = 0 的数据
	public function index()
    {
        $main_tbl = C('DB_PREFIX') . 'b_procure_storage';
        
        $where = array(
            'p.status' => 1,
            'p.deleted' => 0,
            $main_tbl . '.deleted' => 0,
            $main_tbl . '.company_id' => get_company_id()
        );
        $where["pricemode"] = array("gt", 0);
        $this->handleSearch($where);
		$field = $main_tbl.'.*';
		// $field .= ', p.batch as procure_batch, s.company_name, gc.class_name,gc.type,p.status as p_status';
		$field .= ', p.batch as procure_batch, s.company_name, p.status as p_status';
		$field .= ', agc.class_name';
		$field .= ', ('.$main_tbl.'.real_weight - '.$main_tbl.'.weight) as diff_weight';
		//$field .= ', ifnull(su.user_nicename,su.mobile)as creator_name';
		//$field .= ', IF('.$main_tbl.'.storager_id, ifnull(su.user_nicename,su.mobile),"-")as storager_name';
		$field .= ', c_employee.employee_name as creator_name';
		$field .= ', IF('.$main_tbl.'.storager_id, s_employee.employee_name,"-")as storager_name';
		$field .= ', from_unixtime('.$main_tbl.'.create_time, "%Y-%m-%d %H:%i:%s")as show_create_time';
		$field .= ', '.$main_tbl.'.storage_status as sstatus, 
			CASE '.$main_tbl.'.status
			WHEN -2 THEN "已驳回"
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "待分称" END show_status';

		$join = ' LEFT JOIN '.C('DB_PREFIX').'b_procurement as p ON (p.id = '.$main_tbl.'.procurement_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_supplier as s ON (s.id = p.supplier_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'m_users as cu ON (cu.id = '.$main_tbl.'.creator_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'m_users as su ON (su.id = '.$main_tbl.'.storager_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_employee as c_employee ON (c_employee.user_id = '.$main_tbl.'.creator_id and c_employee.deleted=0 and c_employee.company_id = '.$main_tbl.'.company_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_employee as s_employee ON (s_employee.user_id = '.$main_tbl.'.storager_id and s_employee.deleted=0 and s_employee.company_id = '.$main_tbl.'.company_id)';
		// $join .= ' LEFT JOIN '.C('DB_PREFIX').'b_goods_class as gc on (gc.id = '.$main_tbl.'.goods_class_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'a_goods_class as agc on (agc.id = '.$main_tbl.'.agc_id)';
		
		$order = $main_tbl.'.id DESC';

		if(IS_POST){
			if(I('post.submit') == 'excel_out'){
				$this->bprocurestorage_model->excel_out($where, $field, $join);
				die();
			}
		}

		$count = $this->bprocurestorage_model->countList($where, $field, $join);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.",".$page->listRows;
		$storage_list = $this->bprocurestorage_model->getList($where, $field, $limit, $join, $order);
		$where = array();
        $where['table'] = "gb_b_procurement";
        $where['field'] = "status";
        $p_status = D("Business/BStatus")->_getStatusInfo($where);
        $this->b_show_status('b_procure_storage');
        $this->assign("p_status", $p_status);
        $this->assign('storage_list', $storage_list);
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
		$this->display();
	}

    // 找回被删除的数据
    public function dataCallback()
    {
        $error_batch = array();
        $model = M();
        // 1、所有没有详情的分称单
        $stroage_list = $model->query('select gb_b_procure_storage.id, gb_b_procure_storage.batch, gb_b_procure_storage.real_weight from gb_b_procure_storage left join gb_b_product on gb_b_procure_storage.id = gb_b_product.storage_id and gb_b_product.deleted = 0 where gb_b_product.id is null and gb_b_procure_storage.deleted = 0 and gb_b_procure_storage.num > 0 ');
        $res = true;
        foreach ($stroage_list as $key => $value) {
            // 获取分称详情
            $product_list = $model->query('select * from gb_b_product where gb_b_product.storage_id = ' . $value['id']);
            if (!empty($product_list)) {
                $product_ids = '';
                foreach ($product_list as $k => $v) {
                    $product_ids .= $v['id'] . ',';
                }
                $product_ids = rtrim($product_ids, ',');
                // 获取分称货物总重
                $count_weight = $model->query('select sum(weight) as count_weight from gb_b_product_gold where product_id in (' . $product_ids . ')');
                if ($value['real_weight'] == $count_weight[0]['count_weight']) {
                    $update = $this->updateData($product_list);
                    if ($update == false) {
                        $error_batch[] = $value['batch'];
                    }
                } elseif (bcmul($value['real_weight'],2, 2) == $count_weight[0]['count_weight']) {
                    // 特殊情况 详情总重等于单据总重的2倍
                    // 去掉重复的前一条货品
                    $_product_list = array();
                    foreach($product_list as $p_k => $p_v) {
                        $_product_list[substr($p_v['product_code'], -12)] = $p_v;
                    }
                    $update = $this->updateData($_product_list);
                    if ($update == false) {
                        $error_batch[] = $value['batch'];
                    }
                } else {
                    $error_batch[] = $value['batch'];
                }
            } else {
                $error_batch[] = $value['batch'];
            }
        }
        var_dump($error_batch);
    }
    // 改 deleted product_code
    public function updateData($product_list) {
        // return true;
        $model = D('BProduct');
        $model->startTrans();
        $res = true;
        foreach ($product_list as $key => $value) {
            if ($res) {
                $condition = array('id' => $value['id'], 'deleted' => 1);
                $data = array('product_code' => substr($value['product_code'], -12), 'deleted' => 0);
                $res = $model->update($condition, $data);
                $res = ($res == false) ? false : $res;
            }
        }
        if ($res == false) {
            $model->rollback();
            return false;
        } else {
            $model->commit();
            return true;
        }
    }

	/**
	 * 分称审核列表
	 * 采购在审核不通过和撤销状态下该分称单不会在这显示
	 */
	public function check_list(){
        if (I('debug')) {
            $this->dataCallback();
            die();
        }
        $main_tbl = C('DB_PREFIX') . 'b_procure_storage';
        $where = array(
            'p.pricemode' => 1,
            'p.deleted' => 0,
            $main_tbl . '.deleted' => 0,
            $main_tbl . '.company_id' => get_company_id()
        );
        $this->handleSearch($where);
        $where[C('DB_PREFIX') . 'b_procure_storage.storage_status'] = 1;
        $where[C('DB_PREFIX') . 'b_procure_storage.status'] = 0;
        $where['p.status'] = array(
            "neq",
            "2,3"
        );
        
        $field = $main_tbl . '.*';
        // $field .= ', p.batch as procure_batch, s.company_name , gc.class_name ';
        $field .= ', p.batch as procure_batch, s.company_name';
        $field .= ', agc.class_name';
		/*$field .= ', (
			CASE '.$main_tbl.'.type
			WHEN 1 THEN "素金"
			WHEN 2 THEN "金料"
			WHEN 3 THEN "裸钻"
			WHEN 4 THEN "镶嵌"
			WHEN 5 THEN "玉石"
			WHEN 6 THEN "摆件"
			ELSE "" END
		) as show_type';*/
		$field .= ', (' . $main_tbl . '.real_weight - ' . $main_tbl . '.weight) as diff_weight';
		/*$field .= ', ifnull(su.user_nicename,su.mobile)as creator_name';
		$field .= ', IF('.$main_tbl.'.storager_id, ifnull(su.user_nicename,su.mobile),"-")as storager_name';*/
		$field .= ', c_employee.employee_name as creator_name';
        $field .= ', IF(' . $main_tbl . '.storager_id, s_employee.employee_name,"-") as storager_name';
        $field .= ', from_unixtime(' . $main_tbl . '.create_time, "%Y-%m-%d %H:%i:%s") as show_create_time';
        $field .= ', IF(' . $main_tbl . '.storage_status, (
			CASE ' . $main_tbl . '.status 
			WHEN 0 THEN "待审核"
			WHEN 1 THEN "审核通过"
			WHEN 2 THEN "审核不通过"
			WHEN 3 THEN "已撤销"
			ELSE "已结算" END
		), "待分称")as show_status';
        
        $join = ' LEFT JOIN ' . C('DB_PREFIX') . 'b_procurement as p ON (p.id = ' . $main_tbl . '.procurement_id)';
        $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_supplier as s ON (s.id = p.supplier_id)';
        $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'm_users as cu ON (cu.id = ' . $main_tbl . '.creator_id)';
        $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'm_users as su ON (su.id = ' . $main_tbl . '.storager_id)';
        $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_employee as c_employee ON (c_employee.user_id = ' . $main_tbl . '.creator_id and c_employee.deleted=0 and c_employee.company_id = ' . $main_tbl . '.company_id)';
        $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_employee as s_employee ON (s_employee.user_id = ' . $main_tbl . '.storager_id and s_employee.deleted=0 and s_employee.company_id = ' . $main_tbl . '.company_id)';
        // $join .= ' LEFT JOIN '.C('DB_PREFIX').'b_goods_class as gc on (gc.id = '.$main_tbl.'.goods_class_id)';
        $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'a_goods_class as agc ON (agc.id = ' . $main_tbl . '.agc_id)';
        
        $order = $main_tbl . '.id DESC';
        
        if (IS_POST) {
            if (I('post.submit') == 'excel_out') {
                $this->bprocurestorage_model->excel_out($where, $field, $join);
                die();
            }
        }
        
        $count = $this->bprocurestorage_model->countList($where, $field, $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow . "," . $page->listRows;
        
        $storage_list = $this->bprocurestorage_model->getList($where, $field, $limit, $join, $order);
        
        $this->assign('storage_list', $storage_list);
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        
        $this->display();
	}
	
	//审核
	public function check()
    {
        if ($_POST) {
            $id = I("id/d", 0);
            $type = I("type/s", '');
            $check_memo = I("check_memo/s", '');
            $where = array(
                'id' => $id,
                'company_id' => get_company_id()
            );
            $storage_info = D('BProcureStorage')->getInfo($where);
            // 入库单不存在，入库单非待审核状态，入库单非分称完成则不能审核
            if (empty($storage_info) || $storage_info['status'] != 0 || $storage_info['storage_status'] != 1) {
                $info['status'] = 0;
                $info['msg'] = '非待审核状态！';
                $this->ajaxReturn($info);
            }
            M()->startTrans();
            
            if ($type == 1) {
                $result = D('BProcureStorage')->set_status_by_where($where, 1, 0, $check_memo);
                /*添加表单操作记录 add by lzy 2018.5.26 start*/
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$id,BProcureStorageModel::CHECK_PASS);
                /*添加表单操作记录 add by lzy 2018.5.26 end*/
                if ($result['status'] == 1) {
                    $info = $this->bprocurestorage_model->get_info($this->MUser);
                    if ($info['p_status'] == 1) {
                        // $result = D('BProduct')->update_status_with_procure_id($id, 2);
                        $result = D('BProduct')->update_status_with_storage_id($id, 2);
                    }
                }
            } elseif ($type == 2) {
                // 分称审核不通过
                $result = D('BProcureStorage')->set_status_by_where($where, 2, 0, $check_memo);
                /*添加表单操作记录 add by lzy 2018.5.26 start*/
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$id,BProcureStorageModel::CHECK_DENY);
                /*添加表单操作记录 add by lzy 2018.5.26 end*/
                if ($result['status'] == 1) {
                    $info = $this->bprocurestorage_model->get_info($this->MUser);
                    // $result = D('BProduct')->update_status_with_procure_id($id, -1);
                    $result = D('BProduct')->toggle_unpass_with_storage_id($id, 1);
                }
            } elseif ($type == -2) {
                // 分称驳回
                $result = D('BProcureStorage')->set_status_by_where($where, -2, 0, $check_memo);
                /*添加表单驳回操作记录 add by chenzy 2018.5.26 start*/
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$id,BProcureStorageModel::CHECK_REJECT);
                /*添加表单驳回操作记录 add by chenzy 2018.5.26 end*/
                if ($result['status'] == 1) {
                    $info = $this->bprocurestorage_model->get_info($this->MUser);
                    // $result = D('BProduct')->update_status_with_procure_id($id, -1);
                    $result = D('BProduct')->toggle_unpass_with_storage_id($id, 1);
                }
            }

            if ($result['status'] == 1&&$record_result) {
                
                M()->commit();
                
                $info['status'] = 1;
                $info['msg'] = '审核成功！';
                $info['url'] = U("BStorage/check_list");
                $this->ajaxReturn($info);
            } else {
                M()->rollback();
                
                $info['status'] = 0;
                $info['msg'] = '审核失败！';
                $this->ajaxReturn($info);
            }
        } else {
            $this->get_detail();
            $this->display();
        }
    }

	// 货品列表
	public function goods_list(){
		$main_tbl = C('DB_PREFIX').'b_goods';
		$postdata = I('post.');
        $type = I("type");
        $price_mode = I("price_mode");
		$where = array(
			'g.status' => 1,
			'g.deleted' => 0,
            'bc.deleted' => 0//判断商品公共是否删除
		);
		$where['gc.company_id'] = get_company_id();

		if($type == 1){
			$where['g.price_mode']=1;
		}
        if(!empty($price_mode)){
            $where['g.price_mode'] = ($price_mode == 1) ? 1 : 0;
        }
		if(!empty($postdata['search_name']) || $postdata['search_name']=='0'){
            $where['g.goods_code|g.goods_name'] = array('like','%'.trim($postdata['search_name']).'%');
        }
        if(!empty($postdata['class_id'])){
            $class_id = $postdata["class_id"];
            $class_list = $this->bgoodsclass_model->getALLGoodsClass($class_id, array());
            $class_id_list = '0,' . $class_id;
            foreach ($class_list as $key => $val) {
                $class_id_list .= ',' . $val['id'];
            }
            $where["bc.class_id"]=array('in',$class_id_list);
        }
		if(!empty($type)){
			$where['gc.type']=$type;
		}

		$field = 'g.id,gd.weight,gd.sell_fee,g.sell_price,g.procure_price,g.price_mode';
		$field .= ', g.goods_spec, g.goods_code,gd.purity, bc.goods_name,gd.sell_feemode,gc.class_name';

		$join = ' JOIN '.C('DB_PREFIX').'b_goods_common as bc ON (bc.id = g.goods_common_id)';
		$join .= ' JOIN '.C('DB_PREFIX').'b_goods_class as gc ON (gc.id = bc.class_id)';
		$join .= ' LEFT JOIN '.C('DB_PREFIX').'b_goldgoods_detail as gd on (g.id = gd.goods_id)';

		$count = $this->bgoods_model->alias('g')->countList($where,$field,$join);
		$page = $this->page($count, $this->pagenum);
		$limit = $page->firstRow.",".$page->listRows;

		$goods_list = $this->bgoods_model->alias('g')->getList($where, $field, $limit, $join);
        $sid=empty($postdata["class_id"])?0:$postdata["class_id"];
        //获取B分类
        $select_categorys=$this->bgoodsclass_model->get_b_goodsclass($sid, array('type'=>1));

		$this->assign('goods_list', $goods_list);
		$this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
		$this->assign("type",$type);
		$this->assign('select_categorys',$select_categorys);

		$this->display();
	}

	// 获取详细资料及商品列表
	private function get_detail()
    {
        $info = $this->bprocurestorage_model->get_info($this->MUser);
        $product_list = $this->bproduct_model->get_list_by_storage_info($info);
        /*表单的操作记录 add by lzy 2018.5.26 start*/
        $operate_record = $this->bprocurestorage_model->getOperateRecord(I('id'));
        $this->assign('operate_record', $operate_record);
        //表单的操作流程
        $operate_process = $this->bprocurestorage_model->getProcess(I('id'));
        $this->assign('process_list', $operate_process);
        /*表单的操作记录 add by lzy 2018.5.26 end*/
        $this->assign('product_list', $product_list);
        $this->assign('info', $info);
    }

	// 详情 - I('get.id/d')
	public function detail()
    {
        // 有post提交为撤销操作
        if (IS_POST) {
            $id = I("id/d", 0);
            if ($id) {
                $where = array(
                    "id" => $id,
                    "deleted" => 0,
                    "storage_status" => 1,
                    "status" => 0,
                    'company_id' => get_company_id()
                );
                $storage_info = D("BProcureStorage")->getInfo($where);
                if (empty($storage_info)) {
                    $info = array(
                        "status" => 0,
                        "msg" => "撤销失败！"
                    );
                    $this->ajaxReturn($info);
                }
                $data = array(
                    "status" => 3,
                    'storage_status' => 0
                );
                M("")->startTrans();
                $data = D("BProcureStorage")->update($where, $data);
                /*添加表单操作记录 add by lzy 2018.5.26 start*/
                $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$id,BProcureStorageModel::REVOKE);
                /*添加表单操作记录 add by lzy 2018.5.26 end*/
                if ($data !== false&&$record_result) {
                    $info = array(
                        "status" => 1,
                        "msg" => "撤销成功！"
                    );
                    M("")->commit();
                    $this->ajaxReturn($info);
                } else {
                    $info = array(
                        "status" => 0,
                        "msg" => "撤销失败！"
                    );
                    M("")->rollback();
                    $this->ajaxReturn($info);
                }
            } else {
                $info = array(
                    "status" => 0,
                    "msg" => "信息错误！"
                );
                $this->ajaxReturn($info);
            }
        } else {
            $this->get_detail();
            $this->display();
        }
    }
    
    // 分称 - I('get.id/d')
    public function split()
    {
        if (IS_POST) {
            $this->handlePostData(ACTION_NAME);
        } else {
            $type = I("type");
            $this->getExcelUrl();
            $info = $this->bprocurestorage_model->get_info($this->MUser);
            $this->assign("info", $info);
            $product_list = $this->bproduct_model->get_list_by_storage_info($info);
            $where = array();
            $where['company_id'] = $this->MUser["company_id"];
            $where['deleted'] = 0;
            $where['sub_type'] = 1;
            $certifys = D("BProductSub")->get_prodcut_sub($where);
            $certify = $this->set_select($certifys, "certify_type");
            $this->assign("certify_types", $certify);
            foreach ($product_list as $k => $v) {
                $product_list[$k]['certify_type'] = $this->set_select($certifys, "certify_type", $v['certify_type']);
            }
            switch ($type) {
                case 1:
                    $this->assign("product_list", $product_list);
                    $this->display(); // 素金
                    break;
                case 2:
                    $this->assign("product_list", $product_list);
                    $this->display("split_goldm"); // 金料
                    break;
                case 3:
                    $where['sub_type'] = 2;
                    $shapes = D("BProductSub")->get_prodcut_sub($where);
                    $shape = $this->set_select($shapes, "shape");
                    $this->assign("shapes", $shape);
                    $where['sub_type'] = 3;
                    $colors = D("BProductSub")->get_prodcut_sub($where);
                    $color = $this->set_select($colors, "color");
                    $this->assign("colors", $color);
                    $where['sub_type'] = 4;
                    $claritys = D("BProductSub")->get_prodcut_sub($where);
                    $clarity = $this->set_select($claritys, "clarity");
                    $this->assign("claritys", $clarity);
                    $where['sub_type'] = 5;
                    $cuts = D("BProductSub")->get_prodcut_sub($where);
                    $cut = $this->set_select($cuts, "cut");
                    $this->assign("cuts", $cut);
                    $where['sub_type'] = 6;
                    $fluorescents = D("BProductSub")->get_prodcut_sub($where);
                    $fluorescent = $this->set_select($fluorescents, "fluorescent");
                    $this->assign("fluorescents", $fluorescent);
                    $where['sub_type'] = 7;
                    $polishs = D("BProductSub")->get_prodcut_sub($where);
                    $polish = $this->set_select($polishs, "polish");
                    $this->assign("polishs", $polish);
                    $where['sub_type'] = 8;
                    $symmetrics = D("BProductSub")->get_prodcut_sub($where);
                    $symmetric = $this->set_select($symmetrics, "symmetric");
                    $this->assign("symmetrics", $symmetric);
                    foreach ($product_list as $k => $v) {
                        $product_list[$k]['shape'] = $this->set_select($shapes, "shape", $v['shape']);
                        $product_list[$k]['color'] = $this->set_select($colors, "color", $v['color']);
                        $product_list[$k]['clarity'] = $this->set_select($claritys, "clarity", $v['clarity']);
                        $product_list[$k]['cut'] = $this->set_select($cuts, "cut", $v['cut']);
                        $product_list[$k]['fluorescent'] = $this->set_select($fluorescents, "fluorescent", $v['fluorescent']);
                        $product_list[$k]['polish'] = $this->set_select($polishs, "polish", $v['polish']);
                        $product_list[$k]['symmetric'] = $this->set_select($symmetrics, "symmetric", $v['symmetric']);
                    }
                    $this->assign("product_list", $product_list);
                    $this->display("split_diamond"); // 裸钻
                    break;
                case 4:
                    $where['sub_type'] = 9;
                    $materials = D("BProductSub")->get_prodcut_sub($where);
                    $material = $this->set_select($materials, "symmetrics");
                    $this->assign("materials", $material);
                    foreach ($product_list as $k => $v) {
                        $product_list[$k]['material'] = $this->set_select($materials, "material", $v['material']);
                    }
                    $this->assign("product_list", $product_list);
                    $this->display("split_inlay"); // 镶嵌
                    break;
                case 5:
                    $this->assign("product_list", $product_list);
                    $this->display("split_jade"); // 玉石
                    break;
                case 6:
                    $this->assign("product_list", $product_list);
                    $this->display("split_goldb");//摆件
					break;
			}
		}
	}

	public function set_select($list, $name, $sub_note)
    {
        $str = "";
        if (! empty($list)) {
            $str = "<select name='" . $name . "' style='width:100px;'>";
            $is_selected = false;
            // $k 为 sub_value 附属信息值, $v 为 sub_note 注释
            foreach ($list as $k => $v) {
				// 没有用到 sub_value 附属信息值
				// if($sub_note == $v){
				// 	$str.="<option value='".$v."' selected='selected'>".$v."</option>";
				// }else{
				// 	$str.="<option value='".$v."'>".$v."</option>";
				// }

				// edit by jk. 20180326
				if ($sub_note == $k) {
                    $str .= "<option value='" . $k . "' selected='selected'>" . $v . "</option>";
                    $is_selected = true;
                } else {
                    $str .= "<option value='" . $k . "'>" . $v . "</option>";
                }
            }

			if ($is_selected === false) {
                $str .= "<option value='' selected='selected'> - </option>";
            } else {
                $str .= "<option value=''> - </option>";
            }
            
            $str .= "</select>";
        }
        return $str;
	}
	// 删除包裹 - I('get.id/d')
	public function delete(){

		$info = $this->bprocurestorage_model->delete_with_product();
		
		if($info['status'] == 1){
			$info["url"] = U('BStorage/index');
		}

		$this->ajaxReturn($info);
	}

	// excel 批量导入货品数据 - ajax
	public function excel_input()
    {
        $type = I("get.type");
        $file_name = $_FILES['excel_file']['name'];
        $tmp_name = $_FILES['excel_file']['tmp_name'];
        
        $info = $this->uploadExcel($file_name, $tmp_name);
        $datas = array();
        $type_tips = array(
            1 => '素金',
            2 => '金料',
            3 => '裸钻',
            4 => '镶嵌',
            5 => '玉石',
            6 => '摆件'
        );

        if ($info['status'] == 1) {
            
            if ($type == 2) {
                $msg = D('BRecoveryProduct')->excel_check($info['data']);
            } else {
                $msg = $this->bproduct_model->excel_check($info['data']);
            }
            
            if (empty($msg)) {
                $datas['status'] = '1';
                $text = "";
                $message='';
                switch ($type) {
                    // 分称时的excel导入 - 计件素金
                    case 'su':
                        foreach ($info['data'] as $k => $val) {
                            $where = array(
                                'g.goods_code' => array('like', '%' . rtrim($val[0]) . '%')
                            );
                            $field = 'g.id, g.goods_spec, gc.goods_name, g.goods_code, gd.purity, gd.weight, gd.price_mode, gc.type';
                            $join = ' LEFT JOIN __B_GOODS_COMMON__ as gc ON (gc.id = g.goods_common_id)';
                            $join .= ' LEFT JOIN __B_GOLDGOODS_DETAIL__ as gd on gd.goods_id = g.id';
                            $goods_info = D("Business/BGoods")->alias('g')->getInfo($where, $field, $join);
                            if (empty($goods_info)) continue;
                            if ($goods_info['type'] != 1) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与当前所选大类不一致，请选择【' . $type_tips[$goods_info['type']] . '】';
                                break 2;
                            }
                            if ($goods_info['price_mode'] != 0) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与存在计价方式为【计重】的产品，商品编码为【' . rtrim($val[0]) . '】，请筛除！';
                                break 2;
                            }
                            
                            $where = array();
                            $where['company_id'] = $this->MUser["company_id"];
                            $where['deleted'] = 0;
                            $where['sub_type'] = 1;
                            $certifys = D("BProductSub")->get_prodcut_sub($where);
                            
                            $certify = $this->set_select($certifys, "certify_type", $val[10]);
				        
				            $text .= '<tr class="p_num add_product" id="' . $goods_info['id'] . '" goods_code="' . $goods_info['goods_code'] . '" >
		                        <td class="text-center" style="padding: 8px 3px"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
		                        <td class="text-center" style="padding: 8px 3px">' . $goods_info['goods_code'] . '</td>
                                <td><input class="product_code" type="text" value="' . $val[1] . '"></td>
                                <td><input class="sub_product_code" type="text" value="' . $val[2] . '"></td>
				            	<td class="text-center" style="padding: 8px 3px">' . $goods_info['goods_name'] . '</td>
                                <td class="text-center" style="padding: 8px 3px">' . $goods_info['goods_spec'] . '</td>
                                <td><input class="product_age no_arrow" type="number" value="' . $val[4] . '"></td>
				            	<td class="text-center" style="padding: 8px 3px">' . $goods_info['purity'] . '</td>
		                        <td><input class="qc_code" type="text" value="' . $val[5] . '"></td>
		                        <td><input class="design" type="text" value="' . $val[6] . '"></td>
		                        <td><input class="weight no_arrow" type="number" step="0.001" value="' .numberformat($val[7], 2,".",""). '"></td>
		                        <td><input class="gold_price no_arrow" type="number" step="0.001" value="' .numberformat($val[8], 2,".",""). '"></td>
		                        <td><input class="ring_size" type="text" value="' . $val[9] . '"></td>
		                        <td class="text-center" style="padding: 8px 3px">'.$certify.'</td>
		                        <td><input class="certify_code" type="text" value="' . $val[11] . '"></td>
		                        <td><input class="certify_price no_arrow" type="number" step="0.001" value="' . numberformat($val[12], 2, ".", "") . '"></td>
		                        <td><input class="extras no_arrow" type="number" step="0.001" value="' . numberformat($val[13], 2, ".", "") . '"></td>
		                        <td><input class="cost_price no_arrow" type="number" step="0.001" value="' . numberformat($val[14], 2,".","") . '"></td>
		                        <td><input class="sell_price no_arrow" type="number" step="0.001" value="' . numberformat($val[15], 2,".","") . '"></td>
		                        <td class="text-center">
                                    <a href="javascript:;" onclick="init_memo_iframe(this)" title="' . $val[16] . '">备注
                                        <span class="memo" hidden>' . $val[16] . '</span>
                                    </a>&nbsp;&nbsp;
                                    <a class="del" href="javascript:;">删除</a>
                                </td>
                    		</tr>';
				        }
				        break;
					// 分称时的excel导入 - 素金
					case 1:

						$storage_id = I('storage_id/d', 0);
						$storage_info = $this->bprocurestorage_model->getInfo('id = '.$storage_id);
						$m_fee = isset($storage_info) ? $storage_info['fee'] : 0;
						foreach ($info['data'] as $k => $val){
							$where = array(
								'g.goods_code' => array('like', '%' .trim($val[0]) . '%'),
							    'gc.company_id'=>$this->MUser["company_id"],
							    'gc.deleted'=>0,
							    'g.deleted'=>0
							);
							$field = 'g.id, g.goods_spec, gc.goods_name,g.goods_code, gd.purity, gd.weight,gd.sell_feemode';
							$join = ' LEFT JOIN '.C('DB_PREFIX').'b_goods_common as gc ON (gc.id = g.goods_common_id)';
							$join .= ' left join gb_b_goldgoods_detail as gd on gd.goods_id = g.id';
							
							$goods_info = D("Business/BGoods")->alias('g')->getInfo($where, $field, $join);
							if(empty($goods_info)) continue;

							$where = array();
							$where['company_id'] = $this->MUser["company_id"];
							$where['deleted'] = 0;
							$where['sub_type'] = 1;
							$certifys = D("BProductSub")->get_prodcut_sub($where);

							$certify = $this->set_select($certifys, "certify_type", $val[11]);
							$goods_info['sell_feemode_name']=$goods_info['sell_feemode']==1?'克工费销售':($goods_info['sell_feemode']==0?'件工费销售':'');
							if($goods_info['sell_feemode']!=$val[7]){
							    $message.="第".($k+1)."行商品规格编码为".$goods_info['goods_code']."的商品规格销售工费方式与系统中商品规格的销售工费方式不符，已使用系统中商品规格的销售工费方式</br>";
							}
							$text .= '<tr id="' . $goods_info['id'] . '" goods_code="' . $goods_info['goods_code'] . '" >
		                        <td class="text-center"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
		                        <td class="text-center">' . $goods_info['goods_code'] . '</td>
                                <td class="text-center"><input class="product_code" type="text" value="' . $val[1] . '"></td>
                                <td class="text-center"><input class="sub_product_code" type="text" value="' . $val[2] . '"></td>
		                        <td class="text-left" style="padding:8px 3px">' . $goods_info['goods_name'] . '</td>
		                        <td class="text-left" style="padding:8px 3px">' . $goods_info['goods_spec'] . '</td>
		                        <td class="text-center" style="padding:8px 3px">' . $goods_info['purity'] . '</td>
		                        <td class="text-center"><input class="qc_code" type="text" value="' . $val[4] . '"></td>
		                        <td class="text-center"><input class="design" type="text" value="' . $val[5] . '"></td>
		                        <td class="text-center"><input class="weight right no_arrow" step="0.001" type="number" value="' .numberformat($val[6], 2,".",""). '"></td>
		                        <td class="text-center" style="padding:8px 3px">' . $goods_info['sell_feemode_name'] . '</td>
                                <td class="text-center"><input class="sell_fee right no_arrow" step="0.001" type="number" value="' .numberformat($val[8], 2,".",""). '"></td>
                                <td class="text-center"><input class="sell_price right no_arrow" step="0.001" type="number" value="' .numberformat($val[9], 2,".",""). '"></td>
		                        <td class="text-center"><input class="ring_size" type="text" value="' . $val[10] . '"></td>
		                        <td class="text-center">'.$certify.'</td>
                        		<td class="text-center"><input class="certify_code" type="text" value="' . $val[12] . '"></td>
                        		<td class="text-center"><input class="certify_price right no_arrow" step="0.001" type="number" value="' .numberformat($val[13], 2,".",""). '"></td>
                        		<td class="text-center"><input class="extras right no_arrow" step="0.001" type="number" value="'.numberformat($val[14], 2,".","").'"></td>
                        		<td class="text-center">
                                    <a href="javascript:;" onclick="init_memo_iframe(this)" title="' . $val[15] . '">备注
                                        <span class="memo" hidden>' . $val[15] . '</span>
                                    </a>&nbsp;&nbsp;
                                    <a class="del" href="javascript:;">删除</a>
                                </td>
                    		</tr>';
						}
						break;
					// 分称时的excel导入 - 金料
					case 2:
                        $rproduct_num=I('rproduct_code_num');
						foreach ($info['data'] as $k => $val) {
                            if(empty($val[1])){
                                $rproduct_num=$rproduct_num+1;
                            }
							$cost_price = floatval($val[3]) * floatval($val[4]);
                            $val[4] = floor($val[3] * 100) / 100;
                            
                            $purity = decimalsformat($val[4], 2) / decimalsformat($val[5], 2);
                            $purity = floatval($val[4]) > floatval($val[5]) ? 0 : decimalsformat($purity, 6);
                            $val[1]=empty($val[1])?date('ymd').sprintf("%04d", $rproduct_num):$val[1];

                            $text .= '<tr class="p_num add_product is_jl">
                                <td class="text-center"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
                                <td><input class="recovery_name" type="text" value="' . $val[0] . '"></td>
                                <td><input class="rproduct_code" type="text" value="' . $val[1] . '"></td>
                                <td><input class="sub_rproduct_code" type="text" value="' . $val[2] . '"></td>
                                <td><input class="gold_price no_arrow" type="number" step="0.001" value="' . numberformat($val[3], 2, ".", "") . '"></td>
                                <td><input class="gold_weight no_arrow" type="number" step="0.001" value="' . numberformat($val[4], 3, ".", "") . '"></td>
                                <td><input class="total_weight no_arrow" type="number" step="0.001" value="' . numberformat($val[5], 3, ".", "") . '"></td>
                                <td><input class="purity no_arrow" type="number" step="0.001" value="' . numberformat($purity, 2, ".", "") . '"></td>
                                <td><input class="cost_price no_arrow" type="number" step="0.001" value="' . numberformat($cost_price, 2, ".", "") . '"></td>
                                <td><input class="material" type="text" value="' . $val[6] . '"></td>
                                <td><input class="color" type="text" value="' . $val[7] . '"></td>
                                <td class="text-center">
                                    <a href="javascript:;" onclick="init_memo_iframe(this)" title="' . $val[8] . '">备注
                                        <span class="memo" hidden>' . $val[7] . '</span>
                                    </a>&nbsp;&nbsp;
                                    <a class="del" href="javascript:;">删除</a>
                                </td>
                            </tr>';
						}
						break;
					// 3-6 采购计件开单时的excel导入
					case 3:
						foreach ($info['data'] as $k => $val) {
                            $where = array(
                                'g.goods_code' => array('like','%' . $val[0] . '%')
                            );
                            $field = 'g.id, gc.goods_name, g.goods_code, g.goods_spec, gc.type';
                            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'b_goods_common as gc ON (gc.id = g.goods_common_id)';
                            $goods_info = D("Business/BGoods")->alias('g')->getInfo($where, $field, $join);
                            if (empty($goods_info)) continue;
                            if ($goods_info['type'] != $type) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与当前所选大类不一致，请选择【' . $type_tips[$goods_info['type']] . '】';
                                break 2;
                            }
                            if ($goods_info['price_mode'] != 0) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与存在计价方式为【计重】的产品，商品编码为【' . rtrim($val[0]) . '】，请筛除！';
                                break 2;
                            }
                            
                            $where = array();
                            $where['company_id'] = $this->MUser["company_id"];
                            $where['deleted'] = 0;
                            $where['sub_type'] = 1;
                            $certifys = D("BProductSub")->get_prodcut_sub($where);
                            $certify = $this->set_select($certifys, "certify_type", $val[15]);
                            $where['sub_type'] = 2;
                            $shapes = D("BProductSub")->get_prodcut_sub($where);
                            $shape = $this->set_select($shapes, "shape", $val[5]);
                            $where['sub_type'] = 3;
                            $colors = D("BProductSub")->get_prodcut_sub($where);
                            $color = $this->set_select($colors, "color", $val[7]);
                            $where['sub_type'] = 4;
                            $claritys = D("BProductSub")->get_prodcut_sub($where);
                            $clarity = $this->set_select($claritys, "clarity", $val[8]);
                            $where['sub_type'] = 5;
                            $cuts = D("BProductSub")->get_prodcut_sub($where);
                            $cut = $this->set_select($cuts, "cut", $val[9]);
                            $this->assign("cuts", $cut);
                            $where['sub_type'] = 6;
                            $fluorescents = D("BProductSub")->get_prodcut_sub($where);
                            $fluorescent = $this->set_select($fluorescents, "fluorescent", $val[10]);
                            $where['sub_type'] = 7;
                            $polishs = D("BProductSub")->get_prodcut_sub($where);
                            $polish = $this->set_select($polishs, "polish", $val[11]);
                            $where['sub_type'] = 8;
                            $symmetrics = D("BProductSub")->get_prodcut_sub($where);
                            $symmetric = $this->set_select($symmetrics, "symmetric", $val[12]);
							$text .= '<tr id="'. $goods_info['id'].'" goods_code="'.$goods_info['goods_code'].'" class="p_num add_product">
                                <td class="text-center" style="padding: 8px 3px"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_code'].'</td>
                                <td><input class="product_code" type="text" value="'.$val[1].'"></td>
                                <td><input class="sub_product_code" type="text" value="'.$val[2].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_name'].'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_spec'].'</td>
                                <td><input class="product_age no_arrow" type="number" value="'.$val[3].'"></td>
                                <td><input class="qc_code" type="text" value="'.$val[4].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$shape.'</td>
                                <td><input class="caratage no_arrow" type="number" setp="0.001" value="'.$val[6].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$color.'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$clarity.'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$cut.'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$polish.'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$symmetric.'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$fluorescent.'</td>
                                <td><input class="cost_price no_arrow" type="number" step="0.001" value="'.$val[13].'"></td>
                                <td><input class="sell_price no_arrow" type="number" step="0.001" value="'.$val[14].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$certify.'</td>
                                <td><input class="certify_code" type="text" value="'.$val[16].'"></td>
                                <td><input class="certify_price no_arrow" type="number" step="0.001" value="'.$val[17].'"></td>
                                <td><input class="extras no_arrow" type="number" step="0.001" value="'.$val[18].'"></td>
                                <td class="text-center">
                                    <a href="javascript:;" onclick="init_memo_iframe(this)" title="' . $val[19] . '">备注
                                        <span class="memo" hidden>' . $val[19] . '</span>
                                    </a>&nbsp;&nbsp;
                                    <a class="del" href="javascript:;">删除</a>
                                </td>
                    		</tr>';
						}
						break;
					case 4:
						foreach ($info['data'] as $k => $val) {
                            $where = array('g.goods_code' => array('like','%' . $val[0] . '%'));
                            $field = 'g.id, g.goods_spec, gc.goods_name, g.goods_code, gc.type';
                            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'b_goods_common as gc ON (gc.id = g.goods_common_id)';
                            $goods_info = D("Business/BGoods")->alias('g')->getInfo($where, $field, $join);
                            if (empty($goods_info)) continue;
                            if ($goods_info['type'] != $type) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与当前所选大类不一致，请选择【' . $type_tips[$goods_info['type']] . '】';
                                break 2;
                            }
                            if ($goods_info['price_mode'] != 0) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与存在计价方式为【计重】的产品，商品编码为【' . rtrim($val[0]) . '】，请筛除！';
                                break 2;
                            }
                            
                            $where = array();
                            $where['company_id'] = $this->MUser["company_id"];
                            $where['deleted'] = 0;
                            $where['sub_type'] = 1;
                            $certifys = D("BProductSub")->get_prodcut_sub($where);
                            $certify = $this->set_select($certifys, "certify_type", $val[24]);
                            $where['sub_type'] = 9;
                            $materials = D("BProductSub")->get_prodcut_sub($where);
                            $material = $this->set_select($materials, "symmetrics", $val[7]);
							$text .= '<tr id="'. $goods_info['id'].'" goods_code="'.$goods_info['goods_code'].'" class="p_num add_product" >
                                <td class="text-center" style="padding: 8px 3px"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
                                <td class="text-center" style="padding: 8px 3px">'. $goods_info['goods_code'].'</td>
                                <td><input class="product_code" type="text" value="'.$val[1].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_name'].'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_spec'].'</td>
                                <td><input class="product_age no_arrow" type="number" value="'.$val[3].'"></td>
                                <td><input class="sub_product_age no_arrow" type="number" value="'.$val[4].'"></td>
                                <td><input class="qc_code" type="text" value="'.$val[5].'"></td>
                                <td><input class="design" type="text" value="'.$val[6].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$material.'</td>  
                                <td><input class="material_color" type="text" value="'.$val[8].'"></td>
                                <td><input class="ring_size" type="text" value="'.$val[9].'"></td>
                                <td><input class="total_weight no_arrow" type="number" step="0.001" value="'.$val[10].'"></td>
                                <td><input class="weight no_arrow" type="number" step="0.001" value="'.$val[11].'"></td>
                                <td><input class="main_stone_num no_arrow" type="number" step="0.001" value="'.$val[12].'"></td>
                                <td><input class="main_stone_caratage no_arrow" type="number" step="0.001" value="'.$val[13].'"></td>
                                <td><input class="main_stone_price no_arrow" type="number" step="0.001" type="text" value="'.$val[14].'"></td>
                                <td><input class="color" type="text" value="'.$val[15].'"></td>
                                <td><input class="clarity" type="text" value="'.$val[16].'"></td>
                                <td><input class="cut" type="text" value="'.$val[17].'"></td>
                                <td><input class="side_stone_num no_arrow" type="number" step="0.001" value="'.$val[18].'"></td>
                                <td><input class="side_stone_caratag no_arrow" type="number" step="0.001" value="'.$val[19].'"></td>
                                <td><input class="side_stone_price no_arrow" type="number" step="0.001" value="'.$val[20].'"></td>
                                <td><input class="process_cost no_arrow" type="number" step="0.001" value="'.$val[21].'"></td>
                                <td><input class="cost_price no_arrow" type="number" step="0.001" value="'.$val[22].'"></td>
                                <td><input class="sell_price no_arrow" type="number" step="0.001" value="'.$val[23].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$certify.'</td>
                                <td><input class="certify_code" type="text" value="'.$val[25].'"></td>
                                <td><input class="certify_price no_arrow" type="number" step="0.001" value="'.$val[26].'"></td>
                                <td><input class="extras no_arrow" type="number" step="0.001" value="'.$val[27].'"></td>
                                <td class="text-center">
                                    <a href="javascript:;" onclick="init_memo_iframe(this)" title="' . $val[28] . '">备注
                                        <span class="memo" hidden>' . $val[28] . '</span>
                                    </a>&nbsp;&nbsp;
                                    <a class="del" href="javascript:;">删除</a>
                                </td>
                    		</tr>';
						}
						break;
					case 5:
						foreach ($info['data'] as $k => $val) {
                            $where = array('g.goods_code' => array('like', '%' . $val[0] . '%'));
                            $field = 'g.id, g.goods_spec, gc.goods_name, g.goods_code, gc.type';
                            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'b_goods_common as gc ON (gc.id = g.goods_common_id)';
                            $goods_info = D("Business/BGoods")->alias('g')->getInfo($where, $field, $join);
                            if (empty($goods_info))
                                continue;
                            if ($goods_info['type'] != $type) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与当前所选大类不一致，请选择【' . $type_tips[$goods_info['type']] . '】';
                                break 2;
                            }
                            if ($goods_info['price_mode'] != 0) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与存在计价方式为【计重】的产品，商品编码为【' . rtrim($val[0]) . '】，请筛除！';
                                break 2;
                            }
                            
                            $where = array();
                            $where['company_id'] = $this->MUser["company_id"];
                            $where['deleted'] = 0;
                            $where['sub_type'] = 1;
                            $certifys = D("BProductSub")->get_prodcut_sub($where);
                            $certify = $this->set_select($certifys, "certify_type", $val[13]);
							$text .= '<tr id="'. $goods_info['id'].'" goods_code="'.$goods_info['goods_code'].'" class="p_num add_product">
                                <td class="text-center" style="padding: 8px 3px"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
                                <td class="text-center" style="padding: 8px 3px">'. $goods_info['goods_code'].'</td>
                                <td><input class="product_code" type="text" value="'.$val[1].'"></td>
                                <td><input class="sub_product_code" type="text" value="'.$val[2].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_name'].'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_spec'].'</td>
                                <td><input class="product_age no_arrow" type="number" value="'.$val[4].'"></td>
                                <td><input class="qc_code" type="text" value="'.$val[5].'"></td>
                                <td><input class="ring_size" type="text" value="'.$val[6].'"></td>
                                <td><input class="p_weight no_arrow" type="number" step="0.001" value="'.$val[7].'"></td>
                                <td><input class="stone_num no_arrow" type="number" step="0.001" value="'.$val[8].'"></td>
                                <td><input class="stone_weight no_arrow" type="number" step="0.001" value="'.$val[9].'"></td>
                                <td><input class="stone_price no_arrow" type="number" step="0.001" value="'.$val[10].'"></td>
                                <td><input class="cost_price no_arrow" type="number" step="0.001" value="'.$val[11].'"></td>
                                <td><input class="sell_price no_arrow" type="number" step="0.001" value="'.$val[12].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$certify.'</td>
                                <td><input class="certify_code" type="text" value="'.$val[14].'"></td>
                                <td><input class="certify_price no_arrow" type="number" step="0.001" value="'.$val[15].'"></td>
                                <td><input class="extras no_arrow" type="number" step="0.001" value="'.$val[16].'"></td>
                                <td class="text-center">
                                    <a href="javascript:;" onclick="init_memo_iframe(this)" title="' . $val[17] . '">备注
                                        <span class="memo" hidden>' . $val[17] . '</span>
                                    </a>&nbsp;&nbsp;
                                    <a class="del" href="javascript:;">删除</a>
                                </td>
                    		</tr>';
						}
						break;
					case 6:
						foreach ($info['data'] as $k => $val) {
							$where = array(
                                'g.goods_code' => array('like', '%' . $val[0] . '%')
                            );
                            $field = 'g.id, g.goods_spec ,gc.goods_name, g.goods_code, gc.type';
                            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'b_goods_common as gc ON (gc.id = g.goods_common_id)';
                            $goods_info = D("Business/BGoods")->alias('g')->getInfo($where, $field, $join);
                            if (empty($goods_info))
                                continue;
                            if ($goods_info['type'] != $type) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与当前所选大类不一致，请选择【' . $type_tips[$goods_info['type']] . '】';
                                break 2;
                            }
                            if ($goods_info['price_mode'] != 0) {
                                $datas['status'] = 0;
                                $datas['msg'] = '导入的数据与存在计价方式为【计重】的产品，商品编码为【' . rtrim($val[0]) . '】，请筛除！';
                                break 2;
                            }

							$text .= '<tr id="'. $goods_info['id'].'" goods_code="'.$goods_info['goods_code'].'" class="p_num add_product">
                                <td class="text-center" style="padding: 8px 3px"><?php echo ($_GET["p"]?($_GET["p"]-1)*$numpage+$key+1:$key+1);?></td>
                                <td class="text-center" style="padding: 8px 3px">'. $goods_info['goods_code'].'</td>
                                <td><input class="product_code" type="text" value="'.$val[1].'"></td>
                                <td><input class="product_code" type="text" value="'.$val[2].'"></td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_name'].'</td>
                                <td class="text-center" style="padding: 8px 3px">'.$goods_info['goods_spec'].'</td>
                                <td><input class="product_age no_arrow" type="number" value="'.$val[4].'"></td>
                                <td><input class="cost_price no_arrow" type="number" step="0.001" value="'.$val[5].'"></td>
                                <td><input class="sell_price no_arrow" type="number" step="0.001" value="'.$val[6].'"></td>
                                <td><input class="extras no_arrow" type="number" value="'.$val[7].'"></td>
                                <td class="text-center">
                                    <a href="javascript:;" onclick="init_memo_iframe(this)" title="' . $val[8] . '">备注
                                        <span class="memo" hidden>' . $val[8] . '</span>
                                    </a>&nbsp;&nbsp;
                                    <a class="del" href="javascript:;">删除</a>
                                </td>
                    		</tr>';
						}
						break;
                }
                $datas['data'] = $text;
                $datas['rproduct_code_num'] = $rproduct_num;
                $datas['message']=$message;
            } else {
                $datas['status'] = '0';
                $datas['msg'] = $msg;
            }

        } elseif ($info['status'] == 0) {
            $datas['status'] = '0';
            $datas['msg'] = "导入的excel表格中无数据";
        }

        die(json_encode($datas));
	}

	// 分称完成
	public function split_done()
    {
        $id = I('get.id/d', 0);
        
        $info = array(
            'status' => 0,
            'url' => ''
        );
        
        $where = array(
            'id' => $id,
            'company_id' => get_company_id()
        );
        $storage_info = $this->bprocurestorage_model->getInfo($where);
        
        if (! empty($storage_info)) {
            
            if ($storage_info['storage_status'] != 0) {
                $this->error('操作失败，已经分称完成！');
            }
            
            $update_data = array(
                'storage_status' => 1,
                "status" => 0
            );
            
            M()->startTrans();
            
            $result = $this->bprocurestorage_model->update($where, $update_data);
            /*添加表单操作记录 add by lzy 2018.5.26 start*/
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_STORAGE,$id,BProcureStorageModel::COMMIT);
            /*添加表单操作记录 add by lzy 2018.5.26 end*/
            if ($result !== false) {
                
                $rs = D('BProduct')->toggle_unpass_with_storage_id($storage_info['id'], 0);
                if ($rs['status'] == 1) {
                    $info["status"] = 1;
                    $info["url"] = U('BStorage/index');
                }
            }
            
            if ($info['status'] == 1) {
                M()->commit();
            } else {
                M()->rollback();
            }
            
            $this->ajaxReturn($info);
        } else {
            $this->error('访问出错');
        }
    }

	// 读取excel文档并查询数据
	private function uploadExcel($file_name, $tmp_name)
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/excel/';

        if(!is_dir($filePath)){
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
            $data = excel_to_array($extend, $uploadfile);
        }

        @unlink($file_name);
        $info = array();
        if(!empty($data)){
            $info['data'] = $data;
            $info['status'] = '1';
        }else{
            $info['status'] = '0';
        }

        return $info;
    }

	// 处理表单提交的搜索关键词
	private function handleSearch(&$ex_where = NULL)
    {
		$main_tbl = C('DB_PREFIX') . 'b_procure_storage';
        
        $where = array();
        if (I('status') != '') {
            if (I('status') < 0) {
                $where[$main_tbl . '.storage_status'] = 0;
            }
            
            if (I('status') >= 0) {
                $where[$main_tbl . '.storage_status'] = 1;
                $where[$main_tbl . '.status'] = I('status');
            }
        }
        if (I('search_name')) {
            $where[$main_tbl . '.batch|p.batch|c_employee.employee_name|s.company_name|s_employee.employee_name'] = array(
                'LIKE',
                '%' . trim(I('search_name')) . '%'
            );
        }
        if (I('begin_time')) {
            $begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
            $begin_time = strtotime(date('Y-m-d 00:00:00', $begin_time));
            $where[$main_tbl . '.create_time'] = array('gt', $begin_time);
        }
        
        if (I('end_time')) {
            $end_time = I('end_time') ? strtotime(I('end_time')) : time();
            $end_time = strtotime(date('Y-m-d 23:59:59', $end_time));
            
            if (isset($begin_time)) {
                $p1 = $where[$main_tbl . '.create_time'];
                unset($where[$main_tbl . '.create_time']);
                
                $where[$main_tbl . '.create_time'] = array($p1, array('lt', $end_time));
            } else {
                $where[$main_tbl . '.create_time'] = array('lt', $end_time);
            }
        }
        
        $ex_where = array_merge($where, $ex_where);
        
        $request_data = $_REQUEST;
        
        $this->assign('request_data', $request_data);
	}

	// 处理提交数据
	private function handlePostData()
    {
        $result = $this->bprocurestorage_model->add_product();
        if ($result['status'] == 1) {
            S('session_menu' . get_user_id(), null);
            
            $info["status"] = "success";
            $info["url"] = U('BStorage/index');
        } elseif ($result['status'] == 0) {
            $info["status"] = "fail";
            $info["msg"] = $result['msg'];
        }
        
        $this->ajaxReturn($info);
    }

	// 获取excel模板
	private function getExcelUrl()
    {
        $type = I("type");
        $filename = 'example_storage' . $type . '.xlsx';
        $url = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_ADDR'] . $_SERVER['SERVER_PORT']);
        $example_excel = 'http://' . $url . '/Uploads/excel/' . $filename;
        $this->assign('example_excel', $example_excel);
    }
}