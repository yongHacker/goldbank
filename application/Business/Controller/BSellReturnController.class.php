<?php
/**
 * 销售退货
 * @author  alam
 * @time 14:56 2018/6/13
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;
use Business\Model\BBillOpRecordModel;

class BSellReturnController extends BusinessbaseController {

	public $model_bshop, $model_bexpence, $model_bexpencesub, $model_bsell, $model_bselldetail, $model_bsellreturn, $model_bsellredetail, $model_bpayment, $model_bcurrency, $model_bclient;

	public function __construct()
	{
		parent::__construct();
        $this->b_show_status('b_sell_return');

        $this->model_bshop = D('BShop');
        $this->model_bexpence = D('BExpence');
        $this->model_bexpencesub = D('BExpenceSub');
        $this->model_bsell = D('BSell');
        $this->model_bselldetail = D('BSellDetail');
        $this->model_bsellreturn = D('BSellReturn');
        $this->model_bsellredetail = D('BSellRedetail');
        $this->model_bpayment = D('BPayment');
        $this->model_bcurrency = D('BCurrency');
        $this->model_bclient = D('BClient');
	}

	public function _initialize()
	{
		parent::_initialize();
	}

	/**
	 * 退货开单
	 */
	public function add()
	{
		$this->save(ACTION_NAME);
	}

	/**
	 * 单据编辑
	 */
	public function edit()
	{
		$this->save(ACTION_NAME);
	}

	/**
	 * 单据保存
	 * @param  string $action 方法名称
	 */
	protected function save($action = 'add')
	{
		$return_id = I('request.return_id/d', 0);
		if (IS_POST) {
			$type = I('request.type/d', 0);
			$result = $this->model_bsellreturn->saveReturn($return_id);
            if ($result !== true) {
            	$msg = $result['msg'];
            	unset($result['msg']);
                output_error($msg, $result);
            } else {
                output_data(array(
                    'msg' => '操作成功！',
                    'return_id' => $return_id,
                    'url' => (($action == 'add' || $type === 0) ? U('BSellReturn/index') : '')
                ));
            }
		} else {
			// 输出商户门店数据
			$this->_shopList();
			// 输出其它费用类目
			A('BExpence')->_expenceList(4);
			// 收款方式
			$where = array(
                'company_id'=> get_company_id(),
                'deleted'=> 0,
                'status'=> 1
            );
            $payment = $this->model_bpayment->getList($where);
            $currency = $this->model_bcurrency->getList($where, $field = '*', $limit = null, $join = '', $order = 'is_main desc');

            $payment = json_encode($payment);
            $this->assign('payment', $payment);
            $this->assign('currency', $currency);

            if (!empty($return_id)) {
            	$return = $this->_get_detail();
            }

			$this->assign('return_id', $return_id);
            $this->assign('today', date('Y-m-d'));
            $this->assign('employee_name', $this->MUser['employee_name']);
			$this->display('save');
		}
	}

	/**
	 * 单据列表
	 */
	public function index()
	{
		$this->_get_list(ACTION_NAME);
	}

	/**
	 * 审核列表
	 */
	public function check_index()
	{
		$this->_get_list(ACTION_NAME);
	}

	/**
	 * 内部函数 获取退货列表
	 * @param  string $action　方法名称
	 * @return array  $list    退货列表
	 */
	protected function _get_list($action = 'index')
	{
		$condition = $this->_get_condition($action);
		$field = 'bsr.*, bc.client_name, be.employee_name as creator_name, IFNULL(bs.shop_name, "总部") as shop_name';
		$join = 'LEFT JOIN __B_CLIENT__ bc ON bsr.client_id = bc.id AND bc.company_id = ' . get_company_id();
		$join .= ' LEFT JOIN __B_EMPLOYEE__ be ON bsr.creator_id = be.user_id AND be.company_id = ' . get_company_id();
		$join .= ' LEFT JOIN __B_SHOP__ bs ON bsr.shop_id = bs.id AND be.company_id = ' . get_company_id();
		$order = 'bsr.id desc';

        $count = $this->model_bsellreturn->alias('bsr')->countList($condition, '*', $join, $order);
        $page = $this->page($count, $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $limit = $page->firstRow . ',' . $page->listRows;
        $sr_list = $this->model_bsellreturn->alias('bsr')->getList($condition, $field, $limit, $join, $order);

		$this->assign('sr_list', $sr_list);

        $this->b_show_status('b_sell_return');

		$this->display();
	}

    /**
     * 获取列表查询条件
     * @return array 列表查询条件数组
     */
    public function _get_condition($action = 'index')
    {
        $search_name = I('request.search_name/s', '');
        $create_begin_time = I('request.create_begin_time/s', '');
        $create_end_time = I('request.create_end_time/s', '');
        $return_begin_time = I('request.return_begin_time/s', '');
        $return_end_time = I('request.return_end_time/s', '');

        $condition = array(
            'bsr.company_id' => get_company_id(),
            'bsr.deleted' => 0
        );
        if (!empty($search_name)) {
            $condition['bsr.order_id|bc.client_name|bc.client_moblie'] = array('like', '%' . $search_name . '%');
        }
        if ($action == 'check_index') {
            $_REQUEST['status'] = '0';
        }
        if ($_REQUEST['status'] || $_REQUEST['status'] === '0') {
            $condition['bsr.status'] = $_REQUEST['status'];
        }
        // 时间条件： 2018/01/01 ~ 2018/01/02 使用 2018/01/01 到 2018/01/03减一秒 的区间
        if ($create_begin_time || $create_end_time) {
            if ($create_begin_time && $create_end_time) {
                $condition[] = array(
                    array('bsr.create_time' => array('gt', strtotime($create_begin_time))),
                    array('bsr.create_time' => array('elt', strtotime($create_end_time) + 86399)),
                );
            } elseif ($create_begin_time) {
                $condition['bsr.create_time'] = array('gt', strtotime($create_begin_time));
            } else {
                $condition['bsr.create_time'] = array('elt', strtotime($create_end_time) + 86399);
            }
        }
        if ($return_begin_time || $return_end_time) {
            if ($return_begin_time && $return_end_time) {
                $condition[] = array(
                    array('bsr.return_time' => array('gt', strtotime($return_begin_time))),
                    array('bsr.return_time' => array('elt', strtotime($return_end_time) + 86399)),
                );
            } elseif ($return_begin_time) {
                $condition['bsr.return_time'] = array('gt', strtotime($return_begin_time));
            } else {
                $condition['bsr.return_time'] = array('elt', strtotime($return_end_time) + 86399);
            }
        }
        return $condition;
    }

	/**
	 * 退货详情
	 */
	public function detail()
	{
		$this->_get_detail();
        $this->display('detail');
	}

    /**
     * ajax方法 销售退货单删除
     */
    public function delete()
    {
        $info = $this->model_bsellreturn->delete();

        if ($info['status'] == 1) {
            $info["url"] = U('BSellReturn/index');
        }

        $this->ajaxReturn($info);
    }

    // 采购退货单撤销
    public function cancel()
    {
        $info = $this->model_bsellreturn->cancel();

        if ($info['status'] == 1) {
            $info["url"] = U('BSellReturn/index');
        }

        $this->ajaxReturn($info);
    }

	/**
	 * 审核详情、审核提交
	 */
	public function check()
	{
        if (IS_POST) {
            $info = $this->model_bsellreturn->checkReturn();

            if($info['status'] == 1){
                $info["url"] = U('BSellReturn/check_index');
            }

            $this->ajaxReturn($info);
        } else {
            $this->_get_detail();
            $this->display();
        }
	}

	/**
	 * 内部函数 获取退货详情
	 * @param  string $action 方法名称
	 * @return array  $info   退货详情
	 */
	protected function _get_detail($action = 'detail', $return_id = '')
	{
		$return_id = empty($return_id) ? I('return_id/d', 0) : $return_id;

    	// 表单详情
    	if ($action == edit) {
    		$condition['status'] = array('in', '-1,-2');
    	}
    	$return_info = $this->model_bsellreturn->getDetailInfo($return_id, $condition);

    	// 其它费用
        $sub_list = $this->model_bexpencesub->getSublist(array('ticket_id' => $return_id, 'ticket_type' => 4));
        $this->assign('sub_list', $sub_list);

        // 收款信息
        $saccount_list = $this->model_bsell->getsaccount_list($return_id);
        // 确保输出的值为正值
        foreach ($saccount_list as $key => $value) {
        	$saccount_list[$key]['pay_price'] = abs($value['pay_price']);
        	$saccount_list[$key]['receipt_price'] = abs($value['receipt_price']);
        }
        $this->assign('saccount_list', $saccount_list);

    	// 表单列表
    	$return_info['return_product'] = $this->model_bsellredetail->getProductList($return_info['id']);
    	$this->assign('return_info', $return_info);

        // 表单的操作记录
        $operate_record = $this->model_bsellreturn->getOperateRecord($return_id);
        $this->assign('operate_record', $operate_record);

        // 表单的操作流程
        $operate_process = $this->model_bsellreturn->getProcess($return_id);
        $this->assign('process_list', $operate_process);
	}

	/**
	 * ajax方法 获取客户列表
	 */
	public function client_list()
	{
        if(empty($_POST['shop_id'])){
            $_POST['shop_id'] = 0;
        }
        A('BClient')->_getList();
        $this->display();
    }

	/**
	 * ajax方法 获取用户id对应销售清单
	 */
	public function sell_list()
	{
		$client_id = I('request.client_id/d', 0);
		$shop_id = I('request.shop_id/d', 0);
        $order_id = trim(I('request.order_id/d', ''));

        $company_id = get_company_id();

		$condition = array(
			'bsell.client_id' => $client_id,
			'bsell.company_id' => $company_id,
			'bsell.deleted' => 0,
			'bsell.status' => 1,
			'bselldetail.sell_id' => array('exp', 'IS NOT NULL')
		);
		if (isset($_REQUEST['shop_id'])) {
			$condition = array_merge(array(
				'bsell.shop_id' => $shop_id
			), $condition);
		}
		if ($_REQUEST['order_id']) {
			$condition = array_merge(array(
				'bsell.order_id' => array('like', '%' . $order_id . '%')
			), $condition);
		}
		$field = 'bsell.order_id, bsell.id, bsell.count, bsell.real_sell_price, bsell.extra_price, bsell.sell_time, bsell.create_time, ';
		$field .= 'bemployee.employee_name as creator_name, IFNULL(bshop.shop_name, "总部") as shop_name';
		$join = 'LEFT JOIN __B_EMPLOYEE__ bemployee ON bsell.creator_id = bemployee.user_id AND bemployee.company_id = ' . $company_id;
		$join .= ' LEFT JOIN __B_SHOP__ bshop ON bsell.shop_id = bshop.id';
		$join .= ' LEFT JOIN __B_SELL_DETAIL__ bselldetail ON bsell.id = bselldetail.sell_id AND bselldetail.deleted = 0 AND bselldetail.status = 1';
		$order = '';
		$group = '';

		$count = $this->model_bsell->alias('bsell')->countList($condition, $field, $join, $order, $group);
		$this->page($count, $this->pagenum);
        $limit = $page->firstRow . ',' . $page->listRows;
		$sell_list = $this->model_bsell->alias('bsell')->getList($condition, $field, $limit, $join, $order, $group);
		// echo $this->model_bsell->getlastsql();

        if (empty($sell_list)) {
            $this->assign('empty_info', '未查询到销售清单');
        } else {
            $this->assign('sell_list', $sell_list);
        }
		$this->display();
	}

	/**
	 * ajax方法 获取销售id对应货品清单
	 */
	public function sell_product()
	{
		$return_id = I('post.return_id/d', 0);
		$detail_ids = I('post.detail_ids/s', '');
		$detail_ids = explode(',', $detail_ids);
		if (!empty($return_id)) {
			// 获取当前单据中的销售详情id
			$old_detail_ids = $this->_detailIds($return_id);
		}
		$sell_ids = I('post.sell_ids/s', '');
		$sell_ids = empty($sell_ids) ? array() : explode(',', $sell_ids);
		$detail_list = $this->model_bselldetail->getListDetailByPost(null, $sell_ids, $old_detail_ids);

		foreach ($detail_list as $key => $value) {
			if (in_array($value['id'], $detail_ids)) {
				unset($detail_list[$key]);
			}
		}
		
		$product_list = array();
		foreach ($detail_list as $key => $value) {
			$product_list[] = array(
				'detail_id' => $value['id'],
				'product_id' => $value['product_id'],
				'product_pic' => $value['product_pic'],
				'goods_name' => $value['goods_name'],
				'goods_code' => $value['goods_code'],
				'product_code' => $value['product_code'],
				'sub_product_code' => $value['sub_product_code'],
				'p_total_weight' => $value['p_total_weight'],
				'purity' => ($value['sell_pricemode'] == 1) ? $value['purity'] . '‰' : '--',
				'p_gold_weight' => $value['p_gold_weight'],
				'sell_feemode' => ($value['sell_feemode'] == 1) ? '克工费销售' : '件工费销售',
				'detail_sell_fee' => number_format($value['detail_sell_fee'], 2, '.', ','),
				'gold_price' => number_format($value['gold_price'], 2, '.', ','),
				'sell_price' => number_format($value['sell_price'], 2, '.', ','),
				'discount_price' => number_format($value['discount_price'], 2, '.', ','),
				'actual_price' => number_format($value['actual_price'], 2, '.', ','),
				'sell_pricemode' => ($value['sell_pricemode'] == 1) ? '计重' : '计件',
			);
		}
        $data['product_list'] = $product_list;
        output_data($data);
	}

	/**
	 * ajax方法 获取货品清单
	 */
	public function product()
	{
		$return_id = I('request.return_id/d', 0);
		if (!empty(I('request.client_id/d', 0))) {
			if (!empty($return_id)) {
				// 获取当前单据中的销售详情id
				$detail_ids = $this->_detailIds($return_id);
			}
			$detail_count = $this->model_bselldetail->getListDetailByPost(false, 0, $detail_ids);
			$this->page($detail_count, $this->pagenum);
	        $limit = $page->firstRow . ',' . $page->listRows;
			$detail_list = $this->model_bselldetail->getListDetailByPost($limit, 0, $detail_ids);
		}

        if (empty($detail_list)) {
            $this->assign('empty_info', '未查询到销售货品');
        } else {
			$this->assign('detail_list', $detail_list);
        }
		$this->display();
	}

	/**
	 * 助手函数 查询当前退货单中包含的销售详情id
	 * @return array $detail_ids
	 */
	public function _detailIds($return_id) 
	{
		if (empty($return_id)) return array();
		$condition = array(
			'deleted' => 0,
			'sr_id' => $return_id
		);
		$redetail_list = $this->model_bsellredetail->getList($condition, 'sd_id');
		$detail_ids = array();
		foreach ($redetail_list as $key => $value) {
			$detail_ids[] = $value['sd_id'];
		}
		return $detail_ids;
	}

	/**
	 * 助手函数 输出商户门店列表
	 */
	public function _shopList()
	{
		$join = 'left join __B_CURRENCY__ bcurrency on bshop.currency_id = bcurrency.id';
        $condition = array(
        	'bshop.company_id' => $this->MUser['company_id'],
        	'bshop.deleted' => 0,
        	'bshop.enable' => 1
        );
        $get_shop_id = get_shop_id();
        if($get_shop_id > 0){
            $condition['bshop.id'] = $get_shop_id;
            $this->assign('shop_id', $get_shop_id);
        }
        $field = 'bshop.*, bcurrency.id currency_id, bcurrency.exchange_rate, bcurrency.unit';
        $shop = $this->model_bshop->alias('bshop')->getList($condition, $field, $limit = NULL, $join);

        $this->assign('shop', $shop);
	}
}