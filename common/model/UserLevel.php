<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/2
 * Time: 11:36
 */
namespace app\common\model;

use think\Model;

class UserLevel extends Model
{
    //表名
    protected $table = 'user_level';

    /**
     * 获取level, levenname数组
     */
    public static function getKeyValues()
    {
       $data = db('user_level')
                    ->field('level, levelname')
                    ->order('id asc')
                    ->select();
       return $data;
    }

    /**
     * 获取等级（根据等级名称）
     */
    public static function getLevel($levelname = ''){
        $level = db('user_level')
                    ->field('level')
                    ->where("levelname='{$levelname}'")
                    ->find();
        return isset($level['level']) ? $level['level'] : '';
    }

}