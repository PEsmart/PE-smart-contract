<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/18
 * Time: 16:56
 */
namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Config;
use think\Db;
use think\Exception;


class Cc extends Api
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    /**
     * cc币参数（日价区间、卖出手续费、订单状态）
     */
    public function getCcParam()
    {
        $data = [];
        $param = Config::getSetting();
        foreach (json_decode($param['cc_price'], true) as $k=>$v){
            $data['cc_price'][] = ['key'=>$k, 'name'=>$v];
        }

        foreach (\config('cc_order_state') as $k=>$v){
            $data['cc_order_state'][] = ['key'=>$k, 'name'=>$v];
        }
        $data['trade_charge'] = $param['trade_charge'];

        foreach (\config('cc_type') as $k=>$v){
            $data['cc_type'][] = ['key'=>$k, 'title'=>$v['title']];
        }

        $this->success('', $data);
    }


    /**
     * 买单、卖单、交易记录、交易中心列表
     */
    public function ccOrder()
    {
        //1=>买卖，2=>交易记录，3=>交易中心
        //uid, type, state, devide=1,2,3 => uuname page
        $param = $this->request->request();
        $param['page'] = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = config('page_rows');
        if (!isset($param['uid']) || !isset($param['type']) || !isset($param['state']) || !isset($param['devide'])){
            $this->error('参数错误');
        }

        $user = \db('user_detail')
                    ->alias('ud')
                    ->field('ud.*, u.username')
                    ->join('user u', 'ud.uid=u.id')
                    ->where("ud.uid={$param['uid']}")
                    ->find();

        if ($param['devide']==1){   //买单、卖单
            $condition = "uuname='{$user['username']}' and type={$param['type']}";
            if ($param['state'] != 0){
                $condition .= " and state={$param['state']}";
            }
        }
        else if ($param['devide']==2){    //交易记录
            $condition = "(buname='{$user['username']}' or uuname='{$user['username']}')";
            if ($param['state'] != 0){
                $condition .= " and state={$param['state']}";
            }
        }
        else{  //交易中心
            $condition = "type={$param['type']} and state=1";
        }

        $count = \db('cc_order')->where($condition)->count();

        try{
            $orders = \db('cc_order')
                ->field('id, uuname, amount1, amount2, uprice, createtime, state')
                ->where($condition)
                ->limit(($param['page']-1)*$pageSize, $pageSize)
                ->select();
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $this->success('', ['data'=>$orders, 'page'=>$param['page'], 'totalpage'=>ceil($count/$pageSize)]);
    }


    /**
     * 订单详情
     */
    public function detailOrder()
    {
        $param = $this->request->request();

        $order = \db('cc_order')
                    ->where("id={$param['orderid']}")
                    ->find();

        if (empty($order)){
            $this->error('订单不存在');
        }

        $uuser = \db('user')
                    ->alias('u')
                    ->field('u.id, ud.creditid, ud.alipayact, ud.wechatact, bankact')
                    ->where("u.username='{$order['uuname']}'")
                    ->join('user_detail ud', 'u.id=ud.uid')
                    ->find();

        $buser = \db('user')
                    ->alias('u')
                    ->field('u.id, ud.creditid, ud.alipayact, ud.wechatact, bankact')
                    ->where("u.username='{$order['buname']}'")
                    ->join('user_detail ud', 'u.id=ud.uid')
                    ->find();

        $order['isowner'] = ($param['uid'] == $uuser['id']) ? 1 : 0;
        $order['ucreditid'] = $uuser['creditid'];
        $order['ualipayact'] = $uuser['alipayact'];
        $order['uwechatact'] = $uuser['wechatact'];
        $order['ubankact'] = $uuser['bankact'];
        $order['bcreditid'] = $buser['creditid'];
        $order['balipayact'] = $buser['alipayact'];
        $order['bwechatact'] = $buser['wechatact'];
        $order['bbankact'] = $buser['bankact'];

        $this->success('', $order);
    }

    /**
     * 创建买单
     * - 发布人 待交易状态
     */
    public function createBuyOrder(){
        $param = $this->request->request();
        //数量、单价、总金额、支付密码、用户Id
        $user = \db('user_detail')
            ->alias('ud')
            ->field('ud.*, u.username')
            ->join('user u', 'ud.uid=u.id')
            ->where("ud.uid={$param['uid']}")
            ->find();

        if (md5($param['pwd']) != $user['paypwd']){
            $this->error('交易密码错误');
        }

        if ($user['isreal'] == 0){
            $this->error('请先实名');
        }

        $data['tradesn'] = create_order_sn('cb');
        $data['uuname'] = $user['username'];
        $data['uprice'] = $param['uprice'];
        $data['amount2'] = $param['amount2'];
        $data['totalprice'] = $param['totalprice'];
        $data['createtime'] = time();
        $data['type'] = 1; //1买单，2卖单
        $data['state'] = 1; //1待交易、2待付款、3待确认、4已完成、10申诉中

        try{
            \db('cc_order')->insert($data);
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $this->success('创建成功');
    }


    /**
     * 卖出买单
     * - 卖家 待付款
     */
    public function orderBuyOrder()
    {
        $param = $this->request->request();

        $user = \db('user_detail')
            ->alias('ud')
            ->field('ud.*, u.username')
            ->join('user u', 'ud.uid=u.id')
            ->where("ud.uid={$param['uid']}")
            ->find();

        $order = \db('cc_order')
                    ->where("id={$param['orderid']}")
                    ->find();

        if (md5($param['pwd']) != $user['paypwd']){
            $this->error('交易密码错误');
        }

        if ($user['isreal'] == 0){
            $this->error('请先实名');
        }

        if ($order['state'] != 1){
            $this->error('订单状态错误');
        }

        if ($order['uuname'] == $user['username']){
            $this->error('不能卖出自己的订单');
        }

        if ($user['csell'] < $param['amount1']){
            $this->error('可售cc币不足'.$param['amount1']);
        }

        \db()->startTrans();
        try{

            \db('cc_order')
                ->where("id={$param['orderid']}")
                ->update(['state'=>2, 'ordertime'=>time(), 'buname'=>$user['username'], 'amount1'=>$param['amount1'], 'servicecharge'=>$param['servicecharge']]);

            //卖家可售cc币减少amount1，冻结cc币添加amount1
            \db('user_detail')
                ->where("uid={$user['uid']}")
                ->setDec('csell', $param['amount1']);

            setCc($user['username'], 'csell', -$param['amount1'], "{$user['username']}选入了买单，编号为{$order['tradesn']}，减少{$param['amount1']}可售cc币");

            \db('user_detail')
                ->where("uid={$user['uid']}")
                ->setInc('cfree', $param['amount1']);

            setCc($user['username'], 'cfree', $param['amount1'], "{$user['username']}选入了卖单，编号为{$order['tradesn']}，增加{$param['amount1']}冻结cc币");

            \db()->commit();
        }catch (\Exception $e){
            \db()->rollback();
            $this->error($e->getMessage());
        }

        $this->success('提交成功');

    }


    /**
     * 创建卖单
     * - 发布人 待交易
     */
    public function createSellOrder(){
        //交易密码、是否实名
        //amount1, amount2, uprice, servicecharge, totalprice, pwd, uid
        $param = $this->request->request();

        $user = \db('user_detail')
                    ->alias('ud')
                    ->field('ud.*, u.username')
                    ->join('user u', 'ud.uid=u.id')
                    ->where("ud.uid={$param['uid']}")
                    ->find();

        if (md5($param['pwd']) != $user['paypwd']){
            $this->error('交易密码错误');
        }

        if ($user['isreal'] == 0){
            $this->error('请先实名');
        }

        if ($user['csell'] < $param['amount1']){
            $this->error('可售cc币不足'.$param['amount1']);
        }

        $data['tradesn'] = create_order_sn('cs');
        $data['uuname'] = $user['username'];
        $data['uprice'] = $param['uprice'];
        $data['amount1'] = $param['amount1'];
        $data['amount2'] = $param['amount2'];
        $data['servicecharge'] = $param['servicecharge'];
        $data['totalprice'] = $param['totalprice'];
        $data['createtime'] = time();
        $data['type'] = 2; //1买单，2卖单
        $data['state'] = 1; //1待交易、2待付款、3待确认、4已完成、10申诉中

        \db()->startTrans();
        try{
            \db('cc_order')->insert($data);

            //卖家可售cc币减少amount1，冻结cc币添加amount1
            \db('user_detail')
                ->where("uid={$user['uid']}")
                ->setDec('csell', $data['amount1']);
            $sql1 = \db('user_detail')->getLastSql();

            setCc($user['username'], 'csell', -$data['amount1'], "{$user['username']}发布了卖单，编号为{$data['tradesn']}，减少{$data['amount1']}可售cc币");

            \db('user_detail')
                ->where("uid={$user['uid']}")
                ->setInc('cfree', $data['amount1']);

            setCc($user['username'], 'cfree', $data['amount1'], "{$user['username']}发布了卖单，编号为{$data['tradesn']}，增加{$data['amount1']}冻结cc币");

            $sql2 = \db('user_detail')->getLastSql();

            \db()->commit();
        }catch (\Exception $e){
            \db()->rollback();
            $this->error('创建失败'.$e->getMessage());
        }

        $this->success('创建成功', ['sql1'=>$sql1, 'sql2'=>$sql2]);
    }


    /**
     * 买入卖单
     * - 买家 待付款
     */
    public function orderSellOrder(){
        //交易密码、是否实名
        //pwd, uid, orderid
        $param = $this->request->request();

        $user = \db('user_detail')
                    ->alias('ud')
                    ->field('ud.*, u.username')
                    ->join('user u', 'ud.uid=u.id')
                    ->where("ud.uid={$param['uid']}")
                    ->find();

        $order = \db('cc_order')
                    ->where("id={$param['orderid']}")
                    ->find();

        if (md5($param['pwd']) != $user['paypwd']){
            $this->error('交易密码错误');
        }

        if ($user['isreal'] == 0){
            $this->error('请先实名');
        }

        if ($order['state'] != 1){
            $this->error('订单状态错误');
        }

        if ($order['uuname'] == $user['username']){
            $this->error('不能购买自己的订单');
        }

        try{
            \db('cc_order')
                ->where("id={$param['orderid']}")
                ->update(['state'=>2, 'ordertime'=>time(), 'buname'=>$user['username']]);
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $this->success('提交成功');
    }


    /**
     * 买家付款
     * - 买家 待确认
     */
    public function payOrder()
    {
        //交易密码、是否实名
        //pwd, uid, orderid
        $param = $this->request->request();

        $user = \db('user_detail')
                    ->alias('ud')
                    ->field('ud.*, u.username')
                    ->join('user u', 'ud.uid=u.id')
                    ->where("ud.uid={$param['uid']}")
                    ->find();

        $order = \db('cc_order')
                    ->where("id={$param['orderid']}")
                    ->find();

        if (md5($param['pwd']) != $user['paypwd']){
            $this->error('交易密码错误');
        }

        if ($user['isreal'] == 0){
            $this->error('请先实名');
        }

        if ($order['state'] != 2){
            $this->error('订单状态错误');
        }

        try{
            \db('cc_order')
                ->where("id={$param['orderid']}")
                ->update(['state'=>3, 'paytime'=>time(), 'paytype'=>$param['paytype'], 'payproof'=>$param['payproof']]);
        }catch (\Exception $e){
            $this->error('提交失败');
        }

        $this->success('提交成功');
    }


    /**
     * 卖家确认
     * - 买家 已确认
     */
    public function dealOrder()
    {
        //交易密码、是否实名
        //pwd, uid, orderid
        $param = $this->request->request();

        $user = \db('user_detail')
                    ->alias('ud')
                    ->field('ud.*, u.username')
                    ->join('user u', 'ud.uid=u.id')
                    ->where("ud.uid={$param['uid']}")
                    ->find();

        $order = \db('cc_order')
                    ->where("id={$param['orderid']} and (uuname='{$user['username']}' or buname='{$user['username']}')")
                    ->find();

        if (empty($order)){
            $this->error('订单号不存在');
        }

        if (md5($param['pwd']) != $user['paypwd']){
            $this->error('交易密码错误', '');
        }

        if ($user['isreal'] == 0){
            $this->error('请先实名');
        }

        if ($order['state'] != 3){
            $this->error('订单状态错误');
        }

        //买家信息
        $buyuser = \db('user_detail')
                        ->alias('ud')
                        ->field('ud.*, u.username')
                        ->join('user u', 'ud.uid=u.id')
                        ->where("u.username='{$order['buname']}'")
                        ->find();

        try{
           \db('cc_order')
                ->where("id={$param['orderid']} and uuname='{$user['username']}'")
                ->update(['state'=>4, 'dealtime'=>time()]);

           //卖家冻结cc币减少amount1，买家锁定币添加amount2
           \db('user_detail')
                ->where("uid={$user['uid']}")
                ->setDec('cfree', $order['amount1']);
//           var_dump(\db('user_detail')->getLastSql());

            setCc($user['username'], 'cfree', $order['amount1'], "编号为{$order['tradesn']}的订单交易成功，{$user['username']}减少{$order['amount1']}冻结cc币" );


            \db('user_detail')
                ->where("uid={$buyuser['uid']}")
                ->setInc('clock', $order['amount2']);
//            var_dump(\db('user_detail')->getLastSql());
            setCc($buyuser['username'], 'clock', $order['amount2'], "编号为{$order['tradesn']}的订单交易成功，{$buyuser['username']}增加{$order['amount2']}锁定cc币");

        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $this->success('确认成功');
    }

    /**
     * 申述
     */
//    public function


}