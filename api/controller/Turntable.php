<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/1 0001
 * Time: 19:11
 */

namespace app\api\controller;
use app\common\controller\Api;

class Turntable extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = '*';
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];
    
    //奖品名称
    protected $rewards = [];

    public function __construct(){
        parent::__construct();
        $this->rewards = [
            'no'=>'无奖品',
            'credit1' => config('site.credit1_text'),
            'credit2' => config('site.credit2_text'),
            'credit3' => config('site.credit3_text'),
            'credit4' => config('site.credit4_text'),
            'credit5' => config('site.credit5_text'),
        ];
    }

    
    //获取转盘数据
    public function getlist(){
        $mid = $this->request->request('id');
        if(empty($mid)){
            $this->error('请求失败');
        }
        $total=db('turntable_tickets')->where('status',0)->where('mid',$mid)->count();
        $list = db('turntable')->select();
        $rule=db('config')->where('name','turntablerule')->value('value');
        $this->success('请求成功',array('list'=>$list,'total'=>$total,'rule'=>$rule));
        
    }
    
    //获取中奖选项
    public function getreward(){
        //抽奖人
        $mid = $this->request->request('id');
        
        //查询是否还有抽奖券
        $total=db('turntable_tickets')->where('status',0)->where('mid',$mid)->count();
        if(empty($total)){
            $this->error('抽奖券已用完');
        }
        if(empty($mid)){
            $this->error('请求失败');
        }
        
        $temreward =db('turntable')->column('percent','id');
        
        //抽奖
        $reward_id=$this->getrand($temreward);
        $reward=db('turntable')->where('id',$reward_id)->find();
        
        $username=db('user')->where('id',$mid)->value('username');
        $fieldname=$reward['reward'];
        $num=$reward['num'];
        $remark='大转盘抽奖获得'.$this->rewards[$fieldname].':'.$num;
        
        if($fieldname!='no'){
            
                setCc($username,$fieldname,$num,$remark);
                $tid=db('turntable_tickets')->where('mid',$mid)->where('status',0)->value('id');
                db('turntable_tickets')->where('id',$tid)->update(['status'=>1,'type' => $fieldname,'num'=>$num,'content'=>$remark,'updatetime'=>time()]);
            $this->success('1',array('reward_id'=>$reward_id,'remark'=>'恭喜你，获得'.$num.'个'.$this->rewards[$fieldname]));
            
        }else{
            $tid=db('turntable_tickets')->where('mid',$mid)->where('status',0)->value('id');
            db('turntable_tickets')->where('id',$tid)->update(['status'=>1,'type' => $fieldname,'num'=>$num,'content'=>$remark,'updatetime'=>time()]);
            $this->success('1',array('reward_id'=>$reward_id,'remark'=>'很遗憾，没有抽中奖品'));
        }
    
//        $this->success('1',array('reward_id'=>$reward_id));
    }
    
    //根据概率抽奖
    private function getrand($proArr){
        $result = '';
        $proSum = array_sum($proArr);
        foreach ($proArr as $key => $proCur )
        {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur)
            {
                $result = $key;
                break;
            }
            $proSum -= $proCur;
        }
        unset($proArr);
        return intval($result);
    }
    
}