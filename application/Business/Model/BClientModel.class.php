<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BClientModel extends BCommonModel{

    function add_post($url) {
        $postdata = I("post.");
        $status=array();
        $status["status"]=0;
        $status["msg"]="添加失败！";
        $_POST['user_login'] = $postdata["mobile"];
        
        $condition = array("mobile"=> $postdata["mobile"]);
        if (empty($postdata["mobile"])) {
            $status["msg"]="手机号不能为空！";
            return $status;
        }

        $mobile_area = $postdata["mobile_area"] ? $postdata["mobile_area"] : 1;
        if(!check_mobile($postdata["mobile"], $mobile_area)){
            $status["msg"] = "手机号格式有误！";
            return $status;
        }

        $userinfo = D("MUsers")->getInfo($condition, $field = "id", $join = "");
        $bclient_data = array();
        $bclient_data['company_id'] = get_company_id();
        $bclient_data['shop_id'] = empty($postdata['shop_id'])?get_shop_id():$postdata['shop_id'];
        $bclient_data['client_name'] = $postdata["user_nicename"];
        $bclient_data['client_idno'] = $postdata["client_idno"];
        $bclient_data['client_moblie'] = $postdata["mobile"];
        $bclient_data['mobile_area'] = $mobile_area;
        $bclient_data["sex"] = $postdata["sex"];
        $bclient_data['deleted'] = 0;
        $bclient_data['creator_id'] = get_user_id();
        $bclient_data['create_time'] = time();
        unset($_POST['shop_id']);
        unset($_POST['client_idno']);
        M()->startTrans();
        
        if (empty($userinfo)) {
            $_POST['user_pass'] = $this->default_password;
            if (D("MUsers")->create() !== false) {
                $data = array();
                $data['company_id'] = get_company_id();
                $data["user_login"] = $postdata["mobile"];
                $data["mobile"] = $postdata["mobile"];
                $data["mobile_area"] = $mobile_area;
                $data["user_pass"] = $this->default_password;
                $data["user_nicename"] = $postdata["user_nicename"];
                $data["create_time"] = time();
                $data["sex"] = $postdata["sex"];
                $data["operate_user_id"] = get_user_id();
                $data["operate_ip"] = get_client_ip(0, true);
                $result = D("MUsers")->insert($data);
                if ($result !== false) {
                    $bclient_data['user_id'] = $result;
                    $bclient = $this->insert($bclient_data);
                    if ($bclient !== false) {
                        M()->commit();
                        $status["msg"]="添加成功！";
                        $status["status"]=1;
                        return $status;
                    } else {
                        M()->rollback();
                        return $status;
                    }
                } else {
                    return $status;
                }
            } else {
                $status["msg"]=D("MUsers")->getError();
                return $status;
            }
        } else {
            $condition = array("user_id" => $userinfo['id'], "company_id" => get_company_id(), "deleted" => 0);
            $bclientinfo = $this->alias("bclient")->getInfo($condition, $field = "id");
            if (!empty($bclientinfo)) {
                $status["msg"]="客户信息已经存在,请勿重复添加！";
                return $status;
            }
            if ($this->create() !== false) {
                $bclient_data['user_id'] = $userinfo['id'];
                $bclient = $this->insert($bclient_data);
                if ($bclient !== false) {
                    M()->commit();
                    $status["msg"]="添加成功！";
                    $status["status"]=1;
                    return $status;
                } else {
                    M()->rollback();
                    return $status;
                }
            } else {
                $status["msg"]=$this->getError();
                return $status;
            }
        }
    }

    // 根据员工id创建客户
    public function addByEmployee($employee_id = 0)
    {
        $employee_id = empty($employee_id) ? I('employee_id/d', 0) : $employee_id;
        if (empty($employee_id)) return false;
        // 员工信息
        $condition = array(
            'id' => $employee_id,
            'deleted' => 0
        );
        $employee_info = D('BEmployee')->getInfo($condition);
        // 判断客户信息是否存在
        $condition = array(
            'user_id' => $employee_info['user_id'],
            'company_id' => get_company_id(),
            'deleted' => 0
        );
        $client_info = $this->getInfo($condition, $field = 'id');
        if (!empty($client_info)) {
            return $client_info['id'];
        }
        // 用户信息
        $condition = array(
            'id' => $employee_info['user_id'],
            'deleted' => 0
        );
        $user_info = D('MUsers')->getInfo($condition);
        // 当前登录状态下用户员工id
        $condition = array(
            'company_id' => get_company_id(),
            'user_id' => get_user_id(),
            'deleted' => 0
        );
        $res = D('BEmployee')->getInfo($condition);
        $employee_id = $res['id'];

        $shop_id = empty(I('shop_id/d', 0)) ? $employee_info['shop_id'] : I('shop_id/d', 0);
        if (empty($shop_id)) {
            $order = I('order');
            $shop_id = empty($order['shop_id']) ? $shop_id : $order['shop_id'];
        }
        $data = array(
            'company_id' => $employee_info['company_id'],
            'shop_id' => $shop_id,
            'user_id' => $employee_info['user_id'],
            'client_name' => $employee_info['employee_name'],
            'client_moblie' => $employee_info['employee_mobile'],
            'employee_id' => $employee_id,
            'creator_id' => get_user_id(),
            'sex' => empty($user_info['sex']) ? 0 : $user_info['sex'],
            'create_time' => time(),
            'deleted' => 0
        );
        $bclient = $this->insert($data);
        return $bclient;
    }
}
