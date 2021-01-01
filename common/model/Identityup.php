<?php

namespace app\common\model;

use think\Db;
//use think\Model;

//身份升级

class Identityup
{
    public static function autolevelup($uid){
        $thismember = \db("user_detail")->where("uid",$uid)->find();
        $jsstr = $uid;
        if(!empty($thismember['tjstr'])){
            $jsstr = $jsstr.','.$thismember['tjstr'];
        }
        $jsarr = explode(',',$jsstr);
        //获取身份等级
        $levels = \db('user_identity_level')->where('enabled',1)->order('level asc')->select();
        //获取自动升级的参数
        $data = getSys('identityup');

        $user = \db("user");
        $user_detail = \db("user_detail");

        foreach($jsarr as $mid) {
            $member = $user->where("id",$mid)->field('username,level,identity_level,team_usdt,lock_credit1,sf_lock_credit1')->find();
            $level = $member['identity_level'];
            foreach($levels as $key=>$item) {

                //条件三：自身锁定fc
                $need_fc =  $data['fc_num_'.$item['level']];
                $need_fc2 =  $need_fc - $member['sf_lock_credit1'];
                if ($level < $item['level']) { //小于下一个等级，判断是否可以升级
                    $canup = 0;
                    //获取条件一的自身需要达到的会员等级
                    $need_level = $data['level_'.$item['level']];
                    //获取条件二：自己端对的投资金额要达到 达到条件一、二就可升级
                    $need_usdt = $data['usdt_num_'.$item['level']];

                    if ($member['level'] >= $need_level && $member['team_usdt'] >= $need_usdt) {
                        $canup = 1;
                    }

                    if($canup==1){
                        //升级
                        $user->where('id',$mid)->update(['identity_level'=>$item['level'],'is_identity' => 0]);
                    }
                }

                //检测是否符合第三个条件
                $credit1 = db('user')->where('id',$mid)->where('is_identity',0)->value('credit1');
                if ($credit1 >= $need_fc2 && $need_fc2 > 0) {
                    setCc($member['username'],'credit1',-$need_fc2,'升级'.$item['levelname'].'锁定','Upgrade '.$item['levelname'].' lock','lockmoney');
                    db('user')->where('id',$mid)->update(['sf_lock_credit1' => $need_fc,'is_identity' => 1]);

                    //达到第三个条件，释放冻结的usdt
                    $lock_jdjl = db('user')->where('id',$mid)->value('lock_jdjl');
                    if ($lock_jdjl > 0) {
                        db('user')->where('id',$mid)->setDec('lock_jdjl',$lock_jdjl);
                        setCc($member['username'],'credit2',$lock_jdjl,'释放冻结的'.config('site.credit2_text'),'Release frozen '.config('site.credit2_text'),'jdjl');
                    }
                }
            }
        }

        return true;
    }



}
