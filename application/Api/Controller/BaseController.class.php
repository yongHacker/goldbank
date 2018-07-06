<?php
/**
 * 接口底层基类，用户信息、权限信息初始化、判断职能
 * 
 * @autho Alam
 */
namespace Api\Controller;

defined('JHJAPI') or exit('Access Invalid!');

use Think\Controller\RestController;

class BaseController extends RestController
{
    
    // REST允许的请求类型列表
    protected $allowMethod = array('get', 'post', 'put');
    
    // REST允许请求的资源类型列表
    protected $allowType = array('html', 'xml', 'json');
    
    // 权限数据, 权限路径, 用户数据, 语言类型, 返回数据数组, 用户信息, 语言类型, 返回数组, 终端类型（0-H5 1-IOS 2-ANDROID）, api加密密钥
    protected $role, $role_path, $user, $character_type, $result_arr, $device, $api_encrypt;

    protected $model_m_users, $model_b_employee, $model_b_auth_access, $model_b_menu;

    public function __construct()
    {
        parent::__construct();
        
        // 返回数据格式
        $this->result_arr = array(
            'code' => L('CODE_OK'),
            'msg' => L('MSG_OK'),
            'data' => array()
        );
        
        $this->api_encrypt = C('API_ENCRYPT_KEY');
        $this->_param_batch();
    }

    public function _initialize()
    {
        parent::_initialize();
        define('DB_PRE', C('DB_PREFIX'));
        
        $this->model_m_users = D('MUsers');
        $this->model_b_employee = D('BEmployee');
        $this->model_b_auth_access = D('BAuthAccess');
        $this->model_b_menu = D('BMenu');
    }

    /**
     * 接口初始化
     * 
     * @param array $role_pass 不需要验证权限的Action
     */
    public function api_init($role_pass = array())
    {
        // 角色来源路径
        $role_path = I('post.role_path');
        // 用户标识
        $access_token = I('post.access_token');
        
        $this->_param_check(array(
            'access_token' => $access_token
        ));
        
        // 用户信息、状态验证
        $user_id = $this->_decrypt_access_token($access_token);
        if (empty($user_id)) {
            $this->encrypt_exit(L('CODE_LOGIN_TIMEOUT'), L('MSG_LOGIN_TIMEOUT'));
        }
        set_user_id($user_id);
        $condition = array(
            'id' => $user_id
        );
        $this->user = $this->model_m_users->getInfo($condition);
        if (empty($this->user)) {
            $this->encrypt_exit(L('CODE_USER_NOT_EXIST'), L('MSG_USER_NOT_EXIST'));
        } elseif (intval($this->user['user_status']) !== 1) {
            $this->encrypt_exit(L('CODE_USER_NOT_LIFE'), L('MSG_USER_NOT_LIFE'));
        }
        
        // 商户、门店、权限信息
        $this->role = $this->model_b_employee->getUserRole($user_id);
        if (empty($this->role)) {
            $this->encrypt_exit(L('CODE_HAVE_NOT_ROLE'), L('MSG_HAVE_NOT_ROLE'));
        }
        
        // 赋值角色来源路径
        if (ACTION_NAME != 'role_path' && CONTROLLER_NAME != 'Public') {
            if (count($this->role) == 1 && count($this->role[0]['shop']) == 1 && count($this->role[0]['shop'][0]['role']) == 1) {
                $this->role_path['company_id'] = $this->role[0]['company_id'];
                $this->role_path['shop_id'] = $this->role[0]['shop'][0]['shop_id'];
                $this->role_path['employee_id'] = $this->role[0]['shop'][0]['employee_id'];
                $this->role_path['role_id'] = $this->role[0]['shop'][0]['role'][0]['role_id'];
            } elseif (! empty($role_path)) {
                $role_path = explode('|', $role_path);
                if ((empty($this->role[$role_path[0]]['shop'][$role_path[1]]['employee_id']) || $this->role[$role_path[0]]['shop'][$role_path[1]]['employee_id'] != $role_path[2]) || ! isset($this->role[$role_path[0]]['shop'][$role_path[1]]['role'][$role_path[3]]) || ! isset($this->role[$role_path[0]]['shop'][$role_path[1]]) || ! isset($this->role[$role_path[0]])) {
                    $this->encrypt_exit(L('CODE_ROLE_ERR'), L('MSG_ROLE_ERR'));
                }
                $this->role_path['company_id'] = $role_path[0];
                $this->role_path['shop_id'] = $role_path[1];
                $this->role_path['employee_id'] = $role_path[2];
                $this->role_path['role_id'] = $role_path[3];
            } elseif (empty($role_path)) {
                $this->encrypt_exit(L('CODE_ROLE_SELECT'), L('MSG_ROLE_SELECT'));
            } else {
                $this->encrypt_exit(L('CODE_HAVE_NOT_ROLE'), L('MSG_HAVE_NOT_ROLE'));
            }
            // session记录当前权限路径
            set_role_path($this->role_path);
        }
        
        if (CONTROLLER_NAME != 'Public' && ! $this->_check_auth() && ! in_array(ACTION_NAME, array_merge($role_pass, array('test')))) {
            $this->encrypt_exit(L('CODE_HAVE_NO_PRIVILEGE'), L('MSG_HAVE_NO_PRIVILEGE'));
        }
    }

    /**
     * Access Token 加密方法
     * 
     * @param string $user_id            
     * @return string $access_token
     */
    protected function _encrypt_access_token($user_id = '')
    {
        $encrypt = new \Encrypt($this->api_encrypt);
        return $access_token = str_replace(array('+', '/'), array('-', '_'), base64_encode($encrypt->encrypt(json_encode(array('user_id' => $user_id), true))));
    }

    /**
     * Access Token 解密方法
     * 
     * @param string $access_token            
     * @return string $user_id
     */
    protected function _decrypt_access_token($access_token = '')
    {
        $encrypt = new \Encrypt($this->api_encrypt);
        $access_token = json_decode($encrypt->decrypt(base64_decode(str_replace(array('-', '_'), array('+', '/'), $access_token))), true);
        return $user_id = $access_token['user_id'];
    }

    /**
     * 接口底层权限点检查
     */
    protected function _check_auth($role_id = '')
    {
        $role_id = empty($role_id) ? $this->role_path['role_id'] : $this->role_path['role_id'];
        if (empty($role_id)) {
            return false;
        }
        
        $condition = array(
            'baa.role_id' => $this->role_path['role_id'],
            'bar.name' => strtolower(MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME)
        );
        $field = 'DISTINCT *';
        $join = 'LEFT JOIN __B_AUTH_RULE__ as bar on baa.rule_name = bar.name';
        $rule = $this->model_b_auth_access->alias('baa')->getList($condition, $field, '', $join);
        
        if (! empty($rule)) {
            return true;
        } else {
            $condition = array(
                'app' => MODULE_NAME,
                'model' => CONTROLLER_NAME,
                'action' => ACTION_NAME
            );
            $menu = $this->model_b_menu->getInfo($condition);
            if (! empty($menu) && $menu['type'] == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 调用接口处理传递的参数
     */
    protected function _param_batch()
    {
        // 上传图片方法调试
        if ((! empty($_POST['debug_access_token']) || ! empty($_POST['debug_role_path'])) && $_SERVER['HTTP_HOST'] === 'goldbank.cc') {
            $_tem_access_token = empty($_POST['debug_access_token']) ? '' : $_POST['debug_access_token'];
            $_tem_role_path = empty($_POST['debug_role_path']) ? '' : $_POST['debug_role_path'];
        }
        
        $post = & $_POST;
        $post = $post['data'];
        $encrypt = new \Encrypt($this->api_encrypt);
        $data = $encrypt->decrypt($post);
        $post = json_decode($data, true);
        if (! empty($_tem_access_token)) {
            if (! empty($_tem_access_token))
                $post['access_token'] = $_tem_access_token;
            if (! empty($_tem_role_path))
                $post['role_path'] = $_tem_role_path;
        }
        
        $this->character_type = empty(I('post.character_type')) ? 'cn' : I('post.character_type');
        $this->device = empty(I('post.device')) ? 1 : I('post.device');
        
        // 接收数据翻译为中文
        $post = & $_POST;
        if (! empty($this->character_type) && $this->character_type !== 'cn') {
            $Character_obj = new \Org\Util\Character();
            $post = $Character_obj->zhconversion($post, 'cn');
        }
    }

    /**
     * 方法前检查参数置空错误
     * 
     * @param array $param            
     */
    protected function _param_check($param = array())
    {
        foreach ($param as $key => $value) {
            if (is_array($value)) {
                if (empty($value[0]) && (string) $value[0] !== 0) {
                    $this->encrypt_exit(L('CODE_PARAM_LOST'), $value[1]);
                }
            } else {
                if (empty($value) && (string) $value !== 0) {
                    $this->encrypt_exit(L('CODE_PARAM_LOST'), str_replace('?', $key, L('MSG_PARAM_LOST')));
                }
            }
        }
    }

    /**
     * 输出前NULL值检查，统一转换为空字符串
     * 
     * @param array $param            
     */
    protected function _isnull_check(&$param)
    {
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $this->_isnull_check($param[$key]);
            }
        }
        if (is_null($param))
            $param = '';
    }

    /**
     * Api加密输出
     * 
     * @param int $code            
     * @param string $msg            
     * @param array $data            
     */
    public function encrypt_exit($code = 0, $msg = '', $data = array())
    {
        $this->result_arr['code'] = empty($code) ? $this->result_arr['code'] : $code;
        $this->result_arr['msg'] = empty($msg) ? $this->result_arr['msg'] : $msg;
        $this->result_arr['data'] = empty($data) ? $this->result_arr['data'] : $data;
        $this->_isnull_check($this->result_arr);
        // 设置语言翻译
        if (! empty($this->character_type)) {
            $Character_obj = new \Org\Util\Character();
            $this->result_arr = $Character_obj->zhconversion($this->result_arr, $this->character_type);
        }
        
        $encrypt = new \Encrypt($this->api_encrypt);
        $result = json_encode($this->result_arr, true);
        $result = $encrypt->encrypt($result);
        
        exit($result);
    }

    /**
     * 分页函数
     *
     * @param int $count            
     * @return array $res[$limit,$hasmore,$curpage,$total_page]
     */
    public function getPage($count)
    {
        $limit = I('post.limit');
        $curpage = I('post.curpage');
        if (empty($limit) || $limit < 1) {
            $limit = 10;
        }
        if (empty($curpage) || $curpage < 1) {
            $curpage = 1;
        }
        $total_page = ceil($count / $limit);
        if ($curpage > $total_page && $total_page > 0) {
            $curpage = $total_page;
        }
        $limit = ($curpage - 1) * $limit . ',' . $limit;
        $res = array(
            'limit' => $limit,
            'hasmore' => $total_page > $curpage ? 1 : 0,
            'curpage' => $curpage,
            'total_page' => $total_page
        );
        return $res;
    }
}