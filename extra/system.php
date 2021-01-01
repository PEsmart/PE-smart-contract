<?php

use think\Env;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22
 * Time: 18:15
 */
return [
    // 显示层数
    'floor'         => 4,
    // 显示直推数量
    'psize'         => 10,
    // 市场数, 0为太阳线
    'pos_cnt'       => 0,
    //排单的种类
    'weights'       =>[
        '0' => '禁止排单',
        '1' => '普通排单',
        '2' => '优先排单'
    ],
];