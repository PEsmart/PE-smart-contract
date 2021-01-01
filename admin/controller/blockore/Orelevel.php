<?php
/**
 * 区块矿等级
 * User: Administrator
 * Date: 2019/3/27
 * Time: 19:40
 */

namespace app\admin\controller\blockore;

use app\common\controller\Backend;
use think\Validate;

class Orelevel extends Backend
{
    protected $searchFields = 'id,levelname';
    protected $model = null;
    protected $rules = null;
    protected $message = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Orelevel');
        $this->rules = [
            'level'  => 'require|number|gt:0',
            'levelname'  => 'require',
            'min_price'  => 'require|number|gt:0',
            'max_price'  => 'require|number|gt:0',
            'stime'  => 'require|dateFormat:Y-m-d H:i:s',
            'etime'  => 'require|dateFormat:Y-m-d H:i:s',
            'money'  => 'require|number|gt:0',
            'money2'  => 'require|number|gt:0',
            'days'  => 'require|number|gt:0',
            'per'  => 'require|number|gt:0',
            'credit2'  => 'require|number|gt:0',
            'credit4'  => 'require|number|gt:0',
        ];
        $this->messages = [
            'level.require' =>  '等级不能为空',
            'level.number' =>  '等级必须为数字',
            'level.gt' =>  '等级不能小于0',
            'min_price.require' =>  '最小价格不能为空',
            'min_price.number' =>  '最小价格必须为数字',
            'min_price.gt' =>  '最小价格不能小于0',
            'max_price.require' =>  '最大价格不能为空',
            'max_price.number' =>  '最大价格必须为数字',
            'max_price.gt' =>  '最大价格不能小于0',
            'stime.require' =>  '开始时间不能为空',
            'stime.dateFormat' =>  '开始时间的时间格式不正确',
            'etime.require' =>  '结束时间不能为空',
            'etime.dateFormat' =>  '结束时间的时间格式不正确',
            'money.require' =>  '预约所需'.config('site.credit1_text').'不能为空',
            'money.number' =>  '预约所需'.config('site.credit1_text').'必须为数字',
            'money.gt' =>  '预约所需'.config('site.credit1_text').'不能小于0',
            'money2.require' =>  '非预约所需'.config('site.credit1_text').'不能为空',
            'money2.number' =>  '非预约所需'.config('site.credit1_text').'必须为数字',
            'money2.gt' =>  '非预约所需'.config('site.credit1_text').'不能小于0',
            'days.require' =>  '收益天数不能为空',
            'days.number' =>  '收益天数必须为数字',
            'days.gt' =>  '收益天数不能小于0',
            'per.require' =>  '收益百分比不能为空',
            'per.number' =>  '收益百分比必须为数字',
            'per.gt' =>  '收益百分比不能小于0',
            'credit2.require' =>  config('site.credit2_text').'不能为空',
            'credit2.number' =>  config('site.credit2_text').'必须为数字',
            'credit2.gt' =>  config('site.credit2_text').'不能小于0',
            'credit4.require' =>  config('site.credit4_text').'不能为空',
            'credit4.number' =>  config('site.credit4_text').'必须为数字',
            'credit4.gt' =>  config('site.credit4_text').'不能小于0',
        ];
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
                $val['stime_text'] = date('H:i',$val['stime']);
                $val['etime_text'] = date('H:i',$val['etime']);
            }
            unset($val);

            $result = array('total' => $total, 'rows' => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    //执行数据验证
    public function vld($params){
        //对数据进行验证
        $validate = new Validate($this->rules,$this->messages);
        $result = $validate->check($params);
        if (!$result) {
            $this->error($validate->getError());
        }
    }
    public function add(){
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //获取提交的参数
            $params = $this->request->post('row/a');
            //$params['stime'] = '12521212';

            //数据验证
            $this->vld($params);
            $ishas = $this->model->where('level',$params['level'])->find();
            if (empty($ishas)) {
                $params['stime'] = strtotime($params['stime']);
                $params['etime'] = strtotime($params['etime']);
                $this->model->insert($params);
                $this->success(__('success'));
            }else{
                $this->error(__('The grade block ore already exists'));
            }

        }
        return view();
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

                    $ishas = $this->model->where('level',$params['level'])->find();
                    if ($ishas && $params['level'] != $row['level']) {
                        $this->error(__('The grade block ore already exists'));
                    }

                    //处理时间
                    $params['stime'] = strtotime($params['stime']);
                    $params['etime'] = strtotime($params['etime']);
                    $result = $row->allowField(true)->save($params);

                    //更新等级信息的缓存
                    $lvs = db('block_ore_level')->where('status',1)->field('stime,etime,money2,level,id')->select();
                    if ($lvs) {
                        $redis = new  \Redis();
                        $redis->connect(config('redis.host'),config('redis.port'));
                        //当天结束时间
                        $expireTime = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
                        $redis->del('block_level');
                        foreach ($lvs as $val) {
                            $lstr = json_encode($val);
                            $redis->hSet('block_level','lvs'.$val['level'],$lstr);
                            //设置等级信息当天有效
                            $redis->expireAt('block_level', $expireTime);
                        }
                        $redis->close();
                    }

                    if ($result !== false) {
                        $this->success(__('success'));
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

        //处理时间
        $row['stime'] = date('Y-m-d H:i:s',$row['stime']);
        $row['etime'] = date('Y-m-d H:i:s',$row['etime']);

        $this->view->assign("row", $row);

        return $this->view->fetch();
    }
}