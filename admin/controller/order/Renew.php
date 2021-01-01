<?php
/**
 * 续约申请管理
 * User: Administrator
 * Date: 2019/4/3
 * Time: 16:07
 */

namespace app\admin\controller\order;

use app\common\controller\Backend;

class Renew extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id,title';

    protected $model = null;

    public function _initialize(){
        parent::_initialize();
        $this->model = model('Renew');
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

                    if ($params['status'] == 1) {
                        // 同意
                        //获取申请续约的订单信息
                        $sell_info = db('ore_order')->where('ordersn',$params['ordersn'])->where('status4',0)->field('id,ordersn,total_money,level,orecode,due_time,days,renew')->find();

                        if (empty($sell_info)) {
                            $this->error(__('该订单可能进入了待转让的状态，不能进行续约！'));
                        }

                        //获取系统设置的最大值
                        $ore_max = config('site.ore_max');
                        if ($sell_info['total_money'] >= $ore_max) {
                            $this->error(__('该'.config('site.ore_text').'已经达到等级的最大上限，不能续约！'));
                        }
                        //判断矿是否达到升级的条件
                        $lv = db('block_ore_level')->where('max_price','>',$sell_info['total_money'])->order('max_price asc')->find();
                        if ($lv['level'] > $sell_info['level']) {
                            //升级
                            db('block_ore')->where('level','<',$lv['level'])->where('orecode',$sell_info['orecode'])->update(['level' => $lv['level'],'price' => $sell_info['total_money']]);
                        }

                        //修改卖家的订单状态
                        $data = [
                            'renew' => $sell_info['renew'] + 1,
                            'status' => 2,
                            'status2' => 0,
                            'status3' => 0,
                            'status4' => 0,
                            'status5' => 0,
                            'due_time' => $sell_info['due_time'] + ($sell_info['days'] * 86400),
                        ];
                        //续约
                        db('ore_order')->where('ordersn',$sell_info['ordersn'])->update($data);
                        //修改矿的状态为采矿中
                        db('block_ore')->where('orecode',$sell_info['orecode'])->update(['status2' => 2]);

                        //$this->success(__('续约成功'));

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