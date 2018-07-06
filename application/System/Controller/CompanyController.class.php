<?php
namespace System\Controller;
use Common\Controller\SystembaseController;
class CompanyController extends SystembaseController{
    //当前类需要的模型
    const MUSERS='m_users',BCOMPANY='b_company',BEMPLOYEE='b_employee',
        BAUTHACCESS='b_auth_access',BROLE='b_role',BROLEUSER='b_role_user',
        BMENU='b_menu',BWAREHOUSE='b_warehouse',BOPTIONS='b_options';
    public function __construct(){
        parent::__construct();
    }

    // 企业列表
    public function index(){
        $search_name = trim(I('post.search_name'));
        $condition=array(
            'gb_b_company.deleted'=> 0
        );
        if($search_name){
            $condition['gb_b_company.company_name|u.user_nicename|gb_b_company.company_code|gb_b_company.company_legal_person|u.user_login|u3.user_nicename|u3.user_login'] = array('like', "%" . $search_name . "%");
        }
        
        $field = 'gb_b_company.*,u.user_nicename as name1,u.mobile login_mobile,u2.user_nicename as name2,u3.user_nicename as name3';
        
        $order = 'company_id desc';
        
        $join = 'left join gb_m_users as u on u.id=gb_b_company.company_uid ';
        $join .= " left join gb_m_users as u2 on u2.id=gb_b_company.account_uid ";
        $join .= " left join gb_m_users as u3 on u3.id=gb_b_company.user_id ";
        
        $count=D(self::BCOMPANY)->countList($condition, $field, $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.','.$page->listRows;

        $company_list = D(self::BCOMPANY)->getList($condition, $field, $limit, $join, $order);
        if(empty($company_list)){
            $company_list = null;
        }

        // html 没用到 user_nciename
        // foreach($company_list as $key => $val){
        //     $company_list[$key]['user_nicename']=($company_list[$key]['user_nicename']?$company_list[$key]['user_nicename']:($company_list[$key]['user_login']?$company_list[$key]['user_login']:$company_list[$key]['mobile']));
        // }
        $this->assign('company_list',$company_list);
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));

        $this->display();
    }

    // 添加企业用户
    public function add_user(){
        $rsData = array('msg'=> '添加成功！');

        $add_name = I('post.add_name/s');
        $add_mobile = I('post.add_mobile/s');
        $mobile_area = I('post.mobile_area/d', 1);
        if(!empty($add_name) && !empty($add_mobile)){

            if(!check_mobile($add_mobile, $mobile_area)){
                output_error('不支持的手机号码！');
            }

            $where = array(
                'user_nicename'=> $add_name,
                'mobile'=> $add_mobile
            );
            $user_info = D(self::MUSERS)->getInfo($where);
            if(empty($user_info)){

                $nowtime = time();

                M()->startTrans();
                $condition = array(
                    'mobile_area'=> $mobile_area,
                    'mobile|user_login'=> $add_mobile,
                    'user_nicename'=> $add_name,
                    '_logic'=> 'or'
                );
                $condition = array(
                    'mobile_area'=> $mobile_area,
                    array(
                        'mobile|user_login'=> $add_mobile,
                        'user_nicename'=> $add_name,
                        '_logic'=> 'or'
                    )
                );
                $exist_num = D(self::MUSERS)->countList($condition);
                if($exist_num == 0){
                    $new_user_data = array(
                        'mobile'=> $add_mobile,
                        'mobile_area'=> $mobile_area,
                        'user_login'=> $add_mobile,
                        'user_nicename'=> $add_name,
                        'create_time'=> $nowtime,
                        'user_pass'=> sp_password('123456'),
                        'user_type'=> 2
                    );
                    $rs = D(self::MUSERS)->insert($new_user_data);
                    if($rs){
                        $new_user_id = $rs;

                        M()->commit();

                        $rsData['url'] = U('Company/user_list', array('mobile'=> $search_mobile, 'select_id'=> $new_user_id));
                        output_data($rsData);

                    }else{
                        M()->rollback();
                        output_error('添加失败！');
                    }
                }else{
                    output_error('用户名或手机不可用！');
                }
            }
        }else{
            output_error('添加失败！');
        }
    }

    // 用户列表
    public function user_list(){
        // admin 不显示
        // 已经关联商户的用户不显示
        $sub_query = D(self::BCOMPANY)->where('deleted = 0')->field('user_id')->select(false);
        $sub_where = array(
            array('id'=> array('exp', 'not in ('. $sub_query .')')),
            array('id'=> array('neq', 1)),
            '_logic'=> 'AND'
        );
        // 商户登录账户不显示
        $condition = array(
            $sub_where,
            'user_type'=> array('neq', 4),
            'company_id'=> 0,
            'mobile'=> array('neq', '')
        );

        $name = trim(I('mobile'));
        if(!empty($name)){

            $ex_where = array(
                array('user_nicename'=> array('like', '%'. $name .'%')),
                array('mobile'=> $name),
                '_logic'=> 'OR'
            );

            // $condition ='user_nicename like "%'.$name.'%" or mobile ="'.$name.'"';
            $condition['_complex'] = $ex_where;
        }

        $count = D(self::MUSERS)->countList($condition);
        // echo M()->getLastSql();die();
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.','.$page->listRows;
        
        $user_list = D(self::MUSERS)->getList($condition, '*', $limit);

        $select_id = I('select_id/d', 0);

        foreach ($user_list as $key => $val){
            $val['user_nicename'] = (!empty($val['user_nicename'])?$val['user_nicename']:$val['user_login']);
            $val['selected'] = 0;

            if($val['id'] == $select_id){
                $val['selected'] = 1;
            }else{
                $val['selected'] = 0;
            }

            $user_list[$key] = $val;
        }

        $this->assign('user_list', $user_list);
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));

        $this->display();
    }

    // 商户列表
    public function company_list(){

        $condition = array(
            'company_status'=> array('in', '1,3'),
            'deleted'=> 0
        );

        if(!empty(I('post.'))){
            $name = trim(I('company'));
            if(!empty($name)){

                $ex_where = array(
                    array('company_code'=> array('like', '%'. $name .'%')),
                    array('company_name'=> $name),
                    '_logic'=> 'OR'
                );

                $condition['_complex'] = $ex_where;
                // $condition ='company_code like "%'.$name.'%" or company_name ="'.$name.'"';
            }
        }

        $count = D(self::BCOMPANY)->countList($condition);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.','.$page->listRows;

        $user_list = D(self::BCOMPANY)->getList($condition, '*', $limit);

        $this->assign('user_list', $user_list);
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));

        $this->display();
    }

    // 营业执照上传
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

    // 增加商户
    public function add(){
        $id = get_current_system_id();
        if(empty(I('post.'))){
            $info = D(self::MUSERS)->getInfo("id = ". $id);
            $this->assign("user_nicename", $info['user_nicename']);
            $this->display();
        }else{
            $insert = I('post.data');
            foreach($insert as $key =>$val){
                if(empty($val)){
                    unset($insert[$key]);
                }
            }

            $where = array(
                'company_code'=> $insert['company_code']
            );
            $com_info = D(self::BCOMPANY)->getInfo($where);
            if(!empty($com_info)){
                output_error('该商户号已被使用！');
            }

            M()->startTrans();

            $nowtime = time();

            $insert['create_time'] = $nowtime;
            $insert['begin_time'] = $nowtime;
            $insert['end_time'] = empty($insert['service_year'])?$nowtime:strtotime("+".$insert['service_year']." year");

            $condition = array();
            $condition["user_id"] = $insert['user_id'];
            $condition["deleted"] = 0;
            $condition['company_status'] = array("neq", "2");
            $company = D(self::BCOMPANY)->getInfo($condition);
            if (!empty($company)) {
                output_error('该联系人已经绑定企业');
            }else{
                /*查询是否存在同样名称的额企业add by lzy 2018.5.3 start*/
                $name_condition=array('company_name'=>$insert['company_name'],'deleted'=>0,'company_status'=>array('neq','2'));
                $company = D(self::BCOMPANY)->getInfo($name_condition);
                if(!empty($company)){
                    output_error('已有重名企业存在！');
                }
                /*查询是否存在同样名称的额企业add by lzy 2018.5.3 end*/
                // 查询关联信息用户
                $where['id'] = $insert['user_id'];
                $info = D(self::MUSERS)->getInfo($where);

                $arr2 = array();
                // $arr2['mobile'] = "2".substr($info['mobile'], 1);
                $arr2['mobile'] = $insert['user_setting']['user_login'];
                $i = D(self::MUSERS)->getInfo($arr2);
                if(!empty($i)){
                    output_error('已存在该商户号用户');
                }

                $arr2['user_nicename'] = $insert['company_name'];
                $arr2['user_login'] = $arr2['mobile'];
                // $arr2['sex'] = $_POST['sex'];
                $arr2['user_type'] = 4;
                $arr2['create_time'] = $nowtime;
                $user_psw = $insert['user_setting']['user_psw'] == '' ? '123456' : $insert['user_setting']['user_psw'];
                $arr2['user_pass'] = sp_password($user_psw);
                // $arr2['user_pass'] = sp_password("123456");
                // 新增名字为商户名称的user 
                $li = D(self::MUSERS)->insert($arr2);
                if($li > 0){

                    unset($insert['user_setting']);

                    $insert['company_uid'] = $li;
                    $insert['creator_id'] = $id;

                    if($insert['company_type'] != 1){
                        $insert['parent_id'] = 0;
                    }else{
                        $cinfo = D(self::BCOMPANY)->getInfo("company_id = ". $insert['parent_id']);
                        $num = D(self::BCOMPANY)->countList("company_type = 1 and parent_id = ". $insert['parent_id']);
                        if($num+1 > $cinfo['company_num']){
                            M()->rollback();
                            output_error('添加失败超过授权加盟商数量！');
                        }
                    }

                    $r = D(self::BCOMPANY)->insert($insert);
                    if($r !== false) {
                        $w["id"] = $li;

                        $da['company_id'] = $r;

                        $c = D(self::MUSERS)->update($w, $da);
                        $employee_data = array(
                            "company_id"=>$r,
                            "employee_name"=>$insert['company_name'],
                            "user_id"=>$li,
                            "creator_id"=>$id,
                            "updater_id"=>$id,
                            "create_time"=>time(),
                            "update_time"=>time()
                        );
                        $employee = D(self::BEMPLOYEE)->insert($employee_data);

                        $default_warehouse = array(
                            'company_id'=> $r,
                            'wh_uid'=> $li,
                            'wh_name'=> '默认采购仓',
                            'wh_code'=> 'default_'.$r,
                            'shop_id'=> -1,
                            'address'=> $insert['company_addr'],
                            'created_time'=> time()
                        );
                        $ware_house = D(self::BWAREHOUSE)->insert($default_warehouse);

                        $options_data = array(
                            'company_id'=> $r,
                            'option_name'=> 'b_procurement_warehouse',
                            'option_value'=> $ware_house,
                            'update_time'=> time(),
                            'user_id'=> $id
                        );
                        $options_rs = D(self::BOPTIONS)->insert($options_data);

                        if($c !== false && $employee !== false && $ware_house !== false && $options_rs !== false){
                            M("")->commit();
                            output_data(array('msg' => '添加成功！'));
                        }else{
                            M("")->rollback();
                            output_error('添加失败！');
                        }
                    } else {
                        M("")->rollback();
                        output_error('添加失败！');
                    }
                }else{
                    M("")->rollback();
                    output_error('添加失败！');
                }
            }
        }
    }

    // 编辑用金企业
    public function edit(){
        if(empty(I('post.'))){
            $id = I('get.id');
            $condition = array(
                'company_id'=> $id,
                'deleted'=> 0
            );
            $company_info = D(self::BCOMPANY)->getCompanyDetail($condition);

            $where = array('id'=> $company_info['company_uid']);
            $user_login = D(self::MUSERS)->getInfo($where, 'id,user_login,mobile');

            $this->assign('user_login', $user_login);
            $this->assign('company_info', $company_info);

            $this->display();
        }else{

            $update = I('post.data');
            $condition = array('company_id'=>I('post.company_id'));
            $company_info = D(self::BCOMPANY)->getCompanyDetail($condition);

            // 更改登录账户才用到
            // $where = array(
            //     'mobile|user_login'=> '2'.$update['user_setting']['user_login'],
            //     'user_type'=> 4,
            //     'id'=> array('neq', $company_info['company_uid'])
            // );
            // $i = D(self::MUSERS)->getInfo($where);
            // if(!empty($i)){
            //     // 已存在相同登录账户
            //     output_error('该账户不可用！');
            // }

            $where = array('id'=> $company_info['company_uid']);
            // $user_login = '2'.$update['user_setting']['user_login'];
            // $update_data = array(
            //     'mobile'=> $user_login,
            //     'user_login'=> $user_login
            // );
            if($update['user_setting']['user_psw'] != ''){
                $user_psw = $update['user_setting']['user_psw'];
                $update_data['user_pass'] = sp_password($user_psw);

                $rs = D(self::MUSERS)->update($where, $update_data);
            }else{
                $rs = true;
            }

            if($company_info['company_status'] != 1){
                $update["company_status"] = 0;
            }
            
            unset($update['user_setting']);

            $result = D(self::BCOMPANY)->update($condition, $update);

            if($result !== false && $rs !== false){
                output_data(array('msg'=>'编辑成功！'));
            }else{
                output_error('编辑失败！');
            }
        }
    }

    // 用金企业详情
    public function detail(){
        $id = I('get.id');
        $condition=array(
            'company_id'=> $id,
            'deleted'=> 0
        );
        $company_info = D(self::BCOMPANY)->getCompanyDetail($condition);
        $status = array("未审核","审核通过","审核不通过","锁定");

        $this->assign("status", $status);
        $this->assign('company_info',$company_info);

        $this->display();
    }

    // 删除
    public function deleted(){
        $id=I('get.id');
        $condition=array('company_id'=> $id);
        $update=array('deleted'=>1);
        M("")->startTrans();
        $result = D(self::BCOMPANY)->update($condition,$update);
        if($result){
            $condition=array('company_id'=>$id);
            $update=array('user_status'=>0);
            $res=D(self::MUSERS)->update($condition,$update);
        }
        if($res!==false){
            M("")->commit();
            output_data('操作成功！');
        }else{
            M("")->rollback();
            output_error('操作失败！');
        }
    }

    // 锁定
    public function lock(){
        $id=I('get.id');
        $condition=array('company_id'=>$id);
        $update=array('company_status'=>3);
        $result=D(self::BCOMPANY)->update($condition,$update);
        if($result!==false){
            output_data('操作成功！');
        }else{
            output_error('操作失败！');
        }
    }

    // 解锁
    public function unlock(){
        $id=I('get.id');
        $condition=array('company_id'=>$id);
        $update=array('company_status'=>1);
        $result=D(self::BCOMPANY)->update($condition,$update);
        if($result!==false){
            output_data('操作成功！');
        }else{
            output_error('操作失败！');
        }
    }

    // keyup 获取 最大company_code，返回 company_code+1
    public function getCompanyCode(){
        $pre_code = trim(I('pre_code/s'));
        
        $where = array(
            'company_code'=> array('like', $pre_code.'%')
        );

        $tmp = D(self::BCOMPANY)->where($where)->field('MAX(company_code) as max_company_code')->find();

        $max_code = intval($tmp['max_company_code']);

        if($max_code == 0){
            $count = 0;
        }else{
            $count = intval(substr($tmp['max_company_code'], -4));
        }

        $append_code = sprintf('%04d', $count + 1);

        $rs = array(
            'company_code'=> $pre_code . $append_code,
            'append_code'=> $append_code
        );

        $result['status'] = 1;
        $result['info'] = '';
        $result['data'] = $rs;

        $this->ajaxReturn($result);
    }

    public function check_list(){
        $search_name = trim(I('post.search_name'));
        $condition = array(
            'gb_b_company.deleted'=>0,
            'gb_b_company.company_status'=>0
        );

        if($search_name){
            $condition['gb_b_company.company_name|u.user_nicename|gb_b_company.company_code|gb_b_company.company_legal_person|u.user_login|u3.user_nicename|u3.user_login']=array('like', "%" . $search_name . "%");
        }

        $field = 'gb_b_company.*,u.user_nicename as name1,u2.user_nicename as name2,u3.user_nicename as name3';
        $order = 'company_id desc';
        $join = ' left join gb_m_users as u on u.id=gb_b_company.company_uid ';
        $join .= " left join gb_m_users as u2 on u2.id=gb_b_company.account_uid ";
        $join .= " left join gb_m_users as u3 on u3.id=gb_b_company.user_id ";

        $count=D(self::BCOMPANY)->countList($condition, $field, $join);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow.','.$page->listRows;

        $company_list = D(self::BCOMPANY)->getList($condition, $field, $limit, $join, $order);
        if(empty($company_list)){
            $company_list = null;
        }

        foreach($company_list as $key => $val){
            $company_list[$key]['user_nicename']=($company_list[$key]['user_nicename']?$company_list[$key]['user_nicename']:($company_list[$key]['user_login']?$company_list[$key]['user_login']:$company_list[$key]['mobile']));
        }
        
        $this->assign('company_list', $company_list);
        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $page->show('Admin'));

        $this->display();
    }

    // 审核，审核提交
    public function check(){

        if(IS_POST){
            $where['company_id'] = I("id");

            $data['company_status'] = I("type");
            $data['check_remark'] = I("check_remark");
            $data['check_id'] = get_current_system_id();
            $data['check_time'] = time();
            $check_company=D(self::BCOMPANY)->getInfo($where);
            if(empty($check_company)||$check_company['company_status']!=0){
                $result['status'] = 0;
                $result['info'] = '非待审核状态';
                $this->ajaxReturn($result);
            }
            $r = D(self::BCOMPANY)->update($where, $data);
            if($r !== false) {
                $result['status'] = 1;
                $result['info'] = '操作成功';
            }else{
                $result['status'] = 0;
                $result['info'] = '操作失败';
            }
             $this->ajaxReturn($result);
        }else{
            $id = I('get.id');
            $condition = array(
                'company_id'=> $id,
                'deleted'=> 0
            );
            $company_info = D(self::BCOMPANY)->getCompanyDetail($condition);
            $status = array("未审核", "审核通过", "审核不通过", "锁定");

            $this->assign("status", $status);
            $this->assign('company_info',$company_info);

            $this->display();
        }
    }

    // 更改密码，需要原密码
    public function set_pwd(){
        if(IS_POST){
            $user_id = I("user_id");
            $old_pwd = I("old_pwd");
            $new_pwd = I("new_pwd");

           // $info = D(self::MUSERS)->getInfo(array("id"=>$user_id));

           // if(sp_compare_password($old_pwd, $info['user_pass'])){
                $data['user_pass'] = sp_password($new_pwd);
                $r = D(self::MUSERS)->update(array("id"=>$user_id), $data);
                if($r !== false) {
                    $result['status'] = 1;
                    $result['url'] = U("Company/index");
                    $result['info'] = '操作成功';
                }else{
                    $result['status'] = 0;
                    $result['info'] = '操作失败';
                }
            /*}else{
                $result['status'] = 0;
                $result['info'] = '旧密码输入错误';
            }*/
            $this->ajaxReturn($result);
        }else{
            $id = I('id/d', 0);
            $this->assign('user_id',$id);
            $this->display();
        }
    }

    public function set_role() {
        $Ba = D(self::BAUTHACCESS);
        if(IS_POST){
            $id=trim(I("id"));
            //获取该角色下的所有菜单权限
            $old_rule_name=$Ba->getList(array("role_id"=>$id),'rule_name');
            $old_rule_name=array_column($old_rule_name, 'rule_name');
            if (is_array($_POST['menuid']) && count($_POST['menuid'])>0) {
                $menu_model=M("BMenu");
                //$Ba->where(array("role_id"=>$id))->delete();
                $new_authaccess=array();
                foreach ($_POST['menuid'] as $menuid) {
                    $menu=$menu_model->where(array("id"=>$menuid))->field("app,model,action")->find();
                    $app=$menu['app'];
                    $model=$menu['model'];
                    $action=$menu['action'];
                    $name=strtolower("$app/$model/$action");
                    //获取新的菜单权限
                    array_push($new_authaccess,$name);
                    //修改的权限时，不存在的才添加
                    if($menu&&!in_array($name,$old_rule_name)){
                        $Ba->add(array("role_id"=>$id,"rule_name"=>$name,'type'=>'business_url'));
                    }
                }
                //获取旧的菜单权限存在，但新的菜单权限不存在的菜单权限，并进行删除
                $del_authaccess=array_diff($old_rule_name,$new_authaccess);
                $del_authaccess=implode(',',$del_authaccess);
                $Ba->where(array("role_id"=>$id,'rule_name'=>array('in',$del_authaccess)))->delete();
                $this->success("权限设置成功！", U('Company/index'));
            } else{
                $this->error("权限设置失败！");
            }
        }else{
            $id = I("get.id",0,'intval');
            $where['company_uid']=$id;
            $info=D(self::BCOMPANY)->getInfo($where);
            $data = D(self::BROLE)->where(array("name" => $info['company_name'],'company_id'=>$info['company_id']))->find();
            $role_id=$data['id'];
            if (!$data) {
                $data=array();
                $data['name']=$info['company_name'];
                $data['remark']="(商户)".$info['company_name']."专用";
                $data['status']=1;
                $data['shop_id']=-1;
                $data['create_time']=time();
                $data['company_id']=$info['company_id'];
                $role_id=D(self::BROLE)->insert($data);
            }
            //判断是否给商户增加角色，如果没有怎增加
            $user_role_data=array("role_id"=>$role_id,'user_id'=>$id,'company_id'=>$info['company_id']);
            $result_id=D(self::BROLEUSER)->where($user_role_data)->find();
            if(empty($result_id)){
                $user_role_id=D(self::BROLEUSER)->insert($user_role_data);
            }
            $menus=D(self::BMENU)->get_menu_tree();
            $this->assign("menus",$menus);
            $list=$Ba->getList(array("role_id"=>$role_id));
            $m_list=D(self::BMENU)->getList();
            $h_menus=array();
            if($list){
                foreach ($m_list as $k=>$v){
                    $app=$v['app'];
                    $model=$v['model'];
                    $action=$v['action'];
                    $name=strtolower("$app/$model/$action");
                    foreach ($list as $key=>$val){
                        if($name==$val['rule_name']){
                            array_push($h_menus,$v['id']);
                        }
                    }
                }
            }
            $info['role_id']=$role_id;
            $this->assign("h_menus",$h_menus);
            $this->assign("data", $info);
            $this->display();
        }
    }
    //门店权限设置
    public function set_shop_role() {
        $Ba = D(self::BAUTHACCESS);
        if(IS_POST){
            $id=trim(I("id"));
            //获取该角色下的所有菜单权限
            $old_rule_name=$Ba->getList(array("role_id"=>$id),'rule_name');
            $old_rule_name=array_column($old_rule_name, 'rule_name');
            if (is_array($_POST['menuid']) && count($_POST['menuid'])>0) {
                $menu_model=M("BMenu");
                $new_authaccess=array();
                //$Ba->where(array("role_id"=>$id,'type'=>'admin_url'))->delete();
                foreach ($_POST['menuid'] as $menuid) {
                    $menu=$menu_model->where(array("id"=>$menuid))->field("app,model,action")->find();
                    if($menu){
                        $app=$menu['app'];
                        $model=$menu['model'];
                        $action=$menu['action'];
                        $name=strtolower("$app/$model/$action");
                        //$Ba->add(array("role_id"=>$id,"rule_name"=>$name,'type'=>'admin_url'));
                        //获取新的菜单权限
                        array_push($new_authaccess,$name);
                        //修改的权限时，不存在的才添加
                        if($menu&&!in_array($name,$old_rule_name)){
                            $Ba->add(array("role_id"=>$id,"rule_name"=>$name,'type'=>'business_url'));
                        }
                    }
                }
                //获取旧的菜单权限存在，但新的菜单权限不存在的菜单权限，并进行删除
                $del_authaccess=array_diff($old_rule_name,$new_authaccess);
                $del_authaccess=implode(',',$del_authaccess);
                $Ba->where(array("role_id"=>$id,'rule_name'=>array('in',$del_authaccess)))->delete();
                M("")->commit();
                $this->success("权限设置成功！", U('Company/index'));
            } else{
                M("")->rollback();
                $this->error("权限设置失败！");
            }
        }else{
            $id = I("get.id",0,'intval');
            $where['company_uid']=$id;
            $info=D(self::BCOMPANY)->getInfo($where);
            if(empty($info)){
                $this->error("无法获取商户信息");
            }
            $data = D(self::BROLE)->where(array("name" => $info['company_name']."店长",'company_id'=>$info['company_id']))->find();
            $role_id=$data['id'];
            if (!$data) {
                $data=array();
                $data['name']=$info['company_name']."店长";
                $data['remark']="(商户)".$info['company_name']."门店专用";
                $data['status']=1;
                $data['shop_id']=-1;
                $data['type']=1;
                $data['create_time']=time();
                $data['company_id']=$info['company_id'];
                $role_id=D(self::BROLE)->insert($data);
            }
            //判断是否给商户增加角色，如果没有怎增加
            $user_role_data=array("role_id"=>$role_id,'user_id'=>$id,'company_id'=>$info['company_id']);
            $result_id=D(self::BROLEUSER)->where($user_role_data)->find();
            if(empty($result_id)){
                $user_role_id=D(self::BROLEUSER)->insert($user_role_data);
            }
            $menus=D(self::BMENU)->get_menu_tree(0,1);
            $this->assign("menus",$menus);
            
            $list=$Ba->getList(array("role_id"=>$role_id));
            $m_list=D(self::BMENU)->getList();
            $h_menus=array();
            if($list){
                foreach ($m_list as $k=>$v){
                    $app=$v['app'];
                    $model=$v['model'];
                    $action=$v['action'];
                    $name=strtolower("$app/$model/$action");
                    foreach ($list as $key=>$val){
                        if($name==$val['rule_name']){
                            array_push($h_menus,$v['id']);
                        }
                    }
                }
            }
            $info['role_id']=$role_id;
            $this->assign("h_menus",$h_menus);
            $this->assign("data", $info);
            $this->display();
        }
    }
}