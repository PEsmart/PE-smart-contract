<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 20:41
 */
namespace app\api\controller;

use app\common\controller\Api;

/**
 * 测试调用
 */
class Test extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    /**
     * 到时完成
     */
    public function confirm_order(){
        require ROOT_PATH . '/confirmorder.php';
    }

    /**
     * 到时取消
     */
    public function lose_order(){
        require ROOT_PATH . '/loseorder.php';
    }

    public function test(){
        echo "<pre>";
        $redis = rds();
        $server_name = Config('redis_header')?$_SERVER['SERVER_NAME'].'_':'';
        $d = date('Ymd');
        $t = $redis->hGetall($server_name.'user_tz');
        $sy = $redis->hGetall($server_name.'user_sy');
        $day_sy = $redis->hGetall($server_name.$d.'_sy');
//        foreach($t as $v=>$ts){
//            $add_sy = $ts*0.01/8640;
//            echo $v.'---'.$ts.' 静态奖励增加 ='.$add_sy."\r\n";
//            echo '缓存静态收益='.($sy[$v]+$add_sy)."\r\n";
//        }
        var_dump($_SERVER['HTTP_HOST'],$t,$sy,$day_sy);
    }
    public function delhase(){
        $redis = rds();
        $server_name = Config('redis_header')?$_SERVER['SERVER_NAME'].'_':'';
        $redis->Del($server_name.'user_tz');
        $redis->Del($server_name.'user_sy');
    }

    public function reGrantSy(){
        $redis = rds();
        $d = date('Ymd');
        $errs = $redis->hGetAll('error_sy');
        echo "<pre>";
        var_dump($errs);
        foreach($errs as $user_id => $sy){
            $lua = <<<LUA
            local user_id = KEYS[1]
            local day_sy_key = KEYS[2]
            local add_sy = ARGV[1]
            local tz = redis.call('hGet','user_tz',user_id)
            if tz ~= false
            then
                tz = tz*1
                if tonumber(tz) > 0
                then
                    local day_sy = redis.call('hGet',day_sy_key,user_id)
                    add_sy = add_sy*1
                    if day_sy == false
                    then
                        redis.call('hIncrByFloat','user_sy',user_id,add_sy)
                        redis.call('hIncrByFloat',day_sy_key,user_id,add_sy)
                    else
                        local new_sy = day_sy + add_sy
                        local max_sy = tz*0.1
                        day_sy = day_sy*1
                        if tonumber(day_sy) < tonumber(max_sy)
                        then
                            if tonumber(new_sy) <= tonumber(max_sy)
                            then
                                redis.call('hIncrByFloat','user_sy',user_id,add_sy)
                                redis.call('hIncrByFloat',day_sy_key,user_id,add_sy)
                            else
                                local add_sy = max_sy - day_sy
                                redis.call('hIncrByFloat','user_sy',user_id,add_sy)
                                redis.call('hSet',day_sy_key,user_id,max_sy)
                            end
                        end
                    end
                end
            end
            return true
LUA;
            $res = $redis->eval($lua,[$user_id,$d.'_sy',$sy],2);
            if($res === false){
                echo $user_id.'失败!!!';
            }else{
                echo $user_id.'成功!!!';
                $redis->hDel('error_sy',$user_id);
            }
        }
    }

}