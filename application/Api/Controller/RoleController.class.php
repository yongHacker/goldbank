<?php
/**
 * 权限
 * 
 * @author alam 2018/01/16 10:00
 */
namespace Api\Controller;
defined('JHJAPI') or exit('Access Invalid!');

use Api\Controller\BaseController;

class RoleController extends BaseController
{
    protected $model_b_role, $model_b_menu, $model_b_options;
    
    function __construct()
    {
        parent::__construct();
        
        $this->api_init();
    }
    
    public function _initialize()
    {
        parent::_initialize();

        $this->model_b_menu = D('BMenu');
        $this->model_b_role = D('BRole');
        $this->model_b_options = D('BOptions');
    }
    
    /**
     * 角色列表
     */
    public function index()
    {
        $condition = array(
            'company_id' => $this->role_path['company_id'],
            'shop_id' => $this->role_path['shop_id']
        );
        $field = 'id, name, remark, status';
        $order = 'listorder Desc,id ASC';
        $count = $this->model_b_role->countList($condition);
        $_POST['limit'] = 20;
        $page = $this->getPage($count);
        $role = $this->model_b_role->getList($condition, $field, $page['limit'], '', $order);

        $this->encrypt_exit(0, '', array_merge(array('role' => $role), $page));
    }
    
    /**
     * 添加角色
     */
    public function add_post()
    {
        $param = array(
            'name' => I('post.name'),
            'remark' => I('post.remark'),
            'status' => I('post.status'),
        );
        $this->_param_check($param);

        $param['company_id'] = $this->role_path['company_id'];
        $param['shop_id'] = $this->role_path['shop_id'];
        $param['create_time'] = time();
        $insert = $this->model_b_role->insert($param);
        
        if ($insert !== false) {
            $this->encrypt_exit(0, L('MSG_ADD_SUC'));
        } else {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_ADD_ERR'));
        }
    }
    
    /**
     * 编辑角色
     */
    public function edit_post()
    {
        $param = array(
            'name' => I('post.name'),
            'remark' => I('post.remark'),
            'status' => I('post.status'),
            'role_id' => I('post.role_id')
        );
        $this->_param_check($param);
        unset($param['role_id']);
        
        if (I('post.role_id') == $this->role_path['role_id']) {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_OPERATION_OBJECT'));
        }
        
        $condition = array(
            'id' => I('post.role_id', 0, 'intval')
        );
        $this->_param_check($condition);
        
        $update = $this->model_b_role->update($condition, $param);
        if ($update !== false) {
            $this->encrypt_exit(0, L('MSG_EDIT_SUC'));
        } else {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_EDIT_ERR'));
        }
    }

    /**
     * 删除角色
     */
    public function delete()
    {
        $param = array('role_id' => I('post.role_id', 0, 'intval'));
        $this->_param_check($param);
        $role_user = $this->model_b_role_user->countList($param);
        if ($role_user > 0) {
            $this->encrypt_exit(L('CODE_ROLE_USER_EXIST'), L('MSG_ROLE_USER_EXIST'));
        }

        $param = array(
            'id' => I('post.role_id', 0, 'intval'),
            'company_id' => $this->role_path['company_id'],
            'shop_id' => $this->role_path['shop_id']
        );
        $delete = $this->model_b_role->where($param)->delete();
        if ($delete !== false) {
            $this->encrypt_exit(0, L('MSG_DEL_SUC'));
        } else {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_DEL_ERR'));
        }
    }
    
    /**
     * 角色授权列表
     */
    public function authorize()
    {
        // 当前角色权限表数据
        $this->_param_check(array(
            'role_id' => I('post.role_id')
        ));
		$priv_data = $this->model_b_auth_access->where(array('role_id' => I('post.role_id', 0, 'intval')))->getField('rule_name', true);
		
		// 菜单一级栏目ID
		$api_menu_start = $this->_get_menu_start();

		// 当前门店所有角色
		$condition = array(
            'shop_id' => $this->role_path['shop_id'],
            'company_id' => $this->role_path['company_id'],
            'status' => 1
        );
		$role_ids = $this->model_b_role->where($condition)->getField('id', true);
		
		// 当前门店权限表数据
		$condition = array(
		    'role_id' => array('in', $role_ids)
		);
		$field = 'DISTINCT rule_name';
		$ad_priv_data = $this->model_b_auth_access->getList($condition, $field);
		foreach ($ad_priv_data as $key => $value) {
		    $ad_priv_data[$key] = $value['rule_name'];
		}
		
		// 菜单
        $condition = array(
		    'company_id' => array('in', '0,' . $this->role_path['company_id']),
            'status' => 1,
            'type' => 1
        );
        $field = 'id, app, model, action, name, parentid, type';
        $order = 'listorder DESC, id ASC';
        $menus = $this->model_b_menu->getList($condition, $field, '', '', $order);
        $menu_tree = array('id' => '0', 'name' => '云掌柜', 'parentid' => '0', 'checked' => '1');
        
		// 判断角色显示权限目录树
		$role_menu = array();
		$i = 0;
		foreach ($menus as $key => $value) {
		    if ($api_menu_start == $value['id']) {
		        // 菜单根节点
		        $menu_tree['id'] = $value['id'];
		        $menu_tree['parentid'] = $value['parentid'];
		        $i = $i + 1;
		    } elseif ($this->_is_checked($value, $ad_priv_data)) {
				$role_menu[$i]['id'] = $value['id'];
				$role_menu[$i]['name'] = $value['name'];
				$role_menu[$i]['parentid'] = $value['parentid'];

				$role_menu[$i]['checked'] = ($this->_is_checked($value, $priv_data)) ? 1 : 0;
                $i = $i + 1;
			}
		}

		import('Tree');
		$tree = new \Tree();
		$tree->init($role_menu);
		$menu_tree['child'] = $tree->get_tree_array2($api_menu_start);

        $this->encrypt_exit(0, '', array('nodeList' => $menu_tree));
    }
    
    /**
     * 角色授权提交
     */
    public function authorize_post()
    {
        $this->_param_check(array(
            'role_id' => I('post.role_id')
        ));
        $role_id = I('post.role_id', 0, 'intval');

        if ($role_id == $this->role_path['role_id']) {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_OPERATION_OBJECT'));
        }
        
        // 菜单一级栏目ID
        $api_menu_start = $this->_get_menu_start();
        
        $menu_id = I('post.menu_id');
        $menu_ids = explode(',', $menu_id . ',' . $api_menu_start);
        
        $delete = $this->model_b_auth_access->where(array('role_id' => $role_id))->delete();
        if (is_array($menu_ids) && count($menu_ids) > 0 && ! empty($menu_id)) {
            foreach ($menu_ids as $value) {
                $menu = $this->model_b_menu->where(array('id' => $value, 'company_id' => array('in', '0,' . $this->role_path['company_id'])))->field('app, model, action')->find();
                if (! empty($menu)) {
                    $name = strtolower($menu['app'] . '/' . $menu['model'] . '/' . $menu['action']);
                    $this->model_b_auth_access->insert(array('role_id' => $role_id, 'rule_name' => $name, 'type' => 'api_url'));
                }
            }
            $this->encrypt_exit(0, L('MSG_ROLE_SET_SUC'));
        } else {
            // 当没有数据时，清除当前角色授权
            if ($delete !== false) {
                $this->encrypt_exit(0, L('MSG_ROLE_CLEAR_SUC'));
            } else {
                $this->encrypt_exit(0, L('MSG_ROLE_CLEAR_ERR'));
            }
        }
    }
    
    /**
     * 获取菜单根目录
     */
    private function _get_menu_start()
    {
        $condition = array(
            'option_name' => 'api_menu_start',
            'status' => 1,
            'deleted' => 0
        );
        $menu_options = $this->model_b_options->getInfo($condition);
        return empty($menu_options['option_value']) ? 0 : $menu_options['option_value'];
    }

    /**
     * 检查指定菜单是否有权限
     * @param array $menu menu表中数组
     * @param int $roleid 需要检查的角色ID
     */
    private function _is_checked($menu, $priv_data)
    {
        $app = $menu['app'];
        $model = $menu['model'];
        $action = $menu['action'];
        $name = strtolower("$app/$model/$action");
        
        if ($priv_data) {
            if (in_array($name, $priv_data)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}