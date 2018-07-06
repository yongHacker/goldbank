<?php
/**
 * HttpCurl Curl模拟Http工具类
 *
 * @author      gaoming13 <gaoming13@yeah.net>
 * @link        https://github.com/gaoming13/wechat-php-sdk
 * @link        http://me.diary8.com/
 */

namespace Org\UcSdk\Utils;

class HttpCurl {

    /**
     * 模拟GET请求
     *
     * @param string $url
     * @param string $data_type
     * @param array $header
     *
     * @return mixed
     * 
     * Examples:
     * ```   
     * HttpCurl::get('http://api.example.com/?a=123&b=456', 'json');
     * ```               
     */
    static public function get($url, $data_type='text', $header = array())
    {
        $cl = curl_init();
        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1 );
        if (!empty($header)) {
            $headers = array();
            foreach ($header as $key => $value) {
                // $key 定义规则：首字母以及'-'后首字母 大写 
                $headers[] = $key . ': ' . $_SERVER['HTTP_USER_AGENT'];
            }
            if (!empty($headers)) {
                curl_setopt($cl, CURLOPT_HTTPHEADER, $headers);
            }
        }
        $content = curl_exec($cl);
        $status = curl_getinfo($cl);
        curl_close($cl);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($data_type == 'json') {
                $content = json_decode($content);
            }
            return $content;
        } else {
            return FALSE;
        }        
    }

    /**
     * 模拟POST请求
     *
     * @param string $url
     * @param array $fields
     * @param string $data_type
     * @param array $header
     *
     * @return mixed
     * 
     * Examples:
     * ```   
     * HttpCurl::post('http://api.example.com/?a=123', array('abc'=>'123', 'efg'=>'567'), 'json');
     * HttpCurl::post('http://api.example.com/', '这是post原始内容', 'json');
     * 文件post上传
     * HttpCurl::post('http://api.example.com/', array('abc'=>'123', 'file1'=>'@/data/1.jpg'), 'json');
     * ```               
     */
    static public function post($url, $fields, $data_type = 'text', $header = array())
    {
        $cl = curl_init();
        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($cl, CURLOPT_POST, true);        
        curl_setopt($cl, CURLOPT_POSTFIELDS, $fields);
        if (!empty($header)) {
            $headers = array();
            foreach ($header as $key => $value) {
                // $key 定义规则：首字母以及'-'后首字母 大写 
                $headers[] = $key . ': ' . $_SERVER['HTTP_USER_AGENT'];
            }
            if (!empty($headers)) {
                curl_setopt($cl, CURLOPT_HTTPHEADER, $headers);
            }
        }
        $content = curl_exec($cl);
        $status = curl_getinfo($cl);
        curl_close($cl);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($data_type == 'json') {
                $content = json_decode($content);
            }
            return $content;
        } else {
            return FALSE;
        }
    }
}