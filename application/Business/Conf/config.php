<?php
if(file_exists("data/conf/db.php")){
	$db=include "data/conf/db.php";
}else{
	$db=array();
}
if(file_exists("data/conf/config.php")){
	$runtime_config=include "data/conf/config.php";
}else{
    $runtime_config=array();
}

if (file_exists("data/conf/route.php")) {
    $routes = include 'data/conf/route.php';
} else {
    $routes = array();
}
if (file_exists(APP_PATH."Common/Conf/config.php")) {
    $common_config = include 'data/conf/route.php';
} else {
    $common_config = array();
}

$configs= array(
    'INIT_IGNORE_TABLE'=>array(
        $db['DB_PREFIX'].'b_company',
        $db['DB_PREFIX'].'b_company_contribute',
        $db['DB_PREFIX'].'b_auth_access',
        $db['DB_PREFIX'].'b_role',
    ),
    //上传图片的额最大大小
    'MAX_UPLOAD_SIZE'=>10*1024*1024,   
    //上传图片的压缩尺寸
    'UPLOAD_SIZES'=>array(
        'small'=>array(
            'ext'=>'small',
            'pixel'=>'320',
        ),
        'medium'=>array(
            'ext'=>'medium',
            'pixel'=>'640',
        ),
        'large'=>array(
            'ext'=>'large',
            'pixel'=>'1280',
        ),
    ),
);

return  array_merge($configs,$db,$runtime_config,$common_config);
