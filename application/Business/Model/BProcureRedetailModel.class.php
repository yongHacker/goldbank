<?php
/**
 * @author lzy 2018.6.1 13:00
 * 采购退货详情model
 */
namespace Business\Model;

use Business\Model\BCommonModel;

class BProcureRedetailModel extends BCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获得采购单中的货品
     * @param string $ids
     * @param int $wh_id
     * @return 
     */
    public function get_procure_product($ids, $wh_id, $type = 3) {
    	$model_product = D("BProduct");
        $join2 = D("BProduct")->get_product_join_str();
        $field2 = D("BProduct")->get_product_field_str($type);
        $product_list=M('b_product as bproduct')->field('bproduct.*, p.pricemode procurement_pricemode'.$field2)
                ->join($join2)
                ->join('left join '.C('DB_PREFIX').'b_procure_storage as ps on ps.id = bproduct.storage_id left join '.C('DB_PREFIX').'b_procurement as p on ps.procurement_id=p.id')
                ->where(array('p.status'=>1,'bproduct.deleted'=>0,'bproduct.status'=>2,'warehouse_id'=>$wh_id,'p.id'=>array('in',$ids)))->select();
        return $product_list;
    }

    // 获取表单列表详情
    public function getProductList($return_id)
    {
    	$model_product = D("BProduct");
    	// 货品ID序列
    	$detail_list = $this->getList(array('pr_id' => $return_id, 'deleted' => 0));
    	$product_ids = array();
    	foreach ($detail_list as $key => $value) {
    		$product_ids[] = $value['p_id'];
    	}
    	$product_list = array();
    	if (!empty($product_ids)) {
			$join_1 = $model_product->get_product_join_str();
			$join_2 = 'LEFT JOIN __B_PROCURE_STORAGE__ AS ps ON ps.id = bproduct.storage_id';
			$join_2 .= ' LEFT JOIN __B_PROCUREMENT__ as p ON ps.procurement_id = p.id';
			$join_2 .= ' LEFT JOIN __B_PROCURE_REDETAIL__ as prd ON bproduct.id = prd.p_id and prd.deleted = 0 AND pr_id = ' . $return_id;

			$field_1 = $model_product->get_product_field_str();
			$field_1 = rtrim(ltrim($field_1, ','), ',');
			$field_2 = 'bproduct.*, p.pricemode procurement_pricemode';

			$where = array(
				'p.status' => 1,
				'bproduct.deleted' => 0,
				// 'bproduct.status' => 9,
				'bproduct.id' => array('in', $product_ids)
			);
			$product_list = M('b_product as bproduct')
				->field($field_1 . ',' . $field_2)
				->join($join_1)->join($join_2)
				->where($where)
				->order('prd.id asc')
				->select();
    	}

		return $product_list;
    }
}