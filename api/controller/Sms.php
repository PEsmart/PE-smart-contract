<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Sms as Smslib;
use app\common\model\User;


/**
 * 手机短信接口
 */
class Sms extends Api
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 发送短信 (简信/聚合)
     */
    public function send()
    {
        if ($this->request->isPost()) {
            $data = $this->getpm();
            if (empty($data)) {
                $this->error(__('非法提交！'));
            }
            $arr = ['mobile'];
            $this->ret_isset($data,$arr);
            $mobile = $data['mobile'];
            $event = empty($data['event'])?'register':$data['event'];//默认为发送验证码 否则传事件参数
            if (!$mobile || !\think\Validate::regex($mobile, "^^1\d{10}$")) {
                $this->error(__('手机号不正确'));
            }

            $rds = rds();
            // 一分钟发送一次
            if (time() - $rds->get('cz_'.$mobile) < 60) {
                $this->error(__('发送频繁'));
            }else{
                $rds->set('cz_'.$mobile,time());
            }

            $code = mt_rand(1000,9999);
            $ret = Smslib::send($mobile, $code,$event);
            if ($ret) {
                if($event == 'register'){
                    // 验证码保存2分钟，验证成功后会删除，重新发送也会覆盖
                    $rds->set('code_'.$mobile,$code,300);
                }
                $rds->close();
                $this->success(__('Successful delivery'));
            } else {
                $rds->del('cz_'.$mobile);
                $rds->close();
                $this->error(__('fail in send'));
            }

        }
    }

    /**
     * 发送验证码 旧的
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
//    public function send($mobile,$event='register')
//    {
//        if ($event == 'register') {
//            $mobile = $this->request->request("mobile") ? $this->request->request("mobile") : $mobile;
//            $event = $this->request->request("event") ? $this->request->request("event") : $event;
//        }
//
//        if (!$mobile || !\think\Validate::regex($mobile, "^^1\d{10}$")) {
//            $this->error(__('手机号不正确'));
//        }
//        //$last = Smslib::get($mobile, $event);
//        //if ($last && time() - $last['createtime'] < 60) {
//        //    $this->error(__('发送频繁'));
//        //}
//        //$ipSendTotal = \app\common\model\Sms::where(['ip' => $this->request->ip()])->whereTime('createtime', '-1 hours')->count();
//        //if ($ipSendTotal >= 5) {
//        //    $this->error(__('发送频繁'));
//        //}
//        //
//        //if ($event) {
//        //    $userinfo = User::getByMobile($mobile);
//        //    if ($event == 'register' && $userinfo) {
//        //        //已被注册
//        //        $this->error(__('已被注册'));
//        //    } else if (in_array($event, ['changemobile']) && $userinfo) {
//        //        //被占用
//        //        $this->error(__('已被占用'));
//        //    } else if (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
//        //        //未注册
//        //        $this->error(__('未注册'));
//        //    }
//        //}
//        $ret = Smslib::send($mobile, NULL, $event);
//        if ($ret) {
//            if ($event == 'register') {
//                $this->success(__('发送成功'));
//            }else{
//                echo __('发送成功');
//            }
//        } else {
//            if ($event == 'register') {
//                $this->error(__('发送失败'));
//            }else{
//                echo __('发送失败');
//            }
//        }
//    }

    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    //public function sendb($mobile)
    //{
    //
    //    if (!$mobile || !\think\Validate::regex($mobile, "^1[3|4|5|6|7|8|9]\d{9}$")) {
    //        $this->error(__('手机号不正确'));
    //    }
    //    $ret = Smslib::sendb($mobile, NULL, '');
    //}

    //订单变动信息
    /*public function sendb($mobile,$event='buy')
    {
        //$mobile = $this->request->request("mobile");
        //$event = $this->request->request("event");
        //$event = $event ? $event : 'buy';

        if (!$mobile || !\think\Validate::regex($mobile, "^^1\d{10}$")) {
            echo __('手机号不正确');
        }
        $ret = Smslib::sendb($mobile, NULL,$event);
        if ($ret) {
            echo __('发送成功');
        } else {
            echo __('发送失败');
        }
    }*/

    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    //public function sendc($mobile)
    //{
    //
    //    if (!$mobile || !\think\Validate::regex($mobile, "^1[3|4|5|6|7|8|9]\d{9}$")) {
    //        $this->error(__('手机号不正确'));
    //    }
    //    $ret = Smslib::sendc($mobile, NULL, '');
    //}



    /**
     * 检测验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     * @param string $captcha 验证码
     */
    public function check()
    {
        $mobile = $this->request->request("mobile");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';
        $captcha = $this->request->request("captcha");
        if (!$mobile || !\think\Validate::regex($mobile, "^1[3|4|5|6|7|8|9]\d{9}$")) {
            $this->error(__('手机号不正确'));
        }
        //if ($event) {
        //    $userinfo = User::getByMobile($mobile);
        //    if ($event == 'register' && $userinfo) {
        //        //已被注册
        //        $this->error(__('已被注册'));
        //    } else if (in_array($event, ['changemobile']) && $userinfo) {
        //        //被占用
        //        $this->error(__('已被占用'));
        //    } else if (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
        //        //未注册
        //        $this->error(__('未注册'));
        //    }
        //}
        $ret = Smslib::check($mobile, $captcha, $event);
        if ($ret) {
            $this->success(__('成功'));
        } else {
            $this->error(__('验证码不正确'));
        }
    }

    function juhecurl($url,$params=false,$ispost=0){
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        if( $ispost )
        {
            curl_setopt( $ch , CURLOPT_POST , true );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
            curl_setopt( $ch , CURLOPT_URL , $url );
        }
        else
        {
            if($params){
                curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
            }else{
                curl_setopt( $ch , CURLOPT_URL , $url);
            }
        }
        $response = curl_exec( $ch );
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
        curl_close( $ch );
        return $response;
    }

    // 短信验证码校验
    public static function vfCode($mobile,$code)
    {
        if(!config('vfCode_enable')) return true;
        $rds = rds();
        if ($code && $rds->get('code_'.$mobile) == $code) {
            // 验证成功，删除缓存的验证码
            $rds->del('code_'.$mobile);
            return true;
        }else{
            return false;
        }
    }


}
