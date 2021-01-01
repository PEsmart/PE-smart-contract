<?php
/**
 * 续约.
 * User: admin
 * Date: 2019/4/10
 * Time: 17:24
 */

namespace app\admin\model;

use think\Model;

class Renew extends Model
{

    // 表名
    protected $name = 'order_rc_log';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [];


}