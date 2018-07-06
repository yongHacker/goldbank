<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BCompanyModel extends BCommonModel{
    const MUSERS='m_users',BCOMPANY='b_company';
    public function __construct() {
        parent::__construct();
    }
    /** 
    *   查找匹配credit_code的 company 记录
    *   @param string $credit_code 社会信用代码
    *   @return int $c_id company_id
    */
    public function getCompanyIdByCreditCode($credit_code = NULL) {
        $c_id = 0;

        $where = array(
            'credit_code'=> $credit_code,
            'deleted'=> 0
        );
        $field = 'company_id';

        $company_info = $this->getInfo($where, $field);
        if(!empty($company_info)){
            $c_id = $company_info['company_id'];
        }

        return $c_id;
    }

    public function getCompanyDetail($condition){
        
        $company_info = $this->getInfo($condition);

        if(!empty($company_info)){
            $user_info=D(self::MUSERS)->getInfo(array('id'=>$company_info['user_id']));
            $company_info['user_mobile']=$user_info['mobile'];
            $company_info['user_name']=$user_info['user_nicename']?$user_info['user_nicename']:$user_info['user_login'];
            
            $create_info=D(self::MUSERS)->getInfo(array('id'=>$company_info['creator_id']));

            $company_info['create_name']=$create_info['user_nicename']?$create_info['user_nicename']:$create_info['user_login'];
            
            if($company_info['check_id'] > 0){
                $check_info = D(self::MUSERS)->getInfo(array('id'=>$company_info['check_id']));
                $company_info['check_name']=$check_info['user_nicename']?$check_info['user_nicename']:$check_info['user_login'];
            }

            if($company_info['parent_id'] > 0){
                $c_info=D(self::BCOMPANY)->getInfo(array('id'=>$company_info['parent_id']));
                $company_info['p_name']=$c_info['company_name'];
            }
        }
        return $company_info;
    }
}
