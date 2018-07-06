<?php
namespace Business\Controller;
use Business\Controller\BusinessbaseController;
class BCompanyController extends BusinessbaseController{
    //企业列表
    public function index(){
        $search_name=I('post.search_name');
        $condition=array(
            'gb_b_company.company_id'=>$this->MUser["company_id"],
            'gb_b_company.company_type'=>1,
            'gb_b_company.deleted'=>0
        );
        if($_REQUEST['search_name']){
            $condition=array(
                'gb_b_company.company_name|u.user_nicename|gb_b_company.company_code|gb_b_company.company_legal_person|u.user_login|u3.user_nicename|u3.user_login' => array('like', "%" . trim($search_name) . "%")
            );
            $this->assign('search_name',$search_name);
        }
        $field='gb_b_company.*,u.user_nicename as name1,u2.user_nicename as name2,u3.user_nicename as name3';
        $order='company_id desc';
        $join='left join gb_m_users as u on u.id=gb_b_company.company_uid ';
        $join.=" left join gb_m_users as u2 on u2.id=gb_b_company.account_uid ";
        $join.=" left join gb_m_users as u3 on u3.id=gb_b_company.user_id ";
        $count=D('Business/BCompany')->countList($condition,$field,$join);
        $page=$this->page($count,$this->pagenum);
        $show= $page->show('Admin');
        $company_list=D('Business/BCompany')->getList($condition,$field,$page->firstRow.','.$page->listRows,$join,$order);
        if(empty($company_list)){
            $company_list=null;
        }
        foreach($company_list as $key => $val){
            $company_list[$key]['user_nicename']=($company_list[$key]['user_nicename']?$company_list[$key]['user_nicename']:($company_list[$key]['user_login']?$company_list[$key]['user_login']:$company_list[$key]['mobile']));
        }
        $this->assign('company_list',$company_list);
        $this->assign('page',$show);
        $this->display();
    }
    //用户列表
    public function user_list(){
        if(!empty(I('post.'))){
            $name=I('post.mobile')?I('post.mobile'):I('get.mobile');
            if(!empty($name)){
                $condition ='user_nicename like "%'.$name.'%" or mobile ="'.$name.'"';
                $condition.=" and user_type !=4";
                $user_list=D('MUsers')->getList($condition);
                $this->assign('mobile',$name);
                foreach ($user_list as $key => $val){
                    $user_list[$key]['user_nicename']=(!empty($user_list[$key]['user_nicename'])?$user_list[$key]['user_nicename']:$user_list[$key]['user_login']);
                }
                $this->assign('user_list',$user_list);
            }
        }
        $this->display();
    }
    //用户列表
    public function Company_list(){
        if(!empty(I('post.'))){
            $name=I('post.company')?I('post.company'):I('get.company');
            if(!empty($name)){
                $condition ='company_code like "%'.$name.'%" or company_name ="'.$name.'"';
                $condition .=' and company_status in (1,3) and deleted=0 ';
                $user_list=D('Business/BCompany')->getList($condition);
                $this->assign('mobile',$name);
                $this->assign('user_list',$user_list);
            }
        }
        $this->display();
    }
    //营业执照上传
    public function upload_img(){
        $dirpath=$_SERVER['DOCUMENT_ROOT'].__ROOT__.'/Uploads/Company/company_img/';
        if(!is_dir($dirpath)){
            //递归创建多级文件夹
            mkDirs($dirpath);
        }
        //如果有旧发票图片则删除
        if(I('post.del_img')){
            $dir_path=str_replace("http://".$_SERVER['HTTP_HOST'],$_SERVER['DOCUMENT_ROOT'], I('post.del_img'));
            @unlink($dir_path);
        }
        $type=explode('/', $_FILES['upload_img']['type']);
        $file_name=$dirpath.time().'.'.$type[1];
        move_uploaded_file($_FILES["upload_img"]["tmp_name"],$file_name);
        $result=file_exists($file_name);
        $file_name=str_replace($_SERVER['DOCUMENT_ROOT'], "http://".$_SERVER['HTTP_HOST'], $file_name);
        if($result!==false){
            $info["status"]="1";
            $info["url"]=U('procure/financial_check');
            $info["company_img"]=$file_name;
        }else{
            $info["status"]="0";
        }
        echo json_encode($info,true);
    }

    //增加商户
    public function add(){
        if(empty(I('post.'))){
            $info=D("MUsers")->getInfo("id=".$this->MUser["id"]);
            $this->assign("user_nicename",$info['user_nicename']);
            $this->display();
        }else{
            $insert = I('post.data');
            foreach($insert as $key =>$val){
                if(empty($val)){
                    unset($insert[$key]);
                }
            }

            M()->startTrans();
            $insert['create_time']=time();

            $condition = array();
            $condition["user_id"] = $insert['user_id'];
            $condition["deleted"] = 0;
            $condition['company_status'] = array("neq", "2");
            $company = D('Business/BCompany')->getInfo($condition);
            if ($company) {
                output_error('该联系人已经绑定企业');
            }else{
                $where['id'] = $insert['user_id'];
                $info = D('MUsers')->getInfo($where);
                $arr2 = array();
                $arr2['mobile'] = "2".substr($info['mobile'],1);
                $i= D('MUsers')->getInfo($arr2);
                if($i){
                    output_error('该联系人不能绑定企业');
                }
                $arr2['user_nicename'] = $insert['company_name'];
                $arr2['sex'] = $_POST['sex'];
                $arr2['user_type'] = 4;
                $arr2['create_time'] = time();
                $arr2['user_pass'] = sp_password("123456");
                // 创建企业用户
                $comp_user_id = D('MUsers')->insert($arr2);
                if($comp_user_id > 0){
                    $insert['company_uid'] = $comp_user_id;
                    $insert['creator_id'] = get_user_id();
                    if($insert['company_type'] != 1){
                        $insert['parent_id'] = 0;
                    }else{
                        $cinfo = D('Business/BCompany')->getInfo("company_id = ". $insert['parent_id']);
                        $num = D('Business/BCompany')->countList("company_type = 1 and parent_id = ". $insert['parent_id']);
                        if($num+1 > $cinfo['company_num']){
                            M()->rollback();
                            output_error('添加失败超过授权加盟商数量！');
                        }
                    }
                    $r = D('Business/BCompany')->insert($insert);
                    if ($r) {
                        $w["id"] = $comp_user_id;
                        $da['company_id'] = $r;
                        $c = D("Common/MUsers")->update($w,$da);
                        if($c !== false){
                            M()->commit();
                            output_data(array('msg' => '添加成功！'));
                        }else{
                            M()->rollback();
                            output_error('添加失败！');
                        }
                    } else {
                        M()->rollback();
                        output_error('添加失败！');
                    }
                }else{
                    M()->rollback();
                    output_error('添加失败！');
                }
            }
        }
    }

    //编辑用金企业
    public function edit(){
        if(empty(I('post.'))){
            $id=I('get.id');
            $condition=array(
                'company_id'=>$id,
                'deleted'=>0
            );
            $company_info=D('Business/BCompany')->getCompanyDetail($condition);
            $this->assign('company_info',$company_info);
            $this->display();
        }else{
            $update=I('post.data');
            $condition=array('company_id'=>I('post.company_id'));
            $result=D('Business/BCompany')->update($condition,$update);
            if($result!=false||$result==0){
                output_data(array('msg'=>'编辑成功！'));
            }else{
                output_error('编辑失败！');
            }
        }
    }
    //用金企业详情
    public function detail(){
        $id=I('get.id');
        $condition=array(
            'company_id'=>$id,
            'deleted'=>0
        );
        $company_info=D('Business/BCompany')->getCompanyDetail($condition);
        $status=array("未审核","审核通过","审核不通过","锁定");
        $this->assign("status",$status);
        $this->assign('company_info',$company_info);
        $this->display();
    }
    //删除
    public function deleted(){
        $id=I('get.id');
        $condition=array('company_id'=>$id);
        $update=array('deleted'=>1);
        M("")->startTrans();
        $result=D('Business/BCompany')->update($condition,$update);
        if($result){
            $condition=array('company_id'=>$id);
            $update=array('user_status'=>0);
            $res=D('MUsers')->update($condition,$update);
        }
        if($res!==false){
            M("")->commit();
            output_data('操作成功！');
        }else{
            M("")->rollback();
            output_error('操作失败！');
        }
    }
    //锁定
    public function lock(){
        $id=I('get.id');
        $condition=array('company_id'=>$id);
        $update=array('company_status'=>3);
        $result=D('Business/BCompany')->update($condition,$update);
        if($result!==false){
            output_data('操作成功！');
        }else{
            output_error('操作失败！');
        }
    }
    //解锁
    public function unlock(){
        $id=I('get.id');
        $condition=array('company_id'=>$id);
        $update=array('company_status'=>1);
        $result=D('Business/BCompany')->update($condition,$update);
        if($result!==false){
            output_data('操作成功！');
        }else{
            output_error('操作失败！');
        }
    }

    public function check_list(){
        $search_name=I('post.search_name');
        $condition=array(
            'gb_b_company.deleted'=>0,
            'gb_b_company.company_status'=>0
        );
        if($_REQUEST['search_name']){
            $condition=array(
                'gb_b_company.company_name|u.user_nicename|gb_b_company.company_code|gb_b_company.company_legal_person|u.user_login|u3.user_nicename|u3.user_login' => array('like', "%" . trim($search_name) . "%")
            );
            $this->assign('search_name',$search_name);
        }
        $field='gb_b_company.*,u.user_nicename as name1,u2.user_nicename as name2,u3.user_nicename as name3';
        $order='company_id desc';
        $join='left join gb_m_users as u on u.id=gb_b_company.company_uid ';
        $join.=" left join gb_m_users as u2 on u2.id=gb_b_company.account_uid ";
        $join.=" left join gb_m_users as u3 on u3.id=gb_b_company.user_id ";
        $count=D('Business/BCompany')->countList($condition,$field,$join);
        $page=$this->page($count,$this->pagenum);
        $show= $page->show('Admin');
        $company_list=D('Business/BCompany')->getList($condition,$field,$page->firstRow.','.$page->listRows,$join,$order);
        if(empty($company_list)){
            $company_list=null;
        }
        foreach($company_list as $key => $val){
            $company_list[$key]['user_nicename']=($company_list[$key]['user_nicename']?$company_list[$key]['user_nicename']:($company_list[$key]['user_login']?$company_list[$key]['user_login']:$company_list[$key]['mobile']));
        }
        $this->assign('company_list',$company_list);
        $this->assign('page',$show);
        $this->display();
    }

    public function check(){
        if($_POST){
            $where['company_id']=I("id");
            $data['company_status']=I("type");
            $r=D("Business/BCompany")->update($where,$data);
            if($r !== false) {
                $result['status'] = 1;
                $result['info'] = '操作成功';
            }else{
                $result['status'] = 0;
                $result['info'] = '操作失败';
            }
             $this->ajaxReturn($result);
        }else{
            $id=I('get.id');
            $condition=array(
                'company_id'=>$id,
                'deleted'=>0
            );
            $company_info=D('Business/BCompany')->getCompanyDetail($condition);
            $status=array("未审核","审核通过","审核不通过","锁定");
            $this->assign("status",$status);
            $this->assign('company_info',$company_info);
            $this->display();
        }
    }
}