<?php
/**
 * 销售退货
 * @author  alam
 * @time 17:10 2018/6/15
 */
namespace Business\Model;

use Business\Model\BCommonModel;
use Business\Model\BBillOpRecordModel;

class BSellReturnModel extends BCommonModel {
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//提交
    const REVOKE              =  4;//撤销
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝
    const CHECK_REJECT        =  7;//提交驳回

    /*操作状态名*/
    const CREATE_NAME         =  '创建表单';
    const SAVE_NAME           =  '保存表单';
    const COMMIT_NAME         =  '提交表单';
    const REVOKE_NMAE         =  '撤销表单';
    const CHECK_PASS_NMAE     =  '审批通过';
    const CHECK_DENY_NAME     =  '审批拒绝';
    const CHECK_REJECT_NAME   =  '提交驳回';

    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';

    /*操作函数名*/
    const CHECK_FUNCTION     =  'business/bsellreturn/check';
    public $model_bexpencesub, $model_bsellredetail, $model_bstatus, $model_bselldetail, $model_bproduct;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        parent::_initialize();
        $this->model_bexpencesub = D('BExpenceSub');
        $this->model_bselldetail = D('BSellDetail');
        $this->model_bsellredetail = D('BSellRedetail');
        $this->model_bstatus = D('BStatus');
        $this->model_bproduct = D('BProduct');
    }

    /**
     * 保存退货单
     * @param  int            $return_id 退货单id
     * @return boolean|string $result    true|错误提示
     */
    public function saveReturn(&$return_id)
    {
        $error_arr = array(
            'msg' => '保存单据失败！',
            'location' => 0
        );
        $post_data = I('post.');
        // 操作类型 1-保存 2-编辑
        $action_type = empty($return_id) ? 1 : 2;
        // 提交类型 -1-保存 0-提交
        $type = (INT)$post_data['type'];
        // 表单列表
        $detail_datas = $post_data['detail_datas'];

        // 检查退货单状态
        if ($action_type == 2) {
            $return_info = $this->getInfo(array('id' => $return_id, 'deleted' => 0));
            if (empty($return_info)) {
                $error_arr['msg'] = '单据错误或不存在！';
                return $error_arr;
            } elseif (!in_array($return_info['status'], array(-2, -1))) {
                // 获取单个状态注释
                $condition = array(
                    'bs.table' => DB_PRE . 'b_sell_return',
                    'bs.field' => 'status',
                    'bsv.value' => $return_info['status']
                );
                $status_comment = $this->model_bstatus->getFidldComment($condition);
                $error_arr['msg'] = '单据状态不允许编辑，当前状态为【' . $status_comment . '】！';
                return $error_arr;
            }
        }
        $check_res = $this->_checkDetail($detail_datas);
        if ($check_res) {
            $error_arr['msg'] = $check_res;
            return $error_arr;
        }
        // 客户信息
        $condition = array('id' => $post_data['client_id']);
        $client = M('BClient')->where($condition)->find();

        // 保存表单详情
        $res = false;
        M()->startTrans();
        $save_data = array(
            'return_time' => strtotime($post_data['return_time']),
            'buyer_id' => $client['user_id'],
            'client_idno' => $post_data['client_idno'],
            'client_id' => $post_data['client_id'],
            'price' => $post_data['price'],
            'return_price' => $post_data['return_price'],
            'memo' => trim($post_data['memo']),
            'count' => $post_data['count'],
            'extra_price' => $post_data['extra_price'],
            'status' => $post_data['type']
        );
        if ($action_type == 1) {
            $insert_data = array(
                'order_id' => b_order_number('BSellReturn', 'order_id'),
                'company_id' => get_company_id(),
                'shop_id' => $post_data['shop_id'],
                'creator_id' => get_user_id(),
                'create_time' => time()
            );
            $save_data = array_merge($save_data, $insert_data);
            $res = $return_id = $this->insert($save_data);
            $error_arr['location'] = 1;

            // 操作记录 - 创建
            if ($res !== false) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL_RETURN, $return_id, self::CREATE);
            }
        } elseif ($action_type == 2) {
            $condition = array('id' => $return_id);
            $res = $this->update($condition, $save_data);
            $error_arr['location'] = 2;
        }

        // 操作记录 - 提交|保存
        if ($res !== false) {
            if ($type == 0) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL_RETURN, $return_id, self::COMMIT);
            } else {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL_RETURN, $return_id, self::SAVE);
            }
        }

        // 处理其它费用
        if ($res !== false) {
            $res = $this->model_bexpencesub->editList($return_id, 4);
            $error_arr['location'] = 3;
        }

        // 处理退款明细
        if ($res !== false) {
            // 判断默认币种是否使用，更新使用字段
            D('BCurrency')->is_use($post_data['main_currency_id']);
            $res = $this->_saveSaccountRecord($post_data['saccout_record'], $return_id, $post_data['shop_id'], $post_data['main_currency'], $post_data['del_saccount_detail']);
            $error_arr['location'] = 4;
        }

        // 保存表单列表
        if ($res !== false) {
            // 旧的 销售退货表单列表ID 货品ID
            $old_redetail_ids = array();
            if ($action_type == 2) {
                $condition = array(
                    'sr_id' => $return_id,
                    'deleted' => 0
                );
                $redetail_list = $this->model_bsellredetail->getList($condition, 'id');
                foreach ($redetail_list as $key => $value) {
                    $old_redetail_ids[] = $value['id'];
                }
            }

            // 销售表单列表列表ID 退货货品ID
            $sell_detail_ids = $return_product_ids = array();
            foreach ($detail_datas as $key => $value) {
                $sell_detail_ids[] = $value['sell_detail_id'];
                $return_product_ids[] = $value['product_id'];

                if ($res !== false && !empty($value['sell_detail_id'])) {
                    if (!empty($value['redetail_id'])) {
                        // 修改销售退货表单列表
                        $condition = array('id' => $value['redetail_id']);
                        $update_data = array(
                            'return_price' => $value['return_price']
                        );
                        $res = $this->model_bsellredetail->update($condition, $update_data);
                        $error_arr['location'] = 5;
                    } else {
                        // 添加销售退货表单列表
                        $insert_data = array(
                            'sr_id' => $return_id,
                            'sd_id' => $value['sell_detail_id'],
                            'product_id' => $value['product_id'],
                            'return_price' => $value['return_price'],
                        );
                        $res = $this->model_bsellredetail->insert($insert_data);
                        $error_arr['location'] = 6;
                    }

                    $unset_key = array_search($value['redetail_id'], $old_redetail_ids);
                    unset($old_redetail_ids[$unset_key]);
                } else {
                    $res = false;
                    $error_arr['location'] = 7;
                }
            }

            // 删除剩余的销售退货表单列表
            if ($res !== false && !empty($old_redetail_ids)) {
                $condition = array('id' => array('in', $old_redetail_ids));
                $res = $this->model_bsellredetail->update($condition, array('deleted' => 1));
                $error_arr['location'] = 8;
            }

            #TIPS 保存表单时不修改销售表单列表、货品 的状态值，仅在提交、驳回以及审核操作中做修改
            #TIPS 修改销售表单列表状态（销售表单列表状态值修改 销售退货提交时-2 销售退货审核|驳回时-1|3）
            // 修改货品状态（货品状态值修改 销售退货提交时-13 销售退货审核|驳回时-2|14）
            if ($res !== false && $type === 0) {
                $res = $this->_refreshStatus($return_id, 1, $sell_detail_ids, $return_product_ids);
                $error_arr['location'] = 9;
            }
        }

        if ($res !== false) {
            M()->commit();
            return true;
        } else {
            M()->rollback();
            return $error_arr;
        }
    }

    // 销售退货单撤销
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
        $condition = array('id' => $return_id, 'company_id' => get_company_id(), 'status' => 0);
        $data = array('status' => 3);
        $res = $this->update($condition, $data);

        if ($res !== false) {
            $res = $this->_refreshStatus($return_id, 3);
        }

        if ($res !== false) {
            $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL_RETURN, $return_id, SELF::REVOKE);
        }

        // 修改对账流水数据状态
        if ($res !== false) {
            $res = $this->_updateSaccountStatus($return_id, 3);
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

    // 退货单删除
    public function delete($return_id){
        $info = array(
            'status' => 0,
            'msg' => '删除失败！'
        );
        $return_id = empty($return_id) ? I('return_id/d', 0) : $return_id;
        if (empty($return_id)) {
            return $info;
        }

        $condition = array('id' => $return_id, 'company_id' => get_company_id(), 'status' => array('in', '-2,-1,3'));
        $data = array('deleted' => 1);
        $delete_return = $this->update($condition, $data);

        if ($delete_return !== false) {
            return array(
                'status' => 1,
                'msg' => '删除成功！'
            );
        } else {
            return $info;
        }
    }

    // 审核退货单
    public function checkReturn()
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

        // 退货单表单列表状态判断
        if ($status_type != -2) {
            $condition = array('bsr.sr_id' => $return_id, 'bsr.deleted' => 0);
            $field = 'bsr.*, bsd.status as detail_status, bp.product_code';
            $join = 'LEFT JOIN __B_SELL_DETAIL__ bsd ON bsr.sd_id = bsd.id';
            $join .= ' LEFT JOIN __B_PRODUCT__ bp ON bsr.product_id = bp.id';
            $product_list = $this->model_bsellredetail->alias('bsr')->getList($condition, $field, null, $join);
            foreach ($product_list as $key => $value) {
                if (!in_array($value['detail_status'], array(1, 2))) {
                    // 获取单个状态注释
                    $condition = array(
                        'bs.table' => DB_PRE . 'b_sell_detail',
                        'bs.field' => 'status',
                        'bsv.value' => $value['detail_status']
                    );
                    $status_comment = $this->model_bstatus->getFidldComment($condition);
                    $info['msg'] = '货品编码' . $value['product_code'] . '不允许退货，当前状态为【' . $status_comment . '】！';
                    return $info;
                }
            }
        }
        
        // 退货单详情
        $condition = array('id' => $return_id, 'deleted' => 0, 'status' => 0, 'company_id' => get_company_id());
        $return_info = $this->getInfo($condition);
        if ($return_info['status'] != 0 || empty($return_info)) {
            $info['msg'] = '退货单状态不允许审核！';
            return $info;
        }

        M()->startTrans();

        // 修改退货单状态
        $data = array(
            'status' => $status,
            'check_id' => get_user_id(),
            'check_time' => time(),
            'check_memo' => $check_memo
        );
        $res = $this->update($condition, $data);

        if ($status == -2) {
            if ($res !== false) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL_RETURN, $return_id, SELF::CHECK_REJECT);
            }
            $info['msg'] = '退货单驳回失败！';
        } elseif ($status == 1) {
            if ($res !== false) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL_RETURN, $return_id, SELF::CHECK_PASS);
            }
            $info['msg'] = '退货单审核通过失败！';
        } elseif ($status == 2) {
            if ($res !== false) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL_RETURN, $return_id, SELF::CHECK_DENY);
            }
            $info['msg'] = '退货单审核不通过失败！';
        }

        // 修改销售表单列表状态 修改货品状态
        if ($res && $status_type != -2) {
            $res = $this->_refreshStatus($return_id, ($status == 1 ? 2 : 3));
            // 修改对账流水数据状态
            if ($res !== false) {
                $res = $this->_updateSaccountStatus($return_id, ($status == 1 ? 0 : 2));
            }
        }

        if ($res) {
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

    /**
     * 刷新相关联数据的状态
     * @param  integer $return_id 退货单id
     * @param  integer $type      类型 1-提交 2-确认 3-回滚
     * @param  [type]  $sell_detail_ids    销售表单列表ids
     * @param  [type]  $return_product_ids 退货货品ids
     */
    protected function _refreshStatus($return_id, $type = 1, $sell_detail_ids, $return_product_ids)
    {
        if (empty($sell_detail_ids) || empty($return_product_ids)) {
            $condition = array(
                'sr_id' => $return_id,
                'deleted' => 0
            );
            $redetail_list = $this->model_bsellredetail->getList($condition);
            
            foreach ($redetail_list as $key => $value) {
                $sell_detail_ids .= ($value['sd_id'] . ',');
                $return_product_ids .= ($value['product_id'] . ',');
            }
            $sell_detail_ids = rtrim($sell_detail_ids);
            $return_product_ids = rtrim($return_product_ids);
        }

        if ($type == 1) {
            $status_sell_detail_before = 1;   // 已销售
            $status_sell_detail_after = 2;    // 退货中
            $status_product_before = 6;       // 销售出库
            $status_product_after = 13;       // 销售退货中
        } elseif ($type == 2) {
            $status_sell_detail_before = 2;   // 退货中
            $status_sell_detail_after = 3;    // 已退货
            $status_product_before = 13;      // 销售退货中
            $status_product_after = 2;        // 正常在库
        } elseif ($type == 3) {
            $status_sell_detail_before = 2;   // 退货中
            $status_sell_detail_after = 1;    // 已销售
            $status_product_before = 13;      // 销售退货中
            $status_product_after = 6;        // 销售出库
        }
        $update_sell_detail = $this->model_bselldetail->update(array('id' => array('in', $sell_detail_ids), 'status' => $status_sell_detail_before), array('status' => $status_sell_detail_after));
        $update_product = $this->model_bproduct->update(array('id' => array('in', $return_product_ids), 'status' => $status_product_before), array('status' => $status_product_after));

        if ($update_sell_detail !== false && $update_product !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 批量添加收款明细
     * @param $saccount_record
     * @param $sell_id
     * @param $shop_id
     * @param $rate
     * @return bool|string
     */
    protected function _saveSaccountRecord($saccount_record, $sell_id, $shop_id, $main_currency, $del_saccount_detail)
    {
        $saccount_details = array();
        foreach($saccount_record as $k => $v){
            $saccount_detail = array();
            $saccount_detail['company_id'] = get_company_id();
            $saccount_detail['shop_id'] = $shop_id;
            $saccount_detail['sn_id'] = $sell_id;
            $saccount_detail['pay_id'] = $v["pay_id"];
            $saccount_detail['currency_id'] = $v['currency'];
            //判断币种是否使用，更新使用字段
            D('BCurrency')->is_use($v['currency']);
            $saccount_detail['currency_rate'] = $v['currency_rate'];
            $saccount_detail['main_currency_id'] = $main_currency['id'];
            $saccount_detail['main_currency_rate'] = $main_currency['rate'];
            // price确保负值
            $saccount_detail['pay_price'] = bcsub(0, abs($v['pay_price']), 2);
            $saccount_detail['receipt_price'] = bcsub(0, abs($v['actual_price']), 2);
            $saccount_detail['creator_id'] = get_user_id();
            $saccount_detail['create_time'] = time();
            $saccount_detail['type'] = 3;
            $saccount_detail['status'] = -1;
            $saccount_detail['deleted'] = 0;
            if (!empty($v['saccount_id'])) {
                $saccount_detail['id'] = $v['saccount_id'];
            }
            $saccount_details[] = $saccount_detail;
        }
        $del_result = true;
        if(!empty($del_saccount_detail)){
            $del_result = D('BSaccountRecord')->update(array('id' => array('in', $del_saccount_detail)), array('deleted' => 1));
        }
        $b_saccout_record = D('BSaccountRecord')->addAll($saccount_details, array(), true);
        return $b_saccout_record && $del_result;
    }

    /**
     * 修改记账流水表状态
     * @param  integer $return_id [description]
     * @param  integer $type      1审核通过 2审核不通过 3撤销
     */
    protected function _updateSaccountStatus($return_id = 0, $status = -1)
    {
        // 修改对账流水数据状态
        if (!empty($return_id)) {
            $where = array(
                'company_id' => get_company_id(),
                'sn_id' => $return_id,
                'type' => 3,
                'status' => '-1'
            );
            $res = D('BSaccountRecord')->update($where, array('status' => $status));
            return $res;
        }
        return false;
    }

    /**
     * 检查退货单中对应的销售单detail是否重复、已退货
     * @param  [type] $detail_datas 退货详情
     * @return boolean              false|msg
     */
    public function _checkDetail($detail_datas)
    {
        $exist_detail_ids = array();
        foreach ($detail_datas as $key => $value) {
            if (in_array($value['sell_detail_id'], $exist_detail_ids) ) {
                return '第' . ($key + 1) . '行货品与第' . (array_search($value['sell_detail_id'], $detail_data) + 1) . '行货品重复!';
            } else {
                $condition = array(
                    'id' => $value['sell_detail_id']
                );
                $detail_info = $this->model_bselldetail->getInfo($condition);
                if (!in_array($detail_info['status'], array(1, 2))) {
                    // 获取单个状态注释
                    $condition = array(
                        'bs.table' => DB_PRE . 'b_sell_detail',
                        'bs.field' => 'status',
                        'bsv.value' => $detail_info['status']
                    );
                    $status_comment = $this->model_bstatus->getFidldComment($condition);
                    return '第' . ($key + 1) . '行货品不允许退货，当前状态为【' . $status_comment . '】！';
                }
            }
            
            $exist_detail_ids[] = $value['sell_detail_id'];
        }
        return false;
    }

    /**
     * 获取表单详情
     * @param  integer $return_id 退货单id
     * @param  array   $condition 附加条件
     */
    public function getDetailInfo($return_id = 0, $condition = array())
    {
        $condition = array_merge(array(
            'bsr.id' => $return_id,
            'bsr.deleted' => 0
        ), (empty($condition) ? array() : $condition));
        $field = 'bsr.*, bc.client_name, bc.client_moblie client_mobile, be_create.employee_name as creator_name, IFNULL(bs.shop_name, "总部") as shop_name, be_check.employee_name as check_name';
        $join = 'LEFT JOIN __B_CLIENT__ bc ON bsr.client_id = bc.id AND bc.company_id = ' . get_company_id();
        $join .= ' LEFT JOIN __B_EMPLOYEE__ be_create ON bsr.creator_id = be_create.user_id AND be_create.company_id = ' . get_company_id();
        $join .= ' LEFT JOIN __B_EMPLOYEE__ be_check ON bsr.creator_id = be_check.user_id AND be_check.company_id = ' . get_company_id();
        $join .= ' LEFT JOIN __B_SHOP__ bs ON bsr.shop_id = bs.id AND be_create.company_id = ' . get_company_id();
        $return_info = $this->alias('bsr')->getInfo($condition, $field, $join);
        return $return_info;
    }

    // ---------- 所有函数写在此线往上 ----------

    // ---------- 操作记录函数 START----------

    /**
     * @author alam
     * @param int $return_id 销售单id
     * @return 操作记录列表
     */
    public function getOperateRecord($return_id){
        $condition = array(
            'operate.company_id' => get_company_id(),
            'operate.type' => BBillOpRecordModel::SELL_RETURN,
            'operate.sn_id' => $return_id,
            'employee.deleted' => 0,
            'employee.company_id' => get_company_id(),
        );
        $field = 'operate.operate_type, operate.operate_time, employee.employee_name';
        $join = 'join gb_b_employee employee on employee.user_id = operate.operate_id';
        $record_list = D('BBillOpRecord')->alias('operate')->getList($condition, $field, '', $join, 'operate.id asc');
        $type_list = $this->_groupType();
        foreach ($record_list as $key => $val){
            $record_list[$key]['operate_name'] = $type_list[$val['operate_type']];
        }
        return $record_list;
    }

    /**
     * @author alam
     * 将所有的状态码组合起来
     */
    private function _groupType(){
        return array(
            self::CREATE => self::CREATE_NAME,
            self::SAVE => self::SAVE_NAME,
            self::COMMIT => self::COMMIT_NAME,
            self::REVOKE => self::REVOKE_NMAE,
            self::CHECK_PASS => self::CHECK_PASS_NMAE,
            self::CHECK_DENY => self::CHECK_DENY_NAME,
            self::CHECK_REJECT => self::CHECK_REJECT_NAME
        );
    }

    /**
     * @author alam
     * 获取流程数组
     */
    public function getProcess($return_id){
        $process_list = $this->_groupProcess();
        if (!empty($return_id)) {
            $return_info = $this->getDetailInfo($return_id);
            $process_list[self::CREATE_PROKEY]['is_done'] = 1;
            $process_list[self::CREATE_PROKEY]['user_name'] = $return_info['creator_name'];
            $process_list[self::CREATE_PROKEY]['time'] = $return_info['create_time'];
            /*检查是否审核*/
            if($return_info['check_id'] > 0 && ($return_info['status'] == 1 || $return_info['status'] == 2)){
                $process_list[self::CHECK_PROKEY]['is_done'] = 1;
                $process_list[self::CHECK_PROKEY]['user_name'] = $return_info['check_name'];
                $process_list[self::CHECK_PROKEY]['time'] = $return_info['check_time'];
            }else{
                $process_list[self::CHECK_PROKEY]['is_done'] = 0;
                //没有审核读取审核权限的员工
                $employee_name = D('BAuthAccess')->getEmployeenamesByRolename(self::CHECK_FUNCTION);
                $process_list[self::CHECK_PROKEY]['user_name'] = $employee_name ? $employee_name : '该权限人员暂缺';
            }
        }
        return $process_list;
    }

    /**
     * @author alam
     * 将所有的流程组合起来
     */
    private function _groupProcess(){
        return array(
            self::CREATE_PROKEY => array(
                'process_name' => self::CREATE_PROCESS,
            ),
            self::CHECK_PROKEY => array(
                'process_name' => self::CHECK_PROCESS,
            )
        );
    }

    // ---------- 操作记录函数 END----------
}