<?php
namespace app\common\core;
class BonusSettle
{
    /**
     * 购物奖/推荐收益奖
     * - 结算每天机器人收益的时候触发
     */
    public static function do_gwj($rs,$periods)
    {
        $bkey = 'gwj';
        //redis数据出队操作,从redis中将请求取出
        $redis = new \Redis();
        $redis->connect(config('redis.host'), config('redis.port'));
        $gwj = $redis->get('szxz_' . $bkey);  //节省开支
        if (!$gwj) {
            $gwj = db('bonus_type')->where('bkey', $bkey)->where('isopen', 1)->find();
            $redis->set('szxz_' . $bkey, serialize($gwj), 600);
        } else {
            $gwj = unserialize($gwj);
        }
        if (!$gwj)return;//未开启购物奖
        $bonusdata = unserialize($gwj['data']);
        $num = count($bonusdata['rank']);  //代数

        foreach ($rs as $vvv){
            if ($vvv['uid']) {
                $tjstr = db('user_detail')->where('uid', $vvv['uid'])->value('tjstr');
                if ($tjstr) {  //
                    $tjarr = explode(',', $tjstr);
                    $tjarr = array_slice($tjarr, 0, $num);   //结算代数
                    if ($tjarr){
                        foreach ($tjarr as $k=>$v){  //$k+1即使代数
                            $username =db('user')->where('id',$v)->value('username');
                            $percent = $bonusdata['rate'][$k];
                            $rank = $k+1;
                            $award =round($vvv['netincome'] *$percent/100,2);
                            $source = $gwj['bname'].':会员'.$username.'获得来自'.$vvv['username'].'的第'.$rank.'代的奖金，计算公式：'.$vvv['netincome'].'*'.$percent.'%='.$award.'CSQA';
                            $s_e ='Invitation reward:'.'Member '.$username.' receives '.$rank.'-generation bonus from '.$vvv['username'].'，computational formula ：'.$vvv['netincome'].'*'.$percent.'%='.$award.'CSQA';
                            $s_t = '推薦收益獎勵'.':會員'.$username.'獲得來自'.$vvv['username'].'的第'.$rank.'代的獎金，計算公式：'.$vvv['netincome'].'*'.$percent.'%='.$award.'CSQA';
                            $jsparams =  array('bkey'=>$bkey, 'bval'=>$award);
                            Procevent::do_js_bonus( $v,$bkey,$award,time(),$periods,$jsparams,$source,$gwj['fieldname'],$s_e,$s_t);
                        }
                    }
                }

            }

        }

    }

    public static function recommendBonus($uid){

        $thismember = \db("user_detail")->where("uid",$uid)->find();
        $thisUser = \db("user")->where("id",$uid)->find();
        $jsarr = explode(',',$thismember['tjstr']);
        if(count($jsarr)>0){
            $jsarr=array_slice($jsarr,0,3);
            foreach($jsarr as $a=>$mid){
                $sys = \app\common\model\Config::getSetting();
                $bonus=json_decode($sys['recommend_bonus']);
                $user=\db('user')->where('id',$mid)->find();
                switch($a){
                    case 0:
                        $pid="一代";
                        $a=1;
                        break;
                    case 1:
                        $pid="二代";
                        $a=2;
                        break;
                    case 2:
                        $pid="三代";
                        $a=3;
                        break;
                }
                $arr=[];
                foreach($bonus as $k=>$value){
                    $arr[$k]=$value;
                }

                $rate=[];
                foreach (config('site.exchange_wallet_rate') as $k=>$v){
                    $rate['key'] = $k;
                    $rate['value'] = $v;
                }


//                $amount=round($arr[$pid]/($rate['key']/$rate['value']),2);
//                $csqa=intval($arr[$pid])+intval($user['credit1']);
//                \db('user')->where('id',$mid)->update(['credit1'=>$csqa]);
//                setCc($user['username'],'credit1',intval($arr[$pid]),'推荐注册成功，奖励 '.$arr[$pid].' '.config('site.credit1_text'));
//                $source = "推荐奖金".':会员'.$user['username'].'获得来自'.$thisUser['username'].'的第'.$pid.'的奖金，'."计算公式：".$arr[$pid]."/"."(".$rate['key']."/".$rate['value'].")".'='.$amount;
                setCc($user['username'],'credit1',intval($arr[$pid]),'推荐注册成功，奖励 '.$arr[$pid].''."CSQA",'Recommended registration success, reward '.$arr[$pid].''."CSQA","推薦註冊成功，獎勵".$arr[$pid].''."CSQA");

                $source = "推荐奖金".':会员'.$user['username'].'获得来自'.$thisUser['username'].'的第'.$pid.'的奖金'.$arr[$pid]."CSQA";
                $source_en="Referral bonus".": member ".$user['username']."obtained from ".$thisUser['username']." ".$a."-"."generation"."bonus ".$arr[$pid].""."CSQA";
                $source_tw="推薦獎金".":會員".$user['username']."获得来自".$thisUser['username']."的第".$pid."獎金"."CSQA";;
                $data=['uid'=>$mid,'done'=>1,'state'=>1,'addtime'=>time(),'granttime'=>time(),'money'=>$arr[$pid],'netincome'=>$arr[$pid],'f5'=>$arr[$pid],'source'=>$source,'source_en'=>$source_en,'source_tw'=>$source_tw];

                \db('bonus')->insert($data);
            }
        }
        return true;
    }

    //推荐奖
    public static function do_ztj($rs,$periods){

//        var_dump($rs);//调试
        $uid=intval($rs['id']);
        //uid购买或者升级节点算力套餐的会员
        $result=\db('bonus_type')->where('bkey','ztj')->find();
        $ztj=unserialize($result['data']);
        $thisMember=db('user')->alias('u')->join('user_detail d',"u.id=d.uid")->where('id',$uid)->find();
        $this_member_level=$thisMember['level'];
        $recommendMember=\db('user')->where('id',$thisMember['tjid'])->find();
//        var_dump($recommendMember);
        $recommend_member_level=$recommendMember['level'];

        $key=array_search("$recommend_member_level",$ztj['level']);
        $ztj_rate=$ztj['rate'][$key]/100;

        $nowTcManage=\db('tc_manage')->where('level',$this_member_level)->find();
//        $lastTcManage=\db('tc_manage')->where('level',$this_member_level-1)->find();

//        if ($lastTcManage) {
//            $last_price = $lastTcManage['price'];
//        } else {
//            $last_price = 0;
//        }
//        $bonus=round(($nowTcManage['price']-$last_price)*$ztj_rate,2);

        $bonus=$rs['money']*$ztj_rate;
        $bonus=round($bonus,2);
        $remark="获得会员"."【".$thisMember['email']."】"."购买"."【".$nowTcManage['name_cn']."】"."的".$bonus."USDT"."奖金";
        $remark_en="Get Members"." "."[".$thisMember['email']."]"." "."purchase"." "."[".$nowTcManage['name_en']."]"." ".$bonus."USDT"." "."bonus";
        setCc($recommendMember['username'],'credit2',$bonus,$remark,$remark_en,"bonus");

        $user=db('user')->where('id',$uid)->find();




        $data=['uid'=>$recommendMember['id'],'done'=>1,'state'=>1,'addtime'=>time(),'granttime'=>time(),'money'=>$bonus,'netincome'=>$bonus,'f5'=>$bonus,'source'=>$remark,'source_en'=>$remark,"periods"=>$periods,"changed_credit2"=>$user['credit2']];
        \db('bonus')->insert($data);

    }

    /**
     * 社群算力
     * @param $data 二维数组，[['id'=>1,'money'=>1]]
     * @param $periods 期数
     *
     * @return bool
     */
    public static function do_sqsl($data,$periods){
        if ($data) {
            $bkey = 'sqsl';
            //获取该奖金的相关设置
            $set = db('bonus_type')->where('bkey',$bkey)->field('data')->find();
            if (empty($set)) {
                return false;
            }
            $barr = null;
            $set = unserialize($set['data']);


            //获取同级奖设置
            //获取该奖金的相关设置
            $tjjfc = db('bonus_type')->where('bkey','tjj_fc')->field('data')->find();
            $tjjusdt = db('bonus_type')->where('bkey','tjj_usdt')->field('data')->find();
            if ($tjjfc) {
                $tjjfc = unserialize($tjjfc['data']);
            }
            if ($tjjusdt) {
                $tjjusdt = unserialize($tjjusdt['data']);
            }

            $ddb = db('user_detail');

            $redis = rds();
            //获取所有用户的tjstr
            //if ($redis->keys('udetail_*')) {
                $dinfo = $ddb->where('uid','>',0)->field('uid,tjstr')->select();
                if ($dinfo) {
                    foreach ($dinfo as $val) {
                        $redis->set('udetail_'.$val['uid'],$val['tjstr']);
                        $redis->expire('udetail_'.$val['uid'],7200);
                    }
                }
                $dinfo = null;
            //}
            //统计直推人数
            //if (!$redis->keys('tjnum_*')) {
                $tjarr = db()->query('select count(ud.tjid) as tjnum,ud.tjid FROM fa_user as u LEFT JOIN fa_user_detail as ud on u.id = ud.uid where u.id > 0 and u.level > 1 GROUP BY ud.tjid');
                if ($tjarr) {
                    foreach ($tjarr as $val) {
                        if ($val['tjid'] > 0) {
                            $redis->set('tjnum_'.$val['tjid'],$val['tjnum']);
                            //保存两个小时
                            $redis->expire('tjnum_'.$val['tjid'],7200);
                        }
                    }
                }
                $tjarr = null;
            //}

            //获取用户信息
            //if (!$redis->keys('u_*')) {
                $uinfo = db('user')->where('id','>',0)->field('id,username,is_identity,level,identity_level,credit1,credit2')->select();
                foreach ($uinfo as $val) {
                    $redis->set('u_'.$val['id'],serialize($val));
                    $redis->expire('u_'.$val['id'],7200);
                }
                $uinfo = null;
            //}

            //奖金记录数组
            $bonus = null;
            $bfc = null;
            $busdt = null;
            $user = null;
            $log = null;


            $tm = time();
            $num = 0;
            foreach ($data as $val) {
                //不存在就去读表
                if (!$redis->get('udetail_'.$val['id'])) {
                    $tjstr = $ddb->where('uid',$val['id'])->value('tjstr');
                    $redis->set('udetail_'.$val['id'],$tjstr);
                    $redis->expire('udetail_'.$val['id'],7200);
                }
                $tjstr = $redis->get('udetail_'.$val['id']);
                if ($tjstr) {
                    $arr = explode(',',$tjstr);
                    $ds = 1;
                    //记录同级奖的
                    $tfc = null;
                    $tusdt = null;
                    foreach ($arr as $v) {
                        $tjnum = $redis->get('tjnum_'.$v);

                        if ($tjnum > 0) {
                            $cs = null;
                            if (isset($set[$ds])) {
                                $cs = $set[$ds];
                            }else{
                                //如果在配置中找不到对应的推荐代数，那么直接拿配置的最后一个为准
                                $cs = $set[count($set)];
                            }

                            if ($cs) {
                                //如果直推人数小于代数指定的推荐人数
                                if ($tjnum < $cs['zt']) {
                                    $ds++;
                                    continue;
                                }
                                $us2 = unserialize($redis->get('u_'.$v));
                                // 注册会员不能拿
                                if ($us2['level'] < 2) {
                                    $ds++;
                                    continue;
                                }
                                //如果代数大于10，则需要满足特定的条件
                                if ($ds > 10 && $us2['level'] < 6 && $us2['identity_level'] < 4 && $us2['is_identity'] != 1) {
                                    $ds++;
                                    continue;
                                }
                                //计算奖金
                                $award = $val['money'] * floatval($cs['bl']) / 100;
                                if ($award > 0) {
                                    $num ++;
                                    $us = unserialize($redis->get('u_'.$val['id']));
                                    $source = '获得社群算力，来源于'.$us['username'].'计算公式：'.$val['money'].'*'.floatval($cs['bl']).'/100='.$award;
                                    $source_en = 'Getting community power comes from '.$us['username'].'. Calculating formula:'.$val['money'].'*'.floatval($cs['bl']).'/100='.$award;
                                    //用户累加
                                    $user[] = 'update fa_user set credit1 = credit1+'.$award.' where id = '.$v;
                                    //日志
                                    $log[] = [
                                        'username' => $us2['username'],
                                        'type' => 'credit1',
                                        'project' => 'sqsl',
                                        'num' => $award,
                                        'remark' => '获得社群算力奖金',
                                        'remark_en' => 'Get the Community Power Bonus',
                                        'createtime' => $tm,
                                        'updatetime' => $tm
                                    ];
                                    //奖金记录
                                    $bonus[] = ['periods' => $periods,'uid'=>$v,'done'=>1,'state'=>1,'addtime'=>$tm,'granttime'=>$tm,'money'=>$award,'netincome'=>$award,'f6'=>$award,'source'=>$source,'source_en'=>$source_en,'changed_credit1' => $us2['credit1']+$award];

                                    $us2['credit1'] = $us2['credit1']+$award;
                                    $redis->set('u_'.$v,serialize($us2));
                                    $redis->expire('u_'.$v,7200);

                                    //同级奖
                                    if ($ds <= 10) {
                                        if (isset($tfc[$us2['identity_level']]) && $tfc[$us2['identity_level']]) {
                                            foreach ($tfc[$us2['identity_level']] as $tt) {
                                                $perfc = isset($tjjfc[$us2['identity_level']]) ? $tjjfc[$us2['identity_level']] : 0;
                                                $perusdt = isset($tjjusdt[$us2['identity_level']]) ? $tjjusdt[$us2['identity_level']] : 0;

                                                $mfc = $tt['money'] * $perfc / 100;
                                                $musdt = $tt['money'] * $perusdt / 100;
                                                if ($mfc > 0) {
                                                    $source = '获得同级奖-FC，来源于'.$tt['username'].'计算公式：'.$tt['money'].'*'.floatval($perfc).'/100='.$mfc;
                                                    $source_en = 'Received the FC level award, from '.$tt['username'].'. Calculating formula:'.$tt['money'].'*'.floatval($perfc).'/100='.$mfc;
                                                    //用户累加
                                                    $user[] = 'update fa_user set credit1 = credit1+'.$mfc.' where id = '.$v;
                                                    //日志
                                                    $log[] = [
                                                        'username' => $us2['username'],
                                                        'type' => 'credit1',
                                                        'project' => 'tjj_fc',
                                                        'num' => $mfc,
                                                        'remark' => '获得同级奖-FC',
                                                        'remark_en' => 'Received the FC level award',
                                                        'createtime' => $tm,
                                                        'updatetime' => $tm
                                                    ];
                                                    //奖金记录
                                                    $bfc[] = ['periods' => $periods,'uid'=>$v,'done'=>1,'state'=>1,'addtime'=>$tm,'granttime'=>$tm,'money'=>$mfc,'netincome'=>$mfc,'f8'=>$mfc,'source'=>$source,'source_en'=>$source_en,'changed_credit1' => $us2['credit1']+$mfc];

                                                    $us2['credit1'] = $us2['credit1']+$mfc;
                                                    $redis->set('u_'.$v,serialize($us2));
                                                    $redis->expire('u_'.$v,7200);
                                                }

                                                if ($musdt > 0) {
                                                    $source = '获得同级奖-USDT，来源于'.$tt['username'].'计算公式：'.$tt['money'].'*'.floatval($perusdt).'/100='.$musdt;
                                                    $source_en = 'Received the USDT level award, from '.$tt['username'].'. Calculating formula:'.$tt['money'].'*'.floatval($perusdt).'/100='.$musdt;
                                                    //用户累加
                                                    $user[] = 'update fa_user set credit2 = credit2+'.$musdt.' where id = '.$v;
                                                    //日志
                                                    $log[] = [
                                                        'username' => $us2['username'],
                                                        'type' => 'credit2',
                                                        'project' => 'tjj_usdt',
                                                        'num' => $musdt,
                                                        'remark' => '获得同级奖-USDT',
                                                        'remark_en' => 'Received the USDT level award',
                                                        'createtime' => $tm,
                                                        'updatetime' => $tm
                                                    ];
                                                    //奖金记录
                                                    $busdt[] = ['periods' => $periods,'uid'=>$v,'done'=>1,'state'=>1,'addtime'=>$tm,'granttime'=>$tm,'money'=>$musdt,'netincome'=>$musdt,'f9'=>$musdt,'source'=>$source,'source_en'=>$source_en,'changed_credit2' => $us2['credit2']+$musdt];

                                                    $us2['credit2'] = $us2['credit2']+$musdt;
                                                    $redis->set('u_'.$v,serialize($us2));
                                                    $redis->expire('u_'.$v,7200);
                                                }
                                            }
                                        }

                                        $tfc[$us2['identity_level']][] = ['username' => $us2['username'],'money' => $award];
                                    }
                                }
                                if ($ds > 10 && $us2['level'] >= 6 && $us2['identity_level'] >= 4 && $us2['is_identity'] == 1) {
                                    //超过10代，只允许那个一个
                                    break;
                                }
                            }
                        }
                        $ds++;
                    }
                }

                if ($num >= 2000) {
                    //批量处理
                    if ($user) {
                        db()->batchQuery($user);
                        $user = null;
                    }
                    if ($bonus) {
                        db('bonus')->insertAll($bonus);
                        $bonus = null;
                    }
                    if ($bfc) {
                        db('bonus')->insertAll($bfc);
                        $bonus = null;
                    }
                    if ($busdt) {
                        db('bonus')->insertAll($busdt);
                        $busdt = null;
                    }
                    if ($log) {
                        db('cc_detail_log')->insertAll($bonus);
                        $bonus = null;
                    }
                    $num = 0;
                }

            }


            //批量处理
            if ($user) {
                db()->batchQuery($user);
                $user = null;
            }
            if ($bonus) {
                db('bonus')->insertAll($bonus);
                $bonus = null;
            }
            if ($bfc) {
                db('bonus')->insertAll($bfc);
                $bonus = null;
            }
            if ($busdt) {
                db('bonus')->insertAll($busdt);
                $busdt = null;
            }
            if ($log) {
                db('cc_detail_log')->insertAll($log);
                $bonus = null;
            }
            $redis->close();
        }
    }

    //节点奖励
    public static function do_jdjl($data,$periods){
        if ($data) {
            //获取参数设置
            $set = db('bonus_type')->where('bkey','jdjl')->find();
            $dt = unserialize($set['data']);
            $tjstr = db('user_detail')->where('uid',$data['id'])->value('tjstr');
            $sinfo = db('user')->where('id',$data['id'])->field('username')->find();
            if ($tjstr) {
                $tjarr = explode(',',$tjstr);
                $db = db('user');
                $cha = 0;
                $tm = time();
                foreach ($tjarr as $val) {
                    //获取会员信息
                    $info = $db->where('id',$val)->field('username,identity_level,is_identity,credit2')->find();
                    if (isset($dt[$info['identity_level']]) && $dt[$info['identity_level']] > 0) {
                        $per = $dt[$info['identity_level']] - $cha;
                        if ($per > 0) {
                            $award = $data['money'] * $per / 100;
                            if ($info['is_identity']) {
                                //达标直接发放
                                setCc($info['username'],'credit2',$award,'节点奖励','Node reward','jdjl');
                            }else{
                                //不达标，先放到冻结字段
                                setCc($info['username'],'lock_jdjl',$award,'节点奖励(冻结)','Node reward (freeze)','jdjl');
                            }
                            $source = '下级'.$sinfo['username'].'购买算力套餐，获得节点奖励。计算公式：'.$data['money'].'*('.$dt[$info['identity_level']].'-'.$cha.')/100='.$award;
                            $source_en = 'Subordinate '.$sinfo['username'].' buys arithmetic packages and gets nodal rewards. Formula: '.$data['money'].'*('.$dt[$info['identity_level']].'-'.$cha.')/100='.$award;
                            db('bonus')->insert(['periods' => $periods,'uid'=>$val,'done'=>1,'state'=>1,'addtime'=>$tm,'granttime'=>$tm,'money'=>$award,'netincome'=>$award,'f7'=>$award,'source'=>$source,'source_en'=>$source_en,'changed_credit2' => $info['credit2']+$award]);
                            $cha = $dt[$info['identity_level']];
                        }
                    }
                }
            }
        }
    }
}
