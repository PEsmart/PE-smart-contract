<?php
/**
 * 预约记录.
 * User: admin
 * Date: 2019/5/16
 * Time: 18:00
 */

namespace app\admin\controller\blockore;

use app\common\controller\Backend;
use think\Validate;

class Booking extends Backend
{
    protected $searchFields = 'id,levelname';
    protected $model = null;
    //矿等级列表
    protected $orelvs = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Booking');
    }

    public function getLevel(){
        $lvs = db('block_ore_level')->where('id','>',0)->column('level,levelname','level');
        $this->success(json_encode($lvs,JSON_UNESCAPED_UNICODE));
    }

    public function index()
    {
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

            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $lvs = db('block_ore_level')->where('id','>',0)->column('level,levelname','level');
            if ($list) {
                foreach ($list as &$val) {
                    $val['levelname'] = $lvs[$val['level']];
                }
                unset($val);
            }

            $result = array('total' => $total, 'rows' => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}