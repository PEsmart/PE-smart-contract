<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2019-03-09
 * Time: 09:26
 */
namespace app\admin\model;

use think\Cache;
use think\Model;

class Tcmanage extends Model
{
    // 表名
    protected $name = 'tc_manage';
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

