<?php
/**
 * 会员身份登记.
 * User: Administrator
 * Date: 2019-09-29
 * Time: 9:26
 */

namespace app\admin\controller\user;
use think\Db;
use think\Model;
use app\common\controller\Backend;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class Identity extends Backend
{
    protected $model = null;
    // 定义快速搜索的字段
    protected $searchFields = 'id';
    protected $bkey = 'identityup';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Identity');
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


    //等级的升级条件
    public function upgrade(){
        //获取等级升级的参数
        $levelup = getSys($this->bkey);

        //获取所有的会员身份等级
        $levels = $this->model->select();
        $levels = collection($levels)->toArray();
        //获取会员的等级
        $ulvs = db('user_level')->where('enabled',1)->field('level,levelname')->select();
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            setSys($data[$this->bkey],$this->bkey);
            $this->success(__('success'));
        }
        return view(null,compact('levelup','levels','ulvs'));
    }

}