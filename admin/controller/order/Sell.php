<?php
/**
 * 收益出售.
 * User: admin
 * Date: 2019/4/12
 * Time: 14:11
 */

namespace app\admin\controller\order;

use app\common\controller\Backend;

class Sell extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id';

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Sell');
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

                    if ($params['status'] == 1 && $row['status'] == 0) {
                        // 同意申请，生成新的订单

                        //判断收益是否足够
                        $info = db('user')->where('username',$row['username'])->field('credit3')->find();
                        if ($info['credit3'] < $row['num']) {
                            $this->error('该用户的'.config('site.credit3_text').'不足');
                        }

                        //减去矿链
                        setCc($row['username'],'credit3',-$row['num'],'收益出售，减去'.config('site.credit3_text').'：'.$row['num']);

                        //获取矿编号
                        $orecode = db('block_ore')->where('id','>',0)->order('orecode desc')->field('orecode')->find();
                        if (empty($orecode)) {
                            $orecode = '1001';
                        }else{
                            $orecode = $orecode['orecode'] + 1;
                        }

                        //生成订单
                        $lvs = db('block_ore_level')->where('level',$params['level'])->find();
                        $periods = getPer($lvs['level']);
                        $sn = getOrderSn($periods,$orecode);
                        $data = [
                            'periods' => $periods,
                            'ordersn' => $sn,
                            'level' => $row['level'],
                            'orecode' => $orecode,
                            'pcp' => $row['num'],
                            'total_money' => $row['num'],
                            'buy_username' => $row['username'],
                            'days' => $lvs['days'],
                            'per' => $lvs['per'],
                            'money' => $lvs['money'],
                            'money2' => $lvs['money2'],
                            'credit2' => $lvs['credit2'],
                            'credit4_per' => $lvs['credit4'],
                            'success_time' => time(),
                            'due_time' => strtotime(date('Y-m-d').' '.date('H:i',$lvs['stime'])),
                            'status' => 3,
                            'status4' => 1
                        ];
                        db('ore_order')->insert($data);

                        //生成矿
                        $ore = [
                            'orecode' => $orecode,
                            'price' => $row['num'],
                            'level' => $row['level'],
                            'ap_username' => $row['username'],
                            'ap_ordersn' => $sn,
                            'status' => 1,
                            'status2' => 0
                        ];
                        db('block_ore')->insert($ore);
                    }

                    //只做状态的修改，其他的都不做修改
                    $params = ['status' => $params['status'],'audit_time' => time()];
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