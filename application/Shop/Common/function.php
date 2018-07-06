<?php
//门店后台登录用户id
function get_user_id(){
    return session('SHOP_USER_ID');
}
//门店后台登录商户id
function get_company_id(){
    return session('SHOP_COMPANY_ID');
}
//门店后台登录门店id
function get_shop_id(){
    return session('SHOP_SHOP_ID');
}
/**
 * @author lzy 2018.6.1
 * 获取当前登录用户的员工信息
 */
function get_employee_info(){
    $employee_info=D('BEmployee')->getInfo(array('company_id'=>get_company_id(),'user_id'=>get_user_id()));
    return $employee_info;
}
//判断当前用户是库管用户
function check_wh_uid(){
    $warehouse=D("BWarehouse")->getInfo(array('wh_uid'=>get_user_id()));
    //不是商户管理员，但是是库管
    if($warehouse&&!check_company_uid()){
        return true;
    }else{
        return false;
    }
}
//判断当前登录用户是否为商户管理者
function check_company_uid($user_id){
    if(empty($user_id)){
        $user_id=get_user_id();
    }
    $company=D("BCompany")->getInfo(array('company_id'=>get_company_id()));
    if($company["company_uid"]==$user_id||$user_id==1){
        return true;
    }else{
        return false;
    }
}
//不再库货品状态
function get_out_wharehous_status($type="array"){
    switch($type){
        case "array":
            return array(1,6,8);
            break;
        case "string":
            return "1,6,8";
        default:
            return array(1,6,8);
    }
}
/**
 *	b端公用返回批量单号的函数
 *	@param string $table 表名
 *	@param string $field 字段名
 *	@param string $counts 需要的单号数量
 *	@return 格式：{COMPANY_CODE}_{yymmdd}{3位SHOP_ID}{3位数}
 */
function b_order_number($table, $field, $counts=0)
{
    $company_id = get_company_id();
    $company_info = M('BCompany')->field('company_code')->find($company_id);
    $shop_id = get_shop_id();
    $day = substr(date('Ymd', time()), 2);
    $id = sprintf('%03d', $shop_id);
    $table = M($table);
    $condition = array();
    $condition[$field] = array(
        'like',
        '%' . $company_info['company_code'] . '_' . $day . $id . '%'
    );
    $info = $table->where($condition)->field('MAX('.$field.')as max_number')->find();
    if($info['max_number'] == ''){
        $count = $table->where($condition)->count();
    }else{
        $count = intval(substr($info['max_number'], -3));
    }
    if($counts>=1){
        for($i=0;$i<$counts;$i++){
            $count = sprintf("%03d", $count + 1);
            $order_num[$i] = $company_info['company_code'] .'_'. $day . $id . $count;
        }
    }else{
        $count = sprintf("%03d", $count + 1);
        $order_num = $company_info['company_code'] .'_'. $day . $id . $count;
    }
    return $order_num;
}
/**
 * 检查权限
 * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
 * @param uid  int           认证用户的id
 * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
 * @param $models string    代表是哪个管理后台 a端$models=a b端 $models=b
 * @return boolean           通过验证返回true;失败返回false
 */
function sp_auth_check($uid,$name=null,$relation='or',$model='b'){
    if(empty($uid)){
        return false;
    }

    $iauth_obj=new \Business\Lib\iAuth();
    if(empty($name)){
        $name=strtolower(MODULE_NAME."/".CONTROLLER_NAME."/".ACTION_NAME);
    }

    return $iauth_obj->check($uid, $name, $relation,$model="b",get_company_id());
}
/**
 * 模板变量解析,支持使用函数
 * 格式： {$varname|function1|function2=arg1,arg2}
 * @access public
 * @param string $varStr 变量数据
 * @return string
 */
function parseVar($varStr){
    $varStr     =   trim($varStr);
    static $_varParseList = array();
    //如果已经解析过该变量字串，则直接返回变量值
    if(isset($_varParseList[$varStr])) return $_varParseList[$varStr];
    $parseStr   =   '';
    $varExists  =   true;
    if(!empty($varStr)){
        $varArray = explode('|',$varStr);
        //取得变量名称
        $name = array_shift($varArray);
        //对变量使用函数
        if(count($varArray)>0&&!empty($name)){
            $name = parseVarFunction($name,$varArray);
        }
        $parseStr = $name;
    }
    $_varParseList[$varStr] = $parseStr;
    return $parseStr;
}

/**
 * 对模板变量使用函数
 * 格式 {$varname|function1|function2=arg1,arg2}
 * @access public
 * @param string $name 变量名
 * @param array $varArray  函数列表
 * @return string
 */
function parseVarFunction($name,$varArray){
    //对变量使用函数
    $length = count($varArray);
    //取得模板禁止使用函数列表
    $template_deny_funs = explode(',',C('TMPL_DENY_FUNC_LIST'));
    for($i=0;$i<$length ;$i++ ){
        $args = explode('=',$varArray[$i],2);
        //模板函数过滤
        $fun = trim($args[0]);
        switch($fun) {
            case 'default':  // 特殊模板函数
                $name = (isset($name) && $name!== "")?$name:$args[1];
                break;
            default:  // 通用模板函数
                if(!in_array($fun,$template_deny_funs)&&function_exists($fun)){
                    if(isset($args[1])){
                        if(strstr($args[1],'###')){
                            $param = str_replace('###',$name,$args[1]);
                            if(strpos($param,',')){
                                $param=explode(',',$param);
                            }
                            $name=call_user_func_array($fun, $param);
                        }else{
                            if(strpos($args[1],',')){
                                $param=explode(',',$args[1]);
                                array_unshift($param,$name);
                            }else{
                                $param=array();
                                array_push($param,$name,$args[1]);
                            }
                            $name=call_user_func_array($fun, $param);
                        }
                    }else if(!empty($args[0])){
                        $name = $fun($name);
                    }
                }else{
                    $name = (isset($name) && $name!== "")?$name:$args[1];
                }
        }
    }
    return $name;
}
//获取指定表的指定字段所有值
function status_comment($status,$table,$field){
    $condition ["table"] = DB_PRE.$table;
    $condition ["field"] = $field;
    $status_list =D ( 'b_status' )->getFieldValue($condition);
    return $status_list[$status];
}
//获取当前门店的仓库信息
function get_shop_wh_id($type=1){
    $wh_info=D('BWarehouse')->getInfo(array('company_id'=>get_company_id(),'shop_id'=>get_shop_id()));
    if($type==1){
        return $wh_info;
    }else{
        return $wh_info['id'];
    }
}
//获取指定表的指定字段所有值
function a_status_comment($status,$table,$field){
    $condition ["table"] = DB_PRE.$table;
    $condition ["field"] = $field;
    $status_list =D ( 'a_status' )->getFieldValue($condition);
    return $status_list[$status];
}
/**
 *	返回金料单号的函数
 *	@param string $counts 需要的单号数量
 *	@param string $order_num 序位
 *	@return 格式：{COMPANY_CODE}_{yymmdd}{3位SHOP_ID}{3位数}
 */
function get_rproduct_code_num($order_num=4)
{
    $field='rproduct_code';
    $order_num='%0'.$order_num.'d';
    $order_id='';
    //$shop_id = sprintf($order_num, get_shop_id());
    $day = date('ymd', time());
    $order_id.=$day;
    $table = M('b_recovery_product');
    $condition = array('company_id'=>get_company_id());
    $condition[$field] = array('like', '%' . $order_id . '%');
    $info = $table->where($condition)->field('MAX('.$field.')as max_number')->find();
    if($info['max_number'] == ''){
        $count = $table->where($condition)->count();
    }else{
        $count = intval(substr($info['max_number'], -4));
    }
    //$count = sprintf($order_num, $count + 1);
    return $count;
}
?>