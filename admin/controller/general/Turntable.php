<?php

namespace app\admin\controller\general;


use app\common\controller\Backend;

/**
 * 日志管理
 *
 * @icon fa fa-user
 */
class Turntable extends Backend
{
    protected $model = null;
    //奖品名称
    protected $rewards=[];
    //奖品图标
    /*protected $rewardimg=[
        'no'=>'',
        'credit1'=>'/uploads/img/kuangji.png',
        'credit2'=>'/uploads/img/mine.png',
        'credit3'=>'/uploads/img/tongkuang.png',
        'credit4'=>'/uploads/img/dog.png',
        'credit5'=>'/uploads/img/mine.png',
    ];*/
    
    public function _initialize()
    {
        parent::_initialize();
        $this->rewards = [
            'no'=>'无奖品',
            'credit1'=> config('site.credit1_text'),
            'credit2'=> config('site.credit2_text'),
            'credit3'=> config('site.credit3_text'),
            'credit4'=> config('site.credit4_text'),
            'credit5'=> config('site.credit5_text')
        ];
        $this->model = model('Turntable');
        $rewards=$this->rewards;
        $this->view->assign('rewards',$rewards);
        
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
            
            foreach($list as &$v){
                $key=$v['reward'];
                if($key=='no'){
                    
                    $v['reward']='无奖品';
                    continue;
                }
                
                $v['reward']=$this->rewards["$key"];
            }
            
            $result = array("total" => $total, "rows" => $list);
            
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
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
                    
                    if($params['reward']=='no'){
                        $params['num']='';
                    }
                    //$params['rewardimg']=$this->rewardimg[$params['reward']];
                    
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
