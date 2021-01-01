<?php
/**
 * Created by PhpStorm.
 * 系统初始化管理 cjj
 * User: Administrator
 * Date: 2019/3/18
 * Time: 10:09
 */

namespace app\admin\controller\System;
use app\common\controller\Backend;

class Initialization extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Initialization');
    }

    // 系统初始化
    public function index(){
        if($this->request->isAjax()){
            $this->model->index();
            // 初始化成功
            return json(['code' => 1,'msg' => __('successful initialization')]);
        }
        $this->view->assign("app_debug", config("app_debug"));
        return $this->view->fetch();
    }

    //8分钟 获取交易结果(包括投资和提现)
    public function minutetask()
    {
        require_once ROOT_PATH.'/timertask/minutetask.php';
    }

    //10秒 在redis中结算静态收益
    public function secondtask()
    {
        require_once ROOT_PATH.'/timertask/secondtask.php';
    }

    //10分钟 收益数据入库
    public function insql(){
        require_once ROOT_PATH.'/timertask/insql.php';
    }

    //20分钟 不提款奖励
    public function uninvprize(){
        require_once ROOT_PATH.'/timertask/uninvprize.php';
    }

    //60分钟 合约质押总资金池奖励
    public function jackpot(){
        require_once ROOT_PATH.'/timertask/jackpot.php';
    }

    public function clearday(){
        $redis = rds();
        $server_name = Config('redis_header')?$_SERVER['SERVER_NAME'].'_':'';
        $key = $server_name.date('Ymd').'_sy';
        $redis->del($key);
        $this->success();
    }


}