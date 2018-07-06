<?php
/**
 * 销售退货
 * @author  alam
 * @time 17:10 2018/6/15
 */
namespace Business\Model;
use Business\Model\BCommonModel;
class BSellRedetailModel extends BCommonModel {

	public $model_bproduct;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        parent::_initialize();
        $this->model_bproduct = D('BProduct');
    }

    // 获取退货表单列表
    public function getProductList($return_id = 0, $condition = array())
    {
		$return_id = empty($return_id) ? I('return_id/d', 0) : $return_id;
    	$condition['bsellredetail.sr_id'] = $return_id;
    	$condition['bsellredetail.deleted'] = 0;

    	$field = 'bsellredetail.id as redetail_id, bsellredetail.return_price, bselldetail.*, bselldetail.sell_fee detail_sell_fee, bproduct.type, bproduct.product_code, bproduct.sub_product_code, bproduct.goods_id';
        $field .= $this->model_bproduct->get_product_field_str(3);
        $join = ' LEFT JOIN __B_SELL_DETAIL__ bselldetail ON bsellredetail.sd_id = bselldetail.id';
        $join .= ' LEFT JOIN __B_PRODUCT__ bproduct ON bproduct.id = bselldetail.product_id';
        $join .= $this->model_bproduct->get_product_join_str();
        $order = 'bsellredetail.id ASC';

        $list = $this->alias('bsellredetail')->getList($condition, $field, $limit = NULL, $join, $order);
        foreach($list as $k => $v){
            if (!empty($include_ids)) {
                if ($v['status'] == '2' && !in_array($v['id'], $include_ids)) {
                    unset($list[$k]);
                    break;
                }
            }
            $list[$k]['product_detail'] = strip_tags($this->model_bproduct->get_product_detail_html($v));
            $list[$k]['product_pic'] = $this->model_bproduct->get_goods_pic($v['photo_switch'],$v['goods_id'],$v['product_id']);
            $common_data = $this->model_bproduct->get_product_common_data($v);
            $list[$k]['p_gold_weight'] = $common_data['p_gold_weight'] === '' ? '--' : $common_data['p_gold_weight'];
            $list[$k]['p_total_weight'] = $common_data['p_total_weight'] === '' ? '--' : $common_data['p_total_weight'];
        }
        return $list;
    }
}
