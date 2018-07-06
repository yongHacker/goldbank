<?php
namespace System\Controller;
use Common\Controller\SystembaseController;
class GoodsClassController extends SystembaseController
{
    protected $goods_class_model, $goods_model;

    public function __construct()
    {
        parent::__construct();
        $this->goods_class_model = D("System/AGoodsClass");
    }
    // 商品类目列表展示
    public function index()
    {
        // 获取商品类型
        $statuscondition["table"] = C("DB_PREFIX")."a_goods_class";
        $statuscondition["field"] = "type";
        $value_condition['status']=0;
        $statusall = D("a_status")->getStatusInfo($statuscondition,$value_condition);
        // 节点树
        $goods_class = $this->goods_class_model->getGoodsClass();
        $this->assign("statusall", $statusall);
        $this->assign("goods_class", $goods_class);
        $this->display();
    }

    public function class_data()
    {
        // 判断是否为ajax请求
        // if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
        $tree = $this->goods_class_model->gettrees();
        die(json_encode($tree));
        // }
    }

    // 增加商品类目
    public function add()
    {
        // if (I('post.pid')) {
            $sa['pid'] = I('post.pid/d', 1);
            $image = $_FILES["file"];
            if ($image['name']) {
                $imgData = $this->uploads($image);
                if($imgData == 1){
                    $data["status"] = 0;
                    $data["msg"] = "失败";
                    $this->ajaxReturn($data);
                }
                $sa['photo'] = $imgData['up_url'];
            }
            $wsa['deleted'] = 0;
            $wsa['class_name'] = I('post.class_name');
            $is_exist = $this->goods_class_model->getInfo($wsa);
            if($is_exist){
                $data["status"] = 0;
                $data["msg"] = "商品类名已存在请勿重复添加！";
                $this->ajaxReturn($data);
            }
            $sa['class_name'] = I('post.class_name');
            $sa['type'] = I('post.type');
            $sa['deleted'] = 0;
            $sa['create_time'] = time();
            $result = $this->goods_class_model->insert($sa);
            if ($result > 0) {
                $data["status"] = 1;
                $data["msg"] = "成功";
                $this->ajaxReturn($data);
            } else {
                $data["status"] = 0;
                $data["msg"] = "失败";
                $this->ajaxReturn($data);
            }
        // } else {
        //     $data["status"] = 0;
        //     $data["msg"] = "失败";
        //     $this->ajaxReturn($data);
        // }
    }
    // 修改商品类目
    public function edit()
    {
        if (I('post.id')) {
            $sa['pid'] = I('post.pid');
            $sa['class_name'] = I('post.class_name');
            $sa['type'] = I('post.type');
            $map["id"] = I('post.id');
            $image = $_FILES["file"];
            $wsa['deleted'] = 0;
            $wsa['class_name'] = I('post.class_name');
            $wsa['id'] = array("neq",I('post.id'));
            $is_exist = $this->goods_class_model->getInfo($wsa);
            if ($sa['pid'] == $map["id"]) {
                $data["status"] = 0;
                $data["msg"] = "编辑失败,不允许选择当前分类为上级节点！";
                $this->ajaxReturn($data);
            }
            if($is_exist){
                $data["status"] = 0;
                $data["msg"] = "编辑失败,商品类名已存在！";
                $this->ajaxReturn($data);
            }
            if ($image['name']) {
                $imgData = $this->uploads($image);
                if($imgData==1){
                    $data["status"] = 0;
                    $data["msg"] = "失败";
                    $this->ajaxReturn($data);
                }
                $sa['photo'] = $imgData['up_url'];
            }
            $result = $this->goods_class_model->update($map,$sa);
            if ($result !== false) {
                $info["status"] = 1;
                $info["msg"] = "编辑成功";
            } else {
                $info["status"] = 0;
                $info["msg"] = "编辑失败";
            }
        } else {
            $info["status"] = 0;
            $info["msg"] = "编辑失败";
        }
        $this->ajaxReturn($info);
    }
    // 删除商品类目（并非真的删除，只是修改删除标识）
    public function delete()
    {
        if (I('post.bm_id')) {
            $pid = I('post.bm_id');
            $del_save['deleted'] = 1;
            $del_save['update_time'] = time();
            $condition["pid"] = $pid;
            $condition["deleted"] = 0;
            $result = $this->goods_class_model->getInfo($condition);
            if (! empty($result)) {
                $info["status"] = 0;
                $info["msg"] = "该类目下还有子类目，无法删除";
                $this->ajaxReturn($info);
            } else {
                $goodscondition["jb_goods_common.class_id"] = $pid;
                $goodscondition["jb_goods_common.deleted"] = 0;
                /*$result2 = $this->goods_model->goods_common_exsit($goodscondition);
                if ($result2) {
                    $info["status"] = 0;
                    $info["msg"] = "该类目下还有商品，不能删除该类目";
                    $this->ajaxReturn($info);
                } else {*/
                $classcondition = array();
                $classcondition['id'] = $pid;
                $this->goods_class_model->update($classcondition, $del_save);
                $info["status"] = 1;
                $info["msg"] = "节点删除成功";
                $this->ajaxReturn($info);
            }
        } else {
            $info["status"] = 0;
            $info["msg"] = "不存在该节点";
            $this->ajaxReturn($info);
        }
    }
    // 上传图片文件
    public function uploads($image)
    {
        $typeArray = array(
            "image/gif",
            "image/jpeg",
            "image/pjpeg",
            "image/png"
        );

        $name = $image["name"];
        $error = $image["error"];
        $size = $image["size"];
        $type = $image["type"];
        $tmp_name = $image["tmp_name"];

        if ($error) {
            switch ($error) {
                case 1:
                    $err_info = "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                    $err_code = 601;
                    break;
                case 2:
                    $err_info = "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                    $err_code = 602;
                    break;
                case 3:
                    $err_info = "文件只有部分被上传";
                    $err_code = 603;
                    break;
                case 4:
                    $err_info = "没有文件被上传";
                    $err_code = 604;
                    break;
                case 6:
                    $err_info = "找不到临时文件夹";
                    $err_code = 605;
                    break;
                case 7:
                    $err_info = "文件写入失败";
                    $err_code = 606;
                    break;
                default:
                    $err_info = "未知的上传错误";
                    $err_code = 607;
                    break;
            }

        }
        if (!in_array($type, $typeArray)) {
            echo "文件类型错误！";
            return 1;
        }
        if ($size > 1048576) { // 524288
            echo "文件大小不能超过1MB";
            return 1;
        }
        // 根目录路径
        $imgNameUrl = C("SITE_URL").'/Uploads/User/' . time() . '.jpg';
        $imgNameUrl1 = 'Uploads/User/' . time() . '.jpg';
        $imgData = array();
        $imgData['up_url'] = $imgNameUrl;
        $imgStatus = move_uploaded_file($tmp_name, $imgNameUrl1);
        if ($imgStatus) {
            return $imgData;
        } else {
            echo "文件上传失败！";
            return 1;
        }
    }
}