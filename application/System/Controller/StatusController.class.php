<?php

namespace System\Controller;

use Common\Controller\SystembaseController;

class StatusController extends SystembaseController{

    public function __construct() {
        parent::__construct();
        $this->show_status('a_status_value');
    }

    // 参数列表
    public function index(){
        $condition=array(
           '1'=>'1'
        );

        $search_value = trim(I('search_value/s'));
        if(!empty($search_value)){
            $condition['table|field']=array('like','%'. $search_value .'%');
        }

        $join='join '.C('DB_PREFIX').'a_status_value as sv on sv.s_id=gb_a_status.id';

        $count = D('System/AStatus')->countList($condition,"",$join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.','.$page->listRows;

        $field = 'sv.*,gb_a_status.table,gb_a_status.field';
        $order = 'gb_a_status.table ASC,gb_a_status.field ASC,FLOOR(sv.value) ASC';
        $status_list = D('System/AStatus')->getList($condition, $field, $limit, $join, $order);

        $this->assign('setting_url',"status_index");
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));
        $this->assign('status_list', $status_list);

        $this->display();
    }
    //添加参数值
    public function add(){
        $status_model=D('System/AStatus');
        if(!empty($_POST['search_table'])){
            $fields=$status_model->get_all_field($_POST['search_table']);
            die(json_encode($fields));
        }elseif(empty($_POST)){
            $tables=$status_model->get_table();      
            $this->assign('tables',$tables);
            $this->assign("setting_url","status_add");
            $this->display();
        }else{
            $insert['table']=$_POST['table'];
            $insert['field']=$_POST['field'];
            $is_exsit=$status_model->status_exsit($insert);
            $info=array();
            if($is_exsit){
                $insert['value']=$_POST['value'];
                $value_exsit=$status_model->value_exsit($is_exsit,$insert);
                if($value_exsit){
                    $info['status']='0';
                    $info['msg']='已存在相同的值！';
                }else{
                    $insert['s_id']=$is_exsit;
                    $insert['comment']=$_POST['comment'];
                    $insert['value']=$_POST['value'];
                    $insert['status']=$_POST['status'];
                    unset($insert['table']);
                    unset($insert['field']);
                    $result=D("AStatusValue")->insert($insert);
                    if($result!=false){
                        $info['status']='1';
                        $info['url']=U('status/index');
                    }else{
                        $info['status']='0';
                        $info['msg']='添加失败！';
                    }
                }
            }else{
                $result=$status_model->insert($insert);
                $insert['s_id']=$result;
                $insert['value']=$_POST['value'];
                $insert['comment']=$_POST['comment'];
                $insert['value']=$_POST['value'];
                $insert['status']=$_POST['status'];
                unset($insert['table']);
                unset($insert['field']);
                $result=$status_model->insert_value($insert);
                if($result!=false){
                    $info['status']='1';
                    $info['url']=U('status/index');
                }else{
                    $info['status']='0';
                    $info['msg']='添加失败！';
                }
            }
            die(json_encode($info,true));
        }
    }
    //修改参数值
    public function edit(){
        $status_model=D('System/AStatus');
        if(!empty($_POST['search_table'])){
            $fields=$status_model->get_all_field($_POST['search_table']);
            die(json_encode($fields));
        }elseif(empty($_POST)){
            $tables=$status_model->get_table();
            $this->assign('tables',$tables);
            $id=$_REQUEST['id'];
            $value_info=$status_model->get_value_info(array('id'=>$id));
            $s_id=$value_info['s_id'];
            $status_info=$status_model->get_status_info(array('id'=>$s_id));
            unset($status_info['id']);
            $info=array_merge($value_info,$status_info);
            $this->assign("setting_url","status_edit");
            $this->assign('info',$info);
            $this->display();
        }else{
            $condition=array(
                'id'=>$_POST['id']
            );
            $update=array(
                'value'=>$_POST['value'],
                'comment'=>$_POST['comment'],
                'status'=>$_POST['status']
            );
            $result=D("System/AStatusValue")->update($condition,$update);
            if($result>=0){
                $info['status']='1';
                $info['url']=U('status/index');
            }else{
                $info['status']='0';
                $info['msg']='操作失败！';
            }

            die(json_encode($info,true));
        }
    }
    
}