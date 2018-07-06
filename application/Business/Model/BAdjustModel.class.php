<?php
/**
 * @author lzy 2018.6.13 15:00
 * 货品调整model
 */
namespace Business\Model;

use Business\Model\BCommonModel;

class BAdjustModel extends BCommonModel
{
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
    const CHECK_FUNCTION     =  'business/badjust/check';

    // 允许采购信息调整货品状态 适用于除退货和采购入库状态的货品
    CONST ADJUST_PRODUCT_STATUS_PROCURE = '2, 3, 4, 5, 6, 7, 8, 11, 12, 13, 14';
    // 允许销售信息调整货品状态 适用于正常在库、调拨中和挂起状态的货品
    CONST ADJUST_PRODUCT_STATUS_SELL = '2, 3, 13, 14';
    // 允许商品规格调整货品状态 适用任何状态的货品
    CONST ADJUST_PRODUCT_STATUS_GOODS = '1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14';

    public function __construct()
    {
        parent::__construct();
    }

    // 保存或提交
    public function saveAdjust($adjust_data)
    {
        $check_adjust_status = $this->_checkAdjustStatus($adjust_data['adjust_id'], 1);
        if ($check_adjust_status !== true) {
            return array(
                'status' => 0,
                'msg' => $check_adjust_status
            );
        }
        $adjust_insert = array(
            'company_id' => get_company_id(),
            'batch' => b_order_number('BAdjust', 'batch'),
            'num' => $adjust_data['num'],
            'memo' => $adjust_data['memo'],
            'type' => $adjust_data['adjust_type'],
            'status' => $adjust_data['status'],
            'deleted' => 0
        );
        $adjust_id = $adjust_data['adjust_id'];

        $this->startTrans();

        if ($adjust_id > 0) {
            $result = $this->update(array('id' => $adjust_id), $adjust_insert);
        } else {
            $adjust_insert['creator_id'] = get_user_id();
            $adjust_insert['create_time'] = time();
            $result = $adjust_id = $this->insert($adjust_insert);
            if ($result !== false) {
                $result = D('BBillOpRecord')->addRecord(BBillOpRecordModel::ADJUST, $adjust_id, self::CREATE);
            }
        }
        if ($result !== false) {
            if ($adjust_data['status'] == 0) {
                $result = D('BBillOpRecord')->addRecord(BBillOpRecordModel::ADJUST, $adjust_id, self::COMMIT);
            } else {
                $result = D('BBillOpRecord')->addRecord(BBillOpRecordModel::ADJUST, $adjust_id, self::SAVE);
            }
        }

        if ($result !== false) {
            $result = $this->_saveProduct($adjust_data['product_data'], $adjust_data['adjust_type'], $adjust_id, $adjust_data['status']);
        }

        if ($result === true) {
            $this->commit();
            return array(
                'status' => 1,
                'adjust_id' => $adjust_id
            );
        } else {
            $this->rollback();
            return array(
                'status' => 0,
                'msg' => $result
            );
        }
    }

    // 保存货品
    private function _saveProduct($product_data, $adjust_type, $adjust_id, $status = -1)
    {
        $model_badjustdetail = D('BAdjustDetail');
        $check_result = $this->_productCheck($product_data, $adjust_type);
        if ($check_result !== true) {
            return $check_result;
        }

        // 数据库中单据已存在的product_ids
        if (!empty($adjust_id)) {
            $condition = array('ad_id' => $adjust_id, 'deleted' => 0);
            $detail_list = $model_badjustdetail->getList($condition, 'id');
            foreach ($detail_list as $key => $value) {
                $old_detail_ids[] = $value['id'];
            }
        }

        $result = true;
        foreach ($product_data as $key => $val) {
            if ($result !== false) {
                if (empty($val['id'])) {

                    $product_info = $this->getProductInfo('', $val['product_id']);

                    $adjust_before = array();
                    $adjust_after = $val;
                    unset($adjust_after['product_id']);
                    foreach ($adjust_after as $k => $v) {
                        if ($k == 'price_mode') {
                            $adjust_before[$k] = $product_info['procurement_pricemode'];
                        } else {
                            $adjust_before[$k] = $product_info[$k];
                        }
                    }

                    if ($adjust_type == 3) {
                        $agc_check = $this->_agcCheck($adjust_before['goods_code'], $adjust_after['goods_code'], $agc_name);
                        if ($agc_check === false) {
                            return '调整后的商品大类与旧商品大类不一致，请调整为【' . $agc_name . '】大类下的规格编码！';
                        } elseif ($agc_check !== true) {
                            return $agc_check;
                        }
                    }

                    $detail_insert = array(
                        'ad_id' => $adjust_id,
                        'p_id' => $product_info['product_id'],
                        'adjust_before' => json_encode($adjust_before, true),
                        'adjust_after' => json_encode($adjust_after, true),
                        'deleted' => 0
                    );
                    $result = $model_badjustdetail->insert($detail_insert);
                } else {
                    unset($old_detail_ids[array_search($val['id'], $old_detail_ids)]);
                    $condition = array(
                        'id' => $val['id']
                    );
                    $adjust_after = $val;
                    unset($adjust_after['product_id']);
                    unset($adjust_after['id']);
                    $update = array(
                        'adjust_after' => json_encode($adjust_after, true)
                    );
                    $result = $model_badjustdetail->update($condition, $update);
                }
            }
        }
        if ($result !== false && !empty($old_detail_ids)) {
            $condition = array(
                'id' => array('in', $old_detail_ids)
            );
            $update = array(
                'deleted' => 1
            );
            $result = $model_badjustdetail->update($condition, $update);
        }
        return ($result === false) ? '操作失败！' : true;
    }
    
    // 货品检查
    private function _productCheck($product_data, $adjust_type)
    {
        $model_bproduct = D('BProduct');
        $model_bgoods = D('BGoods');

        $result = true;
        foreach ($product_data as $key => $val) {

            if ($result === true) {

                // 货品存在性验证
                $product_info = $model_bproduct->getInfo(array(
                    'product_code' => trim($val['product_code']),
                    'company_id' => get_company_id(),
                    'deleted' => 0
                ));
                if (empty($product_info)) {
                    $result = '货品编码为' . $product_info['product_code'] . '的货品不存在';
                    break;
                }

                // 检查货品状态是否可调整
                $result = $this->_checkProductStatus($val['product_code'], $product_info['status'], $adjust_type);
                if ($result !== true) break;

                if ($adjust_type == 3) {
                    // 规格编码存在性验证
                    $goods_info = $model_bgoods->getInfo(array(
                        'goods_code' => trim($val['goods_code']),
                        'deleted' => 0
                    ));
                    echo $model_bgoods->getlastsql();die;
                    if (empty($goods_info)) {
                        $result = '规格编码' . $val['goods_code'] . '不存在';
                        break;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 检查单据状态是否可进行对应操作
     * @param  integer $adjust_id  单据id
     * @param  integer $check_type 检查类型
     * @param  [type]  $status     单据状态 当置空时从数据库获取
     */
    private function _checkAdjustStatus($adjust_id = 0, $check_type = 1, $status) {
        if (!empty($adjust_id)) {
            if ($check_type == 1) {
                $status_arr = array(-2, -1);
            } elseif ($check_type == 2) {
                $status_arr = array(0);
            }

            if (empty($status)) {
                $adjust_info = $this->getInfo(array('id' => $adjust_id, 'company_id' => get_company_id()));
                $status = $adjust_info['status'];
            }
            if (!in_array($status, $status_arr)) {
                // 获取单个状态注释
                $condition = array(
                    'bs.table' => DB_PRE . 'b_adjust',
                    'bs.field' => 'status',
                    'bsv.value' => $status
                );
                $status_comment = D('BStatus')->getFidldComment($condition);
                return '单据状态为' . $status_comment . '不可' . ($check_type == 1 ? '编辑' : '审核或驳回');
            }
        }
        return true;
    }

    // 检查货品状态是否允许进行对应的类型调整
    public function _checkProductStatus($product_code, $check_status = 0, $adjust_type = 1)
    {
        $res = true;

        if ($adjust_type == 1 && !in_array($check_status, explode(',', SELF::ADJUST_PRODUCT_STATUS_PROCURE))) {
            $res = false;
        }
        if ($adjust_type == 2 && !in_array($check_status, explode(',', SELF::ADJUST_PRODUCT_STATUS_SELL))) {
            $res = false;
        }
        /* if ($adjust_type == 3 && (!in_array($check_status, explode(',', BAdjust::ADJUST_PRODUCT_STATUS_GOODS)))) {
            $res = false;
        }*/

        if ($res === true) {
            return true;
        } else {
            $status_list = $this->_getProductStatusComment();
            $adjust_type_name = ($adjust_type == 1 ? '采购信息' : ($adjust_type == 2 ? '销售信息' : '商品规格'));
            return '货品编码' . $product_code . '当前状态为【' . $status_list[$check_status] . '】,不可以进行' . $adjust_type_name . '调整！';
        }
    }

    // 获取调整类型
    public function _getAdjustTypeComment()
    {
        static $type_list;
        if (empty($status_list)) {
            $condition = array();
            $condition['table'] = DB_PRE . 'b_adjust';
            $condition['field'] = 'type';
            $type_list = D('b_status')->getFieldValue($condition);
        }
        return $type_list;
    }

    // 获取货品状态
    public function _getProductStatusComment()
    {
        static $status_list;
        if (empty($status_list)) {
            $condition = array();
            $condition['table'] = DB_PRE . 'b_product';
            $condition['field'] = 'status';
            $status_list = D('b_status')->getFieldValue($condition);
        }
        return $status_list;
    }
    
    /**
     * 商品规格调整 对比两个货品编码是否同一大类
     * @param  string &$old_goods_code 旧商品规格
     * @param  string &$new_goods_code 新商品规格
     * @param  string &$agc_name       A端商品大类名称
     * @return boolean|string          true-OK false-提示agc_name string-提示res
     */
    public function _agcCheck(&$old_goods_code = '', &$new_goods_code = '', &$agc_name = '')
    {
        $model_bproduct = D('BProduct');
        $goods_code = func_get_args();
        $res = false;
        $type = '';
        $i = 0;
        foreach ($goods_code as $key => $value) {
            if ($i == 2) {
                break;
            }
            $condition = array(
                'bgoods.goods_code' => trim($value),
                'bgoods.deleted' => 0
            );
            $field = 'bgoodsclass.type';
            $join = ' LEFT JOIN __B_GOODS_COMMON__ bgoodscommon ON bgoods.goods_common_id = bgoodscommon.id AND bgoodscommon.company_id = ' . get_company_id();
            $join .= ' LEFT JOIN __B_GOODS_CLASS__ bgoodsclass ON bgoodscommon.class_id = bgoodsclass.id';
            $info = D('BGoods')->alias('bgoods')->getInfo($condition, $field, $join);
            if (!empty($info)) {
                if (empty($type)) {
                    $type = $info['type'];
                } elseif ($type == $info['type']) {
                    $res = true;
                }
                if (empty($agc_name)) {
                    $condition = array(
                        'astatus.table' => DB_PRE . 'a_goods_class',
                        'astatus.field' => 'type',
                        'asv.value' => $info['type']
                    );
                    $agc_name = D('AStatus')->getFidldComment($condition);
                }
            } else {
                return '商品规格【' . $value . '】不存在，请验证！';
            }
            $i++;
        }
        $old_goods_code = trim($old_goods_code);
        $new_goods_code = trim($new_goods_code);
        return $res;
    }

    /**
     * 获取单据详情
     * 
     * @param int $adjust_id id
     * @param int $type 获取类型 1 编辑 2 详情
     */
    public function getAdjustDetail($adjust_id, $type)
    {
        static $adjust_info;
        if (empty($adjust_info)) {
            $condition = array(
                'gb_b_adjust.id' => $adjust_id
            );
            $field = 'gb_b_adjust.*,creator_employee.employee_name as creator_name, check_employee.employee_name as check_name';
            $join = ' LEFT JOIN __B_EMPLOYEE__ creator_employee ON creator_employee.user_id = gb_b_adjust.creator_id';
            $join .= ' LEFT JOIN __B_EMPLOYEE__ check_employee ON check_employee.user_id = gb_b_adjust.check_id';
            $adjust_info = $this->getInfo($condition, $field, $join);

            $condition = array(
                'ad.ad_id' => $adjust_id,
                'ad.deleted' => 0
            );
            $field = 'ad.*, ';
            $field .= rtrim(rtrim($this->_productInfoField()), ',');
            $join = ' LEFT JOIN __B_PRODUCT__ product on product.id = ad.p_id ';
            $join .= $this->_productInfoJoin();

            $product_list = D('BAdjustDetail')->alias('ad')->getList($condition, $field, '', $join);
            foreach ($product_list as $key => $val) {
                $product_list[$key]['adjust_before'] = json_decode($val['adjust_before'], true);
                $product_list[$key]['adjust_after'] = json_decode($val['adjust_after'], true);
                if ($type == 1) {
                    foreach ($product_list[$key]['adjust_after'] as $k => $v) {
                        $product_list[$key][$k] = $v;
                    }
                }
            }
            $adjust_info['product_list'] = $product_list;
        }
        return $adjust_info;
    }

    // 获取货品的信息
    public function getProductInfo($product_code, $product_id)
    {
        $model_bproduct = D('BProduct');
        if (empty($product_code) && empty($product_id)) return array();

        $condition = array();
        if (empty($product_code)) {
            $condition['product.id'] = $product_id;
        } elseif (empty($product_id)) {
            $condition['product.product_code'] = $product_code;
        }
        $condition['product.company_id'] = get_company_id();
        $condition['product.deleted'] = 0;

        $field = '';
        $field .= rtrim(rtrim($this->_productInfoField()), ',');
        $join = '';
        $join .= $this->_productInfoJoin();

        $product_info = $model_bproduct->alias('product')->getInfo($condition, $field, $join);
        return $product_info;
    }

    // 根据商品获取货品列表
    public function getGoodsProduct($goods_ids, $product_codes, $adjust_type = 1)
    {
        $model_bproduct = D('BProduct');
        // 已存在表单列表中的货品
        if (!is_array($product_codes)) {
            $product_codes = explode(',', $product_codes);
        }
        // 选择的商品规格
        if (!is_array($goods_ids)) {
            $goods_ids = explode(',', $goods_ids);
        }
        // 货品信息列表
        $condition = array(
            'product.company_id' => get_company_id(),
            'product.goods_id' => array('in', $goods_ids),
            'product.deleted' => 0
        );
        if ($adjust_type == 1) {
            $condition['product.status'] = array('in', SELF::ADJUST_PRODUCT_STATUS_PROCURE);
        } elseif ($adjust_type == 2) {
            $condition['product.status'] = array('in', SELF::ADJUST_PRODUCT_STATUS_SELL);
        }/* elseif ($adjust_type == 3) {
            $condition['product.status'] = array('in', SELF::ADJUST_PRODUCT_STATUS_GOODS);
        }*/

        $field = '';
        $field .= rtrim(rtrim($this->_productInfoField()), ',');
        $join = '';
        $join .= $this->_productInfoJoin();

        $product_list = $model_bproduct->alias('product')->getList($condition, $field, '', $join);

        foreach ($product_list as $key => $value) {
            if (in_array($value['product_code'], $product_codes)) {
                unset($product_list[$key]);
            }
        }
        return $product_list;
    }

    #TIPS
    #采购信息调整
    #采购计价方式   procurement表   procurement_pricemode字段
    #成本价   product表   cost_price字段
    #金价   product_gold表   buy_price字段
    #克重   product_gold表   weight字段
    #工费   product_gold表   buy_m_fee字段
    #总工费   计算得出
    #
    #销售信息调整
    #销售计价方式   goldgoods_detail表   sell_pricemode字段
    #销售工费方式   goldgoods_detail表   sell_feemode字段
    #销售一口价   product表   sell_price字段
    #销售工费   product_gold表   sell_fee字段

    // 通用货品信息查询field
    public function _productInfoField() {
        $field = 'product.id as product_id, product.product_code, product.cost_price, product.sell_price, product.status as product_status, ';
        $field .= 'goods.goods_name, goods.goods_code, ';
        $field .= 'IFNULL(gold_product.weight, 0) AS weight, IFNULL(gold_product.buy_m_fee, 0) AS buy_m_fee, IFNULL(gold_product.sell_fee, 0) AS sell_fee, ';
        $field .= 'IFNULL(goods_detail.sell_pricemode, 0) AS sell_pricemode, IFNULL(goods_detail.sell_feemode, 0) AS sell_feemode, ';
        $field .= 'IFNULL(procurement.pricemode, 0) AS procurement_pricemode, ';
        return $field;
    }

    // 通用货品信息查询join
    public function _productInfoJoin() {
        $join .= ' LEFT JOIN __B_GOODS__ goods on goods.id = product.goods_id ';
        $join .= ' LEFT JOIN __B_PRODUCT_GOLD__ gold_product on gold_product.product_id = product.id';
        $join .= ' LEFT JOIN __B_GOLDGOODS_DETAIL__ goods_detail on goods_detail.goods_id = product.goods_id ';
        $join .= ' LEFT JOIN __B_PROCURE_STORAGE__ procure_storage on procure_storage.id = product.storage_id';
        $join .= ' LEFT JOIN __B_PROCUREMENT__ procurement on procurement.id = procure_storage.procurement_id';
        return $join;
    }

    // ---------- 单据审核 驳回 撤销 START----------

    // 撤销
    public function cancel($adjust_id)
    {
        $info = array(
            'status' => 0,
            'msg' => '撤销失败！'
        );
        $adjust_id = empty($adjust_id) ? I('adjust_id/d', 0) : $adjust_id;
        if (empty($adjust_id)) {
            return $info;
        }
        
        M()->startTrans();
        $condition = array('id' => $adjust_id, 'company_id' => get_company_id(), 'status' => 0);
        $data = array('status' => 3);
        $res = $this->update($condition, $data);

        if ($res) {
            $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::ADJUST, $adjust_id, SELF::REVOKE);
        }

        if ($res) {
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
    
    // 删除
    public function delete($adjust_id){
        $info = array(
            'status' => 0,
            'msg' => '删除失败！'
        );
        $adjust_id = empty($adjust_id) ? I('adjust_id/d', 0) : $adjust_id;
        if (empty($adjust_id)) {
            return $info;
        }

        $condition = array('id' => $adjust_id, 'company_id' => get_company_id(), 'status' => array('in', '-2, -1, 3'));
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

    // 调整单审核、驳回
    public function checkAdjust()
    {
        $adjust_id = I('request.adjust_id/d', 0);
        $status = I('request.status/d', 0);
        $check_memo = I('request.check_memo/s', '');

        $info = array(
            'status' => 0,
            'msg' => '操作失败！'
        );
        if (empty($status)) {
            return $info;
        }

        // 单据详情
        $condition = array('id' => $adjust_id, 'company_id' => get_company_id(), 'deleted' => 0);
        $adjust_info = $this->getInfo($condition);

        // 单据详情列表
        $condition = array('adjustdetail.ad_id' => $adjust_id, 'adjustdetail.deleted' => 0);
        $field = 'adjustdetail.*, product.product_code, product.status as product_status, ';
        $join = ' LEFT JOIN __B_PRODUCT__ product ON adjustdetail.p_id = product.id';
        if ($adjust_info['type'] == 1 && $status == 1) {
            $field .= 'IFNULL(procurement.pricemode, 0) AS procurement_pricemode, ';
            $join .= ' LEFT JOIN __B_PROCURE_STORAGE__ procure_storage on procure_storage.id = product.storage_id';
            $join .= ' LEFT JOIN __B_PROCUREMENT__ procurement on procurement.id = procure_storage.procurement_id';
        } elseif ($adjust_info['type'] == 2 && $status == 1) {
            $field .= 'IFNULL(goods_detail.sell_pricemode, 0) AS sell_pricemode, IFNULL(goods_detail.sell_feemode, 0) AS sell_feemode, ';
            $join .= ' LEFT JOIN __B_GOLDGOODS_DETAIL__ goods_detail on goods_detail.goods_id = product.goods_id ';
        }
        $adjust_detail = D('BAdjustDetail')->alias('adjustdetail')->getList($condition, rtrim(rtrim($field), ','), null, $join);
        foreach ($adjust_detail as $key => $value) {
            $adjust_detail[$key]['adjust_before'] = json_decode($value['adjust_before'], true);
            $adjust_detail[$key]['adjust_after'] = json_decode($value['adjust_after'], true);
            if ($status == 1) {
                // 判断开单时货品采购计价方式、销售计价方式与当前是否一致
                if ($adjust_info['type'] == 1 && $adjust_detail[$key]['adjust_before']['price_mode'] != $value['procurement_pricemode']) {
                    $info['msg'] = '货品编码' . $value['product_code'] . '采购计价方式被修改，请验证！';
                } elseif ($adjust_info['type'] == 2 && $adjust_detail[$key]['adjust_before']['sell_pricemode'] != $value['sell_pricemode']) {
                    $info['msg'] = '货品编码' . $value['product_code'] . '销售计价方式被修改，请验证！';
                }
            }
        }

        // 检查状态是否可审核
        $check_adjust_status = $this->_checkAdjustStatus($adjust_id, 2, $adjust_info['product_status']);
        if ($check_adjust_status !== true) {
            $info['msg'] = $check_adjust_status;
            return $info;
        }

        // 检查货品状态是否可调整
        $product_ids = array();
        foreach ($adjust_detail as $key => $value) {
            $result = $this->_checkProductStatus($value['product_code'], $value['product_status'], $adjust_type);
            if ($result !== true) {
                $info['msg'] = $result;
                return $info;
            }
            // 挂起状态下的货品
            if ($adjust_info['type'] == 2) {
                $product_ids[$value['product_id']]= $value['product_status'];
            }
        }

        M()->startTrans();
        // 修改单据状态
        $condition = array('id' => $adjust_id, 'status' => 0, 'deleted' => 0,);
        $data = array('status' => $status, 'check_memo' => $check_memo, 'check_time' => time(), 'check_id' => get_user_id());
        $res = $this->update($condition, $data);
        $info['location'] = 1;

        // 开始调整信息
        if ($res && (INT)$status === 1) {
            if ($adjust_info['type'] == 1) {
                $res = $this->_adjustProcureAction($adjust_detail);
                $info['location'] = 2;
            } elseif ($adjust_info['type'] == 2) {
                $res = $this->_adjustSellAction($adjust_detail, $product_ids);
                $info['location'] = 2;
            } elseif ($adjust_info['type'] == 3) {
                $res = $this->_adjustGoodsAction($adjust_detail);
                $info['location'] = 3;
            }
        }

        // 写入操作记录
        if ($res) {
            if ($status == 1) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::ADJUST, $adjust_id, SELF::CHECK_PASS);
                $info['location'] = 4;
            } elseif ($status == 2) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::ADJUST, $adjust_id, SELF::CHECK_DENY);
                $info['location'] = 5;
            } elseif ($status == -2) {
                $res = D('BBillOpRecord')->addRecord(BBillOpRecordModel::ADJUST, $adjust_id, SELF::CHECK_REJECT);
                $info['location'] = 6;
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

    #TIPS 采购计价方式、销售计价方式、销售工费方式 使用记录的值，不使用当前商品规格中的数据，防止开调整单期间商品规格修改导致错误

    // 采购信息调整
    public function _adjustProcureAction($adjust_detail)
    {
        $res = true;
        $model_bproduct = D('BProduct');
        $model_bproductgold = D('BProductGold');

        foreach($adjust_detail as $key => $value) {

            if ($res !== false) {
                $price_mode = $value['adjust_before']['price_mode'];
                if ($price_mode == '0') {
                    // 计件 改成本价
                    $cost_price = $value['adjust_after']['cost_price'];
                } else {
                    // 计重 修改克重和工费 改成本价
                    $weight = $value['adjust_after']['weight'];
                    $buy_m_fee = $value['adjust_after']['buy_m_fee'];

                    $condition = array(
                        'product_id' => $value['p_id'], 
                        'company_id' => get_company_id(), 
                        'deleted' => 0
                    );
                    $data = array('weight' => $weight, 'buy_m_fee' => $buy_m_fee);
                    $res = $model_bproductgold->update($condition, $data);

                    $productgold_info = $model_bproductgold->getInfo($condition);
                    $cost_price = bcmul(bcadd($buy_m_fee, $productgold_info['buy_price'], 2), $weight, 2);
                }
                if ($res !== false) {
                    $condition = array('id' => $value['p_id'], 'company_id' => get_company_id(), 'deleted' => 0);
                    $data = array('cost_price' => $cost_price);
                    $res = $model_bproduct->update($condition, $data);
                }
            }
        }
        return $res === false ? false : true;
    }

    // 销售信息调整
    public function _adjustSellAction($adjust_detail, $product_ids = array())
    {
        $res = true;
        $model_bproduct = D('BProduct');
        $model_bproductgold = D('BProductGold');

        foreach($adjust_detail as $key => $value) {

            if ($res !== false) {
                $sell_pricemode = $value['adjust_before']['sell_pricemode'];
                $sell_feemode = $value['adjust_before']['sell_feemode'];

                $product_data = array();
                if (!empty($product_ids[$value['p_id']])) {
                    $product_data['status'] = ($product_ids[$value['p_id']] == 13 ? 3 : 4);
                }

                if ($sell_pricemode == 0) {
                    // 计件 改销售价
                    $product_data['sell_price'] = $value['adjust_after']['sell_price'];
                } else {
                    // 计重 改销售克工费
                    $condition = array('product_id' => $value['p_id'], 'company_id' => get_company_id(), 'deleted' => 0);
                    $data = array('sell_fee' => $value['adjust_after']['sell_fee']);
                    $res = $model_bproductgold->update($condition, $data);
                }

                if ($res !== false && !empty($product_data)) {
                    $condition = array('id' => $value['p_id'], 'company_id' => get_company_id(), 'deleted' => 0);
                    $res = $model_bproduct->update($condition, $product_data);
                }
            }
        }

        return $res === false ? false : true;
    }

    // 商品规格调整
    public function _adjustGoodsAction($adjust_detail)
    {
        $res = true;
        $model_bgoods = D('BGoods');
        $model_bproduct = D('BProduct');

        foreach($adjust_detail as $key => $value) {
            $condition = array('goods_code' => $value['adjust_after']['goods_code'], 'company_id' => get_company_id(), 'deleted' => 0);
            $goods_info = $model_bgoods->getInfo($condition);
            if (!empty($goods_info)) {
                $condition = array('id' => $value['p_id'], 'company_id' => get_company_id(), 'deleted' => 0);
                $data = array('goods_id' => $goods_info['id']);
                $res = $model_bproduct->update($condition, $data);
            } else {
                return false;
            }
        }

        return $res === false ? false : true;
    }

    // 获取货品调整的次数
    public function _countProductAdjust($product_id = 0)
    {
        $condition = array(
            'adjustdetail.p_id' => $product_id,
            'adjustdetail.deleted' => 0,
            'adjust.status' => 1,
            'adjust.deleted' => 0
        );
        $join = 'LEFT JOIN __B_ADJUST_DETAIL__ adjustdetail ON adjustdetail.ad_id = adjust.id';
        $count = $this->alias('adjust')->countList($condition, $field, $join);
        return $count;
    }

    // ---------- 单据审核 驳回 撤销 END----------

    /**
     * @author alam
     * @param int $adjust_id
     * @return 操作记录列表
     */
    public function getOperateRecord($adjust_id){
        $condition = array(
            'operate.company_id' => get_company_id(),
            'operate.type' => BBillOpRecordModel::ADJUST,
            'operate.sn_id' => $adjust_id,
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
    public function getProcess($adjust_id){
        $process_list = $this->_groupProcess();
        if (!empty($adjust_id)) {
            $return_info = $this->getAdjustDetail($adjust_id);
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