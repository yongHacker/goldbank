<?php
/**
 * 上传图片公用方法
 * @param string $file_path
 * @param string $file
 * @param string $file_name
 */
function upload_image($file_path, $file, $file_name)
{
    $result = array(
        'error_msg' => '上传成功'
    );
    if ($file['error'] != 0) {
        $result['error_msg'] = '图片上传http错误:' . $file['error'];
        return $result;
    }
    if (($file['type'] != 'image/gif') && ($file['type'] != 'image/jpeg') && ($file['type'] != 'image/png') && ($file['type'] != 'image/pjpeg')) {
        $result['error_msg'] = '文件类型不正确' . "[{$file['type']}]";
        return $result;
    }
    $file_type = substr($file['type'], 6);
    
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
    if (!is_dir($file_path)) {
        mkDirs($file_path);
    }
    if (@is_file($file_path)) {
        unlink($file_path);
    }
    $file_path .= '/' . $file_name . '.' . $file_type;
    if (@move_uploaded_file($file['tmp_name'], $file_path)) {
        $result['file_name'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
    } else {
        $result['error_msg'] = '临时文件转移失败';
    }
    return $result;
}
/**
 * session记录权限路径
 * @param unknown $role_path
 */
function set_role_path($role_path = array())
{
    session('ROLE_PATH', $role_path);
}
/**
 * 
 * @return array $role_path
 */
function get_role_path()
{
    $role_path = session('ROLE_PATH');
    return $role_path ? $role_path : array();
}
/**
 * 获取当前用户的商户ID
 * @return int
 */
function get_company_id()
{
    $role_path = session('ROLE_PATH');
    $role_path ? $role_path : array();
    return $role_path['company_id'] ? $role_path['company_id'] : 0;
}
/**
 * 获取当前用户的门店ID
 * @return int
 */
function get_shop_id()
{
    $role_path = session('ROLE_PATH');
    $role_path ? $role_path : array();
    return $role_path['company_id'] ? $role_path['company_id'] : 0;
}
/**
 * 获取当前用户的职位ID
 * @return int
 */
function get_employee_id()
{
    $role_path = session('ROLE_PATH');
    $role_path ? $role_path : array();
    return $role_path['employee_id'] ? $role_path['employee_id'] : 0;
}
/**
 * 获取当前用户的权限ID
 * @return int
 */
function get_role_id()
{
    $role_path = session('ROLE_PATH');
    $role_path ? $role_path : array();
    return $role_path['role_id'] ? $role_path['role_id'] : 0;
}
/**
 * 设置获取用户id
 * @param unknown $user_id
 */
function set_user_id($user_id = array())
{
    session('user_id', $user_id);
}
function get_user_id()
{
    return session('user_id') ? session('user_id') : 0;
}
