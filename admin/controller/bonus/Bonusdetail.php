<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 17:56
 */
namespace app\admin\controller\bonus;

use app\common\controller\Backend;

class Bonusdetail extends Backend
{
    protected $noNeedLogin = [];
    protected $noNeedRight = [];
    protected $relationSearch = true;

    protected $model = null;
    public function _initialize(){
        parent::_initialize();
        $this->model = model('Bonusdetail');
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
                ->with(["user"])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(["user"])
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


}