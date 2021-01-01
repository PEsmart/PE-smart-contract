<?php
/**
 * 卖单相关操作.
 * User: admin
 * Date: 2019/10/9
 * Time: 17:22
 */
namespace app\api\controller;
use app\common\controller\Api;
use think\Cookie;
use think\Db;

class Sellorder extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];
    public $type = null;
    // 分页参数
    // 一个页面显示几条数据
    protected $pagesize = 10;
    // 页码
    protected $page = 1;
    // 总页数
    protected $totalpage = 0;
    // 从第几条数据开始
    protected $index = 0;

    public function _initialize()
    {
        parent::_initialize();
        $this->type = Cookie::get('think_var');
        // 获取分页参数
        $this->page = input('get.page') ? intval(input('get.page'))
            : $this->page;
        if ($this->page <= 0) {
            $this->page = 1;
        }
        $this->index = ($this->page - 1) * $this->pagesize;
    }

    //获取订单列表
    public function getList(){
        $status = input('get.status');
        $data = null;
        if ($status == 'all') {
            $data = db('sell_order')
                ->where('uid',$this->auth->id)
                ->order('id','desc')
                ->limit($this->index,$this->pagesize)
                ->select();
        }else{
            $status = input('get.status/d',0);
            $data = db('sell_order')
                ->where('uid',$this->auth->id)
                ->where('status',$status)
                ->order('id','desc')
                ->limit($this->index,$this->pagesize)
                ->select();
        }
        $this->success('success',$data);
    }

    //获取订单详情
    public function detail(){
        $id = input('get.id/d');
        $data = db('sell_order')
            ->where('id',$id)
            ->where('uid',$this->auth->id)
            ->find();
        if ($data) {
            if ($data['buy_id']) {
                $data['buyinfo'] = db('buy_order')->where('id',$data['buy_id'])->find();
            }
        }
        $this->success('success',$data);
    }

    //自动创建卖单
    public function addOrder(){
        $redis = rds();
        //防止同一秒内点击多次
        if ($redis->get('sell_'.$this->auth->id)) {
            $redis->close();
            $this->error(__('Frequent operation'));
        }
        $redis->set('sell_'.$this->auth->id,1);
        $redis->expireAt('sell_'.$this->auth->id,60);

        $num = input('post.num/d');
        $price = round(input('post.price/f'),4);
        $paypwd = input('post.paypwd/s');
        if ($num <=0) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            //数量必须大于0
            $this->error(__('Quantity must be greater than zero'));
        }
        if ($price <= 0 || $price > config('site.jy_today_sell_maxprice')) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            //单价必须在限定的范围之内
            $this->error(__('Price restrictions'));
        }
        if (empty($paypwd)) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            //密码不能为空
            $this->error(__('paypwd'));
        }
        $pwd = db('user_detail')->where('uid',$this->auth->id)->value('paypwd');
        if ($pwd != md5(md5($paypwd).$this->auth->salt)) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            //密码错误
            $this->error(__('paypwd error'));
        }

        //获取fc转usdt的比例
        /*$per = config('site.exchange_usdt_rate');
        $key = key($per);
        if ($key > 0) {
            $per = $per[$key] / $key;
        }else{
            $this->error(__('per_error'));
        }*/

        $total_money = $num * $price;

        //获取手续费百分比
        $feebl = floatval(config('site.jy_sell_fee'));
        $fee = $num * $feebl / 100;
        $tmy = $num + $fee;

        //获取用户当前的usdt
        $info = db('user')->where('id',$this->auth->id)->field('id,username,credit1')->find();
        if ($info['credit1'] < $tmy) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            //余额不足
            $this->error(__('no money'));
        }

        Db::startTrans();
        try {
            //获取订单号
            $ordersn = getOrderSn($this->auth->id,'sell');

            //防止订单号出现重复
            while($redis->get('sellsn_'.$ordersn)){
                $ordersn = getOrderSn($this->auth->id,'sell');
            }
            $redis->set('sellsn_'.$ordersn,1);
            $redis->expireAt('sellsn_'.$ordersn,60);
            $tm = time();
            $data = [
                'ordersn' => $ordersn,
                'uid' => $this->auth->id,
                'username' => $this->auth->username,
                'num' => $num,
                'price' => $price,
                'fee' => $fee,
                'total_money' => $total_money,
                'createtime' => $tm,
                'updatetime' => $tm,
            ];
            //创建订单
            db('sell_order')->insert($data);

            setCc($this->auth->username,'credit1',-abs($tmy),'创建卖单冻结','Create a Sales Order Freeze','lockmoney');
            //增加用户的冻结金额
            db('user')->where('id',$this->auth->id)->setInc('lock_credit1',abs($tmy));

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            plog('手动创建卖单报错',$e->getMessage(),null,['username' => $this->auth->username,'num' => $num,'price' => $price,'fee' => $fee,'total_money' => $total_money]);

            $redis->del('sell_'.$this->auth->id);
            $redis->close();

            $this->error(__('error'));
        }

        $redis->del('sell_'.$this->auth->id);
        $redis->close();
        $this->success(__('success'));
    }

    //通过买单创建卖单
    public function addOrderBuy(){
        $redis = rds();
        //防止同一秒内点击多次
        if ($redis->get('sell_'.$this->auth->id)) {
            $redis->close();
            $this->error(__('Frequent operation'));
        }
        $redis->set('sell_'.$this->auth->id,1);
        $redis->expireAt('sell_'.$this->auth->id,60);

        $id = input('post.id/d');
        $paypwd = input('post.paypwd/s');
        if (!$id) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            $this->error(__('params error'));
        }
        //获取买单信息
        $buyinfo = db('buy_order')->where('id',$id)->where('uid','<>',$this->auth->id)->where('status',0)->find();
        if (empty($buyinfo)) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            $this->error(__('params error'));
        }
        if (empty($paypwd)) {
            $redis->del('buy_'.$this->auth->id);
            $redis->close();
            //密码不能为空
            $this->error(__('paypwd'));
        }
        $pwd = db('user_detail')->where('uid',$this->auth->id)->value('paypwd');
        if ($pwd != md5(md5($paypwd).$this->auth->salt)) {
            $redis->del('buy_'.$this->auth->id);
            $redis->close();
            //密码错误
            $this->error(__('paypwd error'));
        }

        $num = $buyinfo['num'];
        $price = $buyinfo['price'];
        $total_money = $buyinfo['total_money'];


        //获取手续费百分比
        $feebl = floatval(config('site.jy_sell_fee'));
        $fee = $num * $feebl / 100;
        $tmy = $num + $fee;

        //获取用户当前的usdt
        $info = db('user')->where('id',$this->auth->id)->field('id,username,credit1')->find();
        if ($info['credit1'] < $tmy) {
            $redis->del('sell_'.$this->auth->id);
            $redis->close();
            //余额不足
            $this->error(__('no money'));
        }

        Db::startTrans();
        try {
            //获取订单号
            $ordersn = getOrderSn($this->auth->id,'sell');

            //防止订单号出现重复
            while($redis->get('sellsn_'.$ordersn)){
                $ordersn = getOrderSn($this->auth->id,'sell');
            }
            $redis->set('sellsn_'.$ordersn,1);
            $redis->expireAt('sellsn_'.$ordersn,60);

            $tm = time();

            $data = [
                'ordersn' => $ordersn,
                'uid' => $this->auth->id,
                'username' => $this->auth->username,
                'num' => $num,
                'price' => $price,
                'buy_id' => $buyinfo['id'],
                'type' => 1,//记录是通过买单创建的
                'status' => 1,//进入待付款的状态
                'fee' => $fee,
                'total_money' => $total_money,
                'createtime' => $tm,
                'updatetime' => $tm,
            ];
            //创建订单
            db('sell_order')->insert($data);
            $sellid = db()->getLastInsID();

            //增加用户的冻结金额
            setCc($this->auth->username,'credit1',-abs($tmy),'创建卖单冻结','Create a Sales Order Freeze','lockmoney');
            db('user')->where('id',$this->auth->id)->setInc('lock_credit1',abs($tmy));

            //修改买单状态，进入待付款
            db('buy_order')->where('id',$buyinfo['id'])->update(['sell_id' => $sellid,'sell_username' => $this->auth->username,'sell_uid' => $this->auth->id,'sell_fee' => $fee,'status' => 1,'updatetime' => $tm,'handletime' => $tm]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            plog('通过卖单创建买单报错',$e->getMessage(),null,['username' => $this->auth->username,'num' => $num,'price' => $price,'fee' => $fee,'total_money' => $total_money,'buy_id' => $id]);

            $redis->del('sell_'.$this->auth->id);
            $redis->close();

            $this->error(__('error'));
        }
        $redis->del('sell_'.$this->auth->id);
        $redis->close();
        $this->success(__('success'));
    }

    //卖单确认付款
    public function payment(){
        $redis = rds();
        //防止同一秒内点击多次
        if ($redis->get('payment_'.$this->auth->id)) {
            $redis->close();
            $this->error(__('Frequent operation'));
        }
        $redis->set('payment_'.$this->auth->id,1);
        $redis->expireAt('payment_'.$this->auth->id,60);

        $id = input('post.id/d');
        $paypwd = input('post.paypwd/s');
        if ($id && $paypwd) {
            $pwd = db('user_detail')->where('uid',$this->auth->id)->value('paypwd');
            if ($pwd != md5(md5($paypwd).$this->auth->salt)) {
                $redis->del('payment_'.$this->auth->id);
                $redis->close();
                //密码错误
                $this->error(__('paypwd error'));
            }

            //获取卖单信息
            $info = db('sell_order')->where('uid',$this->auth->id)->where('id',$id)->where('type',1)->where('status',1)->where('buy_id','>',0)->find();
            if ($info) {
                $buyinfo = db('buy_order')->where('id',$info['buy_id'])->where('status',1)->find();
                Db::startTrans();
                try {
                    $tm = time();
                    //卖家 fc减少，usdt增加
                    $money = $info['num']+$info['fee'];
                    //fc减少
                    setCc($this->auth->username,'lock_credit1',-$money,'卖出成功，扣除锁定的'.$money.config('site.credit1_text'),'Successful purchase, deducting '.$money.' '.config('site.credit1_text').' lock-in','trade');
                    //usdt增加
                    setCc($this->auth->username,'credit2',$info['total_money'],'卖出成功，'.config('site.credit2_text').'增加'.$info['total_money'],'Successful sale, '.config('site.credit2_text').' increased by '.$info['total_money'],'trade');
                    //修改卖家订单状态
                    db('sell_order')->where('id',$info['id'])->update(['status' => 2,'updatetime' => $tm,'completetime' => $tm]);

                    //买家 fc增加 usdt减少
                    setCc($buyinfo['username'],'credit1',$buyinfo['num'],'买入成功，'.config('site.credit1_text').'增加'.$buyinfo['num'],'Successful sale, '.config('site.credit1_text').' increased by '.$buyinfo['num'],'trade');
                    setCc($buyinfo['username'],'lock_credit2',-$buyinfo['total_money'],'买入成功，扣除锁定的'.$buyinfo['total_money'].config('site.credit2_text'),'Successful purchase, deducting '.$buyinfo['total_money'].' '.config('site.credit2_text').' lock-in','trade');
                    //修改买家的订单状态
                    db('buy_order')->where('id',$buyinfo['id'])->update(['status' => 2,'updatetime' => $tm,'completetime' => $tm]);

                    Db::commit();

                } catch (\Exception $e) {
                    Db::rollback();
                    $redis->del('payment_'.$this->auth->id);
                    $redis->close();
                    $this->error(__('error'));
                }
                $redis->del('payment_'.$this->auth->id);
                $redis->close();
                $this->success(__('success'));
            }else{
                $redis->del('payment_'.$this->auth->id);
                $redis->close();
                $this->error(__('params error'));
            }
        }else{
            $redis->del('payment_'.$this->auth->id);
            $redis->close();
            $this->error(__('params error'));
        }
    }

    //取消订单
    public function abolish(){
        $redis = rds();
        //防止同一秒内点击多次
        if ($redis->get('abolishsell_'.$this->auth->id)) {
            $redis->close();
            $this->error(__('Frequent operation'));
        }
        $redis->set('abolishsell_'.$this->auth->id,1);
        $redis->expireAt('abolishsell_'.$this->auth->id,60);

        $id = input('get.id/d');
        if ($id) {
            $info = db('sell_order')->where('id',$id)->where('status',0)->where('uid',$this->auth->id)->find();
            if ($info) {
                //改为取消状态
                db('sell_order')->where('id',$id)->update(['status' => -1,'updatetime' => time()]);
                setCc($this->auth->username,'credit1',$info['num'],'取消卖单返回冻结的'.config('site.credit1_text'),'Canceling the sales order and returning to the freeze '.config('site.credit1_text'),'lockmoney');
                db('user')->where('id',$this->auth->id)->setDec('lock_credit1',$info['num']);
                $redis->del('abolishsell_'.$this->auth->id);
                $redis->close();
                $this->success(__('success'));
            }else{
                $redis->del('abolishsell_'.$this->auth->id);
                $redis->close();
                $this->error(__('params error'));
            }
        }else{
            $redis->del('abolishsell_'.$this->auth->id);
            $redis->close();
            $this->error(__('params error'));
        }
    }

    //卖单中心列表
    public function sellList(){
        $info = db('sell_order')->where('status',0)->where('uid','<>',$this->auth->id)->order('id','desc')->limit($this->index,$this->pagesize)->select();
        $this->success('success',$info);
    }

    //卖单中心买单详情
    public function selldetail(){
        $id = input('get.id/d');
        if ($id) {
            $info = db('sell_order')->where('id',$id)->where('status',0)->find();
            if ($info) {
                $this->success('success',$info);
            }else{
                $this->error(__('params error'));
            }
        }else{
            $this->error(__('params error'));
        }
    }
}