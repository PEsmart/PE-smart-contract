<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2019-03-09
 * Time: 09:26
 */
namespace app\admin\model;
use think\Model;
class Newmachine extends Model
{
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $table = 'fa_newmachine';

}

