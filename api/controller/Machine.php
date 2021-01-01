<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 11:08
 */
namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Config;
use think\Db;

class Machine extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];


    private function index(){
        $uid = 37;

        $config = \app\common\model\Config::getSetting();
        $jsconfig = $config['quick_set'];
//        {"初级计算":"1","入门计算机":"1","中型计算机":"2","大型计算机":"3","超级计算机":"5"}

        //获取用户信息和推荐人的信息
        $user = db('user_detail')
            ->where("uid={$uid}")
            ->find();

        if (empty($user['tjid'])) return;

        $tjuser = db('user_detail')
            ->where("uid={$user['tjid']}")
            ->find();


        //获取用户24小时内购买的最大等级计算机以及推荐人的最大等级计算机
        $now = time();
        $last = strtotime('-1 day');

        $order = \db('machine_order')
                    ->where("uid={$user['uid']}")
                    ->where('createtime', 'between', [$last, $now])
                    ->field('machineid, createtime')
                    ->order('price desc')
                    ->limit(0, 1)
                    ->select();
        if (empty($order))  return;
        $order = $order[0];
        $machine = \db('machine')->where("id={$order['machineid']}")->find();

        //获取推荐人购买的最大等级计算机
        $tjorder = \db('machine_order')
                    ->where("uid={$user['tjid']}")
                    ->where('createtime', 'between', [$last, $now])
                    ->field('machineid, createtime')
                    ->order('price desc')
                    ->limit(0, 1)
                    ->select();
        if (empty($tjorder))  return;
        $tjorder = $tjorder[0];
        $tjmachine = \db('machine')->where("id={$tjorder['machineid']}")->find();

        //判断推荐人的计算机的有效期
        $jsdays = $jsconfig[$machine['name']];
        $jsseconds = $jsdays * 3600 * 24;
        if ($tjorder['expiretime'] - $now >= $jsseconds){    #如果有效期剩下的时间比加速时间多，正常运行
            $award = $tjmachine['nissan'] * $jsdays;

            //有效期减小
            $expiretime = $tjorder['expiretime'] - $jsseconds;
        }else{  #如果小，则只取有效期剩余时间，其他作废
            $expireday = ceil(($tjorder['expiretime'] - $now)/(3600 * 24));
            $award = $tjmachine['nissan'] * $expireday;

            //有效期修改为现在
            $expiretime = $now;
        }


    }

    /**
     * 列表
     */
    public function getList(){
        $page = $this->request->request('page');
        $page = !empty($page) ? $page : 1;
        $pageSize = config('page_rows');

        $count = \db('machine')->where("status = 'normal'")->count();

        $data = \db('machine')
                    ->where("status = 'normal'")
                    ->field('id, name, image, price, power')
                    ->limit(($page-1)*$pageSize, $pageSize)
                    ->select();

        $this->success('', ['data'=>$data, 'page'=>$page, 'totalpage'=>ceil($count/$pageSize)]);
    }


    /**
     * 详情
     */
    public function detail(){
        $id = $this->request->request('id');
        if (!$id){
            $this->error(__('Invalid parameters'));
        }

        $data = \db('machine')
                    ->where('id = '.$id)
                    ->field('id, name, image, price, power')
                    ->select();

        $this->success('', ['data'=>$data]);
    }

    /**
     * 判断用户的是否实名、可售cc币是否足够、机器是否达到最大台数
     */
    public function check()
    {
        $uid = $this->request->request('uid');
        $id = $this->request->request('id');
        $type = $this->request->request('cc_type');
        $cc = config('cc_type')[$type]['key'];

        if (!$uid || !$id){
            $this->error(__('Invalid parameters'));
        }

        if ($type!=1 && $type!=3){
            $this->error('cc币参数错误');
        }

        $user = \db('user_detail')->find($uid);
        $machine = \db('machine')->find($id);

        if (!$user || !$machine){
            $this->error(__('No results were found'));
        }

        if ($user['isreal'] == 0){  #实名
            $this->error('用户未实名');
        }

        if ($user[$cc] < $machine['price']){    #余额
            $this->error('cc币不足');
        }

        $config = Config::getSetting();
        $number = \app\common\model\User::getMachineNum($uid); ##计算机台数限制判断
        if ($number >= $config['machine_max_count']){
            $this->error('该用户已达到最大台数');
        }

        $this->success('');
    }


    /**
     * 购买
     */
    # 用户拥有的计算机最大数量数，当购买的计算机到期后，得修改用户的拥有计算机数量
    public function trade(){
        //cc_type：1可售，2冻结，3锁定
        $uid = $this->request->request('uid');
        $id = $this->request->request('id');
        $type = $this->request->request('cc_type');
        $cc = config('cc_type')[$type]['key'];
        $ccname = config('cc_type')[$type]['title'];
        $pwd = $this->request->request('pwd');

        if (!$uid || !$id){
            $this->error(__('Invalid parameters'));
        }

        if ($type!=1 && $type!=3){
            $this->error('cc币参数错误');
        }

        $user = \db('user_detail')
            ->alias('ud')
            ->field('ud.*, u.username')
            ->join('user u', 'ud.uid=u.id')
            ->where("ud.uid={$uid}")
            ->find();

        $machine = \db('machine')->find($id);

        if (!$user || !$machine){
            $this->error(__('No results were found'));
        }

        if ($user['isreal'] == 0){  #实名
            $this->error('用户未实名');
        }

        if ($user[$cc] < $machine['price']){    #余额
            $this->error('cc币不足');
        }

        $config = Config::getSetting();
        $number = \app\common\model\User::getMachineNum($uid); ##计算机台数限制判断
        if ($number >= $config['machine_max_count']){
            $this->error('该用户已达到最大台数');
        }

        //交易密码
        if ($user['paypwd'] != md5($pwd)){
            $this->error('支付密码错误');
        }

        $data = [];
        $data['ordersn'] = create_order_sn('ma');
        $data['uid'] = $uid;
        $data['machineid'] = $id;
        $data['price'] = $machine['price'];
        $data['paytype'] = $type;
        $data['createtime'] = time();
        $data['expiretime'] = strtotime('+120 days');

        Db::startTrans();
        try{
            Db::table('fa_machine_order')->insert($data);
            Db::table('fa_user_detail')
                ->where('uid', $data['uid'])
                ->setDec($cc, $data['price']);

            setCc($user['username'], $cc, $data['price'], $user['username'].'购买'.$machine['name'].'花了'.$data['price'].$ccname);

            Db::commit();
        } catch (\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('购买成功');
    }


    /**
     * 挖矿
     */
    public function produceCc()
    {
        $param = $this->request->request();

        $time = strtotime(date('Y-m-d 00:00:00')); //今日产币时间

        //判断用户今天是否已经产币
        $record = \db('user_machine_cc')
                ->where("uid={$param['uid']} and createtime={$time}")
                ->select();
        if ($record){
            $this->success('', $record);
        }

        //获取用户所有的矿机
        $machines = \db('machine_order')
            ->alias('o')
            ->field('m.id, m.name, m.nissan, o.expiretime')
            ->join('machine m', 'o.machineid=m.id')
            ->where('o.uid='.$param['uid'])
            ->select();

        if (empty($machines)) $this->success('暂无计算机');

        $cc = 0;
        foreach ($machines as $v){
            if ($v['expiretime'] > $time){
                $cc += $v['nissan'];
            }
        }

        //将产量插入到计算机
        $data = [];
        $data['uid'] = $param['uid'];
        $data['cc'] = $cc;
        $data['createtime'] = $time;
        $data['expiretime'] = $time + 3600*24;
        $data['state'] = 0; //0未领取，1领取

        \db('user_machine_cc')->insert($data);

        $this->success('', $data);
    }

    /**
     * 领取
     */
    public function pullCc()
    {
        $param = $this->request->request();

        $time = time();

        $user = \db('user')->where("id={$param['uid']}")->find();

        //根据用户id获取今日记录，然后判断state
        $record = \db('user_machine_cc')
                        ->where("uid={$param['uid']} and createtime <= {$time} and expiretime > {$time}")
                        ->find();

        if (empty($record)) $this->success('暂无可领取的cc币');
        if ($record['state'] == 1){
            $this->success('今日已经领取过');
        }

        //更新record状态为1，用户可售cc币添加
        \db()->startTrans();
        try{
            \db('user_machine_cc')
                ->where("id={$record['id']}")
                ->update(['state'=>1]);

            \db('user_detail')
                ->where("uid={$param['uid']}")
                ->setInc('csell', $record['cc']);

            setCc($user['username'], 'csell', $record['cc'], $user['username'].'领取'.$record['cc'].'可售cc币');
            \db()->commit();
        }catch (\Exception $e){
            \db()->rollback();
            $this->error($e->getMessage());
        }

        $this->success('领取成功');
    }

}