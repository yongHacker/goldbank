<?php
namespace Business\Model;
use Business\Model\BCommonModel;
class BEmployeeModel extends BCommonModel{

    protected $_auto = array(
        array('status','1',BCommonModel:: MODEL_INSERT,'string'),
        array('delete','0',BCommonModel::MODEL_INSERT,'string')
    );

}
