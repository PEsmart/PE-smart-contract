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

class Question extends Model
{

	public function category()
	{
		return $this->belongsTo('Category', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
	}
}

