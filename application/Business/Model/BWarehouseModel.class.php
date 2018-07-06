<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BWarehouseModel extends BCommonModel{
    private $model_company,$model_option,$model_procure;
    public function __construct() {
        parent::__construct();
        $this->model_company=D('BCompany');
        $this->model_option=D('BOptions');
        $this->model_procure=D('BProcurement');
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
            $procurement_list[$key]=$this->model_procure->getProcureInfo(array('jb_procurement.id'=>$val['procurement_id']));
        }
        return $procurement_list;
    }
    /**
     * 获得采购单中的货品
     * @param string $ids
     * @param int $wh_id
     * @return 
     */
    public function get_procure_product($ids,$wh_id,$type = 3){
        $join2=D("BProduct")->get_product_join_str();
        $field2=D("BProduct")->get_product_field_str($type);
        $product_list=M('b_product as bproduct')->field('bproduct.*, p.pricemode procurement_pricemode'.$field2)
                ->join($join2)
                ->join('left join '.C('DB_PREFIX').'b_procure_storage as ps on ps.id = bproduct.storage_id left join '.C('DB_PREFIX').'b_procurement as p on ps.procurement_id=p.id')
                ->where(array('p.status'=>1,'bproduct.deleted'=>0,'bproduct.status'=>2,'warehouse_id'=>$wh_id,'p.id'=>array('in',$ids)))->select();
        return $product_list;
    }
    /**
     * @author lzy 2018-5-7
     * 获得调拨单中的货品
     * @param string $ids
     * @param int $wh_id
     * @return Ambigous <mixed, boolean, multitype:, unknown, object>
     */
    public function getAllotProduct($ids,$wh_id){
        $join2=D("BProduct")->get_product_join_str();
        $field2=D("BProduct")->get_product_field_str(3);
        $product_list=M('b_product as bproduct')->field('bproduct.*'.$field2)
        ->join($join2)
        ->join('join '.C('DB_PREFIX').'b_allot_detail as ad on bproduct.id = ad.p_id left join '.C('DB_PREFIX').'b_allot as allot on ad.allot_id=allot.id')
        ->where(array('allot.status'=>7,'bproduct.deleted'=>0,'bproduct.status'=>2,'bproduct.warehouse_id'=>$wh_id,'allot.id'=>array('in',$ids)))->select();
        return $product_list;
    }
    /**
     * 创建采购仓并写入参数设置
     * @author lzy 2018-3-27
     * @param string $company_id
     * @return boolean
     */
    public function _CreateDefaultWH($company_id=''){
        if(empty($company_id)){
            $company_id=get_company_id();
        }
        $wh_list=$this->getList(array('company_id'=>$company_id));
        if(!empty($wh_list)){
            return false;
        }
        $company_info=$this->model_company->getInfo(array('id'=>$company_id));
        $insert=array(
            'company_id'=>$company_id,
            'wh_name'=>'默认采购仓',
            'shop_id'=>-1,
            'wh_uid'=>0,
            'wh_code'=>$company_info['company_code'].'001',
            'address'=>$company_info['company_addr'],
            'status'=>1,
            'created_time'=>time(),
            'deleted'=>0
        );
        $result=$this->insert($insert);
        if($result){
            $op_insert=array(
                'company_id'=>$company_id,
                'option_name'=>'b_procurement_warehouse',
                'option_value'=>$result,
                'status'=>1,
                'user_id'=>get_user_id(),
                'update_time'=>time(),
                'deleted'=>0,
                'memo'=>'采购仓'
            );
            $result=$this->model_option->insert($op_insert);
        }
        if($result>0){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 获取当前商户下所有的仓库列表
     * @return unknown
     */
    function get_wh_list($where=array()){
        //仓库下拉列表
        $condition=array("bwarehouse.deleted"=>0,"bwarehouse.company_id"=>get_company_id());
        $condition=empty($where)?$condition:array_merge($condition,$where);
        $field='bwarehouse.*';
        $wh_list=$this->alias("bwarehouse")->getList($condition,$field,$limit="",$join="",$order='bwarehouse.id desc');
        return $wh_list;
    }
}
