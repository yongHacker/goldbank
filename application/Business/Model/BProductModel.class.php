<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BProductModel extends BCommonModel {

    // 通过采购id 更新 product 在库状态 status = 2, status = -1 表示 需要删除
    // public function update_status_with_procure_id($id = 0, $status = 1){
    public function update_status_with_storage_id($id = 0, $status = 1){
        // $this->startTrans();

        $rs_data = array('status'=> 1, 'msg'=> '');

        $where = 'deleted=0 AND status=1 AND storage_id ='.$id;
        if($status==-1){

            // $info = $this->getInfo($where, 'id, product_code');
            // $rm_product_code = '_rm_'.$info['id'].'_'.$info['product_code'];

            $update_data = array(
                'deleted'=> 1,
                'product_code'=> array('exp', 'CONCAT("_rm_",id, "_", product_code)')//$rm_product_code
            );
        }else{
            $update_data = array('status'=> $status);
        }
        $rs = $this->update($where, $update_data);

        if($rs === false){
            $rs_data['status'] = 0;
            $rs_data['msg'] = '操作错误！';

            // $this->rollback();
        }else{
            // $this->commit();
        }

        return $rs_data;
    }

    // 更换审核不通过标识 $unpass 1 采购入库 2 正常在库
    public function toggle_unpass_with_storage_id($id = 0, $unpass = 0){
        $info = array('status'=> 1, 'msg'=> '');

        $where = 'deleted=0 AND status=1 AND storage_id ='.$id;

        $update_data = array('unpass'=> $unpass);

        $rs = $this->update($where, $update_data);

        if($rs === false){
            $info['status'] = 0;
            $info['msg'] = '操作错误！';
        }

        return $info;
    }

    // 通过采购id删除 product
    public function delete_with_procure_id($procurement_id = 0){

        // $this->startTrans();

        $rs_data = array('status'=> 1, 'msg'=> '');

        $where = array('procurement_id'=> $procurement_id);

        $subSql = D('BProcureStorage')->where($where)->field('id')->select(false);

        $where = 'deleted=0 AND storage_id IN ('.$subSql.')';

        $update_data = array(
            'deleted'=> 1,
            'product_code'=> array('exp', 'CONCAT("_rm_", id, "_", product_code)')
        );

        $count = $this->countList($where);

        if($count > 0){
            $rs = $this->update($where, $update_data);

            if($rs === false){
                $rs_data['status'] = 0;
                $rs_data['msg'] = '删除错误！';

                // $this->rollback();
            }else{
                // $this->commit();
            }
        }

        return $rs_data;
    }

    // 获取入库表的产品列表
    public function get_list_by_storage_info($storage_info){
        $model_adjust = D('BAdjust');
        $p_tbl = C('DB_PREFIX').'b_product';
        $where = array(
            $p_tbl.'.storage_id' => $storage_info['id'], 
            $p_tbl.'.deleted' => 0
        );
        if ($storage_info['status'] == 2 || $storage_info['status'] == -2) {
            $where[$p_tbl . '.unpass'] = 1;
        } else {
            $where[$p_tbl . '.unpass'] = 0;
        }
        $field = $p_tbl . '.*, ' . $p_tbl . '.id pid, g.goods_code, g.goods_spec, gc.goods_name, gd.purity, gd.sell_feemode';
        
        $join = ' LEFT JOIN ' . C('DB_PREFIX') . 'b_goods as g ON (g.id = ' . $p_tbl . '.goods_id)';
        $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_goods_common as gc ON (gc.id = g.goods_common_id)';
        $join .=' LEFT JOIN ' . C('DB_PREFIX') . 'b_goldgoods_detail as gd ON (g.id = gd.goods_id)';
        
        $type = $storage_info['type'];
        
        switch ($type){
            case 1:
                $field .= " ,pg.*";
                $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_product_gold as pg ON (pg.product_id = ' . $p_tbl . '.id)';
                break;
            case 2:
                $field .= " ,pg.*";
                $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_product_goldm as pg ON (pg.product_id = ' . $p_tbl . '.id)';
                break;
            case 3:
                $field .= " ,pg.*";
                $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_product_diamond as pg ON (pg.product_id = ' . $p_tbl . '.id)';
                break;
            case 4:
                $field .= " ,pg.*";
                $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_product_inlay as pg ON (pg.product_id = ' . $p_tbl . '.id)';
                break;
            case 5:
                $field .= " ,pg.*";
                $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'b_product_jade as pg ON (pg.product_id = ' . $p_tbl . '.id)';
                break;
        }
        $order = 'pid ASC';

        $product_list = $this->getList($where, $field, NULL, $join, $order);
        $sub_info = D('BProductSub')->get_prodcut_sub(array('company_id' => get_company_id(), 'deleted' => 0, 'sub_type' => 1));
        foreach ($product_list as $key => $val){
            $product_list[$key]['certify_type_name'] = $sub_info[$val['certify_type']];
            $product_list[$key]['adjust_count'] = $model_adjust->_countProductAdjust($val['pid']);
        }
        
        return $product_list;
    }

    // 验证产品
	public function product_check($data = array(), $procurement_id = 0){
		$info = array('status'=> 1);

        $product_codes = array();
        foreach($data as $key => $val){
            $product_codes[] = $val['product_code'];
        }

        foreach($data as $key => $val){

            // 旧数据如果更改了 product_code 需要重新验证 jk.2018.03.21
            if($val['is_old'] > 0){
                $old_data = $this->where('id='.$val['is_old'])->field('product_code')->find();
                if($old_data['product_code'] == $val['product_code']){
                    continue;
                }
            }

            $product_code = $product_codes;
            unset($product_code[$key]);
            $repeat_key = array_search($val['product_code'], $product_code);
            $inarray = in_array($val['product_code'], $product_code);
            if(($repeat_key != false || $inarray) && $val['product_code'] != '' ){
                $info['status'] = '0';
                $info['msg'] .= '第'.($key+1).'行数据货品编码('.$val['product_code'].')与第'.($repeat_key+1).'行数据货品编码('.$val['product_code'].')重复!</br>';
            }
            /*change by alam 2018/06/02 为了实现打乱货品详情中 货品编码 顺序，或者删除后重新添加同样的 货品编码 start*/
            $where_count = array(
                'product_code'=> $val['product_code'],
                'deleted'=> 0,
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
                $info['msg'] .= '第'.($key+1).'行数据货品编码('.$val['product_code'].')与已有货品重复!</br>';
            }
        }
        return $info;
	}

    // 验证excel数据
    public function excel_check($data){
        $product_codes = array();
        
        foreach($data as $key=>$val){
            // $product_codes[] = $val['2'];
            if($val[1] == ''){
                continue;
            }

            $product_codes[] = $val[1];   // 1 为货品编码
        }

        $msg = "";

        foreach($data as $key => $val){

            $product_code = $product_codes;
            unset($product_code[$key]);

            $repeat_key = array_search($val[1], $product_code);
            $inarray = in_array($val[1], $product_code);
            if($val[1] !== '' && $repeat_key != false || $inarray){
                $msg .= '第'.($key+1).'行数据货品编码与第'.($repeat_key).'行数据货品编码重复!</br>';
            }
        }

        // 判断 company_id，否则会窜到其他商户商品信息
        foreach($data as $key => $val){

            // $join = 'LEFT JOIN '.C('DB_PREFIX').'b_goods_common as gc ON (gc.id = '.C('DB_PREFIX').'b_goods.goods_common_id)';
            // $is_exsit = D('BGoods')->goods_is_exsit("gc.company_id = ".get_company_id()." AND ".C('DB_PREFIX')."b_goods.goods_code='".$val[0]."'", $join);

            $is_exsit = D('BGoods')->goods_is_exsit("company_id = ".get_company_id()." AND ".C('DB_PREFIX')."b_goods.goods_code='".$val[0]."'");

            // die(M()->getLastSql());
            if(!$is_exsit){
                $msg .= "导入的excel文件中第".($key+2)."行"."货品编码为".$val[1]."，商品编码为".$val[0]."的商品不存在，请先添加此商品后重新导入！</br>";
            }

            // echo '<pre>';
            // print_r($val);die();

            if($val[1] != ''){

                $exist_info = $this->where('company_id = "'. get_company_id() .'" AND product_code="'.$val[1].'" AND deleted = 0')->find();

                // $is_exsit = $this->is_exsit('company_id = "'. get_company_id() .'" AND product_code="'.$val[1].'" AND deleted = 0');
                // die(M()->getLastSql());
                if(!empty($exist_info)){
                    $msg .= "导入的excel文件中第".($key+2)."行，货品编码为".$val[1]."的货品已存在，请修改后重新导入！</br>";
                }
            }
        }

        return $msg;
    }

    // 查看数据是否存在
    public function is_exsit($condition){

        $result = $this->where($condition)->find();

        return (!empty($result));
    }
    //批量货品判断货品状态
    public function check_product_status($postArray,$status=2){
        foreach ($postArray as $k => $v) {
            $productmap2["id"] = $v;
            $productmap2["status"] = $status;
            $allotproduct = M("BProduct")->where($productmap2)->count();
            if (empty($allotproduct)) {
                $info["status"] = 0;
                $info["msg"] = "货品已经不在该状态，不可使用";
                return $info;
            }
            $product_codes[] = $v;
        }
        $info['status'] = 1;
        $info['msg'] .= '全部可用！';
        $info['product_codes']=$product_codes;
        return $info;
    }

    /**
     * 获取单条货品明细的html  或数组
     * @param $v   货品信息（货品类型和货品详情信息）
     * @param string $html 拼接内容的字符串
     * @param $type  type=1 默认返回html字符串 type=2 返回数组
     * @return string
     */
    public function get_product_detail_html($v,$html="<br/>&nbsp;&nbsp;&nbsp;",$type=1){
        $arr = array();
        switch($v['type']){
            case 1:
                $arr = array(
                    "design"=>"款式",
                    "weight"=>"重量",
                    "ring_size"=>"尺寸",
                    "bracelet_size"=>"圈号",
                    "buy_price"=>"采购金价",
                    "buy_m_fee"=>"采购克工费",
                    // "pick_g_price"=>"配货金价",
                    // "pick_m_fee"=>"配货工费",
                    "sell_g_price"=>"销售金价",
                    "sell_m_fee"=>"销售克工费"
                );
               /* $goods_detail="款式：".$v["design"]."<br/>&nbsp;&nbsp;&nbsp;重量：".$v["weight"]."<br/>&nbsp;&nbsp;&nbsp;司马两：".$v["meterage_sml"]
                    ."<br/>&nbsp;&nbsp;&nbsp;盎司：".$v["meterage_ans"]."<br/>&nbsp;&nbsp;&nbsp;尺寸：".$v["ring_size"]."<br/>&nbsp;&nbsp;&nbsp;圈号：".$v["bracelet_size"]
                    ."<br/>&nbsp;&nbsp;&nbsp;采购金价：".$v["buy_price"]."<br/>&nbsp;&nbsp;&nbsp;采购克工费：".$v["buy_m_fee"]."<br/>&nbsp;&nbsp;&nbsp;配货金价：".$v["pick_g_price"]
                    ."<br/>&nbsp;&nbsp;&nbsp;配货工费：".$v["pick_m_fee"]."<br/>&nbsp;&nbsp;&nbsp;销售金价：".$v["sell_g_price"]."<br/>&nbsp;&nbsp;&nbsp;销售克工费：".$v["sell_m_fee"];*/
                break;
            case 2:
                $arr = array(
                    "goldm_weight"=>"重量",
                    "d_weight"=>"折纯重量",
                    // "goldm_meterage_sml"=>"司马两",
                    // "goldm_meterage_ans"=>"盎司",
                    "buy_price"=>"采购金价",
                    "sell_g_price"=>"销售金价", 
                    "goldm_type"=>"类型"
                );
               /* $goods_detail="重量：".$v["goldm_weight"]."<br/>&nbsp;&nbsp;&nbsp;折纯重量：".$v["d_weight"]."<br/>&nbsp;&nbsp;&nbsp;司马两：".$v["goldm_meterage_sml"]."
								<br/>&nbsp;&nbsp;&nbsp;盎司：".$v["goldm_meterage_ans"]."<br/>&nbsp;&nbsp;&nbsp;采购金价：".$v["goldm_buy_price"]."
								<br/>&nbsp;&nbsp;&nbsp;销售金价：".$v["goldm_sell_g_price"]."<br/>&nbsp;&nbsp;&nbsp;类型：".$v["goldm_type"];*/
                break;
            case 3:
                $arr = array(
                    "diamond_shape"=>"形状",
                    "caratage"=>"克拉数",
                    "color"=>"颜色",
                    "diamond_clarity"=>"净度",
                    "diamond_cut"=>"切工",
                    "fluorescent"=>"荧光", 
                    "polish"=>"抛光", 
                    "symmetric"=>"对称"
                );
                /*$goods_detail="形状：".$v["diamond_shape"]."<br/>&nbsp;&nbsp;&nbsp;克拉数：".$v["caratage"]."<br/>&nbsp;&nbsp;&nbsp;颜色：".$v["color"]."
								<br/>&nbsp;&nbsp;&nbsp;净度：".$v["diamond_clarity"]."<br/>&nbsp;&nbsp;&nbsp;切：".$v["diamond_cut"]."<br/>&nbsp;&nbsp;&nbsp;荧光：".$v["fluorescent"]."
								<br/>&nbsp;&nbsp;&nbsp;抛光：".$v["polish"]."<br/>&nbsp;&nbsp;&nbsp;对称：".$v["symmetric"];*/
                break;
            case 4:
                $arr = array(
                    "inlay_design"=>"款式",
                    "inlay_shape"=>"形状",
                    "inlay_ring_size"=>"尺寸",
                    "inlay_bracelet_size"=>"圈号",
                    "material"=>"材质",
                    "material_color"=>"材质颜色", 
                    "total_weight"=>"货品总量", 
                    "inlay_gold_weight"=>"金重",
                    "main_stone_num"=>"主石数量",
                    "main_stone_caratage"=>"主石克拉重", 
                    "main_stone_price"=>"主石总价", 
                    "inlay_color"=>"主石颜色",
                    "inlay_clarity"=>"主石净度",
                    "inlay_cut"=>"主石切工", 
                    "side_stone_num"=>"副石数量", 
                    "side_stone_caratage"=>"副石克拉重",
                    "side_stone_price"=>"副石总价",
                    "process_cost"=>"加工费", 
                    "certify_type"=>"主石证书类别", 
                    "certify_code"=>"主石证书编号"
                );
               /* $goods_detail="款式：".$v["inlay_design"]."<br/>&nbsp;&nbsp;&nbsp;形状：".$v["inlay_shape"]."<br/>&nbsp;&nbsp;&nbsp;尺寸：".$v["inlay_ring_size"]."<br/>&nbsp;&nbsp;&nbsp;圈号：".$v["inlay_bracelet_size"]."
								<br/>&nbsp;&nbsp;&nbsp;材质：".$v["material"]."<br/>&nbsp;&nbsp;&nbsp;材质颜色：".$v["material_color"]."<br/>&nbsp;&nbsp;&nbsp;货品总量：".$v["total_weight"]."<br/>&nbsp;&nbsp;&nbsp;金重：".$v["inlay_gold_weight"]."
								<br/>&nbsp;&nbsp;&nbsp;主石数量：".$v["main_stone_num"]."<br/>&nbsp;&nbsp;&nbsp;主石克拉重：".$v["main_stone_caratage"]."<br/>&nbsp;&nbsp;&nbsp;主石总价：".$v["main_stone_price"]."
								<br/>&nbsp;&nbsp;&nbsp;主石颜色：".$v["inlay_color"]."<br/>&nbsp;&nbsp;&nbsp;主石净度：".$v["inlay_clarity"]."<br/>&nbsp;&nbsp;&nbsp;主石切工：".$v["inlay_cut"]."
								<br/>&nbsp;&nbsp;&nbsp;副石数量：".$v["side_stone_num"]."<br/>&nbsp;&nbsp;&nbsp;副石克拉重：".$v["side_stone_caratage"]."<br/>&nbsp;&nbsp;&nbsp;副石总价：".$v["side_stone_price"]."
								<br/>&nbsp;&nbsp;&nbsp;加工费：".$v["process_cost"]."<br/>&nbsp;&nbsp;&nbsp;主石证书类别：".$v["certify_type"]."<br/>&nbsp;&nbsp;&nbsp;主石证书编号：".$v["certify_code"];*/
                break;
            case 5:
                $arr = array(
                    "jade_ring_size"=>"尺寸",
                    "jade_bracelet_size"=>"圈号",
                    "p_weight"=>"货重",
                    "stone_weight"=>"石重",
                    "stone_num"=>"石头数量",
                    "stone_price"=>"石头价格"
                );
               /* $goods_detail="尺寸：".$v["jade_ring_size"]."<br/>&nbsp;&nbsp;&nbsp;圈号：".$v["jade_bracelet_size"]."<br/>&nbsp;&nbsp;&nbsp;货重：".$v["p_weight"]."
								<br/>&nbsp;&nbsp;&nbsp;石重：".$v["stone_weight"]."<br/>&nbsp;&nbsp;&nbsp;石头数量：".$v["stone_num"]."<br/>&nbsp;&nbsp;&nbsp;石头价格：".$v["stone_price"];*/
                break;
            default:
                $goods_detail='';
        }
        $goods_detail=$type==2?array():"";
        foreach($arr as $key=>$value){
            if(!empty($v[$key])){
                if($type==1){
                    $goods_detail.=$value."：".$v[$key].$html;
                }elseif($type==2){
                    $goods_detail[$key]['name']=$value;
                    $goods_detail[$key]['value']=$v[$key];
                }

            }
        }
        return $goods_detail;
    }
    public function get_product_common_data($v){
        switch($v['type']){
            case 1:
                $goods_detail['p_gold_weight']=$v['weight'];
                $goods_detail['p_total_weight']=$v['weight'];
                break;
            case 2:
                $goods_detail['p_gold_weight']=$v["goldm_weight"];
                $goods_detail['p_total_weight']=$v['goldm_weight'];
                break;
            case 3:
                $goods_detail['p_gold_weight']="";
                $goods_detail['p_total_weight']="";
                break;
            case 4:
                $goods_detail['p_gold_weight']=$v["inlay_gold_weight"];
                $goods_detail['p_total_weight']=$v["total_weight"];
                break;
            case 5:
                $goods_detail['p_gold_weight']='';
                $goods_detail['p_total_weight']=$v["p_weight"];
                break;
            default:
                $goods_detail='';
        }
        return $goods_detail;
    }
    //获取所有货品明细的关联字符串
    public function get_product_join_str(){
        $join=" left join ".DB_PRE."b_goods bgoods on bproduct.goods_id=bgoods.id";
        $join.=" left join ".DB_PRE."b_goods_common g_common on g_common.id=bgoods.goods_common_id";
        $join.=" left join ".DB_PRE."b_goldgoods_detail g_detail on g_detail.goods_id=bgoods.id";
        $join.=" left join ".DB_PRE."b_product_diamond diamond on diamond.product_id=bproduct.id";
        $join.=" left join ".DB_PRE."b_product_gold gold on gold.product_id=bproduct.id";
        $join.=" left join ".DB_PRE."b_product_goldm goldm on goldm.product_id=bproduct.id";
        $join.=" left join ".DB_PRE."b_product_inlay inlay on inlay.product_id=bproduct.id";
        $join.=" left join ".DB_PRE."b_product_jade jade on jade.product_id=bproduct.id";
        return $join;
    }
    //所有货品明细的字段字符串
    /**
     * @param $type 1全部的货品详情字段  2去除采购信息（当前只用于库存） 3去除采购信息和销售信息(基本用于所有的货品列表)
     * @return string
     */
    public function get_product_field_str($type=1){
        switch($type){
            case 1:
                $field =',g_common.goods_name common_goods_name,bgoods.goods_name,bgoods.goods_spec,bgoods.goods_code,bgoods.sell_price g_sell_price,bgoods.photo_switch';
                $field.=',g_detail.weight gdetail_weight,g_detail.price_mode g_price_mode,g_detail.sell_feemode';
                $field.=',g_detail.sell_pricemode g_sell_pricemode,g_detail.buy_fee g_buy_fee,g_detail.sell_fee g_sell_fee,g_detail.purity';
                $field.=',diamond.shape diamond_shape,diamond.caratage,diamond.color,diamond.clarity,diamond.cut,diamond.fluorescent,diamond.polish,diamond.symmetric,';
                $field.='gold.design,gold.weight,gold.ring_size ring_size,gold.bracelet_size,gold.is_cut,gold.sell_weight,gold.cut_weight,';
                $field.='gold.buy_price,gold.buy_m_fee,gold.sell_g_price,gold.sell_m_fee,gold.sell_fee,';
                $field.='goldm.weight goldm_weight,goldm.d_weight,';
                $field.='goldm.buy_price goldm_buy_price,goldm.sell_g_price goldm_sell_g_price,goldm.type goldm_type,';
                $field.='inlay.design inlay_design,inlay.shape inlay_shape,inlay.ring_size inlay_ring_size,inlay.bracelet_size inlay_bracelet_size,inlay.material,inlay.material_color,inlay.total_weight,';
                $field.='inlay.gold_weight inlay_gold_weight,inlay.main_stone_num,inlay.main_stone_caratage,inlay.main_stone_price,inlay.color inlay_color,';
                $field.='inlay.clarity inlay_clarity,inlay.cut inlay_cut,inlay.side_stone_num,inlay.side_stone_caratage,inlay.side_stone_price,inlay.process_cost,inlay.certify_type,inlay.certify_code,';
                $field.='jade.ring_size jade_ring_size,jade.bracelet_size jade_bracelet_size,jade.p_weight,jade.stone_weight,jade.stone_num,jade.stone_price';
                break;
            case 2:
                $field =',g_common.goods_name common_goods_name,bgoods.goods_name,bgoods.goods_spec,bgoods.goods_code,bgoods.sell_price g_sell_price,bgoods.photo_switch';
                $field.=',g_detail.weight gdetail_weight,';
                $field.='g_detail.sell_pricemode g_sell_pricemode,g_detail.sell_fee g_sell_fee,g_detail.purity,g_detail.sell_feemode,';
                $field.='diamond.shape diamond_shape,diamond.caratage,diamond.color,diamond.clarity,diamond.cut,diamond.fluorescent,diamond.polish,diamond.symmetric,';
                $field.='gold.design,gold.weight,gold.ring_size ring_size,gold.bracelet_size,gold.is_cut,gold.sell_weight,gold.cut_weight,';
                $field.='gold.sell_g_price,gold.sell_m_fee,gold.sell_fee,';
                $field.='goldm.weight goldm_weight,goldm.d_weight,';
                $field.='goldm.sell_g_price goldm_sell_g_price,goldm.type goldm_type,';
                $field.='inlay.design inlay_design,inlay.shape inlay_shape,inlay.ring_size inlay_ring_size,inlay.bracelet_size inlay_bracelet_size,inlay.material,inlay.material_color,inlay.total_weight,';
                $field.='inlay.gold_weight inlay_gold_weight,inlay.main_stone_num,inlay.main_stone_caratage,inlay.main_stone_price,inlay.color inlay_color,';
                $field.='inlay.clarity inlay_clarity,inlay.cut inlay_cut,inlay.side_stone_num,inlay.side_stone_caratage,inlay.side_stone_price,inlay.process_cost,inlay.certify_type,inlay.certify_code,';
                $field.='jade.ring_size jade_ring_size,jade.bracelet_size jade_bracelet_size,jade.p_weight,jade.stone_weight,jade.stone_num,jade.stone_price';
                break;
            case 3:
                $field =',g_common.goods_name common_goods_name,bgoods.goods_name,bgoods.goods_spec,bgoods.goods_code,bgoods.sell_price g_sell_price,bgoods.photo_switch';
                $field.=',g_detail.weight gdetail_weight,g_detail.sell_feemode,';
                $field.='g_detail.sell_pricemode g_sell_pricemode,g_detail.sell_fee g_sell_fee,g_detail.purity,';
                $field.='diamond.shape diamond_shape,diamond.caratage,diamond.color,diamond.clarity,diamond.cut,diamond.fluorescent,diamond.polish,diamond.symmetric,';
                $field.='gold.design,gold.weight,gold.ring_size ring_size,gold.bracelet_size,gold.sell_fee,gold.is_cut,gold.sell_weight,gold.cut_weight,';
                $field.='goldm.weight goldm_weight,goldm.d_weight,';
                $field.='goldm.type goldm_type,';
                $field.='inlay.design inlay_design,inlay.shape inlay_shape,inlay.ring_size inlay_ring_size,inlay.bracelet_size inlay_bracelet_size,inlay.material,inlay.material_color,inlay.total_weight,';
                $field.='inlay.gold_weight inlay_gold_weight,inlay.main_stone_num,inlay.main_stone_caratage,inlay.main_stone_price,inlay.color inlay_color,';
                $field.='inlay.clarity inlay_clarity,inlay.cut inlay_cut,inlay.side_stone_num,inlay.side_stone_caratage,inlay.side_stone_price,inlay.process_cost,inlay.certify_type,inlay.certify_code,';
                $field.='jade.ring_size jade_ring_size,jade.bracelet_size jade_bracelet_size,jade.p_weight,jade.stone_weight,jade.stone_num,jade.stone_price';
                break;
        }
        return $field;
    }
    /**
     * @param int $photo_switch
     * @param $goods_id
     * @param $product_id
     * @return string
     */
    function get_goods_pic($photo_switch=0,$goods_id,$product_id){
        $pic="";
        if($photo_switch==1){

        }else{
            $condition=array("type"=>0,"goods_id"=>$goods_id);
            $goods_pic=D("BGoodsPic")->getInfo($condition,$field='*',$join='',$order='',$group='');
            $pic=$goods_pic['pic'];
        }
        return $pic;
    }
    public function get_product_list($condition,$field='bproduct.*',$join='',$limit=null){
        $join2=$this->get_product_join_str();
        $field2=$this->get_product_field_str(2);
        $pro= $this->alias("bproduct")->field($field .$field2.",gb_b_goods.goods_spec,gb_b_goods_pic.pic as product_pic,gb_b_goods.goods_name as product_name,gb_b_warehouse.wh_name,gc.goods_name as goodsname")->join($join . " join ".C('DB_PREFIX')."b_goods on gb_b_goods.id=bproduct.goods_id left join ".C('DB_PREFIX')."b_goods_common as gb_b_goods_common on gb_b_goods.goods_common_id=gb_b_goods_common.id left join ".C('DB_PREFIX')."b_goods_pic as gb_b_goods_pic on gb_b_goods_pic.goods_id=bproduct.goods_id and gb_b_goods_pic.type =0 join ".C('DB_PREFIX')."b_warehouse as gb_b_warehouse on gb_b_warehouse.id=bproduct.warehouse_id left join ".C('DB_PREFIX')."b_goods_common as gc on gb_b_goods.goods_common_id=gc.id")->join($join2)->where($condition)->order('bproduct.id asc')->group('bproduct.id')->limit($limit)->select();
        foreach ($pro as $k => $v){
            $pro[$k]['product_detail']=$this->get_product_detail_html($v,"&nbsp;&nbsp;");
        }
        return $pro;
    }
    public function get_product_list_count($condition,$field='bproduct.*',$join='',$limit=null){
        $join2=$this->get_product_join_str();
        //$field2=$this->get_product_field_str();
        $count= $this->alias("bproduct")->field($field .",gb_b_goods.goods_spec,gb_b_goods_pic.pic as product_pic,gb_b_goods.goods_name as product_name,gb_b_warehouse.wh_name,gc.goods_name as goodsname")->join($join . " join ".C('DB_PREFIX')."b_goods on gb_b_goods.id=bproduct.goods_id left join ".C('DB_PREFIX')."b_goods_common as gb_b_goods_common on gb_b_goods.goods_common_id=gb_b_goods_common.id left join ".C('DB_PREFIX')."b_goods_pic as gb_b_goods_pic on gb_b_goods_pic.goods_id=bproduct.goods_id and gb_b_goods_pic.type =0 join ".C('DB_PREFIX')."b_warehouse as gb_b_warehouse on gb_b_warehouse.id=bproduct.warehouse_id left join ".C('DB_PREFIX')."b_goods_common as gc on gb_b_goods.goods_common_id=gc.id")->join($join2)->where($condition)->order('bproduct.id asc')->count();

        return $count;
    }
    public function get_product_detail_html2($v){
        switch($v['type']){
            case 1:
                $goods_detail="款式：'".$v["design"]."'&nbsp;&nbsp; 重量：'".$v["weight"]."'&nbsp;&nbsp; 尺寸：'".$v["ring_size"]."'&nbsp;&nbsp; 圈号：'".$v["bracelet_size"]
                    ."'&nbsp;&nbsp; 采购金价：'".$v["buy_price"]."'&nbsp;&nbsp; 采购克工费：'".$v["buy_m_fee"]."'&nbsp;&nbsp; 配货金价：'".$v["pick_g_price"]
                    ."'&nbsp;&nbsp; 配货工费：'".$v["pick_m_fee"]."'&nbsp;&nbsp; 销售金价：'".$v["sell_g_price"]."'&nbsp;&nbsp; 销售克工费：'".$v["sell_m_fee"]."'";
                break;
            case 2:
                $goods_detail="重量：'".$v["weight goldm_weight"]."'&nbsp;&nbsp; 折纯重量：'"/*.$v["d_weight"]."'&nbsp;&nbsp; 司马两：'".$v["goldm_meterage_sml"]*/."'&nbsp;&nbsp;
								 盎司：'".$v["goldm_meterage_ans"]."'&nbsp;&nbsp; 采购金价：'".$v["goldm_buy_price"]."'&nbsp;&nbsp;
								 销售金价：'".$v["goldm_sell_g_price"]."'&nbsp;&nbsp; 类型：'".$v["goldm_type"]."'";
                break;
            case 3:
                $goods_detail="形状：'".$v["diamond_shape"]."'&nbsp;&nbsp; 克拉数：'".$v["caratage"]."'&nbsp;&nbsp; 颜色：'".$v["color"]."'&nbsp;&nbsp;
								 净度：'".$v["clarity"]."'&nbsp;&nbsp; 切：'".$v["cut"]."'&nbsp;&nbsp; 荧光：'".$v["fluorescent"]."'&nbsp;&nbsp;
								 抛光：'".$v["polish"]."'&nbsp;&nbsp; 对称：'".$v["symmetric"]."'";
                break;
            case 4:
                $goods_detail="款式：'".$v["inlay_design"]."'&nbsp;&nbsp; 形状：'".$v["inlay_shape"]."'&nbsp;&nbsp; 尺寸：'".$v["inlay_ring_size"]."'&nbsp;&nbsp; 圈号：'".$v["inlay_bracelet_size"]."'&nbsp;&nbsp;
								 材质：'".$v["material"]."'&nbsp;&nbsp; 材质颜色：'".$v["material_color"]."'&nbsp;&nbsp; 货品总量：'".$v["total_weight"]."'&nbsp;&nbsp; 金重：'".$v["gold_weight"]."'&nbsp;&nbsp;
								 主石数量：'".$v["main_stone_num"]."'&nbsp;&nbsp; 主石克拉重：'".$v["main_stone_caratage"]."'&nbsp;&nbsp; 主石总价：'".$v["main_stone_price"]."'&nbsp;&nbsp;
								 主石颜色：'".$v["inlay_color"]."'&nbsp;&nbsp; 主石净度：'".$v["inlay_clarity"]."'&nbsp;&nbsp; 主石切工：'".$v["cut inlay_cut"]."'&nbsp;&nbsp;
								 副石数量：'".$v["side_stone_num"]."'&nbsp;&nbsp; 副石克拉重：'".$v["side_stone_caratage"]."'&nbsp;&nbsp; 副石总价：'".$v["side_stone_price"]."'&nbsp;&nbsp;
								 加工费：'".$v["process_cost"]."'&nbsp;&nbsp; 主石证书类别：'".$v["certify_type"]."'&nbsp;&nbsp; 主石证书编号：'".$v["certify_code"]."'";
                break;
            case 5:
                $goods_detail="尺寸：'".$v["jade_ring_size"]."'&nbsp;&nbsp; 圈号：'".$v["jade_bracelet_size"]."''&nbsp;&nbsp; 货重：'".$v["p_weight"]."'
								&nbsp;&nbsp; 石重：'".$v["stone_weight"]."'&nbsp;&nbsp; 石头数量：'".$v["stone_num"]."'&nbsp;&nbsp; 石头价格：'".$v["stone_price"]."'";
                break;
            default:
                $goods_detail='';
        }
        return $goods_detail;
    }
    // 导出 excel
    /**
     * @param $excel_where
     * @param string $excel_field
     * @param string $excel_join
     * @param int $page
     * @param string $action
     */
    public function excel_out($excel_where,$min_id=0){
        static $row;
        if ($min_id==0) {
             $row = 0;
            $title=array('序','仓库','类型','商品分类','货品编号','商品名称','规格','克重','质检编号','入库时间','销售指导价','销售时间','货品状态');
        } else {
            $excel_where['bproduct.id'] = array('lt', $min_id);
        }
        $limit = '0,1000';
        $join="left join ".DB_PRE."b_warehouse bwarehouse on bwarehouse.id=bproduct.warehouse_id";
        $join.=$this->get_product_join_str();
        $join.=" left join ".DB_PRE."b_goods_class b_goods_class on b_goods_class.id=g_common.class_id";
        $join.=" left join ".DB_PRE."b_goods_class b_goods_class2 on b_goods_class2.id=b_goods_class.pid";
        $field='bproduct.*,bwarehouse.wh_name,g_common.class_id,b_goods_class.pid class_pid';
        $field.=',if(b_goods_class2.class_name,b_goods_class.class_name,concat(b_goods_class.class_name,"/",b_goods_class2.class_name)) class_name';
        $field.=$this->get_product_field_str();
        //$field='bproduct.*,bwarehouse.wh_name,g_common.class_id,b_goods_class.pid class_pid,b_goods_class.class_name,g_common.goods_name common_goods_name';
        //$field.='bgoods.goods_spec';
        //查询数据
        $data=$this->alias("bproduct")->export($excel_where,$field,$limit,$join,$order='bproduct.id desc');
        if($data['data']){
            //过滤需要导出的数据，并按类型转换
            $filter_data=array('wh_name'=>'','type'=>'a_status_comment=a_goods_class,type','class_name'=>'','product_code'=>'','common_goods_name'=>'','goods_spec'=>''
            ,'weight'=>'','qc_code'=>'','buy_time'=>'date=Y-m-d H:i:s,###','sell_price'=>''
            ,'sell_time'=>'date=Y-m-d H:i:s,###','status'=>'status_comment=b_product,status');
            $export_data = $this->alias("bproduct")->export_data_filter($data,$filter_data,$title,$row);
            //递归导出数据
            $export_data['filename']='product_list';
            $this->alias("bproduct")->export_excel($export_data,array(&$this, 'excel_out'),$excel_where,$data['end_id']);
        }
    }
    //调拨通过货品编号查询货品
    function get_product_by_excel($product_codes,$wh_id=0){
        $join2=D("BProduct")->get_product_join_str();
        $field2=D("BProduct")->get_product_field_str(3);
        $where=array("bproduct.status"=>2,"bproduct.warehouse_id"=>$wh_id);
        $where["product_code"]=array("in",$product_codes);
        $product_list=M('b_product as bproduct')->field('bproduct.*'.$field2)
            ->join($join2)
            ->where(array('bproduct.deleted'=>0,'bproduct.status'=>2,'warehouse_id'=>$wh_id,'bproduct.product_code'=>array('in',$product_codes)))->select();
        return $product_list;
    }

    /**
     * @param $v
     * @param int $type 1 基本信息  2采购信息
     * @param $is_show_null 是否显示空的数据
     * @return array|string
     */
    public function get_product_info($v,$type=1,$is_show_null=1){
        $common_base=array('product_code'=>'货品编码','p_sub_product_code'=>'附属货品编码','common_goods_name'=>'货品名称','goods_class_name'=>'货品分类',
            'belong_type'=>'所属套系','goods_code'=>'商品编码','goods_spec'=>'规格','wh_name'=>'仓库',
            'p_certify_type_name'=>'证书类型','p_certify_code'=>'证书号','qc_code'=>'质检编号');
        $common_procure=array();
        $procure_info=array();
        switch($v['type']){
            case 1:
                $base_info = array(
                    "design"=>"款号",
                    "metal_type_name"=>"贵金属",
                    "bank_gold_type_name"=>"金价类型",
                    "purity"=>"含金量",
                    "ring_size"=>"尺寸",
                    "weight"=>"金重",
                );
                break;
            case 2:
                $base_info = array(
                    "goldm_weight"=>"重量",
                    "d_weight"=>"折纯重量",
                    "buy_price"=>"采购金价",
                    "sell_g_price"=>"销售金价",
                    "goldm_type"=>"类型"
                );
                break;
            case 3:
                $base_info = array(
                    "diamond_shape"=>"形状",
                    "caratage"=>"石重",
                    "color"=>"颜色",
                    "diamond_clarity"=>"净度",
                    "diamond_cut"=>"切工",
                    "polish"=>"抛光",
                    "symmetric"=>"对称",
                    "fluorescent"=>"荧光",
                );

                break;
            case 4:
                $base_info = array(
                    "inlay_design"=>"款式",
                    "material"=>"材质",
                    "material_color"=>"材质颜色",
                    //"inlay_shape"=>"形状",
                    "inlay_ring_size"=>"尺寸",
                    //"inlay_bracelet_size"=>"圈号",
                    "total_weight"=>"货重",
                    "inlay_gold_weight"=>"金重",
                    "main_stone_num"=>"主石数",
                    "main_stone_caratage"=>"主石重",
                    //"main_stone_price"=>"主石总价",
                    "inlay_color"=>"主石颜色",
                    "inlay_clarity"=>"主石净度",
                    "inlay_cut"=>"主石切工",
                    "side_stone_num"=>"副石数",
                    "side_stone_caratage"=>"副石重",
                    /*"side_stone_price"=>"副石总价",
                    "process_cost"=>"加工费",
                    "certify_type"=>"主石证书类别",
                    "certify_code"=>"主石证书编号"*/
                );

                break;
            case 5:
                $base_info = array(
                    "jade_ring_size"=>"尺寸",
                    //"jade_bracelet_size"=>"圈号",
                    "p_weight"=>"货重",
                    "stone_num"=>"主石数",
                    "stone_weight"=>"石重",
                    "stone_num"=>"主石重",
                    //"stone_price"=>"石头价格"
                );
                break;
            default:
                $base_info=array();
                $procure_info=array();
        }
        // 分类树
        $class_nav = D('BGoodsClass')->classNav($v["class_id"]);
        // 商品大类列表
        $class_status = D('AGoodsClass')->_get_class_status();
        $v['goods_class_name']=$class_nav . ' (' . $class_status[$v['type']] . '类)';
        $goods_detail=array();
        $info=$type==1?array_merge($common_base,$base_info):array_merge($common_procure,$procure_info);
        foreach($info as $key=>$value){
            if(!empty($v[$key])||$is_show_null==1){
                $goods_detail[$key]['name']=$value;
                $goods_detail[$key]['value']=$v[$key];
            }
        }
        return $goods_detail;
    }
}
