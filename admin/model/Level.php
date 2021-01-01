<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 9:28
 */

namespace app\admin\model;

use think\Model;

class Level extends Model
{

    // 表名
    protected $name = 'user_level';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        // 'prevtime_text',
        // 'logintime_text',
        // 'jointime_text'
    ];
}