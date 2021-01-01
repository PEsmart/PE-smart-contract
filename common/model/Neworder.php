<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/4/16
 * Time: 19:16
 */
namespace app\common\model;
use think\Model;

class Neworder Extends Model
{
// 表名
    protected $name = 'order';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}