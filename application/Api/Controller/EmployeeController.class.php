<?php
/**
 * 行政
 * 
 * @author Alam
 */
namespace Api\Controller;
defined('JHJAPI') or exit('Access Invalid!');

use Api\Controller\BaseController;

class EmployeeController extends BaseController
{
    protected $model_b_role;

    protected $password = 123456;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->api_init();
    }
    
    public function _initialize()
    {
        parent::_initialize();
        
        $this->model_b_role = D('BRole');
    }
    
    /**
     * 门店员工资料列表
     */
    public function index()
    {
        $search_content = I('post.search_content');
        
        if (!empty($search_content)) {
            $condition['be.sector_id|be.job_id|be.employee_name|mu.mobile|mu.realname|mu.user_nicename'] = array(
                'like',
                '%' . $search_content . '%'
            );
        }
        $condition['be.company_id'] = $this->role_path['company_id'];
        $condition['bse.shop_id'] = $this->role_path['shop_id'];
        $condition['be.deleted'] = 0;
        $condition['mu.user_status'] = 1;

        $field = 'be.company_id, bse.shop_id, be.sector_id, /* be.job_id, bse.employee_id, bj.job_name,*/ be.employee_name, be.user_id, be.status, 
            mu.mobile, mu.user_email, mu.avatar, mu.sex';
        $join = 'LEFT JOIN __B_SHOP_EMPLOYEE__ AS bse ON be.id = bse.employee_id ';
//         $join .= 'LEFT JOIN __B_ROLE_USER__ AS bru ON bru.user_id = be.user_id ';
//         $join .= 'LEFT JOIN __B_ROLE__ AS br ON be.company_id = br.company_id AND be.shop_id = br.shop_id AND br.id = bru.role_id AND br.status = 1 ';
//         $join .= 'LEFT JOIN __B_JOBS__ AS bj ON be.job_id = bj.id AND bj.deleted = 0 ';
        $join .= 'LEFT JOIN __M_USERS__ AS mu ON be.user_id = mu.id';
        $order = 'be.status DESC,be.id ASC';
        
        $count = $this->model_b_employee->alias('be')->countList($condition, $field, $join, $order);
        $page = $this->getPage($count);
        $list = $this->model_b_employee->alias('be')->getList($condition, $field, $page['limit'], $join, $order);

        foreach ($list as $key => $value) {
            $list[$key]['signature'] = strip_tags(htmlspecialchars_decode($value['signature']));
        }
        
        $this->encrypt_exit(0, '', array_merge(array('list' => $list), $page));
    }

    /**
     * 添加员工信息
     */
    public function add_post()
    {
        $param['mobile'] = I('post.mobile');
        $param['sex'] = I('post.sex', 0);
        $param['employee_name'] = I('post.employee_name');
        $this->_param_check($param);
        
        $condition = array(
            'mobile' => $param['mobile']
        );
        $field = 'id, mobile';
        $user = $this->model_m_users->getInfo($condition, $field);
        
        M()->startTrans();
        if (empty($user)) {
            // 添加用户
            $insert = array();
            $insert['user_pass'] = sp_password($this->password);
            $insert['mobile'] = $param['mobile'];
            $insert['user_nicename'] = $param['sex'];
            $insert['create_time'] = time();
            $insert['user_status'] = 1;
            $insert['user_type'] = 4;
            $insert['sex'] = $param['sex'];
            $insert['operate_user_id'] = $this->encryptid;
            $insert['operate_ip'] = get_client_ip(0, true);
            $insert['device'] = $this->device;
            $user_id = $this->model_m_users->insert($insert);
            if ($user_id === false) {
                M()->rollback();
                $this->encrypt_exit(L('CODE_FAIL'), L('MSG_ADD_ERR'));
            }
        } else {
            $user_id = $user['id'];
        }
        
        $employee = $this->model_b_employee->_check_exist($user_id, $this->role_path['company_id'], $this->role_path['shop_id']);
        // 员工已存在，并有门店对应员工信息
        if (!empty($employee['shop_id'])) {
            $this->encrypt_exit(L('CODE_EMPLOYEE_EXIST'), L('MSG_EMPLOYEE_EXIST'));
        }

        // 员工不存在
        if (empty($employee['id'])) {
            $insert = array(
                'company_id' => $this->role_path['company_id'],
                'shop_id' => $this->role_path['shop_id'],
                'sector_id' => 0,
                'job_id' => 0,
                'employee_name' => $param['sex'],
                'user_id' => $user_id,
                'creator_id' => $this->user['id'],
                'create_time' => time()
            );
            $employee_id = $this->model_b_employee->insert($insert);
            if ($employee_id === false) {
                M()->rollback();
                $this->encrypt_exit(L('CODE_FAIL'), L('MSG_ADD_ERR'));
            }
        } else {
            $employee_id = $employee['id'];
        }
        
        // 没有门店对应员工信息
        if (empty($employee['shop_id'])) {
            $insert = array(
                'shop_id' => $this->role_path['shop_id'],
                'employee_id' => $employee_id,
                'user_id' => $user_id,
            );
            $employee_shop_id = $this->model_b_shop_employee->insert($insert);
            if ($employee_shop_id === false) {
                M()->rollback();
                $this->encrypt_exit(L('CODE_FAIL'), L('MSG_ADD_ERR'));
            }
        } else {
            $employee_shop_id = $employee['shop_id'];
        }
        
        M()->commit();
        $this->encrypt_exit(0, L('MSG_ADD_SUC'));
    }
    
    /**
     * 编辑员工信息
     */
    public function edit_post()
    {
        $param['employee_name'] = I('post.employee_name');
        $param['sex'] = I('post.sex', 0);
        $param['user_id'] = I('post.user_id');
        $param['employee_id'] = I('post.user_id');
        $param['status'] = I('post.status');
        $this->_param_check(param);
        
        // 改USER
        $condition = array(
            'user_id' => $param['user_id']
        );
        $update = array(
            'sex' => $param['sex']
        );
        $user_res = $this->model_m_users->update($condition, $update);
        
        // 改EMPLOYEE
        #TODO 后期根据产品的原型添加相应数据 比如 学历 地址等参数
        $update = array(
            'id' => $param['employee_id'],
            'employee_name' => $param['employee_name'],
            'status' => $param['status']
        );
        $employee_res = $this->model_b_employee->save($update);
        if ($user_res === false || $employee_res === false) {
            $this->encrypt_exit(L('CODE_FILE'), L('MSG_EDIT_ERR'));
        }
        $this->encrypt_exit(0, L('MSG_EDIT_SUC'));
    }

    /**
     * 员工离职
     */
    public function leave_office()
    {
        $param = array(
            'user_id' => I('post.user_id'),
            'employee_id' => I('post.employee_id')
        );
        $this->_param_check($param);
        
        if ($param['user_id'] == $this->user['id']) {
            $this->encrypt_exit(L('CODE_OPERATION_OBJECT'), L('MSG_OPERATION_OBJECT'));
        }
        
        $update = array(
            'id' => $param['employee_id'],
            'status' => 0,
        );
        $result = $this->model_b_employee->save($update);
        if ($result === false) {
            $this->encrypt_exit(L('CODE_FILE'), L('MSG_EDIT_ERR'));
        }
        $this->encrypt_exit(0, L('MSG_EDIT_SUC'));
    }
    
    /**
     * 权限选项
     */
    public function role()
    {
        $condition = array(
            'company_id' => $this->role_path['company_id'],
            'shop_id' => $this->role_path['shop_id'],
            'status' => 1
        );
        $role = $this->model_b_role->getList($condition);
        $this->encrypt_exit(0, '', $role);
    }
    
    /**
     * 权限分配
     */
    public function set_role()
    {
        $param['user_id'] = I('post.user_id');
        $param['role_id'] = I('post.role_id');
        $this->_param_check($param);

        $condition = array(
            'company_id' => $this->role_path['company_id'],
            'shop_id' => $this->role_path['shop_id'],
            'status' => 1,
            'role_id' => $param['role_id']
        );
        $role = $this->model_b_role->getInfo($condition);
        if (empty($role)) {
            $this->encrypt_exit(L('CODE_FILE'), L('MSG_EDIT_ERR'));
        }
        
        $condition = array(
            'user_id' => $param['user_id']
        );
        $delete = $this->model_b_role_user->where($condition)->delete();
        
        $insert = array(
            'role_id' => $param['role_id'],
            'user_id' => $param['user_id']
        );
        $update = $this->model_b_role_user->insert($insert);
    }
}