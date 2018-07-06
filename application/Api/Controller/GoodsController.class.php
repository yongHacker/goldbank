<?php
/**
 * 商品货品
 * 
 * @author alam
 * @date 2018/02/22 10:00
 */
namespace Api\Controller;
defined('JHJAPI') or exit('Access Invalid!');
use Api\Controller\BaseController;
class GoodsController extends BaseController
{
    protected $model_b_goods_class, $model_b_goods_common, $model_b_goods_pice, $model_b_warehouse;
    
    public function __construct()
    {
        parent::__construct();

        $this->api_init();
    }
    
    public function _initialize()
    {
        parent::_initialize();

        $this->model_b_goods_class = D('BGoodsClass');
        $this->model_b_goods_common = D('BGoodsCommon');
        $this->model_b_goods_pic = D('BGoodsPic');
        $this->model_b_warehouse = D('BWarehouse');
    }
    
    /**
     * 商品分类
     */
    public function goods_class()
    {
        $where = array(
            'company_id' => $this->role_path['company_id']
        );
        $b_class = $this->model_b_goods_class->getGoodsClass(0, $where);
        array_unshift($b_class, array(
            'id' => '0',
            'agc_id' => '0',
            'parentid' => '0',
            'name' => '全部分类',
            'photo' => '',
        ));
        $this->encrypt_exit(0, '', $b_class);
    }
    
    /**
     * 获取当前仓库详细信息
     */
    public function _get_wh_info_detail()
    {
        $condition = array(
            'bwarehouse.company_id' => $this->role_path['company_id'],
            'bwarehouse.shop_id' => $this->role_path['shop_id']
        );
        $warehouse = $this->model_b_warehouse->getInfoDetail($condition);
        return $warehouse ? $warehouse : array();
    }
    
    /**
     * 获取指定商品分类下商品
     */
    public function get_goods_from_class()
    {
        $class_id = I('post.class_id');
        $class_list = $this->model_b_goods_class->getAllGoodsClass($class_id, $this->role_path['company_id'], array());

        $goods_list = array();
        $class_id_list = ($class_id == 0) ? '0,' . $class_id : '0';
        foreach ($class_list as $key => $val) {
            $class_id_list .= ',' . $val['id'];
        }
        
        $warehouse = $this->_get_wh_info_detail();
        $condition = array(
            'gcl.id' => array('in', $class_id_list),
            'gco.deleted' => 0,
            /* 'gco.is_show_type' => array('in', '0,2') */
        );
        
        $count = $this->model_b_goods_common->getGoodsByClass($count = true, $condition);
        $res = $this->getPage($count);
        $goods_list = $this->model_b_goods_common->getGoodsByClass($count = false, $condition, $warehouse['id'], $res['limit']);
        
        $this->encrypt_exit(0, '', array_merge(array('goods' => $goods_list), $res));
    }
    
    /**
     * 获取商品详情
     */
    public function get_goods_detail()
    {
        $goods_common_id = I('post.goods_common_id');
        $this->_param_check(array('goods_common_id' => $goods_common_id));

        $warehouse = $this->_get_wh_info_detail();
        $goods_common = $this->model_b_goods_common->getGoodsCommonDetail($goods_common_id, $warehouse['id']);
        
        if (empty($goods_common)) {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_FAIL'));
        } else {
            $this->encrypt_exit(0, '', $goods_common);
        }
    }
	
	// 获取商品公共的所有规格列表
	public function getGoodsSpec($goods_list){
	    if (empty($goods_list)) return '';
	    $goods_spec="";
	    $i=1;
	    $min=0;
	    $max=0;
	    $type=0;
	    foreach($goods_list as $key => $val){
	        if($val['gold_type']==C("GOLD_TYPE") && $val['sell_pricemode'] == 1 ){
	            if(!empty($goods_spec)){
	                $goods_spec.="/";
	            }
	            $goods_spec.=$val['goods_spec'];
	        }else{
	            $type=1;
	            $goods_spec=decimalsformat($val['sell_price'],2);
	            if($i==1){
	                $min=$goods_spec;
	                $max=$goods_spec;
	            }else{
	                if($min>$goods_spec){
	                    $min=$goods_spec;
	                }
	                if($max<$goods_spec){
	                    $max=$goods_spec;
	                }
	            }
	        }
	        $i++;
	    }
	    if($type==1){
	        if($min==$max){
	            $goods_spec=$min."元";
	        }else{
	            $goods_spec=$min."元"."~".$max."元";
	        }
	    }
	    return $goods_spec;
	}
}