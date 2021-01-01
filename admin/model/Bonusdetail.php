<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 18:44
 */
namespace app\admin\model;

use think\Model;

class Bonusdetail extends Model
{
    protected $table = 'fa_bonus';

    public function user()
    {
        return $this->belongsTo('\app\admin\model\User', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}