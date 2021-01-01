<?php

namespace app\admin\model;

use think\Model;

class Recharge extends Model
{
    // 表名
    protected $name = 'recharge_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    

    







    public function user()
    {
        return $this->belongsTo('User', 'mid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
