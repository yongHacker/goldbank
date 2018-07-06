<?php
/**
 * 用户
 * 
 * @author 门店
 * @date 2018/01/01 10:00
 */
namespace Api\Controller;

defined('JHJAPI') or exit('Access Invalid!');

use Api\Controller\BaseController;

class ShopController extends BaseController
{
    
    protected $model_b_shop, $model_b_shop_attribute, $model_m_area;
    
    public function __construct()
    {
        parent::__construct();

        // 不需要权限验证的功能点
        $role_pass = array(
            'upload_logo',
            'upload_shop_image'
        );
        $this->api_init($role_pass);
    }
    
    public function _initialize()
    {
        parent::_initialize();

        $this->model_b_shop = D('BShop');
        $this->model_b_shop_attribute = D('BShopAttribute');
        $this->model_m_area = D('MArea');
    }
    
    /**
     * 上传金行logo
     * @param array $_FILES['logo']
     */
    public function upload_logo()
    {
        $shop_info = $this->model_b_shop_attribute->shopDetail($this->role_path['shop_id']);
        if (empty($shop_info)) {
            $this->encrypt_exit(L('CODE_SHOP_NOT_EXIST'), L('MSG_SHOP_NOT_EXIST'));
        }
        $file = $_FILES['logo'];
        
        $file_path = '/Uploads/Shops/' . $this->role_path['shop_id'] . '/logo';
        $file_name = time() . rand(100, 999);
        $upload = upload_image($file_path, $file, $file_name);
        
        if (empty($upload['file_name'])) {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_FAIL'), array('upload_error_msg' => $upload['error_msg']));
        } else {
            $condition = array(
                'shop_id' => $this->role_path['shop_id']
            );
            $data = array(
                'shop_logo' => $upload['file_name']
            );
            $update = $this->model_b_shop_attribute->update($condition, $data);
            if ($update !== false) {
                $logo_path = $_SERVER['DOCUMENT_ROOT'] . $shop_info['shop_logo'];
                @unlink($logo_path);
                $this->encrypt_exit(0, '', array('shop_logo' => $upload['file_name']));
            }
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_FAIL'));
        }
    }
    
    /**
     * 上传金行图片
     * @param array $_FILES['images[]']
     */
    public function upload_shop_image()
    {
        $file_path = '/Uploads/shops/' . $this->role_path['shop_id'];
        $file = array();
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if (!empty($_FILES['images']['name'][$i])) {
                $file[$i]['name'] = $_FILES['images']['name'][$i];
                $file[$i]['type'] = $_FILES['images']['type'][$i];
                $file[$i]['tmp_name'] = $_FILES['images']['tmp_name'][$i];
                $file[$i]['error'] = $_FILES['images']['error'][$i];
                $file[$i]['size'] = $_FILES['images']['size'][$i];
            }
        }

        $success_list = array();
        foreach ($file as $key => $value) {
            $file_name = time() . rand(100, 999);
            $upload = upload_image($file_path, $value, $file_name);

            if (empty($upload['file_name'])) {
                foreach ($success_list as $key => $value) {
                    @unlink($value);
                }
                $this->encrypt_exit(L('CODE_IMG_UPLOAD_ERR'), L('MSG_IMG_UPLOAD_ERR'), array('upload_error_msg' => $upload['error_msg']));
            } else {
                $success_list[] = $upload['file_name'];
            }
        }

        $this->encrypt_exit(0, L('MSG_IMG_UPLOAD_SUC'), array('success_list' => $success_list));
    }
    
    /**
     * 修改门店信息
     */
    public function info_post()
    {
        $shop_info = $this->model_b_shop_attribute->shopDetail($this->role_path['shop_id']);
        
        /* $_POST['area_id'] = '440307014test1';
        $_POST['area_info'] = '广东省 深圳市 龙岗区 布吉街道test1';
        $_POST['post_code'] = '518000test1';
        $_POST['desc'] = 'test1';
        $_POST['begin_time'] = '06:00';
        $_POST['end_time'] = '20:00';
        $_POST['address'] = 'addresstest1';
        $_POST['shop_tel'] = '1234-5678test1'; */

        $condition = array(
            'id' => $shop_info['id']
        );
        if ($_POST['area_id']) {
            $data['area_id'] = I('post.area_id/d', 0);
        }
        if ($_POST['shop_hours_start']) {
            $data['shop_hours_start'] = I('post.shop_hours_start/s');
        }
        if ($_POST['shop_hours_start']) {
            $data['shop_hours_start'] = I('post.shop_hours_start/s');
        }
        if ($_POST['area_info']) {
            $data['area_info'] = I('post.area_info/s');
        }
        if ($_POST['shop_tel']) {
            $data['shop_tel'] = I('post.shop_tel/s');
        }
        if ($_POST['address']) {
            $data['address'] = I('post.address/s');
        }
        if ($_POST['post_code']) {
            $data['post_code'] = I('post.post_code/d', 0);
        }
        if ($_POST['address']) {
            $data['address'] = I('post.address/s');
        }
        if ($_POST['scope']) {
            $data['scope'] = I('post.scope/s');
        }
        if ($_POST['desc']) {
            $data['desc'] = I('post.desc/s');
        }
        if (!empty($data)) {
            $data['update_id'] = $this->user['id'];
            $data['update_time'] = time();
            $update = $this->model_b_shop_attribute->update($condition, $data);
            if ($update !== false) {
                $this->encrypt_exit(0, L('MSG_EDIT_SUC'));
            }
        }
        $this->encrypt_exit(L('CODE_EDIT_ERR'), L('MSG_EDIT_ERR'));
    }
    
    /**
     * 获取门店信息
     */
    public function shop_info()
    {
        #TODO 等门店数据结构确定下来再改这段代码的字段
        $shop_id = (empty(I('post.shop_id'))) ? $this->role_path['shop_id'] : I('post.shop_id');
        
        // shop
        $shop_info = $this->model_b_shop->shopInfo($shop_id);
        if (empty($shop_info)) {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_SHOP_NOT_EXIST'));
        }
        
        // shop attr
        $attr_info = $this->model_b_shop_attribute->getInfo($condition = array('shop_id' => $shop_id));
        if (empty($attr_info)) {
            $attr_info = $this->_add_shop_attr();
        }
        if ($attr_info === false) {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_FAIL'));
        }
        $attr_info['area_list'] = array();
        if (!empty($attr_info['area_id'])) {
            $this->model_m_area->getAreaIdByChild($attr_info['area_id'], 1, $attr_info['area_list']);
            $attr_info['area_list'] = array_reverse($attr_info['area_list']);
        }

        $attr_info['shop_name'] = $shop_info['shop_name'];
        $attr_info['desc'] = strip_tags(str_replace(array('<br>','<br/>'), ' ', htmlspecialchars_decode($attr_info['desc'])));
        $attr_info['image_list'] = array_values($this->_get_shop_image_list());

        $this->encrypt_exit(0, '', array('info' => $attr_info));
    }
    
    /**
     * 添加门店资料
     * @param array $info
     * @return boolean|array $attr_info
     */
    protected function _add_shop_attr($info = array())
    {
        $attr_info = array(
            'shop_id' => $this->role_path['shop_id'],
            'area_id' => '0',
            'shop_hours_start' => isset($info['shop_hours_start']) ? $info['shop_hours_start'] : '',
            'shop_hours_end' => isset($info['shop_hours_end']) ? $info['shop_hours_end'] : '',
            'area_info' => isset($info['area_info']) ? $info['area_info'] : '',
            'shop_tel' => isset($info['shop_tel']) ? $info['shop_tel'] : '',
            'shop_logo' => isset($info['shop_logo']) ? $info['shop_logo'] : '',
            'default_image' => isset($info['default_image']) ? $info['default_image'] : '',
            'address' => isset($info['address']) ? $info['address'] : '',
            'post_code' => isset($info['post_code']) ? (string)$info['post_code'] : '0',
            'update_id' => $this->user['id'],
            'update_time' => time(),
            'scope' => isset($info['scope']) ? $info['scope'] : '',
            'desc' => isset($info['desc']) ? $info['desc'] : '',
        );
        
        $insert = $this->model_b_shop_attribute->insert($attr_info);
        if ($insert === false) {
            return false;
        }
        return $attr_info;
    }
    
    /**
     * 获取金行所有图片
     * @param int $shop_id
     */
    protected function _get_shop_image_list($shop_id = 0)
    {
        $shop_id = empty($shop_id) ? $this->role_path['shop_id'] : $shop_id;
        $file_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $file_path .= '/Uploads/Shops/' . $shop_id;
        if (! is_dir($file_path)) {
            mkDirs($file_path);
            return array();
        }
        
        $files = scandir($file_path);
        foreach ($files as $key => $value) {
            if ($value == '.' || $value == '..' || $value == "logo") {
                unset($files[$key]);
                continue;
            }
            $files[$key] = '/Uploads/shops/' . $shop_id . '/' . $value;
        }
        return $files;
    }
}