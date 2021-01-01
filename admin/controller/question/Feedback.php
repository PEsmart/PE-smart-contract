<?php
/**
 * 公告栏.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 14:36
 */

namespace app\admin\controller\question;
use app\common\controller\Backend;

class Feedback extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id,uid';
    protected $model = null;
    protected static $category = null;

    public function _initialize()
    {
		parent::_initialize();
		$this->model = model('Feedback');
    }


    // 查看
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
				->with(["user_detail"])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
				->with(["user_detail"])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    public function detail($ids = null){
        $row = $this->model->get($ids);
        if(!empty($row)){
            $row = $row->toArray();
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}