<?php

namespace app\admin\controller\Complaint;
use app\common\controller\Backend;
use Endroid\QrCode\QrCode;
use think\Response;

/**
 * 投诉模块
 *
 * @icon fa fa-user
 */
class Complaint  extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id,respondent,complainant,tradesn,status';

    protected $model = null;
	public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Complaint');
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
            foreach($list as &$val){
                // $val['contents'] = unserialize($val['contents']);
                $val['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            }
            unset($val);
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
                    if ($params['status'] == 1){
                        //判断该订单的状态，修改为已处理时必须先去订单中改状态
                        $state = db('cc_order')->field('state')->where("tradesn='{$params['tradesn']}'")->find();
                        if ($state['state'] == 10){
                            $this->error("请前往订单列表修改订单状态");
                        }
                    }

                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
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

    // 详情
    public function detail($ids = null){
        $row = $this->model->get($ids);
        $row = $row->toArray();
        if(!empty($row)){
            $row = collection($row)->toArray();
            $row['createtime'] = date('Y-m-d H:i:s',$row['createtime']);
            $row['updatetime'] = date('Y-m-d H:i:s',$row['updatetime']);
            $row['status'] = $row['status'] == 0 ? __('untreated') : __('processed');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
}
