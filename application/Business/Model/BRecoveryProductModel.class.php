<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BRecoveryProductModel extends BCommonModel{

    public function getList_detail($where){
        $condition=array('brecoverydetail.deleted'=>0);
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."b_product bproduct on bproduct.id=brecoverydetail.product_id";
        $field="brecoverydetail.*,bproduct.type,bproduct.product_code,bproduct.goods_id";
        $detail_data=$this->alias("brecoverydetail")->getList($condition,$field,$limit="",$join,$order='brecoverydetail.id desc');
        foreach ($detail_data as $key => $value) {
            $detail_data[$key]['purity'] = bcmul($value['purity'], 1000, 3);
        }
        return $detail_data;
    }

    public function excel_out($excel_where, $excel_field='', $excel_join='', $page = 1, $action='excel_out'){
        C('SHOW_PAGE_TRACE', false);

        set_time_limit(0);

        $limit = (($page - 1) * intval(500)) . "," . (intval(500));

        $data = $this->alias('rproduct')->getList($excel_where, $field = '*', $limit, $join, $order='id desc');
        $filename = "rproduct_list";
        if($data){
            $expotdata = array();

            foreach($data as $k=>$v){

                $show_type = '-';
                switch ($v['type']) {
                    case 0: $show_type = '金料回购'; break;
                    case 1: $show_type = '销售截金'; break;
                    case 2: $show_type = '金料采购'; break;
                    case 3: $show_type = '结算来料'; break;
                }

                $show_status = '-';
                switch ($v['status']) {
                    case 0: 
                        if($v['type'] == 0){ $show_status = '回购中'; }
                        if($v['type'] == 1){ $show_status = '销售回购中'; }
                        if($v['type'] == 2){ $show_status = '采购中'; }
                        if($v['type'] == 3){ $show_status = '来料结算中'; }
                    break;
                    case 1: $show_status = '正常在库'; break;
                    case 2: 
                        if($v['type'] == 0){ $show_status = '回购审核不通过'; }
                        if($v['type'] == 1){ $show_status = '销售审核不通过'; }
                        if($v['type'] == 2){ $show_status = '采购审核不通过'; }
                        if($v['type'] == 3){ $show_status = '结算单审核不通过'; }
                    break;
                    case 3: 
                        // $show_status = '已撤销'; 
                        if($v['type'] == 0){ $show_status = '回购单已撤销'; }
                        elseif($v['type'] == 3){ $show_status = '结算单已撤销'; }
                        else{ $show_status = '已撤销'; }
                    break;
                    case 4: 
                        $show_status = '结算中';
                    break;
                    case 5: 
                        $show_status = '结算完成';
                    break;
                }

                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);

                $expotdata[$k]['rproduct_code'] = $v['rproduct_code'];
                $expotdata[$k]['recovery_name'] = iconv('utf-8', 'gbk', $v['recovery_name']);
                $expotdata[$k]['gold_price'] = $v['gold_price'];
                $expotdata[$k]['gold_weight'] = $v['gold_weight'];
                $expotdata[$k]['total_weight'] = $v['total_weight'];
                $expotdata[$k]['purity'] = $v['purity'];
                $expotdata[$k]['attrition'] = $v['attrition'];
                $expotdata[$k]['service_fee'] = $v['service_fee'];
                $expotdata[$k]['cost_price'] = $v['cost_price'];

                $expotdata[$k]['show_type'] = $show_type; //iconv('utf-8', 'gbk', $show_type);
                $expotdata[$k]['show_status'] = $show_status; //iconv('utf-8', 'gbk', $show_status);
            }

            register_shutdown_function(array(&$this, $action), $excel_where, $excel_field, $excel_join, $page + 1);
            
            $title = array('序','金料编号','金料名称','金价','金重', '总重', '纯度', '损耗率', '克工费', '成本价', '来源', '金料状态');

            exportexcel($expotdata, $title, "rproduct_list", 2);
        }else{
            // exportexcel 最后一个参数不为2时，注释下面代码
            $filepath = SITE_PATH . "Uploads/export/".$filename.".xls";

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($filepath));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));

            // 下载文件
            readfile($filepath);
        }
    }

    // 验证产品
    /**
     *采购判断采购单内金料是否重复，采购单内金料和采购单外的金料(采购单外的金料=未入库和采购单金料除外)是否重复
     * 未入库  status=0
     * @param array $data  单据内的金料数据
     * @param int $procurement_id   单据id
     * @return array
     */
    public function product_check($data = array(), $procurement_id = 0){
        $info = array('status'=> 1);

        $product_codes = array();
        foreach($data as $key => $val){
            $product_codes[] = $val['rproduct_code'];
        }
        $exist_rproduct=array();//存储金料编号已经存在的编号数组
        //$rproduct_code_tr=array();//存储金料编号已经存在的行号
        $repeat_rproduct=array();//当前单据重复的编号
        //$repeat_rproduct_code_tr=array();//当前单据重复的行号
        foreach($data as $key => $val){

            // 旧数据如果更改了 rproduct_code 需要重新验证 jk.2018.03.21
            if($val['is_old'] > 0||$val['recovery_product_id'] > 0){
                $recovery_product_id=empty($val['recovery_product_id'])?$val['is_old']:$val['recovery_product_id'];
                $old_data = $this->where(array('id'=>$recovery_product_id))->field('rproduct_code')->find();
                if($old_data['rproduct_code'] == $val['rproduct_code']){
                    continue;
                }
            }
            $product_code = $product_codes;
            unset($product_code[$key]);
            $repeat_key = array_search($val['rproduct_code'], $product_code);
            $inarray = in_array($val['rproduct_code'], $product_code);
            if(($repeat_key != false || $inarray) && $val['rproduct_code'] != '' ){
                $info['status'] = '0';
                $repeat_rproduct[$key+1]=$val['rproduct_code'];
                $info['msg'] .= '第'.($key+1).'行数据金料编码('.$val['rproduct_code'].')与第'.($repeat_key+1).'行数据金料编码('.$val['rproduct_code'].')重复!</br>';
            }
            /*change by alam 2018/06/02 为了实现打乱金料详情中 金料编号 顺序，或者删除后重新添加同样的 金料编号 start*/
            $where_count = array(
                'rproduct_code'=> $val['rproduct_code'],
                'deleted'=> 0,
               // 'status'=> array('neq',0),
                'company_id' => get_company_id()
            );

            // 采购入库单id
            if (!empty($procurement_id)) {
                $procure_storage_id = D('BProcureStorage')->where(array('procurement_id' => $procurement_id, 'deleted' => 0))->getField('id');
                if (!empty($procure_storage_id)) {
                    $where_count['storage_id'] = array('neq', $procure_storage_id);
                }
            }
            /*change by alam 2018/06/02 end*/

            $count = $this->where($where_count)->count();
            if($count > 0){
                $info['status'] = '0';
                $exist_rproduct[$key+1]=$val['rproduct_code'];
               /* array_push($exist_rproduct_code,$val['rproduct_code']);
                array_push($rproduct_code_tr,($key+1));*/
                $info['msg'] .= '第'.($key+1).'行数据金料编码('.$val['rproduct_code'].')与已有金料重复!';
            }
        }
        if($info['status'] ==0){
            $repeat_rproduct=array_diff($repeat_rproduct,$exist_rproduct);//取不在$exist_rproduct中的数据
            $err_rproduct=array_merge($exist_rproduct,$repeat_rproduct);//获取所有需要修改的数据
            $err_rproduct_code=implode(',',$err_rproduct);
            $rproduct_code_tr=array_merge(array_keys($exist_rproduct),array_keys($repeat_rproduct));
            $rproduct_code_tr=implode(',',$rproduct_code_tr);
            $info['exist_rproduct_code'] =$err_rproduct_code;//只是用于判断数据是否存在
            $info['rproduct_code_tr'] =$rproduct_code_tr;//所有需要修改的行号
            $repeat_msg1=empty($repeat_rproduct)?'':'金料编码('.implode(',',$repeat_rproduct).')在当前数据中重复！';
            $repeat_msg2=empty($exist_rproduct)?'':'金料编码('.implode(',',$exist_rproduct).')与已有金料重复!';
            $info['msg'] = $repeat_msg2.$repeat_msg1;
        }
        return $info;
    }

    // 验证excel数据
    public function excel_check($data){
        $product_codes = array();

        foreach($data as $key=>$val){
            $product_codes[] = $val[1];   // 1 为金料编码
        }

        $msg = "";

        foreach($data as $key => $val){

            $product_code = $product_codes;
            unset($product_code[$key]);

            $repeat_key = array_search($val[1], $product_code);
            $inarray = in_array($val[1], $product_code);
            if($val[1] !== '' && $repeat_key != false || $inarray){
                $msg .= '第'.($key+1).'行数据金料编码与第'.($repeat_key+1).'行数据金料编码重复!</br>';
            }
        }

        // 判断 company_id，否则会窜到其他商户商品信息
        foreach($data as $key => $val){

            if($val[1] != ''){
                $where = array(
                    'company_id'=> get_company_id(),
                    'rproduct_code'=> $val[1],
                    'deleted'=> 0
                );

                $exist_info = $this->where($where)->find();
                if(!empty($exist_info)){
                    $msg .= "导入的excel文件中第".($key+2)."行，金料编码为".$val[1]."的金料已存在，请修改后重新导入！</br>";
                }
            }
        }

        return $msg;
    }

    /**
     * 检测销售的以旧换新和截金信息编号是否重复，或者是否编号已经存在
     * @param $recovery_old_products
     * @param $recovery_products
     * @return mixed
     */
    function check_sell_recovery_product($recovery_old_products,$recovery_products){
        $recovery_old_products=empty($recovery_old_products)?array():$recovery_old_products;
		$recovery_products=empty($recovery_products)?array():$recovery_products;
        //判断截金信息是否和以旧换新金料编号重复
        if(!empty($recovery_products)&&!empty($recovery_old_products)){
            $c_rproduct_code = array_column($recovery_products, 'rproduct_code');
            $r_rproduct_code = array_column($recovery_old_products, 'rproduct_code');
            $new_recovery_product=array_intersect($c_rproduct_code,$r_rproduct_code);//取交集
            if(!empty($new_recovery_product)){
                $info['msg']='截金数据金料编号不能和以旧换新金料编号存在重复';
                $info['status']=0;
                return $info;
            }
        }
        //判断以旧换新信息是否重复
        if($recovery_old_products != '' && count($recovery_old_products) > 0){
            // 查重 rproduct_code
            $info = D('BRecoveryProduct')->product_check($recovery_old_products);
            if($info['status'] == 0){
                $info['msg']='以旧换新：'.$info['msg'];
                $info['rproduct_type']=1;
                return $info;
            }
        }
        // 处理截金数据
        if($recovery_products != '' && count($recovery_products) > 0){
            // 查重 rproduct_code
            $info = D('BRecoveryProduct')->product_check($recovery_products);
            if($info['status'] == 0){
                $info['msg']='截金信息：'.$info['msg'];
                $info['rproduct_type']=2;
                return $info;
            }
        }
        $info['status'] = 1;
        $info['msg']='不存在相同信息';
        return $info;
    }

    /**
     * 添加金料明细
     * @param $recovery_products 金料数据
     * @param $order_id   金料来源的单号
     * @param $type       金料类型   类型 0回购 1销售截金 2采购金料 3结算来料 4以旧换新 5出库转料
     * @param $wh_id      金料存放仓库
     * @return mixed
     */
    public function add_product($recovery_products,$order_id,$type,$wh_id){
        $products = array();
        $result=true;
        $gold_price=D('BOptions')->get_current_gold_price();
        foreach($recovery_products as $k=>$v){
            $data = array();
            $data['order_id'] = $order_id;
            $data['company_id'] = get_company_id();
            $data['rproduct_code'] =empty($v['rproduct_code'])?$this->get_rproduct_code():trim($v['rproduct_code']);//trim($v['rproduct_code']); //$rproduct_code[$i];//trim($v['rproduct_code']);
            $data['sub_rproduct_code'] = trim($v['sub_rproduct_code']);
            $data['recovery_name'] = trim($v['recovery_name']);
            $data['recovery_price'] = $v['recovery_price'];
            $data['gold_price'] = $gold_price;//empty($v['gold_price'])?$v['recovery_price']:$v['gold_price'];
            $data['service_fee'] = empty($v['service_fee'])?0:$v['service_fee'];
            $data['total_weight'] = $v['total_weight'];
            $data['gold_weight'] = empty($v['gold_weight'])?$v['total_weight']:$v['gold_weight'];
            $data['purity'] = $v['purity'];
            $data['attrition'] = empty($v['attrition'])?0:$v['attrition'];
            $data['cost_price'] = $v['cost_price'];
            $data['product_id'] = $v['product_id'];
            $data['status'] = 1;
            $data['wh_id'] = empty($wh_id)?get_current_warehouse_id():$wh_id;
            $data['create_time'] = time();
            $data['material'] = $v['material'];
            $data['color'] = $v['color'];
            if($type){
                $data['type'] = $type;
            }
            $products[] = $data;
            $result = D("BRecoveryProduct")->insert($data);
            if($result==false) {
                return $result;
            }
        }
        /*$result=true;
        if(!empty($products)) {
            $result = D("BRecoveryProduct")->insertAll($products);
        }*/
        return $result;
    }
    //更新旧金货品明细
    public function update_product($recovery_products,$order_id,$type,$wh_id,$del_detail){
        $products = array();
        $result=true;
        $gold_price=D('BOptions')->get_current_gold_price();
        //编辑删除金料明细
        if(!empty($del_detail)){
            $result=D("BRecoveryProduct")->update(array('company_id'=>get_company_id(),'id'=>array('in',$del_detail)),array('deleted'=>1));
            if($result===false) {
                return $result;
            }
        };
        foreach($recovery_products as $k=>$v) {
            $data = array();
            $data['order_id'] = $order_id;
            $data['company_id'] = get_company_id();
            if(empty($v['recovery_product_id'])) {
                $data['rproduct_code'] =empty($v['rproduct_code'])?$this->get_rproduct_code():trim($v['rproduct_code']); //trim($v['rproduct_code']);
            }
            $data['sub_rproduct_code'] = trim($v['sub_rproduct_code']);
            $data['recovery_name'] = trim($v['recovery_name']);
            $data['recovery_price'] = $v['recovery_price'];
            $data['gold_price'] = $gold_price;//empty($v['gold_price'])?$v['recovery_price']:$v['gold_price'];
            $data['service_fee'] = empty($v['service_fee'])?0:$v['service_fee'];
            $data['total_weight'] = empty($v['total_weight'])?0:$v['total_weight'];
            $data['gold_weight'] = empty($v['gold_weight'])?$data['total_weight']:$v['gold_weight'];
            $data['purity'] = $v['purity'];
            $data['attrition'] = empty($v['attrition'])?0:$v['attrition'];
            $data['cost_price'] = $v['cost_price'];
            $data['product_id'] = $v['product_id'];
            $data['id'] = $v['recovery_product_id'];
            $data['material'] = $v['material'];
            $data['color'] = $v['color'];
            if($wh_id){
                $data['wh_id'] = $wh_id;
            }
            if($type){
                $data['type'] = $type;
            }
            $data['status'] = 1;
            $data['create_time'] = time();
            $products[] = $data;
            if(empty($v['recovery_product_id'])) {
                $result = D("BRecoveryProduct")->insert($data);
            }else{
                $result = D("BRecoveryProduct")->update(array('id'=>$v['recovery_product_id']),$data);
            }
            if($result===false) {
                return $result;
            }
        }
        return $result;
    }
    /**
     * 根据单号和类型更新金料状态
     * @param $order_id 单号
     * @param $type      类型
     * @param $new_status  金料新状态
     * @param int $old_status 金料旧状态
     * @return mixed
     */
    public function update_status($order_id,$type,$new_status,$old_status=1){
        $update_data = array('status' => $new_status);
        $where = array(
            'order_id' => $order_id,
            'company_id' => get_company_id(),
            'status' => $old_status,
            'type' => $type,//截金和以旧换新金料
            'deleted' => 0
        );
        $recovery_detail = D('BRecoveryProduct')->update($where, $update_data);
        return $recovery_detail;
    }
    //判断数据是否已经是在库状态
    public function check_product_status($allot_pids,$status=2,$warehouse_id='empty'){
        if(!empty(array_filter($allot_pids))){
            $i=1;
            foreach ($allot_pids as $k => $v) {
                $condition["id"] = $v;
                $condition["status"] = $status;
                $rproduct = M("BRecoveryProduct")->where($condition)->find();
                $warehouse_id=$warehouse_id=='empty'?$rproduct['wh_id']:$warehouse_id;
                if (empty($rproduct)||$warehouse_id!=$rproduct['wh_id']) {
                    $info["status"] = 0;
                    $info["msg"] = "第".$i."行为非在库的货品";
                    return $info;
                }
                $i++;
                $product_codes[] = $v;
            }
        }
        $info['status'] = 1;
        $info['msg'] .= '无重复!';
        $info['product_codes']=$product_codes;
        return $info;
    }
    //判断调拨数据是否重复
    public function check_product_repeat($postArray,$product_codes){
        foreach ($postArray as $key => $val) {
            $product_code = $product_codes;
            unset($product_code[$key]);
            $repeat_key = array_search($val, $product_code);
            $inarray = in_array($val, $product_code);
            if ($repeat_key != false || $inarray) {
                $info['status'] = '0';
                $info['msg'] .= '第' . ++ $key . '行数据货品编码与第' . ++ $repeat_key . '行数据货品编码重复!';
                return $info;
            }
        }
        $info['status'] = 1;
        $info['msg'] .= '无重复!';
        return $info;
    }
    /**
     *	返回金料单号的函数
     *	@param string $counts 需要的单号数量
     *	@param string $order_num 序位
     *	@return 格式：{COMPANY_CODE}_{yymmdd}{3位SHOP_ID}{3位数}
     */
    function get_rproduct_code($order_num=4)
    {
        $field='rproduct_code';
        $order_num='%0'.$order_num.'d';
        $order_id='';
        //$shop_id = sprintf($order_num, get_shop_id());
        $day = date('ymd', time());
        $order_id.=$day;
        $table = M('b_recovery_product');
        $condition = array('company_id'=>get_company_id());
        $condition[$field] = array('like', '%' . $order_id . '%');
        $info = $table->where($condition)->field('MAX('.$field.')as max_number')->find();
        if($info['max_number'] == ''){
            $count = $table->where($condition)->count();
        }else{
            $count = intval(substr($info['max_number'], -4));
        }
        $count = sprintf($order_num, $count + 1);
        return $order_id.$count;
    }
}   