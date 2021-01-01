<?php
/**
 * 收益出售
 * User: admin
 * Date: 2019/4/12
 * Time: 14:12
 */
namespace app\admin\model;

use think\Model;

class Sell extends Model
{

    // 表名
    protected $name = 'profit_sell_log';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    // 追加属性
    protected $append = [];


}