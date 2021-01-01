<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\core\Get;
use app\common\core\TransL;

/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ["getBanner","getPrice"];
    protected $noNeedRight = ['*'];
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];


    public function _initialize()
    {
        parent::_initialize();
        $this->type=\think\Cookie::get('think_var');
    }

    /**
     * 首页
     * 
     */
    public function index()
    {
        $this->success('请求成功');
    }

    public function getBanner(){

        $banner=config('site.banner');
        if($this->type=="en"){
            $banner=config('site.banner_en');

        }

        $this->success(__('获取成功'),$banner);
    }


    public function getShowData(){

        $user=$this->auth->getUser();
        foreach (config('site.exchange_fc_rate') as $k => $v) {
            $fcRate['key'] = $k;
            $fcRate['value'] = $v;
        }
        foreach (config('site.exchange_usdt_rate') as $k => $v) {
            $ustdRate['key'] = $k;
            $ustdRate['value'] = $v;
        }
        $exchange_usdt_rate=$ustdRate['value']/$ustdRate['key'];
        $rate="1FC=".round($exchange_usdt_rate,4)."USDT";
        $fclt=config("site.fc_ltl");
        $lock_jdjl=$user['lock_jdjl'];
        $str=Get::getLang("id,name_cn,per2,per3,price,per,image","id,name_en as name_cn,price,per,per2,per3,image",$this->type);
        $result=db('tc_manage')->where("is_recommend",1)->field($str)->order("updatetime","desc")->select();

        $this->success("获取成功",["rate"=>$rate,"fclt"=>$fclt,"lock_jdjl"=>$lock_jdjl,"tc"=>$result]);
    }



    public function getPrice(){

        $getPrice=new \app\common\core\Wallet("aa","aa","aa");
        $data=[];
        $result=$getPrice->get("https://data.block.cc/api/v1/price?symbol_name=bitcoin,Ethereum,CNY");
        foreach ($result['data'] as $v){

            $arr=["price"=>round($v['price'],2),"type"=>$v['symbol']];
            array_push($data,$arr);
        }
     $this->success("获取成功",$data);
    }


//    public function getPrice(){
//
//
//
//        $ty = date('Y', time());
//        $tm = date('m', time());
//        $td = date('d', time());
//        $todayStartTime = mktime(0,0,0,$tm,$td,$ty);
//        $todayEndTime   = mktime(23,59,59,$tm,$td,$ty);
//        $yesterdayStartTime =$todayStartTime-86400;
//        $yesterdayEndTime = $todayEndTime-86400;
//
//        $btcData=\db('currency_price')->where('symbol',"BTC")->where('type',"day")->whereBetween('createtime',[$yesterdayStartTime,$yesterdayEndTime])->find();
//        $ethData=\db('currency_price')->where('symbol',"ETH")->where('type',"day")->whereBetween('createtime',[$yesterdayStartTime,$yesterdayEndTime])->find();
//        $bchData=\db('currency_price')->where('symbol',"BCH")->where('type',"day")->whereBetween('createtime',[$yesterdayStartTime,$yesterdayEndTime])->find();
//
////        var_dump(time()-86400);
////
////        if($bchData){
////            var_dump("aa");
////        }
//        $getPrice=new \app\common\core\Wallet("aa","aa","aa");
//        $btcResult=$getPrice->get("https://api.coinbase.com/v2/prices/BTC-USD/sell");
//        $btc=$btcResult['data']['amount'];
//        $ethResult=$getPrice->get("https://api.coinbase.com/v2/prices/ETH-USD/sell");
//        $eth=$ethResult['data']['amount'];
//        $bchResult=$getPrice->get("https://api.coinbase.com/v2/prices/BCH-USD/sell");
//        $bch=$bchResult['data']['amount'];
//
//        $data=[];
//        $data[0]=['name'=>$btcData['symbol'],'yesterdayPrice'=>$btcData['price'],'latestPrice'=>$btc,'change_daily'=>round(($btc-$btcData['price'])/$btcData['price'],4)];
//        $data[1]=['name'=>$ethData['symbol'],'yesterdayPrice'=>$ethData['price'],'latestPrice'=>$eth,'change_daily'=>round(($eth-$ethData['price'])/$ethData['price'],4)];
//        $data[2]=['name'=>$bchData['symbol'],'yesterdayPrice'=>$bchData['price'],'latestPrice'=>$bch,'change_daily'=>round(($bch-$bchData['price'])/$bchData['price'],4)];
//        $this->success("获取成功",$data);
//    }



    public function fcRate(){

        $result=\db('currency_change')->field('price,create_time')->order('create_time','asc')->select();

        if (count($result)==0){

            $this->error('请到后台设置FC兑换USDT比例');
        }
        $arr=[];
        array_push($arr,['create_time'=>$result[0]['create_time'],'date'=>date("Y-m-d",$result[0]['create_time']),'price'=>$result[0]['price']]);

        $arrLen=count($result);
        foreach ($result as $k=>$item){
            if ($k>=1){
                $start=$result[$k-1]['create_time'];
                $startY=date("Y",$start);
                $startM=date("m",$start);
                $startD=date("d",$start);
                $startTime=mktime(00,00,00,$startM,$startD,$startY);

                $end=$result[$k]['create_time'];
                $endY=date('Y',$end);
                $endM=date('m',$end);
                $endD=date('d',$end);
                $endTime=mktime(00,00,00,$endM,$endD,$endY);


                $noSetDay=($endTime-$startTime)/86400-1;

                if ($noSetDay>0){
                    for ($i=0;$i<$noSetDay;$i++){
                        array_push($arr,['create_time'=>$result[$k-1]['create_time']+86400*($i+1),'date'=>date('Y-m-d',$result[$k-1]['create_time']+86400*($i+1)),'price'=>$result[$k-1]['price']]);
               }
                    array_push($arr,['create_time'=>$result[$k]['create_time'],'date'=>date('Y-m-d',$result[$k]['create_time']),'price'=>$result[$k]['price']]);
                }else{
                    array_push($arr,['create_time'=>$result[$k]['create_time'],'date'=>date('Y-m-d',$result[$k]['create_time']),'price'=>$result[$k]['price']]);
                }
            }


            if ($arrLen-1==$k){
                $start=$result[$k]['create_time'];
                $startY=date("Y",$start);
                $startM=date("m",$start);
                $startD=date("d",$start);
                $startTime=mktime(00,00,00,$startM,$startD,$startY);

                $end=time();
                $endY=date('Y',$end);
                $endM=date('m',$end);
                $endD=date('d',$end);
                $endTime=mktime(00,00,00,$endM,$endD,$endY);

                $noSetDay=($endTime-$startTime)/86400;

                if ($noSetDay>0){
                    for ($i=0;$i<$noSetDay;$i++){
                        array_push($arr,['create_time'=>$result[$k]['create_time']+86400*($i+1),'date'=>date('Y-m-d',$result[$k]['create_time']+86400*($i+1)),'price'=>$result[$k]['price']]);
                    }
                }

            }
        }

        $this->success("获取成功",$arr);


    }


//
//        for ($i=1;$i<$day;$i++){
//
//        }

//        for ($i=0;$i<20;$i++){
//
//            db('currency_change')->insert(['price'=>rand(1,9),"create_time"=>1568959312+$i*86400]);
//        }
//        db()->commit();




//    public function change(){
//        $result=\db('currency_change')->order('create_time','desc')->select();
//
//
//        $data=[];
//        foreach($result as $k=>&$value){
//            $value['date']=date('Y-m-d',$value['create_time']);
//
//            $arr=[
//                $value['id'],
//               $value['change_min'],
//               $value['change_max'],
//                $value['min'],
//                $value['max'],
//               $value['create_time'],
//               $value['date'],
//
//            ];
//            array_push($data,$arr);
//        }
//        $this->success(__('获取成功'),$data);
//    }
//











    public function test(){

//          $aa=strtotime("2019-7-04");
//          var_dump($aa);
          $max=0.25;
          $min=0.15;
          $chang_min=0.15;
          $chang_max=0.2;
          for($i=0;$i<20;$i++){
              db('currency_change')->insert(['max'=>$max+$i*0.01,'min'=>$min+$i*0.01,'change_min'=>$chang_min+$i*0.01,'change_max'=>$chang_max+$i*0.01,'create_time'=>1562169600+$i*86400]);
          }
          $this->success(1);

      }


    public function getArticle(){
        $lang=$this->request->request('language');
        $type=$this->request->request('type');
        $title="";
        switch($type){
            case "plan_one":
                 $title="收并购计划";
                break;
            case "plan_two":
                $title="研发计划";
                break;
            case "service":
                $title="服务条款";
                break;
            case "agreement":
                $title="隐私协议";
                break;
            case "app_info";
                $title="关于我们";
                break;
        }
        $baiDu = new TransL();
        $result = $baiDu->translate($title, "auto",$lang);
        $title=$result['trans_result'][0]['dst'];
        $this->success(__('获取成功'),['title'=>$title,'content'=>config('site.'.$type."_".$lang)]);
    }

    public function planInfo(){
        $oneAll=config('site.plan_one_all');
        $oneQuota=config('site.plan_one_quota');
        $oneDate=config('site.plan_one_date');
        $oneDate=strtotime($oneDate);
        $oneDate=date("Y-m-d",$oneDate);
        $twoAll=config('site.plan_two_all');
        $twoQuota=config('site.plan_two_quota');
        $twoDate=config('site.plan_two_date');
        $twoDate=strtotime($twoDate);
        $twoDate=date("Y-m-d",$twoDate);
        $this->success(__("获取成功"),['one_all'=>$oneAll,'one_quota'=>$oneQuota,'one_date'=>$oneDate,'two_all'=>$twoAll,'two_quota'=>$twoQuota,'two_date'=>$twoDate]);
    }
}
