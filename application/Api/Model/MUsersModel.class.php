<?php
namespace Api\Model;

use Api\Model\ApiCommonModel;

class MUsersModel extends ApiCommonModel
{
    public function __construct() {
        parent::__construct();
        $this->curmodel='a_appversion';
    }
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('user_login', 'require', '用户名称不能为空！', 1, 'regex', ApiCommonModel:: MODEL_INSERT  ),
        array('user_pass', 'require', '密码不能为空！', 1, 'regex', ApiCommonModel:: MODEL_INSERT ),
        array('user_login', 'require', '用户名称不能为空！', 0, 'regex', ApiCommonModel:: MODEL_UPDATE  ),
        array('user_pass', 'require', '密码不能为空！', 0, 'regex', ApiCommonModel:: MODEL_UPDATE  ),
        array('user_login','','用户名已经存在！',0,'unique',ApiCommonModel:: MODEL_BOTH ), // 验证user_login字段是否唯一
        array('mobile','','手机号已经存在！',0,'unique',ApiCommonModel:: MODEL_BOTH ), // 验证mobile字段是否唯一
      /*  array('user_email','require','邮箱不能为空！',0,'regex',CommonModel:: MODEL_BOTH ), // 验证user_email字段是否唯一
        array('user_email','','邮箱帐号已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证user_email字段是否唯一
        array('user_email','email','邮箱格式不正确！',0,'',CommonModel:: MODEL_BOTH ), // 验证user_email字段格式是否正确*/
    );
    protected $_auto = array(
        array('create_time','mGetTime',ApiCommonModel:: MODEL_INSERT,'callback'),
        array('birthday','',ApiCommonModel::MODEL_UPDATE,'ignore')
    );

    //用于获取时间，格式为当前时间戳,注意,方法不能为private
    function mGetTime() {
        return time();
    }

    protected function _before_write(&$data) {
        parent::_before_write($data);

        if(!empty($data['user_pass']) && strlen($data['user_pass'])<25){
            $data['user_pass']=sp_password($data['user_pass']);
        }
    }
    public function insert($insert){
        if (!empty($insert) && empty($insert["user_nicename"])) {
            $insert["user_nicename"] = $insert["mobile"];
        }
        $insert["device"]=get_device_type();
        $user_id = D('Common/Musers')->add($insert);
        if (!empty($insert['openid'])) {
            $condition['access_id'] = $insert['openid'];
            $condition['access_type'] = 'weixin';
            $ucdata['u_id'] = $user_id;
            D("Common/MUserConnect")->update($condition, $ucdata);
        }
        if ($user_id) {
            $data["user_id"] = $user_id;
            D('Common/MUserBean')->insert($data);
            $data["create_time"] = time();
            $data["update_time"] = time();
            D("Common/MUserPay")->insert($data);
            D("Common/MUserGold")->insert($data);
        }

        return $user_id;
    }
}
