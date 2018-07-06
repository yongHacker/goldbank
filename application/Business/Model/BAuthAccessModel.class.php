<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BAuthAccessModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }
    /**
     * @author lzy 2018.5.26
     * 根据节点名称获取拥有权限的员工名字
     * @param string $rule_name 节点名称,如:business/bprocure/check
     * @param int $type 返回类型 1员工名称 2员工列表
     * @return Ambigous <string, unknown>
     */
    public function getEmployeenamesByRolename($rule_name,$type=1){
        $condition=array(
            'auth_access.rule_name'=>$rule_name,
            'role_user.company_id'=>get_company_id(),
            'employee.deleted'=>0,
            'employee.status'=>array('in','1,2'),
            'employee.company_id'=>get_company_id(),
        );
        $field='DISTINCT employee.employee_name,employee.user_id';
        $join=' join gb_b_role_user role_user on role_user.role_id=auth_access.role_id ';
        $join.=' join gb_b_employee employee on employee.user_id=role_user.user_id';
        $employee_list=$this->alias("auth_access")->getList($condition,$field,'',$join,'employee.id asc','employee.id');

        //需要返回员工列表的时候
        if($type==2){
            return $employee_list;
        }
        $company_info=D('BCompany')->getInfo(array('company_id'=>get_company_id()));
        $employee_name='';
        if(!empty($employee_list)){
            foreach ($employee_list as $key => $val){
                if($val['user_id']!=$company_info['company_uid']){
                    if(empty($employee_name)){
                        $employee_name.=$val['employee_name'];
                    }else{
                        $employee_name.=','.$val['employee_name'];
                    }
                }
            }
        }
        return $employee_name;
    }
    /**
     * @author lzy 2018.5.31
     * 
     * @param string $rule_name 节点名称,如:business/bprocure/check
     * @param int $allot_id 调拨单号id
     * @param number $check_type 1获取出库人 2获取入库人
     */
    public function getEmployeenamesByAllotid($rule_name,$allot_id,$check_type=1){
        $employee_list=$this->getEmployeenamesByRolename($rule_name,2);
        $employee_name='';
        if(!empty($employee_list)){
            $field=$check_type==1?'outbound_id':'receipt_id';
            
            $condition=array('allot.id'=>$allot_id);
            $field='allot.*,wh.wh_uid';
            $join='';
            if($check_type==1){
                $join.=' join gb_b_warehouse wh on wh.id=allot.from_id';
            }else if($check_type==2){
                $join.=' join gb_b_warehouse wh on wh.id=allot.to_id';
            }
            $wh_info=D('BAllot')->alias('allot')->field($field)->where($condition)->join($join)->find();
            foreach($employee_list as $key => $val){
                if($val['user_id']==$wh_info['wh_uid']){
                    if(empty($employee_name)){
                        $employee_name.=$val['employee_name'];
                    }else{
                        $employee_name.=','.$val['employee_name'];
                    }
                }
            }
        }
        return $employee_name;
    }
    /**
     * @author lzy 2018.5.31
     *
     * @param string $rule_name 节点名称,如:business/bprocure/check
     * @param int $allot_id 调拨单号id
     * @param number $check_type 1获取出库人 2获取入库人
     */
    public function getEmployeenamesByAllotRproductid($rule_name,$allot_id,$check_type=1){
        $employee_list=$this->getEmployeenamesByRolename($rule_name,2);
        $employee_name='';
        if(!empty($employee_list)){
            $field=$check_type==1?'outbound_id':'receipt_id';

            $condition=array('allot.id'=>$allot_id);
            $field='allot.*,wh.wh_uid';
            $join='';
            if($check_type==1){
                $join.=' join gb_b_warehouse wh on wh.id=allot.from_id';
            }else if($check_type==2){
                $join.=' join gb_b_warehouse wh on wh.id=allot.to_id';
            }
            $wh_info=D('BAllotRproduct')->alias('allot')->field($field)->where($condition)->join($join)->find();

            foreach($employee_list as $key => $val){
                if($val['user_id']==$wh_info['wh_uid']){
                    if(empty($employee_name)){
                        $employee_name.=$val['employee_name'];
                    }else{
                        $employee_name.=','.$val['employee_name'];
                    }
                }
            }
        }
        return $employee_name;
    }
}
