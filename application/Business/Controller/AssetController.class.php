<?php
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class AssetController extends BusinessbaseController {

    function _initialize() {
    	$adminid=get_user_id();
    	$userid=sp_get_current_userid();
    	if(empty($adminid) && empty($userid)){
    		exit("非法上传！");
    	}
    }
    
    
    // 文件上传
    public function plupload(){
        $upload_setting=sp_get_upload_setting();
        $filetypes=array(
            'image'=>array('title'=>'Image files','extensions'=>$upload_setting['image']['extensions']),
            'video'=>array('title'=>'Video files','extensions'=>$upload_setting['video']['extensions']),
            'audio'=>array('title'=>'Audio files','extensions'=>$upload_setting['audio']['extensions']),
            'file'=>array('title'=>'Custom files','extensions'=>$upload_setting['file']['extensions'])
        );

        if (IS_POST) {
            $dir = I('dir','goods_class');
            $type=explode('/',$_FILES['file']['type']);
            $result=b_upload_pic($dir,$_FILES['file']['tmp_name'],'thumb',$type[1]);
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
        } else {
            $filetype = I('get.filetype/s','image');
            $mime_type=array();
            if(array_key_exists($filetype, $filetypes)){
                $mime_type=$filetypes[$filetype];
            }else{
                $this->error('上传文件类型配置错误！');
            }
            
            $multi=I('get.multi',0,'intval');
            $app=I('get.app/s','');
            $upload_max_filesize=$upload_setting[$filetype]['upload_max_filesize'];
            $this->assign('extensions',$upload_setting[$filetype]['extensions']);
            $this->assign('upload_max_filesize',$upload_max_filesize);
            $this->assign('upload_max_filesize_mb',intval($upload_max_filesize/1024));
            $this->assign('mime_type',json_encode($mime_type));
            $this->assign('multi',$multi);
            $this->assign('app',$app);
            $this->display();
        }
    }

    /**
     *文件处理
     * @author dengzs @date 2018/7/9 17:38
     */
    public function plhandle(){
        $rd['status'] = -1;
        $type = I('type');
        $file_name = I('file_name');
        if ($file_name == ''){
            output_data(['status'=>2,'msg'=>'图片不存在，请先上传图片']);
        }
        if ($type == 'del')
        $res = b_del_pic($file_name);
        if($res){
            $rd['status'] = 1;
            $rd['id'] = $res;
        }else{
            $rd['msg'] = '失败';
        }
        output_data($rd);
    }

}
