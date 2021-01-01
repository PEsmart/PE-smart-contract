<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 11:14
 */

//配置文件
return [
    'exception_handle'        => '\\app\\api\\library\\ExceptionHandle',
    'page_rows'               => 10,
    'cc_type'                   => [
        '1' => ['title' => '可售cc币', 'key' => 'csell'],
        '2' => ['title' => '冻结cc币', 'key' => 'cfree'],
        '3' => ['title' => '锁定cc币', 'key' => 'clock'],
    ],
];