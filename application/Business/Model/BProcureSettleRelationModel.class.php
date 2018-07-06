<?php

/**
*	采购结算、采购单关系列表
**/

namespace Business\Model;

use Business\Model\BCommonModel;

class BProcureSettleRelationModel extends BCommonModel {

	/** 通过 settle_id 添加 procure_list 与settle_id 的关系
	*	I('post.procure_list')
	**/
	public function insert_by_settle_id($settle_id = 0,$settle_type=1){

		$info = array('status'=> 1, 'msg'=> '');

		$postdata = I('post.');

		if(count($postdata['procure_list']) > 0){
			$procure_tbl=D("BProcureSettle")->get_settle_model($settle_type);
			$procure_model =D($procure_tbl);
			
			$nowtime = time();

			$this->startTrans();

			foreach($postdata['procure_list'] as $batch){
				$where = $settle_type==3?array('order_id'=> $batch):array('batch'=> $batch);
				$procure_info = $procure_model->getInfo($where);

				$insert_data = array(
					'procure_settle_id'=> $settle_id,
					'procure_id'=> $procure_info['id'],
					'create_time'=> $nowtime,
					'type'=>$settle_type
				);

				$rs = $this->insert($insert_data);
				if($rs === false){
					$info['status'] = 0;
					$info['msg'] .= '操作失败！';
				}
			}

			if($info['status'] == 0){
				$this->rollback();
			}else{
				$this->commit();
			}

		}

		return $info;

	}

}