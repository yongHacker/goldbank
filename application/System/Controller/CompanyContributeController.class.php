<?php
namespace System\Controller;

use Common\Controller\SystembaseController;

class CompanyContributeController extends SystembaseController{
    const MUSERS='m_users',BCOMPANYCONTRIBUTE='b_company_contribute',BCOMPANY='b_company';
    public function __construct() {
        parent::__construct();
        $this->show_status('b_company_contribute');
    }
    //企业授权列表
    public function index(){
        // 0未审核 1审核通过 2审核不通过 3已撤销
        $status_list = array(
            '0'=> '未审核',
            '1'=> '审核通过',
            '2'=> '审核不通过',
            '3'=> '已撤销'
        );
        $this->assign('status_list', $status_list);

        $search_name = trim(I('search_name/s'));
        $condition = array(
            'gb_b_company_contribute.deleted'=>0
        );
        
        $search_status = I('search_status/d', -1);
        if($search_status >= 0){
            $condition['gb_b_company_contribute.status'] = $search_status;
        }

        $search_date = I('search_date/s');
        if($search_date){
            $search_date = strtotime($search_date);
            if($search_date){
                $t1 = $search_date;
                $t2 = $search_date + 24*60*60;
                $condition['gb_b_company_contribute.create_time'] = array('BETWEEN', array($t1, $t2));
            }
        }

        if($search_name){
            $condition['bc.company_name|gb_b_company_contribute.contract_sn|gb_b_company_contribute.contribute_no'] = array('like', "%" . trim($search_name) . "%");
        }
        $this->assign('search_name', $search_name);
        $this->assign('search_status', $search_status);
        $this->assign('search_date', date('Y-m-d', $search_date));

        $field='gb_b_company_contribute.*,bc.company_name,mu.user_nicename';
        $order='gb_b_company_contribute.create_time desc';
        $join="join gb_m_users as mu on mu.id = gb_b_company_contribute.creator_id";
        $join.=" join gb_b_company as bc on bc.company_id = gb_b_company_contribute.company_id";
        $count=D(self::BCOMPANYCONTRIBUTE)->countList($condition,$field,$join);
        $page=$this->page($count,$this->pagenum);
        $show= $page->show('Admin');
        $company_list=D(self::BCOMPANYCONTRIBUTE)->getList($condition,$field,$page->firstRow.','.$page->listRows,$join,$order);
        $this->assign('company_list',$company_list);
        $this->assign('page',$show);
        $this->assign("numpage",$this->pagenum);
        $this->display();
    }

    // 授权编号
    public function getContributeNo($company_id = 0){
        // $company_id = I('company_id/d', 0);
        $order_num = 0;
        if($company_id){
            $field = 'contribute_no';

            $company_info = D(self::BCOMPANY)->field('company_code')->find($company_id);

            $day = substr(date('Ymd', time()), 2);

            $table = D(self::BCOMPANYCONTRIBUTE);
            $condition = array();
            $condition[$field] = array(
                'like',
                '%' . $company_info['company_code'] . '_' . $day . '%'
            );

            $info = $table->where($condition)->field('MAX('.$field.')as max_number')->find();
            if($info['max_number'] == ''){
                $count = $table->where($condition)->count();
            }else{
                $count = intval(substr($info['max_number'], -3));
            }

            $count = sprintf("%03d", $count + 1);

            $order_num = $company_info['company_code'] .'_'. $day . $count;
        }
        
        return $order_num;
        // die($order_num);
    }

    //增加商户收取
    public function add(){
        $user_id = get_current_system_id();
        if(empty(I('post.'))){
            $info = D(self::MUSERS)->getInfo("id=".$user_id);
            $time = date("Y-m-d");

            $where['company_type'] = 0;
            $where['deleted'] = 0;
            $where['company_status'] = array("in","1,3");

            $companys = D(self::BCOMPANY)->getlist($where);

            $this->assign("companys", $companys);
            $this->assign("user_nicename", $info['user_nicename']);
            $this->assign("time", $time);

            $this->display();
        }else{
            $data = I('post.');
            $contract_sn = $data['contract_sn'];
            $price = $data["price"];
            $service_year = $data["service_year"]?$data["service_year"]:0;
            $company_num = $data["company_num"]?$data["company_num"]:0;
            $shop_num = $data["shop_num"]?$data["shop_num"]:0;
            $memo = $data["memo"];
            $company_id = $data["company_id"];
            if(empty($company_id)){
                output_error('信息错误！');
            }
            if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/',$price)){
                output_error('缴纳金额输入有误！');
            }
            if($service_year>0 && !is_numeric($service_year)){
                output_error('增加期限输入有误！');
            }
            if($company_num>0 && !is_numeric($company_num)){
                output_error('增加加盟商输入有误！');
            }
            if($shop_num>0 && !is_numeric($shop_num)){
                output_error('增加店铺数输入有误！');
            }
            if(empty($contract_sn)){
                output_error('请填写合同编号！');
            }

            $contribute_no = $this->getContributeNo($company_id);
            // $contribute_no = $data['contribute_no'];
            if(empty($contribute_no)){
                output_error('缺少授权单号！');
            }
            $where = array('contribute_no'=> $contribute_no);
            $rs = D(self::BCOMPANYCONTRIBUTE)->getInfo($where);
            if(!empty($rs)){
                output_error('授权单号已被使用！');
            }

            $data1 = array(
                'contribute_no'=> $contribute_no,
                'contract_sn'=> $contract_sn,
                "price"=> $price,
                "service_year"=> $service_year,
                "company_num"=> $company_num,
                "shop_num"=> $shop_num,
                "memo"=> $memo,
                "company_id"=> $company_id,
                "create_time"=> time(),
                "creator_id"=> $user_id
            );
            $re = D(self::BCOMPANYCONTRIBUTE)->insert($data1);
            if($re){
                output_data('商户授权申请单添加成功！');
            }else{
                output_error('商户授权申请单添加失败！');
            }
        }
    }

    //查看
    public function detail(){
        if(I('type')!= "show"){
            $id=I('post.id');
            if(empty($id)){
                output_error('信息错误！');
            }
            $condition = array('id' => $id,'status'=>0);
            $info = D(self::BCOMPANYCONTRIBUTE)->getInfo($condition);
            if (empty($info)) {
                output_error('信息不存在！');
            }
            $data['status']=3;
            $res=D(self::BCOMPANYCONTRIBUTE)->update($condition,$data);
            if($res !== false){
                $data['status']=1;
                $data['msg']="撤销成功！";
                $data['url']=U("CompanyContribute/index");
                $this->ajaxReturn($data);
            }else{
                $data['status']=1;
                $data['msg']="撤销失败！";
                $this->ajaxReturn($data);
            }
        }else{
            $id=I('get.id');
            $condition=array('gb_b_company_contribute.id'=>$id);
            $field='gb_b_company_contribute.*,bc.company_name,mu.user_nicename';
            $join="join gb_m_users as mu on mu.id = gb_b_company_contribute.creator_id";
            $join.=" join gb_b_company as bc on bc.company_id = gb_b_company_contribute.company_id";
            $info=D(self::BCOMPANYCONTRIBUTE)->getInfo($condition,$field,$join);
            $this->assign('info',$info);
            $this->display();
        }
    }

    public function check_list(){

        $search_name = trim(I('search_name/s'));
        $condition = array(
            'gb_b_company_contribute.deleted'=>0
        );

        $search_date = I('search_date/s');
        if($search_date){
            $search_date = strtotime($search_date);
            if($search_date){
                $t1 = $search_date;
                $t2 = $search_date + 24*60*60;
                $condition['gb_b_company_contribute.create_time'] = array('BETWEEN', array($t1, $t2));
            }
        }

        if($search_name){
            $condition['bc.company_name|gb_b_company_contribute.contract_sn|gb_b_company_contribute.contribute_no'] = array('like', "%" . trim($search_name) . "%");
        }
        $this->assign('search_name', $search_name);
        $this->assign('search_date', date('Y-m-d', $search_date));

        $field = 'gb_b_company_contribute.*,bc.company_name,mu.user_nicename';
        $order = 'gb_b_company_contribute.create_time desc';

        $join = "join gb_m_users as mu on mu.id = gb_b_company_contribute.creator_id";
        $join .= " join gb_b_company as bc on bc.company_id = gb_b_company_contribute.company_id";

        $count = D(self::BCOMPANYCONTRIBUTE)->countList($condition,$field,$join);
        $page = $this->page($count,$this->pagenum);
        $show = $page->show('Admin');
        $company_list = D(self::BCOMPANYCONTRIBUTE)->getList($condition,$field,$page->firstRow.','.$page->listRows,$join,$order);

        $this->assign('company_list',$company_list);
        $this->assign('page',$show);
        $this->assign("numpage",$this->pagenum);
        $this->display();
    }

    public function check() {
        if (I('type') != "show") {
            $id = I('post.id/d', 0);
            $status = I("status");
            $check_memo = I("check_memo");
            if (empty($id)) {
                output_error('信息错误！');
            }

            $condition = array('id' => $id,'status'=>0);
            $info = D(self::BCOMPANYCONTRIBUTE)->getInfo($condition);
            if (empty($info)) {
                output_error('信息不存在！');
            }
            $da['status'] = $status;
            $da['check_id'] = get_current_system_id();
            $da['check_time'] = time();
            $da['check_memo'] = $check_memo;
            $res = D(self::BCOMPANYCONTRIBUTE)->update($condition, $da);
            if ($res !== false) {
                if($status == 1){
                    $c_info=D(self::BCOMPANY)->getInfo(array("company_id"=>$info['company_id']));
                    $c_info['end_time']=empty($c_info['end_time'])?time():$c_info['end_time'];//判断是否为空，为空则授权开始时间为当前时间
                    $c_info['end_time']=$c_info['end_time']<time()?time():$c_info['end_time'];//判断是否过期，过期则授权开始时间为当前时间
                    $d['company_num'] = $c_info['company_num'] + $info['company_num'];
                    $d['service_year'] = $c_info['service_year'] + $info['service_year'];
                    $d['shop_num'] = $c_info['shop_num'] + $info['shop_num'];
                    $d['end_time'] = strtotime("+".$info['service_year']." year", $c_info['end_time']);
                    $aa = D(self::BCOMPANY)->update(array("company_id"=>$info['company_id']), $d);
                    if($aa !== false){
                        $data['status'] = 1;
                        $data['msg'] = "审核成功！";
                        $data['url'] = U("CompanyContribute/check_list");
                        $this->ajaxReturn($data);
                    }else{
                        $data['status'] = 0;
                        $data['msg'] = "审核失败！商户信息更新失败";
                        $this->ajaxReturn($data);
                    }
                }else{
                    $data['status'] = 1;
                    $data['msg'] = "审核成功！";
                    $data['url'] = U("CompanyContribute/check_list");
                    $this->ajaxReturn($data);
                }
            } else {
                $data['status'] = 0;
                $data['msg'] = "审核失败！授权失败";
                $this->ajaxReturn($data);
            }
        } else {
            $id = I('get.id');
            $condition = array('gb_b_company_contribute.id' => $id);
            $field = 'gb_b_company_contribute.*,bc.company_name,mu.user_nicename';
            $join = "join gb_m_users as mu on mu.id = gb_b_company_contribute.creator_id";
            $join .= " join gb_b_company as bc on bc.company_id = gb_b_company_contribute.company_id";
            $info = D(self::BCOMPANYCONTRIBUTE)->getInfo($condition, $field, $join);
            $this->assign('info', $info);
            $this->display();
        }
    }
}