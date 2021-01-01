<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2019-03-09
 * Time: 09:26
 */
namespace app\admin\model;
use think\Model;
class Neworder extends Model
{
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $table = 'fa_order';

    public function user(){
       return $this->hasOne('User', 'id', 'uid', [], 'LEFT')->setEagerlyType(0);
    }

    public  function machine(){
        return $this->hasOne('Newmachine', 'id', 'm_id', [], 'LEFT')->setEagerlyType(0);
    }
}

