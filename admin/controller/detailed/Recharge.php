<?php
/**
 * 各个币种变动的明细记录
 * User: Administrator
 * Date: 2019/3/19
 * Time: 10:00
 */

namespace app\admin\controller\Detailed;
use app\common\controller\Backend;
use app\common\model\Config;
use app\common\model\Levelup;
class Recharge extends Backend {

    // 定义快速搜索的字段
    protected $searchFields = 'id';

    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Recharge');
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
				->with(['userDetail'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
				->with(['userDetail'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach($list as &$v){
            	if($v['paytype']==1){
					$v['paytype'] = "微信" ;
				}elseif($v['paytype']==2){
					$v['paytype'] = "支付宝" ;
				}
				$v['cointype'] =  $v['cointype']=='credit1' ? config('site.credit1_text') : "";
				$v['status'] =  $v['status']==0 ? "未审核通过" : "已审核通过";
			}
            unset($v);
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
			$sys = Config::getSetting();
			$params = $this->request->post("row/a");
			if ($params) {
				try {
					//是否采用模型验证
					if ($this->modelValidate) {
						$name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
						$validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
						$row->validate($validate);
					}
					$result = $row->allowField(true)->save($params);
					if ($result !== false) {
						//添加充值金额 触发升级
					   if($params['status']==1){
					   	   $log = \db("user_recharge_log")->where('id',$params['id'])->find();

					   	   $user =   \db('user')->where('id', $log['uid'])->find();
					   	   $money = $log['hkmoney'];
					   	   $fee_money =  $money*$sys['kj_fee'] /100;
						   $dz_money  =  $money -  $fee_money;
						   \db('user')->where('id', $log['uid'])->setInc('credit1', $dz_money);
						   \db('user')->where('id',$log['uid'])->setInc('credit1acc', $money);
						   \db("user_recharge_log")->where('id',$params['id'])->update(['dzmoney'=>$dz_money,'feemoney'=>$fee_money]);
						   $remark = "后台审核通过".config('site.credit1_text')."充值,汇款:".$money.",手续费:".$fee_money.",实际到账:". $dz_money;
						   \db('cc_detail_log')->insert(['username' =>  $user['username'],'type' => 'credit1','num' => $dz_money,'remark' =>$remark,'createtime' => time(),'updatetime' =>time()]);
						   Levelup::autolevelup($log['uid']);
						   $this->success("");
					   }
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
        if(!empty($row)){
            $row = $row->toArray();
			if($row['paytype']==1){
				$row['paytype'] = "微信" ;
			}elseif($row['paytype']==2){
				$row['paytype'] = "支付宝" ;
			}
			$row['cointype'] =  $row['cointype']=='credit1' ? config('site.credit1_text') : "";
			$row['status'] =  $row['status']==0 ? "未审核通过" : "已审核通过";
            $row['createtime'] = date('Y-m-d H:i:s',$row['createtime']);
            $row['updatetime'] = date('Y-m-d H:i:s',$row['updatetime']);
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
