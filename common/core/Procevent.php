<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/25
 * Time: 11:51
 */
namespace app\common\core;
use think\Db;
/*******************
 * 奖金发放
 */

class Procevent
{
    /**
     * 触发奖金
     */
    public static function dsell_event($rs, $type){
        $periods = Jsutil::getPeriods()+1;

        switch ($type){
            case 'gwj': #(购物奖）推荐收益奖
                BonusSettle::do_gwj($rs,$periods);
                self::do_grant_bonus();
                break;

            case 'ztj':
                /** $rs=购买者的uid、套餐差价money  */
                BonusSettle::do_ztj($rs,$periods);
                break;
            case 'sqsl':
                /** $rs为数组 社群算力奖  */
                BonusSettle::do_sqsl($rs,$periods);
                break;
            case 'jdjl':
                /** $rs为数组 节点奖励  */
                BonusSettle::do_jdjl($rs,$periods);
                break;

        }
    }


    /**
     * 结算奖金
     * -credit1：奖金发放的币种
     */
    public static function do_grant_bonus()
    {
        //限制为本次执行如果没有执行完的话，其他人不能调用，等到本次执行完成在执行下一个要执行的
        $redis = new \Redis();
        $redis->connect(config('redis.host'),config('redis.port'));
        if ($redis->get('grant') == 1) {
            sleep(1);
            self::do_grant_bonus();
            return ;
        }else{
            $redis->set('grant',1);
        }

        $bonus_list = db('bonus')
            ->field('id, uid, netincome, source,f2,f4,f5')
            ->where('done=0 and state=0')->select();
        $bonus_arr = [];
        $log_arr = [];
        $user_arr = [];
        $tm = time();
        //表头
        $prefix = config('database.prefix');
        $user = db('user')->where('id','>',0)->column('id,username,level','id');

        /*
         * 两种奖金发credit1，订单日收益发credit2
         * 且f2,f4,f5 3者每次之后一个会大于0
         */

        foreach ($bonus_list as $bonus){
            if (floatval($bonus['f4'])>0){
                $moneytype='credit2';
            }else{
                $moneytype='credit1';
            }

            if (isset($user_arr[$bonus['uid']][$moneytype] )) {
//                $user_arr[$bonus['uid']] = [
//                    $moneytype => $user_arr[$bonus['uid']][$moneytype] + $bonus['netincome']
//                ];

                $user_arr[$bonus['uid']][$moneytype] = $user_arr[$bonus['uid']][$moneytype] + $bonus['netincome'];
            }else{
                $user_arr[$bonus['uid']][$moneytype] = $bonus['netincome'];
            }

            $log_arr[] = [
                'username' => $user[$bonus['uid']]['username'],
                'type' => $moneytype,
                'num' => $bonus['netincome'],
                'remark' => $bonus['source'],
                'remark_en' => $bonus['source_en'],
                'remark_tw' => $bonus['source_tw'],
                'createtime' => $tm,
                'updatetime' => $tm
            ];

            $bonus_arr[] = 'update '.$prefix.'bonus set granttime='.$tm.',done=1 where done=0 and state=0 and id='.$bonus['id'];
            if (count($bonus_arr) == 1000) {
                //批量处理
                if ($user_arr) {
                    $arr = [];
                    foreach ($user_arr as $k=>$v) {
                        if (count($v)==2){
                            $arr[] = 'update '.$prefix.'user set credit1 = credit1 +'.$v['credit1'].',credit2=credit2+'.$v['credit2'].' where id='.$k;
                        }else{
                            $arr[] = 'update '.$prefix.'user set '.key($v).' = '.key($v).'+'.current($v).' where id='.$k;
                        }
                    }

                    if ($arr) {
                        db('user')->batchQuery($arr);
                    }
                }
                if ($bonus_arr) {
                    db('bonus')->batchQuery($bonus_arr);
                }
                if ($log_arr) {
                    db('cc_detail_log')->insertAll($log_arr);
                }

                $user_arr = [];
                $bonus_arr = [];
                $log_arr = [];
            }
        }

        //批量处理
        if ($user_arr) {
            $arr = [];

            foreach ($user_arr as $k=>$v) {
                if (count($v)==2){
                    $arr[] = 'update '.$prefix.'user set credit1 = credit1 +'.$v['credit1'].',credit2=credit2+'.$v['credit2'].' where id='.$k;
                }else{
                    $arr[] = 'update '.$prefix.'user set '.key($v).' = '.key($v).'+'.current($v).' where id='.$k;
                }
            }
            if ($arr) {
                db('user')->batchQuery($arr);
            }
        }
        if ($bonus_arr) {
            db('bonus')->batchQuery($bonus_arr);
        }
        if ($log_arr) {
            db('cc_detail_log')->insertAll($log_arr);
        }

        $redis->del('grant');
        $redis->close();
    }

    public static function do_js_bonus($mid, $bkey, $bonus, $modtime, $periods, $jsparams, $source,$filename,$s_e,$s_t){
        Db::startTrans();
        $user = db('user_detail ud')
            ->field('ud.*, u.username')
            ->join('user u', 'ud.uid=u.id')
            ->where("ud.uid",$mid)
            ->find();
        if (!$user) return;

        $tax = 0;//手续费
        $managefee = 0;//管理费
        $originbonus = $bonus;
        $netIncome = $bonus-$tax-$managefee;//实发奖金
        $state = 0; //状态：0正常，1停发

        //查询是否已经生成当前期数
        $where=['uid'=>$user['uid'],'periods'=>$periods,$filename=>['>',0]];
        $bonus_info = db('bonus')->where($where)->find();
        if ($bonus_info) {
            $source = $bonus_info['source'] . '\n' . $source;
            $s_e = $bonus_info['source_en'] . '\n' . $s_e;
            $s_t= $bonus_info['source_tw'] . '\n' . $s_t;
            if (!empty($bonus_info['jsparams'])) {
                $params = json_decode($bonus_info['jsparams'], TRUE);
                $jsparams = $params + $jsparams; //数组合并
            }
            $params = json_encode($jsparams);

            $sql = "update fa_bonus set money=cast(money as DECIMAL(9,2))+cast({$originbonus} as DECIMAL(9,2)), netincome=cast(netincome as DECIMAL(9,2))+cast({$originbonus} as DECIMAL(9,2)), {$filename}=CAST({$filename} as DECIMAL(9,2))+CAST({$bonus} as DECIMAL(9,2)), state={$state}, jsparams='{$params}', source='{$source}',source_en='{$s_e}',source_tw='{$s_t}' where id = {$bonus_info['id']}";
        }else {
            $params = json_encode($jsparams);
            $sql = "insert into fa_bonus(periods, uid, done, state, addtime, money, netincome, {$filename}, jsparams, source,source_en,source_tw) values({$periods}, '{$user['uid']}', 0, {$state}, {$modtime}, {$originbonus}, {$netIncome}, {$bonus}, '{$params}','{$source}','{$s_e}','{$s_t}')";
        }
        db('bonus')->execute($sql);  //执行
        Db::commit();

    }


}