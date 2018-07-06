<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BWgoodsStockModel extends BCommonModel{
    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
       // array('sector_id', 'require', '部门不能为空！', 1, 'regex', BCommonModel:: MODEL_BOTH ),
    );
    //自动完成
    protected $_auto = array(
        //array(填充字段,填充内容,填充条件,附加规则)
    );

    /**
     * @param $where 查询条件
     * @param $stock  需要判断的库存大小
     * @param $sell_pricemode 计价方式
     * @return bool
     */
    function is_enough_stock($where,$stock,$sell_pricemode){
        $data=$this->alias("bwgoodsstock")->lock(true)->getinfo($where);
        if(empty($data)){
            return false;
        }
        $update_stock=$data["goods_stock"]-$stock;
        $result=$update_stock<0?false:true;
        return $result;
    }

}
