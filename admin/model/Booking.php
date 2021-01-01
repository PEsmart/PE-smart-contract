<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/5/16
 * Time: 18:02
 */

namespace app\admin\model;

use think\Model;

class Booking extends Model
{

    // 表名
    protected $name = 'booking_log';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [];
}