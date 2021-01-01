<?php
/**
 * 矿订单.
 * User: Administrator
 * Date: 2019/4/3
 * Time: 16:07
 */

namespace app\admin\controller\order;

use app\common\controller\Backend;

class Oreorder extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id,title';

    protected $model = null;

    public function _initialize(){
        parent::_initialize();
        $this->model = model('Oreorder');
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
                ->where('status2',0)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('status2',0)
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
        //测试暂时 要删除
        $row['due_time'] = date('Y-m-d H:i:s',$row['due_time']);

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

                    //修改成功之后要做一下判断，如果订单的状态为收益完成，则需要把矿的状态改为可以预约
                    if ($params['status'] == 3 && $params['status4']== 1) {
                        //改为可预约
                        $data['status2'] = 0;
                    }elseif($params['status'] == 2){
                        //改为采矿中
                        $data['status2'] = 2;
                    }elseif($params['status'] < 2 ){
                        //改为抢矿中
                        $data['status2'] = 1;
                    }

                    //测试暂时 要删除
                    $params['due_time'] = strtotime($params['due_time']);
                    //状态改变需要做一些处理
                    if ($params['status'] == 3 && $params['status4'] == 0) {
                        //如果订单的状态改为了收益完成，则转让的状态要改为待转让
                        $params['status4'] = 1;
                        //改为可预约
                        $data['status2'] = 0;
                    }elseif($params['status'] < 3){
                        $params['status4'] = 0;
                    }
                    $ishas = null;
                    if ($params['status4'] > 1) {
                        $ishas = db('ore_order')->where('sell_ordersn',$params['ordersn'])->field('id,buy_username,ordersn')->find();
                        if (empty($ishas)) {
                            $this->error(__('该'.config('site.ore_text').'还没有买家，不能修改为选择的转让状态'));
                        }
                    }

                    $result = $row->allowField(true)->save($params);

                    $data = null;
                    //如果转让状态为交易完成 4
                    if ($params['status4'] == 4){
                        //把矿的归属用户编号和归属订单编号该为买家的信息
                        $data['ap_username'] = $ishas['buy_username'];
                        $data['ap_ordersn'] = $ishas['ordersn'];
                        //改为采矿中
                        $data['status2'] = 2;
                        //修改买家订单的状态为收益中
                        db('ore_order')->where('sell_ordersn',$params['ordersn'])->where('sell_username',$params['buy_username'])->update(['status' => 2]);
                    }


                    if ($data) {
                        db('block_ore')->where('orecode',$params['orecode'])->update($data);
                    }



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