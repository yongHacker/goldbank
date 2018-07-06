<?php
namespace Business\Model;

use Business\Model\BCommonModel;

class BSupplierModel extends BCommonModel{

	public function __construct() {
		parent::__construct();
	}
	protected $_validate = array(
        // array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
		array('supplier_code', 'require', '供应商编号不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),

        array('company_name', 'require', '公司名称不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT  ),

        array('contact_member', 'require', '业务联系人不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT ),

        array('contact_phone', 'require', '业务联系人电话不能为空！', 1, 'regex', BCommonModel::MODEL_INSERT),

        array('credit_code', 'require', '社会信用代码不能为空！', 1, 'regex', BCommonModel:: MODEL_INSERT ),
    );
	
	/**
	*	查找是否已经存在相同 creditCode 的记录
	*	@param string $credit_code 社会信用代码
	*	@param int $except_id 排除id
	*	@return bool true 已存在 | false 不存在
	*/
	public function checkExistCreditCodeRecord($credit_code = NULL, $except_id = NULL) {
		$rs = false;
		$where = array(
			'credit_code'=> $credit_code,
			// 'deleted'=> 0
		);

		if(!empty($except_id)){
			$where['id'] = array('NEQ', $except_id);
		}

		$count = $this->countList($where);
		if($count > 0){
			$rs = true;
		}

		return $rs;
	}

	public function checkExistSupplier_code($supplier_code = NULL) {
		$rs = false;

		$where = array(
			'bc.supplier_code'=> $supplier_code,
			'bc.company_id'=> get_company_id()
			// 'deleted'=> 0
		);

		$join = ' RIGHT JOIN '.C('DB_PREFIX').'b_company_account as bc ON (bc.supplier_id = s.id)';

		$count = $this->alias('s')->countList($where, '*', $join);
		if($count > 0){
			$rs = true;
		}

		return $rs;
	}

	// 获取列表并 assign 至模板
	public function get_list_assign(VIEW &$view = NULL){
		$main_tbl = C('DB_PREFIX').'b_supplier';

		$where = array(
			'ca.company_id'=> get_company_id(),
			// 不上锁的供应商
			'ca.status'=> 1,
			'ca.deleted'=> 0,
		);
		
		$field = $main_tbl.'.id, '.$main_tbl.'.company_name';

		$join = 'LEFT JOIN '.C('DB_PREFIX').'b_company_account as ca ON (ca.supplier_id = '. $main_tbl .'.id)';

		$supplier_info = $this->getList($where, $field, null, $join);

		$view->assign('supplier_info', $supplier_info);
	}

}
