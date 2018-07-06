<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class BEmployeeModel extends ApiCommonModel
{
    public function __construct()
    {
        parent::__construct();
    }

    protected $_auto = array(
        array('status', '1', ApiCommonModel:: MODEL_INSERT, 'string'),
        array('delete', '0', ApiCommonModel::MODEL_INSERT, 'string')
    );
    
    /**
     * 获取用户角色权限
     * @return array $role:
     */
    public function getUserRole($user_id = 0)
    {
        if (empty($user_id)) {
            return array();
        }
        $condition = array(
            'be.user_id' => $user_id,
            'be.status' => 1,
            'be.deleted' => 0,
            'bc.company_status' => 1,
            'bc.deleted' => 1,
            'br.id' => array('neq', '')
        );
        $field = 'bc.company_id, bc.company_name, bc.company_short_name, bc.company_img, 
            bse.shop_id, bs.shop_name, bs.shop_pic, 
            be.id as employee_id, be.employee_name, 
            br.id as role_id, br.name as rold_name';
        $join = 'LEFT JOIN __B_SHOP_EMPLOYEE__ bse ON be.id = bse.employee_id ';
        $join .= 'LEFT JOIN __B_COMPANY__ bc ON be.company_id = bc.company_id ';
        $join .= 'LEFT JOIN __B_SHOP__ bs ON bse.shop_id = bs.id ';
        $join .= 'LEFT JOIN __B_ROLE_USER__ bru ON bse.user_id = bru.user_id ';
        $join .= 'LEFT JOIN __B_ROLE__ br ON bru.role_id = br.id AND be.company_id = br.company_id AND bse.shop_id = br.shop_id AND br.status = 1 ';
        $employee = $this->alias('be')->getList($condition, $field, null, $join);

        $role = array();
        // 商户、门店
        foreach ($employee as $key => $value) {
            if (empty($role[$value['company_id']])) {
                $role[$value['company_id']] = array(
                    'company_id' => $value['company_id'],
                    'company_name' => $value['company_name'],
                    'company_short_name' => $value['company_short_name'],
                    'company_img' => $value['company_img']
                );
            }
            unset($value['company_name'], $value['company_short_name'], $value['company_img']);
            
            if (empty($role[$value['company_id']]['shop'][$value['shop_id']])) {
                $role[$value['company_id']]['shop'][$value['shop_id']] = $value;
                unset($role[$value['company_id']]['shop'][$value['shop_id']]['company_id']);
            }
            if (empty($role[$value['company_id']]['shop'][$value['shop_id']]['role'][$value['role_id']])) {
                $role[$value['company_id']]['shop'][$value['shop_id']]['role'][$value['role_id']] = array(
                    'role_id' => $value['role_id'],
                    'role_name' => $value['rold_name']
                );
                unset($role[$value['company_id']]['shop'][$value['shop_id']]['role_id'], $role[$value['company_id']]['shop'][$value['shop_id']]['rold_name']);
            }
        }
        return $role;
    }
    
    /**
     * 检查门店员工存在否
     * @param number $user_id
     * @return boolean|array <boolean, employee info>
     */
    public function _check_exist($user_id = 0, $company_id = 0, $shop_id = 0)
    {
        if (empty($user_id) || empty($company_id) || empty($shop_id)) {
            return false;
        }
        $field = 'be.id,bse.shop_id';
        $condition = array(
            'be.status' => 1,
            'be.deleted' => 0,
            'be.user_id' => $user_id,
            'be.company_id' => $company_id
        );
        $join = 'LEFT JOIN __B_SHOP_EMPLOYEE__ bse ON be.id = bse.employee_id AND bse.shop_id = ' . $shop_id;
        $employee = $this->alias('be')->join($join)->getInfo($condition, $field);
        return empty($employee) ? false : $employee;
    }
}