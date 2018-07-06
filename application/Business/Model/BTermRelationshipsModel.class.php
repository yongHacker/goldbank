<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BTermRelationshipsModel extends BCommonModel {
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}

}