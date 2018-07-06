<?php
namespace Api\Model;
use Api\Model\ApiCommonModel;
class AGoodsClassModel extends ApiCommonModel{
    //获取A端分类
    /**
     * @param int $sid 默认选择的id
     * @return string
     */
    public function get_a_goodsclass($sid = 0){
        $tree = new \Tree();
        $parentid = I("get.aparentid",0,'intval');
        $result = D("AGoodsClass")->getList($condition="deleted=0",$field='*,pid as parentid,class_name as name',$limit=null,$join='',$order='id asc',$group='');
        echo D("AGoodsClass")->getLastSql();die;
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option type='\$type' value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $Aselect_categorys = $tree->get_tree(0, $str,$sid);
        return $Aselect_categorys;
    }
}
