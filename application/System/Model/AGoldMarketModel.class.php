<?php
namespace System\Model;
use System\Model\ACommonModel;
class AGoldMarketModel extends ACommonModel{
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取最新信息列表
     * @return array
     */
    public function getnewlist()
    {
        $insert_time = $this->newsTime();
        $where=" agm.insert_time = ".$insert_time['0']." and agm.api_type=0 and c.status =1";
        $list1 = M('a_gold_market as agm')->join('left join ' . C('DB_PREFIX') . 'a_gold_category c on c.id = agm.cat_id')->where($where)->order('agm.insert_time desc')->group("agm.cat_id")->select();
        $where=" agm.insert_time = ".$insert_time['1']." and api_type = 1 and c.status =1";
        $list2 = M('a_gold_market as agm')->join('left join ' . C('DB_PREFIX') . 'a_gold_category c on c.id = agm.cat_id')->where($where)->order('agm.insert_time desc')->group("agm.cat_id")->select();
        foreach ($list1 as $k=>$v){
            foreach ($list2 as $key=>$val){
                if($v['cat_id']==$val['cat_id']){
                    if($v['insert_time']>$val['insert_time']){
                        unset($list2[$key]);
                    }else{
                        unset($list1[$k]);
                    }
                }
            }
        }
        $list=array_merge($list1,$list2);
        return $list;
    }

    /**
     * 查询最新插入的记录
     * @return string
     */
    public function newsTime()
    {
        $record = $this->order('insert_time desc')->where("api_type=0")->find();
        $record1=$this->order("insert_time desc")->where("api_type=1")->find();
        $data["0"]=$record['insert_time'];
        $data["1"]=$record1['insert_time'];
        return $data;
    }
}
