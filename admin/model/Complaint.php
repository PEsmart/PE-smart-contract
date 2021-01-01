<?php

namespace app\admin\model;

use think\Model;

class Complaint extends Model
{

   // 表名
    protected $name = 'complaint';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
//        'prevtime_text',
//        'logintime_text',
//        'jointime_text'
    ];

    public function index()
    {
        // 链表操作
        // return $this->belongsTo('\app\admin\model\User', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
