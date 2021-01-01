<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        $seventtime = \fast\Date::unixtime('day', -7);
        $paylist = $createlist = [];
        for ($i = 0; $i < 7; $i++)
        {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
        }
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');







        $todayTimeStart= strtotime(date('Y-m-d 00:00:00'));
        $todayTimeEnd=strtotime(date('Y-m-d 23:59:59'));
        $weekTimeStart= strtotime(date('Y-m-d 00:00:00',strtotime('-7 day')));
        $monthTimeStart= strtotime(date('Y-m-d 00:00:00',strtotime('-30 day')));




//        var_dump(date("Y-m-d H:i:s",$todayTimeEnd));
//        die;



//1、系统内总会员数字
        $data['totaluser']= db('user')->count();


        //2、系统内所有等级为注册会员的会员总数
        $data['reguser']= db('user')->where("level",1)->count();


        //3、系统内今日注册会员总数
        $data['todayreguser']=db('user')->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->count();


        //4、系统内所有会员的累积提现并且审核通过的USDT总数
        $data['all_usdt_tx']=db("cash_withdrawal")->where(['type'=>"usdt","status"=>1])->sum("real_amount");



        //5、系统内所有会员今日提现并且审核通过的USDT总数
        $data['today_usdt_tx']=db("cash_withdrawal")->where(['type'=>"usdt","status"=>1])->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("real_amount");


        //6、系统内所有会员的累积提现并且审核通过的FC总数
        $data['all_fc_tx']=db("cash_withdrawal")->where("type","fc")->where("status",1)->sum("real_amount");


        //7、系统内所有会员今日提现并且审核通过的FC总数
        $data['today_fc_tx']=db("cash_withdrawal")->where(['type'=>"fc","status"=>1])->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("real_amount");


        //8、系统内所有会员累积充值的USDT总数
        $data['all_recharge']=db('recharge_order')->where('status',1)->sum("amount");


        //9、系统内所有会员今日充值的USDT总数
        $data['day_recharge']=db('recharge_order')->where('status',1)->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("amount");



        //10、系统内所有会员的FC账户的余额总数：显示FC总数、流通的FC总数、锁定的FC总数
        $data['user_lt_fc']=db('user')->sum("credit1");
        $data['user_lock_fc']=db('user')->sum("lock_credit1");
        $data['all_fc']=$data['user_lock_fc']+$data['user_lt_fc'];



        //11、系统内当日USDT转化成功FC总数（包括会员自己兑换的和在交易中心买入的） 1 trade 正数
        $user_exchange_fc=db('cc_detail_log')->where(['type'=>"credit1","project"=>"exchange"])->where("num",">",0)->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("num");
        $user_trade_fc=db('cc_detail_log')->where(['type'=>"credit1","project"=>"trade"])->where("num",">",0)->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("num");


        $data['all_exchange_fc']=abs($user_exchange_fc)+$user_trade_fc;


        //12、系统内当日FC转化成功USDT总数（包括会员自己兑换的和在交z易中心卖出的）
        $all_exchange_usdt=db('cc_detail_log')->where(['type'=>"credit2","project"=>"exchange"])->where("num",">",0)->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("num");
        $user_trade_usdt=db('cc_detail_log')->where(['type'=>"credit2","project"=>"trade"])->where("num",">",0)->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("num");


        $data['all_exchange_usdt']=$all_exchange_usdt+$user_trade_usdt;
        //13、


        //14、系统内所有会员的押金USDT总数
        $data['all_yj_usdt']=db('tc_order')->where(['status'=>0])->sum("price_d");



        //15、系统内所有会员当前的USDT账户钱包余额总数（包括交易中心发布卖单时锁定的）
        $all_user_usdt=db('user')->sum("credit2");
        $all_lock_usdt=db('user')->sum("lock_credit2");

        $data['all_usdt']=$all_user_usdt+$all_lock_usdt;


        //16、系统内所有会员当前USDT账户钱包余额可提现总数（包括申请中的)
        $all_user_usdt=db('user')->sum("credit2");
        $all_fc_tx=db("cash_withdrawal")->where("status",0)->sum("usdt");


        $data['all_usdt1']=$all_user_usdt+$all_fc_tx;


        //17、系统内累积FC代币生成总数：显示总数、节点套餐日收益的总数、静态返佣奖生成总数（分开数字显示)
        $data['fc_jtfy']=db('bonus')->sum("f6");
        $data['fc_day']=db('tc_profit_log')->sum("num");
        $data['sc_fc']=$data['fc_jtfy']+$data['fc_day'];


//18、系统累积USDT生成总数：显示总数、推荐奖生成总数、节点奖励生成总数（分开数字显示）
        $data['tjj_usdt']=db('bonus')->sum("f5");
        $data['jdjl_usdt']=db('bonus')->sum("f7");
        $data['sc_usdt']=$data['tjj_usdt']+$data['jdjl_usdt'];
//19、系统内当日FC代币生成总数：显示总数、节点套餐日收益的总数、静态返佣奖生成总数（分开数字显示）
        $data['fc_jtfy_day']=db('bonus')->whereBetween("granttime",[$todayTimeStart,$todayTimeEnd])->sum("f6");
        $data['fc_jdsy_day']=db('tc_profit_log')->whereBetween("createtime",[$todayTimeStart,$todayTimeEnd])->sum("num");
        $data['sc_fc_day']= $data['fc_jtfy_day']+$data['fc_jdsy_day'];


//20、系统当日USDT生成总数：显示总数、推荐奖生成总数、节点奖励生成总数（分开数字显示）
        $data['tjj_usdt_day']=db('bonus')->whereBetween("granttime",[$todayTimeStart,$todayTimeEnd])->sum("f5");
        $data['jdjl_usdt_day']=db('bonus')->whereBetween("granttime",[$todayTimeStart,$todayTimeEnd])->sum("f7");
        $data['sc_usdt']=$data['tjj_usdt_day']+$data['jdjl_usdt_day'];
//        var_dump($user_trade_fc);
//        var_dump($all_usdt);
//        die;
//

        foreach ($data as &$v){
            $v=number_format($v);
        }


        $this->view->assign([
            'totalviews'       => 219390,
            'totalorder'       => 32143,
            'totalorderamount' => 174800,
            'todayuserlogin'   => 321,
            'todayusersignup'  => 430,
            'todayorder'       => 2324,
            'unsettleorder'    => 132,
            'sevendnu'         => '80%',
            'sevendau'         => '32%',
            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'addonversion'       => $addonVersion,
            'uploadmode'       => $uploadmode,
"data"=>$data,
//            'totaluser'        => $totaluser,
//            "reguser"=>$reguser,


        ]);

        return $this->view->fetch();
    }

}
