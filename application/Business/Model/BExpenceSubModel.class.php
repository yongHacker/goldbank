<?php
/**
 * 单据其它费用
 * @author  alam
 * @date 2018/5/11
 */
namespace Business\Model;
use Business\Model\BCommonModel;
class BExpenceSubModel extends BCommonModel {

	public $model_status, $model_status_value;

	public function __contruct()
	{
		parent:__contruct();
	}

	public function _initialize()
	{
		parent::_initialize();
	}

	/**
	 * 获取单据其它费用
	 */
	public function getSublist($condition = array())
	{
		if (empty($condition['ticket_type'])) return false;
		$field = 'es.*, e.name';
		$condition = array_merge(array('es.deleted' => 0), $condition);
		$join = 'LEFT JOIN __B_EXPENCE__ e ON es.expence_id = e.id';
		$list = $this->alias('es')->getList($condition, $field, '', $join);
		return $list ? $list : array();
	}

	/**
	 * 添加多行单据其它费用
	 */
	public function addList($ticket_id = 0, $ticket_type = 1)
	{
		if (empty($ticket_id)) return false;
        $insert_sub = true;
		$sub_datas = I('post.sub_datas');
        if (!empty($sub_datas) && count($sub_datas) > 0) {
            $sub_insert_data = array();
            foreach ($sub_datas as $key => $value) {
                $datas[] = array(
                    'cost' => $value['sub_cost'],
                    'expence_id' => $value['expence_id'],
                    'ticket_id' => $ticket_id,
                    'ticket_type' => $ticket_type
                );
            }
            $insert_sub = $this->insertAll($datas);
        }
        return $insert_sub;
	}

	/**
	 * 编辑单据， 整理单据内其它费用
	 */
	public function editList($ticket_id = 0, $ticket_type = 1)
	{
		if (empty($ticket_id)) return false;
		$sub_datas = I('post.sub_datas');
		// 遍历 存在 修改 不存在 添加 剩下删除
		$condition = array(
			'ticket_id' => $ticket_id,
			'ticket_type' => $ticket_type,
			'deteled' => 0
		);
		$all_list_id = $this->getList($condition, 'id');
		$ids = array();
		foreach ($all_list_id as $key => $value) {
			$ids[$value['id']] = $value['id'];
		}

		$update = $insert = $deleted = true;
		foreach ($sub_datas as $key => $value) {
			if (!empty($value['sub_id']) && in_array($value['sub_id'], $ids)) {
				unset($ids[$value['sub_id']]);
				$condition = array('id' => $value['sub_id']);
				$data = array(
					'cost' => $value['sub_cost'],
					'expence_id' => $value['expence_id']
				);
				$update = $this->update($condition, $data);
			} else {
                $data = array(
                    'cost' => $value['sub_cost'],
                    'expence_id' => $value['expence_id'],
                    'ticket_id' => $ticket_id,
                    'ticket_type' => $ticket_type
                );
				$insert = $this->insert($data);
			}
		}

		// 删除
		$deleted = true;
		if ($ids) {
			$condition = array('id' => array('in', $ids));
			$data = array('deleted' => 1);
			$deleted = $this->update($condition, $data);
		}

		if ($update === false ||  $insert === false || $deleted === false) {
			return false;
		} else {
			return true;
		}
	}
}