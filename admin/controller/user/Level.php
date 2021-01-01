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

use app\common\behavior\Walletapi;
/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class Level extends Backend
{
    protected $model = null;
    // 定义快速搜索的字段
    protected $searchFields = 'id';
    protected $bkey = 'levelup';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Level');
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

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    //等级的升级条件
    public function upgrade(){
        //获取等级升级的参数
        $levelup = getSys($this->bkey);

        //获取所有的会员等级
        $levels = $this->model->select();
        $levels = collection($levels)->toArray();
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            setSys($data[$this->bkey],$this->bkey);
            $this->success(__('success'));
        }
        return view(null,compact('levelup','levels'));
    }


    //资金自动归集 - USDT钱包设置
    public function guijiusdt()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //USDT归集钱包设置
            $info = [
                'addr' => $data['addr'],
                'walletType' => $data['walletType'],
                'delimitBalance' => $data['delimitBalance'],
            ];
            $result = Walletapi::collection_set($info);

            //ETH油费钱包设置
            $params = [
                'addr' => $data['oil_addr'],
            ];
            $result2 = Walletapi::gas_wallet_set($params);

            if($result['code'] == "200"){
                if ($result2['code'] == '200'){
                    $this->success("USDT归集设置成功，油费钱包设置成功");
                } else {
                    $this->error("USDT归集设置成功，油费钱包设置失败");
                }
            }else{
                if ($result2['code'] == '200'){
                    $this->error("USDT归集设置失败，油费钱包设置成功");
                } else {
                    $this->error("USDT归集设置失败，油费钱包设置失败");
                }
            }
        }
        return view(null,compact('levelup','levels','robotlv'));
    }

}