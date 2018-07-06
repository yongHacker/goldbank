<?php
/**
* 用户
* @date: 2018年3月20日 下午3:45:17
* @author: Alam
* @return:
*/
namespace Api\Controller;
defined('JHJAPI') or exit('Access Invalid!');

use Api\Controller\BaseController;

class UserController extends BaseController
{
    
    public function __construct()
    {
        parent::__construct();

        // 不需要权限验证的功能点
        $role_pass = array(
            'info_post',
            'password_post',
            'upload_avatar'
        );
        $this->api_init($role_pass);
    }
    
    /**
     * 修改用户资料
     */
    public function info_post()
    {
        $user_nicename = I('post.user_nicename');
        $user_email = I('post.user_email');
        $signature = I('post.signature');
        $sex = I('post.sex');

        $save['id'] = $this->user['id'];
        if (isset($_POST['user_nicename'])) {
            $save['user_nicename'] = $user_nicename;
        }
        if (isset($_POST['user_email'])) {
            $save['user_email'] = $user_email;
        }
        if (isset($_POST['signature'])) {
            $save['signature'] = $signature;
        }
        if (isset($_POST['sex'])) {
            $save['sex'] = $sex;
        }

        $result = true;
        if (count($save) > 1) {
            $result = $this->model_m_users->save($save);
        }
        if ($result !== false) {
            $this->encrypt_exit(0, L('MSG_EDIT_SUC'));
        } else {
            $this->encrypt_exit(L('CODE_FAIL'), L('MSG_EDIT_ERR'));
        }
    }

    /**
     * 修改用户密码
     */
    public function password_post()
    {
        $password = I('post.password');
        $repassword = I('post.repassword');
        $old_password = I('post.old_password');
        $this->_param_check(array(
            'password' => array(
                $password,
                L('MSG_PASSWORK_NULL')
            ),
            'repassword' => array(
                $repassword,
                L('MSG_REPASSWORK_NULL')
            ),
            'old_password' => array(
                $old_password,
                L('MSG_OLDPASSWORK_NULL')
            )
        ));

        if (sp_compare_password($old_password, $this->user['user_pass'])) {
            if ($password == $repassword) {
                $save['id'] = $this->user['id'];
                $save['user_pass'] = sp_password($password);
                
                $result = $this->model_m_users->save($save);
                if ($result !== false) {
                    $this->encrypt_exit(0, L('MSG_EDIT_SUC'));
                } else {
                    // 修改失败
                    $this->encrypt_exit(L('CODE_FAIL'), L('MSG_EDIT_ERR'));
                }
            } else {
                // 确认密码不一致
                $this->encrypt_exit(L('CODE_PASSWORD_NOTACCORDANCE'), L('MSG_PASSWORD_NOTACCORDANCE'));
            }
        }
        // 密码错误
        $this->encrypt_exit(L('CODE_RAW_PASSWORD_ERR'), L('MSG_RAW_PASSWORD_ERR'));
    }
    
    /**
     * 上传头像
     */
    public function upload_avatar()
    {
        $filepath = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $filepath .= '/Uploads/Avatar/' . (floor($this->user['id'] / 1000) + 1);
        if (! is_dir($filepath)) {
            mkDirs($filepath);
        }
        $filepath .= '/' . $this->user['id'] . '.jpg';
        
        if (($_FILES['file']['type'] != 'image/gif') && ($_FILES['file']['type'] != 'image/jpeg') && ($_FILES['file']['type'] != 'image/png') && ($_FILES['file']['type'] != 'image/pjpeg')) {
            $this->encrypt_exit(L('CODE_IMG_UPLOAD_ERR'), L('MSG_IMG_UPLOAD_ERR'));
        }
        if ($_FILES['file']['error'] > 0) {
            $this->encrypt_exit(L('CODE_IMG_UPLOAD_ERR'), L('MSG_IMG_UPLOAD_ERR'));
        }
        $res = false;
        if (@is_file($filepath)) {
            unlink($filepath);
        }
        if (@move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
            $filename = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filepath);
            $res = $this->model_m_users->update(array(
                'id' => $this->user['id']
            ), array(
                'avatar' => $filename
            ));
        } else {
            $this->encrypt_exit(L('CODE_IMG_UPLOAD_ERR'), L('MSG_IMG_UPLOAD_ERR'));
        }
        if ($res !== false) {
            $this->encrypt_exit(0, L('MSG_IMG_UPLOAD_SUC'), array('url' => $filename));
        } else {
            $this->encrypt_exit(L('CODE_IMG_UPLOAD_ERR'), L('MSG_IMG_UPLOAD_ERR'));
        }
    }
}