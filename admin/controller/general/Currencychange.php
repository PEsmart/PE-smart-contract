<?php

namespace app\admin\controller\general;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Currencychange extends Backend
{

    /**
     * Change模型对象
     * @var \app\admin\model\currency\Change
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\currency\Change;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
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

    public function add()
    {

        if ($this->request->isPost()) {

            $params = $this->request->post("row/a");

            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $time=time();
                $ty = date('Y', time());
                $tm = date('m', time());
                $td = date('d', time());
                $todayStartTime = mktime(0,0,0,$tm,$td,$ty);
                $todayEndTime   = mktime(23,59,59,$tm,$td,$ty);

                if($params['min']>$params['max']){
                    $this->error('区间起始值大于结束值');
                }

                if($params['change_min']>$params['max']){
                    $this->error('最小变化值不在变化区间值里');
                }

                if($params['change_max']>$params['max']){
                    $this->error('最小变化值不在变化区间值里');
                }
                $todayChange=db('currency_change')->whereBetween('create_time',[$todayStartTime,$todayEndTime])->find();
                if($todayChange){
                    $this->error('今天已经设置过了');
                }
                $data=[
                    'min'=>$params['min'],
                    'max'=>$params['max'],
                    'change_min'=>$params['change_min'],
                    'change_max'=>$params['change_max'],
                    'create_time'=>$time,
                ];

                \db('currency_change')->insert($data);
                $this->success("添加成功");
            }
        }
        return $this->view->fetch();

    }



}
