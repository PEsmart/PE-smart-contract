<?php

namespace app\admin\model;

use think\Model;

class Withdrawal extends Model
{
    // 表名
    protected $name = 'cash_withdrawal';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'approval_time_text'
    ];
    



    public function getApprovalTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['approval_time']) ? $data['approval_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setApprovalTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

//    public  function user(){
//        return $this->belongsTo('User','uid','id',[],'LEFT')->setEagerlyType(0);
//    }

//
//    public function user()
//    {
//        return parent::hasOne('User','id','uid')->field('username,id');
//    }

    public function user()
    {
            return $this->belongsTo('\app\admin\model\User', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }


}
