<?php
namespace System\Model;
use System\Model\ACommonModel;
class AGoodsClassModel extends ACommonModel{
    public function __construct() {
        parent::__construct();
        $this->curmodel='a_appversion';
    }

    // 废弃
    // 添加节点，所需的父类树形展示
    function gethtmltreedata()
    {
        $bm_id = I('post.bm_id') ? I('post.bm_id') : 0;
        $result = $this->where(array('deleted' => 0, 'id' => array('neq', $bm_id)))->Field('id,class_name,pid,type,photo')->select();
        $tree = $this->sortOut($result, 0, 0, '&nbsp;&nbsp;&nbsp;&nbsp;', I('post.bm_id'), I('post.optype'));
        return $tree;

    }
    
    // 废弃
    // 商品分类树形结构
    public function sortOut($cate, $pid = 0, $level = 0, $html = '--', $bm_id, $optype)
    {
        $tree = array();
        foreach ($cate as $v) {
            if ($optype == 'edit') {
                if ($v['pid'] == $bm_id) {
                    continue;
                } else if ($v['pid'] == $pid) {
                    $v['level'] = $level + 1;
                    $v['html'] = str_repeat($html, $level);
                    $tree[] = $v;
                    $tree = array_merge($tree, $this->sortOut($cate, $v['id'], $level + 1, $html, $bm_id, $optype));
                }
            } else {
                if ($v['pid'] == $pid) {
                    $v['level'] = $level + 1;
                    $v['html'] = str_repeat($html, $level);
                    $tree[] = $v;
                    $tree = array_merge($tree, $this->sortOut($cate, $v['id'], $level + 1, $html, $bm_id, $optype));
                }
            }
        }
        return $tree;
    }

    // 废弃
    // 遍历递归获取商品分类下的子分类
    public function gettrees($pid = 0)
    {
        $result = $this->where(array('pid' => $pid, 'deleted' => 0))->select();
        //遍历分类
        $arr = array();
        if ($result) {
            foreach ($result as $key => $value) {
                $temp['id'] = $value['id'];
                $temp['type'] = $value['type'];
                $temp['text'] = $value['class_name'];
                $temp['bm_pid'] = $value['pid'];
                $temp['photo']=$value['photo'];
                //递归获取子集分类
                $temp['nodes'] = $this->gettrees($temp['id']);

                $arr[] = $temp;
            }
            return $arr;
        }
    }

    /**
     * 获取A端分类
     * @param int $sid 默认选择的id
     * @return string
     */
    public function getGoodsClass($sid = 0){
        $tree = new \Tree();
        $parentid = I("get.aparentid", 0, 'intval');
        $result = $this->getList($condition = "deleted=0",$field = '*,pid as parentid,class_name as name', $limit = null, $join = '',$order = 'id asc', $group = '');
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option type='\$type' value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $a_select_categorys = $tree->get_tree(0, $str,$sid);
        return $a_select_categorys;
    }
}
