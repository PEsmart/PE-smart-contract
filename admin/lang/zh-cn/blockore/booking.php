<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/5/16
 * Time: 18:18
 */

$lvs = db('block_ore_level')->where('id','>',0)->column('level,levelname','level');

return [
    'periods'           => '期数',
    'username'           => '用户编号',
    'level'           => '等级',
    'levelname'           => '等级名称',
    'credit1'           => config('site.credit1_text'),
    'status'           => '状态',
    'createtime'           => '创建时间',
    'level_json'            => json_encode($lvs,JSON_UNESCAPED_UNICODE),
];