<?php
/**
 * @author lzy 2018-06-01
 * 采购退货管理
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BProcureReturnController extends BusinessbaseController
{

    public $model_warehoust, $model_procure_return, $model_product, $model_supplier, $model_bexpence;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        parent::_initialize();

        $this->model_product = D('BProduct');
        $this->model_warehouse = D('BWarehouse');
        $this->model_procure_return = D('BProcureReturn');
        $this->model_supplier = D('BSupplier');
        $this->model_bexpence = D('BExpence');
        $this->model_bexpence_sub = D('BExpenceSub');
    }

    // 采购退货列表
    public function index()
    {
        $this->get_list('index');
        $this->display('index');
    }

    // 财务采购退货列表
    public function index1()
    {
        $this->index();
    }

    // 采购退货审核列表
    public function check_index()
    {
        $this->get_list(ACTION_NAME);
        $this->display();
    }

    // 采购退货上传凭证列表
    public function payment_index()
    {
        $this->get_list(ACTION_NAME);
        $this->display();
    }

    // 统一获取单据列表
    public function get_list($action_name = 'index') {
        $condition = $this->_get_condition($action_name);
        $field = 'pr.*,wh.wh_name,create_employee.employee_name creator_name';
        $join = ' left join gb_b_warehouse wh on wh.id=pr.wh_id';
        $join .= ' left join gb_b_employee create_employee on create_employee.user_id=pr.creator_id';
        $order = 'pr.id desc';
        $count = $this->model_procure_return->alias('pr')->countList($condition, '*', $join, $order);
        $page = $this->page($count, $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $limit = $page->firstRow . ',' . $page->listRows;
        $pr_list = $this->model_procure_return->alias('pr')->getList($condition, $field, $limit, $join, $order);
        $this->assign('pr_list', $pr_list);

        $this->b_show_status('b_procure_return');

        $this->assign('action_name', $action_name);
    }

    /**
     * 获取列表查询条件
     * @return array 列表查询条件数组
     */
    public function _get_condition($action_name = 'index')
    {
        $search_name = I('request.search_name/s', '');
        $create_begin_time = I('request.create_begin_time/s', '');
        $create_end_time = I('request.create_end_time/s', '');
        $return_begin_time = I('request.return_begin_time/s', '');
        $return_end_time = I('request.return_end_time/s', '');

        $condition = array(
            'pr.company_id' => get_company_id(),
            'pr.deleted' => 0
        );
        if (!empty($search_name)) {
            $condition['pr.batch|create_employee.employee_name|wh.wh_name'] = array('like', '%' . $search_name . '%');
        }
        if ($action_name == 'check_index') {
            $_REQUEST['status'] = '0';
        } elseif ($action_name == 'payment_index') {
            $_REQUEST['status'] = '1';
        }
        if ($_REQUEST['status'] || $_REQUEST['status'] === '0') {
            $condition['pr.status'] = $_REQUEST['status'];
        }
        // 时间条件： 2018/01/01 ~ 2018/01/02 使用 2018/01/01 到 2018/01/03减一秒 的区间
        if ($create_begin_time || $create_end_time) {
            if ($create_begin_time && $create_end_time) {
                $condition[] = array(
                    array('pr.create_time' => array('gt', strtotime($create_begin_time))),
                    array('pr.create_time' => array('elt', strtotime($create_end_time) + 86399)),
                );
            } elseif ($create_begin_time) {
                $condition['pr.create_time'] = array('gt', strtotime($create_begin_time));
            } else {
                $condition['pr.create_time'] = array('elt', strtotime($create_end_time) + 86399);
            }
        }
        if ($return_begin_time || $return_end_time) {
            if ($return_begin_time && $return_end_time) {
                $condition[] = array(
                    array('pr.return_time' => array('gt', strtotime($return_begin_time))),
                    array('pr.return_time' => array('elt', strtotime($return_end_time) + 86399)),
                );
            } elseif ($return_begin_time) {
                $condition['pr.return_time'] = array('gt', strtotime($return_begin_time));
            } else {
                $condition['pr.return_time'] = array('elt', strtotime($return_end_time) + 86399);
            }
        }
        return $condition;
    }

    // 采购退货保存
    protected function save()
    {
        $return_id = I('return_id/d', 0);
        $this->assign('return_id', $return_id);
        if (! IS_POST) {
            $this->_get_detail();

            A('BExpence')->_expenceList(3);

            $wh_list = $this->model_warehouse->get_wh_list();
            $this->assign('wh_list', $wh_list);

            $this->assign('employee_info', get_employee_info());
            $this->assign('today', date('Y-m-d', time()));
            $this->model_supplier->get_list_assign($this->view);

            if (!empty($return_id)) {
                $sub_list = $this->model_bexpence_sub->getSublist(array('ticket_id' => $return_id, 'ticket_type' => 3));
                $this->assign('sub_list', $sub_list);
            }
            
            $this->display('save');
        } else {
            $insert = I('post.');
            $product_ids = $insert['product_ids'];
            unset($insert['product_ids']);
            $result = $this->model_procure_return->saveReturn($insert, $product_ids, $return_id);
            if ($result !== true) {
                output_error($result);
            } else {
                output_data(array(
                    'msg' => '操作成功！',
                    'return_id' => $return_id,
                    'url' => U('BProcureReturn/index')
                ));
            }
        }
    }

    // 采购退货开单
    public function add()
    {
        $this->save(ACTION_NAME);
    }

    // 采购退货编辑
    public function edit()
    {
        $this->save(ACTION_NAME);
    }

    // 退货开单货品列表
    public function product_list()
    {
        $condition = array(
            'bproduct.warehouse_id' => I('wh_id/d', 0),
            'bproduct.status' => 2,
            'pm.supplier_id' => I('supplier_id/d', 0)
        );
        A('BProduct')->product_list($condition);
        $this->display();
    }

    // 选中采购单 获取对应货品详情
    public function get_pro_product()
    {
        $wh_id = I('post.wh_id/d', 0);
        $ids = I('post.ids');
        $product_codes = rtrim(I('post.product_codes'), ',');

        $product = $this->model_warehouse->get_procure_product($ids, $wh_id, 1);

        $product_list = array();
        $select_product_codes = empty($product_codes) ? array() : explode(',', $product_codes);
        foreach ($product as $key => $val) {
            if(in_array($val['product_code'], $select_product_codes)) {
                //避免前端已经选择的货品重复选择
                continue;
            } else {
                //避免调拨单的货品多次调拨导致货品重复
                array_push($select_product_codes, $val['product_code']);
            }
            $product_list[] = array(
                'id' => $val['id'],
                'product_code' => $val['product_code'],
                'sub_product_code' => empty($val['sub_product_code']) ? '-' : $val['sub_product_code'],
                'product_name' => $val['goods_name'],
                'goods_code' => $val['goods_code'],
                'pricemode' => $val['procurement_pricemode'],
                'weight' => $val['weight'],
                'buy_m_fee' => $val['buy_m_fee'],
                'cost_price' => $val['cost_price']
            );
        }
        $data['product_list'] = $product_list;
        output_data($data);
    }

    // 采购清单
    public function procure_list()
    {
        $model_procurement = D('BProcurement');
        $batch = trim(I("batch"));
        $wh_id = I("wh_id", 0, 'intval');
        $supplier_id = I("supplier_id", 0, 'intval');
        $mystore = I("mystore", 0, 'intval');
        $warehouse_id = $wh_id ? $wh_id : $mystore;
        if ($batch) {
            $where['gb_b_procurement.batch'] = array('like', "%$batch%");
        }
        
        $join = ' join gb_b_procure_storage as ps on ps.procurement_id = gb_b_procurement.id' . ' and gb_b_procurement.supplier_id = ' . $supplier_id;
        $join .= ' join gb_b_product as p on p.storage_id = ps.id and p.status = 2 and p.deleted = 0 and p.warehouse_id = ' . $warehouse_id . ' and p.company_id = ' . $this->MUser['company_id'];
        $join .= ' join gb_m_users as u on u.id = gb_b_procurement.creator_id';
        $join .= ' join gb_b_supplier as s on s.id = gb_b_procurement.supplier_id';
        
        $field = 'gb_b_procurement.*,u.user_nicename,s.company_name';
        $group = 'gb_b_procurement.id';
        $order = 'gb_b_procurement.id desc';
        
        $list = $model_procurement->getList($where, $field, '', $join, $order, $group);
        
        $count = $list ? count($list) : 0;
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $procure_list = $model_procurement->getList($where, $field, $limit, $join, $order, $group);
        if (empty($procure_list)) {
            $this->assign('empty_info', '未查询到采购清单');
        } else {
            $this->assign('procure_list', $procure_list);
        }
        $this->display();
    }

    // 采购退货详情
    public function detail()
    {
        $this->_get_detail();
        $this->display('detail');
    }

    // 财务采购退货详情
    public function detail1()
    {
        $this->detail();
    }

    // 采购退货上传凭证详情
    public function payment()
    {
        if (IS_POST) {
            $return_id = I('return_id/d', 0);
            $result = $this->model_procure_return->changeImg($return_id);
            output_data(array('msg'=>'操作成功！'));
        } else {
            $this->assign('today', date('Y-m-d H:i'));
            $this->_get_detail();
            $this->display();
        }
    }

    // 凭证上传 - ajax
    public function upload_pic()
    {
        $year = date('Y', time());
        $dir_path ='ReturnPayment/'.$year.'/';
        $info = upload_pic_by_count($dir_path, $_FILES['upload_payment_pic'], 'image', I('post.del_upload_pic'));
        die(json_encode($info));
    }

    // 采购退货单删除
    public function delete(){

        $info = $this->model_procure_return->delete();

        if ($info['status'] == 1) {
            $info["url"] = U('BProcureReturn/index');
        }

        $this->ajaxReturn($info);
    }

    // 采购退货单撤销
    public function cancel(){
        $info = $this->model_procure_return->cancel();

        if ($info['status'] == 1) {
            $info["status"] = "success";
            $info["url"] = U('BProcureReturn/index');
        } else {
            $info["status"] = 'fail';
        }

        $this->ajaxReturn($info);
    }

    // 采购退货审核
    public function check()
    {
        if (IS_POST) {

            $result = $this->model_procure_return->check_return();

            if($result['status'] == 1){
                $info["status"] = "success";
                $info["url"] = U('BProcureReturn/check_index');
            }else{
                $info["status"] = 'fail';
            }
            $info['msg'] = $result['msg'];

            $this->ajaxReturn($info);
        } else {
            $this->_get_detail();
            $this->display();
        }
    }

    // 采购退货明细
    private function _get_detail($return_id)
    {
        $return_id = empty($return_id) ? I('return_id/d', 0) : $return_id;
        $return_info = $this->model_procure_return->getDetail($return_id);
        $return_info['payment_pic'] = explode('|', $return_info['payment_pic']);
        $this->assign('return_info', $return_info);

        $sub_list = $this->model_bexpence_sub->getSublist(array('ticket_id' => $return_id, 'ticket_type' => 3));
        $this->assign('sub_list', $sub_list);

        // 表单的操作记录
        $operate_record=$this->model_procure_return->getOperateRecord($return_id);
        $this->assign('operate_record', $operate_record);
        // 表单的操作流程
        $operate_process=$this->model_procure_return->getProcess($return_id);
        $this->assign('process_list', $operate_process);
    }
}