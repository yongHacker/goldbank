<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BShopModel extends BCommonModel{
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('shop_name', 'require', '门店名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('code', 'require', '编号不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT ),
        array('code','','编号已经存在！',0,'unique',BCommonModel:: MODEL_BOTH ), // 验证code字段是否唯一
        array('mobile', 'require', '手机号不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT ),
        array('shop_name', 'require', '门店名称不能为空！', 0, 'regex', BCommonModel:: MODEL_UPDATE  ),
        array('code', 'require', '编号不能为空！', 0, 'regex', BCommonModel:: MODEL_UPDATE ),
        array('mobile', 'require', '手机号不能为空！', 0, 'regex', BCommonModel:: MODEL_UPDATE ),
        array('user',"require","店长不能为空", 0, 'regex', BCommonModel:: MODEL_INSERT ),
         array('user',"require","店长不能为空", 0, 'regex', BCommonModel:: MODEL_UPDATE ),
        array('currency_id','check_gt_0',"默认币种不能为空", 0, 'callback', BCommonModel:: MODEL_INSERT ),
        array('currency_id','check_gt_0',"默认币种不能为空", 0, 'callback', BCommonModel:: MODEL_UPDATE ),
    );
    public function __construct() {
        parent::__construct();
    }
    public function check_gt_0(){
        $currency_id=$_REQUEST['currency_id'];
        if($currency_id>0){
            return true;
        }
        return false;
    }
    /**
     * @author lzy 2018.5.10
     * 获取下拉菜单的门店列表
     * @param array $condition
     * @return multitype:unknown
     */
    public function getShopListForSelect($condition){
        $list=$this->getList($condition);
        $shop_list=array();
        foreach($list as $key => $val){
            $shop_list[$val['id']]=$val;
        }
        return $shop_list;
    }
}
