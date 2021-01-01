<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/18
 * Time: 10:11
 */

namespace app\admin\model;

use think\Db;
use think\Model;
use think\Config;

class Initialization extends Model
{
    public function index()
    {
        // 获取extra文件夹中的inisql.php文件中的数据
        $data = Config::get('initsql');
        if(!empty($data)){
            $prefix = config("database.prefix");
            foreach($data as $val){
                $sql = 'truncate table '.$prefix.$val.';';
                $this->query($sql);
            }
        }
        \db('config')->where('name','kc_balance')->update(['value'=>0]);
        \db('config')->where('name','kc_jackpot')->update(['value'=>0]);
        \db('config')->where('name','kc_ensure')->update(['value'=>6000]);
        \db('config')->where('name','kc_restart_num')->update(['value'=>3]);
        $redis = rds();
//        $redis->flushAll();
        $delArr = ['user_tz','user_sy'];
        date('Ymd');
        foreach($delArr as $item){
            $redis->del($item);
        }
        $redis->del(date('Ymd').'_sy');
        $redis->close();

    }
}