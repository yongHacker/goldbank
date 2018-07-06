<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BSectorsModel extends BCommonModel{
    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('sector_name', 'require', '部门名称不能为空！', 1, 'regex', BCommonModel:: MODEL_BOTH ),
    );
    //自动完成
    protected $_auto = array(
        //array(填充字段,填充内容,填充条件,附加规则)
    );

    /**获取B端部门下拉树形分类数据
     * @param int $sid 默认选择的id
     * @return string
     */
    public function get_bsectors_tree($sid = 0){
        $tree = new \Tree();
        $parentid = I("get.parentid",0,'intval');
        $condition=array("company_id"=>get_company_id(),"deleted"=>0);
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
