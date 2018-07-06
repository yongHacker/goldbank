<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BSellModel extends BCommonModel {
    /*操作状态值 */
    const CREATE              =  1;//创建
    const SAVE                =  2;//保存
    const COMMIT              =  3;//提交
    const REVOKE              =  4;//撤销
    const CHECK_PASS          =  5;//审批通过
    const CHECK_DENY          =  6;//审批拒绝

    /*操作状态名*/
    const CREATE_NAME         =  '创建表单';
    const SAVE_NAME           =  '保存表单';
    const COMMIT_NAME         =  '提交表单';
    const REVOKE_NMAE         =  '撤销表单';
    const CHECK_PASS_NMAE     =  '审批通过';
    const CHECK_DENY_NAME     =  '审批拒绝';

    /*操作流程名*/
    const CREATE_PROKEY       =  1;//创建表单流程键值
    const CHECK_PROKEY        =  2;//审核表单流程键值
    const CREATE_PROCESS      =  '创建表单';
    const CHECK_PROCESS       =  '审核表单';

    /*操作函数名*/
    const CHECK_FUNCTION     =  'shop/bsell/check_post';
    public $model_expence_sub;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        parent::_initialize();
        $this->model_expence_sub = D('BExpenceSub');
    }

    // order.sell_price手填销售价 order.count 计算应总售价
    public function add_post(){
        $order_id = b_order_number('BSell','order_id');
        $postArray = I("order_detail");
        $order = I('post.order');
        $shop_id = get_shop_id();
        $count = I('post.count');
        $saccout_record = I('post.saccout_record');
        $recovery_products = I('post.recovery_products'); // 截金货品列表
        $recovery_old_products=I('post.recovery_old_products'); // 以旧换新货品列表
        foreach($postArray as $k => $val){
            $product_ids[] = $val['id'];
            $p_status = D('BProduct')->is_exsit(array('id' => $val['id'], 'status' => 2));
            if(!$p_status){
                $arr['status'] = 0;
                $arr['msg'] = '存在非在库货品！';
                return $arr;
            }
        }
        //检测销售的以旧换新和截金信息编号是否重复，或者是否编号已经存在
        $info=D('BRecoveryProduct')->check_sell_recovery_product($recovery_old_products,$recovery_products);
        if($info['status']==0){
            return $info;
        }
        $param = $this->get_insert_data($order, get_shop_id(), $order_id, $count/*, $total_onsale_price*/);
        M()->startTrans();
        // 销售单
        $sell_id =$this->insert($param);

        // 销售单详细
        $li2 = true;
        if (!empty($postArray)) {
            $li2 = $this->add_sell_detail($postArray, $sell_id/*, $rate*/);
        }
        // 处理以旧换新数据
        $old_product_detail = true;
        if($recovery_old_products != '' && count($recovery_old_products) > 0){
            $old_product_detail = D('BRecoveryProduct')->add_product($recovery_old_products,$sell_id,4);
        }

        // 处理截金数据
        $recovery_detail = true;
        if($recovery_products != '' && count($recovery_products) > 0){
            $recovery_detail =  D('BRecoveryProduct')->add_product($recovery_products,$sell_id,1);
        }
        /*change by alam 2018/5/11 start*/
        // 处理其它费用
        $insert_sub = $this->model_expence_sub->addList($sell_id, 1);
        /*change by alam 2018/5/11 end*/
        //判断默认币种是否使用，更新使用字段
        D('BCurrency')->is_use($order["main_currency_id"]);
        // 处理收款明细
        $saccout_detail = $this->add_saccount_record($saccout_record,$sell_id,$shop_id,$order);

        // 提交时修改货品状态，保存不修改货品状态
        $li = true;
        if (!empty($product_ids)) {
            $condition = array(
                'id' => array('in', $product_ids)
            );
            $update_product = array(
                'status' => 4,
                'sell_time' => $param['sell_time']
            );
            $li = D('BProduct')->update($condition, $update_product);
        }
        //销售单创建记录或保存记录
        $create_record=D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL,$sell_id,self::CREATE);//创建
        if($create_record){
            $record_status=$param['status']==0?self::COMMIT:self::SAVE;
            $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL,$sell_id,$record_status);//保存或提交
        }
        if($old_product_detail&&$record_result&&$sell_id !== false && $li !== false && $li2 !== false && $saccout_detail !== false && $recovery_detail !== false && $insert_sub !== false) {
            M()->commit();
            S('session_menu' . get_current_admin_id(), null);
            $arr['status'] = 1;
            $arr['url'] = U("BSell/index");
        } else {
            M()->rollback();
            $recovery_detail = $recovery_detail ? 1 : 0;
            $arr['status'] = 0;
            $arr['msg'] = '添加失败！';
            $arr['test'] = $sell_id."//".$li."//".$li2."//".$saccout_detail."//".$recovery_detail."//".$add_sell_sub.'//'.count($recovery_products);
        }
        return $arr;
    }
    //编辑
    public function edit_post(){
        //$order_id = b_order_number('BSell','order_id');
        $postArray = I("order_detail");
        $order = I('post.order');
        $sell_info=$this->getInfo(array('id'=>$order['sell_id']));
        $order_id=$sell_info['order_id'];
        $shop_id = get_shop_id();
        $count =$order['product_count'];
        $saccout_record = I('post.saccout_record');
        $recovery_products = I('post.recovery_products'); // 截金货品列表
        $recovery_old_products = I('post.recovery_old_products'); //以旧换新详情列表
        $del_old_product_detail = I('post.del_old_product_detail'); // 删除的以旧换新明细
        $del_sell_detail = I('post.del_sell_detail'); // 删除的销售货品明细
        $del_product_detail=I('post.del_product_detail'); // 删除的货品明细
        $del_sub_detail = I('post.del_sub_detail'); // 删除的其他费用明细
        $del_cut_detail = I('post.del_cut_detail'); // 删除的截金明细
        $del_saccount_detail = I('post.del_saccount_detail'); // 删除的收款明细
        foreach($postArray as $k => $val){
            $product_ids[] = $val['id'];
            if(empty($val['sell_detail_id'])){
                $p_status = D('BProduct')->is_exsit(array('id' => $val['id'], 'status' => 2));
                if(!$p_status){
                    $arr['status'] = 0;
                    $arr['msg'] = '存在非在库货品！';
                    return $arr;
                }
            }

        }
        $condition=array('id'=>$order['sell_id'],'company_id'=>get_company_id(),'status'=>array('in','-1'));
        $check_info =$this->getInfo($condition);
        if(empty($check_info)){
            $arr['status'] = 0;
            $arr['msg'] = '订单不存在！';
            return $arr;
        }
        $condition=array('id'=>$order['sell_id'],'company_id'=>get_company_id(),'status'=>array('in','-1'));
        $check_info =$this->getInfo($condition);
        if(empty($check_info)){
            $arr['status'] = 0;
            $arr['msg'] = '订单不存在！';
            return $arr;
        }
        //检测销售的以旧换新和截金信息编号是否重复，或者是否编号已经存在
        $info=D('BRecoveryProduct')->check_sell_recovery_product($recovery_old_products,$recovery_products);
        if($info['status']==0){
            return $info;
        }
        $param = $this->get_insert_data($order, $shop_id, $order_id, $count);
        M()->startTrans();
        // 销售单
        $sell_id =$this->update($condition,$param);
        // 销售单详细
        $li2 = true;
        if (!empty($postArray)) {
            $li2 = $this->update_sell_detail($postArray, $order['sell_id'],$del_sell_detail,$del_product_detail);
        }
        $recovery_detail =D('BRecoveryProduct')->update_product($recovery_products,$order['sell_id'],1,$del_cut_detail);
        $recovery_old_product_detail = D('BRecoveryProduct')->update_product($recovery_old_products,$order['sell_id'],4,$del_old_product_detail);
        // 处理其它费用
        $insert_sub = $this->model_expence_sub->editList($order['sell_id'], 1);
        //判断默认币种是否使用，更新使用字段
        D('BCurrency')->is_use($order["main_currency_id"]);
        // 处理收款明细
        $saccout_detail = $this->update_saccount_record($saccout_record,$order['sell_id'],$shop_id,$order,$del_saccount_detail);
        // 提交时修改货品状态，保存不修改货品状态
        $li = true;
        if (!empty($product_ids)) {
            $condition = array(
                'id' => array('in', $product_ids)
            );
            $update_product = array(
                'status' => 4,
                'sell_time' => $param['sell_time']
            );
            $li = D('BProduct')->update($condition, $update_product);
        }
        //销售编辑的保存或提交的操作记录
        $record_status=$param['status']==0?self::COMMIT:self::SAVE;
        $record_result=D('BBillOpRecord')->addRecord(BBillOpRecordModel::SELL,$order['sell_id'],$record_status);//保存或提交
        if($recovery_old_product_detail&&$record_result&&$sell_id !== false && $li !== false && $li2 !== false && $saccout_detail !== false && $recovery_detail !== false && $insert_sub !== false) {
            M()->commit();
            $arr['status'] = 1;
            $arr['url'] = U("BSell/index");
        } else {
            M()->rollback();
            $recovery_detail = $recovery_detail ? 1 : 0;
            $arr['status'] = 0;
            $arr['msg'] = '添加失败！';
            $arr['test'] = $sell_id."/".$li."/".$li2."/".$saccout_detail."/".$recovery_detail."/".$insert_sub.'/'.count($recovery_products);
        }
        return $arr;
    }
    //获取销售单添加信息
    /**
     * @param $order
     * @param $buyer_id
     * @param $shop_id
     * @param $order_id
     * @param $count
     * @param $total_onsale_price
     * @return array
     */
    public function get_insert_data($order, $shop_id, $order_id, $count/*,$total_onsale_price*/){
        $param=array();
        $param["company_id"]=get_company_id();
        $param["shop_id"]=$shop_id;
        $param["creator_id"]=get_user_id();
        $param['status']=0;
        $param['order_id'] =$order_id;
        $param['shop_id']=$shop_id;
        $param['count']=$count;
        $param["create_time"]=time();
        $param["sell_time"]=empty($order["sell_date"])?time():strtotime($order["sell_date"]);
        $condition = array('id' => $order['buyer_id']);
        $client = M('BClient')->where($condition)->find();
        $param["buyer_id"]=$client['user_id'];
        $param["client_id"]= $order['buyer_id'];
        $param["memo"]=$order["remark"];
        // $param["price"]=$total_onsale_price;
        $param["real_sell_price"]=$order["count"];
        $param["extra_price"]=$order["extra_price"];
        $param["status"]=$order["status"];
        $param["sell_type"]=$order["sell_type"];
        $param["client_idno"]=$order["client_idno"];
        $param["sn_id"]=$order["sn_id"];
        $wh_info=get_shop_wh_id();
        $param["recovery_wh_id"]=$wh_info["id"];
        return $param;
    }
    //批量添加收款明细
    /**
     * @param $saccout_record
     * @param $sell_id
     * @param $shop_id
     * @param $rate
     * @return bool|string
     */
    public function add_saccount_record($saccout_record,$sell_id,$shop_id,$order){
        $saccount_details=array();
        foreach($saccout_record as $k=>$v){
            $saccount_detail=array();
            $saccount_detail["company_id"]=get_company_id();
            $saccount_detail["shop_id"]=$shop_id;
            $saccount_detail["sn_id"]=$sell_id;
            $saccount_detail["flow_id"]=$v["flow_id"];
            $saccount_detail["pay_id"]=$v["pay_id"];
            $saccount_detail["currency_id"]=$v["currency"];
            //判断币种是否使用，更新使用字段
            D('BCurrency')->is_use($v["currency"]);
            $saccount_detail["currency_rate"]=$v["currency_rate"];
            $saccount_detail["main_currency_id"]=$order["main_currency_id"];
            $saccount_detail["main_currency_rate"]=$order["main_currency_rate"];
            $saccount_detail["pay_price"]=$v["pay_price"];
            $saccount_detail["receipt_price"]=$v["actual_price"];
            $saccount_detail["creator_id"]=get_user_id();
            $saccount_detail["create_time"]=time();
            $saccount_detail["type"]=1;
            $saccount_detail["status"]=-1;
            $saccount_detail["deleted"]=0;
            $saccount_details[]=$saccount_detail;
        }
        $b_saccout_record = D("b_saccount_record")->addAll($saccount_details);
        return $b_saccout_record;
    }
    //添加销售明细
    /**
     * @param $postArray
     * @param $sell_id
     * @param $rate
     * @return bool|string
     */
    public function add_sell_detail($postArray, $sell_id/*, $rate*/){
        $sell_details=array();
        $is_ture=true;
        foreach($postArray as $k2=>$v){
            $gold_type=$v['gold_type'];
            //销售明细写入
            $sell_detail["sell_id"]=$sell_id;//订单id
            $sell_detail["actual_price"]=$v["rell_sell_price"];//实际售价
            $sell_detail["sell_fee"]=$v["sell_m_fee"];//工费
            $sell_detail["product_id"]=$v['id'];//货品id
            if ($v['sell_pricemode'] == '计重') {
                //$sell_detail["sell_g_price"]=$v['sell_g_price'];//销售金价
                $sell_detail["gold_price"]=$v['sell_g_price'];//销售金价
                $sell_detail['sell_pricemode']=1;
                $sell_detail["detail_sell_feemode"]=$v["sell_feemode"];//销售工费方式
            }else{
                $sell_detail["gold_price"]=0;//销售金价
                $sell_detail['sell_pricemode']=0;
                $sell_detail["detail_sell_feemode"]=0;
            }
            $sell_detail["discount_price"]=$v["discount_price"];//单品优惠
            $sell_detail["sell_price"]=$v["be_onsale_price"];//销售指导价
            $sell_detail['actual_price']=(float)$v['sell_price']/**$rate*/;//真实售价
            $sell_details[]=$sell_detail;
            //更新素金明细的销售信息
            if($v['goods_weight']){
                if($v['is_cut']==1){
                    $productdata=array("is_cut"=>$v['is_cut'],"cut_weight"=>$v['cut_weight'],"sell_weight"=>$v['goods_weight']
                    ,'sell_m_fee'=>$v["sell_m_fee"],'sell_g_price'=>$v["sell_g_price"]);
                }else {
                    $productdata = array("sell_weight" => $v['goods_weight'],'sell_m_fee'=>$v["sell_m_fee"],'sell_g_price'=>$v["sell_g_price"]);
                }
                $BProductGold=D("BProductGold")->update(array("product_id"=>$sell_detail['product_id']),$productdata);
                //echo M()->getLastSql();die();
                if($BProductGold===false){
                    $is_ture=false;
                    return false;
                }
            }
        }
        if(!$is_ture){
            return false;
        }
        $li2 = D("b_sell_detail")->addAll($sell_details);
        return $li2;
    }
    //批量添加收款明细
    /**
     * @param $saccout_record
     * @param $sell_id
     * @param $shop_id
     * @param $rate
     * @return bool|string
     */
    public function update_saccount_record($saccout_record,$sell_id,$shop_id,$order,$del_saccount_detail){
        $saccount_details=array();
        foreach($saccout_record as $k=>$v){
            $saccount_detail=array();
            $saccount_detail["company_id"]=get_company_id();
            $saccount_detail["shop_id"]=$shop_id;
            $saccount_detail["sn_id"]=$sell_id;
            $saccount_detail["flow_id"]=$v["flow_id"];
            $saccount_detail["pay_id"]=$v["pay_id"];
            $saccount_detail["currency_id"]=$v["currency"];
            //判断币种是否使用，更新使用字段
            D('BCurrency')->is_use($v["currency"]);
            $saccount_detail["currency_rate"]=$v["currency_rate"];
            $saccount_detail["main_currency_id"]=$order["main_currency_id"];
            $saccount_detail["main_currency_rate"]=$order["main_currency_rate"];
            $saccount_detail["pay_price"]=$v["pay_price"];
            $saccount_detail["receipt_price"]=$v["actual_price"];
            $saccount_detail["creator_id"]=get_user_id();
            $saccount_detail["create_time"]=time();
            $saccount_detail["type"]=1;
            $saccount_detail["status"]=-1;
            $saccount_detail["deleted"]=0;
            $saccount_detail["id"]=$v['saccount_id'];
            $saccount_details[]=$saccount_detail;
        }
        $del_result=true;
        //编辑删除销售货品明细
        if(!empty($del_saccount_detail)){
            $del_result=D("BSaccountRecord")->update(array('id'=>array('in',$del_saccount_detail)),array('deleted'=>1));
        }
        $b_saccout_record = D("BSaccountRecord")->addAll($saccount_details,array(),true);
        return $b_saccout_record&&$del_result;
    }
    //添加销售明细
    /**
     * @param $postArray
     * @param $sell_id
     * @param $rate
     * @return bool|string
     */
    public function update_sell_detail($postArray, $sell_id,$del_sell_detail,$del_product_detail){
        $sell_details=array();
        $is_ture=true;
        foreach($postArray as $k2=>$v){
            $gold_type=$v['gold_type'];
            //销售明细写入
            $sell_detail["sell_id"]=$sell_id;//订单id
            $sell_detail["actual_price"]=$v["rell_sell_price"];//实际售价
            $sell_detail["sell_fee"]=$v["sell_m_fee"];//工费
            $sell_detail["product_id"]=$v['id'];//货品id
            if ($v['sell_pricemode'] == '计重') {
                //$sell_detail["sell_g_price"]=$v['sell_g_price'];//销售金价
                $sell_detail["gold_price"]=$v['sell_g_price'];//销售金价
                $sell_detail['sell_pricemode']=1;
                $sell_detail["detail_sell_feemode"]=$v["sell_feemode"];//销售工费方式
            }else{
                $sell_detail["gold_price"]=0;//销售金价
                $sell_detail['sell_pricemode']=0;
                $sell_detail["detail_sell_feemode"]=0;
            }
            $sell_detail["discount_price"]=$v["discount_price"];//单品优惠
            $sell_detail["sell_price"]=$v["be_onsale_price"];//销售指导价
            $sell_detail['actual_price']=(float)$v['sell_price']/**$rate*/;//真实售价
            $sell_detail["id"]=$v["sell_detail_id"];//销售指导价
            $sell_details[]=$sell_detail;
            //更新素金明细的销售信息
            if($v['goods_weight']){
                if($v['is_cut']==1){
                    $productdata=array("is_cut"=>1,"cut_weight"=>$v['cut_weight'],"sell_weight"=>$v['goods_weight']
                    ,'sell_m_fee'=>$v["sell_m_fee"],'sell_g_price'=>$v["sell_g_price"]);
                }else {
                    $productdata = array("is_cut"=>0,"cut_weight"=>0,"sell_weight" => $v['goods_weight']
                    ,'sell_m_fee'=>$v["sell_m_fee"],'sell_g_price'=>$v["sell_g_price"]);
                }
                $BProductGold=D("BProductGold")->update(array("product_id"=>$sell_detail['product_id']),$productdata);
                if($BProductGold===false){
                    $is_ture=false;
                    return false;
                }
            }
        }
        if(!$is_ture){
            return false;
        }
        $del_result=true;
        //编辑删除销售货品明细
        if(!empty($del_sell_detail)){
            $del_result=D("BSellDetail")->update(array('id'=>array('in',$del_sell_detail)),array('deleted'=>1));
            $del_result2=D("BProduct")->update(array('id'=>array('in',$del_product_detail)),array('status'=>2));
            $del_result=$del_result&&$del_result2;
        }
        $li2 = D("BSellDetail")->addAll($sell_details,$options=array(),true);
        return $li2&&$del_result;
    }
    // 销售单付款信息
    public function getsaccount_list($sell_id = 0){
        $where = array(
            'sa.sn_id'=> $sell_id,
            'sa.deleted'=> 0
        );

        $field = 'sa.*, (CASE sa.status
            WHEN "-1" THEN "待审核"
            WHEN "0" THEN "未确认"
            WHEN "1" THEN "已确认"
            WHEN "2" THEN "确认不通过"
            ELSE "-" END) as show_status';
        $field .= ', cu.currency_name, cu.exchange_rate, cu.unit';
        $field .= ', cu2.currency_name main_currency_name';
        $field .= ', p.pay_name, p.pay_fee, p.pay_type';

        $join = ' LEFT JOIN '.C('DB_PREFIX').'b_currency as cu ON (cu.id = sa.currency_id)';
        $join .= ' LEFT JOIN '.C('DB_PREFIX').'b_currency as cu2 ON (cu2.id = sa.main_currency_id)';
        $join .= ' LEFT JOIN '.C('DB_PREFIX').'b_payment as p ON (p.id = sa.pay_id)';

        $sa_list = D('b_saccount_record')->alias('sa')->getList($where, $field, $limit = '', $join);

        return $sa_list;
    }

    //获取一条销售的所有信息
    public function getInfo_detail(){
        $condition=array("bsell.company_id"=>get_company_id(),"bsell.deleted"=>0,"bsell.id"=>I("get.id",0,"intval"));
        $join=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=bsell.creator_id and create_musers.deleted=0 and create_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=bsell.check_id  and check_musers.deleted=0 and check_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_employee  buy_musers on buy_musers.user_id=bsell.buyer_id  and buy_musers.deleted=0 and buy_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_shop b_shop on b_shop.id=bsell.shop_id";
        $join.=" left join ".DB_PRE."b_client b_client on b_client.id=bsell.client_id";
        $field="bsell.*";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,b_client.client_name buy_name,b_shop.shop_name";
        $bsell_data=$this->alias("bsell")->getInfo($condition,$field,$join,$order='bsell.id desc');
        return $bsell_data;
    }
    //获取销售明细的货品id
    public function get_pruductids($sell_id,$field="product_id"){
        $condition=array("bsell.id"=>$sell_id,"bsell.company_id"=>get_company_id(),"bsell.deleted"=>0,"bselldetail.deleted"=>0);
        $join="left join ".DB_PRE."b_sell_detail bselldetail on bsell.id=bselldetail.sell_id";
        $productid =$this->alias("bsell")->where($condition)->join($join)->getField($field, true);
        return $productid;
    }
    // 导出 excel
    /**
     * @param $excel_where
     * @param string $excel_field
     * @param string $excel_join
     * @param int $page
     * @param string $action
     */
    public function excel_out($excel_where, $excel_field='', $excel_join='', $page = 1,$action='excel_out'){
        set_time_limit(0);
        $limit = (($page - 1) * intval(500)) . "," . (intval(500));
        $condition=array();
        $condition ["table"] = DB_PRE.'b_sell';
        $condition ["field"] = 'status';
        $status_list = D ( 'b_status' )->getFieldValue($condition);
        $join=" left join ".DB_PRE."b_employee  create_musers on create_musers.user_id=bsell.creator_id and create_musers.deleted=0 and create_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_employee  check_musers on check_musers.user_id=bsell.check_id  and check_musers.deleted=0 and check_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_employee  buy_musers on buy_musers.user_id=bsell.buyer_id  and buy_musers.deleted=0 and buy_musers.company_id=bsell.company_id";
        $join.=" left join ".DB_PRE."b_shop b_shop on b_shop.id=bsell.shop_id";
        $join.=" left join ".DB_PRE."b_client b_client on b_client.id=bsell.client_id";
        $join.=" left join ".DB_PRE."b_sell_detail b_sell_detail on b_sell_detail.sell_id=bsell.id ";
        $join.=" left join ".DB_PRE."b_product bproduct on bproduct.id=b_sell_detail.product_id ";
        $join.=D('BProduct')->get_product_join_str();
        $field="bsell.*";
        $field.=",create_musers.employee_name user_nicename,check_musers.employee_name check_name,b_client.client_name buy_name,b_shop.shop_name";
        $field.=",b_sell_detail.sell_fee,b_sell_detail.sell_pricemode,b_sell_detail.sell_price,b_sell_detail.actual_price";
        $field.=",b_sell_detail.gold_price,b_sell_detail.discount_price,b_sell_detail.detail_sell_feemode";
        $field.=",bproduct.type product_type,bproduct.product_code,bgoods.goods_code,bgoods.goods_common_id,g_common.class_id,g_common.goods_name";
        $field.=D('BProduct')->get_product_field_str();
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $excel_where['b_sell_detail.deleted']=0;
        $data=$this->alias("bsell")->getList($excel_where,$field,$limit,$join,$order='bsell.id desc');
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['batch'] = $v['order_id'];
                $expotdata[$k]['sn_id'] = $v['sn_id'];
                $expotdata[$k]['user_nicename'] = $v['user_nicename'];
                $expotdata[$k]['buy_name'] = $v['buy_name'];
                $expotdata[$k]['price'] = $v['price'];
                $expotdata[$k]['count'] =$v['count'];
                $expotdata[$k]['sell_time'] = empty($v['sell_time'])?'':date('Y-m-d H:i:s',$v['sell_time']);
                $expotdata[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $expotdata[$k]['status'] = $status_list[$v['status']];
                $expotdata[$k]['class_name'] = D('BGoodsClass')->classNav($v['class_id']);
                $expotdata[$k]['goods_name'] = $v['goods_name'];
                $expotdata[$k]['goods_code'] = $v['goods_code'];
                $expotdata[$k]['product_code'] = $v['product_code'];
                $expotdata[$k]['weight'] = $v['weight'];
                $expotdata[$k]['purity'] = $v['purity'];
                $expotdata[$k]['detail_sell_feemode'] = $v['product_type']==1?($v['detail_sell_feemode']==1?'克工费销售':'件工费销售'):'-';
                $expotdata[$k]['sell_fee'] = $v['sell_fee'];
                $expotdata[$k]['gold_price'] = $v['gold_price'];
                $expotdata[$k]['sell_price'] = $v['sell_price'];
                $expotdata[$k]['discount_price'] = $v['discount_price'];
                $expotdata[$k]['actual_price'] = $v['actual_price'];
                $expotdata[$k]['sell_pricemode'] = $v['sell_pricemode']==1?'计重':'计件';
            }
            register_shutdown_function(array(&$this, $action),$excel_where,$excel_field, $excel_join, $page + 1);
            $title=array('序','单号','外部订单号','销售员','会员','销售总价','数量','销售时间','制单时间','状态',
                '商品分类','商品名称','商品编号','货品编号','重量','含金量','销售工费方式','工费','克单价','建议售价','优惠金额','实际售价','计价方式');
            exportexcel($expotdata,$title,"销售记录列表");
        }
    }
    /**
     * @author chenzy
     * @param int $sell_id 销售单id
     * @return 操作记录列表
     */
    public function getOperateRecord($sell_id){
        $condition=array(
            'operate.company_id'=>get_company_id(),
            'operate.type'=>BBillOpRecordModel::SELL,
            'operate.sn_id'=>$sell_id,
            'employee.deleted'=>0,
            'employee.company_id'=>get_company_id(),
        );
        $field="operate.operate_type,operate.operate_time,employee.employee_name";
        $join="join gb_b_employee employee on employee.user_id=operate.operate_id";
        $record_list=D('BBillOpRecord')->alias("operate")->getList($condition,$field,'',$join,'operate.id asc');
        $type_list=$this->_groupType();
        foreach ($record_list as $key => $val){
            $record_list[$key]['operate_name']=$type_list[$val['operate_type']];
        }
        return $record_list;
    }
    /**
     * @author chenzy
     * 将所有的状态码组合起来
     */
    private function _groupType(){
        return array(
            self::CREATE=>self::CREATE_NAME,
            self::SAVE=>self::SAVE_NAME,
            self::COMMIT=>self::COMMIT_NAME,
            self::REVOKE=>self::REVOKE_NMAE,
            self::CHECK_PASS=>self::CHECK_PASS_NMAE,
            self::CHECK_DENY=>self::CHECK_DENY_NAME
        );
    }
    /**
     * @author chenzy
     * 获取流程数组
     */
    public function getProcess($sell_id){
        $process_list=$this->_groupProcess();
        if(!empty($sell_id)){
            $sell_info=$this->getInfo_detail();
            $process_list[self::CREATE_PROKEY]['is_done']=1;
            $process_list[self::CREATE_PROKEY]['user_name']=$sell_info['user_nicename'];
            $process_list[self::CREATE_PROKEY]['time']=$sell_info['create_time'];
            /*检查是否审核*/
            if($sell_info['check_id']>0&&($sell_info['status']==1||$sell_info['status']==2)){
                $process_list[self::CHECK_PROKEY]['is_done']=1;
                $process_list[self::CHECK_PROKEY]['user_name']=$sell_info['check_name'];
                $process_list[self::CHECK_PROKEY]['time']=$sell_info['check_time'];
            }else{
                $process_list[self::CHECK_PROKEY]['is_done']=0;
                //没有审核读取审核权限的员工
                $employee_name=D('BAuthAccess')->getEmployeenamesByRolename(self::CHECK_FUNCTION);
                $process_list[self::CHECK_PROKEY]['user_name']=$employee_name?$employee_name:'该权限人员暂缺';
            }
        }
        return $process_list;

    }
    /**
     * @author chenzy
     * 将所有的流程组合起来
     */
    private function _groupProcess(){
        return array(
            self::CREATE_PROKEY=>array(
                'process_name'=>self::CREATE_PROCESS,
            ),
            self::CHECK_PROKEY=>array(
                'process_name'=>self::CHECK_PROCESS,
            )
        );
    }
}
