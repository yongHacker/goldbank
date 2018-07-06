<?php
namespace Shop\Controller;

use Shop\Controller\ShopbaseController;

class BClientController extends ShopbaseController {
    private $bclient_model, $bshop_model, $muser_model, $bemployee_model;
    public function __construct() {
        parent::__construct();
        $this->bclient_model=D("BClient");
        $this->bshop_model=D("BShop");
        $this->muser_model = D('MUsers');
        $this->bemployee_model = D('BEmployee');
    }
    public function _getList(){
        $getdata=I("");
        $condition=array("bclient.deleted"=>0,'bclient.company_id'=>$this->MUser["company_id"]);
        if ($getdata['search']) {
            $condition["bclient.client_moblie|bclient.client_name"] = array("like", "%" . trim($getdata['search']) . "%");
        }

        if(I('begin_time')){
            $begin_time = I('begin_time') ? strtotime(I('begin_time')) : time();
            $condition['bclient.create_time'] = array('gt', $begin_time);
        }

        if(I('end_time')){
            $end_time = I('end_time') ? strtotime(I('end_time')) : time();
            if(isset($begin_time)){
                $p1 = $condition['bclient.create_time'];
                unset($condition['bclient.create_time']);
                $condition['bclient.create_time'] = array($p1, array('lt', $end_time));
            }else{
                $condition['bclient.create_time'] = array('lt', $end_time);
            }
        }
        $field='bclient.*,bshop.shop_name,musers.id_no,musers.user_status,bclient.client_moblie mobile,bclient.client_name user_nicename';
        $join="left join ".DB_PRE."m_users musers on bclient.user_id=musers.id";
        $join.=" left join ".DB_PRE."b_shop bshop on bshop.id=bclient.shop_id";
        $count = $this->bclient_model->alias("bclient")->countList($condition,$field,$join,$order='bclient.create_time desc',$group='');
        $page = $this->page($count, $this->pagenum);
        $limit=$page->firstRow.",".$page->listRows;
        $data = $this->bclient_model->alias("bclient")->getList($condition,$field,$limit,$join,$order='bclient.create_time desc',$group='');
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$data);
    }

    // 获取可开单用户列表
    public function _getClient(){
        $getdata = I('');
        $condition = array(
            'bc.company_id' => get_company_id(),
        );
        $condition_em = array(
            'be.deleted' => 0,
            'be.employee_mobile' => array('neq', ''),
            'be.company_id' => get_company_id()
        );
        if ($getdata['search']) {
            if (is_numeric($getdata['search'])) {
                $condition['bc.client_name|bc.client_moblie'] = array('like', '%' . $getdata['search'] . '%');
                $condition_em['be.employee_name|be.employee_mobile'] = array('like', '%' . $getdata['search'] . '%');
            } else {
                $condition["bc.client_name"] = array('like', '%' . $getdata['search'] . '%');
                $condition_em['be.employee_name'] = array('like', '%' . $getdata['search'] . '%');
            }
        }

        // 员工用户id
        $field = 'user_id';
        $employee = $this->bemployee_model->alias('be')->getList($condition_em, $field);
        foreach ($employee as $key => $value) {
            $employee_user_id[] = $value['user_id'];
        }
        if (!empty($employee_user_id)) {
            $condition = array(
                $condition,
                'mu.id' => array('in', $employee_user_id),
                '_logic' => 'or'
            );
        }

        $field = 'DISTINCT bc.id, bc.company_id, bc.shop_id, bc.client_name, bc.client_moblie, bc.client_idno, mu.id AS user_id, mu.id_no, mu.user_status, mu.sex, IFNULL( bc.client_moblie, be.employee_mobile) AS mobile, IFNULL(bc.client_name, be.employee_name) AS user_nicename, be.id as employee_id';
        $join = 'left join __B_CLIENT__ bc ON bc.user_id = mu.id AND bc.deleted = 0 AND bc.company_id = ' . get_company_id();
        $join .= ' left join __B_EMPLOYEE__ be ON be.user_id = mu.id AND be.deleted = 0 AND be.company_id = ' . get_company_id();
        $order = 'bc.create_time desc, mu.id desc';
        $count = $this->muser_model->alias('mu')->countList($condition, $field, $join, $order);
        $page = $this->page($count, $this->pagenum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $this->muser_model->alias('mu')->getList($condition, $field, $limit, $join, $order);
        // echo $this->muser_model->getlastsql();die;
        $this->assign('page', $page->show('Admin'));
        $this->assign('list', $data);
    }
    // 根据员工id添加客户
    public function _addClientByEmployee($employee_id = 0, &$client_id){
        $client_id = $this->bclient_model->addByEmployee($employee_id);
        if ($client_id === false) {
            $info['status'] = 0;
            $info['msg'] = '当前选择客户为内部员工，使用员工信息创建客户失败！';
            $this->ajaxReturn($info);
        }
    }
    public function index() {
        $shop_list=$this->bshop_model->getShopListForSelect(array('company_id'=>get_company_id(),'deleted'=>0));
        $this->assign('shop_list',$shop_list);
        $this->_getList();
        $this->display();
    }
    public function add() {
        if(IS_POST){
            $status=$this->bclient_model->add_post();
            if ($status['status'] == 1) {
                $this->success($status['msg'], U("BClient/index"));
            } else {
                $this->error($status['msg']);
            }
        }else{
            $condition=array("deleted"=>0,"company_id"=>$this->MUser["company_id"]);
            if(get_shop_id()){
                $condition["id"]=get_shop_id();
                $this->assign('shop_id', get_shop_id());
            }
            $shopdata = D("BShop")->getList($condition,$field='*',$limit=null,$join='',$order='',$group='');
            $this->assign('shopdata', $shopdata);
            $add_tpl=$this->fetch('add_tpl');
            $this->assign('add_tpl', $add_tpl);
            $this->display();
        }

    }
    //删除
    public function delete() {
        $postdata = I("");
        $data = array();
        $data["deleted"] = 1;
        $condition = array("id" => $postdata["id"], "company_id" => $this->MUser["company_id"]);
        $BSectors = $this->bclient_model->update($condition, $data);
        if ($BSectors !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }
    // 编辑
    public function edit(){
        $postdata=I("post.");
        if(empty($postdata)){
            $id = I('get.id',0,'intval');
            $bclient = $this->bclient_model->getInfo(array('id'=>$id,'company_id'=>get_company_id()));
            $this->assign('data', $bclient);
            $this->display();
        }else{
            if (IS_POST) {

                $id=I('post.id',0,'intval');
                $client_name = I('post.client_name/s');
                M()->startTrans();
                $condition=array("id"=>$id,'company_id'=>get_company_id());
                $data=array("client_name"=>$postdata["client_name"],"sex"=>$postdata["sex"]);
                $result=$this->bclient_model->update($condition,$data);
                if ($result!==false) {
                    M()->commit();
                    //指派角色和门店
                    $this->success("保存成功！", U("BClient/index"));
                } else {
                    M()->rollback();
                    $this->error("保存失败！");
                }
            }else{
                $this->error("添加失败！");
            }
        }

    }
}