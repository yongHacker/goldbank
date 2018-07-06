<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class MUsersModel extends BCommonModel
{
    //当前类需要的模型
    const MUSERS='m_users',MUSERCONNECT='m_user_connect',MUSERBEAN='m_user_bean',MUSERPAY='m_user_pay',MUSERGOLD='m_user_gold',MUSERSOURCE='m_user_source';
    public function __construct() {
        parent::__construct();
    }
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('user_login', 'require', '用户名称不能为空！', 1, 'regex', CommonModel:: MODEL_INSERT  ),
        array('user_pass', 'require', '密码不能为空！', 1, 'regex', CommonModel:: MODEL_INSERT ),
        array('user_login', 'require', '用户名称不能为空！', 0, 'regex', CommonModel:: MODEL_UPDATE  ),
        array('user_pass', 'require', '密码不能为空！', 0, 'regex', CommonModel:: MODEL_UPDATE  ),
        array('user_login','','用户名已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证user_login字段是否唯一
        array('mobile','','手机号已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证mobile字段是否唯一
        array('mobile','/^(\d{11})|(6\d{7})|([569]{1}\d{7})$/','手机号格式错误！'), // 验证mobile规则
      /*  array('user_email','require','邮箱不能为空！',0,'regex',CommonModel:: MODEL_BOTH ), // 验证user_email字段是否唯一
        array('user_email','','邮箱帐号已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证user_email字段是否唯一
        array('user_email','email','邮箱格式不正确！',0,'',CommonModel:: MODEL_BOTH ), // 验证user_email字段格式是否正确*/
    );
    protected $_auto = array(
        array('create_time','mGetTime',CommonModel:: MODEL_INSERT,'callback'),
        array('birthday','',CommonModel::MODEL_UPDATE,'ignore')
    );

    //用于获取时间，格式为当前时间戳,注意,方法不能为private
    function mGetTime() {
        return time();
    }

    protected function _before_write(&$data) {
        parent::_before_write($data);
        $uc_data=$data;
        $uc_data['uc_password']=$data['user_pass'];
        if(!empty($data['user_pass']) && strlen($data['user_pass'])<25){
            $data['user_pass']=sp_password($data['user_pass']);
        }
        $data['uc_openid']=$this->get_uc_openid($uc_data);
    }
    public function insert($insert){
        if (!empty($insert) && empty($insert["user_nicename"])) {
            $insert["user_nicename"] = $insert["mobile"];
        }
        $insert["device"]=get_device_type();
        $user_id = parent::insert($insert);
        //更新三方记录的openid
        if (!empty($insert['openid'])) {
            $condition['access_id'] = $insert['openid'];
            $condition['access_type'] = 'weixin';
            $ucdata['u_id'] = $user_id;
            D(self::MUSERCONNECT)->update($condition, $ucdata);
        }
        //添加金豆，资金，黄金账户
        if ($user_id) {
            $data=array();
            $data["user_id"] = $user_id;
            D(self::MUSERBEAN)->insert($data);
            $data["create_time"] = time();
            $data["update_time"] = time();
            D(self::MUSERPAY)->insert($data);
            D(self::MUSERGOLD)->insert($data);
            //添加来源
            $source=array();
            $source['user_id']=$user_id;
            $shop_id=0;
            if(strtolower(MODULE_NAME)=="shop"){
                $shop_id=session('SHOP_SHOP_ID')>0?session('SHOP_SHOP_ID'):0;
            }
            $source['add_source']=$insert['company_id'].",".$shop_id;
            $source['add_rule_name']=$rule=MODULE_NAME."/".CONTROLLER_NAME."/".ACTION_NAME;
            D(self::MUSERSOURCE)->insert($source);
        }

        return $user_id;
    }
    public function get_uc_openid($data){
        $uc_goldpay=new \Org\UcSdk\Goldpay(
            array(
                'appid' => C('APP_ID'),
                'secrect_key'	=> C('ENCRYPT_KEY'),
                'get_access_token' => function(){
                    return S('uc_goldpay_token');// 用户需要自己实现access_token的返回
                },
                'save_access_token' => function($token) {
                    S('uc_goldpay_token', $token);// 用户需要自己实现access_token的保存
                }
            )
        );
        $uc_openid=$uc_goldpay->get_uc_openid($data);
        return $uc_openid;
    }
    /**
     * 验证用户密码
     * @author lzy 2018-3-22
     * @param int $user_id 用户id
     * @param string $password 用户密码
     * @return boolean
     */
    public function verifyPassword($user_id,$password){
        $user_info=$this->getInfo(array('id'=>$user_id));
        if($user_info['user_pass']==sp_password($password)){
            return true;
        }else{
            return false;
        }
    }
}
