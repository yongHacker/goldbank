<?php
namespace Business\Controller;
use Business\Controller\BusinessbaseController;
class BProductSubController extends BusinessbaseController{

    public function __construct() {
        parent::__construct();
        $this->bstatus_model = D('BStatus');
        $this->bprodcut_sub_model = D('BProductSub');
        $where=array();
        $where['table']="gb_b_product_sub";
        $were['field']="sub_type";
        $this->sub_types = $this->bstatus_model->getStatusInfo($where); //获取货品附属信息类型
        $this->assign("sub_types",$this->sub_types);
    }

    public function index(){
        $where=array(
            'deleted'=>0,
            'company_id' => $this->MUser["company_id"]
        );
        $search_value=trim(I('search_value'));
        $sub_type=I('sub_type');
        if(!empty($search_value)){
            $where['sub_value|sub_note']=array('like','%'.$search_value.'%');
            $this->assign('search_value',$search_value);
        }
        if(!empty($sub_type)){
            $where['sub_type']=$sub_type;
            $this->assign("sub_type",$sub_type);
        }
        $where['company_id']=$this->MUser["company_id"];
        $count=$this->bprodcut_sub_model->countList($where);
        $page=$this->page($count,$this->pagenum);
        $show= $page->show('Admin');
        $limit=$page->firstRow.",".$page->listRows;
        $list=$this->bprodcut_sub_model->getList($where,"*",$limit);
        $tmp_arr = array();
        foreach($this->sub_types as $val){
            $tmp_arr[$val['value']] = $val;
        }
        $this->sub_types = $tmp_arr;

        $this->assign("list", $list);
        $this->assign("sub_types", $this->sub_types);
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $show);
        $this->display();
    }
    public function add(){
        if(IS_POST){
            $sub_type=trim(I("sub_type"));
            $sub_note=trim(I("sub_note"));
            $sub_value=trim(I("sub_value"));
            $where=array();
            $where['company_id']=$this->MUser["company_id"];
            $where['sub_type']=$sub_type;
            $where['sub_value']=$sub_value;
            $info=$this->bprodcut_sub_model->getInfo($where);
            if($info){
                $info['status']='0';
                $info['msg']='当前类型的附属信息值已经存在！';
                $info['info']='当前类型的附属信息值已经存在！';
                $this->ajaxReturn($info);
            }else{
                $data=array(
                    'company_id'=>$this->MUser['company_id'],
                    'sub_type'=>$sub_type,
                    'sub_note'=>$sub_note,
                    'sub_value'=>$sub_value,
                );
                $res=$this->bprodcut_sub_model->add($data);
                if($res){
                    $info['status']='1';
                    $info['msg']='添加成功！';
                    $info['url']=U("BProductSub/index");
                    $this->ajaxReturn($info);
                }else{
                    $info['status']='0';
                    $info['msg']='添加失败！';
                    $this->ajaxReturn($info);
                }
            }
        }else{
            $this->display();
        }
    }
    public function edit(){
        if(IS_POST){
            $sub_type=trim(I("sub_type"));
            $sub_note=trim(I("sub_note"));
            $sub_value=trim(I("sub_value"));
            $id=I("id");
            $where=array();
            $where['company_id']=$this->MUser["company_id"];
            $where['sub_type']=$sub_type;
            $where['sub_value']=$sub_value;
            $where['id']=array("neq",$id);
            $info=$this->bprodcut_sub_model->getInfo($where);
            if($info){
                $info['status']='0';
                $info['msg']='当前类型的附属信息值已经存在！';
                $info['info']='当前类型的附属信息值已经存在！';
                $this->ajaxReturn($info);
            }else{
                $data=array(
                    'company_id'=>$this->MUser['company_id'],
                    'sub_type'=>$sub_type,
                    'sub_note'=>$sub_note,
                    'sub_value'=>$sub_value,
                );
                $where=array(
                  'id'=>$id
                );
                $res=$this->bprodcut_sub_model->update($where,$data);
                if($res!==false){
                    $info['status']='1';
                    $info['msg']='修改成功！';
                    $info['url']=U("BProductSub/index");
                    $this->ajaxReturn($info);
                }else{
                    $info['status']='0';
                    $info['msg']='修改失败！';
                    $this->ajaxReturn($info);
                }
            }
        }else{
            $id=I("id");
            if(empty($id)){
                $this->error("信息错误");
            }
            $where=array(
                'id'=>$id
            );
            $info=$this->bprodcut_sub_model->getInfo($where);
            $this->assign("info",$info);
            $this->display();
        }
    }
    public function deleted(){
        $id=trim(I("id"));
        if(empty($id)){
            $this->error("信息错误");
        }
        $data['deleted']=1;
        $res=$this->bprodcut_sub_model->update("id =".$id,$data);
        if ($res!==false) {
            $this->success("删除成功！", U("BProductSub/index"));
        } else {
            $this->error("删除失败！");
        }
    }
}