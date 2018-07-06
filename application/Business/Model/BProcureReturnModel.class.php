<?php
/**
 * @author lzy 2018.6.1 15:00
 * 采购退货model
 */
namespace Business\Model;

use Business\Model\BCommonModel;

class BProcureReturnModel extends BCommonModel
{
    /*操作状态值 */
    const CREATE              =  1;//创建表单
    const SAVE                =  2;//保存表单
    const COMMIT              =  3;//提交表单
    const REVOKE              =  4;//撤销表单
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝
    const CHECK_REJECT        =  7;//提交驳回
    const UPLOAD_IMG          =  8;//上传凭证
    
    /*操作状态名*/
    const CREATE_NAME         =  '创建表单';
    const SAVE_NAME           =  '保存表单';
    const COMMIT_NAME         =  '提交表单';
    const REVOKE_NAME         =  '撤销表单';
    const CHECK_PASS_NAME     =  '审批通过';
    const CHECK_DENY_NAME     =  '审批拒绝';
    const CHECK_REJECT_NAME   =  '提交驳回';
    const UPLOAD_IMG_NAME     =  '上传凭证';
    
    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const UPLOAD_PROKEY       =  3;//上传凭证流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';
    const UPLOAD_PROCESS      =  '上传凭证';
    
    /*操作函数名*/
    const CHECK_FUNCTION      =  'business/bprocurereturn/check';
    const UPLOAD_FUNCTION     =  'business/bprocurereturn/upload_pic';

    public $model_procure_redatil, $model_product, $model_company_account_flow, $model_expence_sub;

    public function __construct()
    {   
        parent::__construct();
    }

    public function _initialize()
    {
        parent::_initialize();

        $this->model_procure_redatil = D('BProcureRedetail');
        $this->model_product = D('BProduct');
        $this->model_company_account_flow = D('BCompanyAccountFlow');
        $this->model_expence_sub = D('BExpenceSub');
    }

    /**
     * @author lzy 2018.06.04 保存一个采购退货单
     * @param array $data 保存到采购退货单中的数据
     * @param string $product_ids 货品的id值，逗号隔开
     * @param int $return_id 退货单的ID
     * @return Ambigous <string, boolean>|boolean|string
     */
    public function saveReturn($data, $product_ids, &$return_id)
    {
        // 检查退货单状态
        if (!empty($return_id)) {
            // 表单详情
            $condition = array('deleted' => 0, 'id' => $return_id);
            $return_info = $this->getInfo($condition);
            // 表单列表
            $condition = array('deleted' => 0, 'pr_id' => $return_id);
            $old_product_list = $this->model_procure_redatil->getList($condition);
            if (empty($return_info)) {
                // 根据id查询不到表单详情，新建表单
                unset($return_id);
            } else {
                if ($return_info['status'] >= 0 && $return_info['status'] != 3) {
                    return '采购退货单非可编辑状态！';
                }
                // 旧表单列表中的货品id
                $old_product_ids = array();
                foreach ($old_product_list as $key => $value) {
                    $old_product_ids[] = $value['p_id'];
                }
            }
        }

        $product_ids = explode(',', $product_ids);
        $result = $this->_check_product($product_ids, (INT)$data['status']);
        if ($result !== true) {
            return $result;
        }
        $wh_info = D('BWarehouse')->getInfo(array(
            'id' => $data['wh_id']
        ));
        if (empty($wh_info)) {
            return '门店不存在！';
        }

        $this->startTrans();

        $result = false;

        // 保存表单详情
        $procure_return_data = array(
            'company_id' => get_company_id(),
            'wh_id' => $data['wh_id'],
            'supplier_id' => $data['supplier_id'],
            'shop_id' => $wh_info['shop_id'] > 0 ? $wh_info['shop_id'] : 0,
            'batch' => b_order_number('BProcureReturn', 'batch'),
            'return_time' => strtotime($data['return_date']),
            'num' => $data['return_num'],
            'price' => $data['return_price'],
            'weight' => $data['return_weight'],
            'status' => $data['status'],
            'creator_id' => get_user_id(),
            'memo' => trim($data['memo'])
        );
        if (!$return_id) {
            $procure_return_data['create_time'] = time();
            $return_id = $this->insert($procure_return_data);
            $result = ($return_id === false) ? false : $return_id;
            if ($result) {
                $result = D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_RETURN, $return_id, SELF::CREATE);
            }
        } else {
            $return_update = $this->update(array('id' => $return_id), $procure_return_data);
            $result = ($return_update === false) ? false : $return_id;
            if ($result) {
                $result = D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_RETURN, $return_id, SELF::SAVE);
            }
        }

        if ($result !== false) {
            // 处理其它费用
            $result = $this->model_expence_sub->editList($return_id, 3);
        }

        if ($result !== false) {
            // 保存表单列表
            $insert_all_arr = array();
            foreach ($product_ids as $key => $val) {
                if (in_array($val, $old_product_ids)) {
                    unset($old_product_ids[array_search($val, $old_product_ids)]);
                } else {
                    $insert_all_arr[] = array(
                        'p_id' => $val,
                        'pr_id' => $return_id,
                        'deleted' => 0
                    );
                }
            }
            if (!empty($insert_all_arr)) {
                $result = $this->model_procure_redatil->insertAll($insert_all_arr);
            }
            // 删除旧的表单列表
            if ($result !== false && !empty($old_product_ids)) {
                $condition = array(
                    'pr_id' => $return_id,
                    'p_id' => array('in', $old_product_ids)
                );
                $result = $this->model_procure_redatil->update($condition, array('deleted' => 1));
            }

            // 仅当提交时修改货品状态
            if ((INT)$data['status'] == 0) {
                // 修改货品状态
                if ($result !== false) {
                    $condition = array(
                        'id' => array('in', $product_ids),
                        'status' => 2
                    );
                    $update = array(
                        'status' => '9'
                    );
                    $result = $this->model_product->update($condition, $update);
                }
                // 还原旧的表单列表中的货品状态
                if ($result !== false && !empty($old_product_ids)) {
                    $condition = array(
                        'id' => array('in', $old_product_ids),
                        'status' => 9
                    );
                    $update = array(
                        'status' => '2'
                    );
                    $result = $this->model_product->update($condition, $update);
                }
            }
        }

        if ($result !== false) {
            $this->commit();
            return true;
        } else {
            $this->rollback();
            return '操作失败！';
        }
    }

    /**
     * 获取退货单表单详情
     * @param  int $return_id 退货表单ID
     * @return string|array
     */
    public function getDetail($return_id)
    {
        $return_id = empty($return_id) ? I('return_id/d', 0) : $return_id;
        if (empty($return_id)) {
            return array();
        }
        
        // 表单详情
        $condition = array(
            'pr.deleted' => 0,
            'pr.id' => $return_id,
            'pr.company_id' => get_company_id(),
        );
        $field = 'wh.id, wh.wh_name, pr.*, be.employee_name creator_name, sp.company_name supplier_name, cbe.employee_name check_name';
        $join = 'LEFT JOIN __B_WAREHOUSE__ wh ON pr.wh_id = wh.id';
        $join .= ' LEFT JOIN __B_EMPLOYEE__ be ON pr.creator_id = be.user_id AND be.company_id = ' . get_company_id();
        $join .= ' LEFT JOIN __B_SUPPLIER__ sp ON pr.supplier_id = sp.id';
        $join .= ' LEFT JOIN __B_EMPLOYEE__ cbe on cbe.user_id = pr.creator_id AND cbe.company_id = ' . get_company_id();
        $return_info = $this->alias('pr')->getInfo($condition, $field, $join);

        // 表单列表详情
        if ($return_info) {
            $return_info['product_list'] = $this->model_procure_redatil->getProductList($return_id);
        }

        return $return_info;
    }

    // 采购退货单撤销
    public function cancel($return_id)
    {
        $info = array(
            'status' => 0,
            'msg' => '撤销失败！'
        );
        $return_id = empty($return_id) ? I('return_id/d', 0) : $return_id;
        if (empty($return_id)) {
            return $info;
        }
        
        M()->startTrans();
        
        $condition = array('id' => $return_id, 'company_id' => get_company_id());
        $data = array('status' => 3);
        $res = $this->update($condition, $data);

        if ($res) {
            // 修改货品状态
            $res = $this->_refresh_product_status($return_id, 2);
        }

        if ($res) {
            $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_RETURN, $return_id,SELF::REVOKE);
        }

        if ($res !== false) {
            M()->commit();
            return array(
                'status' => 1,
                'msg' => '撤销成功！'
            );
        } else {
            M()->rollback();
            return $info;
        }
    }

    // 采购退货单删除
    public function delete($return_id)
    {
        $info = array(
            'status' => 0,
            'msg' => '删除失败！'
        );
        $return_id = empty($return_id) ? I('return_id/d', 0) : $return_id;
        if (empty($return_id)) {
            return $info;
        }

        M()->startTrans();

        $condition = array('id' => $return_id, 'company_id' => get_company_id());
        $data = array('deleted' => 1);
        $res = $this->update($condition, $data);

        if ($res !== false) {
            M()->commit();
            return array(
                'status' => 1,
                'msg' => '删除成功！'
            );
        } else {
            M()->rollback();
            return $info;
        }
    }

    // 审核退货单
    public function check_return()
    {
        $return_id = I('return_id/d', 0);
        $status = I('status/d', 0);
        $check_memo = I('check_memo/s', '');
        $info = array(
            'status' => 0,
            'msg' => '操作失败！'
        );
        if (empty($status) && !in_array($status, array(-2, 1, 2))) {
            return $info;
        }
        // 退货单详情
        $condition = array('id' => $return_id, 'deleted' => 0, 'status' => 0, 'company_id' => get_company_id());
        $return_info = $this->getInfo($condition);
        if ($return_info['status'] != 0 || empty($return_info)) {
            $info['msg'] = '退货单状态不允许审核！';
            return $info;
        }

        M()->startTrans();
        $data = array('status' => $status);
        if ($status > 0) {
            $data['check_id'] = get_user_id();
            $data['check_time'] = time();
            $data['check_memo'] = trim($check_memo);
        }
        $result = $this->update($condition, $data);

        if ($result) {
            $operate_type = ($status == -2) ? (self::CHECK_REJECT) : (($status == 1) ? (self::CHECK_PASS) : (self::CHECK_DENY));
            $result = D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_RETURN, $return_id, $operate_type);
        }

        // 后续操作以及失败提示
        if ($status == -2) {
            // 驳回
            $info['msg'] = '驳回退货单失败！';
            if ($result) {
                // 修改货品状态
                $result = $this->_refresh_product_status($return_id, 2);
            }
        } elseif ($status == 2) {
            // 审核不通过
            $info['msg'] = '退货单审核不通过失败！';
            if ($result) {
                // 修改货品状态
                $result = $this->_refresh_product_status($return_id, 2);
            }
        } elseif ($status == 1) {
            // 审核通过
            $info['msg'] = '退货单审核通过失败！';
            if ($result) {
                // 修改货品状态
                $result = $this->_refresh_product_status($return_id, 10);
            }
            if ($result) {
                // 修改供应商结欠流水表
                $company_account_where = array(
                    'company_id'=> get_company_id(),
                    'supplier_id'=> $return_info['supplier_id'],
                    'deleted'=> 0
                );
                $company_account_info = D('Business/BCompanyAccount')->getInfo($company_account_where);
                if(empty($company_account_info)){
                    M()->rollback();
                    $info['msg'] = '结欠账户数据有误！';
                }
                $result = $this->model_company_account_flow->add_flow($company_account_info['id'], $return_info['id'], $return_info['weight'], $return_info['price'], 5, 3);
            }
            if ($result) {
                // 修改供应商结欠信息表信息
                $condition = array('id' => $company_account_info['id']);
                $data = array(
                    'weight' => bcsub($company_account_info['weight'], $return_info['weight'], 2),
                    'total_weight' => bcsub($company_account_info['total_weight'], $return_info['weight'], 2),
                    'price' => bcsub($company_account_info['price'], $return_info['price'], 2),
                    'total_price' => bcsub($company_account_info['total_price'], $return_info['price'], 2),
                );
                $result = D('Business/BCompanyAccount')->update($condition, $data);
            }
        }

        if ($result) {
            M()->commit();
            return array(
                'status' => 1,
                'msg' => '操作成功！'
            );
        } else {
            M()->rollback();
            return $info;
        }
    }

    // 上传凭证
    public function changeImg($return_id){
        $return_id = empty($return_id) ? I('post.return_id/d', 0) : $return_id;

        $condition = array(
            'id' => $return_id
        );
        $update = array(
            'payment_pic' => $this->get_payment_pic(),
            'upimg_id' => get_user_id(),
            'payment_time' => empty(I('post.payment_time')) ? '' : strtotime(I('post.payment_time')),
            'payop_time' => time(),
            'status' => 4
        );
        $this->startTrans();
        $result = $this->update($condition, $update);
        if($result){
            $result = D('BBillOpRecord')->addRecord(BBillOpRecordModel::PROCURE_RETURN, $return_id, self::UPLOAD_IMG);
        }
        if($result){
            $this->commit();
        }else{
            $this->rollback();
        }
        return result;
    }

    // 筛除删除的凭证
    private function get_payment_pic(){
        $upload_img_arr = I('upload_img_arr/s');
        $upload_img_arr = explode('|', $upload_img_arr);

        $remove_img_arr = I('remove_img_arr/s');
        $remove_img_arr = explode('|', $remove_img_arr);

        $upload_img_arr = array_diff($upload_img_arr, $remove_img_arr);

        foreach($remove_img_arr as $img){
            $dir_path = str_replace("http://".$_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT'], $img);
            @unlink($dir_path);
        }

        $payment_pic= implode('|', $upload_img_arr);
        return $payment_pic;
    }

    /**
     * @author lzy 2018-6-4 检测退款货品中是否包含重复和不在库的货品
     * @param string $product_ids
     * @param int    $status            退货单操作类型 仅提交时判断货品状态
     * @return string|boolean
     */
    private function _check_product($product_ids, $status = -1)
    {
        foreach ($product_ids as $key => $val) {
            $p_list = $product_ids;
            unset($p_list[$key]);
            $repeat_key = array_search($val, $p_list);
            $inarray = in_array($val, $p_list);
            if (($repeat_key != false || $inarray) && $val != '') {
                return '第' . ($key + 1) . '行货品与第' . ($repeat_key + 1) . '行货品重复!';
            }
            if ($status != -1) {
                $product_info = D('BProduct')->getInfo(array(
                    'id' => $val,
                    'deleted' => 0
                ));
                if (empty($product_info)) {
                    return '第' . ($key + 1) . '行货品不存在!';
                } elseif ($product_info['status'] != 2) {
                    return '第' . ($key + 1) . '行货品不是正常在库，不可退货!';
                }
            }
        }
        return true;
    }

    /**
     * 修改一个退货单中的货品状态
     * @param  [type]  $return_id 退货单id
     * @param  integer $status    修改状态的结果
     * @return [type]             [description]
     */
    public function _refresh_product_status($return_id, $status = 9)
    {
        // 有且必须为 2-正常在库 9-退货中 10-退货完成
        if (empty($return_id) || !in_array($status, array(2, 9, 10))) return false;
        $condition = array(
            'deleted' => 0,
            'pr_id' => $return_id
        );
        $product_list = $this->model_procure_redatil->getList($condition);
        if (empty($product_list)) {
            return true;
        }

        $product_ids = array();
        foreach ($product_list as $key => $value) {
            $product_ids[] = $value['p_id'];
        }
        $update_product_status = true;
        if (!empty($product_ids)) {
            $condition = array('company_id' => get_company_id(), 'status' => ($status == 9 ? 2 : 9), 'id' => array('in', $product_ids));
            $data = array('status' => $status);
            $update_product_status = $this->model_product->update($condition, $data);
        }

        return $update_product_status;
    }

    // ---------- 所有函数写在此线往上 ----------

    // ---------- 操作记录函数 START----------
    /**
     * @author lzy 2018.5.26
     * copy by alam 2018/06/10
     * @param int $return_id 分称单id
     * @return 操作记录列表
     */
    public function getOperateRecord($return_id)
    {
        $condition=array(
            'operate.company_id' => get_company_id(),
            'operate.type' => BBillOpRecordModel::PROCURE_RETURN,
            'operate.sn_id' => $return_id,
            'employee.deleted' => 0,
            'employee.company_id' => get_company_id(),
        );
        $field = "operate.operate_type,operate.operate_time,employee.employee_name";
        $join = "join gb_b_employee employee on employee.user_id=operate.operate_id";
        $record_list = D('BBillOpRecord')->alias("operate")->getList($condition,$field,'',$join,'operate.id asc');
        $type_list = $this->_groupType();
        foreach ($record_list as $key => $val){
            $record_list[$key]['operate_name'] = $type_list[$val['operate_type']];
        }
        return $record_list;
    }
    /**
     * @author lzy 2018.5.26
     * copy by alam 2018/06/10
     * 将所有的状态码组合起来
     */
    private function _groupType()
    {
        return array(
            self::CREATE => self::CREATE_NAME,
            self::SAVE => self::SAVE_NAME,
            self::COMMIT => self::COMMIT_NAME,
            self::REVOKE => self::REVOKE_NAME,
            self::CHECK_PASS => self::CHECK_PASS_NAME,
            self::CHECK_DENY => self::CHECK_DENY_NAME,
            self::CHECK_REJECT => self::CHECK_REJECT_NAME,
            self::UPLOAD_IMG=>self::UPLOAD_IMG_NAME
        );
    }
    /**
     * @author lzy 2018.5.26
     * copy by alam 2018/06/10
     * 获取流程数组
     */
    public function getProcess($return_id)
    {
        $process_list = $this->_groupProcess();
        if (! empty($return_id)) {
            $condition = array(
                'procure_return.id' => $return_id
            );
            $field = 'procure_return.*,create_employee.employee_name as creator_name,check_employee.employee_name as check_name,upload_employee.employee_name as upload_name';
            $join = 'left join gb_b_employee create_employee on procure_return.creator_id=create_employee.user_id and create_employee.company_id=' . get_company_id();
            $join .= ' left join gb_b_employee check_employee on procure_return.check_id=check_employee.user_id and check_employee.company_id=' . get_company_id();
            $join.=' left join gb_b_employee upload_employee on procure_return.upimg_id=upload_employee.user_id and upload_employee.company_id='.get_company_id();
            $procure_info = $this->alias("procure_return")->getInfo($condition, $field, $join);
            $process_list[self::CREATE_PROKEY]['is_done'] = 1;
            $process_list[self::CREATE_PROKEY]['user_name'] = $procure_info['creator_name'];
            $process_list[self::CREATE_PROKEY]['time'] = $procure_info['create_time'];
            /* 检查是否审核 */
            if ($procure_info['check_id'] > 0 && ($procure_info['status'] == 1 || $procure_info['status'] == 2 || $procure_info['status'] == 4)) {
                $process_list[self::CHECK_PROKEY]['is_done'] = 1;
                $process_list[self::CHECK_PROKEY]['user_name'] = $procure_info['check_name'];
                $process_list[self::CHECK_PROKEY]['time'] = $procure_info['check_time'];
            } else {
                $process_list[self::CHECK_PROKEY]['is_done'] = 0;
                // 没有审核读取审核权限的员工
                $employee_name = D('BAuthAccess')->getEmployeenamesByRolename(self::CHECK_FUNCTION);
                $process_list[self::CHECK_PROKEY]['user_name'] = $employee_name ? $employee_name : '该权限人员暂缺';
            }
            /*检查是否上传凭证*/
            if($procure_info['status']==4){
                $process_list[self::UPLOAD_PROKEY]['is_done']=1;
                $process_list[self::UPLOAD_PROKEY]['user_name']=$procure_info['upload_name'];
                $process_list[self::UPLOAD_PROKEY]['time']=$procure_info['payop_time'];
            }else{
                $process_list[self::UPLOAD_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::UPLOAD_FUNCTION);
                $process_list[self::UPLOAD_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
            }
        }
        return $process_list;
    }
    /**
     * @author lzy 2018.5.26
     * copy by alam 2018/06/10
     * 将所有的流程组合起来
     */
    private function _groupProcess()
    {
        return array(
            self::CREATE_PROKEY => array(
                'process_name' => self::CREATE_PROCESS
            ),
            self::CHECK_PROKEY => array(
                'process_name' => self::CHECK_PROCESS
            ),
            self::UPLOAD_PROKEY=>array(
                'process_name'=>self::UPLOAD_PROCESS,
            )
        );
    }
    // ---------- 操作记录函数 END----------
}