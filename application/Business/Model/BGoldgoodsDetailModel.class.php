<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BGoldgoodsDetailModel extends BCommonModel{

function insert($insert) {
    $insert["company_id"]=get_company_id();
    return parent::insert($insert); // TODO: Change the autogenerated stub
}

}
