<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/10/10
 * Time: 17:43
 */

namespace app\admin\model;

use think\Model;

class Sellorder extends Model
{

    // 表名
    protected $name = 'sell_order';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [];
}