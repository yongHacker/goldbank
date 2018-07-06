<?php

/*
 * @author 林志远
 * 2018-1-16 14:30
 *
 */
class GoldPrice{
    
    private $url;     //请求的地址
    private $data;    //请求的参数
    private $header;  //请求头文件
    
    public function __construct($type,$header,$url,$data){
        $this->_init($type,$header,$url,$data);
    }
    /**
     * @author lzy 2018-1-16
     * 初始化金价类 
     */
    private function _init($type='',$header=array(),$url='',$data=array()){
        if(!empty($data)){
            $this->data=$data;
        }else{
            $this->data=array(
                'codes'=>'JO_92233',
                //'isCalc'=>'true', //true以克计算  false:以盎司计算
               // 'codes'=>'JO_38493,JO_111',
                /*'codes'=>'JO_60376,JO_9754,JO_9753,JO_38493,JO_38496,JO_42757,JO_111,JO_9833,JO_357,JO_429,JO_42758,JO_60374,JO_59811,JO_56382,JO_92234,JO_92236,JO_1843,JO_2008,JO_2516,JO_2881,JO_263,JO_264,JO_265,JO_266,JO_267,JO_268,JO_269,JO_270,JO_271,JO_272,JO_273,JO_274,JO_275,JO_276,JO_277,JO_278,JO_279,JO_280,JO_281,JO_282,JO_87,JO_92235,JO_12568,JO_60855,JO_60851,JO_60850,JO_60849,JO_60847,JO_60862,JO_56373,JO_56376,JO_56379,JO_42226,JO_56375,JO_42258,JO_92240,JO_57235',*/
                /*'currentPage'=>1,
                'pageSize'=>16,*/
                '_'=>ceil((time()+microtime())*1000),
            );
        }
        if(!empty($url)){
            $this->url=$url;
        }else{
            $this->url='https://api.jijinhao.com/realtime/quotejs.htm?'.http_build_query($this->data);
            if($type=='realtime'){
                $this->url='https://api.jijinhao.com/quoteCenter/realTime.htm?'.http_build_query($this->data);
            }
            if($type=='srealtime'){
                $this->url='https://api.jijinhao.com/sQuoteCenter/realTime.htm?'.http_build_query($this->data);
            }

        }
        if(!empty($header)){
            $this->header=$header;
        }else{
            $this->header=array(
                "GET ".$this->url." HTTP/1.1",
                "Host: api.jijinhao.com",
                "Connection: keep-alive",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36",
                "Accept: */*",
                "Referer: http://www.cngold.org/",
                "Accept-Encoding: gzip, deflate, br",
                "Accept-Language: zh-CN,zh;q=0.8",
            );
        }
    }
    /**
     * @author lzy 2018-1-16
     * curl获取数据
     */
    public function getdata(){
        $ch=curl_init($this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);//设置报文头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $ret = curl_exec($ch); // 运行curl
//         die(var_dump($ret));
        curl_close($ch);
        $ret=gzdecode($ret);
        $ret=explode('=', $ret);
        $json_data=$ret[1];
        $json_array=json_decode($json_data,true);
        $data=$json_array['0']['data'];
        return $data;
    }
    public function getRealTimeData(){
        $ch=curl_init($this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);//设置报文头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $ret = curl_exec($ch); // 运行curl
        curl_close($ch);
        $ret=gzdecode($ret);
        $ret=explode('=', $ret);
        $json_data=$ret[1];
        $json_array=json_decode($json_data,true);
        return $json_array;
    }
    public function getsRealTimeData(){
        $ch=curl_init($this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);//设置报文头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $ret = curl_exec($ch); // 运行curl
        curl_close($ch);
        $ret=gzdecode($ret);
        $ret=explode('=', $ret);
        $data=explode(',', $ret[1]);
       // $json_array=json_decode($json_data,true);die(var_dump($json_array));
       // $data=$json_array['0']['data'];
        return $data;
    }
}
