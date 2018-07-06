<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class AAppVersionModel extends ApiCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取版本信息
     * @param number $app_type APP类型 1-苹果 2-安卓
     * @param number $type 1-新版 0-旧版
     */
    public function version($app_type = 1, $version = 0, $order = 'update_time DESC') 
    {
        $condition = array(
            'status' => 1,
            'deleted' => 0,
            'app_type' => $app_type
        );
        if (!empty($version)) {
            $condition['app_version'] = $version;
        }
        $version = $this->where($condition)->order($order)->find();
        return empty($version) ? array() : $version;
    }
}
