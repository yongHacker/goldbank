<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BBankGoldModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }
    //通过贵金属类型和金价设置公式获取金价
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
    //通过贵金属类型和金价设置公式获取金价
    function get_price_by_bg_id($bg_id){
        $condition=array("bbankgoldtype.company_id"=>get_company_id(),"bbankgoldtype.deleted"=>0,"bbankgoldtype.status"=>"1","bbankgold.bg_id"=>$bg_id);
        $join="left join ".DB_PRE."b_bank_gold p_bank_gold on bbankgold.parent_id=p_bank_gold.bg_id";
        $join.=" left join ".DB_PRE."b_bank_gold_type bbankgoldtype on bbankgoldtype.bgt_id=bbankgold.bgt_id";
        $join.="  left join ".DB_PRE."b_metal_type metaltype on metaltype.id=bbankgoldtype.b_metal_type_id";
        $field='p_bank_gold.*,bbankgold.bg_id id,bbankgold.formula b_formula,bbankgoldtype.name,bbankgoldtype.bgt_id,metaltype.id bmt_id';
        $data=$this->alias("bbankgold")->getInfo($condition,$field,$join,$order="");
        $price=$this->get_bankgold_price($data);
        $expression = str_replace("price", (float)$price, $data["b_formula"]);
        if(!empty($expression)){
            eval("\$price=" . $expression . ";");
            $price=$price;
        }else{
            $price=0;
        }
        return $price;
    }
}
