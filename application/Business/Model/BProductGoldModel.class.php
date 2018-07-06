<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BProductGoldModel extends BCommonModel {

    // 通过采购id 更新 product 在库状态 status = 2, status = -1 表示 需要删除
    public function update_status_with_procure_id($id = 0, $status = 1){
        $this->startTrans();
        $info = array('status'=> 1, 'msg'=> '');

        $where = 'deleted=0 AND status=1 AND storage_id = '.$id;
        if($status==-1){
            $update_data = array('deleted'=> 1);
        }else{
            $update_data = array('status'=> $status);
        }
        $rs = $this->update($where, $update_data);

        if($rs === false){
            $info['status'] = 0;
            $info['msg'] = '操作错误！';

            $this->rollback();
        }else{
            $this->commit();
        }

        return $info;
    }

    // 通过采购id删除 product
    public function delete_with_procure_id($procurement_id = 0){

        $this->startTrans();

        $info = array('status'=> 1, 'msg'=> '');

        $where = array('procurement_id'=> $procurement_id);

        $subSql = D('BProcureStorage')->where($where)->field('id')->select(false);

        $where = 'deleted=0 AND storage_id IN ('.$subSql.')';

        $update_data = array('deleted'=> 1);

        $count = $this->countList($where);

        if($count > 0){
            $rs = $this->update($where, $update_data);

            if($rs === false){
                $info['status'] = 0;
                $info['msg'] = '删除错误！';

                $this->rollback();
            }else{
                $this->commit();
            }
        }

        return $info;
    }

    // 获取入库表的产品列表
    public function get_list_by_storage_info($storage_info){

        $p_tbl = C('DB_PREFIX').'b_product_gold';
        $where = array(
            $p_tbl.'.storage_id'=> $storage_info['id'], 
            $p_tbl.'.deleted'=> 0
        );

        $field = $p_tbl.'.*, g.goods_code, g.goods_spec, gc.goods_name, g.purity';
        $join = ' LEFT JOIN '.C('DB_PREFIX').'b_goods as g ON (g.id = '.$p_tbl.'.goods_id)';
        $join .= ' LEFT JOIN '.C('DB_PREFIX').'b_goods_common as gc ON (gc.id = g.goods_common_id)';
        $order = 'id ASC';

        $product_list = $this->getList($where, $field, NULL, $join, $order);

        return $product_list;
    }

    // 验证产品
	public function product_check($data = array()){
		$info = array('status'=> 1);

        $product_codes = array();
        foreach($data as $key => $val){
            $product_codes[] = $val['product_code'];
        }

        foreach($data as $key => $val){

            if($val['is_old'] > 0){
                continue;
            }

            $product_code = $product_codes;
            unset($product_code[$key]);
            $repeat_key = array_search($val['product_code'], $product_code);
            // print_r($repeat_key."   ".$key.",,");
            $inarray = in_array($val['product_code'], $product_code);
            if($repeat_key != false || $inarray){
                $info['status'] = '0';
                $info['msg'] .= '第'.($key+1).'行数据货品编码('.$val['product_code'].')与第'.($repeat_key+1).'行数据货品编码('.$val['product_code'].')重复!</br>';
            }
            $where_count = array(
            	'product_code'=> $val['product_code'],
            	'deleted'=> 0
            );

            $where_count['company_id'] = get_company_id();

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
            $product_codes[] = $val['2'];
        }

        $msg = "";

        foreach($data as $key => $val){
            $product_code = $product_codes;
            unset($product_code[$key]);

            $repeat_key = array_search($val[2], $product_code);
            $inarray = in_array($val[2], $product_code);
            if($repeat_key != false || $inarray){
                $msg .= '第'.($key+1).'行数据货品编码与第'.($repeat_key).'行数据货品编码重复!</br>';
            }
        }

        foreach($data as $key => $val){
            $is_exsit = D('BGoods')->goods_is_exsit("goods_code='".$val[0]."'");
            if(!$is_exsit){
                $msg .= "导入的excel文件中第".($key+2)."行"."货品名称为".$val[1]."，商品编码为".$val[0]."的商品不存在，请先添加此商品后重新导入！</br>";
            }
            $is_exsit = $this->is_exsit('product_code="'.$val[2].'" AND deleted = 0');
            if($is_exsit){
                $msg .= "导入的excel文件中第".($key+2)."行"."货品名称为".$val[1]."，货品编码为".$val[2]."的货品已存在，请修改后重新导入！</br>";
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
            $allotproduct =$this->where($productmap2)->count();
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
    function insert($insert) {
        $insert["company_id"]=get_company_id();
        return parent::insert($insert); // TODO: Change the autogenerated stub
    }
}
