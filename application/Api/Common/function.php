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

function b_upload_pic($pic_type='goods',$tmp_file,$upload_type="normal",$type='jpg') {
    $path=$_SERVER ['DOCUMENT_ROOT'].__ROOT__.'/data/upload/pic/'.get_company_id().'/'.$pic_type.'/';
    //如果文件夹不存在，递归创建文件夹
    if (!is_dir($path)) {
        mkDirs($path);
    }
    //获取文件夹下文件夹的个数
    $dir_count=getdircount ($path);
    $dir_path="";
    if($dir_count>0) {
        $dir_path=$path.$dir_count."/";
        $file_count=getfilecounts($dir_path);
        //如果文件夹下文件的个数大于设置的可存放的最大文件夹个数，指向另外一个文件夹
        if ($file_count>= C('MAX_FILE_COUNT')){
            $dir_path=$path.($dir_count + 1)."/";
        }
    }else{
        //如果文件夹下不存在文件夹，直接创建一个文件夹
        $dir_path=$path."1/";
    }
    //递归创建文件夹
    if(!is_dir($dir_path)) {
        mkDirs($dir_path);
    }
    $file_names=array();
    $file_name=time().'.'.$type;
    if(filesize($tmp_file)>C('MAX_UPLOAD_SIZE')){
        $data=array(
            'status'=>0,
            'msg'=>'上传的图片不得大于'.(C('MAX_UPLOAD_SIZE')/1024/1024).'M！',
        );
    }else{
        $Image = new \Think\Image();
        //原图
        $file=array(
            'file_name'=>$dir_path.$file_name,
            'width'=>$Image->open($tmp_file)->width(),
            'height'=>$Image->open($tmp_file)->height(),
        );
        $file_names[]=$file;
        //根据不同的上传类型进行处理
        switch($upload_type){
            case 'normal':
                break;
            case 'thumb':
                $file_sizes=C('UPLOAD_SIZES');
                if(!empty($file_sizes)){
                    foreach($file_sizes as $key =>$val){
                        $file=array(
                            'file_name'=>$dir_path.$val['ext'].'_'.$file_name,
                            'width'=>$val['pixel'],
                            'height'=>$val['pixel'],
                        );
                        $file_names[]=$file;
                    }
                }
                break;
            default:
                break;
        }
        //保存多种规格的图片
        foreach($file_names as $key => $val){
            $Image->open($tmp_file)->thumb($val['width'],$val['height'],\Think\Image::IMAGE_THUMB_FILLED)->save($val['file_name']);
        }
        $result = file_exists($dir_path.$file_name);
        $data=array();
        if($result){
            $data=array(
                'status'=>1,
                'filename'=>str_replace($_SERVER['DOCUMENT_ROOT'],'',$dir_path.$file_name),
            );
        }else{
            $data=array(
                'status'=>0,
                'msg'=>'上传失败！',
            );
        }
    }
    return $data;
}