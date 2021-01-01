<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/9/29
 * Time: 11:51
 */
namespace app\admin\model;

use think\Model;

class Identity extends Model
{

    // 表名
    protected $name = 'user_identity_level';
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