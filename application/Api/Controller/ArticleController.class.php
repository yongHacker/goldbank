<?php
/**
 * 资讯
 * 
 * @author alam 2018/01/15 10:00
 */
namespace Api\Controller;
defined('JHJAPI') or exit('Access Invalid!');

use Api\Controller\BaseController;

class ArticleController extends BaseController
{
    
    protected $model_b_article, $model_b_article_class;
    
    public function __construct()
    {
        parent::__construct();

        // 不需要权限验证的功能点
        $role_pass = array(
            'class_list',
            'article_list',
            'article_detail'
        );
        $this->api_init($role_pass);
    }
    
    public function _initialize()
    {
        parent::_initialize();

        $this->model_b_article = D('BArticle');
        $this->model_b_article_class = D('BArticleClass');
    }
    
    /**
     * 获取Ipad文章分类根目录节点
     */
    protected function _get_base_class()
    {
        $condition = array(
            'is_ipad' => 1
        );
        $field = 'ac_id, path';
        $class = $this->model_b_article_class->getInfo($condition, $field);
        return empty($class) ? false : $class;
    }
    
    /**
     * 文章分类
     */
    public function class_list()
    {
        $base_class = $this->_get_base_class();
        if (empty($base_class)) {
            $this->encrypt_exit(0, '');
        }
        
        $condition = array(
            'company_id' => $this->role_path['company_id'],
            'path' => array('like', $base_class['path'] . '-%'),
            'status' => 1
        );
        $field = 'ac_id, name, description, ac_pic, ac_content';
        $order = 'listorder DESC, ac_id ASC';
        $count = $this->model_b_article_class->countList($condition, $field);
        $page = $this->getPage($count);
        $class = $this->model_b_article_class->getList($condition, $field, $page['limit'], $join = '', $order);

        $this->encrypt_exit(0, '', array_merge(array('class' => $class), $page));
    }
    
    /**
     * 文章列表
     */
    public function article_list()
    {
        $base_class = $this->_get_base_class();
        
        $class_id = I('post.class_id');
        $condition = array(
            array(
                'bac.is_ipad' => 1,
                'bac.path' => array('like', $base_class['path'] . '-%'),
                '_logic' => 'or'
            ),
            'bac.status' => 1,
            'ba.id' => array('exp', 'is not null')
        );
        
        $class = array(
            'name' => '',
            'description' => ''
        );
        if (!empty($class_id)) {
            // 当前分类简介
            $field = 'name, description';
            $condition = array(
                'ac_id' => $class_id
            );
            $class = $this->model_b_article_class->getInfo($condition, $field);
            
            $condition = array(
                'bac.ac_id' => $class_id,
                'ba.id' => array('exp', 'is not null')
            );
        }
        $condition['bac.company_id'] = $this->role_path['company_id'];
        $condition['ba.deleted'] = 0;
        $field = 'ba.id, ba.ac_id, ba.article_title as title, ba.article_sub_title as sub_title, ba.article_excerpt as excerpt, ba.article_pic as pic, ba.create_time';
        $join = 'LEFT JOIN __B_ARTICLE__ AS ba ON bac.ac_id = ba.ac_id AND ba.deleted = 0';
        $order = 'sort DESC, id ASC';
        $count = $this->model_b_article_class->alias('bac')->countList($condition, $field, $join, $order = '', $group = '');
        $page = $this->getPage($count);
        $list = $this->model_b_article_class->alias('bac')->getList($condition, $field, $limit = $page['limit'], $join, $order,$group = '');
        
        foreach ($list as $key => $value) {
            $pic = json_decode($value['pic'], true);
            $list[$key]['pic'] = $pic['thumb'];
            $list[$key]['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?g=Api&m=Article&a=article_detail&id=' . $value['id'] . '&ac_id=' . $value['ac_id'];;
        }
        
        $this->encrypt_exit(0, '', array_merge(array('article' => $list, 'class' => $class), $page));
        
    }
    
    /**
     * 文章详情
     */
    public function article_detail()
    {
        $id = I('get.id', 0, 'intval');
        $class_id = I('get.class_id', 0, 'intval');
        
        $condition = array(
            'ba.id' => $id,
            'ba.ac_id' => $class_id
        );
        $field = 'mu.user_nicename, ba.*';
        $join = 'LEFT JOIN __B_ARTICLE_CLASS__ bac ON bac.ac_id = ba.ac_id ';
        $join .= 'LEFT JOIN __M_USERS__ mu ON ba.article_author = mu.id ';
        $article = $this->model_b_article->alias('ba')->getInfo($condition, $field, $join);

        if (empty($article) || empty($id) || empty($class_id)) {
            header('HTTP/1.1 404 Not Found');
            header('Status:404 Not Found');
            if (sp_template_file_exists(MODULE_NAME . '/404')) {
                $this->display(':404');
            }
            return;
        }
        $article['article_pic'] = json_decode(article_pic, true);
        
        // 上下页
        $condition = array(
            'ba.ac_id' => $class_id,
            'ba.deleted' => 0
        );
        $order = 'ba.sort DESC, ba.id ASC';
        $list = $this->model_b_article->alias('ba')->getList($condition, 'id', null, '', $order, '');
        
        $key_site = 0;
        foreach ($list as $key => $value) {
            if ($value['id'] == $id) {
                $key_site = $key;
            }
        }

        $next = $prev = 0;
        if ($list[$key_site + 1]['id']) {
            $next = $list[$key_site + 1]['id'];
        }
        if ($key_site > 0 && !empty($list[$key_site - 1]['id'])) {
            $prev = $list[$key_site - 1]['id'];
        }
        echo '<pre>';
        print_r($article).'<br>';
        die;
        $this->assign('next', $next);
        $this->assign('prev', $prev);
        $this->assign('article', $article);
        $this->display();
    }
}