<?php
namespace Shop\Model;
use Shop\Model\BCommonModel;
class BGoodsPicModel extends BCommonModel{
    function insert($insert) {
        $insert["company_id"]=get_company_id();
        return parent::insert($insert); // TODO: Change the autogenerated stub
    }
}
