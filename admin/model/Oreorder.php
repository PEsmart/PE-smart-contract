<?php
/**
 * 矿订单
 * User: Administrator
 * Date: 2019/4/3
 * Time: 16:10
 */

namespace app\admin\model;

use think\Model;

class Oreorder extends Model
{

    // 表名
    protected $name = 'ore_order';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [];

}
