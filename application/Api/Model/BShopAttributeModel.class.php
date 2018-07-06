<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class BShopAttributeModel extends ApiCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 获取门店属性信息
     * @param number $shop_id
     */
    public function shopDetail($shop_id = 0, $field = 'bsa.*')
    {
        if (empty($shop_id)) return array();
        
        $condition = array(
            'bs.deleted' => 0,
            'bs.id' => $shop_id
        );
        $join = 'LEFT JOIN __B_SHOP_ATTRIBUTE__ bsa ON bs.id = bsa.shop_id';
        $detail = D('BShop')->alias('bs')->getInfo($condition, $field, $join);
        return empty($detail) ? array() : $detail;
    }
}
