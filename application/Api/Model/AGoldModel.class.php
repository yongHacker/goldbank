<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class AGoldModel extends ApiCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取最新金价
     * 
     * @param array $condition            
     * @return array
     */
    public function getGoldPrice($condition = array('cat_id'=>7))
    {
        $gold = $this->field('price')->where($condition)->order('id DESC')->find();
        return $gold['price'];
    }
}
