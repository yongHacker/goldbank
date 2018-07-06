<?php
namespace Api\Model;
use Api\Model\ApiCommonModel;
class BGoodsPicModel extends ApiCommonModel {
    
    public function __construct() {
        parent::__construct();
    }
    
    // 获得商品图片列表
    public function getGoodsPicList($condition, $field = '*', $order = 'is_hot desc') {
        $pic_list = $this->alias('gp')->field($field)->where($condition)->order($order)->select();
        return $pic_list;
    }

    // 获取商品图片
    public function getGoodsPic($condition, $field = '*', $order = 'is_hot desc') {
        $pic = $this->alias('gp')->field($field)->where($condition)->order($order)->find();
        return $pic;
    }
    
	// 获取商品公共主图
	public function getGoodsCommonPicFromCommonId($goods_common_id/* , $type = null */) {
//         if (empty($type)) {
            $goods_list = D('Api/BGoods')->getGoodsList(array(
                'goods_common_id' => $goods_common_id
            ));
            $goods_id = '0';
            foreach ($goods_list as $k => $v) {
                $goods_id .= ',' . $v['id'];
            }
            // 获取规格图片
            $pic = $this->getGoodsPic(array(
                'goods_id' => array(
                    'in',
                    $goods_id
                ),
                'type' => 0,
                'deleted' => 0
            ));
            $common_pic = $this->getGoodsPic(array(
                'goods_id' => $goods_common_id,
                'type' => 1,
                'deleted' => 0
            ));
            if (! empty($common_pic) && is_array($common_pic)) {
                $pic = $common_pic;
            }
            return $pic['pic'];
        /* } else {
            $goods_list = $this->getGoodsList(array(
                'goods_common_id' => $goods_common_id
            ));
            $goods_id = '0';
            foreach ($goods_list as $k => $v) {
                $goods_id .= ',' . $v['id'];
            }
            if ($type == "all") {
                $common_pic = $this->getGoodsPic(array(
                    'goods_id' => $goods_common_id,
                    'type' => 1,
                    'deleted' => 0
                ));
                if (! empty($common_pic) && is_array($common_pic)) {
                    $pic = $common_pic;
                }
                $result = array(
                    'pic' => $pic['pic'],
                    'is_hot' => $pic['is_hot'],
                    'goods_id' => $goods_id
                );
                return $result;
            } else if ($type == "list") {
                // 获取公共图片列表
                $pic_list = $this->getGoodsPicList(array(
                    'goods_id' => $goods_common_id,
                    'type' => 1,
                    'deleted' => 0
                ));
                if (I('get.debug') == 1) {
                    (var_dump($pic_list));
                }
                return $pic_list;
            } elseif ($type == "list1") {
                // 获取规格图片列表
                $pic_list = $this->getGoodsPicList(array(
                    'goods_id' => array(
                        'in',
                        $goods_id
                    ),
                    'type' => 0,
                    'deleted' => 0
                ));
                // 获取公共图片列表
                $common_list = $this->getGoodsPicList(array(
                    'deleted' => 0,
                    'type' => 1,
                    'goods_id' => array(
                        'in',
                        $goods_common_id
                    )
                ));
                if (! is_array($common_list))
                    $common_list = array();
                
                $pic_list = array_merge($pic_list, $common_list);
                
                return $pic_list;
            } elseif ($type == "pic") {
                $goods_list = D('Api/BGoods')->getGoodsList(array(
                    'goods_common_id' => $goods_common_id
                ));
                $goods_id = '0';
                foreach ($goods_list as $k => $v) {
                    $goods_id .= ',' . $v['id'];
                }
                // 获取规格图片
                $pic = $this->getGoodsPic(array(
                    'goods_id' => array(
                        'in',
                        $goods_id
                    ),
                    'type' => 0,
                    'deleted' => 0
                ));
                $common_pic = $this->getGoodsPic(array(
                    'goods_id' => $goods_common_id,
                    'type' => 1,
                    'deleted' => 0
                ));
                if (! empty($common_pic) && is_array($common_pic)) {
                    $pic = $common_pic;
                }
                $pic['goods_id'] = $goods_id;
                return $pic;
            }
        } */
    }
}