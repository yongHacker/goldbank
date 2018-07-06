<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BBankGoldModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }
    //获取贵金属金价
    function get_bankgold_price($v){
        $price=D("BMetalType")->get_metaltype_price(array("b_metal_type_id"=>$v['bmt_id']));
        $expression = str_replace("price", (float)$price, $v["formula"]);
        if(!empty($expression)){
            eval("\$price=" . $expression . ";");
            $price=$price;
        }else{
            $price=0;
        }
        return $price;
    }
    //获取金价类型价格
    function get_price_by_bgt_id($bgt_id){
        $condition=array("company_id"=>get_company_id(),"shop_id"=>get_shop_id(),"bgt_id"=>$bgt_id,"deleted"=>0);
        $bankgold=$this->getInfo($condition);
        $condition["status"]=1;
        $bankgoldtype=D("BBankGoldType")->getInfo($condition);
        $price=D("BMetalType")->get_metaltype_price(array("b_metal_type_id"=>$bankgoldtype['b_metal_type_id']));
        $expression = str_replace("price", (float)$price, $bankgold["formula"]);
        if(!empty($expression)){
            eval("\$price=" . $expression . ";");
            $price=$price;
        }else{
            $price=0;
        }
        return $price;
    }

    /**
     * 获取设置的金价
     * @param string $type
     * @return mixed
     */
    function get_setting_price($type='all'){
        $info=D('BOptions')->get_recover_setting();
        $price=array();
        if($info){
            $price=json_decode($info["option_value"],true);
            if($type='all'||$type='cut_gold_price'){
                $price["cut_gold_price"]=D("BBankGold")->get_price_by_bgt_id($price['cut_bgt_id']);//截金金价
            }
            if($type='all'||$type='recovery_price') {
                $price["recovery_price"] = D("BBankGold")->get_price_by_bgt_id($price['recovery_bgt_id']);//回购金价
            }
            if($type='all'||$type='gold_price') {
                $price["gold_price"] = D("BBankGold")->get_price_by_bgt_id($price['bgt_id']);//当前金价
            }
        }
        return $price;
    }
}
