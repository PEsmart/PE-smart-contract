<?php
/**
 * 矿订单.
 * User: Administrator
 * Date: 2019/4/3
 * Time: 16:07
 */

namespace app\admin\controller\neworder;

use app\common\controller\Backend;

class Order extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id';

    protected $model = null;

    public function _initialize(){
        parent::_initialize();
        $this->model = model('Neworder');
    }


    /**
     * 查看
     */
    public function index()
    {
        /*
         * 数据库插入测试
         */
        //1：直接插入
//        set_time_limit(0);
//        echo 'start<br/>';
//        $s_time=microtime();
//        for($i=1;$i<5000;$i++){
//            db('test_use')->insert(['random_num'=>mt_rand(10000,99999)]);
//        }
//        $e_time=microtime();
//        echo $e_time-$s_time;
//        die;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort='neworder.id';
            $total = $this->model
                ->where($where)
                ->with(['user','machine'])
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->with(['user'=>function($query){
                    $query->withField('username');
                },'machine'=>function($query){
                    $query->withField('name');
                }])
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as &$v){
                $v['createtime']=date('Y-m-d H:i:s',$v['createtime']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

}