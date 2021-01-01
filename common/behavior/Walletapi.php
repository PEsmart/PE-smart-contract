<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 14:23
 */

namespace app\common\behavior;
use app\admin\library\Log;
use fast\Http;

class Walletapi
{
    public function _initialize(){
        //检测是否启用钱包
        if(!config('site.bcw_enable')){
            return ['code' => 0, 'msg' => '钱包尚未启用'];
        }
    }

    /**
     * 初始化创建会员钱包
     * @param $id 会员id
     */
    public static function createwallet($id){
        $config=config('wallet');

        if(empty($config['port'])){
            $url=$config['server'].$config['init'];
        }else{
            $url=$config['server'].':'.$config['port'].$config['init'];
        }
        $data['appId']=$config['appId'];
        $data['appSecret']=$config['appSecret'];
        $data['walletType']=$config['walletType'];
        $data['openId']=$id;
        $res = json_decode(Http::post($url,$data),true);
        if($res['code']==200){
            $addr=$res['data'][$config['coinName']];
            return $addr;
        }

    }

    /**
     * 获得钱包订单信息
     * @param $hash 交易hash值
     * @param $walletType 钱包类型
     * @param $coinName 币种名字
     */
    public static function gettx($hash,$walletType,$coinName,$txType = ''){
        $config=config('wallet');

        if(empty($config['port'])){
            $url=$config['server'].$config['getTx'];
        }else{
            $url=$config['server'].':'.$config['port'].$config['getTx'];
        }
        $data['hash']=$hash;
        $data['walletType']=$walletType;
        $data['coinName']=$coinName;
        $data['appId']=$config['appId'];
        $data['appSecret']=$config['appSecret'];
        if ($txType) {
            $data['txType']=$txType;
        }
        $res=json_decode(Http::post($url,$data),true);
        return $res;
    }

    /**
     * 获取节点余额
     * @param $hash 交易hash值
     */
    public static function amount(){
        $config=config('wallet');
        if(empty($config['port'])){
            $url=$config['server'].$config['amount'];
        }else{
            $url=$config['server'].':'.$config['port'].$config['amount'];
        }
        $data['appId']=$config['appId'];
        $data['appSecret']=$config['appSecret'];
        $data['walletType']=$config['walletType'];
        $res=json_decode(Http::post($url,$data),true);

        return $res['data'];
    }
    //获取币种链上余额
    public static function getamount($addr,$coinName='',$walletType=''){
        $config=config('wallet');
        if(empty($config['port'])){
            $url=$config['server'].$config['amount'];
        }else{
            $url=$config['server'].':'.$config['port'].$config['amount'];
        }
        $coinName = $coinName ?:$config['coinName'];
        $walletType = $walletType ?:$config['walletType'];
        $data['appId']=$config['appId'];
        $data['appSecret']=$config['appSecret'];
        $data['walletType']=$walletType;
        $data['addr']=$addr;
        $data['coinName']=$coinName;
        $res=json_decode(Http::post($url,$data),true);
        return $res['data'];
    }
    /**
     * 资金归集
     * @param $hash 交易hash值
     */
    public static function collection($addr){
        $config=config('wallet');
        if(empty($config['port'])){
            $url=$config['server'].$config['collection'];
        }else{
            $url=$config['server'].':'.$config['port'].$config['collection'];
        }
        $data['appId']=$config['appId'];
        $data['appSecret']=$config['appSecret'];
        $data['walletType']=$config['walletType'];
        $data['from']=$addr;
        $data['to']=$config['gjAddr'];
        $data['coinName']=$config['bzName'];
        $data['informUrl']=$config['collCoinUrl'];
        $res=json_decode(Http::post($url,$data),true);
        return $res;

    }

    //Exmo交易所行情接口
    public static function Exmo($coin,$type='USD'){
        $burl=config('Trade.Exmo');
        $url=$burl.$coin.'_'.$type;//https://api.exmo.me/v1/order_book/?pair=DASH_USD
        $res=json_decode(Http::get($url),true);
        $data=sprintf('%.2f',$res[$coin.'_'.$type]['bid_top']);
        return $data;
    }
    public static function Exmoall($coin,$type='USD'){
        $url=config('Trade.Exmoall');
        $res=json_decode(Http::get($url),true);
        foreach ($coin as $v) {
            $data[$v]=sprintf('%.2f',$res[$v.'_'.$type]['buy_price']);
        }
        return $data;
    }


    /**
     * 火币k线图
     * @param $symbol 合约名称 如"BTC_CW"表示BTC当周合约，"BTC_NW"表示BTC次周合约，"BTC_CQ"表示BTC季度合约
     * @param $period K线类型  1min, 5min, 15min, 30min, 60min,4hour,1day, 1mon
     * @param $size   获取数量 [1,2000]
     */
    public static function huobi_kline($symbol='BTC_CW',$period='1day',$size='150'){
        $url='https://api.hbdm.com';
        $api='/market/history/kline';
        $geturl=$url.$api.'?symbol='.$symbol.'&period='.$period.'&size='.$size;
        $res=json_decode(Http::get($geturl),true);
        return $res;
    }

    //ETH转账接口
    public static function takeCoin($to){
        if (!$to)return ['code' => 0, 'msg' => '参数有误'];
        $config = config('wallet');
        if (empty($config['port'])) {
            $url = $config['server'] . $config['takeCoin'];
        } else {
            $url = $config['server'] . ':' . $config['port'] . $config['takeCoin'];
        }
        $walletType = $config['walletType'];
        $data['from'] = $config['takeCoinAddr'];
        $data['pk'] = $config['takeCoinPk'];
        $data['to'] = $to;
        $data['coinName'] = 'ETH';
        $data['amount'] = $config['takeCoinNum'];
        $data['informUrl'] = $config['takeCoinUrl'];
        $data['walletType'] = $walletType;
        $data['appId'] = $config['appId'];
        $data['appSecret'] = $config['appSecret'];
        $res = json_decode(Http::post($url, $data), true);
        return $res;
    }

    /**
     * 火币行情信息
     */
    public static function huobiKline()
    {
        $list = [];

        $symbol = 'btcusdt';
        $list[$symbol] = self::huobiCompute($symbol);

        $symbol = 'ethusdt';
        $list[$symbol] = self::huobiCompute($symbol);

        $symbol = 'eosusdt';
        $list[$symbol] = self::huobiCompute($symbol);

        self::huobiFile($list);

        return $list;

    }

    /**
     * 计算数额
     * @param array $data
     * @return array|bool
     */
    protected static function huobiCompute($symbol = '')
    {
        if (empty($symbol)) {
            return false;
        }

        $config = config('huobi');

        $url = $config['server'] . $config['kline'];

        $data['symbol'] = $symbol;
        $res = json_decode(Http::get($url, $data), true);

        if ($res['status'] == 'ok' && isset($res['status'])) {
            $tick = $res['tick'];
            $list['list'] = $tick;
            $list['data'] = [
                'total' => round($tick['amount']),
                'amount' => $tick['close'],
                'gain' => round(($tick['close'] - $tick['open']) / $tick['open'] * 1, 2)
            ];;
        }else{
            $huobikline = config('huobikline');

            $list = $huobikline[$symbol];

        }

        return $list;
    }

    /**
     * 刷新火币信息配置文件
     */
    protected static function huobiFile($data = [])
    {

        if (!is_array($data) || empty($data)) {
            return false;
        }

        $data['updatetime'] = time();

        file_put_contents(APP_PATH . 'extra' . DS . 'huobikline.php', '<?php' . "\n\nreturn " . var_export($data, true) . ";");

        return true;
    }




    //=================== 2020.8.13新增 =================== //
    /**
     * ETH或者ETH代币转账接口
     * @param $to           提现地址
     * @param $coinName     币种类型
     * @param $amount       提现实际到账金额（扣除手续费后）
     * @return array|mixed
     */
    public static function coin_transfer($to, $coinName, $amount)
    {
        if (!$to)return ['code' => 0, 'msg' => '参数有误'];
        $config = config('wallet');
        if (empty($config['port'])) {
            $url = $config['server'] . $config['coin_transfer_eth'];
        } else {
            $url = $config['server'] . ':' . $config['port'] . $config['coin_transfer_eth'];
        }
        $walletType = $config['walletType'];
        //提现-转账回调地址
        $callback_url = "http://{$_SERVER['HTTP_HOST']}/api/user/withdrawalop";

        $data = [
            'from'  => $config['transfer_addr_eth'],
            'pk'    => $config['transfer_privateKey_eth'],
            'to'    => $to,
            'coinName'  => $coinName,
            'amount'    => $amount,
            'informUrl' => $callback_url,
            'walletType' => $walletType,
            'appId'     => $config['appId'],
            'appSecret' => $config['appSecret'],
        ];
        $res = json_decode(Http::post($url, $data), true);
        $data['url'] = $url;

        //记录日志
        $logInfo = '钱包转账-参数'.json_encode($data, JSON_UNESCAPED_UNICODE).'，结果:'.json_encode($res, JSON_UNESCAPED_UNICODE);
        TestLogAdd($logInfo, 'wallet');
        return $res;
    }

    /**
     * ETH或ETH代币钱包归集设置
     */
    public static function collection_set($params)
    {
        $config = config('wallet');
        if(empty($config['port'])){
            $url=$config['server'].$config['collection_set_eth'];
        }else{
            $url=$config['server'].':'.$config['port'].$config['collection_set_eth'];
        }
        //钱包类型，组合方式：币种名_类型（COLLECT），如ERC20代币归集设置组合：ERC20_COLLECT，ETH归集组合：ETH_COLLECT
        $walletType = $params['walletType'];
        //归集回调地址
        $callback_url = "http://{$_SERVER['HTTP_HOST']}/api/user/storage";

        $data = [
            'addr' => $params['addr'], //归集钱包地址
            'walletType' => $walletType,
            'informUrl' => $callback_url,
            'delimitBalance' => $params['delimitBalance'], //最低归集金额设置
        ];
        $res = json_decode(Http::post($url,$data),true);
        $data['url'] = $url;

        //记录日志
        $logInfo = '钱包归集设置-参数'.json_encode($data, JSON_UNESCAPED_UNICODE).'，结果:'.json_encode($res, JSON_UNESCAPED_UNICODE);
        TestLogAdd($logInfo, 'wallet');
        return $res;
    }

    /**
     * ETH油费钱包设置
     */
    public static function gas_wallet_set($params)
    {
        $config = config('wallet');
        if(empty($config['port'])){
            $url = $config['server'].$config['gas_wallet_eth'];
        }else{
            $url = $config['server'].':'.$config['port'].$config['gas_wallet_eth'];
        }
        $data = [
            'addr'       => $params['oil_addr'],
            'privateKey' => base64_encode($config['oil_privateKey']),//私钥，使用BASE64加密
        ];
        $res = json_decode(Http::post($url,$data),true);
        $data['url'] = $url;

        //记录日志
        $logInfo = 'ETH油费钱包设置-参数'.json_encode($data, JSON_UNESCAPED_UNICODE).'，结果:'.json_encode($res, JSON_UNESCAPED_UNICODE);
        TestLogAdd($logInfo, 'wallet');
        return $res;
    }



}