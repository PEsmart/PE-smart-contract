<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2019-03-09
 * Time: 09:26
 */
namespace app\admin\model;

use think\Cache;
use think\Model;

class Feedback extends Model
{
	public function userDetail()
	{
		return $this->belongsTo('UserDetail', 'uid', 'uid', [], 'LEFT')->setEagerlyType(0);
	}

}

