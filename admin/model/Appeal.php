<?php
/**
 * 订单申诉模块.
 * User: Administrator
 * Date: 2019/4/8
 * Time: 14:06
 */

namespace app\admin\model;

use think\Model;

class Appeal extends Model
{

    // 表名
    protected $name = 'order_appeal_log';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [];
}