<?php
/**
 * 区块矿列表
 * User: Administrator
 * Date: 2019/3/27
 * Time: 19:40
 */

namespace app\admin\controller\blockore;

use app\common\controller\Backend;
use think\Validate;

class Blockore extends Backend
{
    protected $searchFields = 'id,levelname';
    protected $model = null;
    //矿等级列表
    protected $orelvs = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Blockore');
        //获取矿等级列表
        $this->orelvs = db('block_ore_level')->field('level,levelname')->where('status',1)->column('level,levelname','level');
        $this->view->assign('orelvs',$this->orelvs);
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

            foreach ($list as &$val) {
                $val['levelname'] = isset($this->orelvs[$val['level']]) ? $this->orelvs[$val['level']] : '无';
            }
            unset($val);

            $result = array('total' => $total, 'rows' => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        //获取矿编号
        $orecode = db('block_ore')->where('id','>',0)->order('orecode desc')->field('orecode')->find();
        if (empty($orecode)) {
            $orecode['orecode'] = '1001';
        }else{
            $orecode['orecode'] += 1;
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }

                    //矿编号是唯一的，新增前做检测
                    $code = db('block_ore')->field('id')->where('orecode',$params['orecode'])->find();
                    if (!empty($code)) {
                        $this->error(__('该'.config('site.ore_text').'编号已存在，请换一个！'));
                    }

                    //添加时如果有指定归属用户编号，则要生成订单
                    if (!empty($params['ap_username'])) {
                        //先查询该用户编号是否存在
                        $apinfo = db('user')->field('id,username')->where('username',$params['ap_username'])->find();
                        if (empty($apinfo)) {
                            $this->error(__('该归属用户编号不存在！'));
                        }else{
                            //生成订单
                            $lvs = db('block_ore_level')->where('level',$params['level'])->find();
                            $periods = getPer($lvs['level']);
                            $sn = getOrderSn($periods,$params['orecode']);
                            $data = [
                                'periods' => $periods,
                                'ordersn' => $sn,
                                'level' => $params['level'],
                                'orecode' => $params['orecode'],
                                'pcp' => $params['price'],
                                'total_money' => $params['price'],
                                'buy_username' => $params['ap_username'],
                                'days' => $lvs['days'],
                                'per' => $lvs['per'],
                                'money' => $lvs['money'],
                                'money2' => $lvs['money2'],
                                'credit2' => 0,//$lvs['credit2'],
                                'credit4_per' => 0,//$lvs['credit4'],
                                'success_time' => time(),
                                'due_time' => strtotime(date('Y-m-d').' '.date('H:i',$lvs['stime'])),
                                'status' => 3,
                                'status4' => 1
                            ];
                            db('ore_order')->insert($data);
                            $params['ap_ordersn'] = $sn;
                        }
                    }

                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('orecode',$orecode);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }

                    //如果用户编号为真，则去判断是否有对应的等级矿机可以扣除
                    if ($params['username']) {
                        $uinfo = db('user')->where('username',$params['username'])->field('credit1,username')->find();
                        if (empty($uinfo)) {
                            $this->error('指定的用户不存在！');
                        }
                        $lvs = db('block_ore')->where('orecode',$params['orecode'])->field('level')->find();
                        $level = db('block_ore_level')->where('level',$lvs['level'])->field('money')->find();
                        if ($uinfo['credit1'] >= $level['money']) {
                            //扣除用户的矿机，按预约的算
                            setCc($uinfo['username'],'credit1',$level['money'],'后台指定用户，扣除预约'.config('site.credit1_text').'：'.$level['money']);
                        }else{
                            $this->error(__('指定用户的'.config('site.credit1_text').'不足！'));
                        }
                    }


                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $this->view->assign("row", $row);

        return $this->view->fetch();
    }


}