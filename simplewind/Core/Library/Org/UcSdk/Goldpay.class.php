<?php
namespace Org\UcSdk;

use Org\UcSdk\Utils\HttpCurl;
use Org\UcSdk\Utils\Error;
use Org\UcSdk\Utils\SHA1;
use Org\UcSdk\Utils\Xml;
/*
 * @author chenzy
 * 2018-01-04 9:30
 *
 */
class Goldpay {
    // 微信用户中心域名
    protected $api_domain ='';
    // 开发者中心-配置项-AppID(应用ID)
    protected $appid;
    // 开发者中心-配置项-AppSecret(应用密钥)
    protected $encrypt_key;
    // 加密解密类加载
    protected $encrypt;
    /** @var callable $get_access_token_diy 用户自定义获取access_token的方法 */
    protected $get_access_token_diy;
    /** @var callable $save_access_token_diy 用户自定义保存access_token的方法 */
    protected $save_access_token_diy;

    public function __construct($config) {
        $this->api_domain=empty($config['api_domain']) ? C('API_DOMAIN') : $config['api_domain'];
        $this->appid                    =empty($config['appid']) ? C('APP_ID') : $config['appid'];
        $this->encrypt_key				 =empty($config['secrect_key']) ? C('ENCRYPT_KEY') : $config['secrect_key'];
        $this->encrypt 					= new \Encrypt($this->encrypt_key);
        $this->get_access_token_diy     =   isset($config['get_access_token']) ? $config['get_access_token'] : false;
        $this->save_access_token_diy    =   isset($config['save_access_token']) ? $config['save_access_token'] : false;
    }

    /**
     * 获取access_token
     *
     * @return string
     */
    public function get_access_token()
    {
        $token = false;
        if ($this->get_access_token_diy !== false) {
            // 调用用户自定义获取AccessToken方法
            $token = call_user_func($this->get_access_token_diy);
            if ($token) {
                $token = json_decode($token);
            }
        } else {
            // 异常处理: 获取access_token方法未定义
            @error_log('Not set get_tokenDiy method, AccessToken will be refreshed each time.', 0);
        }
        // 验证AccessToken是否有效
        if (!$this->valid_access_token($token)) {
            // 生成新的AccessToken
            $token = $this->new_access_token();
            if ($token === false) {
                return false;
            }

            // 保存新生成的AccessToken
            if ($this->save_access_token_diy !== false) {
                // 用户自定义保存AccessToken方法
                call_user_func($this->save_access_token_diy, json_encode($token));
            } else {
                // 异常处理: 保存access_token方法未定义
                @error_log('Not set saveTokenDiy method, AccessToken will be refreshed each time.', 0);
            }
        }
        return $token->access_token;
    }

    /**
     * 校验access_token是否过期
     *
     * @param string $token
     *
     * @return bool
     */
    public function valid_access_token($token)
    {
        //接口最高有效时间为7200，所以设置有效时间6000，避免失效
        return $token && isset($token->expires_in) && ($token->expires_in > time() + 1200);
    }

    /**
     * 生成新的access_token
     *
     * @return mixed
     */
    public function new_access_token()
    {
        $url = $this->api_domain. 'index.php?g=Api&m=Public&a=get_access_token';
        $field=array("app_id"=>$this->appid);
        $res = HttpCurl::post($url,$field);
        //判断是json还是加密字符串，最终转为json字符串
        $res_json=is_array(json_decode($res, true))?$res:$this->encrypt->decrypt($res);
        $res_json=json_decode($res_json);
        // 异常处理: 获取access_token网络错误
        if ((INT)$res_json->code!=200) {
            @error_log('Http Get AccessToken Error.'. json_encode($res_json), 0);
            return false;
        }
        // 异常处理: access_token获取失败
        if (!isset($res_json->data->access_token)) {
            @error_log('Get AccessToken Error: ' . json_encode($res_json), 0);
            return false;
        }
        $time=time()+7200;
        $res_json->expires_in = $time;
        $res_json->access_token= $res_json->data->access_token;
        unset($res_json->data);
        return $res_json;
    }
    //获取用户唯一标识openid
    public function get_uc_openid($data){
        $is_exist=$this->check_user($data);
        if($is_exist===false){
            @error_log('Http check_user Error.',0);
            return false;
        }
        $type=$is_exist==1?"location":"register";
        $url = $this->api_domain. 'index.php?g=Api&m=Client&a=operate_client';
        $field = array(
            "access_token"=>$this->get_access_token(),
            "mobile"=>$data["mobile"],
            "password"=>$data["uc_password"],
            "type"=>$type
        );

        if(isset($data['user_login'])){
            $field['user_login'] = $data['user_login'];
        }

        if(isset($data['user_nicename'])){
            $field['nickname'] = $data['user_nicename'];
        }

        if(isset($data['user_email'])){
            $field['user_email'] = $data['user_email'];
        }

        $datas["data"]=$this->encrypt->encrypt(json_encode($field));
        $datas["app_id"]=$this->appid;
        $res = HttpCurl::post($url, $datas);
        $res_json=is_array(json_decode($res, true))?$res:$this->encrypt->decrypt($res);
        $res_json=json_decode($res_json);
        // 异常处理: 获取access_token网络错误
        if ((INT)$res_json->code!==200) {
            @error_log('Http Get openid Error.'. json_encode($res_json), 0);
            return false;
        }
        // 异常处理: openid获取失败
        if (!isset($res_json->data->openid)) {
            @error_log('Http Get openid Error.'. json_encode($res_json), 0);
            return false;
        }
        return $res_json->data->openid;
    }

    //检测用户中心用户是否存在
    public function check_user($data){
        $url = $this->api_domain. 'index.php?g=Api&m=Client&a=exist_client';
        $field=array("access_token"=>$this->get_access_token(),"mobile"=>$data["mobile"]);
        $datas["data"]=$this->encrypt->encrypt(json_encode($field));
        $datas["app_id"]=$this->appid;
        $res = HttpCurl::post($url, $datas);
        $res_json=is_array(json_decode($res, true))?$res:$this->encrypt->decrypt($res);
        $res_json=json_decode($res_json);
        // 异常处理: 获取access_token网络错误
        if ((INT)$res_json->code!=200) {
            @error_log('Http Get openid Error.'. json_encode($res_json), 0);
            return false;
        }
        return (INT)$res_json->data->is_exist;
    }
}

