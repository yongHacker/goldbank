<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BArticleClassModel extends BCommonModel{
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('name', 'require', '分类名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),
        array('name',"require","分类名称不能为空", 1, 'regex', BCommonModel:: MODEL_UPDATE ),
    );
    public function __construct() {
        parent::__construct();
    }
    protected $taxonomys=array("article"=>"文章","picture"=>"图片");
    protected function _after_insert($data,$options){
        parent::_after_insert($data,$options);
        $ac_id=$data['ac_id'];
        $parent_id=$data['parent'];
        if($parent_id==0){
            $d['path']="0-$ac_id";
        }else{
            $parent=$this->where("ac_id=$parent_id")->find();
            $d['path']=$parent['path'].'-'.$ac_id;
        }
        $this->where("ac_id=$ac_id")->save($d);
    }

    protected function _after_update($data,$options){
        parent::_after_update($data,$options);
        if(isset($data['parent'])){
            $ac_id=$data['ac_id'];
            $parent_id=$data['parent'];
            if($parent_id==0){
                $d['path']="0-$ac_id";
            }else{
                $parent=$this->where("ac_id=$parent_id")->find();
                $d['path']=$parent['path'].'-'.$ac_id;
            }
            $result=$this->where("ac_id=$ac_id")->save($d);
            if($result){
                $children=$this->where(array("parent"=>$ac_id))->select();
                foreach ($children as $child){
                    $this->where(array("ac_id"=>$child['ac_id']))->save(array("parent"=>$ac_id,"ac_id"=>$child['ac_id']));
                }
            }
        }

    }
    //获取文章分类表格树形数据
    public function get_articleclass_treetable(){
        $result = $this->where(array("deleted"=>0,"company_id"=>get_company_id()))->order(array("listorder"=>"asc"))->select();

        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        foreach ($result as $r) {
            $r['str_manage'] = '<a href="' . U("BArticleClass/add", array("parent" => $r['ac_id'])) . '">'.L('ADD_SUB_CATEGORY').'</a> | <a href="' . U("BArticleClass/edit", array("id" => $r['ac_id'])) . '">'.L('EDIT').'</a> | <a class="js-ajax-delete" href="' . U("BArticleClass/delete", array("id" => $r['ac_id'])) . '">'.L('DELETE').'</a> ';
            $url=U('Business/BArticleClass/show',array('id'=>$r['ac_id']));
            $r['url'] = $url;
            $r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
            $r['id']=$r['ac_id'];
            $r['parentid']=$r['parent'];
            $array[] = $r;
        }

        $tree->init($array);
        //<td>$spacer <a href='$url' target='_blank'>$name</a></td>
        $str = "<tr>
					<td><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input input-order'></td>
					<td>\$id</td>
					<td>\$spacer \$name</td>
	    			<td>\$taxonomys</td>
					<td>\$str_manage</td>
				</tr>";
        $taxonomys = $tree->get_tree(0, $str);
        return $taxonomys;
    }
    //获取添加文章分类树形数据
    public function get_articleclass_tree($parentid=0){
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $terms = $this->where(array("deleted"=>0,"company_id"=>get_company_id()))->order(array("path"=>"asc"))->select();

        $new_terms=array();
        foreach ($terms as $r) {
            $r['id']=$r['ac_id'];
            $r['parentid']=$r['parent'];
            $r['selected']= (!empty($parentid) && $r['sc_id']==$parentid)? "selected":"";
            $new_terms[] = $r;
        }
        $tree->init($new_terms);
        $tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
        $tree=$tree->get_tree(0,$tree_tpl,$parentid);
        return $tree;
    }
    //获取编辑文章分类树形数据
    public function get_edit_articleclass_tree($id,$data){
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $terms = $this->where(array("ac_id" => array("NEQ",$id), "path"=>array("notlike","%-$id-%"),"deleted"=>0))->order(array("path"=>"asc"))->select();
        $new_terms=array();
        foreach ($terms as $r) {
            $r['id']=$r['ac_id'];
            $r['parentid']=$r['parent'];
            $r['selected']=$data['parent']==$r['ac_id']?"selected":"";
            $new_terms[] = $r;
        }
        $tree->init($new_terms);
        $tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
        $tree=$tree->get_tree(0,$tree_tpl,$data["parent"]);
        return $tree;
    }
}
