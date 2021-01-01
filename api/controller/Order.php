<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 20:41
 */
namespace app\api\controller;

use app\common\controller\Api;
use think\Cookie;
use think\Db;
use app\common\core\Jsutil;
use app\common\model\Neworder;
use app\common\core\Procevent;
use  app\common\model\Bonus;
/**
 * 公告栏
 */
class Order extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['setLanguage','getLanguage','clock_netincome'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];
    public $type=null;
    public function _initialize()
    {
        parent::_initialize();
        $this->type=Cookie::get('think_var');
    }
    //购买JJ人
    public function  buy(){
        if ($this->request->isPost()){
            $uid=$this->auth->id;
            $m_id = input('id');
            $key_day=input('day');
            $money = input('money');
            if (!$m_id || !is_numeric($key_day) || !$money){
                $this->error( __('Parameters are missing'));
            }
            $userinfo = db('user')->field('username,credit1,credit2')->where('id',$uid)->find();
            $qdf = Db::name('bonus_type')->where('bkey','qdf')->where('isopen',1)->field('bname,data')->find();
            if (empty($qdf)){
                $fee =0;
            }else{
                $fee=unserialize($qdf['data'])['credit1']; //启动费用
            }

            $machineinfo = db('newmachine')->where('id',$m_id)->find();
            if($money>$userinfo['credit2']){
                $this->error( __('Insufficient account balance') );
            }

            if (!$machineinfo){
                $this->error( __('Robots don\'t exist') );
            }

            if ($machineinfo['dprice']>$money){
                $this->error( __('The payment price is less than the minimum price') );
            }

            if ($machineinfo['uprice']<$money){
                $this->error( __('The payment price exceeds the maximum price') );
            }

            $meal =unserialize($machineinfo['dayandnetincome']);
//            var_dump($meal,$meal[0][$key_day],$meal[1][$key_day]);die;
            $day=isset($meal[0][$key_day])?:false;
            $netincome = isset($meal[1][$key_day])?:false;
            if (!$day || !$netincome ){
                $this->error( __('Robot package error, please choose again') );
            }
            if ($userinfo['credit1']<$fee){
                $this->error( __('The user\'s balance is not enough to pay the starting fee') );
            }
            $netincomeinfo=unserialize($machineinfo['dayandnetincome']);

            $ordersn = 'SN:'.date('YmdHis').mt_rand(10000,99999);
            $insert = [
                'ordersn'=>$ordersn,
                'uid'=>$uid,
                'realpay'=>$money,
                'fee'=>$fee,
                'm_id'=>$m_id,
                'day'=>$meal[0][$key_day],
                'rate'=>$meal[1][$key_day],
                'status'=>1,
                'bonusstatus'=>1,
                'createtime'=>time(),
                'updatetime'=>time()
            ];
            Db::startTrans();
            try{
                db('order')->insert($insert);
                //扣除启动费
                $r_c='会员'.$userinfo['username'].'购买机器人'.$machineinfo['name'].'扣除'.$qdf['bname'].$fee.'CSQA';
                $r_e='member '.$userinfo['username'].' buying robots '.$machineinfo['name'].' deduct  '.$qdf['bname'].$fee.'CSQA';
                $r_t='會員'.$userinfo['username'].'購買機器人'.$machineinfo['name'].'扣除'.$qdf['bname'].$fee.'CSQA';
                setCc($userinfo['username'],'credit1',0-$fee,$r_c,$r_e,$r_t);
                //扣除机器人费用
                $c_c='会员'.$userinfo['username'].'购买机器人'.$machineinfo['name'].'扣除'.$money.'充值钱包';
                $c_e='member '.$userinfo['username'].' buying robots '.$machineinfo['name'].' deduct '.$money.' prepaid phone wallet ';
                $c_t='會員'.$userinfo['username'].'購買機器人'.$machineinfo['name'].'扣除'.$money.'充值錢包';
                setCc($userinfo['username'],'credit2',0-$money,$c_c,$c_e,$c_t);
                Db::commit();
            }catch (\Exception $e){
                $this->error($e->getMessage());
                Db::rollback();
            }
            $this->success(1,__('Done'));

        }
    }
    public function canceltips(){

        if ($this->request->isPost()){
            $sxf =db('bonus_type')->where('bkey','sxf')->where('isopen',1)->value('data');
            if (!$sxf){
                $rate=0;
            }else{
                $rate = unserialize($sxf)['fee'];
            }
            $this->success(1,['head'=>__('Tips'),'content'=>__('Note: ').$rate.__('% of the fee will be deducted from your Amount')]);
        }
    }
    //取消订单
    public function cancel(){
        if ($this->request->isPost()){
            $o_id= input('id');
            $uid= $this->auth->id;
            $orderinfo = db('order o')->where('o.id',$o_id)->where('o.uid',$uid)->
            where('o.status',1)->where('o.bonusstatus',1)->
            join('fa_newmachine n','o.m_id=n.id','left')->field('n.name,o.*,u.username')
                ->join('fa_user u','u.id=o.uid','left')->find();

            if (!$orderinfo){
                $this->error( __('Order does not exist') );
            }
            $sxf =db('bonus_type')->where('bkey','sxf')->where('isopen',1)->value('data');
            if (!$sxf){
                $fee=0;
            }
            $sxf = unserialize($sxf);
            $fee = $orderinfo['realpay'] *$sxf['fee']/100;  //需要扣除的手续费
            $netincome =$orderinfo['bonus'];//已经产生的收益
            $realpay =  $orderinfo['realpay'];//实际支付
            $total = $realpay - $netincome - $fee;
            $username =db('user')->where('id',$uid)->value('username');
            Db::startTrans();
            try{
//                db('user')->where('id',$o_id)->setInc('credti2',$total);
                $source = '会员：'.$username.'取消机器人：'.$orderinfo['name'].',本金为.'.$realpay.',扣除手续费：'.$fee.'和已产生收益'.$netincome.'后，实得'.$total;
                $s_e =  'member：'.$username.' cancel robot ：'.$orderinfo['name'].', the principal is .'.$realpay.',deduction of fee ：'.$fee.' and has generated revenue '.$netincome.',actually received '.$total;
                $s_t =  '會員：'.$username.'取消機器人：'.$orderinfo['name'].',本金為.'.$realpay.',扣除手續費：'.$fee.'和已產生收益'.$netincome.'後，實得'.$total;
                setCc($orderinfo['username'],'credit2',$total,$source,$s_e,$s_t);
                db('order')->update(['id'=>$o_id,'status'=>-1,'bonusstatus'=>0]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error( $e->getMessage() );
            }
            $this->success(1,__('Complete Cancellation'));

        }
    }

    //单条订单展示
    public function showorder(){
        if ($this->request->isPost()){
            $uid = $this->auth->id;
            $o_id=input('id');
            if (!$o_id){
                $this->error( __('Order does not exist') );
            }
            $orderinfo = db('order o')->where('o.uid',$uid)->join('newmachine n','n.id=o.m_id','left')->field('o.*,n.name,n.name_en,n.name_tw')->where('o.id',$o_id)->find();
            if (!$orderinfo){
                $this->error( __('Order does not exist') );
            }
            $orderinfo['deadline'] =date("Y-m-d", strtotime("+".$orderinfo['day']." days",  $orderinfo['createtime'])); ;
            $orderinfo['createtime']=date( 'Ymd H:i:s',$orderinfo['createtime']);
            if($this->type=='en'){
                $orderinfo['name']= $orderinfo['name_en'];
            }elseif ($this->type=='zh-tw'){
                $orderinfo['name']= $orderinfo['name_tw'];
            }
            $this->success(1,['data'=>$orderinfo]);
        }
    }

    //所有订单
    public function showallorder(){
        if ($this->request->isPost()){
            $uid=$this->auth->id;
            $page = input('page')?input('page'):1;
            $limit = input('limit')?input('limit'):10;
            $offset = ($page-1)*$limit;
            $status = input('status')===null?3:input('status');
            if ($status==3){
                $where=[];
            }else{
                $where=['bonusstatus'=>$status];
            }

            $arr_bonus=[
                '2'=>__('Completed'),
                '1'=>__('Icoming'),
                '0'=>__('Canceled'),
            ];
            $order = db('order o')->join('fa_newmachine n','o.m_id=n.id','left')->where('uid',$uid)->
            field('o.*,n.name,n.name_en,n.name_tw')->limit($offset,$limit)->where($where)->order('o.id desc')->select();
            $info = db('order')->field('sum(realpay) realpay,count(id) num')->where('uid',$uid)->where('bonusstatus',1)->where('status',1)->find();
            $totalmoney= $info['realpay'];
            $count=$info['num'];
//            var_dump($info);die;
            foreach ($order as &$v){
                if($this->type=='zh-tw'){
                    $v['name'] = $v['name_tw'];
                }elseif ($this->type=='en'){
                    $v['name'] = $v['name_en'];
                }
                $v['createtime']=date('Ymd-H-i-s',$v['createtime']);
                $v['bonusstatus'] =$arr_bonus[ $v['bonusstatus'] ]  ;
            }
            $this->success(1,['totalmoney'=>$totalmoney,'count'=>$count,'data'=>$order]);
        }
    }
    //订单升级
    public function  upgrade(){
        if($this->request->isPost()){
            $uid=$this->auth->id;
            $o_id=input('id');
            $money = input('money');

            if (!$o_id || !$money ){
                $this->error( __('Parameters are missing'));
            }
            $orderinfo = db('order o')->where('o.id',$o_id)->where('o.uid',$uid)->
            where('o.status',1)->where('o.bonusstatus',1)->where('n.status',1)->
            join('fa_newmachine n','o.m_id=n.id','left')->field('n.name,n.uprice,o.*,u.username,u.credit2')
                ->join('fa_user u','u.id=o.uid','left')->find();
            if (!$orderinfo){
                $this->error( __('Order does not exist'));
            }
            if ($orderinfo['credit2']<$money){
                $this->error( __('Insufficient account balance'));
            }
            //加金超过价值区间则时间重置，套餐自动升级  不超过则只是增加金额
            $newmoney = floatval($orderinfo['realpay'])+floatval($money);

            $update=[];
            if ($newmoney<$orderinfo['uprice']){  //如果不超过价价值区间

                $update['realpay'] = $newmoney;
                Db::startTrans();
                try{
                    db('order')->where('id',$o_id)->update($update);
                    $r_c='会员'.$orderinfo['username'].'加金机器人'.$orderinfo['name'].'成功，扣除加金金额'.$money;
                    $r_e='member '.$orderinfo['username'].' gold adding robot '.$orderinfo['name'].'to success，amount deducted '.$money;
                    $r_t='會員'.$orderinfo['username'].'加金機器人'.$orderinfo['name'].'成功，扣除加金金額'.$money;
                    setCc( $orderinfo['username'],'credit2',0-$money,$r_c,$r_e,$r_t);
                    Db::commit();
                }catch (\Exception $e){
                    Db::rollback();
                    $this->error(__('System is busy, please try again later'));
                }
            }else{ //如果超过价价值区间  则变成下一级别的同等级套餐
                $machineinfo = db('newmachine')->where('status',1)->select();
                $key=null;
//                var_dump($newmoney);die;
                $oldmachine=db('newmachine')->where('id',$orderinfo['m_id'])->value('dayandnetincome');
                foreach ($machineinfo as $k=>$v){
                    if ($v['dprice']<=$newmoney && $v['uprice']>$newmoney){  //如果加金金额在下一个机器人区间内
                        $v['dayandnetincome']=unserialize($v['dayandnetincome']);
                        foreach ($v['dayandnetincome'][0] as $kk=>$vv){
                            $oldmachine=unserialize($oldmachine);
                            $key=null;
                            foreach ($oldmachine[0] as $kkk=>$vvv){
                                if ($vvv==$orderinfo['day']){
                                    $key=$kkk;  //上一次套餐的键
                                }
                            }
                            if ($key===null){
                                $this->error( __('Order does not exist'));
                            }

                            $update =  [
                                'realpay'=>$newmoney,
                                'm_id'=>$v['id'],
                                'day'=>$v['dayandnetincome'][0][$key],
                                'updatetime'=>time(),
                                'bonusday'=>0,
                                'rate'=>$v['dayandnetincome'][1][$key],
                            ];
//                            var_dump($update);die;
                            Db::startTrans();
                            try{
                                db('order')->where('id',$o_id)->update($update);
                                $s_c='会员'.$orderinfo['username'].'加金机器人'.$orderinfo['name'].'超过原有价值区间，套餐自动升级为'.$v['name'].',套餐周期为：'.$v['dayandnetincome'][0][$key].'，扣除加金金额'.$money;
                                $s_e='member '.$orderinfo['username'].' gold adding robot '.$orderinfo['name'].' beyond the original value range ，the package is automatically upgraded to '.$v['name'].',the package period is：'.$v['dayandnetincome'][0][$key].' days，amount deducted '.$money;
                                $s_t='會員'.$orderinfo['username'].'加金機器人'.$orderinfo['name'].'超過原有價值區間，套餐自動升級為'.$v['name'].',套餐週期為：'.$v['dayandnetincome'][0][$key].'天，扣除加金金額'.$money;
                                setCc( $orderinfo['username'],'credit2',0-$money,$s_c,$s_e, $s_t);
                                Db::commit();
                            }catch (\Exception $e){
                                Db::rollback();
                                $this->error(__('System is busy, please try again later'));
                            }
                            $this->success(__('Success'));

                        }

                    }
                }
                $this->error( __('Order does not exist')); //如果加金升级失败

            }


            $this->success(__('Success'));
        }
    }

    //量化资产
    public function daynetincome(){
        if ($this->request->isPost()){
            $uid=$this->auth->id;
            $page = input('page')?input('page'):1;
            $limit = input('limit')?input('limit'):10;
            $offset = ($page-1)*$limit;
            $order =  db('order o')->field('o.*,n.name')->order('o.createtime desc')->limit($offset,$limit)->join('fa_newmachine n','n.id=o.m_id','left')->where('o.uid',$uid)->where('o.status',1)->select();
            $arr=[];
            $total=0;
            foreach ($order as $k=>$v){
                $total+=$v['bonus'];
                if ($v['bonusstatus']){
                    $arr[$k]['total']=$v['bonus'];
                    $arr[$k]['daytotal']=$v['rate']*$v['realpay']/100;
                    $arr[$k]['createtime']=date('Y-m-d',$v['createtime']);
                    $arr[$k]['name']=$v['name'];
                }
            }
            $this->success(1,['data'=>$arr,'totalmoney'=>$total]);
        }
    }
    public function state(){
        if ($this->request->isPost()){
            $work=config('site.system_working_state').'%';
            $saturated=config('site.system_saturated_state').'%';
            $this->success(1,['data'=>['work'=>$work,'saturated'=>$saturated]]);
        }
    }

    //随机4个机器人
    public  function  random(){
        if ($this->request->isPost()){
            $machines = db('newmachine')->field('id,name,dayandnetincome,status')->select();
            $num = input('num')?input('num'):4;
            $num = min(count($machines),$num);
            $random_keys=array_rand($machines,$num);
            $arr= [];
            $temp=[
                '0'=>__('Closed'),
                '1'=>__('Opening')
            ];
            foreach ($random_keys as $v){
                $machines[$v]['dayandnetincome'] = unserialize($machines[$v]['dayandnetincome']);
                $machines[$v]['status'] = $temp[  $machines[$v]['status']];
                $machines[$v]['type'] = __('Daily Return');
                $arr[] = $machines[$v];
            }
            $this->success(1,['data'=>$arr]);
        }
    }
    //多语言设置
    public function setLanguage()
    {
        $type = $this->request->request('lang');
        if(empty($type)) $type = 'en';
        Cookie::set('think_var',$type,7600);
        $this->success($type,'','1');
    }
    //多语言返回数据
    public function getLanguage()
    {
        $data = \app\common\model\Lang::language();
        $this->success(__('The query is successful'),$data,'1');
    }
    //JJ人定时任务收益
    public function clock_netincome(){
//        if (!check_ip()){
//            echo '非法访问';return;
//        }
        $bkey='jjr';
        $jjr = db('bonus_type')->where('bkey',$bkey)->where('isopen',1)->find();
        if (!$jjr){
            echo '未开启'.$jjr['bname'];return;
        }
        $allorder = db('order o')->where('o.status',1)->field('o.*,u.username')->where('o.bonusstatus',1)
            ->join('user u','o.uid=u.id','left')->select();
        if (!$allorder){
            echo '没有可以结算的订单';return;
        }
        echo  'start'.date('Ymd H:i:s',time());
        $machineinfo=[];
        $machine  = db('newmachine')->where([])->field('id,name')->select();
        foreach ($machine as &$vv){
            $machineinfo[$vv['id']]= $vv;
        }
        unset($machine);

        $temp =[];  //奖金入库
        $update=[]; //订单更新

        error_reporting(0);  //下面 第一次使用+= 不报错  多个mid相同的订单合为一条
        $periods = Jsutil::getPeriods()+1;

        foreach ( $allorder as &$v){
            //奖金数据
            $v['source'] ='获得'.$machineinfo[ $v['m_id'] ]['name'].'的每日收益,计算公式：'.$v['realpay'].'*'.$v['rate'].'%='.($v['realpay']*$v['rate']/100) ;
            $temp[$v['uid']]['netincome'] +=$v['realpay']*$v['rate']/100;
            $temp[$v['uid']]['money'] =$temp[$v['uid']]['netincome'];
//            $temp[$v['uid']]['periods'] = $periods;
            $temp[$v['uid']]['done'] =0;
            $temp[$v['uid']]['state'] =0;
            $temp[$v['uid']]['uid'] =$v['uid'];
            $temp[$v['uid']]['username'] =$v['username'];
//            $temp[$v['uid']]['addtime'] =time();
            $temp[$v['uid']]['jsparams'] =json_encode( [  'bkey'=>$bkey ,'bval'=> $temp[$v['uid']]['netincome'] ]   );
            $temp[$v['uid']]['source'] .=$v['source'].'<br>';
            $temp[$v['uid']][$jjr['fieldname']] =$temp[$v['uid']]['netincome'];
            $temp[$v['uid']]['addtime']=time();
            $temp[$v['uid']]['periods']=$periods;
            //订单数据
            $updatetemp['id'] = $v['id'];
            $updatetemp['bonusday']=$v['bonusday']+1;
            $updatetemp['bonusstatus'] = 1;
            if ($updatetemp['bonusday'] == $v['day']){  //套餐到期
                $updatetemp['bonusstatus'] = 2;
                //返还套餐的押金
                $s_c='会员：'.$v['username'].'的机器人套餐.'.$machineinfo[ $v['m_id'] ]['name'].'收益周期到期，返还本金'.$v['realpay'];
                $s_e='member：'.$v['username'].' robot packages .'.$machineinfo[ $v['m_id'] ]['name'].' has expired ，return the principal '.$v['realpay'];
                $s_t='會員：'.$v['username'].'的機器人套餐.'.$machineinfo[ $v['m_id'] ]['name'].'收益週期到期，返還本金'.$v['realpay'];
                setCc($v['username'],'credit2',$v['realpay'],$s_c,$s_e,$s_t);
            }
            $updatetemp['bonus']=$v['realpay']*$v['rate']/100 + $v['bonus'] ;
            $update[] = $updatetemp;
        }

        if ($temp){
            $bonusmodel = new Bonus();   //结算 订单收益
            $bonusmodel->allowField(['source','netincome','money','uid','done','state','jsparams',$jjr['fieldname'],'addtime','periods'])->saveall($temp);
            Procevent::dsell_event($temp,'gwj');       //结算 因为订单收益产生的推荐收益奖励
        }

        if ($update){
            $order = new Neworder();
            $order->isUpdate(true)->saveAll($update);
        }
        echo  'finish'.date('Ymd H:i:s',time());
    }

}