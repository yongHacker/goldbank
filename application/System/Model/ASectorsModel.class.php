<?php
namespace System\Model;
use System\Model\ACommonModel;
class ASectorsModel extends ACommonModel{
    public function __construct() {
        parent::__construct();
        $this->curmodel='a_appversion';
    }
    /**获取部门下拉树形分类数据
     * @param int $sid 默认选择的id
     * @return string
     */
    public function get_bsectors_tree($sid = 0){
        $tree = new \Tree();
        $parentid = I("get.parentid",0,'intval');
        $condition=array("deleted"=>0);
        $result = $this->getList($condition,$field='*,sector_pid as parentid,sector_name as name',$limit=null,$join='',$order='id asc',$group='');
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $select_categorys = $tree->get_tree(0, $str,$sid );
        return $select_categorys;
    }
}
