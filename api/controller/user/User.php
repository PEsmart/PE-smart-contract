<?php

namespace app\index\controller\user;

use Think\Controller;
use think\Db;
use Think\Model;

use app\common\model\User as Userb  ;

/**
 * 会员中心
 */
class User
{
    /*
     * 实名认证
     * */
    public function isreal(){
        $data['creditid'] = input('creditid');
        $data['credittype'] = 1; // 默认为身份证类型
        $match = preg_match("/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/", $data['creditid'], $matches);
        if($match>0){
            $res = [
                'alipayact' => input('alipayact'),
                'wechatact' => input('wechatact'),
                'realname' => input('realname'),
                'bankact' => input('bankact'),
                'bankphone' => input('bankphone'),
                'bankarea' => input('bankarea'),
                'bankname' => input('bankname'),
                'interbank' => input('interbank'),
                'paypwd' => input('paypwd'),
                'creditid' => $data['creditid'],
                'credittype' => $data['credittype'],
                'isreal'=> 1,
            ];
//            $data = db('user_b')->fetchSql(true)->insert($res);
            $info = db('user_b')
                ->where('uid',input('id'))
                ->update($res);
            if($info>0){
                return json_encode(array('msg'=>'202'));
            }
        }else{
            return json_encode(array('msg'=>'301'));
        }
    }
    /*
     * 找回密码
     * */
    public function backpwd(){

    }

    public function test()
    {
       var_dump(123);
    }

    static public function isrealVerity($uid){
        $user = User::get($uid);

//        $user->


    }

    public function province(){

        echo 1;die;

    }


}
