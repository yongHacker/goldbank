<?php
namespace Common\Model;

use Api\Model\ApiCommonModel;

class MAreaModel extends ApiCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 根据地区子Code获取地区Code链
     * @param number $param 条件参数
     * @param number $type 条件类型 1-area_id 2-area_code
     * @param array $area_list
     * @return
     */
    public function getAreaIdByChild($param, $type = 1, &$area_list)
    {
        if (empty($param)) return false;
        if ($type == 1) {
            $condition = array(
                'area_id' => $param
            );
        } else {
            $condition = array(
                'area_code' => $param
            );
        }
        $area_info = $this->getInfo($condition);
        if (!empty($area_info)) {
            $area_list[] = $area_info['area_code'];
            if (!empty($area_info['area_parent_code'])) {
                $this->getAreaIdByChild($area_info['area_parent_code'], 2, $area_list);
            }
            return true;
        }
    }
}