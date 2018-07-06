<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BShopModel extends BCommonModel{
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('shop_name', 'require', '门店名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('code', 'require', '编号不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT ),
        /*array('code','','编号已经存在！',0,'unique',BCommonModel:: MODEL_BOTH ), // 验证code字段是否唯一*/
        array('code','check_code',"编号已经存在", 0, 'callback', BCommonModel:: MODEL_BOTH ),
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
    public function check_code(){
        $shop_id=$_REQUEST['id'];
        $condition=array("company_id"=>get_company_id(),'deleted'=>0,'code'=>$_REQUEST['code']);
        if($shop_id>0){
            $condition[id]=array('neq',$shop_id);
        }
        $shop=$this->getInfo($condition);
        if(empty($shop)){
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

    /**获取当前商户下的未删除门店
     * @param $where
     * @return array
     */
    public function getShopList($where){
        $shop_condition=array("company_id"=>get_company_id(),'deleted'=>0);
        if(!empty($where)){
            $shop_condition=array_merge($shop_condition,$where);
        }
        $shop_list=$this->getList($shop_condition);
        return $shop_list;
    }

}
