<?php
/**
 * 测试使用的接口.
 * User: admin
 * Date: 2019/7/17
 * Time: 10:24
 */

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 示例接口
 */
class Nozzle extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = '*';
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    //处理抢购数据
    public function handle(){
        require_once ROOT_PATH.'/handle.php';
    }
    //检测自动确认
    public function checkfinish(){
        require_once ROOT_PATH.'/checkfinish.php';
    }
    //检测到期
    public function checkore(){
        require_once ROOT_PATH.'/checkore.php';
    }
    //检测超过付款时间没付款的
    public function checkpaytime(){
        require_once ROOT_PATH.'/checkpaytime.php';
    }
    //结算收益
    public function getprofit(){
        require_once ROOT_PATH.'/getprofit.php';
    }
    //参数日志
    public function mininglogs(){
        require_once ROOT_PATH.'/mininglogs.php';
    }
    //发送短信
    public function sendsms(){
        require_once ROOT_PATH.'/sendsms.php';
    }

    //预约退还
    public function booking(){
        require_once ROOT_PATH.'/booking.php';
    }

    //检测订单是否存在问题
    public function checkorder(){
        require_once ROOT_PATH.'/checkorder.php';
    }

    //算力套餐日收益结算
    public function tcprofit(){
        require_once ROOT_PATH.'/tcprofit.php';
    }

    //算力套餐到期检测
    public function checktc(){
        require_once ROOT_PATH.'/checktc.php';
    }

    //检测买单两小时没付款取消订单
    public function tcfinish(){
        require_once ROOT_PATH.'/tcfinish.php';
    }
}