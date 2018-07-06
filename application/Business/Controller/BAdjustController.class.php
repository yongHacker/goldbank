<?php
/**
 * @author lzy 2018-06-13
 * 货品调整
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Business\Model\BAdjustModel;

class BAdjustController extends BusinessbaseController
{

    public $model_badjust, $model_badjustdetail, $model_bproduct;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        parent::_initialize();

        $this->model_badjust = D('BAdjust');
        $this->model_badjustdetail = D('BAdjustDetail');
        $this->model_bproduct = D('BProduct');
    }

    // 添加
    public function add()
    {
        $this->_save(ACTION_NAME);
    }

    // 编辑
    public function edit()
    {
        $this->_save(ACTION_NAME);
    }

    // 列表
    public function index()
    {
        $this->_get_list(ACTION_NAME);
    }

    // 详情
    public function detail()
    {
        $this->_get_detail();
        $this->display();
    }

    // 审核列表
    public function check_index()
    {
        $this->_get_list(ACTION_NAME);
    }

    // 审核
    public function check()
    {
        if (IS_POST) {
            $info = $this->model_badjust->checkAdjust();

            if($info['status'] == 1){
                $info['url'] = U('BAdjust/check_index');
            }

            $this->ajaxReturn($info);
        } else {
            $this->_get_detail(ACTION_NAME);
            $this->display();
        }
    }

    // 撤销
    public function cancel()
    {
        $info = $this->model_badjust->cancel();

        if ($info['status'] == 1) {
            $info['url'] = U('BAdjust/index');
        }

        $this->ajaxReturn($info);
    }

    // 删除
    public function delete()
    {
        $info = $this->model_badjust->delete();

        if ($info['status'] == 1) {
            $info['url'] = U('BAdjust/index');
        }

        $this->ajaxReturn($info);
    }

    // 类型权限点 - 采购信息调整
    public function procure_adjust()
    {}

    // 类型权限点 - 销售信息调整
    public function sell_adjust()
    {}

    // 类型权限点 - 商品规格调整
    public function goods_adjust()
    {}

    private function _save($action)
    {
        if (! IS_POST) {
            // 输出调整类型
            $adjust_type = array();
            if (sp_auth_check(get_user_id(), 'Business/BAdjust/procure_adjust')) {
                $adjust_type[1] = '采购信息调整';
            }
            if (sp_auth_check(get_user_id(), 'Business/BAdjust/sell_adjust')) {
                $adjust_type[2] = '销售信息调整';
            }
            if (sp_auth_check(get_user_id(), 'Business/BAdjust/goods_adjust')) {
                $adjust_type[3] = '商品规格调整';
            }

            // 下载的EXCEL模板的链接地址
            $url = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_ADDR'] . $_SERVER['SERVER_PORT']);
            $example_excel = 'http://' . $url . '/Uploads/excel/example_adjust{type}.xlsx';
            $this->assign('example_excel', $example_excel);
            // 单据类型 1-采购 2-销售 3-商品规格
            $this->assign('adjust_type', $adjust_type);
            // 单据详情
            $this->_get_detail();
            // 输出员工信息
            $this->assign('employee_info', get_employee_info());
            $this->display('save');
        } else {
            $adjust_data = array(
                'status' => I('post.status'),
                'adjust_type' => I('post.adjust_type'),
                'num' => I('post.num'),
                'memo' => I('post.memo'),
                'product_data' => I('post.product_data'),
                'adjust_id' => I('post.adjust_id')
            );
            $result = D('BAdjust')->saveAdjust($adjust_data);
            if ($result['status'] == 1) {
                output_data(array(
                    'adjust_id' => $result['adjust_id'],
                    'url' => U('BAdjust/index')
                ));
            } else {
                output_error($result['msg']);
            }
        }
    }

    // 获取商品规格列表
    public function goods_list()
    {
        $adjust_type = I('adjust_type/d', 1);
        $condition = array();
        if ($adjust_type == 1) {
            $condition['gb_b_product.status'] = array('not in', array(1, 9, 10));
        } else if ($adjust_type == 2) {
            $condition['gb_b_product.status'] = array('in', array(2, 3));
        }

        A('BGoods')->goods_iframe($condition);
        $this->display();
    }

    // 根据商品规格获取货品列表
    public function get_goods_product()
    {
        $adjust_type = I('post.adjust_type/d', 0);
        $product_codes = I('post.product_codes');
        $goods_ids = I('post.goods_ids');

        $product_list = $this->model_badjust->getGoodsProduct($goods_ids, $product_codes, $adjust_type);

        // 货品状态
        $status_list = $this->model_badjust->_getProductStatusComment();

        $text = '';
        foreach ($product_list as $key => $value) {
            if ($adjust_type == 1) {
                // 采购信息调整
                $text .= $this->create_html_procure(
                    $value['product_code'],
                    $value['goods_name'],
                    $value['goods_code'],
                    ($value['procurement_pricemode'] == 1 ? '计重' : '计件'),
                    ($value['procurement_pricemode'] == 1 ? $value['weight'] : '-'),
                    ($value['procurement_pricemode'] == 1 ? $value['buy_m_fee'] : '-'),
                    ($value['procurement_pricemode'] == 1 ? bcmul($value['weight'], $value['buy_m_fee'], 2) : '-'),
                    ($value['procurement_pricemode'] == 1 ? '-' : $value['cost_price']),
                    $status_list[$value['product_status']],
                    $value['procurement_pricemode'],
                    $value['product_id']
                );
            } elseif ($adjust_type == 2) {
                // 销售信息调整
                $text .= $this->create_html_sell(
                    $value['product_code'],
                    $value['goods_name'],
                    $value['goods_code'],
                    ($value['sell_pricemode'] == 1 ? '计重销售' : '计价销售'),
                    ($value['sell_pricemode'] == 1 ? ($value['sell_feemode'] == 1 ? '克工费销售' : '件工费销售') : '-'),
                    ($value['sell_pricemode'] == 1 ? '-' : $value['sell_price']),
                    ($value['sell_pricemode'] == 1 ? $value['sell_fee'] : '-'),
                    $status_list[$value['product_status']],
                    $value['sell_pricemode'],
                    $value['sell_feemode'],
                    $value['product_id']
                );
            } elseif ($adjust_type == 3) {
                // 商品规格调整
                $text .= $this->create_html_goods(
                    $value['product_code'],
                    $value['goods_name'],
                    $value['goods_code'],
                    '',
                    $status_list[$value['product_status']],
                    $value['product_id']
                );
            }
        }
        $datas = array(
            'status' => 1,
            'msg' => '导入成功',
            'text' => $text
        );
        output_data($datas);
    }

    // 获取货品列表
    public function product_list()
    {
        $adjust_type = I('adjust_type/d', 0);
        if ($adjust_type == 1) {
            // 采购信息调整
            $condition = array(
                'bproduct.status' => array('not in', '1, 10')
            );
            A('BProduct')->product_list($condition);
        } elseif ($adjust_type == 2) {
            // 销售信息调整
            $condition = array(
                'bproduct.status' => array('in', '2, 3')
            );
            A('BProduct')->product_list($condition, 3);
        } elseif ($adjust_type == 3) {
            // 商品规格调整
            A('BProduct')->product_list();
        }
        $this->display();
    }
    
    // 获取单据列表
    protected function _get_list($action = 'index')
    {
        $condition = $this->_get_condition($action);
        $field = 'ba.*, creator_employee.employee_name as creator_name';
        $join .= ' LEFT JOIN __B_EMPLOYEE__ creator_employee ON ba.creator_id = creator_employee.user_id AND creator_employee.company_id = ' . get_company_id();
        $order = 'ba.id desc';
        
        $count = $this->model_badjust->alias('ba')->countList($condition, '*', $join, $order);
        $page = $this->page($count, $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $this->model_badjust->alias('ba')->getList($condition, $field, $limit, $join, $order);

        // 调整类型
        $type_value = $this->model_badjust->_getAdjustTypeComment();
        foreach ($list as $key => $value) {
            $list[$key]['type'] = $type_value[$value['type']];
        }
        
        $this->assign('list', $list);
        
        $this->b_show_status('b_adjust');
        
        $this->assign('search_placeholder', '编号/制单人');
        
        $this->display();
    }

    // 获取列表查询条件
    protected function _get_condition($action_name = 'index')
    {
        $search_name = I('request.search_name/s', '');
        $create_begin_time = I('request.create_begin_time/s', '');
        $create_end_time = I('request.create_end_time/s', '');
        
        $condition = array(
            'ba.company_id' => get_company_id(),
            'ba.deleted' => 0
        );
        if (! empty($search_name)) {
            $condition['ba.batch|create_employee.employee_name'] = array('like', '%' . $search_name . '%');
        }
        if ($action_name == 'check_index') {
            $_REQUEST['status'] = '0';
        }
        if ($_REQUEST['status'] || $_REQUEST['status'] === '0') {
            $condition['ba.status'] = $_REQUEST['status'];
        }
        // 时间条件： 2018/01/01 ~ 2018/01/02 使用 2018/01/01 到 2018/01/03减一秒 的区间
        if ($create_begin_time || $create_end_time) {
            if ($create_begin_time && $create_end_time) {
                $condition[] = array(
                    array('ba.create_time' => array('gt', strtotime($create_begin_time))),
                    array('ba.create_time' => array('elt', strtotime($create_end_time) + 86399))
                );
            } elseif ($create_begin_time) {
                $condition['ba.create_time'] = array('gt', strtotime($create_begin_time));
            } else {
                $condition['ba.create_time'] = array('elt', strtotime($create_end_time) + 86399);
            }
        }
        return $condition;
    }

    // 获取单据详情
    protected function _get_detail()
    {
        $adjust_id = I('request.adjust_id/d', 0);
        if (!empty($adjust_id)) {
            $this->b_show_status('b_product');

            $condition = array(
                'ba.id' => $adjust_id,
                'ba.company_id' => get_company_id(),
                'ba.deleted' => 0
            );
            $adjust_info = $this->model_badjust->getAdjustDetail($adjust_id, 1);
            $this->assign('adjust_info', $adjust_info);
            
            $adjust_type = $this->model_badjust->_getAdjustTypeComment();
            $this->assign('adjust_type', $adjust_type);

            // 表单的操作记录
            $operate_record = $this->model_badjust->getOperateRecord($adjust_id);
            $this->assign('operate_record', $operate_record);

            // 表单的操作流程
            $operate_process = $this->model_badjust->getProcess($adjust_id);
            $this->assign('process_list', $operate_process);
        }
    }

    // excel上传
    public function excel_input()
    {
        $file_name = $_FILES['excel_file']['name'];
        $tmp_name = $_FILES['excel_file']['tmp_name'];
        $info = $this->uploadExcel($file_name, $tmp_name);
        $datas = array('status'=> 0, 'msg'=> '上传失败');

        // 货品状态
        $status_list = $this->model_badjust->_getProductStatusComment();

        $adjust_type = I('post.adjust_type/d', 1);
        $adjust_type_name = $adjust_type == 1 ? '采购信息' : ($adjust_type == 2 ? '销售信息' : '商品规格');
        $product_codes = I('post.product_codes/s', '');
        $product_codes = explode(',', $product_codes);

        if ($info['status'] == 1 && count($info['data']) > 0) {
            $text = '';
            foreach ($info['data'] as $key => $value) {
                // 货品编码去重
                $product_code = $value[0];
                if (!in_array($product_code, $product_codes)) {
                    // 货品存在性验证
                    $product_info = $this->model_badjust->getProductInfo($product_code);
                    if (empty($product_info)) {
                        output_data(array('status'=> 0, 'msg'=> '货品编码' . $product_code . '不存在！'));
                    }

                    // 货品状态可调整验证
                    $check_status = $this->model_badjust->_checkProductStatus($product_info['product_code'], $product_info['product_status'], $adjust_type);
                    if ($check_status !== true) {
                        output_data(array('status'=> 0, 'msg'=> $check_status));
                    }

                    if ($adjust_type == 1) {
                        // 采购信息调整
                        if ($value[1] != $product_info['procurement_pricemode']) {
                            output_data(array('status' => 0, 'msg'=> '货品编码' . $product_info['product_code'] . '采购计价方式错误，请验证！'));
                        }
                        $text .= $this->create_html_procure(
                            $product_info['product_code'], 
                            $product_info['goods_name'],
                            $product_info['goods_code'],
                            ($product_info['procurement_pricemode'] == 1 ? '计重' : '计件'),
                            ($product_info['procurement_pricemode'] == 1 ? $value[2] : '-'),
                            ($product_info['procurement_pricemode'] == 1 ? $value[3] : '-'),
                            ($product_info['procurement_pricemode'] == 1 ? bcmul($value[2], $value[3], 2) : '-'),
                            ($product_info['procurement_pricemode'] == 1 ? '-' : $value[4]),
                            $status_list[$product_info['product_status']], 
                            $product_info['procurement_pricemode'],
                            $product_info['product_id']
                        );
                    } elseif ($adjust_type == 2) {
                        // 销售信息调整
                        if ($value['1'] != $product_info['sell_pricemode']) {
                            output_data(array('status' => 0, 'msg'=> '货品编码' . $product_info['product_code'] . '销售计价方式错误，请验证！'));
                        }
                        if ($product_info['sell_pricemode'] == 1 && $value['2'] != $product_info['sell_feemode']) {
                            output_data(array('status' => 0, 'msg'=> '货品编码' . $product_info['product_code'] . '销售工费方式错误，请验证！'));
                        }
                        $text .= $this->create_html_sell(
                            $product_info['product_code'], 
                            $product_info['goods_name'],
                            $product_info['goods_code'],
                            ($value[1] == 1 ? '计重销售' : '计价销售'),
                            ($value[1] == 1 ? ($value[2] == 1 ? '克工费销售' : '件工费销售') : '-'),
                            ($value[1] == 1 ? '-' : $value['3']),
                            ($value[1] == 1 ? $value['4'] : '-'),
                            $status_list[$product_info['product_status']], 
                            $value[1],
                            $value[2],
                            $product_info['product_id']
                        );
                    } elseif ($adjust_type == 3) {
                        // 商品规格调整
                        $agc_check = $this->model_badjust->_agcCheck($product_info['goods_code'], $value[1], $agc_name);
                        if ($agc_check !== true) {
                            output_data(array('status' => 0, 'msg'=> '调整后的商品大类与旧商品大类不一致，请调整为[' . $agc_name . ']大类下的规格编码！'));
                        }
                        $text .= $this->create_html_goods(
                            $product_info['product_code'], 
                            $product_info['goods_name'],
                            $product_info['goods_code'],
                            $value[1],
                            $status_list[$product_info['product_status']], 
                            $product_info['product_id']
                        );
                    }
                }
            }
        }
        $datas = array(
            'status' => 1,
            'msg' => '导入成功',
            'text' => $text
        );
        output_data($datas);
    }

    // 读取excel文档并查询数据
    private function uploadExcel($file_name, $tmp_name){
        $filePath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/excel/';
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
            $data = excel_to_array($extend, $uploadfile);
        }

        @unlink($file_name);
        $info = array();
        if(!empty($data)){
            $info['data'] = $data;
            $info['status'] = 1;
        }else{
            $info['status'] = 0;
        }

        return $info;
    }

    // 构建采购信息调整的html
    protected function create_html_procure($product_code, $product_name, $goods_code, $pricemode_name, $weight, $buy_m_fee, $total_fee, $cost_price, $status_name, $pricemode, $product_id)
    {
        if ($pricemode == 1) {
            $weight_html = '<input type="text" class="text-right" name="weight" value="' . $weight . '">';
            $buy_m_fee_html = '<input class="text-right" type="text" name="buy_m_fee" value="' . $buy_m_fee . '">';
            $cost_price_html = $cost_price;
        } else if ($pricemode == 0) {
            $weight_html = $weight;
            $buy_m_fee_html = $buy_m_fee;
            $cost_price_html = '<input class="text-right" type="text" name="cost_price" value="' . $cost_price . '">';
        }
        $text = '<tr class="plus" data-productid="' . $product_id . '" data-productcode="' . $product_code . '" data-pricemode="' . $pricemode . '">';
        $text .= '<td class="text-center"></td>';
        $text .= '<td class="text-center product_code">' . $product_code . '</td>';
        $text .= '<td class="text-center product_name">' . $product_name . '</td>';
        $text .= '<td class="text-center goods_code">' . $goods_code . '</td>';
        $text .= '<td class="text-center pricemode">' . $pricemode_name . '</td>';
        $text .= '<td class="text-center weight">' . $weight_html . '</td>';
        $text .= '<td class="text-center buy_m_fee">' . $buy_m_fee_html . '</td>';
        $text .= '<td class="text-right total_fee">' . $total_fee . '</td>';
        $text .= '<td class="text-center cost_price">' . $cost_price_html . '</td>';
        $text .= '<td class="text-center status_name">' . $status_name . '</td>';
        $text .= '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除""></a></td>';
        $text .= '</tr>';
        return $text;
    }

    // 构建销售信息调整的html
    protected function create_html_sell($product_code, $product_name, $goods_code, $sell_pricemode_name, $sell_feemode_name, $sell_price, $sell_fee, $status_name, $sell_pricemode, $sell_feemode, $product_id)
    {
        if ($sell_pricemode == 1) {
            $sell_price_html = $sell_price;
            $sell_fee_html = '<input class="text-right" name="sell_fee" type="text" value="' . $sell_fee . '">';
        } else if ($sell_pricemode == 0){
            $sell_price_html = '<input class="text-right" name="sell_price" type="text" value="' . $sell_price . '">';
            $sell_fee_html = $sell_fee;
        }
        $text = '<tr class="plus" data-productid="' . $product_id . '" data-productcode="' . $product_code . '" data-sellpricemode="' . $sell_pricemode . '" data-sellfeemode="' . $sell_feemode . '">';
        $text .= '<td class="text-center"></td>';
        $text .= '<td class="text-center product_code">' . $product_code . '</td>';
        $text .= '<td class="text-center product_name">' . $product_name . '</td>';
        $text .= '<td class="text-center goods_code">' . $goods_code . '</td>';
        $text .= '<td class="text-center sell_pricemode">' . $sell_pricemode_name . '</td>';
        $text .= '<td class="text-center sell_feemode">' . $sell_feemode_name . '</td>';
        $text .= '<td class="text-center sell_price">' . $sell_price_html . '</td>';
        $text .= '<td class="text-center sell_fee">' . $sell_fee_html . '</td>';
        $text .= '<td class="text-center status_name">' . $status_name . '</td>';
        $text .= '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除""></a></td>';
        $text .= '</tr>';
        return $text;
    }

    // 构建商品规格调整的html
    protected function create_html_goods($product_code, $product_name, $old_goods_code, $new_goods_code, $status_name, $product_id)
    {
        $text = '<tr class="plus" data-productid="' . $product_id . '" data-productcode="' . $product_code . '">';
        $text .= '<td class="text-center"></td>';
        $text .= '<td class="text-center product_code">' . $product_code . '</td>';
        $text .= '<td class="text-center product_name">' . $product_name . '</td>';
        $text .= '<td class="text-center old_goods_code">' . $old_goods_code . '</td>';
        $text .= '<td class="text-center new_goods_code"><input type="text" class="text-center" name="new_goods_code" value="' . $new_goods_code . '"></td>';
        $text .= '<td class="text-center status_name">' . $status_name . '</td>';
        $text .= '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除""></a></td>';
        $text .= '</tr>';
        return $text;
    }

    /**
     * 货品的调整记录
     */
    public function product_record()
    {
        $product_id = I('request.product_id/d', 0);
        $product_code = I('request.product_code');
        $adjust_type = I('request.adjust_type/d', 0);

        $condition = array(
            'bproduct.company_id' => get_company_id(),
            'badjust.deleted' => 0
        );
        if (empty($product_id)) {
            $condition['product_code'] = array('like', "%{$product_code}%");
        } else {
            $condition['id'] = $product_id;
        }

        if (!empty($adjust_type)) {
            $condition['badjust.type'] = $adjust_type;
        }
        $field = 'badjust.id, badjust.batch as adjust_batch, badjust.type as adjust_type, badjustdetail.adjust_before, badjustdetail.adjust_after';
        $join = ' LEFT JOIN __B_ADJUST_DETAIL__ badjustdetail ON badjustdetail.p_id = bproduct.id AND badjustdetail.deleted = 0';
        $join .= ' LEFT JOIN __B_ADJUST__ badjust ON badjustdetail.ad_id = badjust.id';

        $count = $this->model_bproduct->alias('bproduct')->countList($condition, $field, $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $record_list = $this->model_bproduct->alias('bproduct')->getList($condition, $field, $limit, $join);
        if (!empty($record_list)) {
            foreach ($record_list as $key => $value) {
                $record_list[$key]['adjust_before'] = json_decode($value['adjust_before'], true);
                $record_list[$key]['adjust_after'] = json_decode($value['adjust_after'], true);
            }
        }
        $this->assign('page', $page->show('Admin'));
        $this->assign('record_list', $record_list);

        $adjust_type = $this->model_badjust->_getAdjustTypeComment();
        $this->assign('adjust_type', $adjust_type);

        $this->display();
    }
}