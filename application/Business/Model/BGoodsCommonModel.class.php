<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BGoodsCommonModel extends BCommonModel{

// 根据数据添加商品全部信息
    /**
     * @param $goods_common_id
     * @param $goods_imgs
     * @param $goods_default_img
     */
    public function add_common_img($goods_common_id,$goods_imgs,$goods_default_img){
        if ($goods_common_id) {
            $pic_insert=array();
            foreach ($goods_imgs as $key => $item) {
                $is_hot = 0;
                if ($goods_default_img == $key) $is_hot = 1;
                $pic_insert[] = array(
                    'goods_id' => $goods_common_id,
                    'pic' => $item,
                    'is_hot' => $is_hot,
                    'type' => 1,
                );
            }
            $res = D('BGoodsPic')->insertAll($pic_insert);
            if($res!==false){
                $flag = true;
            }else{
                $flag = false;
            }
        }else {
            $flag = false;
        }
        return $flag;
    }
    //根据商品公共添加商品规格
    /**
     * @param $goods_common_id
     * @param $goods_list
     * @param $goods_common_info
     * @return bool
     */
    public function add_goods_list($goods_common_id,$goods_list,$goods_common_info){
        if ($goods_common_id) {
            $flag = true;
            $info='保存成功';
            foreach ($goods_list as $key => $val) {
                $insert = array(
                    'goods_code' => trim($val ['goods_code']),
                    'goods_common_id' => $goods_common_id,
                    'goods_spec' => $val ['goods_spec'],
                    'goods_name' => $goods_common_info['goods_common_name'] . " " . $val ['goods_spec'],
                    'price_mode' => $val ['price_mode'],
                    // 'pick_pricemode' => $val ['pick_pricemode'],
                    'sell_pricemode' => $val ['sell_pricemode'],
                    /*'weight' => $val ['weight'],
                    'buy_fee' => $val ['buy_fee'],
                    'sell_fee' => $val ['sell_fee'],
                    'pick_fee' => $val ['pick_fee'],*/
                    'procure_price' => $val['procure_price'],
                    'market_price' => $val ['market_price'],
                    // 'pick_price' => $val ['pick_price'],
                    'sell_price' => $val ['sell_price'],
                   /* 'gold_type' => $val ['gold_type'],
                    'bank_gold_type' => $val['bank_gold_type'],*/
                    'photo_switch' => $val['photo_switch'],
                    'status' => $val ['status'],
                   /* 'bean' => $val['bean'],
                    'agent1_bean' => $val['agent1_bean'],
                    'agent2_bean' => $val['agent2_bean'],
                    'purity' => $val['purity'],
                    'memo' => $val['memo'],*/
                    'create_time' => time(),
                    'update_time' => time()
                );
                $goods_detail = array(
                   'weight' => $val ['weight'],
                    'price_mode' => $val ['price_mode'],
                    // 'pick_pricemode' => $val ['pick_pricemode'],
                    'sell_pricemode' => $val ['sell_pricemode'],
                    'buy_fee' => $val ['buy_fee'],
                    'sell_fee' => $val ['sell_fee'],
                    'sell_feemode' => $val ['sell_feemode'],
                    // 'pick_fee' => $val ['pick_fee'],
                    'gold_type' => $val ['gold_type'],
                    'bank_gold_type' => $val['bank_gold_type'],
                   /*  'bean' => $val['bean'],
                     'agent1_bean' => $val['agent1_bean'],
                     'agent2_bean' => $val['agent2_bean'],*/
                     'purity' => $val['purity'],
                     'memo' => $val['memo']
                );
                $insert["company_id"]=get_company_id();
                //判断商户下是否存在未删除的该编码的规格
                $is_exsit=D("BGoods")->goods_is_exsit(array('company_id'=>get_company_id(),'goods_code'=>$val ['goods_code'],'deleted'=>0));
                if(!$is_exsit){
                    $goods_id = D("BGoods")->insert($insert);
                }else{
                    $goods_id=false;
                }
                //$goods_id = D("BGoods")->insert($insert);
                $pic_insert = array(
                    'goods_id' => $goods_id,
                    'pic' => $val ['goods_pic'],
                    'is_hot' => $val ['is_hot']
                );
                if (!$goods_id) {
                    $flag = false;
                    $info='规格保存失败，可能商品编码已经存在';
                } else {
                    if($goods_common_info['type']==1){
                        $goods_detail["goods_id"]=$goods_id;
                        $goods_detail_id=D("b_goldgoods_detail")->insert($goods_detail);
                        if(!$goods_detail_id){
                            $flag = false;
                            $info='素金规格明细保存失败在';
                        }else{
                            if (!empty ($val ['goods_pic'])) {
                                $res = D('b_goods_pic')->insert($pic_insert);
                            }
                        }
                    }else{
                        if (!empty ($val ['goods_pic'])) {
                            $res = D('b_goods_pic')->insert($pic_insert);
                        }
                    }
                }
            }
        }else {
            $flag = false;
            $info='保存失败';
        }
        $result=array('status'=>$flag,'info'=>$info);
        return $result;
    }
    //根据批发商品公共添加批发商品规格
    /**
     * @param $goods_common_id
     * @param $goods_list
     * @param $goods_common_info
     * @return bool
     */
    public function add_wgoods_list($goods_common_id,$goods_list,$goods_common_info){
        if ($goods_common_id) {
            $flag = true;
            foreach ($goods_list as $key => $val) {
                $insert = array(
                    'goods_code' => $val ['goods_code'],
                    'goods_common_id' => $goods_common_id,
                    'goods_spec' => $val ['goods_spec'],
                    'goods_name' => $goods_common_info['goods_common_name'] . " " . $val ['goods_spec'],
                    'price_mode' => $val ['price_mode'],
                    'sell_pricemode' => $val ['sell_pricemode'],
                    'procure_price' => $val['procure_price'],
                    'market_price' => $val ['market_price'],
                    // 'pick_price' => $val ['pick_price'],
                    'goods_unit' => $val ['goods_unit'],
                    'status' => $val ['status'],
                    'create_time' => time(),
                    'update_time' => time()
                );
                $goods_detail = array(
                    'gold_type' => $val ['gold_type'],
                    'sale_fee' => $val ['sell_fee'],
                    'purity' => $val['purity'],
                    'memo' => $val['memo']
                );
                $insert["company_id"]=get_company_id();
                $goods_id = D("BWgoods")->insert($insert);
                $pic_insert = array(
                    'goods_id' => $goods_id,
                    'pic' => $val ['goods_pic'],
                    'is_hot' => $val ['is_hot'],
                    'type' => 2,
                );
                if (!$goods_id) {
                    $flag = false;
                } else {
                    $stock_id = D("BWgoodsStock")->insert(array("goods_id"=>$goods_id,"warehouse_id"=>get_current_warehouse_id()));
                    if (!$stock_id) {
                        $flag = false;
                    }
                    if($goods_common_info['type']==1){
                        $goods_detail["goods_id"]=$goods_id;
                        $goods_detail_id=D("b_goldgoods_wholesale")->insert($goods_detail);
                        if(!$goods_detail_id){
                            $flag = false;
                        }else{
                            if (!empty ($val ['goods_pic'])) {
                                $res = D('b_goods_pic')->insert($pic_insert);
                            }
                        }
                    }else{
                        if (!empty ($val ['goods_pic'])) {
                            $res = D('b_goods_pic')->insert($pic_insert);
                        }
                    }
                }
            }
        }else {
            $flag = false;
        }
        return $flag;
    }
//商品公共图片处理
    function common_goods_img($data){
        $m = M('b_goods_pic');
        if($data['type'] == 'del'){
            $m->id = $data['id'];
            $m->deleted = 1;
            $res = $m->save();
            $filename = $m->where('id='.$data['id'])->field('pic')->find();
            b_del_pic($filename['pic']);
            $res = $m->where('id='.$data['id'])->delete();
        }
        if($data['type'] == 'link'){
            $m->type = 1;
            $m->goods_id = $data['id'];
            $m->pic = $data['pic'];
            $res = $m->add();
        }
        if($data['type'] == 'default'){
            $goods_id = $m->where("id='".$data['id']."'")->getField('goods_id');
            $m->where("goods_id=".$goods_id)->save(array('is_hot'=>0));
            $m->id = $data['id'];
            $m->is_hot = 1;
            $res = $m->save();
        }
        return $res;
    }
    //获取商品编辑的所有信息
    function get_edit_info($getdata){
        $bbankgoldtype_model=D("BBankGoldType");
        $bgoodscommon_model=D("BGoodsCommon");
        $bgoods_model=D("b_goods");
        $bgoodsclass_model=D("b_goods_class");
        $bgoodspic_model=D("b_goods_pic");
        $data=array();
        //获取b端金属类型
        $condition=array("deleted"=>0,"company_id"=>get_company_id());
        $tree=D("BMetalType")->getList($condition,$field='*',$limit="",$join='');
        $data["cate_list"]= $tree;
        //获取金价类型
        $condition=array("company_id"=>get_company_id(),"deleted"=>0,'status'=>1);
        $bank_gold_type_list=$bbankgoldtype_model->getList($condition,$field='*',$limit="",$join='');
        $data["bank_gold_type_list"]= $bank_gold_type_list;
        //获取商品信息
        $condition=array("deleted"=>0,"id"=>$getdata["goods_common_id"]);
        $goods_detail=$bgoodscommon_model->getInfo($condition,$field='*',$join="");
        $goods_detail ['description'] = htmlspecialchars_decode ( $goods_detail ['description'] );
        //获取商品所有规格信息
        $condition=array("deleted"=>0,"goods_common_id"=>$goods_detail["id"]);
        $join="left join ".DB_PRE."b_goldgoods_detail detail on bgoods.id=detail.goods_id";
        // $field="bgoods.*,detail.weight,detail.price_mode d_price_mode,detail.pick_pricemode d_pick_pricemode,detail.sell_pricemode d_sell_pricemode,";
        $field="bgoods.*,detail.weight,detail.price_mode d_price_mode,detail.sell_pricemode d_sell_pricemode,detail.sell_feemode,";
        // $field.="detail.buy_fee,detail.pick_fee,detail.sell_fee,detail.gold_type,detail.bank_gold_type,detail.purity,detail.memo";
        $field.="detail.buy_fee,detail.sell_fee,detail.gold_type,detail.bank_gold_type,detail.purity,detail.memo";
        $goods_detail["goods_list"]=$bgoods_model->alias("bgoods")->getList($condition,$field,$limit="",$join,$order='id desc');
        foreach($goods_detail["goods_list"] as $k=>$v){
            $condition=array("type"=>0,"deleted"=>0,"goods_id"=>$v["id"]);
            $pic=$bgoodspic_model->getInfo($condition,$field="pic",$join="");
            $goods_detail["goods_list"][$k]["pic"]=$pic["pic"];
            $goods_detail["goods_list"][$k]["has_product"]=D('BProduct')->countList(array('goods_id'=>$v["id"]));
        }
        $data["goods_detail"]= $goods_detail;
        //获取B端商品分类
        $select_categorys=$bgoodsclass_model->get_b_goodsclass($goods_detail['class_id']);
        $data["select_categorys"]= $select_categorys;
        //获取商品图片
        $condition=array("deleted"=>0,"type"=>1,"goods_id"=>$goods_detail["id"]);
        $goods_img =$bgoodspic_model->getList($condition,$field="id,pic,is_hot",$limit="",$join='',$order='id desc');
        $data["goods_img"]= $goods_img;
        return $data;
    }

    //获取批发商品编辑的所有信息
    function get_wgoods_edit_info($getdata){
        $bbankgoldtype_model=D("BBankGoldType");
        $bgoodscommon_model=D("BGoodsCommon");
        $bgoods_model=D("b_wgoods");
        $bgoodsclass_model=D("b_goods_class");
        $bgoodspic_model=D("b_goods_pic");
        $data=array();
        //获取b端金属类型
        $condition=array("deleted"=>0,"company_id"=>get_company_id());
        $tree=D("BMetalType")->getList($condition,$field='*',$limit="",$join='');
        $data["cate_list"]= $tree;
        //获取金价类型
        $condition=array("company_id"=>get_company_id(),"deleted"=>0,'status'=>1);
        $bank_gold_type_list=$bbankgoldtype_model->getList($condition,$field='*',$limit="",$join='');
        $data["bank_gold_type_list"]= $bank_gold_type_list;
        //获取商品信息
        $condition=array("deleted"=>0,"id"=>$getdata["goods_common_id"]);
        $goods_detail=$bgoodscommon_model->getInfo($condition,$field='*',$join="");
        $goods_detail ['description'] = htmlspecialchars_decode ( $goods_detail ['description'] );
        //获取商品所有规格信息
        $condition=array("deleted"=>0,"goods_common_id"=>$goods_detail["id"]);
        $join="left join ".DB_PRE."b_goldgoods_wholesale detail on bgoods.id=detail.goods_id";
        $field="bgoods.*";
        $field.=",detail.sale_fee sell_fee,detail.gold_type,detail.purity,detail.memo";
        $goods_detail["goods_list"]=$bgoods_model->alias("bgoods")->getList($condition,$field,$limit="",$join,$order='id desc');
        foreach($goods_detail["goods_list"] as $k=>$v){
            $condition=array("type"=>2,"deleted"=>0,"goods_id"=>$v["id"]);
            $pic=$bgoodspic_model->getInfo($condition,$field="pic",$join="");
            $goods_detail["goods_list"][$k]["pic"]=$pic["pic"];
        }
        $data["goods_detail"]= $goods_detail;
        //获取B端商品分类
        $select_categorys=$bgoodsclass_model->get_b_goodsclass($goods_detail['class_id']);
        $data["select_categorys"]= $select_categorys;
        //获取商品图片
        $condition=array("deleted"=>0,"type"=>1,"goods_id"=>$goods_detail["id"]);
        $goods_img =$bgoodspic_model->getList($condition,$field="id,pic,is_hot",$limit="",$join='',$order='id desc');
        $data["goods_img"]= $goods_img;
        return $data;
    }

    // 修改商品公共信息及商品信息
    public function editGoodsCommon($data)
    {
        $flag = true;
        $info='保存成功';
        $condition = array(
            'id' => $data ['goods_common_id']
        );
        $update = array(
            'class_id' => $data ['class_id'],
            'goods_code'=>$data['goods_common_code'],
            'goods_name' => $data ['goods_common_name'],
            'tag_name' => $data ['tag_name'],
            'moral' => $data ['moral'],
            //'is_standard' => $data ['is_standard'],
           // 'mobile_show' => $data ['mobile_show'],
            'description' => $data ['description'],
            'sell_type' => $data ['sell_type'],
            'update_time' => time()
        );
        $result = $this->update($condition, $update);
        //删除规格
        if($data['del_goods_id']){
            D("BGoods")->update(array('id'=>array('in',$data['del_goods_id'])), array('deleted'=>1));
        }
        if ($result!==false) {
            $goods_list = $data ['goods_list'];
            foreach ($goods_list as $key => $val) {
                if ($flag) {
                    $type = $val ['type'];
                    switch ($type) {
                        case 'add' :
                            $insert = array(
                                'goods_code' => trim($val ['goods_code']),
                                'goods_common_id' => $data ['goods_common_id'],
                                'price_mode' => $val ['price_mode'],
                                // 'pick_pricemode' => $val ['pick_pricemode'],
                                'sell_pricemode' => $val ['sell_pricemode'],
                                'market_price' => $val ['market_price'],
                                'sell_price' => $val ['sell_price'],
                                // 'pick_price' => $val ['pick_price'],
                                'procure_price'=>$val['procure_price'],
                                'status' => $val ['status'],
                                'goods_spec' => $val ['goods_spec'],
                                'goods_name' => $data ['goods_common_name'] . " " . $val ['goods_spec'],
                                'create_time' => time(),
                            );
                            $goods_detail = array(
                                'weight' => $val ['weight'],
                                'price_mode' => $val ['price_mode'],
                                // 'pick_pricemode' => $val ['pick_pricemode'],
                                'sell_pricemode' => $val ['sell_pricemode'],
                                'sell_feemode' => $val ['sell_feemode'],
                                'buy_fee' => $val ['buy_fee'],
                                'sell_fee' => $val ['sell_fee'],
                                // 'pick_fee' => $val ['pick_fee'],
                                'gold_type' => $val ['gold_type'],
                                'bank_gold_type' => $val['bank_gold_type'],
                                'purity' => $val['purity'],
                                'memo' => $val['memo']
                            );
                            $insert["company_id"]=get_company_id();
                            //判断商户下是否存在未删除的该编码的规格
                            $is_exsit=D("BGoods")->goods_is_exsit(array('company_id'=>get_company_id(),'goods_code'=>$val ['goods_code'],'deleted'=>0));
                            if(!$is_exsit){
                                $goods_id = D("BGoods")->insert($insert);
                            }else{
                                $goods_id=false;
                            }
                            $pic_insert = array(
                                'goods_id' => $goods_id,
                                'pic' => $val ['goods_pic'],
                                'is_hot' => $val ['is_hot']
                            );
                            if (!$goods_id) {
                                $flag = false;
                                $info='规格保存失败，可能商品编码已经存在';
                            } else {
                                /*$stock_id = D("BWgoodsStock")->insert(array("goods_id"=>$goods_id,"warehouse_id"=>get_current_warehouse_id()));
                                if (!$stock_id) {
                                    $flag = false;
                                }*/
                                if($data['type']==1) {
                                    $goods_detail["goods_id"] = $goods_id;
                                    $goods_detail_id = D("b_goldgoods_detail")->insert($goods_detail);
                                    if (!$goods_detail_id) {
                                        $flag = false;
                                        $info='素金规格明细添加失败';
                                    } else {
                                        if (!empty ($val ['goods_pic'])) {
                                            $res = D('b_goods_pic')->insert($pic_insert);
                                        }
                                    }
                                }else{
                                    if (!empty ($val ['goods_pic'])) {
                                        $res = D('b_goods_pic')->insert($pic_insert);
                                    }
                                }
                            }
                            break;
                        default :
                            $condition = array(
                                'id' => $val ['goods_id']
                            );
                            $update = array(
                                'goods_code'=>trim($val['goods_code']),
                                'price_mode' => $val ['price_mode'],
                                // 'pick_pricemode' => $val ['pick_pricemode'],
                                'sell_pricemode' => $val ['sell_pricemode'],
                                'market_price' => $val ['market_price'],
                                'sell_price' => $val ['sell_price'],
                                // 'pick_price' => $val ['pick_price'],
                                'procure_price'=>$val['procure_price'],
                                'status' => $val ['status'],
                                'goods_spec' => $val ['goods_spec'],
                                'goods_name' => $data ['goods_common_name'] . " " . $val ['goods_spec'],
                                'update_time' => time(),
                            );
                            $goods_detail = array(
                                'weight' => $val ['weight'],
                                'sell_feemode' => $val ['sell_feemode'],
                                'price_mode' => $val ['price_mode'],
                                // 'pick_pricemode' => $val ['pick_pricemode'],
                                'sell_pricemode' => $val ['sell_pricemode'],
                                'buy_fee' => $val ['buy_fee'],
                                'sell_fee' => $val ['sell_fee'],
                                // 'pick_fee' => $val ['pick_fee'],
                                'gold_type' => $val ['gold_type'],
                                'bank_gold_type' => $val['bank_gold_type'],
                                'purity' => $val['purity'],
                                'memo' => $val['memo']
                            );
                            //判断商户下是否存在未删除的该编码的规格
                            $is_exsit=D("BGoods")->goods_is_exsit(array('id'=>array('neq',$val ['goods_id']),'company_id'=>get_company_id(),'goods_code'=>$val ['goods_code'],'deleted'=>0));
                            if(!$is_exsit){
                                $goods_id = D("BGoods")->update($condition, $update);
                            }else{
                                $goods_id=false;
                            }
                            $pic_condition = array(
                                'goods_id' => $val ['goods_id'],
                                'type'=>0
                            );
                            $pic_update = array(
                                'pic' => $val ['goods_pic'],
                                'is_hot' => $val ['is_hot']
                            );
                            if ($goods_id!==false) {
                                    if($data['type']==1) {
                                        $goods_detail["goods_id"] = $val ['goods_id'];
                                        $condition = array("goods_id"=> $val['goods_id']);

                                        $goldgoods_detail = D("b_goldgoods_detail")->getInfo($condition);
                                        if(empty($goldgoods_detail)){
                                            $goods_detail_id = D("b_goldgoods_detail")->insert($goods_detail);
                                        }else{
                                            $goods_detail_id = D("b_goldgoods_detail")->update($condition, $goods_detail);
                                        }

                                        if ($goods_detail_id!==false) {
                                            if (!empty ($val ['goods_pic']) && $val ['goods_pic'] != C('DEFAULT_GOODS_PIC')) {
                                                $res = M('b_goods_pic')->where($pic_condition)->find();
                                                if ($res) {
                                                    $res = D('b_goods_pic')->update($pic_condition, $pic_update);
                                                } else {
                                                    $picdata = array_merge($pic_condition, $pic_update);
                                                    $res = D('b_goods_pic')->insert($picdata);
                                                }
                                            }
                                        } else {
                                            $flag = false;
                                            $info='素金规格明细保存失败';
                                        }
                                    }else{
                                        if (!empty ($val ['goods_pic']) && $val ['goods_pic'] != C('DEFAULT_GOODS_PIC')) {
                                            $res = M('b_goods_pic')->where($pic_condition)->find();
                                            if ($res) {
                                                $res = D('b_goods_pic')->update($pic_condition, $pic_update);
                                            } else {
                                                $picdata = array_merge($pic_condition, $pic_update);
                                                $res = D('b_goods_pic')->insert($picdata);
                                            }
                                        }
                                    }
                            } else {
                                $flag = false;
                                $info='规格保存失败，可能商品编码已经存在';
                            }
                            break;
                    }
                    /*if (!$goods_id) {
                        $flag = false;
                    }*/
                }
            }
        }
        $result=array('status'=>$flag,'info'=>$info);
        return $result;
    }

    // 修改批发商品公共信息及商品信息
    public function editWgoodsCommon($data)
    {
        $flag = true;
        $condition = array(
            'id' => $data ['goods_common_id']
        );
        $update = array(
            //'class_id' => $data ['class_id'],
            'goods_code'=>$data['goods_common_code'],
            'goods_name' => $data ['goods_common_name'],
            'moral' => $data ['moral'],
           // 'is_standard' => $data ['is_standard'],
            //'mobile_show' => $data ['mobile_show'],
            'description' => $data ['description'],
           // 'sell_type' => $data ['sell_type'],
            'update_time' => time()
        );
        $result = $this->update($condition, $update);
        if ($result!==false) {
            $goods_list = $data ['goods_list'];
            foreach ($goods_list as $key => $val) {
                if ($flag) {
                    $type = $val ['type'];
                    switch ($type) {
                        case 'add' :
                            $insert = array(
                                'goods_code' => $val ['goods_code'],
                                'goods_common_id' => $data['goods_common_id'],
                                'goods_spec' => $val ['goods_spec'],
                                'goods_name' => $data['goods_common_name'] . " " . $val ['goods_spec'],
                                'price_mode' => $val ['price_mode'],
                                'sell_pricemode' => $val ['sell_pricemode'],
                                'procure_price' => $val['procure_price'],
                                'market_price' => $val ['market_price'],
                                // 'pick_price' => $val ['pick_price'],
                                'goods_unit' => $val ['goods_unit'],
                                'status' => $val ['status'],
                                'create_time' => time(),
                                'update_time' => time()
                            );
                            $goods_detail = array(
                                'sale_fee' => $val ['sell_fee'],
                                'gold_type' => $val ['gold_type'],
                                'purity' => $val['purity'],
                                'memo' => $val['memo']
                            );
                            $insert["company_id"]=get_company_id();
                            $goods_id =D("BWgoods")->insert($insert);
                            $pic_insert = array(
                                'goods_id' => $goods_id,
                                'pic' => $val ['goods_pic'],
                                'is_hot' => $val ['is_hot'],
                                'type'=>2
                            );
                            if (!$goods_id) {
                                $flag = false;
                            } else {
                                if($data['type']==1) {
                                    $goods_detail["goods_id"] = $goods_id;
                                    $goods_detail_id = D("b_goldgoods_wholesale")->insert($goods_detail);
                                    if (!$goods_detail_id) {
                                        $flag = false;
                                    } else {
                                        if (!empty ($val ['goods_pic'])) {
                                            $res = D('b_goods_pic')->insert($pic_insert);
                                        }
                                    }
                                }else{
                                    if (!empty ($val ['goods_pic'])) {
                                        $res = D('b_goods_pic')->insert($pic_insert);
                                    }
                                }
                            }
                            break;
                        default :
                            $condition = array(
                                'id' => $val ['goods_id']
                            );
                            $update = array(
                                'goods_code' => $val ['goods_code'],
                               // 'goods_common_id' => $data['goods_common_id'],
                                'goods_spec' => $val ['goods_spec'],
                                'goods_name' => $data['goods_common_name'] . " " . $val ['goods_spec'],
                                'price_mode' => $val ['price_mode'],
                                'sell_pricemode' => $val ['sell_pricemode'],
                                'procure_price' => $val['procure_price'],
                                'market_price' => $val ['market_price'],
                                // 'pick_price' => $val ['pick_price'],
                                'goods_unit' => $val ['goods_unit'],
                                'status' => $val ['status'],
                                'update_time' => time()
                            );
                            $goods_detail = array(
                                'sale_fee' => $val ['sell_fee'],
                                'gold_type' => $val ['gold_type'],
                                'purity' => $val['purity'],
                                'memo' => $val['memo']
                            );
                            $goods_id = D("BWgoods")->update($condition, $update);

                            $pic_condition = array(
                                'goods_id' => $val ['goods_id'],
                                'type'=>2
                            );
                            $pic_update = array(
                                'pic' => $val ['goods_pic'],
                                'is_hot' => $val ['is_hot']
                            );
                            if ($goods_id!==false) {
                                if($data['type']==1) {
                                    $goods_detail["goods_id"] = $val ['goods_id'];
                                    $condition=array("goods_id"=>$val ['goods_id']);
                                    $goods_detail_id = D("b_goldgoods_wholesale")->update($condition,$goods_detail);
                                    if ($goods_detail_id!==false) {
                                        if (!empty ($val ['goods_pic']) && $val ['goods_pic'] != C('DEFAULT_GOODS_PIC')) {
                                            $res = M('b_goods_pic')->where($pic_condition)->find();
                                            if ($res) {
                                                $res = D('b_goods_pic')->update($pic_condition, $pic_update);
                                            } else {
                                                $picdata = array_merge($pic_condition, $pic_update);
                                                $res = D('b_goods_pic')->insert($picdata);
                                            }
                                        }
                                    } else {
                                        $flag = false;

                                    }
                                }else{
                                    if (!empty ($val ['goods_pic']) && $val ['goods_pic'] != C('DEFAULT_GOODS_PIC')) {
                                        $res = M('b_goods_pic')->where($pic_condition)->find();
                                        if ($res) {
                                            $res = D('b_goods_pic')->update($pic_condition, $pic_update);
                                        } else {
                                            $picdata = array_merge($pic_condition, $pic_update);
                                            $res = D('b_goods_pic')->insert($picdata);
                                        }
                                    }
                                }
                            } else {
                                $flag = false;
                            }
                            break;
                    }
                }
            }
        }
        return $flag;
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
        $join="left join ".DB_PRE."b_goods_class bgoodsclass on bgoodscommon.class_id=bgoodsclass.id";
        $field='bgoodscommon.*,bgoodsclass.class_name';
        $field=empty($excel_field)?$field:$excel_field;
        $join=empty($excel_join)?$join:$excel_join;
        $data=$this->alias("bgoodscommon")
            ->getList($excel_where,$field,$limit,$join,$order='bgoodscommon.id desc');
        if($data){
            $expotdata=array();
            foreach($data as $k=>$v){
                $expotdata[$k]['id'] = $k + 1 + ($page - 1) * intval(500);
                $expotdata[$k]['goods_code'] = $v['goods_code'];
                $expotdata[$k]['class_name'] = $v['class_name'];
                $expotdata[$k]['goods_name'] =$v['goods_name'];
                $expotdata[$k]['sell_type'] = $v['sell_type']==1?'零售':'批发';
            }
            register_shutdown_function(array(&$this, $action),$excel_where,$excel_field, $excel_join, $page + 1);
            $title=array('序','商品编码','商品分类','商品名称','销售类型');
            exportexcel($expotdata,$title,"商品公共列表");
        }
    }
    // 判断商品编码是否存在
    /**
     * @param $data  $data ['id']商品id  $data ['goods_code'] 商品编码
     * @return bool
     */
    public function goodscode_is_exsit($data) {
        $condition=array('company_id'=>get_company_id(),'goods_code'=>$data ['goods_code'],'deleted'=>0);
        if(!empty($data ['id'])){
            $condition['id']=array('neq',$data ['id']);
        }
        $goodscommom_info = $this->getInfo($condition);
        return (!empty($goodscommom_info));
    }


}
