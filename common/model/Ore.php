<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/4/16
 * Time: 19:16
 */
namespace app\common\model;

use think\Db;
use think\Exception;
use think\Model;
use think\Validate;


class Ore Extends Model
{
// 表名
    protected $name = 'block_ore';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
//        'url',
    ];
}