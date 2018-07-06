<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class BShopModel extends ApiCommonModel{
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取门店信息
     * @param number $shop_id
     * @return array $shop_info
     */
    public function shopInfo($shop_id = 0)
    {
        if (empty($shop_id)) {
            return array();
        }
        $condition = array(
            'id' => $shop_id,
            'deleted' => 0
        );
        $shop_info = $this->getInfo($condition);
        return empty($shop_info) ? array() : $shop_info;
    }
}
