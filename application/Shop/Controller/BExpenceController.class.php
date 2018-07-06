<?php
/**
 * 其它费用类目设置
 * @author: alam
 * @time: 9:17 2018/5/22
 */
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BExpenceController extends ShopbaseController
{

    public $model_expence, $model_expence_sub;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize() 
    {
        parent::_initialize();
        $this->model_expence = D('BExpence');
        $this->model_expence_sub = D('BExpenceSub');
        $this->b_show_status('b_expence');
    }

    /**
     * 助手函数 输出其它费用类目 用于单据中
     * @param  $type 类目类型 1-销售 2-采购 3-采购退货 4-销售退货 
     */
    public function _expenceList($type = 1)
    {
        $condition = array(
            'deleted' => 0,
            'company_id' => get_company_id(),
            'type' => $type
        );
        $expence_list = $this->model_expence->getList($condition);
        $this->assign('expence_list', $expence_list);
    }
}