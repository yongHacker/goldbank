<?php
/**
 * 数据表的注释管理
 * Date: 16/12/6
 * Time: 下午13:30
 */
namespace Business\Controller;
use Business\Controller\BusinessbaseController;
class BStatusController extends BusinessbaseController{
    public function __construct() {
        parent::__construct();
        $this->bstatus_model = D('BStatus');
        $this->b_show_status('b_status');
        $this->b_show_status('b_status_value');
    }
    //参数列表
    public function index(){
        $user_id=get_user_id();
        //查询是否是最高权限组
        $is_admin=1;//is_admin($user_id);
        $this->assign('is_admin',$is_admin?1:0);
        $status_model=D('BStatus');
        $condition=array(
           '1'=>'1'
        );
        $_REQUEST['search_value']=trim($_REQUEST['search_value']);
        if(!empty($_REQUEST['search_value'])){
            $condition['table|field']=array('like','%'.$_REQUEST['search_value'].'%');
            $this->assign('search_value',$_REQUEST['search_value']);
        }
        $count=M('b_status_value as bstatusvalue')
            ->join('join '.DB_PRE.'b_status as bstatus on bstatusvalue.s_id=bstatus.id')
            ->where($condition)->count();
        $page=$this->page($count,$this->pagenum);
        $show= $page->show('Admin');
        $field='bstatus.table ASC,bstatus.field ASC,FLOOR(bstatusvalue.value) ASC';
        $limit=$page->firstRow.",".$page->listRows;
        $status_list=$status_model->getStatusList($condition,$page->firstRow.','.$page->listRows,$field);
        $this->assign('numpage',$this->pagenum);
        $this->assign('page',$show);
        $this->assign('status_list',$status_list);
        $this->display();
    }
    //添加参数值
    public function add(){
        $status_model=$this->bstatus_model;
        if(!empty($_POST['search_table'])){
            $fields=$status_model->get_all_field($_POST['search_table']);
            die(json_encode($fields));
        }elseif(empty($_POST)){
            $tables=$status_model->get_table();      
            $this->assign('tables',$tables);
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
                    $result=$status_model->insert_value($insert);
                    if($result!=false){
                        $info['status']='1';
                        $info['url']=U('BStatus/index');
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
                    $info['url']=U('BStatus/index');
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
        $status_model=D('BStatus');
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
            $result=$status_model->updateValue($condition,$update);
            if($result>=0){
                $info['status']='1';
                $info['url']=U('BStatus/index');
            }else{
                $info['status']='0';
                $info['msg']='操作失败！';
            }

            die(json_encode($info,true));
        }
    }
    
}