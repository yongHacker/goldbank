<?php
namespace Api\Model;
use Api\Model\ApiCommonModel;
class BGoodsCommonModel extends ApiCommonModel {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 根据分类获取商品列表
     * @param unknown $class_ids
     * @param unknown $condition
     */
    public function getGoodsByClass($count = false, $condition = array(), $warehouse_id = 0, $limit = '')
    {
        if ($count) {
            $count = $this->alias('gco')->join('left join __B_GOODS_CLASS__ AS gcl ON gcl.id = gco.class_id')
            ->where($condition)
            ->count();
            return $count;
        } else {
            $goods = $this->alias('gco')
            ->join('left join __B_GOODS_CLASS__ AS gcl ON gcl.id = gco.class_id')
            ->join('left join __B_GOODS__ AS g ON g.goods_common_id = gco.id')
            ->join('left join __B_PRODUCT__ AS p ON p.goods_id = g.id AND p.deleted = 0 AND p.warehouse_id = ' . $warehouse_id . ' AND p.status IN (2)')
            ->where($condition)
            ->order('num desc, gco.id asc')
            ->group('gco.id')
            ->limit($limit)
            ->field('DISTINCT gco.id as goods_common_id, gco.goods_code, gco.goods_name, COUNT(p.id) as goods_num, if(COUNT(p.id)>0, "1", "0") AS num')
            ->select();

            foreach ($goods as $key => $value) {
                $goods[$key]['goods_pic'] = D('Api/BGoodsPic')->getGoodsCommonPicFromCommonId($value['goods_common_id']);
            }
            
            return $goods ? $goods : array();
        }
    }
    
    /**
     * 获取商品公共
     */
    public function getGoodsCommonInfo($condition = array(), $field = 'gco.*')
    {
        if (empty($condition)) return array();
        $goods_common_info = $this->alias('gco')->where($condition)->field($field)->join('join __B_GOODS_CLASS__ AS gcl on gcl.id = gco.class_id')->find();
        return $goods_common_info ? $goods_common_info : array();
    }
    
    /**
     * 获取商品公共的详情
     */
    public function getGoodsCommonDetail($goods_common_id = 0, $warehouse_id = 0)
    {
        if (empty($goods_common_id)) return array();
        
        $gold_price = D('Api/AGold')->getGoldPrice();

        $goods_common_field = 'gco.id, gco.class_id, gco.goods_code, gco.goods_name, gco.description, gcl.class_name';
        $goods_common = $this->getGoodsCommonInfo(array('gco.id' => $goods_common_id), $goods_common_field);

        $condition = array(
            'gco.id' => $goods_common_id
        );
        $goods_field = 'DISTINCT g.id, g.goods_code, g.goods_common_id, g.goods_name, g.goods_spec, g.sell_pricemode, g.sell_price, g.photo_switch, gp.pic, count(p.id) as goods_num,';
        $goods_field .= 'ggd.weight, ggd.sell_pricemode, ggd.sell_fee, ggd.gold_type, ggd.bank_gold_type, ggd.purity, ggd.memo';
        $goods = D('Api/BGoods')->getGoodsDetailList($condition, $goods_field, $warehouse_id);
        
        return array('gold_price' => $gold_price, 'goods_common' => $goods_common, 'goods' => $goods);
    }


    /**
     *商品公共图片处理
     * @param $data
     * @return bool|mixed
     * @author dengzs @date 2018/7/5 15:54
     */
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
            $m->pic = $data['goods_img'];
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
}