<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BMetalTypeModel extends BCommonModel{
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('name', 'require', '分类名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('name',"require","分类名称不能为空！", 0, 'regex', BCommonModel:: MODEL_UPDATE ),
    );
    public function __construct() {
        parent::__construct();
    }
    protected $taxonomys=array("article"=>"文章","picture"=>"图片");
    protected function _after_insert($data,$options){
        parent::_after_insert($data,$options);
        $term_id=$data['id'];
        $parent_id=$data['pid'];
        if($parent_id==0){
            $d['path']="0-$term_id";
            $d['level']=1;
        }else{
            $parent=$this->where("id=$parent_id")->find();
            $d['path']=$parent['path'].'-'.$term_id;
            $d['level']=$parent['level']+1;
        }
        $d['update_time']=time();
        $this->where("id=$term_id")->save($d);
    }
    protected function _after_update($data,$options){
        parent::_after_update($data,$options);
        if(isset($data['pid'])){
            $term_id=$data['id'];
            $parent_id=$data['pid'];
            if($parent_id==0){
                $d['path']="0-$term_id";
                $d['level']=1;
            }else{
                $parent=$this->where("id=$parent_id")->find();
                $d['path']=$parent['path'].'-'.$term_id;
                $d['level']=$parent['level']+1;
            }
            $d['update_time']=time();
            $result=$this->where("id=$term_id")->save($d);
            if($result){
                $children=$this->where(array("pid"=>$term_id))->select();
                foreach ($children as $child){
                    $this->where(array("id"=>$child['id']))->save(array("pid"=>$term_id,"id"=>$child['id']));
                }
            }
        }
    }
    //获取a端贵金属分类
    public function get_a_gold_category_tree(){
        //获取a端金属类型
        $condition=array('status'=>1,"is_show"=>1);
        $cate_list=D("AGoldCategory")->getList($condition,$field='id,IFNULL(memo,name) as name,status',$limit="",$join='');
        return $cate_list;
    }
    //获取贵金属表格树形数据
    public function get_metaltype_treetable(){
        //$result = M("BMetalType")->where(array("deleted"=>0))->order(array("listorder"=>"asc"))->select();
        $condition=array("deleted"=>0,"company_id"=>get_company_id());
        $result = $this->getList($condition,$field='*',$limit=null,$join='',$order=array("listorder"=>"asc"),$group='');
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        foreach ($result as $r) {
            $r['parentid_node'] = ($r['pid']) ? ' class="child-of-node-' . $r['pid'] . '"' : '';

            $r['style'] = empty($r['pid']) ? '' : 'display:none;';
            $r['str_manage'] = '<a href="' . U("BMetalType/add", array("parent" => $r['id'],"level"=>$r['level'])) . '">'.L('ADD_SUB_CATEGORY').'</a> | <a href="' . U("BMetalType/edit", array("id" => $r['id'])) . '">'.L('EDIT').'</a> | <a class="js-ajax-delete" href="' . U("BMetalType/delete", array("id" => $r['id'])) . '">'.L('DELETE').'</a> ';
            $r['taxonomys'] = $this->taxonomys[1];
            $r['id']=$r['id'];
            $r['parentid']=$r['pid'];
            $r['price']=$this->get_metaltype_price(array("b_metal_type_id"=>$r['id']));
            $r['relation']=$r['is_relation']==1?'是':'否';
            $array[] = $r;
        }
        $tree->init($array);
        $str = "<tr id='node-\$id' \$parentid_node style='\$style'>
					<td style='padding-left:20px;'><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input input-order'></td>
					<td class='text-center'>\$id</td>
					<td>\$spacer \$name</td>
	    			<td class='text-right'>\$price</td>
	    			<td class='text-center'>\$relation</td>
					<td class='text-center'>\$str_manage</td>
				</tr>";
        $taxonomys = $tree->get_tree(0, $str);
        return $taxonomys;
    }
    //获取添加贵金属树形数据
    public function get_metaltype_tree($parentid,$where=array()){
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $condition=array("deleted"=>0,"status"=>1,"company_id"=>get_company_id());
        if(!empty($where)){
            $condition=array_merge($where,$condition);
        }
        $terms = $this->getList($condition,$field='*',$limit=null,$join='',$order=array("path"=>"asc"),$group='');
        // $terms = $this->where(array("deleted"=>0))->order(array("path"=>"asc"))->select();
        $new_terms=array();
        foreach ($terms as $r) {
            $r['id']=$r['id'];
            $r['parentid']=$r['pid'];
            $r['selected']= (!empty($parentid) && $r['id']==$parentid)? "selected":"";
            $new_terms[] = $r;
        }
        $tree->init($new_terms);
        $tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
        $tree=$tree->get_tree(0,$tree_tpl,$parentid);
        return $tree;
    }
    //获取编辑贵金属树形数据
    public function get_edit_metaltype_tree($id,$data){
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $condition=array("id" => array("NEQ",$id), "path"=>array("notlike","%-$id-%"),"deleted"=>0,"company_id"=>get_company_id());
        $terms = $this->getList($condition,$field='*',$limit=null,$join='',$order=array("path"=>"asc"),$group='');
        //$this->where(array("id" => array("NEQ",$id), "path"=>array("notlike","%-$id-%"),"deleted"=>0))->order(array("path"=>"asc"))->select();
        $new_terms=array();
        foreach ($terms as $r) {
            $r['id']=$r['id'];
            $r['parentid']=$r['pid'];
            $r['selected']=$data['pid']==$r['id']?"selected":"";
            $new_terms[] = $r;
        }
        $tree->init($new_terms);
        $tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
        $treedata=$tree->get_tree(0,$tree_tpl,$data["pid"]);
        return $treedata;
    }
    public function get_metaltype_price($condition=array()){
        $info=D("BMetalPrice")->getInfo($condition,$field='price',$join="",$order="create_time desc");
        return empty($info['price'])?"0.00":$info['price'];
    }

}
