<?php
/**
 * 其它费用类目设置
 * @author: alam
 * @time: 9:17 2018/5/22
 */
namespace Business\Controller;

use Business\Controller\BusinessbaseController;

class BExpenceController extends BusinessbaseController
{

    public $model_expence, $model_expence_sub;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize() 
    {
        parent::_initialize();
        $this->model_expence = D('BExpence');
        $this->model_expence_sub = D('BExpenceSub');
        $this->b_show_status('b_expence');
    }

    /**
     * 其它费用类目
     */
    public function index()
    {
        $type = I('type/d', 0);
        $search = I('search/s', '');
        $condition = array(
            'deleted' => 0,
            'company_id' => $this->MUser['company_id'],
        );
        if ($type) {
            $condition['type'] = $type;
        }
        if ($search) {
            $condition['name'] = array('like', '%' . $search . '%');
        }

        $field = 'name, id as expence_id, type, create_time';
        $count = $this->model_expence->countList($condition, '', '', true);
        $page = $this->page($count, $this->pagenum);
        $show = $page->show('Admin');
        $limit = $page->firstRow . "," . $page->listRows;
        $list = $this->model_expence->getList($condition, $field, $limit, '', 'id DESC');

        $this->assign('numpage', $this->pagenum);
        $this->assign('page', $show);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加类目
     */
    public function add()
    {
        if (IS_POST) {
            $name = I('name/s', '');
            $type = I('type/d', 0);
            if (empty($type) || empty($name)) {
                $info['status'] = '0';
                $info['info'] = '网络错误';
                $this->ajaxReturn($info);
            }

            $this->check_count($type, $name);

            $data = array(
                'type' => $type,
                'company_id' => $this->MUser['company_id'],
                'name' => $name,
                'create_time' => date('Y-m-d H:i:s')
            );
            $res = $this->model_expence->add($data);
            if ($res !== false) {
                $info['status'] = '1';
                $info['info'] = '添加成功！';
                $this->ajaxReturn($info);
            } else {
                $info['status'] = '0';
                $info['info'] = '添加失败！';
                $this->ajaxReturn($info);
            }
        } else {
            $this->display();
        }
    }

    /**
     * 修改类目
     */
    public function edit()
    {
        $expence_id = I('expence_id/d', 0);
        if (IS_POST) {
            if (! $expence_id) {
                $this->add();
                die();
            }
            $name = I('name/s', '');
            $type = I('type/d', 0);
            if (empty($type) || empty($name)) {
                $info['status'] = '0';
                $info['info'] = '网络错误';
                $this->ajaxReturn($info);
            }

            $this->check_count($type, $name);

            $condition = array(
                'deleted' => 0,
                'id' => $expence_id
            );
            $data = array(
                'type' => $type,
                'name' => $name
            );
            $res = $this->model_expence->update($condition, $data);
            if ($res !== false) {
                $info['status'] = '1';
                $info['info'] = '修改成功！';
                $this->ajaxReturn($info);
            } else {
                $info['status'] = '0';
                $info['info'] = '修改失败！';
                $this->ajaxReturn($info);
            }
        } else {
            $condition = array(
                'deleted' => 0,
                'company_id' => get_company_id(),
                'id' => $expence_id
            );
            $data = $this->model_expence->getInfo($condition);
            $this->assign('data', $data);
            $this->display();
        }
    }

    /**
     * 删除类目
     */
    public function deleted()
    {
        $expence_id = I('expence_id/d', 0);
        if (empty($expence_id)) {
            $info['status'] = '0';
            $info['info'] = '信息错误！';
            $this->ajaxReturn($info);
        }
        // 检查该类目是否已被使用
        $condition = array(
            'deleted' => 0,
            'expence_id' => $expence_id
        );
        $sub_info = $this->model_expence_sub->getInfo($condition);
        if ($sub_info) {
            $info['status'] = '0';
            $info['info'] = '该类目存在采购单据，无法删除！';
            $this->ajaxReturn($info);
        }

        $condition = array(
            'id' => $expence_id, 
            'company_id' => $this->MUser['company_id']
        );
        $res = $this->model_expence->update($condition, array('deleted' => 1));
        if ($res !== false) {
            $info['status'] = '1';
            $info['info'] = '删除成功！';
            $this->ajaxReturn($info);
        } else {
            $info['status'] = '0';
            $info['info'] = '删除失败！';
            $this->ajaxReturn($info);
        }
    }

    /**
     * 检查是否存在相同类目
     */
    protected function check_count($type, $name)
    {
        $condition = array(
            'type' => $type,
            'deleted' => 0,
            'company_id' => $this->MUser['company_id'],
            'name' => $name
        );
        $count = $this->model_expence->getInfo($condition);
        if ($count) {
            $info['status'] = '0';
            $info['info'] = '已存在相同名称类目！';
            $this->ajaxReturn($info);
        }
    }

    /**
     * 助手函数 输出其它费用类目 用于单据中
     * @param  $type 类目类型 1-销售 2-采购 3-采购退货 4-销售退货 
     */
    public function _expenceList($type = 1)
    {
        $condition = array(
            'deleted' => 0,
            'company_id' => get_company_id(),
            'type' => $type
        );
        $expence_list = $this->model_expence->getList($condition);
        $this->assign('expence_list', $expence_list);
    }
}