<?php
/**
 * 接口公共控制器，该控制下的函数不做权限判断
 * 
 * @author alam
 * @date Before 2018/01/01 10:00
 */
namespace Api\Controller;
defined('JHJAPI') or exit('Access Invalid!');

use Api\Controller\BaseController;
use System\Controller\SendMessageController;

class PublicController extends BaseController
{
    
    protected $model_b_menu, $model_b_options, $model_a_gold, $model_a_app_version;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function _initialize()
    {
        parent::_initialize();
        
        $this->model_b_menu = D('BMenu');
        $this->model_b_options = D('BOptions');
        $this->model_a_gold = D('AGold');
        $this->model_a_app_version = D('AAppVersion');
        $this->model_m_area = D('MArea');
    }
    
    // 登录
    public function login()
    {
        $user_login = I('post.user_login');
        $user_pass = I('post.user_pass');
        $this->_param_check(array('user_login' => $user_login, 'user_pass' => $user_pass));
        
        $condition = array(
            'mobile' => $user_login
        );
        $field = 'id, user_login, user_pass, user_nicename, user_email, avatar, sex, birthday, signature, user_status, mobile';
        $user = $this->model_m_users->getInfo($condition, $field);

        $time = substr(time(), 0, 7);
        $pass = md5(substr($user['user_pass'], 3) . $time);

        if (empty($user)) {
            $this->encrypt_exit(L('CODE_USER_NOT_EXIST'), L('MSG_USER_NOT_EXIST'));
        } elseif (intval($user['user_status']) !== 1) {
            $this->encrypt_exit(L('CODE_USER_NOT_LIFE'), L('CODE_USER_NOT_LIFE'));
        } elseif ($pass !== $user_pass) {
            $this->encrypt_exit(L('CODE_ACCOUNT_ERR'), L('MSG_ACCOUNT_ERR'), array('time' => $time));
        }
        // 创建身份标识
        $access_token = $this->_encrypt_access_token($user['id']);
        
        // 修改登录信息
        $condition = array(
            'id' => $user['id']
        );
        $update['last_login_ip'] = get_client_ip(0, true);
        $update['last_login_time_pad'] = date('Y-m-d H:i:s');
        $this->model_m_users->update($update);
        
        unset($user['id'], $user['user_status'], $user['user_pass']);
        $user['access_token'] = $access_token;
        $user['signature'] = strip_tags(htmlspecialchars_decode($user['signature']));
        $this->encrypt_exit(0, '', $user);
    }
    
    // 登出
    public function logout()
    {
        $this->_param_check(array('access_token' => I('post.access_token')));
        #TODO APP前台登出，清除key，后台操作待构造
        $this->encrypt_exit();
    }
    
    /**
     * 刷新菜单
     */
    public function refresh_menu()
    {
        $this->api_init();
        $role_id = $this->role_path['role_id'];
        
        // 权限
        $condition = array(
            'role_id' => $role_id
        );
        $rule = $this->model_b_auth_access->where($condition)->getField('rule_name', true);;

        // 菜单
        $condition = array(
		    'company_id' => array('in', '0,' . $this->role_path['company_id']),
            'status' => 1
        );
        $field = 'id, app, model, action, name, parentid, type';
        $order = 'listorder DESC, id ASC';
        $menus = $this->model_b_menu->getList($condition, $field, '', '', $order);
        
        // 菜单一级栏目ID
        $condition = array(
            'option_name' => 'api_menu_start',
            'status' => 1,
            'deleted' => 0
        );
        $menu_options = $this->model_b_options->getInfo($condition);
        $api_menu_start = empty($menu_options['option_value']) ? 0 : $menu_options['option_value'];
        
        $role_menu = array();
        $menu_tree = array('id' => '0', 'name' => '云掌柜', 'parentid' => '0', 'remind_count' => '0');
        
        foreach ($menus as $key => $value) {
            $api_path = strtolower($value['app'] . '/' . $value['model'] . '/' . $value['action']);
            $type = $value['type'];
            unset($value['app'], $value['model'], $value['action'], $value['type']);
            // 红点提醒数量
            $value['remind_count'] = '0';

            if ($api_menu_start == $value['id']) {
                // 菜单根节点
                $menu_tree = $value;
            } elseif ($api_path == 'api/public/role_path') {
                // 切换金行功能点显示判断
                $role_num = 0;
                foreach ($this->role as $company => $value_c) {
                    foreach ($value_c['shop'] as $shop => $value_s) {
                        $role_num += count($value_s['role']);
                    }
                }
                if ($role_num != 1) {
                    $role_menu[] = $value;
                }
            } elseif(in_array($api_path, $rule) || $type == 0) {
                // 数字红点提示
                if ($value['num_status'] == 1) {
                    #TODO 获取提示数量
                    $value['remind_count'] = '1';
                    $menu_tree['remind_count'] += intval($value['remind_count']);
                }
                $role_menu[] = $value;
            }
        }
        $menu_tree['remind_count'] = (string)$menu_tree['remind_count'];

        import('Tree');
        $tree = new \Tree();
        $tree->init($role_menu);
        $menu_tree['child'] = $tree->get_tree_array2($api_menu_start);
        
        $this->encrypt_exit(0, '', array('menu' => $menu_tree));
    }
    
    /**
     * 平台、门店、角色 三重选择
     */
    public function role_path()
    {
        $this->api_init();
        $role = $this->role;
        foreach ($role as $key => $value) {
            foreach ($value['shop'] as $k => $v) {
                $role[$key]['shop'][$k]['role'] = array_values($role[$key]['shop'][$k]['role']);
            }
            $role[$key]['shop'] = array_values($role[$key]['shop']);
        }
        $this->encrypt_exit(0, '', array('company' => array_values($role)));
    }
    
    /**
     * 验证码操作
     *
     * @param string $type          验证类型
     * @param string $mobile        验证对象
     * @param string $verify_code   验证码，缺省-发送验证短信 验证码-验证验证码
     */
    public function operate_verify()
    {
        $type = I('post.type');
        $mobile = I('post.mobile');
        $this->_param_check(array('type' => $type, 'mobile' => $mobile));
        $verify_code = I('post.verify_code');
        $time = time();
        
        if (empty($verify_code)) {
            $code = rand(100000, 999999);
            $msg = new SendMessageController();
            switch ($type) {
                case 'forgot_pass':
                    $content = '您正在重置密码,验证码' . $code . ',有效时间为300秒';
                    break;
                default:
                    $content = '验证码' . $code . ',有效时间为300秒';
                    break;
            }
            $result = $msg->sendOrderSMS($mobile, $content, 5);
            if ($result['is_ok'] == 1) {
                S('verify_code_' . $type . '_' . $mobile, $code . '.' . $time);
                $this->encrypt_exit(L('CODE_MSG_SEND_SUC'), L('CODE_MSG_SEND_SUC'), array('content' => $content));
            } else {
                $this->encrypt_exit(L('CODE_MSG_SEND_ERR'), L('CODE_MSG_SEND_ERR'));
            }
        } else {
            $code_info = S('verify_code_' . $type . '_' . $mobile);
            $check_res = $this->_check_verify_code($code_info, $verify_code);
            if (is_array($check_res) && $check_res !== true) {
                $this->encrypt_exit($check_res[0], $check_res[1]);
            }
            $this->encrypt_exit(0, '');
        }
    }
    
    /**
     * 验证码检查
     */
    protected function _check_verify_code($code_info, $verify_code)
    {
        if (empty($code_info)) {
            $this->encrypt_exit(L('CODE_VERIFY_CODE_ERR'), L('MSG_VERIFY_CODE_ERR'));
        }
        $code_info = explode('.', $code_info);
        $code_info_code = $code_info[0];
        $code_info_time = $code_info[1];
        if ($code_info_time - time() > 300) {
            return array(L('CODE_VERIFY_CODE_TIMEOUT'), L('MSG_VERIFY_CODE_TIMEOUT'));
        } elseif ($verify_code != $code_info_code) {
            return array(L('CODE_VERIFY_CODE_ERR'), L('MSG_VERIFY_CODE_ERR'));
        }
        return true;
    }
    
    /**
     * 忘记密码
     */
    public function forgot_pass()
    {
        $mobile = I('post.mobile');
        $verify_code = I('post.verify_code');
        $password = I('post.password');
        $confirm_password = I('post.confirm_password');
        $this->_param_check(array('mobile' => $mobile, 'verify_code' => $verify_code, 'password' => $password, 'confirm_password' => $confirm_password));
        
        $condition = array('mobile' => $mobile);
        $user_info = $this->model_m_users->getInfo($condition);
        
        if (empty($user_info)) {
            exit($this->returnJson(L('CODE_USER_NOT_EXIST'), L('MSG_USER_NOT_EXIST')));
        }
        
        if ($password !== $confirm_password) {
            exit($this->returnJson(L('CODE_PASSWORD_NOTACCORDANCE'), L('MSG_PASSWORD_NOTACCORDANCE')));
        }
        
        $code_info = S('verify_code_forgot_pass_' . $mobile);
        $check_res = $this->_check_verify_code($code_info, $verify_code);
        if (is_array($check_res) && $check_res !== true) {
            $this->encrypt_exit($check_res[0], $check_res[1]);
        }
        
        $result = $this->model_m_users->update(array(
            'mobile' => $mobile
        ), array(
            'user_pass' => sp_password($password)
        ));
        if ($result === false) {
            $this->encrypt_exit(L('CODE_ALTER_PASSWORD_ERR'), L('MSG_ALTER_PASSWORD_ERR'));
        } else {
            $this->encrypt_exit(0, L('MSG_ALTER_PASSWORD_SUC'));
        }
    }
    
    /**
     * 刷新当前金价
     */
    public function refresh_gold_price()
    {
        $this->api_init();
        $gold_price = $this->model_a_gold->getGoldPrice();
        $this->encrypt_exit(0, '', array('gold_price' => $gold_price));
    }
    
    /**
     * 版本识别以及更新
     */
    public function version_check()
    {
        $version = empty(I('post.version')) ? 0 : I('post.version');
        $new_version = $this->model_a_app_version->version($this->device);
        
        $result = array(
            'version' => $version,
            'status' => '0',
            'update_content' => ''
        );
        $msg = L('MSG_OK');
        if (!empty($new_version) && $version < $new_version['app_version']) {
            $result['version'] = $new_version['app_version'];
            $result['status'] = ($new_version['update_status'] == 1) ? 1 : 2;
            $result['update_content'] = $new_version['update_content'];
        }
        $this->encrypt_exit(0, $msg, $result);
    }
    
    /**
     * 三级联动
     * @param int level 地区深度 从1开始
     * @param int area_code 地区父编码
     */
    public function area_list()
    {
        $level = I('post.level');
        $this->_param_check(array('level' => $level));
        
        $condition['area_deep'] = $level;
        if (intval(I('post.level')) !== 1) {
            $condition['area_parent_code'] = I('post.area_code', 0, 'intval');
        }
        $level_list = $this->model_m_area->getList($condition, $field = 'area_id,area_name,post_code,area_code');
        $this->encrypt_exit(0, '', array('level_list' => $level_list));
    }

    /**
     * 导出excel
     */
    public function export2excel(){
        vendor("PHPExcel.PHPExcel");    //引入PHPExcel类库
        $objPHPExcel = new \PHPExcel(); //首先创建一个新的对象 PHPExcelobject
        //设置文件的一些属性，在xls文件->属性->详细信息里可以看到这些值，xml表格里是没有这些值的
        $objPHPExcel->getProperties()   //获得文件属性对象，给下文提供设置资源
                    ->setCreator("邓智森") //设置文件的创建者
                    ->setLastModifiedBy("邓智森") //设置最后修改者
                    ->setTitle("office2007 xls testfile") //设置标题
                    ->setSubject("office2007 xls testtest") //设置主题
                    ->setDescription("Test document for Office2007 XLSX, generated using PHP classes.") //设置备注
                    ->setKeywords("office 2007 openxmlphp") //设置标记
                    ->setCategory("test resultFile");   //设置类别
        //给表格添加数据
        $objPHPExcel->setActiveSheetIndex(0)    //设置第一个内置表(一个xls文件里可以有多个表)
                    ->setCellValue('A1','Hello')    //给表的单元格设置数据
                    ->setCellValue('B2','world!')   //数据格式可以为字符串
                    ->setCellValue('C1',12)         //数字型
                    ->setCellValue('D2',12)
                    ->setCellValue('D3',true)       //布尔型
                    ->setCellValue('D4','=SUM(C1:D2)'); //公式
        //得到当前活动的表，ps: 下面会经常用到$objActSheet
        $objActSheet = $objPHPExcel->getActiveSheet();
        //给当前活动的表设置名称
        $objActSheet->setTitle('simple2333');
/*        ******************************************************************************************************/
        //直接生成一个文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        $objWriter->save('myexchel.xlsx');
        //提示下载文件
        //生成Excel 2003格式的xls文件
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename='01simple.xls'");
        header("Cache-Control:max-age=0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
        //生成Excel 2007格式的xlsx文件
        header("Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition:attachement;filename='02simple.xlsx'");
        header("Cache-Control:max-age=0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        $objWriter->save('php://output');
        exit;
        //下载一个pdf文件
        header("Content-Type:application/pdf");
        header("Content-Dispositon:attachment;filename='03simple.pdf'");
        header("Cache-Control:max-age=0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'PDF');
        $objWriter->save('php://output');
        exit;
        //生成一个pdf文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'PDF');
        $objWriter->save('a.pdf');
        //生成CSV文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'CSV')
            ->setDelimiter(',')     //设置分隔符
            ->setEnclosure('"')     //设置包围符
            ->setLineEnding("\r\n") //设置行分隔符
            ->setSheetIndex(0)      //设置活动表
            ->save(str_replace('.php', '.csv', __FILE__));
        //生成HTML文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'HTML');
        $objWriter->setSheetIndex(0);   //设置活动表
        $objWriter->save(str_replace('.php', '.htm', __FILE__));    //保存文件









       /* $data = ['100','80',90];
        for ($i=1;$i<=3;$i++){
            if ($i>1){
                $objPHPExcel->createSheet();//创建新的内置表
            }
            $objPHPExcel->setActiveSheetIndex($i-1);
            $objSheet = $objPHPExcel->getActiveSheet();
            $objSheet->setTitle($i.'年级');
            $objSheet->setCellValue("A1","姓名")->setCellValue("B1","分数")->setCellValue("C1","班级");
            $j = 2;
            foreach ($data as $key=>$val){
                $objSheet->setCellValue("A".$j,$val);
                $j++;
            }
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,"Excel5"); //生成Excel文件
        $objWriter->save('php://output');*/
    }

    /**
     *商品图片源文件删除
     * @author dengzs @date 2018/7/3 13:19
     */
    public function del_pic(){
        $data = trim(I('post.'));
        //删除图片源文件
        if(b_del_pic($data)){
            //json格式输出
            output_data($data);
        }

    }

    /**
     * 导入或读取文件
     */
    public function import2excel(){
        //通过PHPExcel_IOFactory::load方法来载入一个文件，load会自动判断文件的后缀名来导入相应的处理类，
        //读取格式含xlsx/xls/xlsm/ods/slk/csv/xml/gnumeric
        vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = \PHPExcel_IOFactory::load("");
        //把载入的文件默认表(一般是第一个)通过toArray方法来返回一个多维数组
        $dataArray = $objPHPExcel->getActiveSheet()->toArray();
        //读完直接写到一个xlsx文件里
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');   //$objPHPExcel是上文中读的资源
        $objWriter->save(str_replace('.php','.xlsx',__FILE__));

        //读取xml文件
        $objReader = \PHPExcel_IOFactory::createReader('Excel2003XML');
        $objPHPExcel = $objReader->load("Excel2003XMLTest.xml");
        //读取ods文件
        $objReader = \PHPExcel_IOFactory::createReader('OOCalc');
        $objPHPExcel = $objReader->load("OOCalcTest.ods");
        //读取numeric文件
        $objReader = \PHPExcel_IOFactory::createReader('Gnumeric');
        $objPHPExcel = $objReader->load("GnumericTest.gnumeric");
        //读取slk文件
        $objPHPExcel = \PHPExcel_IOFactory::load("SylkTest.slk");

        //循环遍历数据
        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');    //创建一个2007读取对象
        $objPHPExcel = $objReader->load("nxcbvkjn.xlsx");   //读取一个xlsx文件
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet){   //遍历工作表
            echo 'Worksheet -',$worksheet->getTitle(),PHP_EOL;
            foreach ($worksheet->getRowIterator() as $row){     //遍历行
                echo '  Row number -',$row->getRowIndex(),PHP_EOL;
                $cellIterator = $row->getCellIterator();    //得到所有列
                $cellIterator->setIterateOnlyExistingCells(false);  //// Loopall cells, even if it is not set
                foreach ($cellIterator as $cell){       //遍历列
                    if (!is_null($cell)){       //如果列不给空就得到它的坐标和计算的值
                        echo '      Cell -',$cell->getCoordinate(),' -',$cell->getCalculatedValue(),PHP_EOL;
                    }

                }

            }
        }

        //把数组插入到表中
        $data =  array(        //事先准备的数组
            array(
                'title' => 'Excel fordummies',
                'price' => '17.99',
                'quantity' => '2',
            ),
            array(
                'title' => 'PHP fordummies',
                'price' => 15.99,
                'quantity' =>1
            ),
            array(
                'title' => 'InsideOOP',
                'price' => 12.95,
                'quantity' =>1
            )
        );
        $baseRow = 5;   //指定插入到第5行后
        foreach ($data as $r=>$dataRow){
            $row = $baseRow+$r;     //$row是循环操作行的行号
            $objPHPExcel->getActiveSheet()->insertNewRowBefore($row,1); //在操作行的号前加一空行，这空行的行号就变成了当前的行号
            //对应的列都附上数据和编号
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,$r+1);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$row,$dataRow['title']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$row,$dataRow['price']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$row,$dataRow['quantity']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$row,'=C'.$row.'*D'.$row);
        }
        $objPHPExcel->getActiveSheet()->removeRow($baseRow-1,1);     //最后删去第4行，这是示例需要，在此处为大家提供删除实例

    }


}