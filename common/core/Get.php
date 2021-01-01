<?php


namespace app\common\core;


class Get
{

    public  static function getLang($msg,$msg_en,$lang){
        if ($lang=="en"){
            return $msg_en;
        }else{
            return $msg;
        }
    }
}