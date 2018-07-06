<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class BWarehouseModel extends ApiCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }
    
    // 获取门店仓库详细信息
    public function getInfoDetail($condition = array())
    {
        $condition['bwarehouse.deleted'] = 0;
        
        $join = 'left join ' . DB_PRE . 'm_users muser on muser.id = bwarehouse.wh_uid';
        $field = 'bwarehouse.*,muser.user_nicename';
        $info = $this->alias('bwarehouse')->getInfo($condition, $field, $join);
        return $info;
    }
    
}