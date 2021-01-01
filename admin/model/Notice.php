<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 14:57
 */

namespace app\admin\model;

use think\Model;

class Notice extends Model
{

    // 表名
    protected $name = 'notice';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
//        'prevtime_text',
//        'logintime_text',
//        'jointime_text'
    ];

    // 获取所有的公告分类,会员首页 mh 交易中心tc
    public function getCategory($type = null){
        // 获取所有的分类，以id作为对应数组的键名
        if(empty($type)){
            return db('notice_category')->column('id,name,createtime','id');
        }else{
            return db('notice_category')->where('type',$type)->column('id,name,createtime','id');
        }
    }

}