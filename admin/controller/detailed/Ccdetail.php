<?php
/**
 * 各个币种变动的明细记录
 * User: Administrator
 * Date: 2019/3/19
 * Time: 10:00
 */

namespace app\admin\controller\Detailed;
use app\common\controller\Backend;

class Ccdetail extends Backend {

    // 定义快速搜索的字段
    protected $searchFields = 'id,username,type';

    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Ccdetail');
    }

    /**
     * 查看
     */
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
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
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

    // 详情
    public function detail($ids = null){
        $row = $this->model->get($ids);
        if(!empty($row)){
            $row = $row->toArray();
            $row['createtime'] = date('Y-m-d H:i:s',$row['createtime']);
            $row['updatetime'] = date('Y-m-d H:i:s',$row['updatetime']);
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
