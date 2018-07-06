<?php
namespace Common\Controller;
use Common\Controller\WXMemberController;
class PayBaseController extends WXMemberController {
    public $user_pay;
    public function __construct()
    {
        parent::__construct();
        if ($this->user_info['is_user'] == 1) {
            $this->user_pay = D('Common/user_pay')->getOne(array('user_id' => $this->user_info['id']));
        } else {
            if ($this->user_info['user_id'] > 0) {
                $this->user_pay = D('Common/user_pay')->getOne(array('user_id' => $this->user_info['user_id']));
            } else {
                //没有用户信息，可能是第三方登录
                $this->redirect(U('Wechat/Index/member_index'));
                //$this->user_pay['pay_password'] = '';
            }

        }
        /**
         * 先实名，再绑卡，最后设置支付密码
         */
        //是否有资金账户
        $ref_url = C('SITE_URL') . $_SERVER["REQUEST_URI"];
        if (empty($this->user_pay)) {
            $this->redirect(U('Wechat/Index/member_index'));
        }
        //是否实名
        if (empty($this->user_info['is_real_name'])) {
            // $this->user_info['real_name'] = '没有实名';
            $this->redirect(U('Wechat/Member/realname', array('ref_url' => $ref_url)));
        }
        //是否绑卡
        if (empty($this->user_info['is_bank'])) {
            $this->redirect(U('Wechat/Member/mybank', array('ref_url' => $ref_url)));
        }
        //是否设置支付密码
        if (empty($this->user_pay['pay_password'])) {
            //$this->redirect(U('Index/member_index'));
            //output_error(array('msg' => 'fail', "info" => "请设置支付密码"));
            $url = U("Wechat/Member/paypass_set", array('ref_url' => $ref_url));
            $this->redirect($url);
        }


    }
}