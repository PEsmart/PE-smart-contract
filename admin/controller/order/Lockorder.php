<?php
/**
 * 冻结订单的处理.
 * User: Administrator
 * Date: 2019/4/8
 * Time: 15:18
 */
namespace app\admin\controller\order;

use app\common\controller\Backend;
use app\common\core\Procevent;

class Lockorder extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id,title';

    protected $model = null;
    protected $db = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Oreorder');
        $this->db = db('ore_order');
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

            //重写查询条件

            $total = $this->model
                ->where('status','<=',3)
                ->where('status2',1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where('status','<=',3)
                ->where('status2',1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();


            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    //取消冻结
    public function clock(){
        $id = input('get.id/d',0);
        if ($id) {
            //查询改订单的状态，如果status不为0 则不能进行操作
            $info = $this->db->where('id',$id)->where('status',0)->field('id')->find();
            if (empty($info)) {
                $this->error(__('该订单不能进行取消冻结操作'));
            }
            //取消冻结，延时一小时，买家抢购成功，但是在规定的时间内没有付款
            $this->db->where('id',$id)->update(['status2' => 0,'status3' => 0]);
            $info = $this->db->where('id',$id)->find();
            if ($info['status'] < 2) {
                //买家 买家申诉
                $this->db->where('id',$id)->setInc('pay_etime',3600);
                //卖家修改状态
                $this->db->where('ordersn',$info['sell_ordersn'])->update(['status2' => 0,'status3' => 0]);
            }else{
                //卖家 卖家申诉
                //修改买家的状态
                $this->db->where('sell_ordersn',$info['ordersn'])->update(['status2' => 0,'status3' => 0]);
            }
            $this->success(__('操作成功'));
        }else{
            $this->error(__('参数错误'));
        }
    }

    //强制交易，把矿转给买家,这种情况是买家已经付款，但是卖家没有点击确认才能操作
    public function ctst(){
        $id = input('get.id/d',0);
        if ($id) {
            $tm = time();
            //首先查询该订单的信息
            $info = $info = $this->db->field('ordersn,orecode,buy_username,credit2,credit4,credit5')->where('status',3)->where('status2',1)->where('status3',0)->where('status4',3)->where('status5',0)->where('id',$id)->find();

            if (empty($info)) {
                $this->error(__('该订单不能进行强制交易操作'));
            }
            //进行完成交易操作
            //确认交易完成，对应的收益要发放给卖家

            //卖家确认交易完成
            $this->db->where('status',3)->where('status2',1)->where('status3',0)->where('status4',3)->where('status5',0)->where('id',$id)->update(['status4' => 4,'status2' => 0,'sell_time' => $tm]);


            //发放卖家收益
            if ($info['credit2'] > 0) {
                setCc($info['buy_username'],'credit2',$info['credit2'],config('site.ore_text').'到期，交易完成获得'.config('site.credit2_text').'：'.$info['credit2']);
            }
            if ($info['credit4'] > 0) {
                setCc($info['buy_username'],'credit4',$info['credit4'],config('site.ore_text').'到期，交易完成获得'.config('site.credit4_text').'：'.$info['credit4']);
            }
            if ($info['credit5'] > 0) {
                setCc($info['buy_username'],'credit5',$info['credit5'],config('site.ore_text').'到期，交易完成获得'.config('site.credit5_text').'：'.$info['credit5']);
            }

            // 卖家确认交易完成，修改买家的订单状态
            $this->db->where('sell_ordersn',$info['ordersn'])->update(['status'=>2,'status3' => 0,'wc_time' => $tm]);

            //修改矿的状态,同时要修改归属人和订单
            $buy_info = $this->db->where('sell_ordersn',$info['ordersn'])->field('ordersn,buy_username')->find();
            db('block_ore')->where('orecode',$info['orecode'])->update(['status2' => 2,'ap_username' => $buy_info['buy_username'],'ap_ordersn' => $buy_info['ordersn']]);

            // 这里要调用触发奖金
            $id = db('user')->field('id')->where('username',$info['buy_username'])->find();
            Procevent::dsell_event(['uid' => $id['id'],'amount' => $info['credit5'],'ordersn' => $info['ordersn']],'tgj');
            Procevent::dsell_event(['uid' => $id['id'],'amount' => $info['credit5'],'ordersn' => $info['ordersn']],'tdj');

            $this->success('操作成功');
        }else{
            $this->error(__('参数错误'));
        }
    }

    //续约，在买家没有付款的情况下执行
    public function crenew(){
        $id = input('get.id/d',0);
        if ($id) {
            //查询改订单的状态，如果status不为0 则不能进行操作
            $info = $this->db->where('id',$id)->where('status',0)->field('id,sell_ordersn')->find();
            if (empty($info)) {
                $this->error(__('该订单不能进行卖家续约操作'));
            }
            //续约，拿到卖家的信息
            $sell_info = $this->db->where('ordersn',$info['sell_ordersn'])->find();

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
            $this->db->where('ordersn',$sell_info['ordersn'])->update($data);
            //修改矿的状态为采矿中
            db('block_ore')->where('orecode',$sell_info['orecode'])->update(['status2' => 2]);
            //把买家的订单变为已失效
            $this->db->where('id',$info['id'])->update(['status' => 99,'status2' => 0]);
            $this->success(__('操作成功'));
        }else{
            $this->error(__('参数错误'));
        }
    }

    //处理上传假图的申诉
    public function jt_play(){
        $id = input('get.id/d',0);
        if ($id) {
            //查询改订单的状态，如果status不为0 则不能进行操作
            $info = $this->db->where('id',$id)->field('id,sell_ordersn')->find();

            //续约，拿到卖家的信息
            $sell_info = $this->db->where('ordersn',$info['sell_ordersn'])->find();

            //修改卖家的订单状态
            $data = [
                'status' => 2,
                'status2' => 0,
                'status3' => 0,
                'status4' => 0,
                'status5' => 0,
                'due_time' => $sell_info['due_time'] + 86400
            ];
            //续约
            $this->db->where('ordersn',$sell_info['ordersn'])->update($data);
            //修改矿的状态为采矿中
            db('block_ore')->where('orecode',$sell_info['orecode'])->update(['status2' => 2]);
            //把买家的订单变为已失效
            $this->db->where('id',$info['id'])->update(['status' => 99]);
            $this->success(__('操作成功'));
        }else{
            $this->error(__('参数错误'));
        }
    }
}