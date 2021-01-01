<?php

//配置文件
return [
    'exception_handle'        => '\\app\\api\\library\\ExceptionHandle',
    'page_rows'               => 10,
    'cc_type'                   => [
        '1' => ['title' => '可售cc币', 'key' => 'csell'],
        '2' => ['title' => '冻结cc币', 'key' => 'cfree'],
        '3' => ['title' => '锁定cc币', 'key' => 'clock'],
    ],
    'cc_order_state'        => [
        '1' => '待交易',
        '2' => '待付款',
        '3' => '待确认',
        '4' => '已完成',
        '10' => '申述中',
        '20' => '已取消',
        '30' => '已撤单',
    ],

];
