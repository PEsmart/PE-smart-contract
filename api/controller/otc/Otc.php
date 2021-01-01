<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/18
 * Time: 16:56
 */

namespace app\api\controller\otc;

use app\common\controller\Api;
use app\common\library\Auth;
use app\common\model\Config;
use think\Db;
use think\Exception;
use think\Request;
use think\Session;


class Otc extends Api
{

    protected $noNeedLogin = ['getCcParam','tradenum'];
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

        $otc_income = \config('site.otc_income');
        $otc_out = \config('site.otc_out');

        foreach ($otc_income as $k => $v) {
            $data['price']['otc_income'][] = ['key' => $k, 'name' => $v];
        }

        foreach ($otc_out as $k => $v) {
            $data['price']['otc_out'][] = ['key' => $k, 'name' => $v];
        }

        $order_state = [
            '1' => '待交易',
            '2' => '待付款',
            '3' => '待确认',
            '4' => '已完成',
            '10' => '申述中',
            '20' => '已取消',
            '30' => '已撤单',
        ];

        foreach ($order_state as $k => $v) {
            $data['order_state'][] = ['key' => $k, 'name' => $v];
        }

        $data['otc_charge'] = \config('site.otc_charge');

        $this->success('', $data);
    }

    /**
     * 获取当天成交量
    */
    public function tradenum()
    {
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end = strtotime(date('Y-m-d 23:59:59'));

        $total = \db('otc_order')
                    ->where('state', 4)
                    ->whereTime('dealtime', '>=', $start)
                    ->whereTime('dealtime', '<=', $end)
                    ->count();

        $this->success('', $total);
    }

    /**
     * 交易大厅
    */
    public function trade_hall()
    {
        $this->otc_hall();

        $type = $this->request->request('type', 0, 'intval');
        $page = $this->request->request('page', 0, 'intval');
        $page = max(1, $page);
        $pageSize = config('page_rows');

        if ($type <= 0) {
            $this->error('请选择类型');
        }

        $where = array(
            'type' => $type,
            'state' => 1,
        );

        $count = \db('otc_order')->where($where)->count();

        $orders = \db('otc_order')
            ->field('id, uuname, remain_amount, uprice, createtime, minimum')
            ->where($where)
            ->order('createtime DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();

        foreach ($orders as $key => &$val) {
            if ($val['minimum'] > $val['remain_amount']) {
                $val['minimum'] = $val['remain_amount'];
            }
        }
        unset($val);

        $this->success('', ['data' => $orders, 'page' => $page, 'totalpage' => ceil($count / $pageSize)]);
    }

    /**
     * 我的买卖单
    */
    public function trade_my()
    {
        $state = $this->request->request('state', 0, 'intval');
        $type = $this->request->request('type', 0, 'intval');
        $page = $this->request->request('page', 0, 'intval');
        $page = max(1, $page);
        $pageSize = config('page_rows');

        if (!in_array($type, [1,2])) {
            $this->error('类型有误');
        }

        $user = $this->auth->getUser();

        $username = $user['username'];

        if ($state != 7) {
            if ($type == 1) {
                $where = "((uuname='{$username}' and type=1) or (buname='{$username}' and type=2))";
            }else if ($type == 2) {
                $where = "((uuname='{$username}' and type=2) or (buname='{$username}' and type=1))";
            }
        }else{
            if ($type == 1) {
                $where = "uuname='{$username}' and type=1";
            }else if ($type == 2) {
                $where = "uuname='{$username}' and type=2";
            }
        }

        if ($state != 0 && $state != 7) {
            $where .= " and state={$state}";
        }

        $count = \db('otc_order')->where($where)->count();

        $orders = \db('otc_order')
            ->field('id, uuname, remain_amount, amount2, uprice, createtime, state, type, buname, minimum')
            ->where($where)
            ->order('createtime DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();

        foreach ($orders as $key => &$val) {
            if ($val['state'] == 1) {
                if ($val['minimum'] > $val['remain_amount']) {
                    $val['minimum'] = $val['remain_amount'];
                }
            }
        }
        unset($val);

        $this->success('', ['data' => $orders, 'page' => $page, 'totalpage' => ceil($count / $pageSize)]);
    }

    /**
     * 订单详情
     */
    public function detailOrder()
    {
        $user = $this->auth->getUser();
        $orderid = $this->request->request('orderid', 0, 'intval');

        $order = \db('otc_order')
            ->where("id={$orderid}")
            ->find();

        if (empty($order)) {
            $this->error('订单不存在');
        }

        $uuser = \db('user')
            ->alias('u')
            ->field('u.id, ud.creditid, ud.alipayact, ud.wechatact, ud.bankact, ud.alipay_url, ud.wechat_url')
            ->where("u.username='{$order['uuname']}'")
            ->join('user_detail ud', 'u.id=ud.uid')
            ->find();

        $buser = \db('user')
            ->alias('u')
            ->field('u.id, ud.creditid, ud.alipayact, ud.wechatact, ud.bankact, ud.alipay_url, ud.wechat_url')
            ->where("u.username='{$order['buname']}'")
            ->join('user_detail ud', 'u.id=ud.uid')
            ->find();

        $isowner  = $user['id'] == $uuser['id'] ? 1 : 0;

        $order['isowner'] = $isowner;
        $order['ucreditid'] = $uuser['creditid'];
        $order['ualipayact'] = $uuser['alipayact'];
        $order['uwechatact'] = $uuser['wechatact'];
        $order['ualipay_url'] = $uuser['alipay_url'];
        $order['uwechat_url'] = $uuser['wechat_url'];
        $order['ubankact'] = $uuser['bankact'];
        $order['bcreditid'] = $buser['creditid'];
        $order['balipayact'] = $buser['alipayact'];
        $order['bwechatact'] = $buser['wechatact'];
        $order['balipay_url'] = $buser['alipay_url'];
        $order['bwechat_url'] = $buser['wechat_url'];
        $order['bbankact'] = $buser['bankact'];

        if ($order['state'] == 1) {
            if ($order['minimum'] > $order['remain_amount']) {
                $order['minimum'] = $order['remain_amount'];
            }

            if ($order['totalprice'] == 0) {
                $order['totalprice'] = $order['remain_amount'] * $order['uprice'];
            }
        }



        $this->success('', $order);
    }

    /**
     * 创建买单
     * - 发布人 待交易状态
     */
    public function createBuyOrder()
    {
        $this->paybd();
        $user = $this->auth->getUser();
        $userDetail = $this->auth->getDetail();
        $otc_income = \config('site.otc_income');

        $i = 0;
        foreach ($otc_income as $key => $item) {
            $otc_income[$i] = $item;
            $i++;
        }

        if ($userDetail['isreal'] == 0) {
//            $this->error('请先实名');
        }

        $pwd = $this->request->request('pwd', '', 'trim');
        $uprice = round($this->request->request('uprice'), 2);
        $amount2 = round($this->request->request('amount2'), 2);
        $minimum = round($this->request->request('minimum'), 2);

        if ($uprice <= 0) {
            $this->error('请输入单价');
        }
        if ($amount2 <= 0) {
            $this->error('请输入数量');
        }
        if (empty($pwd)) {
            $this->error('请输入交易密码');
        }

        if ($otc_income[0] > $uprice || $otc_income[1] < $uprice) {
            $this->error('单价取值范围：' . $otc_income[0] . ' ~ ' . $otc_income[1]);
        }

        if ($minimum <= 0) {
            $this->error('请输入最低限额');
        }

        if ($minimum > $amount2) {
            $this->error('最低限额不能大于要买入的数量');
        }

        //数量、单价、总金额、支付密码、用户Id
        if ($userDetail['paypwd'] != Auth::getEncryptPassword($pwd, $user['salt'])) {
            $this->error('交易密码错误');
        }

        $totalprice = $uprice * $amount2;

        $data = [];
        $data['tradesn'] = create_order_sn('cb');
        $data['uuname'] = $user['username'];
        $data['uprice'] = $uprice;
        $data['total_amount'] = $amount2;
        $data['remain_amount'] = $amount2;
        $data['totalprice'] = $totalprice;
        $data['minimum'] = $minimum;
        $data['createtime'] = time();
        $data['type'] = 1; //1买单，2卖单
        $data['state'] = 1; //1待交易、2待付款、3待确认、4已完成、10申诉中

        try {
            \db('otc_order')->insert($data);
        } catch (\Exception $e) {
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
        $this->otc_hall();
        $this->paybd();

        $orderid = $this->request->request('orderid', 0, 'intval');
        $pwd     = $this->request->request('pwd', '', 'trim');
        $amount  = round($this->request->request('amount'), 2);

        if ($orderid <= 0) {
            $this->error('缺少参数');
        }

        if ($amount <= 0) {
            $this->error('请输入卖出数量');
        }

        if (empty($pwd)) {
            $this->error('请输入交易密码');
        }

        $order = \db('otc_order')
            ->where("id={$orderid}")
            ->where("type=1")
            ->find();

        if (empty($order)) {
            $this->error('订单不存在');
        }

        $this->checkop('otc_order_'.$orderid);

        $user = $this->auth->getUser();
        $userDetail = $this->auth->getDetail();

        if (Auth::getEncryptPassword($pwd, $user['salt']) != $userDetail['paypwd']) {
            $this->error('交易密码错误');
        }

        if ($userDetail['isreal'] == 0) {
//            $this->error('请先实名');
        }

        if ($order['state'] != 1) {
            $this->error('订单状态错误');
        }

        if ($order['uuname'] == $user['username']) {
            $this->error('不能卖出自己的订单');
        }

        $remain_amount = $order['remain_amount'];
        $minimum = $order['minimum'];

        if ($amount > $remain_amount) {
            $this->error('卖出数量大于剩余数量');
        }

        //判断最低限额
        if ($amount < $minimum && $remain_amount >= $minimum) {
            $this->error('最低限额为'.$minimum);
        }

        //判断当前剩余的最低限额
        if ($remain_amount < $minimum && $amount != $remain_amount) {
            $this->error('最低限额为'.$remain_amount);
        }

        //计算手续费
        $otc_charge = \config('site.otc_charge');
        $servicecharge = round($amount * ($otc_charge / 100), 2);
        $amount1 = round($amount + $servicecharge, 2);

        if ($user['credit2'] < $amount1) {
            $this->error('可交易'.config('site.credit2_text').'不足' . $amount1);
        }

        \db()->startTrans();
        try {
            $remain = $remain_amount - $amount;
            $price = $order['uprice'] * $amount;
            //如果把剩余的都交易了，就直接改本订单，否则就生成另外一个子订单
            if ($remain <= 0) {
                $updata = array(
                    'state' => 2,
                    'ordertime' => time(),
                    'buname' => $user['username'],
                    'amount1' => $amount1,
                    'amount2' => $amount,
                    'price' => $price,
                    'servicecharge' => $servicecharge,
                    'remain_amount' => 0,
                );

                \db('otc_order')
                    ->where("id={$orderid}")
                    ->update($updata);

                $ordersn = $order['tradesn'];

            }else{

                $data = [];
                $data['tradesn'] = create_order_sn('cb');
                $data['uuname'] = $order['uuname'];
                $data['uprice'] = $order['uprice'];
                $data['buname'] = $user['username'];
                $data['amount1'] = $amount1;
                $data['amount2'] = $amount;
                $data['price'] = $price;
                $data['createtime'] = time();
                $data['ordertime'] = time();
                $data['servicecharge'] = $servicecharge;
                $data['type'] = 1; //1买单，2卖单
                $data['state'] = 2; //1待交易、2待付款、3待确认、4已完成、10申诉中
                $data['zoid'] = $order['id'];

                \db('otc_order')->insert($data);

                $updata = array(
                    'remain_amount' => $remain,
                    'issplit' => 1,
                );

                \db('otc_order')
                    ->where("id={$orderid}")
                    ->update($updata);

                $ordersn = $data['tradesn'];
            }


            //卖家可售减少amount1，冻结添加amount1
            setCc($user['username'], 'credit2', -$amount1, "{$user['username']}选入了买单，编号为{$ordersn}，减少{$amount1}".config('site.credit2_text'));

            setCc($user['username'], 'lock_credit2', $amount1, "{$user['username']}选入了买单，编号为{$ordersn}，增加{$amount1}锁定的".config('site.credit2_text'));

            \db()->commit();
        } catch (\Exception $e) {
            \db()->rollback();
            $this->error($e->getMessage());
        }

        $this->success('提交成功');

    }


    /**
     * 创建卖单
     * - 发布人 待交易
     */
    public function createSellOrder()
    {
        $this->paybd();
        $user = $this->auth->getUser();
        $userDetail = $this->auth->getDetail();
        $otc_out = \config('site.otc_out');
        $otc_charge = \config('site.otc_charge');

        //防止多次点击
        $this->checkop('createSellOrder_'.$user['id']);

        $i = 0;
        foreach ($otc_out as $key => $item) {
            $otc_out[$i] = $item;
            $i++;
        }

        if ($userDetail['isreal'] == 0) {
//            $this->error('请先实名');
        }

        $pwd = $this->request->request('pwd', '', 'trim');
        $uprice = round($this->request->request('uprice'), 2);
        $amount1 = round($this->request->request('amount1'), 2);
        $minimum = round($this->request->request('minimum'), 2);

        if ($uprice <= 0) {
            $this->error('请输入单价');
        }
        if ($amount1 <= 0) {
            $this->error('请输入数量');
        }
        if (empty($pwd)) {
            $this->error('请输入交易密码');
        }

        if ($otc_out[0] > $uprice || $otc_out[1] < $uprice) {
            $this->error('单价取值范围：' . $otc_out[0] . ' ~ ' . $otc_out[1]);
        }

        if ($minimum <= 0) {
            $this->error('请输入最低限额');
        }

        //数量、单价、总金额、支付密码、用户Id
        if ($userDetail['paypwd'] != Auth::getEncryptPassword($pwd, $user['salt'])) {
            $this->error('交易密码错误');
        }

        if ($user['credit2'] < $amount1) {
            $this->error('可交易'.config('site.credit2_text').'不足' . $amount1);
        }

        //手续费
        $servicecharge = round($amount1 * ($otc_charge / 100), 2);

        //到账数量
        $amount2 = round($amount1 - $servicecharge, 2);

        if ($minimum > $amount2) {
            $this->error('最低限额不能大于要卖出的数量');
        }

        //总金额
        $totalprice = round($amount2 * $uprice, 2);

        $data = [];
        $data['tradesn'] = create_order_sn('cs');
        $data['uuname'] = $user['username'];
        $data['uprice'] = $uprice;
        $data['amount1_total'] = $amount1;
        $data['total_amount'] = $amount2;
        $data['remain_amount'] = $amount2;
        $data['servicecharge'] = $servicecharge;
        $data['totalprice'] = $totalprice;
        $data['minimum'] = $minimum;
        $data['createtime'] = time();
        $data['type'] = 2; //1买单，2卖单
        $data['state'] = 1; //1待交易、2待付款、3待确认、4已完成、10申诉中

        \db()->startTrans();
        try {
            \db('otc_order')->insert($data);

            setCc($user['username'], 'credit2', -$amount1, $user['username'].'发布了卖单，编号为'.$data['tradesn'].'，减少'.$amount1.config('site.credit2_text'));
            setCc($user['username'], 'lock_credit2', $amount1, $user['username'].'发布了卖单，编号为'.$data['tradesn'].'，增加'.$amount1.'锁定的'.config('site.credit2_text'));

            \db()->commit();
        } catch (\Exception $e) {
            \db()->rollback();
            $this->error('创建失败' . $e->getMessage());
        }

        $this->success('创建成功');
    }
    
    /**
     * 买入卖单
     * - 买家 待付款
     */
    public function orderSellOrder()
    {
        $this->otc_hall();
        $this->paybd();

        $orderid = $this->request->request('orderid', 0, 'intval');
        $pwd = $this->request->request('pwd', '', 'trim');
        $amount = round($this->request->request('amount'), 2);

        if ($orderid <= 0) {
            $this->error('缺少参数');
        }

        if ($amount <= 0) {
            $this->error('请输入买入数量');
        }

        if (empty($pwd)) {
            $this->error('请输入交易密码');
        }

        $order = \db('otc_order')
            ->where("id={$orderid}")
            ->where("type=2")
            ->find();

        if (empty($order)) {
            $this->error('订单不存在');
        }

        $this->checkop('otc_order_'.$orderid);

        $user = $this->auth->getUser();
        $userDetail = $this->auth->getDetail();

        if (Auth::getEncryptPassword($pwd, $user['salt']) != $userDetail['paypwd']) {
            $this->error('交易密码错误');
        }

        if ($userDetail['isreal'] == 0) {
//            $this->error('请先实名');
        }

        if ($order['state'] != 1) {
            $this->error('订单状态错误');
        }

        if ($order['uuname'] == $user['username']) {
            $this->error('不能购买自己的订单');
        }

        $remain_amount = $order['remain_amount'];
        $minimum = $order['minimum'];

        if ($amount > $remain_amount) {
            $this->error('买入数量大于剩余数量');
        }

        //判断最低限额
        if ($amount < $minimum && $remain_amount >= $minimum) {
            $this->error('最低限额为'.$minimum);
        }

        //判断当前剩余的最低限额
        if ($remain_amount < $minimum && $amount != $remain_amount) {
            $this->error('最低限额为'.$remain_amount);
        }

        \db()->startTrans();
        try {
            $remain = $remain_amount - $amount;
            $price = $order['uprice'] * $amount;

            if ($remain <= 0) {
                //需要加上手续费
                $amount1 = $amount + $order['servicecharge'];

                $updata = array(
                    'state' => 2,
                    'ordertime' => time(),
                    'buname' => $user['username'],
                    'amount1' => $amount1,
                    'amount2' => $amount,
                    'price' => $price,
                    'remain_amount' => 0,
                    'operation' => 0,
                );

                \db('otc_order')
                    ->where("id={$orderid}")
                    ->update($updata);
            }else{

                $data = [];
                $data['tradesn'] = create_order_sn('cs');
                $data['uuname'] = $order['uuname'];
                $data['buname'] = $user['username'];
                $data['uprice'] = $order['uprice'];
                $data['price'] = $price;
                $data['amount1'] = $amount;
                $data['amount2'] = $amount;
                $data['zoid'] = $order['id'];
                $data['createtime'] = time();
                $data['ordertime'] = time();
                $data['type'] = 2; //1买单，2卖单
                $data['state'] = 2; //1待交易、2待付款、3待确认、4已完成、10申诉中

                \db('otc_order')->insert($data);

                $updata = array(
                    'remain_amount' => $remain,
                    'issplit' => 1,
                );

                \db('otc_order')
                    ->where("id={$orderid}")
                    ->update($updata);
            }

            \db()->commit();
        } catch (\Exception $e) {
            \db()->rollback();
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
        $orderid = $this->request->request('orderid', 0, 'intval');
        $paytype = $this->request->request('paytype', 0, 'intval');
        $payproof = $this->request->request('payproof', '', 'trim');
        $pwd = $this->request->request('pwd', '', 'trim');

        if (!in_array($paytype, [1, 2, 3])) {
            $this->error('请选择支付类型');
        }

        if (empty($payproof)) {
            $this->error('请上传支付凭证');
        }

        $user = $this->auth->getUser();
        $userDetail = $this->auth->getDetail();

        $order = \db('otc_order')
            ->where("id={$orderid} and (uuname='{$user['username']}' or buname='{$user['username']}')")
            ->find();

        if (empty($order)) {
            $this->error('订单不存在');
        }

        if (Auth::getEncryptPassword($pwd, $user['salt']) != $userDetail['paypwd']) {
            $this->error('交易密码错误');
        }

        if ($order['state'] != 2) {
            $this->error('订单状态错误');
        }

        if ($order['type'] == 1 && $order['buname'] == $user['username']) {
            $this->error('不可支付自己的订单');
        }

        if ($order['type'] == 2 && $order['uuname'] == $user['username']) {
            $this->error('不可支付自己的订单');
        }

        try {
            \db('otc_order')
                ->where("id={$orderid}")
                ->update(['state' => 3, 'paytime' => time(), 'paytype' => $paytype, 'payproof' => $payproof]);
        } catch (\Exception $e) {
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
        $orderid = $this->request->request('orderid', 0, 'intval');
        $pwd = $this->request->request('pwd', '', 'trim');

        $user = $this->auth->getUser();
        $userDetail = $this->auth->getDetail();

        $order = \db('otc_order')
            ->where("id={$orderid} and (uuname='{$user['username']}' or buname='{$user['username']}')")
            ->find();

        if (empty($order)) {
            $this->error('订单不存在');
        }

        //防止多次点击
        $this->checkop('otc_order_'.$orderid);

        if (Auth::getEncryptPassword($pwd, $user['salt']) != $userDetail['paypwd']) {
            $this->error('交易密码错误');
        }

        if ($order['state'] != 3) {
            $this->error('订单状态错误');
        }

        if ($order['type'] == 1 && $order['uuname'] == $user['username']) {
            $this->error('买家不可操作订单确认');
        }

        if ($order['type'] == 2 && $order['buname'] == $user['username']) {
            $this->error('买家不可操作订单确认');
        }

        \db()->startTrans();
        try {
            \db('otc_order')
                ->where("id={$orderid}")
                ->update(['state' => 4, 'dealtime' => time()]);

            $amount1 = $order['amount1'];   //卖出数量
            $amount2 = $order['amount2'];   //到账数量

            $credit2_text = config('site.credit2_text');

            //如果是买单
            if ($order['type'] == 1) {

                //发起人 加币 到账数量

                setCc($order['uuname'], 'credit2', $amount2, "编号为{$order['tradesn']}的订单交易成功，{$order['uuname']}增加{$amount2}".$credit2_text);


                //卖家 减少冻结币 卖出数量

                setCc($order['buname'], 'lock_credit2', -$amount1, "编号为{$order['tradesn']}的订单交易成功，{$order['buname']}减少{$amount1}锁定的".$credit2_text);


            }else if($order['type'] == 2){  //如果是卖单

                //发起人 减少冻结币 卖出数量
                setCc($order['uuname'], 'lock_credit2', -$amount1, "编号为{$order['tradesn']}的订单交易成功，{$order['uuname']}减少{$amount1}锁定的".$credit2_text);

                //买家 加 到账数量
                setCc($order['buname'], 'credit2', $amount2, "编号为{$order['tradesn']}的订单交易成功，{$order['buname']}增加{$amount2}".$credit2_text);

            }else{
                throw new Exception("订单类型有误");
            }

            \db()->commit();
        } catch (\Exception $e) {
            \db()->rollback();
            $this->error($e->getMessage());
        }

        $this->success('确认成功');
    }

    /**
     * 申诉
     */
    public function complaint()
    {
        //买单/卖单、订单id、投诉内容
        $user = $this->auth->getUser();

        $orderid = $this->request->request('orderid', 0, 'intval');
        $contents = $this->request->request('contents', '', 'trim');
        $images = $this->request->request('images', '', 'trim');

        if (empty($contents)) {
            $this->error('内容不能为空');
        }

        $order = \db('otc_order')
            ->where("id={$orderid} and (uuname='{$user['username']}' or buname='{$user['username']}')")
            ->find();

        if (empty($order)){
            $this->error('订单不存在');
        }

        if (!in_array($order['state'], [2,3])) {
            $this->error('订单状态错误');
        }

        if ($order['uuname'] == $user['username']){
            $respondent = $order['buname'];
        } elseif ($order['buname'] == $user['username']){
            $respondent = $order['uuname'];
        }

        $data = [];
        $data['tradesn'] = $order['tradesn'];
        $data['respondent'] = $respondent;
        $data['complainant'] = $user['username'];
        $data['contents'] = $contents;
        $data['images'] = $images;
        $data['status'] = 0;
        $data['createtime'] = time();
        $data['updatetime'] = time();

        \db()->startTrans();
        try{
            \db('complaint')->insert($data);

            //修改为申诉状态
            \db('otc_order')
                ->where("id={$order['id']}")
                ->update(['state'=>10]);

            \db()->commit();
        }catch (\Exception $e){
            \db()->rollback();
            $this->error($e->getMessage());
        }
        $this->success('申诉成功');
    }

    /**
     * 撤单
     */
    public function revokeOrder()
    {

        $user = $this->auth->getUser();
        $userDetail = $this->auth->getDetail();

        $orderid = $this->request->request('orderid', 0, 'intval');
        $pwd     = $this->request->request('pwd', '', 'trim');

        $order = \db('otc_order')
            ->where("id={$orderid} and uuname='{$user['username']}'")
            ->find();

        if (empty($order)){
            $this->error('订单不存在');
        }

        $this->checkop('otc_order_'.$orderid);

        if ($order['state'] != 1) {
            $this->error('订单状态错误');
        }

        if (Auth::getEncryptPassword($pwd, $user['salt']) != $userDetail['paypwd']) {
            $this->error('交易密码错误');
        }

        $Otcorder = new  \app\admin\model\Otcorder;

        $res = $Otcorder->revokeOrder($order);

        if ($res == false){
            if ($this->model->error_ts) {
                $this->error($this->model->error_ts);
            }
            $this->error('撤单失败');
        }

        $this->success('撤单成功');
    }

    /**
     * 查看子订单
    */
    public function get_Children()
    {
        $orderid = $this->request->request('orderid', 0, 'intval');
        $state = $this->request->request('state', 0, 'intval');
        $page = $this->request->request('page', 0, 'intval');
        $page = max(1, $page);
        $pageSize = config('page_rows');

        if ($orderid <= 0) {
            $this->error('缺少参数');
        }

        $where = "zoid={$orderid}";
        if ($state != 0) {
            $where .= " and state={$state}";
        }

        $count = \db('otc_order')->where($where)->count();

        $orders = \db('otc_order')
            ->field('id, uuname, remain_amount, amount2, uprice, createtime, state, type, buname, minimum')
            ->where($where)
            ->order('createtime DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();

        $this->success('', ['data' => $orders, 'page' => $page, 'totalpage' => ceil($count / $pageSize)]);
    }

    //交易大厅开放时间
    protected function otc_hall()
    {
        $otc_hall_starttime = \config('site.otc_hall_starttime');
        $otc_hall_endtime = \config('site.otc_hall_endtime');

        $otc_hall_starttime = intval(str_replace(':', '', $otc_hall_starttime));
        $otc_hall_endtime = intval(str_replace(':', '', $otc_hall_endtime));

        $time = date('Hi');

        if ($time < $otc_hall_starttime || $time > $otc_hall_endtime) {
            $this->error('开放时间为：'.\config('site.otc_hall_starttime').' - '.\config('site.otc_hall_endtime'));
        }
    }

    /**
     *判断支付绑定信息
    */
    protected function paybd()
    {
        $id = $this->auth->id;

        $info = \db('user_detail')
            ->field('alipayact,wechatact,alipayname,wechatname,alipay_url,wechat_url,bankact,bank,bankname')
            ->where('uid',$id)
            ->find();

        $i = 0;
        if ($info['alipayact'] && $info['alipay_url']) {
            $i++;
        }
        if ($info['wechatact'] && $info['wechat_url']) {
            $i++;
        }
        if ($info['bankact']) {
            $i++;
        }

        if ($i < 2) {
            $this->error('请先绑定支付方式至少两种及以上');
        }

        return true;
    }

}