<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 10:04
 */
namespace app\admin\controller\bonus;

use app\common\controller\Backend;
use think\Db;

class Bonussource extends Backend
{
    protected $noNeedLogin = [];
    protected $noNeedRight = [];

    protected $model = null;
    public function _initialize(){
        parent::_initialize();
        $this->model = model('Bonussource');
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
                ->group('periods')
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('periods, granttime, done, sum(money) as money, sum(netincome) as netincome, sum(f5) as f5, sum(f6) as f6,sum(f7) as f7,sum(f8) as f8,sum(f9) as f9')
                ->group('periods')
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function detail($ids = NULL){
        $row = $this->model->where(['periods' => $ids])->select();
        if (!$row)
            $this->error(__('No Results were found'));

        foreach ($row as &$item) {
            $username = \db('user')
                ->field('username')
                ->where("id={$item['uid']}")
                ->find();
            $item['uname'] = $username['username'];
        }
        unset($item);

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


}