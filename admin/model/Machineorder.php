<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14
 * Time: 9:53
 */
namespace app\admin\model;

use think\Model;

class Machineorder extends Model
{
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $table = 'fa_machine_order';

    public function userDetail()
    {
        return $this->belongsTo('UserDetail', 'uid', 'uid', [], 'LEFT')->setEagerlyType(0);
    }

    public function machine()
    {
        return $this->belongsTo('Machine', 'machineid', 'id', [], 'LEFT')->setEagerlyType(0);
    }



}