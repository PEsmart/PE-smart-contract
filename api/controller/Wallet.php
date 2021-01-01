<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/12
 * Time: 12:05
 */
namespace app\api\controller;

use app\admin\library\Auth;
use app\common\controller\Api;
use app\common\core\Get;
use app\common\core\TransL;
use app\common\library\Ems;
use app\common\model\Config;
use app\common\model\Identityup;
use app\common\model\User;
use Symfony\Component\HttpFoundation\Cookie;
use think\Db;
use app\common\behavior\Walletapi;
class Wallet extends Api
{
    protected $noNeedRight = '*';
    protected $noNeedLogin = ["recharge",'ethCallback','collection'];
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    private $stateArr = [
        '-1' => '拒绝',
        '0' => '待审批',
        '1' => '通过',
    ];



    /**
     * 提取申请（区块Mine，Doge币）
     * address number type
     */

    public function _initialize()
    {
        parent::_initialize();
        $this->type=\think\Cookie::get('think_var');
    }




    public function recharge(){

        $coinName=$this->request->request('coinName');
        $walletType=$this->request->request('walletType');
        $hash=$this->request->request('hash');

        if(is_null($hash) || $hash==""){
            $this->error("充值失败","",500);
        }

        $hashed=\db('recharge_order')->where("order_no",$hash)->where("status","1")->find();
        if ($hashed){
           $tip= Get::getLang("hash已经充值过","Hash has been recharged",$this->type);
            $this->error($tip);
        }


        if ($coinName!="USDT-ERC20"){
            $this->success("",200);
        }

        //获取配置参数
        $sys =Config::getSetting();
        // 初始化化区块链钱包
        $wallet=new \app\common\core\Wallet($sys['appId'],$sys['appSecret'],$sys['walletType']);
        //交易信息查询确认
        $result=$wallet->getTx($coinName,$hash,$sys['wallet_ip']."/api/getTx");

        if(!$result){
            $this->error("充值失败","",500);
        }


        //判断订单是否是充值过的订单,根据充值通知
        if($result && $result['code']==200){
            if($result['data']['to']!=""){
                $user=\db('user')
                    ->alias('u')
                    ->join('user_detail d','u.id=d.uid')
                    ->where('d.credit2_url',$result['data']['to'])
                    ->find();


                $amount=round($result['data']['amount'],4);
                //会员美元余额;
//                $credit2=$user['credit2']+$amount;
                $data=[
                    'order_no'=>$result['data']['hash'],
                    'uid'=>$user['id'],
//                    'wallet_type'=>$result['walletType'],
                    'coin_name'=>$result['data']['coinName'],
//                    'amount'=>$result['data']['amount'],
                    'amount'=>$amount,
                    'wallet_addr'=>$result['data']['to'],
                    'createtime'=>intval($result['data']['timestamp']/1000),
                    'status'=>1
                ];
                db()->startTrans();
                try{
                    db('recharge_order')->insert($data);
//                    db('user')->where('id',$user['id'])->update(['credit2'=>$credit2]);
                    setCc($user['username'],'credit2', +$amount, '充值'."增加".$amount."USDT;".'单号为'.$result['data']['hash'],'Recharge increase'." ".$amount.""."USDT".' single number is '." ".$result['data']['hash'],'recharge');
                    db()->commit();
                }catch (\Exception $e){
                    db()->rollback();
                    $this->error($e->getMessage());
                }
            }
        }else{

            $this->error("充值失败","",500);
        }
        $this->success("充值成功","",200);
    }









//    public function recharge(){
//
//        $coinName=$this->request->request('coinName');
//        $walletType=$this->request->request('walletType');
//        $hash=$this->request->request('hash');
//
//        if(is_null($hash) || $hash==""){
//            $this->error("充值失败","",500);
//        }
//        //获取配置参数
//        $sys =Config::getSetting();
//        // 初始化化区块链钱包
//        $wallet=new \app\common\core\Wallet($sys['appId'],$sys['appSecret'],$sys['walletType']);
//        //交易信息查询确认
//        $result=$wallet->getTx($coinName,$hash,$sys['wallet_ip']."/api/getTx");
//
//        if(!$result){
//            $this->error("充值失败","",500);
//        }
//
//        //判断订单是否是充值过的订单,根据充值通知
//        if($result && $result['code']==200){
//            if($result['data']['to']!=""){
//                $user=\db('user')
//                    ->alias('u')
//                    ->join('user_detail d','u.id=d.uid')
//                    ->where('d.cash_url',$result['data']['to'])
//                    ->find();
////                $credit2=$user['credit2']+$result['data']['amount'];
//
//                $getRate=new \app\common\core\Wallet("ss","ss","ss");
//                //忽略类的参数
//                $getRate=$getRate->get("https://api.coinbase.com/v2/prices/BTC-USD/sell");
//                $usd_to_btc_rate=round($getRate['data']['amount'],2);
//                $amount=round($usd_to_btc_rate*$result['data']['amount'],2);
//                //会员美元余额;
//                $credit2=$user['credit2']+$amount;
//                $data=[
//                    'order_no'=>$result['data']['hash'],
//                    'uid'=>$user['id'],
////                    'wallet_type'=>$result['walletType'],
//                    'coin_name'=>$result['data']['coinName'],
////                    'amount'=>$result['data']['amount'],
//                    'amount'=>$amount,
//                    'wallet_addr'=>$result['data']['to'],
//                        'createtime'=>intval($result['data']['timestamp']/1000),
//                    'status'=>1
//                ];
//                db()->startTrans();
//                try{
//                    db('recharge_order')->insert($data);
////                    db('user')->where('id',$user['id'])->update(['credit2'=>$credit2]);
//                    setCc($user['username'],'credit2', +$amount, '充值，单号为'.$result['data']['hash'],'Recharge wallet, single number is '." ".$result['data']['hash'],'充值，單號為'.$result['data']['hash']);
//                    db()->commit();
//                }catch (\Exception $e){
//                    db()->rollback();
//                    $this->error($e->getMessage());
//                }
//            }
//        }else{
//
//            $this->error("充值失败","",500);
//        }
//        $this->success("充值成功","",200);
//        }



    public function getRate(){

        $user=$this->auth->getUser();
        if(!$user){
            $this->error(__("未登录"));
        }
        //获取兑换比列
        $info = db('user')->where('id',$user['id'])->field('credit1,credit2')->find();
        $exchangeFC= array();
        foreach (config('site.exchange_fc_rate') as $k=>$v){
            $exchangeFC['key'] = $k;
            $exchangeFC['value'] = $v;
            $exchangeFC['usdt'] = $info['credit2'];
            $exchangeWallet['is_exchange']=config('site.usdt_fc');
        }
        $exchangeWallet=array();
        foreach(config('site.exchange_usdt_rate') as $k=>$v){
            $exchangeWallet['key'] = $k;
            $exchangeWallet['value'] = $v;
            $exchangeWallet['fc'] = $info['credit1'];
            $exchangeWallet['is_exchange']=config('site.usdt_fc');
        }
        $this->success((__("获取成功")), ['exhange_fc'=>$exchangeFC, 'exchange_usdt'=>$exchangeWallet]);
    }

//TODO 兑换比率、提示等
    public function exchangeRate(){
        $user=$this->auth->getUser();
        foreach (config('site.exchange_fc_rate') as $k => $v) {
            $fcRate['key'] = $k;
            $fcRate['value'] = $v;
        }
        $exchange_fc_rate=round($fcRate['value']/$fcRate['key'],4);
        $exchange_fc_fee_rate=\config('site.exchange_fc_fee_rate');


        foreach (config('site.exchange_usdt_rate') as $k => $v) {
            $ustdRate['key'] = $k;
            $ustdRate['value'] = $v;
        }


        $exchange_usdt_rate=round($ustdRate['value']/$ustdRate['key'],4);
        $exchange_usdt_fee_rate=\config('site.exchange_usdt_fee_rate');

        if ($this->type=="en"){
            $exchange_fc_tip="1USDT≈".$exchange_fc_rate."FC".","."exchange charges are"." ".$exchange_fc_fee_rate."%";
            $exchange_usdt_tip="1FC≈".$exchange_usdt_rate."USDT".","."exchange charges are"." ".$exchange_usdt_fee_rate."%";

        }else{
            $exchange_fc_tip="1USDT≈".$exchange_fc_rate."FC".","."兑换手续费为".$exchange_fc_fee_rate."%";
            $exchange_usdt_tip="1FC≈".$exchange_usdt_rate."USDT".","."兑换手续费为".$exchange_usdt_fee_rate."%";
        }

        $result=[
            'fc'=>[
                'fc'=>$user['credit1'],
                'exchange_fc_rate'=>$exchange_fc_rate,
                'exchange_fc_fee_rate'=>round($exchange_usdt_fee_rate,2),
                'tip'=>$exchange_fc_tip
            ],
            'usdt'=>[
                'usdt'=>$user['credit2'],
                'exchange_usdt_rate'=>$exchange_usdt_rate,
                'exchange_fc_usdt_rate'=>round($exchange_usdt_fee_rate,2),
                'tip'=>$exchange_usdt_tip,
            ]

        ];


        $this->result('获取成功',$result);
    }

//TODO 币种兑换
    public function exchange()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error(__("未登录"));
        }
        $type = $this->request->request('type');
        $exchange_amount = $this->request->request('amount');

        if ($exchange_amount <= 0) {
            $this->error("兑换的数额不能少于等于0");
        }


        if ($type == "fc") {
            if (config('site.usdt_fc') == "0") {
                $this->error(__("Exchange not open"));
            }
            //判断余额
            if ($exchange_amount > round($user['credit2'], 2)) {
                $this->error(__('Insufficient wallet balance'));
            }
            $rate = array();
            foreach (config('site.exchange_fc_rate') as $k => $v) {
                $rate['key'] = $k;
                $rate['value'] = $v;
            }
            db()->startTrans();
            try {
                $info = Db::name('user')->where('id', $user['id'])->lock(true)->find();
                sleep(2);
                //计算兑换所得的FC
                $exchange_fc = round($rate['value'] * ($exchange_amount / $rate['key']), 2);
                //FC、钱包变动记录

                //手续费
                $fee=config('site.exchange_fc_fee_rate')/100*$exchange_fc;

                $fc=$exchange_fc-$fee;

                setCc($user['username'], "credit2", -$exchange_amount, "兑换" . "FC" . "扣除" . $exchange_amount . "USDT", "Exchange" . " " . "FC" . " " . "Deduction" . " " . $exchange_amount . " " . "USDT", "exchange");
                setCc($user['username'], "credit1", +$fc, "兑换增加" .$fc. "FC", "Exchange to increase" . " " . $fc. " " . "FC", "exchange");



                //兑换记录
                $data = [
                    'uid' => $user["id"],
                    'type' => "fc",
                    'fc' => $exchange_fc,
                    'usdt' => $exchange_amount,
                    'fee'=>$fee,
                    'exchange_time' => time()
                ];
                db('exchange_record')->insert($data);
                Db::commit();

            } catch (\Exception $e) {
                db()->rollback();
                $this->error($e->getMessage());
            }
            Identityup::autolevelup($user['id']);
            $this->success(__('Successful Exchange'));
        }

        if ($type == "usdt") {
            //FC兑换成美元是否开启
            if (config('site.fc_usdt') == "0") {
                $this->error(__("Exchange not open"));
            }
            if ($exchange_amount > round($user['credit1'], 2)) {
                $this->error(__('Insufficient wallet balance'));
                }
                $rate = array();
                foreach (config('site.exchange_usdt_rate') as $k => $v) {
                    $rate['key'] = $k;
                    $rate['value'] = $v;
                }
                db()->startTrans();
                try {
                    $info = Db::name('user')->where('id', $user['id'])->lock(true)->find();
                    sleep(2);
                    //计算兑换所得的FC
                    $exchange_usdt = round($rate['value'] * ($exchange_amount / $rate['key']), 2);
                    //FC、钱包变动记录

                    $fee=config('site.exchange_usdt_fee_rate')/100*$exchange_usdt;

                    $usdt=$exchange_usdt-$fee;

                    //手续费
                    setCc($user['username'], "credit1", -$exchange_amount, "兑换" . "USDT" ."扣除". $exchange_amount. "FC", "Exchange" . " " . "USDT" ." ". "Deduction" . " " . $exchange_amount . " " . "FC", "exchange");
                    setCc($user['username'], "credit2", +$usdt, "兑换增加" .$usdt. "USDT", "Exchange to increase" . " " . $usdt . " " . "USDT", "exchange");
                    //兑换记录
                    $data = [
                        'uid' => $user["id"],
                        'type' => "usdt",
                        'fc' => $exchange_amount,
                        'usdt' => $exchange_usdt,
                        'exchange_time' => time(),
                        'fee'=>$fee
                    ];
                    db('exchange_record')->insert($data);
                    db()->commit();
                } catch (\Exception $e) {
                    db()->rollback();
                    $this->error($e->getMessage());
                }
            $this->success(__('Successful Exchange'));
        }

    }

//todo 获取兑换记录
        public function exchangeRecord(){
        $user=$this->auth->getUser();
        if(!$user){
            $this->error(__("未登录"));
        }
        $page=!is_null($this->request->request('page'))?$this->request->request('page'):1;
        $num=!is_null($this->request->request('num'))?$this->request->request('num'):10;
        $type=!is_null($this->request->request('type'))?$this->request->request('type'):"fc";

        $exchangeRecord=\db('exchange_record')->where('type',$type)->where('uid',$user['id'])->limit(($page-1)*$num,$num)->select();
        $allNum=\db('exchange_record')->where('type',$type)->where('uid',$user['id'])->count();
        $allPage=ceil($allNum/$num);
        foreach($exchangeRecord as $k=>&$item){
            $item['exchange_time']=date('Y-m-d H:i:s',$item['exchange_time']);
        }
        $this->success(__("查询成功"),["record"=>$exchangeRecord,'allPage'=>$allPage,'page'=>intval($page)]);
    }


    //todo 获取提现参数
    public function withdrawalConfig(){

        $user=$this->auth->getUser();
        $usdt_min=\config('site.usdt_min');
        $usdt_fee=\config('site.usdt_fee');
        $usdt_tx=\config('site.usdt_tx');
        $fc_min=\config('site.fc_min');


        $fc_fee=\config('site.fc_fee');
        $fc_tx=\config('site.fc_tx');


        $usdtMinAmountTip=Get::getLang('最低提取数量：'.$usdt_min."USDT",'Minimum withdrawal amount'." ".$usdt_min."USDT",$this->type);
        $fcMinAmountTip=Get::getLang('最低提取数量：'.$fc_min."FC",'Minimum withdrawal amount'." ".$fc_min."FC",$this->type);

        $usdtFeeTip=Get::getLang('提取手续费:(提取金额 * '.$usdt_fee."%".")USDT",'Withdrawal fee : (withdrawal amount * '.$usdt_fee."%".")USDT",$this->type);
        $fcFeeTip=Get::getLang('提取手续费:(提取金额 * '.$fc_fee."%".")FC",'Withdrawal fee : (withdrawal amount * '.$fc_fee."%".")FC",$this->type);
        $result=[
            "fc"=>[
            'fc_min'=>floatval($fc_min),
            'fc_fee'=>floatval($fc_fee)/100,
            'fc_min_tip'=>$fcMinAmountTip, 'fc_fee_tip'=>$fcFeeTip,
            'fc'=>floatval($user['credit1']),'is_can_apply'=>intval($fc_tx),
            ],
            'usdt'=>[
                "usdt_min"=>floatval($usdt_min),
                "usdt_fee"=>floatval($usdt_fee)/100,
                'usdt_min_tip'=>$usdtMinAmountTip,'usdt_fee_tip'=>$usdtFeeTip,
                "usdt"=>floatval($user['credit2']),'is_can_apply'=>intval($usdt_tx)

            ],
        ];
        $this->success('获取成功',$result);
    }

    //todo 提现申请
    public function withdrawalApply(){



        $user=$this->auth->getUser();
        if(!$user){
            $this->error(__("未登录"));
        }

        $type=trim($this->request->request('type'));
        $amount=$this->request->request('amount');//提取金额，整数
//        $addr=$this->request->request('addr');//提取地址
        $captcha=$this->request->request('captcha');//邮箱验证码
//        $real_amount=$this->request->request('real_amount');//扣除手续费用后，实际提取金额
//        $btc=$this->request->request('btc');//整数

        $userDetail=\db('user_detail')->where('uid',$user['id'])->find();
        $addr=$userDetail['cash_url'];
//        var_dump($addr);
//        die;
        $switch=config('site.'.$type."_tx");

        if ($switch=="0"){
            $this->error(__("Discount not turned on"));
        }

        if(is_null($amount) || $amount<0){
            $this->error(__("The amount of application must not be less than or equal to 0"));
        }
        if(is_null($addr)||$addr==""){

            $msg=Get::getLang("未设置提现地址","Withdrawal address not set.",$this->type);
            $this->error($msg);
        }

        $check = Ems::check($user['email'], $captcha, 'apply');
//        $check=true;
        if(!$check) {
            $this->error(__('Captcha is incorrect'));
        }
        //提现最小金额
        if ($amount<config("site.".$type."_min")){
            $this->error(__('The amount of application does not meet the requirements'));
        }
        //手续费比例
        $apply_charge=config("site.".$type."_fee")/100;

        $arr=["fc"=>"credit1","usdt"=>"credit2"];

        if($amount>round($user[$arr[$type]],2)){
            $this->error(__('Insufficient wallet balance'));
        }
        if ($type=="fc"){
            $rate = array();
            foreach (config('site.exchange_usdt_rate') as $k => $v) {
                $rate['key'] = $k;
                $rate['value'] = $v;
            }
            $exchange_usdt = round($rate['value'] * (($amount-$amount*$apply_charge) / $rate['key']), 4);

        }else{
            $exchange_usdt=$amount-$amount*$apply_charge;
        }
        db()->startTrans();
        try{
            $info=Db::name('user')->where('id',$user['id'])->lock(true)->find();
            sleep(1);

            setCc($user['username'], $arr[$type], -$amount,"提现扣除".$amount.strtoupper($type),"Withdrawal deduction".' '.$amount.' '.strtoupper($type),"withdraw");
            //兑换记录
            $data=[
                'uid'=>$user["id"],
                'type'=>$type,
                'amount'=>$amount,
                'addr'=>$addr,
                'service_charge'=>$amount*$apply_charge,
                'real_amount'=>round($amount-$amount*$apply_charge,4 ),
                'usdt'=>$exchange_usdt,
                'status'=>0,
                'createtime'=>time(),
//                'btc'=>$btc
            ];

            db('cash_withdrawal')->insert($data);
            db()->commit();
        }catch (\Exception $e){
            db()->rollback();
            $this->error($e->getMessage());
        }

        $this->success(__("Successful application for cash withdrawal"));
    }




//    public  function  getApplyConfig(){
//
//        $user=$this->auth->getUser();
//        if(!$user){
//            $this->error(__("未登录"));
//        }
//
//        $user_detail=db('user_detail')->where('uid',$user['id'])->find();
//        $data['credit2']=round($user['credit2'],2);
//        $sys=Config::getSetting();
//        $data['min_apply']=round(($sys['min_apply']),2);
//        $data['apply_charge']=$sys['apply_charge']/100;
//        $data['cash_url']=$user_detail['cash_url'];
//        $sum=0;
//        $data['apply_data']=[];
//        foreach (config('site.apply_data') as $k=>$v){
//            $sum=$sum+$v;
//            array_push($data['apply_data'],intval($v));
//        }
//
//        //当sum为0，默认每一天都可以提现
//        //is_apply 0今天不可以提现，1今天可以提现
//        if($sum!=0){
//            if(!in_array(intval(date('d', time())),$data)){
//                $is_apply=0;
//            }else{
//                $is_apply=1;
//            }
//        }else{
//            $is_apply=1;
//        }
//        $data['apply_cycle']=intval($sys['apply_cycle']);
//        $data['is_apply']=$is_apply;
//
//        $getRate=new \app\common\core\Wallet("ss","ss","ss");//忽略类的参数
//
//  /*      $news=\app\common\core\Wallet::get('https://data.block.cc/api/v1/price?symbol_name=bitcoin,ethereum,bitcoin-cash');
//        $data['usd_to_btc_rate']=$news['data'][0]['price'];*/
//        $getRate=$getRate->get("https://api.coinbase.com/v2/prices/BTC-USD/sell");
//        $data['usd_to_btc_rate']=round($getRate['data']['amount'],2);
//        $this->success(__("获取成功"),$data);
//    }

//    public  function  withdrawalApply(){
//
//        $user=$this->auth->getUser();
//        if(!$user){
//            $this->error(__("未登录"));
//        }
//        $amount=$this->request->request('amount');//提取金额，整数
//        $addr=$this->request->request('addr');//提取地址
//        $captcha=$this->request->request('captcha');//邮箱验证码
//        $real_amount=$this->request->request('real_amount');//扣除手续费用后，实际提取金额
//        $btc=$this->request->request('btc');//整数
//
//        if(is_null($amount) || $amount<0){
//            $this->error(__("The amount of application must not be less than or equal to 0"));
//          }
//
//        if(is_null($addr)||$addr==""){
//
//            $this->error(__('Please enter the wallet address'));
//        }
//
//        $check = Ems::check($user['email'], $captcha, 'apply');
//        $check=true;
//        if(!$check) {
//            $this->error(__('Captcha is incorrect'));
//        }
//
//        $sys=Config::getSetting();
//        //最小提现金额
//        if($amount<$sys['min_apply']){
//            $this->error(__('The amount of application does not meet the requirements'));
//        }
//
//        $data=[];
//        $sum=0;
//        foreach (config('site.apply_data') as $k=>$v){
//            $sum=$sum+$v;
//            array_push($data,intval($v));
//        }
//        if($sum!=0){
//            if(!in_array(intval(date('d', time())),$data)){
//                $this->error(__('Today is not the present day'));
//            }
//        }
//
//        // 获取btc行情比例
//        /*      $news=\app\common\core\Wallet::get('https://data.block.cc/api/v1/price?symbol_name=bitcoin,ethereum,bitcoin-cash');
//        $data['usd_to_btc_rate']=$news['data'][0]['price'];*/
//        $getRate=new \app\common\core\Wallet("ss","ss","ss");//忽略类的参数
//        $getRate=$getRate->get("https://api.coinbase.com/v2/prices/BTC-USD/sell");
//
//        $btcRate=round($getRate['data']['amount'],2);
//        $apply_charge=$sys['apply_charge']/100;
//
//        if($amount>round($user['credit2'],2)){
//            $this->error(__('Insufficient wallet balance'));
//        }
//
//        //防止调用改参数，对比与前端传来的参数
//        if(intval($amount-$amount*$apply_charge)!=intval($real_amount)){
//            $this->error(__("实际金额应为".intval($amount-$amount*$apply_charge)));
//        }
//
//        if(intval(($amount-$amount*$apply_charge)/$btcRate)!=intval($btc)){
//            $btc=intval(($amount-$amount*$apply_charge)/$btcRate);
//            $this->error(__("可转为比特币应为".$btc."个"));
//        }
//
//        db()->startTrans();
//        try{
//
//            $info=Db::name('user')->where('id',$user['id'])->lock(true)->find();
//            sleep(1);
//            setCc($user['username'], "credit2", -$amount,"提现扣除".$amount."充值钱包/美元","Withdrawal deduction".' '.$amount.' '."Recharge Wallet/Dollar","提現扣除".$amount."充值錢包/美元");
//            //兑换记录
//            $data=[
//                'uid'=>$user["id"],
//                'amount'=>intval($amount),
//                'addr'=>$addr,
//                'service_charge'=>$amount*$apply_charge,
//                'real_amount'=>intval($amount-$amount*$apply_charge),
//                'status'=>0,
//                'createtime'=>time(),
//                'btc'=>$btc
//            ];
//
//            db('cash_withdrawal')->insert($data);
//            db()->commit();
//        }catch (\Exception $e){
//            db()->rollback();
//            $this->error($e->getMessage());
//        }
//
//        $this->success(__("Successful application for cash withdrawal"));
//    }

    public  function  getWithdrawalRecord(){
        $user=$this->auth->getUser();
        if(!$user){
            $this->error(__("未登录"));
        }
        $page=!is_null($this->request->request('page'))?$this->request->request('page'):1;
        $num=!is_null($this->request->request('num'))?$this->request->request('num'):10;

        $records=db('cash_withdrawal')->where('uid',$user['id'])->order("createtime","desc")->field('id,amount,addr,createtime,status,type')->limit(($page-1)*$num,$num)->select();
        $allNum=\db('cash_withdrawal')->where('uid',$user['id'])->count();

        foreach($records as $k=>&$record){
            $record['createtime']=date('Y-m-d H:i:s',$record['createtime']);
            $record['amount']=number_format($record['amount']);
            $record['type']=strtoupper($record['type']);
            if($record['status']==-1){
                $record['status_name']=Get::getLang("拒绝","Refuse",$this->type);
            }
            if($record['status']==0){
                $record['status_name']=Get::getLang("审核中","Under Review",$this->type);
            }
            if($record['status']==1){
                $record['status_name']=Get::getLang("已通过","Passed",$this->type);
            }

        }
        $allPage=ceil($allNum/$num);
        $this->success(__("查询成功"),['records'=>$records,"allPage"=>$allPage,'page'=>intval($page)]);
    }






    public  function getRechargeRecord(){
        $user=$this->auth->getUser();
        if(!$user){
            $this->error(__("未登录"));
        }
        $page=!is_null($this->request->request('page'))?$this->request->request('page'):1;
        $num=!is_null($this->request->request('num'))?$this->request->request('num'):10;

        $records=db('recharge_order')->where('uid',$user['id'])->field('id,amount,wallet_addr,createtime,status')->limit(($page-1)*$num,$num)->select();


        $allNum=\db('recharge_order')->where('uid',$user['id'])->count();
        foreach($records as $k=>&$record){
            if($record['status']==1){

                $status_name="已完成";
                if($this->type=="en"){
                    $status_name="Completed";
                }
                if($this->type=="zh-tw"){
                    $status_name="Completed";
                }
            }else{

                $status_name="订单失效";
                if($this->type=="en"){
                    $status_name="Order invalid";
                }
                if($this->type=="zh-tw"){
                    $status_name="訂單實現";
                }
            }

            $record['status_name']=$status_name;
            $record['createtime']=date('Y-m-d H:i:s',$record['createtime']);
        }


        $this->success(__("查询成功"),["record"=>$records,'allPage'=>ceil($allNum/$num),'page'=>intval($page)]);
    }


    public function  test(){

        $array = array("0"=>"a",'1'=>"a","2"=>"v","3"=>"b","4"=>"b",'5'=>"b");

        $keyarr =[];
        $resultkey = [];
        $kv=[];
        $diff=[];
foreach ($array as $k => $v) {
    if (in_array($v, $keyarr)){
        //在数组中搜索键值$v，并返回它的键
            $resultkey[] = array_search($v,$diff);
        $resultkey[] = $k;
        var_dump($resultkey);
//        var_dump($k);
    }else{
        $kv[]=$k;
        $keyarr[] = $v;
        for($i=0;$i<count($keyarr);$i++){
            $diff[$kv[$i]]=$keyarr[$i];
            var_dump($diff);
        }
    }
}
        var_dump($resultkey);

       $resultkey=array_unique($resultkey);
        var_dump(implode(",",$resultkey));
    }

    public  function userInit()
    {
        $user=$this->auth->getUser();
        if(!$user){
            $this->error(__("未登录"));
        }
        $sys =\app\common\model\Config::getSetting();
        // 初始化化区块链钱包
        $wallet=new \app\common\core\Wallet($sys['appId'],$sys['appSecret'],$sys['walletType']);
        $wallet=$wallet->init($user['id'],$sys['wallet_ip']."/api/init");
        if(!$wallet){
            $this->error("区块链钱包链接失败");
        }
        if($wallet && $wallet['code']==200){
            if($wallet['data']['eth'] && $wallet['data']['eth']!=""){
                $walletAddr=Db::name('user_detail')->where('uid',$user['id'])->update(['credit2_url'=>$wallet['data']['eth']]);

                echo "钱包地址:".$wallet['data']['eth'];
            }else{
                echo "初始化钱包失败";
            }
        }else{

            echo "初始化钱包失败";

        }
    }


    public  function  allInit(){

        $user=db('user')->select();
        foreach($user as $item){
            $sys =\app\common\model\Config::getSetting();
            // 初始化化区块链钱包
            $wallet=new \app\common\core\Wallet($sys['appId'],$sys['appSecret'],$sys['walletType']);
            if(!$wallet){
                $this->error("区块链钱包链接失败");
            }
            $wallet=$wallet->init($item['id'],$sys['wallet_ip']."/api/init");
            if($wallet && $wallet['code']==200){
                if($wallet['data']['eth'] && $wallet['data']['eth']!=""){
                    $walletAddr=Db::name('user_detail')->where('uid',$item['id'])->update(['credit2_url'=>$wallet['data']['eth']]);
                    echo $item['email']."钱包地址:".$wallet['data']['eth']."<br>";

                }else{
                    echo $item['email']."初始化钱包失败"."<br>";

                }
            }
        }
    }

    public function ems(){

        $aa=db('ems')->order('id','desc')->select();
        var_dump($aa);
    }
    public function user(){
        $aa=db('user')->where('email',"939585775@qq.com")->update(array('email'=>"11111@qq.com"));
        var_dump("成功");
    }

    public function  notice(){
    $user=$this->auth->getUser();
    $sys =\app\common\model\Config::getSetting();
    $wallet=new \app\common\core\Wallet($sys['appId'],$sys['appSecret'],$sys['walletType']);
    $wallet=$wallet->getTx('BTC','67e08bddaeb59ed1c928aebd2cfdab91aa332f3ec72b901ef13cc2d6705081f3',$sys['wallet_ip']."/api/getTx");
        var_dump($wallet);
    }

    public function aa(){

        $sys =\app\common\model\Config::getSetting();

        $wallet=new \app\common\core\Wallet($sys['appId'],$sys['appSecret'],$sys['walletType']);

//        $allAmount=$wallet->allAmount($sys['wallet_ip']."/api/btc/allAmount");
//
//        var_dump($allAmount);
//        echo sprintf("%.2e", $allAmount['data']);
//        $getRate=new \app\common\core\Wallet("ss","ss","ss");//忽略类的参数
//        $getRate=$getRate->get("https://api.coinbase.com/v2/prices/BTC-USD/sell");
//        $data['usd_to_btc_rate']=round($getRate['data']['amount'],2);
//        var_dump($data['usd_to_btc_rate']);


        $wallet=$wallet->takeCoin("1PT1EiqKskrHj8Efb27D76Hmnph645AKkH",$sys['wallet_ip']."/api/btc/takeCoin");

        var_dump($wallet);
    }
    public function check(){



        $sys =\app\common\model\Config::getSetting();

        $wallet=new \app\common\core\Wallet($sys['appId'],$sys['appSecret'],$sys['walletType']);


        $result=$wallet->getTx($sys['coinname'],"0x0b1fe663d510f9203bf6b9df0b734f5962e0314ba1b3e9aedc3fdc497c8f1567",$sys['wallet_ip']."/api/getTx");
        $result1=$wallet->informList("Y",1,100,$sys['wallet_ip']."/api/informList");
        var_dump($result);
        var_dump($result1);
    }

    /**
     * eth转账回调
     */
    public function ethCallback()
    {
        $ip = request()->ip();
        trace('eth转账回调请求日志'.'ip:'.$ip.'_'.json_encode($this->request->request()));
        //拦截ip
        check_api_ip();
        $hash = $this->request->request('hash'); // 交易记录hash
        $coinName = $this->request->request('coinName'); // 币种信息
        $walletType = $this->request->request('walletType'); //钱包类型
        if ($hash == '' || $coinName == '' || $walletType == '') {
            $this->error(__('Invalid parameters'));
        }

        //查询记录
        $recharge = db('collection_log')->where('eth_hash', $hash)->find();
        if (empty($recharge)) {
            $res = Walletapi::gettx($hash, $walletType, $coinName);
            if ($res['code'] != 200) {
                $this->error($res['msg']);
            }
            $rdata = $res['data'];

            $insert = array(
                'to' => $rdata['to'],
                'eth_hash' => $rdata['hash'],
                'create_time' => time(),
            );
            \db('collection_log')->insert($insert);

            $recharge = db('collection_log')->where('eth_hash', $rdata['hash'])->find();
        }

        if ($recharge['status'] == 1) {
            $this->success('该订单已归集','',200);
        }

        //发起归集
        $ret = Walletapi::collection($recharge['to']);
        trace('eth转账回调，执行归集失败'.'地址：'.$hash);
        trace($ret);
        if ($ret['code'] != 200) {
            \db('collection_log')->where('id', $recharge['id'])->update(['status' => -1, 'error_msg' => $ret['msg']]);
            $this->error($ret['msg']);
        }

        \db('collection_log')->where('id', $recharge['id'])->update(['hash' => $ret['data']]);

        $this->success('执行成功','',200);

    }

    /**
     * 归集回调
     */
    public function collection()
    {
        $ip = request()->ip();
        trace('归集回调请求日志','ip:'.$ip.'info'.'_'.json_encode($this->request->request()));
        trace($this->request->request());
        //拦截ip
        check_api_ip();
        $hash = $this->request->request('hash'); // 交易记录hash
        $coinName = $this->request->request('coinName'); // 币种信息
        $walletType = $this->request->request('walletType'); //钱包类型
        if ($hash == '' || $coinName == '' || $walletType == '') {
            $this->error(__('Invalid parameters'));
        }

        //查询记录
        $recharge = db('collection_log')->where('hash', $hash)->find();
        if (empty($recharge)) {
            $res = Walletapi::gettx($hash, $walletType, $coinName, 'coll');
            if ($res['code'] != 200) {
                $this->error($res['msg']);
            }
            $rdata = $res['data'];

            $insert = array(
                'to' => $rdata['from'],
                'hash' => $rdata['hash'],
                'create_time' => time()
            );
            \db('collection_log')->insert($insert);

            $recharge = db('collection_log')->where('hash', $rdata['hash'])->find();
        }

        if ($recharge['status'] == 1) {
            $this->success('该订单已归集','',200);
        }

        \db('collection_log')->where('id', $recharge['id'])->update(['status' => 1, 'finish_time' => time()]);

        $this->success('执行成功','',200);
    }
}