<?php
namespace Api\Controller;
use Api\Controller\BaseController;
class UploadController extends BaseController {
	function _initialize() {
        $this->bgoodscommon_model = D("BGoodsCommon");
        $this->bgoodsclass_model = D("BGoodsClass");
        $this->bmetaltype_model = D("BMetalType");
        $this->bbankgoldtype_model = D("BBankGoldType");
        $this->astatus_model = D("AStatus");
	}

    /**
     *上传图片
     * @author dengzs @date 2018/7/5 15:46
     */
	function upload_img(){
        $type=explode('/',$_FILES['goods_pic']['type']);
        $result=b_upload_pic('goods',$_FILES['goods_pic']['tmp_name'],'thumb',$type[1]);
        if ($result['status']==1) {
            output_data ( array (
                'file_name' => $result['filename'],
                'code' => 1
            ) );
        } else {
            output_data ( array (
                'code' => 0,
                'msg' => $result['msg']
            ) );
        }
	}

    /**
     *图片数据库处理
     * @author dengzs @date 2018/7/5 15:45
     */
    function common_img(){
        $rd['status'] = -1;
        $data['type'] = I('type');
        $data['id'] = (int)I('id');
        $data['goods_img'] = I('goods_img');
        $res = $this->bgoodscommon_model->common_goods_img($data);
        if($res){
            $rd['status'] = 1;
            $rd['id'] = $res;
        }else{
            $rd['msg'] = '失败';
        }
        output_data($rd);
    }
	
}