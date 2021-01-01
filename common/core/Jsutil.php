<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/25
 * Time: 11:55
 */
namespace app\common\core;
use think\Db;

/*******************
 * 奖金结算
 */


class Jsutil
{

    /**
     * 获取当前结算的最大期数
     */
    public static function getPeriods(){

        $periods = db('bonus')
            ->value('max(periods)');

        if (empty($periods)){
            $periods = 1;
        }
        return intval($periods);
    }


    /**
     * 结算奖金
     */
    public static function do_js_bonus($data)
    {
        if ($data) {
            $db = \db('user_detail');
            $sql_arr = [];
            $user = $db
                ->alias('ud')
                ->join('user u', 'ud.uid=u.id')
                ->where("ud.uid > 0")
                ->column('ud.uid, u.username','u.id');

            foreach ($data as $val) {
                if (empty($user[$val['uid']])) return;

                $originbonus = $val['award'];
                $netIncome = $val['award']; # - $tax - $fljkf1 - $managefee;
                $state = 0; //状态：0正常，1停发

                //$sql_arr[] = "insert into fa_bonus(periods, uid, done, state, addtime, money, netincome, {$val['bkey']}, jsparams, source) values({$val['periods']}, '{$user['uid']}', 0, {$state}, {$val['time']}, {$originbonus}, {$netIncome}, {$val['award']}, '{$params}', '{$val['source']}')";
                $sql_arr[] = [
                    'periods' => $val['periods'],
                    'uid' => $val['uid'],
                    'done' => $state,
                    'addtime' => $val['time'],
                    'money' => $originbonus,
                    'netincome' => $netIncome,
                    $val["bkey"] => $val['award'],
                    'jsparams' => json_encode($val['params']),
                    'source' => $val['source']
                ];
            }
            if ($sql_arr) {
                db('bonus')->insertAll($sql_arr);
            }
        }

    }

}