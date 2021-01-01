<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2019-03-11
 * Time: 14:02
 */
namespace app\admin\validate;

use think\Validate;

class Machine extends Validate
{
        protected $rule = [
            'name'  => 'require',
            'image'  => 'require',
            'price'  => 'require|',
            'power'  => 'require',
            'life'  => 'require',
            'status'  => 'require',
        ];

        protected $message = [
            'name'  => '名称不能为空',
            'image'  => '图片不能为空',
            'price'  => '',
            'power'  => '',
            'life'  => '',
            'status'  => '',
        ];

}