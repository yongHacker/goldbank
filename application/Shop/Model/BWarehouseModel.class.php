<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BWarehouseModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }
//获取仓库列表与其相关的详细信息
    public function getList_detail($where=array()){

        $condition=array("bwarehouse.deleted"=>0,"bwarehouse.company_id"=>get_company_id());
        if(!empty($where)){
            $condition=array_merge($condition,$where);
        }
        $join="left join ".DB_PRE."m_users muser on muser.id=bwarehouse.wh_uid";
        $field='bwarehouse.*,muser.user_nicename';
        $wh_data=$this->alias("bwarehouse")->getList($condition,$field,$limit="",$join,$order='bwarehouse.id desc');
        return $wh_data;
    }
    public function getAllProcureFromId($wh_id,$page,$batch=null){
        if(empty($batch)){
            $procurement_list=M('b_product as p')->field('storage_id')->join('left join '.C('DB_PREFIX').'b_procurement as pr on p.storage_id=pr.id')->where(array('pr.status'=>1,'p.deleted'=>0,'p.status'=>2,'warehouse_id'=>$wh_id))->group('storage_id')->limit($page->firstRow.','.$page->listRows)->order('pr.id desc')->select();
        }else{
            $procurement_list=M('b_product as p')->field('storage_id')->join('left join '.C('DB_PREFIX').'b_procurement as pr on p.storage_id=pr.id')->where(array('pr.status'=>1,'p.deleted'=>0,'p.status'=>2,'warehouse_id'=>$wh_id,'pr.batch'=>array('like','%'.$batch.'%')))->group('storage_id')->order('pr.id desc')->limit($page->firstRow.','.$page->listRows)->select();
        }
        foreach ($procurement_list as $key=>$val){
            $procurement_list[$key]=D('Shop/BProcurement')->getProcureInfo(array('jb_procurement.id'=>$val['procurement_id']));
        }
        return $procurement_list;
    }
    public function get_procure_product($ids,$wh_id){
        $product_list=M('b_product as bp')->field('bp.*,g.goods_name as product_name')
                ->join('left join '.C('DB_PREFIX').'b_goods as g on bp.goods_id=g.id left join '.C('DB_PREFIX').'b_procure_storage as ps on ps.id = bp.storage_id left join '.C('DB_PREFIX').'b_procurement as p on ps.procurement_id=p.id')
                ->where(array('p.status'=>1,'bp.deleted'=>0,'bp.status'=>2,'warehouse_id'=>$wh_id,'p.id'=>array('in',$ids)))->select();
        return $product_list;
    }
    //通过调拨单获取调拨货品
    public function getAllotProduct($ids,$wh_id){
        $join2=D("BProduct")->get_product_join_str();
        $field2=D("BProduct")->get_product_field_str(3);
        $product_list=M('b_product as bproduct')->field('bproduct.*'.$field2)
            ->join($join2)
            ->join('join '.C('DB_PREFIX').'b_allot_detail as ad on bproduct.id = ad.p_id left join '.C('DB_PREFIX').'b_allot as allot on ad.allot_id=allot.id')
            ->where(array('allot.status'=>7,'bproduct.deleted'=>0,'bproduct.status'=>2,'bproduct.warehouse_id'=>$wh_id,'allot.id'=>array('in',$ids)))->select();
        return $product_list;
    }
}
