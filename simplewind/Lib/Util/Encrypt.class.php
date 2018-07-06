<?php
/*
 * @author 林志远
 * 2017-06-12 9:30
 *
 */
class Encrypt {
    
    private $_secrect_key;
    
    public function __construct($secrect_key = '')
    {
        $this->setSecrectKey($secrect_key);
    }

    /**
     * 设定秘钥
     * @param string $secrect_key
     */
    public function setSecrectKey($secrect_key = '')
    {
        $this->_secrect_key = empty($secrect_key) ? C('ENCRYPT_KEY') : $secrect_key;
        return $this;
    }
    
    /**
     * 加密方法
     * @param string $str
     * @return string
     */
    public function encrypt($str)
    {
        //AES, 128 ECB模式加密数据
        $screct_key = $this->_secrect_key;
        $screct_key = base64_encode($screct_key);
        $str = trim($str);
        $str = $this->addPKCS7Padding($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_ECB),MCRYPT_RAND);
        $encrypt_str = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     * 
     * @param string $str            
     * @return string
     */
    public function decrypt($str)
    {
        // AES, 128 ECB模式加密数据
        $screct_key = $this->_secrect_key;
        $str = base64_decode($str);
        $screct_key = base64_encode($screct_key);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $encrypt_str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        $encrypt_str = trim($encrypt_str);
        $encrypt_str = $this->stripPKSC7Padding($encrypt_str);
        return $encrypt_str;
    }

    /**
     * 填充算法
     * 
     * @param string $source            
     * @return string
     */
    function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $source .= str_repeat(chr($pad), $pad);
        }
        return $source;
    }

    /**
     * 移去填充算法
     * 
     * @param string $source
     * @return string
     */
    function stripPKSC7Padding($source)
    {
        $source = trim($source);
        $char = substr($source, - 1);
        $num = ord($char);
        if ($num == 125) return $source;
        $source = substr($source, 0, -substr_count($source, $char));
        return $source;
    }
}

