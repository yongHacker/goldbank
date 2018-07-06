<?php
namespace System\Controller;
use Common\Controller\HomebaseController;
class CrontabController extends HomebaseController{
    public function __construct(){
        parent::__construct();
    }
    /**
     * 请求金价数据
     */
    public function get_gold_data(){
        //file_put_contents('/run.log', 'juh------'.date('Y-m-d H:i:s')."\r\n", FILE_APPEND);
        $model_gold=D('a_gold');
        $model_gold_cat = D('a_gold_category');
        $model_gold_mak = D('a_gold_market');
        $model_gold_mak_new = D("gold_market_new");
        $model_option = D('a_options');
        $weekday = date('w');//星期几
        $day = date('Y-m-d', time());
        $time = time();
        $day_9 = strtotime($day) + 9 * 3600;//每天9点
        $day_11 = strtotime($day) + 11.55 * 3600;//每天11点半
        $day_13 = strtotime($day) + 13.5 * 3600;//每天13点半
        $day_15 = strtotime($day) + 15.55 * 3600;//每天15点半
        $day_16 = strtotime($day) + 16.5 * 3600;//每天16点半
        $day_17 = strtotime($day) + 17 * 3600;//每天17点半
        $day_20 = strtotime($day) + 20 * 3600;//每天16点半
        $day_02 = strtotime($day) + 2.5 * 3600;//每天2点半
        $date=date("H:i",time());
        $date=explode(":",$date);
        $appkey='f6ba99f6ced6cff480e6c3afc3db3d45';
        if($date[0]>=20){
            $condition=($weekday != '6' && $weekday != '0');
        }else {
            $condition = ((($time > $day_9 && $time < $day_11) || ($time < $day_15 && $time > $day_13)||$time < $day_02) && $weekday != '6' && $weekday != '0')||($weekday == '6'&&$time < $day_02);
        }
        if ($condition) {
            $url = "http://web.juhe.cn:8080/finance/gold/shgold";
            $params = array(
                "key" => $appkey,//APP Key
                "v" => "1",//JSON格式版本(0或1)默认为0
            );
            $paramstring = http_build_query($params);
            $content = juhecurl($url, $paramstring);
            $result = json_decode($content, true);
            $open_info = $model_option->getGoldSwitch();
            $is_open = $open_info['option_value'];
            $gold_relation_cate = $model_option->getGoldRelationCatid();
            $gold_relation = explode(',', $gold_relation_cate['option_value']);
            if ($result) {
                if ($result['error_code'] == '0') {
                    $g_list = $result['result'][0];
                    foreach ($g_list as $key => $val) {
                        $gold_category = $model_gold_cat->getInfo(array('status' => 1, 'name' => $key));
                        if (!empty($gold_category)) {
                            $gold_price = array();
                            $gold_price['latestprice'] = $val['latestpri'] != "--" ? $val['latestpri'] : '0.00';
                            $gold_price['openprice'] = $val['openpri'] != "--" ? $val['openpri'] : '0.00';
                            $gold_price['maxprice'] = $val['maxpri'] != "--" ? $val['maxpri'] : '0.00';
                            $gold_price['minprice'] = $val['minpri'] != "--" ? $val['minpri'] : '0.00';
                            $val['limit'] = str_replace('%', '', $val['limit']);
                            $gold_price['rose'] = $val['limit'] != "--" ? $val['limit'] : '0.00';
                            $gold_price['yesprice'] = $val['yespri'] != "--" ? $val['yespri'] : '0.00';
                            $gold_price['totalvol'] = $val['totalvol'] != "--" ? $val['totalvol'] : '0.00';
                            $gold_price['time'] = strtotime($val['time']);
                            $gold_price['cat_id'] = $gold_category['id'];
                            $gold_price['api_type']=0;//聚合api类型值
                            $gold_price['insert_time'] = time();
                            $goldmakid = $model_gold_mak_new->insert($gold_price);
                            //$goldmakid = $model_gold_mak->insert($gold_price);
                            $api_type=$model_option->getGoldTypeSwitch();
                            //同步更新到实际在用的金价表中
                            if ($is_open == 1&&$api_type==0) {
                                if (in_array($gold_category['id'], $gold_relation)) {
                                    $option = $model_option->getInfo(array('option_name' => 'gold_cate' . $gold_category['id']));
                                    $expression = $option['option_value'];
                                    $price = $val['latestpri'] != "--" && !empty($val['latestpri']) ? $val['latestpri'] : $val['yespri'];
                                    if ($gold_category['name'] == 'Ag99.99') {
                                        $price = $price / 1000;
                                    }
                                    $expression = str_replace("price", (float)$price, $expression);
                                    eval("\$price=" . $expression . ";");
                                    $insert = array(
                                        'price' => $price,
                                        'create_time' => time(),
                                        'user_id' => 0,
                                        'cat_id' => $gold_category['id']
                                    );
                                    $model_gold->insert($insert);
                                    @D("GoldPrice")->get_business_gold_price($gold_category['id'],$price);
                                }
                            }
                        }
                    }
                } else {
                    echo $result['error_code'] . ":" . $result['reason'];
                }
            } else {
                echo "请求失败";
            }
        }
    }
    /**
     * 获取nowapi的金价数据
     */
    public function get_nowapi_golddata(){
        $model_gold=D('a_gold');
        $model_gold_cat = D('a_gold_category');
        $model_gold_mak = D('a_gold_market');
        $model_gold_mak_new = D('gold_market_new');
        $model_option = D('a_options');
        $result = $this->get_gold_price();
        $weekday = date('w');//星期几
        $day = date('Y-m-d', time());
        $time = time();
        $day_9 = strtotime($day) + 9 * 3600;//每天9点
        $day_11 = strtotime($day) + 11.55 * 3600;//每天11点半
        $day_13 = strtotime($day) + 13.5 * 3600;//每天13点半
        $day_15 = strtotime($day) + 15.55 * 3600;//每天15点半
        $day_16 = strtotime($day) + 16.5 * 3600;//每天16点半
        $day_17 = strtotime($day) + 17 * 3600;//每天17点半
        $day_20 = strtotime($day) + 20 * 3600;//每天16点半
        $day_02 = strtotime($day) + 2.5 * 3600;//每天2点半
        $date=date("H:i",time());
        $date=explode(":",$date);
        $appkey='f6ba99f6ced6cff480e6c3afc3db3d45'; //*/2 * * * *  curl mt.gold-banker.cn/index.php?g=System\&m=crontab\&a=get_nowapi_golddata
        if($date[0]>=20){
            $condition=($weekday != '6' && $weekday != '0');
        }else {
            $condition = (((($time > $day_9 && $time < $day_11) || ($time < $day_15 && $time > $day_13)||$time < $day_02) && $weekday != '6' && $weekday != '0')||($weekday == '6'&&$time < $day_02));
        }
        //file_put_contents('/run.log', date('Y-m-d H:i:s')."\r\n", FILE_APPEND);
        if ($result != false&&$condition) {
            $open_info = $model_option->getGoldSwitch();
            $is_open = $open_info['option_value'];
            $gold_relation_cate = $model_option->getGoldRelationCatid();
            $gold_relation = explode(',', $gold_relation_cate['option_value']);
            if ($result) {
                $g_list = $result;
                foreach ($g_list as $key => $val) {
                    $gold_category = $model_gold_cat->getInfo(array('status' => 1, 'id' => $val['goldid']));
                    if (!empty($gold_category)) {
                        $gold_price = array();
                        $gold_price['latestprice'] = $val['last_price'] != "--" ? $val['last_price'] : '0.00';
                        $gold_price['openprice'] = $val['open_price'] != "--" ? $val['open_price'] : '0.00';
                        $gold_price['maxprice'] = $val['high_price'] != "--" ? $val['high_price'] : '0.00';
                        $gold_price['minprice'] = $val['low_price'] != "--" ? $val['low_price'] : '0.00';
                        $val['change_margin'] = str_replace('%', '', $val['change_margin']);
                        $val['change_margin']=$val['change_price']<0?('-'.$val['change_margin']):$val['change_margin'];
                        $gold_price['rose'] = $val['change_margin'] != "--" ? $val['change_margin'] : '0.00';
                        $gold_price['yesprice'] = $val['yesy_price'] != "--" ? $val['yesy_price'] : '0.00';
                        $gold_price['totalvol'] = !empty($val['totalvol'])&&$val['totalvol']!= "--" ? $val['totalvol'] : '0.00';
                        $gold_price['time'] = strtotime($val['uptime']);
                        $gold_price['cat_id'] = $gold_category['id'];
                        $gold_price['insert_time'] = time();
                        $gold_price['api_type']=1;//nowapi类型值
                        $goldmakid = $model_gold_mak_new->insert($gold_price);
                        //$goldmakid = $model_gold_mak->insert($gold_price);
                        $api_type=$model_option->getGoldTypeSwitch();
                        //同步更新到实际在用的金价表中
                        if ($is_open == 1&&$api_type==1) {
                            if (in_array($gold_category['id'], $gold_relation)) {
                                $option = $model_option->getInfo(array('option_name' => 'gold_cate' . $gold_category['id']));
                                $expression = $option['option_value'];
                                $price = $val['last_price'] != "--" && !empty($val['last_price']) ? $val['last_price'] : $val['yespri'];
                                if ($gold_category['name'] == 'Ag99.99') {
                                    $price = $price / 1000;
                                }
                                $expression = str_replace("price", (float)$price, $expression);
                                eval("\$price=" . $expression . ";");
                                $insert = array(
                                    'price' => $price,
                                    'create_time' => time(),
                                    'user_id' => 0,
                                    'cat_id' => $gold_category['id']
                                );
                                $model_gold->insert($insert);
                                //新增b端金属价格关联
                                @D("GoldPrice")->get_business_gold_price($gold_category['id'],$price);
                            }
                        }
                    }
                }
            } else {
                echo "请求失败";
            }
        }
    }
    private function get_gold_price(){
        $nowapi_parm['app'] = 'finance.shgold';
        // $nowapi_parm['apiurl']='https://sapi.k780.com';
        $nowapi_parm['version']='2';
        $nowapi_parm['appkey'] = '28854';
        $nowapi_parm['sign'] = 'e1b50ca227dbf36cdbae43ac67492ed1';
        $nowapi_parm['format'] = 'json';
        $result = $this->nowapi_call($nowapi_parm);
        $api_config=include NOWAPI_PATH.'nowapi_config.php';
        foreach ($result as $key => $val){
            $result[$key]['goldid']=$api_config[$val['goldid']];
        }
        return $result;
    }
    
    /**
     * nowapi接口
     */
    private function nowapi_call($a_parm){
        if (!is_array($a_parm)) {
            return false;
        }
    
        $a_parm['format'] = empty($a_parm['format']) ? 'json' : $a_parm['format'];
        $apiurl = empty($a_parm['apiurl']) ? 'http://api.k780.com:88/' : $a_parm['apiurl'] . '/';
        unset($a_parm['apiurl']);
        $params = http_build_query($a_parm);
        $callapi = juhecurl($apiurl, $params);
        if (!$callapi) {
            return false;
        }
        //format
        if ($a_parm['format'] == 'base64') {
            $a_cdata = unserialize(base64_decode($callapi));
        } elseif ($a_parm['format'] == 'json') {
            if (!$a_cdata = json_decode($callapi, true)) {
                return false;
            }
        } else {
            var_dump($callapi);
            return false;
        }
        //array
        if ($a_cdata['success'] != '1') {
            echo $a_cdata['msgid'] . ' ' . $a_cdata['msg'];
            return false;
        }
        return $a_cdata['result'];
    }

    //定时查询集金号数据写入系统
    function jjh_gold_price(){
        $goldprice=D("System/GoldPrice");
        $result=$goldprice->insert_gold();
        var_dump($result);
    }
    function test_gold_price(){
        D("GoldPrice")->get_business_gold_price(7,278);
    }
} 