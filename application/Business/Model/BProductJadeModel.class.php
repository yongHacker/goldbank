<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BProductJadeModel extends BCommonModel{
    public function __construct() {
        parent::__construct();
    }
    function insert($insert) {
        $insert["company_id"]=get_company_id();
        return parent::insert($insert); // TODO: Change the autogenerated stub
    }
}
