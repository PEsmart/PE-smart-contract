<?php
/**
 * 区块矿等级
 * User: Administrator
 * Date: 2019/3/27
 * Time: 19:41
 */

namespace app\admin\model;

use think\Model;

class Orelevel extends Model
{

    // 表名
    protected $name = 'block_ore_level';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [];
}