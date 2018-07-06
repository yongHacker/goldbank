<?php
/**
 * 客户模型
 * @date: 2018年3月20日 下午4:12:43
 * @author: Alam
 * @return:
 */
namespace Api\Model;

use Api\Model\ApiCommonModel;

class BClientModel extends ApiCommonModel
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取客户列表
     * 
     * @return array $result
     */
    public function getClients($count = false, $field = '', $limit = null)
    {
        $search = I('post.search', '', 'string');
        $creator_id = I('post.creator_id', 0, 'intval');
        $start_time = I('post.start_time', 0, 'intval');
        $end_time = I('post.end_time', 0, 'intval');
        
        $field = empty($field) ? 'bc.*' : $field;
        $clients = array();
        $condition = array(
            'bc.deleted' => 0,
            'bc.company_id' => get_company_id()/* ,
            'bc.shop_id' => get_shop_id() */
        );
        if ($search) {
            $condition['mu.mobile|mu.user_nicename'] = $search; 
        }
        if ($creator_id) {
            $condition['bc.creator_id'] = $creator_id; 
        }
        if ($start_time) {
            $condition['bc.create_time'] = array('gt', $start_time); 
        }
        if ($end_time) {
            $condition['bc.create_time'] = array('lt', $end_time); 
        }
        $join = 'LEFT JOIN __M_USERS__ mu ON mu.id = bc.user_id AND mu.user_status = 1 ';
        $join .= 'LEFT JOIN __B_EMPLOYEE__ be ON be.id = bc.employee_id ';
        $join .= 'LEFT JOIN __M_USERS__ mue ON mue.id = be.user_id ';
        $order = 'bc.create_time DESC';
        if ($count) {
            $result = $this->alias('bc')->countList($condition, $field, $join);
        } else {
            $result = $this->alias('bc')->getList($condition, $field, $limit, $join, $order);
        }
        return $result;
    }
    
    /**
     * 客户查询筛选条件 - 销售员
     */
    public function getSellers()
    {
        $condition = array(
            'bc.deleted' => 0,
            'bc.employee_id' => array('neq', '')
        );
        $field = 'DISTINCT bc.employee_id, mue.user_nicename as name';
        $join = 'LEFT JOIN __B_EMPLOYEE__ be ON be.id = bc.employee_id AND be.deleted = 0 AND be.status = 1 ';
        $join .= 'LEFT JOIN __M_USERS__ mue ON be.user_id = mue.id';
        $order = 'be.id';
        $sellers = $this->alias('bc')->getList($condition, $field, $limit = null, $join, $order);

        return $sellers;
    }
    
    /**
     * 检查客户存在与否
     */
    public function exist($mobile = '')
    {
        if (empty($mobile))
            return false;
        $condition = array(
            'mu.mobile' => $mobile,
            // 'bc.shop_id' => get_shop_id(),
            'bc.company_id' => get_company_id()
        );
        $join = 'LEFT JOIN __M_USERS__ AS MU ON bc.user_id = mu.id AND bc.deleted = 0';
        $result = $this->alias('bc')->countList($condition, '', $join);
        return $result;
    }
    
    /**
     * 编辑客户
     * @return int -1-已经存在 0-添加失败 1-添加成功
     */
    public function add()
    {
        $model_m_users = D('MUsers');
        $mobile = I('post.mobile');
        
        // 检查用户存在与否
        $condition = array('mobile' => $mobile);
        $userinfo = $model_m_users->getInfo($condition, $field = 'id');
        
        M()->startTrans();
        if (empty($userinfo)) {
            if ($model_m_users->create() === false) {
            echo $model_m_users->getError();
            M()->rollback();
            die;
                return 0;
            }
            $data = array();
            $data['company_id'] = get_company_id();
            $data['user_login'] = $mobile;
            $data['mobile'] = $mobile;
            $data['user_pass'] = 123456;
            $data['user_nicename'] = I('post.user_nicename/s', '');
            $data['create_time'] = time();
            $data['sex'] = I('post.sex/d', 0);
            $data['operate_user_id'] = get_user_id();
            $data['operate_ip'] = get_client_ip(0, true);
            $result = $model_m_users->insert($data);
            if ($result === false) {
                return 0;
            }
            $user_id = $result;
        } else {
            $user_id = $userinfo['id'];
        }
        // 检查客户是否存在
        if (!$this->exist($mobile)) {
            return -1;
        }
        
        if ($this->create() === false) {
            echo $this->getError();
            M()->rollback();
            die;
            return 0;
        }
        $bclient_data = array();
        $bclient_data['company_id'] = $this->MUser['company_id'];
        $bclient_data['shop_id'] = $this->MUser['shop_id'];
        $bclient_data['employee_id'] = I('post.employee_id/d', 0);
        $bclient_data['creator_id'] = $this->MUser['id'];
        $bclient_data['create_time'] = time();
        $bclient_data['deleted'] = 0;
        $bclient_data['user_id'] = $user_id;
        $bclient = $this->insert($bclient_data);
        if ($bclient === false) {
            M()->rollback();
            return 0;
        }
        M()->commit();
        return 1;
    }
}