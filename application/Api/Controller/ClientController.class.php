<?php
/**
 * 客户
 * @date: 2018年3月20日 下午3:44:57
 * @author: Alam
 * @return:
 */
namespace Api\Controller;

defined('JHJAPI') or exit('Access Invalid');

use Api\Controller\BaseController;

class ClientController extends BaseController
{
    
    protected $model_b_client;

    public function __construct()
    {
        parent::__construct();
        
        $role_pass = array(
            'index', 'seller', 'edit'
        );
        $this->api_init($role_pass);
    }
    
    public function _initialize()
    {
        parent::_initialize();

        $this->model_b_client = D('BClient');
    }
    
    /**
     * 我的客户
     */
    public function index()
    {
        $field = 'bc.user_id, mu.avatar, mu.user_nicename, mu.mobile, mu.sex, mu.user_email, mu.birthday, mu.signature, bc.create_time, bc.employee_id, mue.user_nicename as employee';
        $count = $this->model_b_client->getClients(true);
        $page = $this->getPage($count);
        $clients = $this->model_b_client->getClients(false, $field, $page['limit']);
        foreach ($clients as $key => $value) {
            $clients[$key]['signature'] = strip_tags(htmlspecialchars_decode($value['signature']));
            #TODO 交易明细金额
            $clients[$key]['total_money'] = numberformat(0, 2);
        }
        
        $this->encrypt_exit(0, '', array('clients' => $clients));
    }
    
    /**
     * 我的客户查询筛选 - 销售员
     */
    public function seller()
    {
        $seller = $this->model_b_client->getSellers();

        $this->encrypt_exit(0, '', array('seller' => $seller));
    }
    
    /**
     * 编辑或者添加客户
     */
    public function edit()
    {
        $this->_param_check(array(
            'mobile' => I('post.mobile')
        ));
        if(empty(I('post.client_id'))) {
            $result = $this->model_b_client->add();
        } else {
            $result = $this->model_b_client->alter();
        }

        if (!$result){
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_FAIL'));
        } elseif ($result == -1) {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_USER_EXIST'));
        } else {
            $this->encrypt_exit(0, '', array('id' => $result));
        }
    }
}
?>