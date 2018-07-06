<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BGoodsClassModel extends BCommonModel{
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('class_name', 'require', '商品分类名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('class_name', 'require', '商品分类名称不能为空！', 1, 'regex', BCommonModel:: MODEL_UPDATE ),
         array('agc_id', 'require', '关联分类不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('agc_id', 'require', '关联分类不能为空！', 1, 'regex', BCommonModel:: MODEL_UPDATE )
    );
    protected function _after_insert($data,$options){
        parent::_after_insert($data,$options);
        if($data["pid"]>0){
            $class=M("BGoodsClass")->where(array("id"=>$data["pid"]))->Field("level,type")->find();
//             $update_data["type"]=$class["type"];
            $update_data["level"]=$class["level"]+1;
        }else{
            $update_data["level"]=0;
//             $update_data["type"]=$data["type"];
        }
        $this->where("id=".$data['id'])->save($update_data);
    }

    protected function _after_update($data,$options){
        parent::_after_update($data,$options);
        if(isset($data['pid'])){
            if($data["pid"]==0){
                $update_data["level"]=0;
//                 $update_data["type"]=$data["type"];
            }else{
                $class=M("BGoodsClass")->where(array("id"=>$data["pid"]))->Field("level,type")->find();
//                 $update_data["type"]=$class["type"];
                $update_data["level"]=$class["level"]+1;
            }
            $result=$this->where("id=".$data['id'])->save($update_data);
            if($result){
                $children=$this->where(array("pid"=>$data['id']))->select();
                foreach ($children as $child){
                    $this->where(array("id"=>$child['id']))->save(array("pid"=>$data['id'],"id"=>$child['id']));
                }
            }
        }

    }
    //获取A端分类
    /**
     * @param int $sid 默认选择的id
     * @return string
     */
    public function get_a_goodsclass($sid = 0){
        return D("AGoodsClass")->get_a_goodsclass($sid);
    }
    //获取B端分类
    /**
     * @param int $sid 默认选择的id
     * @return string
     */
    public function get_b_goodsclass($sid = 0,$where){
        $tree = new \Tree();
        $parentid = I("get.parentid",0,'intval');
        $condition=array("company_id"=>get_company_id(),"deleted"=>0);
        if(!empty($where)){
            $condition=array_merge($where,$condition);
        }
        $result = $this->getList($condition,$field='*,pid as parentid,class_name as name,type',$limit=null,$join='',$order='id asc',$group='');
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option type='\$type' value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        //获取B分类
        $select_categorys = $tree->get_tree(0, $str,$sid );
        return $select_categorys;
    }
    // 获得指定id的分类的所有底层的分类集合
    public function getALLGoodsClass($class_id, $class_list = array())
    {
        global $class_list;
        if (empty($class_list)) {
            $class_list = array();
        }
        if (empty($class_id)) {
            $class_id = 0;
        }
        $condition = array('deleted' => 0, "pid" => $class_id,'company_id' => get_company_id());
        $goods_class = $this->getList($condition);
        if (! empty($goods_class)) {
            foreach ($goods_class as $key => $val) {
                $condition = array('deleted' => 0, "pid" => $val['id'], 'company_id' => get_company_id());
                $list = $goods_class = $this->getList($condition);
                $class_list[] = $val;
                if (!empty($list)) {
                    $list = $this->getALLGoodsClass($val['id'], $class_list);
                }
            }
            return $class_list;
        }
        return array();
    }

    // 获得指定id的分类的所有上层的分类集合
    public function getAllGoodsClassUp($class_id = 0)
    {
        $condition = array('deleted' => 0, 'id' => $class_id, 'company_id' => get_company_id());
        $goods_class = $this->getInfo($condition);
        if (! empty($goods_class) && ! empty($goods_class['pid'])) {
            $class = $this->getAllGoodsClassUp($goods_class['pid']);
            $class['child'] = $goods_class;
        } else {
            $class = $goods_class;
        }
        return $class;
    }
    // 获取指定id的分类的所有上层的分类名称
    public function classNav($class_id = 0, $class_tree, $nav)
    {
        global $nav;
        if (empty($nav)) {
            $nav = '';
        }
        if (empty($class_tree)) {
            $class_tree = $this->getAllGoodsClassUp($class_id);
        }
        if (! empty($class_tree['child'])) {
            $child_nav = $this->classNav($class_id, $class_tree['child'], $nav);
        }
        $nav = $class_tree['class_name'] . (!empty($child_nav) ? '/' . $child_nav : '');
        return $nav;
    }

    //商品公共图片处理
    function common_goods_img($data){
        $m = M('b_goods_pic');
        if($data['type'] == 'del'){
            $m->id = $data['id'];
            $m->deleted = 1;
            $res = $m->save();
        }
        if($data['type'] == 'link'){
            $m->type = 1;
            $m->goods_id = $data['id'];
            $m->pic = $data['goods_img'];
            $res = $m->add();
        }
        if($data['type'] == 'default'){
            $goods_id = $m->where("id='".$data['id']."'")->getField('goods_id');
            $m->where("goods_id=".$goods_id)->save(array('is_hot'=>0));
            $m->id = $data['id'];
            $m->is_hot = 1;
            $res = $m->save();
        }
        return $res;
    }
}