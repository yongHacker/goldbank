<?php
namespace Client\Model;
use Client\Model\CommonModel;
class CFavoritesModel extends CommonModel{

    /**
     * @param $condition
     * @param string $field
     * @param null $limit
     * @param string $join
     * @param string $order
     * @return array
     */
    public function getList($condition,$field='*',$limit=null,$join='',$order=''){
        return $this->join($join)->where($condition)->field($field)->limit($limit)->order($order)->select();
    }

    /**
     * @param $condition
     * @param string $field
     * @param string $join
     * @param string $order
     * @return int
     */
    public function countList($condition,$field='*',$join='',$order=''){
        return $this->join($join)->where($condition)->field($field)->order($order)->count();
    }

    /**
     * @param $condition
     * @param string $field
     * @param string $join
     * @return array
     */
    public function getInfo($condition,$field='*',$join=""){
        return $this->where($condition)->field($field)->join($join)->find();
    }

    /**
     * @param $data
     * @return int
     */
    public function insert($data)
    {
        return $this->add($data);
    }

    /**
     * @param $condition
     * @param $data
     * @return bool
     */
    public function update($condition, $data)
    {
        return $this->where($condition)->save($data);
    }

}
