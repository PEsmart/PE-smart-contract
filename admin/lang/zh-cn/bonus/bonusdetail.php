<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 9:43
 */
//会员编号、会员用户名、总额、实得奖金、奖金1、奖金2、状态、来源
$fieldname=Db('bonus_type')->field('fieldname,bname')->select();
$name=[];
foreach ($fieldname  as $v){
    $name[$v['fieldname']] = $v['bname'];
}
return [
    'Periods'       =>  '期数',
    'Granttime'     =>  '结算时间',
    'Money'         =>  '总额',
    'Netincome'     =>  '实得奖金',
    'Done'          =>  '状态',
    'Processed'     =>  '已发',
    'Untreated'     =>  '未发',
    'Source'        =>  '来源',
    'F5'=> $name['f5'],
    'F6'=> $name['f6'],
    'F7'=> $name['f7'],
    'username'=> '用户名',
];