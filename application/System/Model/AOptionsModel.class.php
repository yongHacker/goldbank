<?php
namespace System\Model;
use System\Model\ACommonModel;
class AOptionsModel extends ACommonModel{
    public function __construct() {
        parent::__construct();
    }
    //获得金价接口开关
    public function getGoldSwitch(){
        $condition=array(
            'option_name'=>'gold_price_switch'
        );
        $open_info=$this->getInfo($condition);
        if(empty($open_info)){
            $insert=array(
                'option_name'=>'gold_price_switch',
                'option_value'=>'0'
            );
            $this->insert($insert);
            $open_info=$this->getInfo($condition);
        }
        return $open_info;
    }
    public function getMobileLimit(){
        $condition=array(
            'option_name'=>'mobile_limit'
        );
        $open_info=$this->getInfo($condition);
        if(empty($open_info)){
            $insert=array(
                'option_name'=>'mobile_limit',
                'option_value'=>'0'
            );
            $this->insert($insert);
            $open_info=$this->getInfo($condition);
        }
        return $open_info;
    }
    public function getMobileOpen(){
        $condition=array(
            'option_name'=>'mobile_open'
        );
        $open_info=$this->getInfo($condition);
        if(empty($open_info)){
            $insert=array(
                'option_name'=>'mobile_open',
                'option_value'=>'0'
            );
            $this->insert($insert);
            $open_info=$this->getInfo($condition);
        }
        return $open_info;
    }
    //获得金价接口类型
    public function getApiType(){
        $condition=array(
            'option_name'=>'gold_type_switch'
        );
        $open_info=$this->getInfo($condition);
        if(empty($open_info)){
            $insert=array(
                'option_name'=>'gold_type_switch',
                'option_value'=>'0'
            );
            $this->insert($insert);
            $open_info=$this->getInfo($condition);
        }
        return $open_info;
    }
    //获得金价接口开关的数据
    public function getGoldRelationCatid(){
        $condition=array(
            'option_name'=>'gold_relation_catid'
        );
        $gold_relation_cate=$this->getInfo($condition);
        if(empty($gold_relation_cate)){
            $insert=array(
                'option_name'=>'gold_relation_catid',
                'option_value'=>'0'
            );
            $this->insert($insert);
            $gold_relation_cate=$this->getInfo($condition);
        }
        return $gold_relation_cate;
    }
    //获取开启的金价写入api类型
    public function getGoldTypeSwitch(){
        $condition=array(
            'option_name'=>'gold_type_switch'
        );
        $gold_relation_cate=$this->getInfo($condition);
        if(empty($gold_relation_cate)){
            $insert=array(
                'option_name'=>'gold_type_switch',
                'option_value'=>'0'
            );
            $this->insert($insert);
            $gold_relation_cate=$this->getInfo($condition);
        }
        return $gold_relation_cate['option_value'];
    }
    //获取每一种贵金属种类的最新详细信息
    public function getNewGoldList($page=null){
        $gold_relation_cate=$this->getGoldRelationCatid();
        $gold_relation=explode(',', $gold_relation_cate['option_value']);

        $model_cate=D('System/a_gold_category');
        $cate_list=$model_cate->getList(array('status'=>1),"*",$page->firstRow.','.$page->listRows);
        foreach($cate_list as $key => $val){
            $condition=array(
                'option_name'=>'gold_cate'.$val['id']
            );
            $cate_info=$this->getInfo($condition);
            if(empty($cate_info)){
                $insert=array(
                    'option_name'=>'gold_cate'.$val['id'],
                    'option_value'=>'price'
                );
                $this->insert($insert);
                $cate_info=$this->getInfo($condition);
            }
            $cate_list[$key]['expression']=$cate_info['option_value'];

            $gold_info=D('System/a_gold')->getNewGold(array('cat_id'=>$val['id']));
            $cate_list[$key]['price']=$gold_info['price'];
            $cate_list[$key]['create_time']=$gold_info['create_time'];
            $cate_list[$key]['user']=$gold_info['user_nicename'];
            if(in_array($val['id'], $gold_relation)){
                $cate_list[$key]['statu']="open";
            }else{
                $cate_list[$key]['statu']="close";
            }
            if($gold_info['user_id']=='0'){
                $cate_list[$key]['user']="系统自动";
            }
            if(empty($gold_info['price'])){
                $cate_list[$key]['price']="----";
                $cate_list[$key]['user']="----";
            }
        }
        return $cate_list;
    }
    public function operateCate($id,$operate){
        $condition=array('option_name'=>'gold_relation_catid');
        $option_info=$this->getInfo($condition);
        $update=array();
        switch($operate){
            case "add":
                $update['option_value']=$option_info['option_value'].",".$id;
                break;
            case "del":
                $update['option_value']=str_replace(",".$id, "", $option_info['option_value']);
                break;
            default:
                break;
        }
        if(!empty($update)){
            $result=$this->update($condition, $update);
        }
        return $result;
    }
    //集金号
    public function getJJHGoldSetting(){
        $condition=array('option_name'=>'jjh_gold');
        $option_info=$this->getInfo($condition);
        if(empty($option_info)){
            $insert=array(
                'option_name'=>'jjh_gold',
                'option_value'=>'{"xauusd_price":"ceil(price*rate/31.1035*10)/10","au9999_price":"price","is_open":"xauusd_price"}'
            );
            $this->insert($insert);
            $option_info=$this->getInfo($condition);
        }
        $option_info['config']=array("xauusd_price"=>"o_data/JO_92233","au9999_price"=>"o_data/JO_71",
            "london_gold_price"=>"o_data/JO_111","london_gold_price"=>"o_data/JO_111",
            "usdcny_price"=>"o_data/JO_56382","xauusd_cny_price"=>"g_data/JO_92233");
        return $option_info;
    }
    public function updateJJHGoldSetting($expression,$type){
        $info=$this->getJJHGoldSetting();
        $option_value=json_decode($info["option_value"],true);
        $option_value[$type]=$expression;
        $option_value["is_open"]=$type;
        $expression=json_encode($option_value);
        $condition=array('option_name'=>'jjh_gold');
        $update=array('option_value'=>$expression);
        $result=$this->update($condition,$update);
        return $result;
    }
}
