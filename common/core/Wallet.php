<?php
namespace app\common\core;
use think\Response;

class Wallet{

    private $appId;
    private $appSecret;
    private $walletType;



    //区块链回调我的接口，告诉我有一个订单了，回调参数hash、coinName、walletType
    function __construct($appId,$appSecret,$walletType)
    {
        $this->appId=$appId;
        $this->appSecret=$appSecret;
        $this->walletType=$walletType;
    }

    public  function createBTC($url){

        $data=[
            'walletType'=>$this->walletType,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];
        $result=self::doPost($url,$data);
        return $result;
    }


    //初始化钱包
    public  function init($uid,$url){
        /** @var $uid用户id */
        $data=[
            'walletType'=>$this->walletType,
            'openId'=>$uid,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];
        $result=self::doPost($url,$data);
        return $result;
    }


    //重启充值通知
/*充值通知失败后，可以重启继续通知，id为空时，默认是重启全部；*/
    public function resetInform($id,$url){
        $data=[
            'walletType'=>$this->walletType,
            'id'=>$id,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];
        $result=self::doPost($url,$data);
        return $result;
    }

    //充值通知列表
    public  function informList($status,$pageNum,$pageSize,$url){
        $data=[
            'status'=>$status,
            'pageNum'=>$pageNum,
            'pageSize'=>$pageSize
        ];
        $result=self::doPost($url,$data);
        return $result;

    }

    //获取节点btc的余额
    public  function allAmount($url){
        /** @var $url请求地址 */
        $data=[
            'walletType'=>$this->walletType,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];
        $result=self::doPost($url,$data);
        return $result;
    }

    //获取币种链上余额
    public function getAmount($url,$addr,$coinName){
        /** $addr 初始化钱包返回的地址*/
        /** $coinName币种，如:BTC*/

        $data=[
            'addr'=>$addr,
            'coinName'=>$coinName,
            'walletType'=>$this->walletType,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];
        $result=self::doPost($url,$data);
        return $result;
    }

    //交易信息查询确认
    public  function getTx($coinName,$hash,$url){
        $data=[
            'coinName'=>$coinName,
            'walletType'=>$this->walletType,
            'hash'=>$hash,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];

        $result=self::doPost($url,$data);
        return $result;
    }

    //转走btc节点余额
    public  function takeCoin($addr,$url){
        $data=[
            'addr'=>$addr,
            'walletType'=>$this->walletType,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];
        $result=self::doPost($url,$data);
        return $result;
    }

    public function create($url){

        $data=[
            'url'=>$url,
            'walletType'=>$this->walletType,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];
        $result=self::doPost($url,$data);
        return $result;
    }

    public function usdtTakeCoin($url,$from,$to,$fee){
        $data=[
            'from'=>$from,
            "to"=>$to,
            "fee"=>$fee,
            'walletType'=>$this->walletType,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];

        $result=self::doPost($url,$data);
        return $result;
    }




    //eth或者eth代币转账接口

    public  function ethTakeCoin($url,$from,$to,$coinName){
        $data=[
            'from'=>$from,
            "to"=>$to,
            "coinName"=>$coinName,
            'walletType'=>$this->walletType,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
        ];

        $result=self::doPost($url,$data);
        return $result;
    }

    public function infromList($url){
        $data=[
            'status'=>"Y",
            "pageNum"=>"1",
            "pageSize"=>"2",
        ];

        $result=self::doPost($url,$data);
        return $result;
    }

        //请求
    public static function doPost($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS , http_build_query($data));
        //post方式请求
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_HEADER,false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if ($output === FALSE) {
            return false;
        }
        $output=json_decode($output,true);
        curl_close($ch);
        return $output;
    }



    public static  function get($url){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $output=json_decode($output,true);
        curl_close($ch);
        return $output;
//        echo $output;
//        var_dump($output);
    }

}