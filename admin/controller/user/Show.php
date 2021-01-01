<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/27
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
class Show extends Backend
{
    protected $model = null;
   
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Show');
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
            
            foreach ($list as &$v){
              $user = db('user')
                  ->field('username')
                  ->where('id',$v['mid'])
                  ->find();
              $v['mid']=$user['username'];
           
            }
         
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
    
                    $status=$params['status'];
                    $num=$params['num'];
                    
                    //发放抽奖券
                    if($status==1){
                        for($i=0;$i<$num;$i++){
                            $data = ['mid' => $row['mid'], 'createtime' => time(),];
                            Db::table('fa_turntable_tickets')->insert($data);
                            
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