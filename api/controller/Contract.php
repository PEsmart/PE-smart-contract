<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 20:41
 */
namespace app\api\controller;

use app\api\library\RedLock;
use app\common\controller\Api;
use app\common\library\tron\Tron;
use app\common\library\tron\Provider\HttpProvider;
use think\Exception;
use think\Log;

/**
 * 智能合约
 */
class Contract extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['asynchronous_work','test','remedy'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    protected $tron = null;

    protected $server_name = '';

    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->server_name = Config('redis_header')?$_SERVER['SERVER_NAME'].'_':'';
        $this->config = $config = Config('transaction');
        $fullNode = new HttpProvider($config['fullNode_url']);
        $solidityNode = new HttpProvider($config['solidityNode_url']);
        $eventServer = new HttpProvider($config['eventServer_url']);
        try {
            $this->tron = new Tron($fullNode, $solidityNode, $eventServer);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->error(__('internal error'));//内部错误
        }
    }

    //申请提现 异步处理模式
    public function apply_withdraw(){
        $this->checkop();
        $this->check_kc();//检测矿池状态是否处于重启中
        $data = $this->getpm();
        if (empty($data)) {
            $this->error(__('Illegal submission'));
        }
        $server_name = $this->server_name;
        $quota = ise($data,'amt');
        $uid = $this->auth->id;
        $user = \db('user')->field('tronAddr,credit2')->find($uid);
        $withdraw_address = $user['tronAddr'];
        if($quota < 0.001){
            $this->error(__('The minimum amount of withdrawal is 0.001'));//提现额度最低为0.001
        }
        if(empty($withdraw_address)){
            $this->error(__('No cashiers found'));//提现人找不到
        }

        $system = gettransaction();
        if($system['kc_balance'] < $quota && $withdraw_address != $this->config['admin_address']){
            $this->error(__('The mine pool balance is insufficient'));//矿池余额不足
        }

        $redis = $this->redis == null?rds():$this->redis;
        $rds_sy = 0;
        $where = [
            't.status' => 1,
            't.isout' => 0,
            't.type' => 0,
            'u.credit1'=>['>',0],
            'uid' => $uid
        ];
        $cangetsy = \db('user')
            ->alias('u')
            ->join('tron_order t','u.id=t.uid')
            ->where($where)
            ->sum('t.max_profit-t.profit');
        if($cangetsy > 0){//因为有可能缓存的收益比可获得的收益还多 所以判断最多可以提现多少
            $sy = $redis->hGet($server_name.'user_sy','user_'.$uid);
            if($cangetsy >= $sy){
                $rds_sy = $sy;
            }else{//如果将要获得的收益 大于 能获得的收益 则只有能获得的收益可以提现
                $rds_sy = $cangetsy;
            }
        }

        $sum_sy = $rds_sy + $user['credit2'];
        if($sum_sy < $quota){
            $this->error(__('Insufficient income'));//收益额度不足
        }

        $insert = [
            'uid' => $uid,
            'type' => 1,
            'amt' => $quota,
            'from_addr' => $withdraw_address,
            'status' => -1,
            'createtime' => time(),
        ];
        \db()->startTrans();
        try {
            $oid = \db('tron_order')->insertGetId($insert);
            //扣除矿池余额
            \db()->execute("update fa_config set value=@balance:=value-{$quota} where name='kc_balance'");
            $kc_balance = \db()->query("select @balance as balance");//确保返回的矿池余额不受并发影响
            $kc_balance[0]['balance'] = keeDecimal($kc_balance[0]['balance'],4);
            if($kc_balance[0]['balance'] <= 0) {//重启
                $redis->set('kc_restart',1);
            }

//            if($rds_sy > 0){
//                //扣除会员收益
//                $redis->hIncrByFloat('user_sy','user_'.$uid,-$rds_sy);
//
//            }
//            $sub = $rds_sy - $quota;
//            if($sub < 0){
//                \db('user')->where('id',$uid)->setDec('credit2',abs($sub));
//            }

            //允许扣到负数 在入库定时任务执行时 会把缓存收益添加到收益余额中
            \db('user')->where('id',$uid)->setDec('credit2',$quota);

            $ret = doRequest($_SERVER['SERVER_NAME'],'/api/contract/asynchronous_work',['oid'=>$oid,'kc_balance'=>$kc_balance[0]['balance']]);
            if(!$ret['code']){//如果异步调用失败
                if($kc_balance[0]['balance'] <= 0) {//重启
                    $redis->del('kc_restart');
                }
                throw new Exception($ret['message']);
            }

            \db()->commit();
        } catch (\Exception $e) {
            \db()->rollback();
            Log::error($e->getMessage());
            Log::log('insert:'.json_encode($insert));
            $this->error(__('Execution failed'));//执行失败
        }

        $this->success(__('Application successful, waiting for processing'));//申请成功，等待处理

    }

    //异步任务处理提现请求
    public function asynchronous_work(){
        set_time_limit(0);
        Log::info("开始执行合约提现交易请求");
        $oid = $_POST['oid'];
        $kc_balance = $_POST['kc_balance'];
        $server_name = $this->server_name;
//        $oid = 18;
//        $kc_balance = 1000;

        $order = \db('tron_order')->where('id',$oid)->find();
        if(empty($order)){
            Log::error("找不到该订单");
            echo "找不到该订单";
            die;
        }
        $quota = $order['amt'];
        $withdraw_address = $order['from_addr'];
        $redis = $this->redis == null?rds():$this->redis;
        $num = intval($quota*$this->config['proportion']*0.8);
        $abi = $this->config['abi'];
        $contract = $this->tron->toHex($this->config['contract_address']);//合约地址
        $admin = $this->tron->toHex($this->config['owner_address']);//操作员地址
        $withdraw_Hex = $this->tron->toHex($withdraw_address);//提现地址
        $function = 'withdraw';//合约方法名
        $params = [$withdraw_Hex,$num];
        $attempt = 20;//尝试次数
        while(true){
            try {
                $this->tron->setPrivateKey($this->config['Private_key']);//设置私钥
                $transaction = $this->tron->getTransactionBuilder()->triggerSmartContract($abi,$contract,$function,$params,1000000000,$admin);
                $signedTransaction = $this->tron->signTransaction($transaction);
                $response = $this->tron->sendRawTransaction($signedTransaction);//广播成功后才是交易发送成功
                if($response['result']){
                    break;
                }else{//如果广播失败则提现失败
                    $attempt = 0;
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $attempt--;//如果想无限次尝试 可注释这里 但可能会执行超时
            }

            if($attempt <= 0){
                //返还矿池余额 会员收益
                \db()->startTrans();
                try {
                    \db('tron_order')->where('id', $oid)->update(['status' => -2]);
                    \db('user')->where('id', $order['uid'])->setInc('credit2', $order['amt']);
                    \db('config')->where('name','kc_balance')->setInc('value',$order['amt']);
                    //如果矿池处于重启状态则恢复正常 可继续注册投资提现
                    $redis->del('kc_restart');
                    \db()->commit();
                } catch (\Exception $e) {
                    \db()->rollback();
                    Log::error($e->getMessage());
                }
                Log::error("提现失败");
                echo "提现失败";
                die;
            }
        }

        if($response['result']){
            $txid = $response['txid'];
            \db()->startTrans();
            try {
                \db('tron_order')->where('id',$oid)->update(['txid'=>$txid,'status'=>0]);
                if($kc_balance <= 0){//重启
                    //每次重启时扣除保险金注入矿池余额
                    $re_ensure = [
                        3 => 1000,
                        2 => 2000,
                        1 => 3000,
                    ];

                    $system = gettransaction();
                    if($system['kc_ensure'] > 0 && $system['kc_restart_num'] > 0 && !empty($re_ensure[$system['kc_restart_num']])){
                        //扣除保证金->扣除余额 扣除奖池->会员收益
                        \db()->execute("update fa_config set `value`=(case
                        when `name`='kc_ensure' then `value`-{$re_ensure[$system['kc_restart_num']]}
                        when `name`='kc_restart_num' then `value`-1
                        when `name`='kc_balance' then `value`+{$re_ensure[$system['kc_restart_num']]}
                        when `name`='kc_jackpot' then 0 else `value` end)");
                        if($system['kc_jackpot'] > 0){//发放奖池
//                            $ranknum = config("app_debug")?8:200;
                            $ranknum = 200;
//                            $where = [
//                                't.type' => 0,
//                                't.status' => 1,
//                                't.isout' => 0,
//                            ];
//                            $tusers = \db('tron_order')
//                                ->alias('t')
//                                ->join('user u','t.uid=u.id')
//                                ->where($where)
//                                ->group('t.uid')
//                                ->order('t.id','desc')
//                                ->limit(0,$ranknum)
//                                ->field('t.uid,u.credit1')
//                                ->select();
//                            $tusers = \db()->query("select * from (select t.id,t.uid,u.credit1 from fa_tron_order as t join fa_user u on t.uid=u.id where t.type=0 and t.status=1 and t.isout=0 group BY t.id desc,t.uid) as a group by uid order by id desc limit 0,{$ranknum}");
                            $tusers = \db()->query("select * from (select id,uid,amt from fa_tron_order  where type=0 and status=1 and isout=0 group BY id desc,uid) as a group by uid order by id desc limit 0,{$ranknum}");
                            if(!empty($tusers)){
                                $one = $system['kc_jackpot'] * 0.5;
                                $all_tz = array_sum(array_column(array_slice($tusers,1,199),'amt'));
//                                $lua = '';
                                foreach($tusers as $rank=>$tuser){
                                    //第一名分50% 其他根据投资比例分配
                                    $sy = $rank==0?$one:$one*($tuser['amt']/$all_tz);
                                    $redis->hIncrByFloat($server_name.'user_sy','user_'.$tuser['uid'],$sy);
//                                    $lua .= <<<LUA
//                                    redis.call('hIncrByFloat','{$server_name}user_sy','user_{$tuser['uid']}','{$sy}')
//LUA;
                                }
//                                if($lua) $lua .= " return true";
//                                $ret = $redis->eval($lua);
//                                if($ret === false){
//                                    Log::error("分发奖池失败");
//                                    throw new \Exception("Redis+Lua执行失败");
//                                }
                            }

                        }

                        $redis->del('kc_restart');
                    }
                }
                echo "成功";
                \db()->commit();
            } catch (\Exception $e) {
                \db()->rollback();
                Log::error($e->getMessage());
                echo "执行失败";
            }

        }

    }

    //发起提现 同步处理模式 (后面弃用了)
    public function withdraw(){
        $this->checkop();
        $this->check_kc();//检测矿池状态是否处于重启中
        $data = $this->getpm();
        if (empty($data)) {
            $this->error(__('Illegal submission'));
        }
        $server_name = $this->server_name;
        $quota = ise($data,'amt');
        $uid = $this->auth->id;
        $user = \db('user')->field('tronAddr,credit2')->find($uid);
        $withdraw_address = $user['tronAddr'];
        if($quota <= 0){
            $this->error(__('Please enter the correct amount'));
        }
        if(empty($withdraw_address)){
            $this->error(__('No cashiers found'));
        }

        $system = gettransaction();
        if($system['kc_balance'] < $quota){
            $this->error(__('The mine pool balance is insufficient'));
        }

        $redis = $this->redis == null?rds():$this->redis;
        $rds_sy = 0;
        $where = [
            't.status' => 1,
            't.isout' => 0,
            't.type' => 0,
            'u.credit1'=>['>',0],
            'uid' => $uid
        ];
        $cangetsy = \db('user')
            ->alias('u')
            ->join('tron_order t','u.id=t.uid')
            ->where($where)
            ->sum('t.max_profit-t.profit');
        if($cangetsy > 0){
            $sy = $redis->hGet($server_name.'user_sy','user_'.$uid);
            if($cangetsy >= $sy){
                $rds_sy = $sy;
            }else{//如果将要获得的收益 大于 能获得的收益 则只有能获得的收益可以提现
                $rds_sy = $cangetsy;
            }
        }

        $sum_sy = $rds_sy + $user['credit2'];
        if($sum_sy < $quota){
            $this->error(__('Insufficient income'));
        }

        $num = intval($quota*$this->config['proportion']*0.8);
        $abi = $this->config['abi'];
        $contract = $this->tron->toHex($this->config['contract_address']);//合约地址
        $admin = $this->tron->toHex($this->config['owner_address']);//管理员地址
        $withdraw_Hex = $this->tron->toHex($withdraw_address);//提现地址
        $function = 'withdraw';//合约方法名
        $params = [$withdraw_Hex,$num];
//        var_dump($num);die;
        try {
            $this->tron->setPrivateKey($this->config['Private_key']);//设置私钥
            $transaction = $this->tron->getTransactionBuilder()->triggerSmartContract($abi,$contract,$function,$params,1000000000,$admin);
            $signedTransaction = $this->tron->signTransaction($transaction);
            $response = $this->tron->sendRawTransaction($signedTransaction);//广播成功后才是交易发送成功
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->error(__('Transaction failed. Please try again later'));//交易失败，请稍后再试
        }

        if($response['result']){
            $txid = $response['txid'];
            $insert = [
                'uid' => $uid,
                'type' => 1,
                'amt' => $quota,
                'from_addr' => $withdraw_address,
                'txid' => $txid,
                'status' => 0,
                'createtime' => time(),
            ];
            //每次重启时扣除保险金注入矿池余额
            $re_ensure = [
                3 => 1000,
                2 => 2000,
                1 => 3000,
            ];
            \db()->startTrans();
            try {
                \db('tron_order')->insert($insert);
                //扣除矿池余额
                \db()->execute("update fa_config set value=@balance:=value-{$quota} where name='kc_balance'");
                $kc_balance = \db()->query("select @balance as balance");//确保返回的矿池余额不受并发影响
                if($kc_balance[0]['balance'] <= 0){//重启
                    $redis->set('kc_restart',1);
                    if($system['kc_ensure'] > 0 && $system['kc_restart_num'] > 0 && !empty($re_ensure[$system['kc_restart_num']])){
                        \db()->execute("update fa_config set `value`=(case when `name`='kc_ensure' then `value`-{$re_ensure[$system['kc_restart_num']]} when `name`='kc_restart_num' then `value`-1 when `name`='kc_balance' then `value`+{$re_ensure[$system['kc_restart_num']]} when `name`='kc_jackpot' then 0 else `value` end)");
                        if($system['kc_jackpot'] > 0){//发放奖池
                            $where = [
                                't.type' => 0,
                                't.status' => 1,
                                't.isout' => 0,
                            ];
                            $tusers = \db('tron_order')
                                ->alias('t')
                                ->join('user u','t.uid=u.id')
                                ->where($where)
                                ->group('t.uid')
                                ->order('t.id','desc')
                                ->limit(0,200)
                                ->field('t.uid,u.credit1')
                                ->select();
                            if(!empty($tusers)){
                                $one = $system['kc_jackpot'] * 0.5;
                                $all_tz = array_sum(array_column(array_slice($tusers,1,199),'credit1'));
                                $lua = '';
                                foreach($tusers as $rank=>$tuser){
                                    //第一名分50% 其他根据投资比例分配
                                    $sy = $rank==0?$one:$one*($tuser['credit1']/$all_tz);
                                    $lua .= <<<LUA
                                    redis.call('hIncrByFloat','{$server_name}user_sy','user_{$tuser['uid']}','{$sy}')
LUA;
                                }
                                if($lua) $lua .= " return true";
                                $ret = $redis->eval($lua);
                                if($ret === false){
                                    Log::error("分发奖池失败");
                                    throw new \Exception("Redis+Lua执行失败");
                                }

                            }

                        }
                        $redis->del('kc_restart');
                    }
                }
                //扣除会员收益
//                $redis->hIncrByFloat('user_sy','user_'.$uid,-$rds_sy);
//                $sub = $rds_sy - $quota;
//                if($sub < 0){
//                    \db('user')->where('id',$uid)->setDec('credit2',abs($sub));
//                }

                //允许扣到负数 在入库定时任务执行时 会把缓存收益添加到收益余额中
                \db('user')->where('id',$uid)->setDec('credit2',$quota);

                \db()->commit();
            } catch (\Exception $e) {
                \db()->rollback();
                Log::error($e->getMessage());
                Log::log('insert:'.json_encode($insert));
                $this->error(__('Execution failed'));
            }
            $this->success(__('Transaction initiated successfully, waiting for processing'));//发起交易成功，等待处理
        }else{
            $this->error(__('Contract transaction broadcast failure'));//合约交易广播失败
        }

    }

    /**
     * 投资
     */
    public function investment(){
        $this->checkop();
        $this->check_kc();//检测矿池状态是否处于重启中
        $data = $this->getpm();
        if (empty($data)) {
            $this->error(__('Illegal submission'));
        }
        $amt = ise($data,'amt');
        $txid = trim(ise($data,'txid'));
        if($amt < 0.01){
            $this->error(__('Wrong investment amount'));//投资额度有误
        }
        $uid = $this->auth->id;
        $user = \db('user')->field('tronAddr')->find($uid);
        $investment_address = $user['tronAddr'];
        if(empty($investment_address)){
            $this->error(__('Unable to get cash withdrawal address'));//无法获取到提现地址
        }
        if(empty($txid)){
            $this->error(__('Txid cannot be empty'));//txid不能为空
        }
        $tdb = \db('tron_order');
        if($tdb->where('txid',$txid)->count()){
            $this->error(__('Txid already exists'));//txid已存在
        }

        $insert = [
            'uid' => $uid,
            'type' => 0,
            'amt' => $amt,
            'max_profit' => $amt*2,
            'from_addr' => $investment_address,
            'txid' => $txid,
            'status' => 0,
            'createtime' => time(),
        ];
        \db()->startTrans();
        try {
            $tdb->insert($insert);
            \db()->commit();
        } catch (\Exception $e) {
            \db()->rollback();
            Log::error($e->getMessage());
            Log::log('insert:'.json_encode($insert));
            $this->error(__('Investment failure'));
        }
        $this->success(__('Investment submitted successfully, waiting for processing'));//投资提交成功，等待处理

    }
    
        public function remedy(){
        echo "<pre>";
//        var_dump(mb_substr(1606834398000,0,10));die;
        $txid = $this->request->request('txid');
        if(empty($txid)){
            echo "交易号不能空";
            die;
        }
        $tdb = \db('tron_order');
        if($tdb->where('txid',$txid)->count()){
            echo "该交易号已存在";
            die;
        }
//        $result = '';
//        while(empty($result)){
//            $result = \fast\Http::post('https://api.trongrid.io/wallet/gettransactionbyid',json_encode(['value'=>$txid]));
//        }
        $info = '';
        while(empty($info)){
            $info = \fast\Http::post('https://api.trongrid.io/walletsolidity/gettransactioninfobyid',json_encode(['value'=>$txid]));
        }
        $info = json_decode($info,1);
//        $result = json_decode($result,1);
//        if(!empty($result['ret'][0]['contractRet']) && $result['ret'][0]['contractRet'] == 'SUCCESS') {
        if(!empty($info['receipt']['result']) && $info['receipt']['result'] == 'SUCCESS') {
//            $addr = $this->tron->fromHex($result['raw_data']['contract'][0]['parameter']['value']['owner_address']);
            $addr = $this->tron->fromHex('41'.mb_substr($info['log'][0]['topics'][1],-40));
            $uid = \db('user')->where('tronAddr',$addr)->value('id');
            if(empty($uid)){
                echo "没找到该会员";
                die;
            }
            $amt = hexdec($info['log'][0]['data'])/$this->config['proportion'];
            $insert = [
                'uid' => $uid,
                'type' => 0,
                'amt' => $amt,
                'max_profit' => $amt*2,
                'from_addr' => $addr,
                'txid' => $txid,
                'status' => 0,
                'createtime' => mb_substr($info['blockTimeStamp'],0,10),
            ];
            $tdb->insert($insert);
            echo "成功";
            // var_dump($insert,$info);
        }else{
            echo "交易失败";
        }

    }

    //获取交易结果
    public function test(){
        $abi = $this->config['abi'];
        $contract = $this->tron->toHex($this->config['contract_address']);//合约地址
        $admin = $this->tron->toHex($this->config['admin_address']);//管理员地址
//        $contract_address = $this->tron->toHex('TCgVmSSHkAufPkZQv8xGw9VYjjsGt7qz96');//代币合约地址
//        $function = 'setTRC20addr';//合约方法名
//        $params = [$contract_address];
        $function = 'getaddr';//合约方法名
        $params = [];
//        var_dump($num);die;
//        try {
            $this->tron->setPrivateKey($this->config['Private_key']);//设置私钥
            $transaction = $this->tron->getTransactionBuilder()->triggerSmartContract($abi,$contract,$function,$params,1000000000,$admin);
//            $signedTransaction = $this->tron->signTransaction($transaction);
//            $response = $this->tron->sendRawTransaction($signedTransaction);//广播成功后才是交易发送成功
//        } catch (\Exception $e) {
//            Log::error($e->getMessage());
//            $this->error(__('Transaction failed. Please try again later'));//交易失败，请稍后再试
//        }
//        var_dump($response);
        var_dump($transaction);
    }

}