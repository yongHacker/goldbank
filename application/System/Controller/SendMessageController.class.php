<?php
namespace System\Controller;
use Common\Controller\HomebaseController;
class SendMessageController extends HomebaseController{
	private $sms;
    public function __construct(){
        parent::__construct();
		$this->sms= M('a_sms')->where("is_effect=1 and deleted=0")->find();
    }
	
	public function sendOrderSMS($mobiles,$content,$type=0){
		$options= M('a_options')->where("option_name='mobile_open'")->find();
		if(empty($this->sms)||$options["option_value"]==0){
			$error["status"]=0;
			$msgdata["code"]="";
			$msgdata["is_ok"] = 0;
			$msgdata["create_time"]=time();
			$msgdata["info"]="没有可用的短信接口或短信关闭";
			$msgdata["content"]=$content;
			$msgdata["type"]=$type;
			$msgdata["mobile"]=$mobiles;
			M("a_sms_log")->add($msgdata);
			return $msgdata;
		}
		$count_limit = $this->count_limit($mobiles, $content, $type);
		if ($count_limit['is_ok'] == 0) {
			return $count_limit;
		}
		$options= M('a_options')->where("option_name='mobile_limit'")->find();
		$sms_mobile="";
		//判断是否开启名单限制
		if($options["option_value"]>0){
			$map["mobile"]=$mobiles;
			$map["type"]=$options["option_value"];
			$sms_mobile= M('a_sms_mobile')->where($map)->find();
			
			if($map["type"]==2){
				if($sms_mobile){//判断是否黑名单
					$msgdata["code"]="";
					$msgdata["is_ok"] = 0;
					$msgdata["create_time"]=time();
					$msgdata["info"]="黑名单手机号";
					$msgdata["content"]=$content;
					$msgdata["type"]=$type;
					$msgdata["mobile"]=$mobiles;
					M("a_sms_log")->add($msgdata);
					//print_r($data);
					return $msgdata;
				}else{
					$data=$this->ipyy_SendOrderSMS($mobiles,$content);
					$msgdata["is_ok"] = $data["is_ok"];
					$msgdata["code"]=$data["code"];
					$msgdata["create_time"]=time();
					$msgdata["info"]=$data["info"];
					$msgdata["content"]=$content;
					$msgdata["type"]=$type;
					$msgdata["mobile"]=$mobiles;
					M("a_sms_log")->add($msgdata);
					//print_r($data);
					return $data;
				}
				
			}
			
			if($map["type"]==1){
				if($sms_mobile){//判断是否白名单
					$data=$this->ipyy_SendOrderSMS($mobiles,$content);
					$msgdata["is_ok"] = $data["is_ok"];
					$msgdata["code"]=$data["code"];
					$msgdata["create_time"]=time();
					$msgdata["info"]=$data["info"];
					$msgdata["content"]=$content;
					$msgdata["type"]=$type;
					$msgdata["mobile"]=$mobiles;
					M("a_sms_log")->add($msgdata);
					//print_r($data);
					return $data;
				}else{
					$msgdata["code"]="";
					$msgdata["is_ok"] = 0;
					$msgdata["create_time"]=time();
					$msgdata["info"]="该手机号不在白名单";
					$msgdata["content"]=$content;
					$msgdata["type"]=$type;
					$msgdata["mobile"]=$mobiles;
					M("a_sms_log")->add($msgdata);
					//print_r($data);
					return $msgdata;
				}
			}
		}else{
			//$mobiles="18079147093";
			//$content="【永坤金行家】尊敬的用户，感谢您购买永坤金行家的产品，订单号为：170111000001";
			//$content="【永坤金行】".$content;
			$data=$this->ipyy_SendOrderSMS($mobiles,$content);
			$msgdata["is_ok"] = $data["is_ok"];
			$msgdata["code"]=$data["code"];
			$msgdata["create_time"]=time();
			$msgdata["info"]=$data["info"];
			$msgdata["content"]=$content;
			$msgdata["type"]=$type;
			$msgdata["mobile"]=$mobiles;
			M("a_sms_log")->add($msgdata);
			//print_r($data);
			return $data;
			
		}


	}

	public function count_limit($mobiles, $content, $type) {
		$today = strtotime(date('Y-m-d'));
		$condition = array();
		$condition["mobile"] = $mobiles;
		$condition["create_time"] = array("gt", $today);
		$condition["is_ok"] = 1;
		$count = M('a_sms_log')->where($condition)->count();
		if ($count >= 20) {
			$error["status"] = 0;
			$msgdata["code"] = "";
			$msgdata["is_ok"] = 0;
			$msgdata["create_time"] = time();
			$msgdata["info"] = "短信条数使用已经到达上限，明天才可以使用";
			$msgdata["content"] = $content;
			$msgdata["type"] = $type;
			$msgdata["mobile"] = $mobiles;
			//M("sms_log")->add($msgdata);
			return $msgdata;
		} else {
			$msgdata["is_ok"] = 1;
			return $msgdata;
		}
	}
	
    private function ipyy_SendOrderSMS($mobiles,$content){
        $username=$this->sms["account"];							//改为实际账户名
		$password=strtoupper(md5($this->sms["password"]));							//改为实际短信发送密码
		
		//$content="【金行家】尊敬的用户，您的验证码是12345请在五分钟之内填写，如非本人操作请勿理会。";
		$extnumber="";
		$plansendtime='';						//定时短信发送时间,格式 2016-06-06T06:06:06，null或空串表示为非定时短信(即时发送)
		//$plansendtime='2016-06-06T06:06:06'
		//define('SITE_PATH', dirname(__FILE__)."/");
		//define('SPAPP_PATH',   SITE_PATH.'simplewind/');
		require_once SPAPP_PATH."/Core/Library/Org/Util/MessageSend.class.php";  //引入接口
		//var_dump(SPAPP_PATH."/Core/Library/Org/Util/MessageSend.class.php");
		//import("ORG.Util.MessageSend");
		$MessageSend=new \MessageSend();
		$result = $MessageSend->send($username, $password, $mobiles, "【金行家】" . $content, $extnumber, $plansendtime);
		//$client=new SoapClient("https://dx.ipyy.net/webservice.asmx?wsdl");
		//print_r($result);
		
		if($result==null)
		{
			$error["info"]="短信接口请求失败";
			$error["status"]=0;
			return $error;
		}
		else
		{
			//print_r($result);
			//echo "返回信息提示：",$result->Description,"\n";
			//echo "返回状态为：",$result->StatusCode,"\n";
			//echo "返回条数余额：",$result->Amount,"\n";
			//echo "返回本次任务ID：",$result->MsgId,"\n";
			//echo "返回成功短信数：",$result->SuccessCounts,"\n";
			$error["info"]=$result->Description;
			$error["status"]=1;
			$error["amount"]=$result->Amount;
			$error["code"]=$result->StatusCode;
			if (strtoupper($error["code"]) == "OK") {
				$error["is_ok"] = 1;
			} else {
				$error["is_ok"] = 0;
			}
			$error["count"]=$result->SuccessCounts;//"返回成功短信数
			return $error;
		}
        
    }
}