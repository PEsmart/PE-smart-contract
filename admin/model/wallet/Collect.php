<?php

namespace app\admin\model\wallet;

use think\Model;

class Collect extends Model
{
    // 表名
    protected $name = 'wallet_collect';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [


    ];
    

    







}
