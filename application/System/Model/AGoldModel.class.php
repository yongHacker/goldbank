<?php
namespace System\Model;
use System\Model\ACommonModel;
class AGoldModel extends ACommonModel{
    public function __construct() {
        parent::__construct();
    }
    /**
     * 获取最新金价
     * @param array $condition
     * @return array
     */
    public function getNewGold($condition=array('cat_id'=>7)){
        $gold=$this->alias('ag')->field('ag.*,u.user_nicename')->join('left join '.C('DB_PREFIX').'m_users as u on ag.user_id=u.id')->where($condition)->order('ag.id desc')->find();
        return $gold;
    }
}
