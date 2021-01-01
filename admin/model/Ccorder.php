<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14
 * Time: 11:17
 */
namespace app\admin\model;

use think\Model;

class Ccorder extends Model
{
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';

    protected $table = 'fa_cc_order';

    public function user()
    {
        return $this->belongsTo('\app\admin\model\User', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}


