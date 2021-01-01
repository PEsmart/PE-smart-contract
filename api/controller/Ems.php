<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems as Emslib;
use app\common\model\User;
use think\Cookie;

/**
 * 邮箱验证码接口
 */
class Ems extends Api
{

    protected $noNeedLogin = ['send','check'];
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->type=\think\Cookie::get('think_var');

         $message1="验证码";
         $message2="Your code ";

        if($this->type=="en"){
            $message1="verification code";
            $message2="Your code ";
        }

        if($this->type=="zh-tw"){
            $message1="驗證碼";
            $message2="你的驗證碼是";
        }

        parent::_initialize();
        \think\Hook::add('ems_send', function($params) use($message1,$message2) {
            $obj = \app\common\library\Email::instance();
            $result = $obj
                    ->to($params->email)
                    ->subject($message1)
                    ->message($message2.":" . $params->code)
                    ->send();
            return $result;
        });
    }

    /**
     * 发送验证码
     *
     * @param string    $email      邮箱
     * @param string    $event      事件名称
     */
    public function send()
    {
        $email = $this->request->request("email");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';

        $last = Emslib::get($email, $event);
        if ($last && time() - $last['createtime'] < 60)
        {
            $this->error(__('Send frequently'));
        }
        if ($event)
        {

            $userinfo = User::getByEmail($email);
            if ($event == 'register' && $userinfo)
            {
                //已被注册
                $this->error(__('The mailbox  has been registered'));
            }
            else if (in_array($event, ['changeemail']) && $userinfo)
            {
                //被占用
                $this->error(__('The mailbox is already occupied'));
            }
            else if (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo)
            {
                //未注册
                $this->error(__('Email not registered'));
            }
        }
        $ret = Emslib::send($email, NULL, $event);
        if ($ret)
        {
            $message="发送成功";
            if($this->type=="en"){
                $message="Sent successfully";
            }
            if($this->type=="zh-tw"){
                $message="發送成功";
            }
            $this->success($message);
//            $this->success(__('Sent successfully'));
        }
        else
        {
            $this->error(__('Failed to send'));
        }
    }

    /**
     * 检测验证码
     *
     * @param string    $email      邮箱
     * @param string    $event      事件名称
     * @param string    $captcha    验证码
     */
    public function check()
    {
        $email = $this->request->request("email");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';
        $captcha = $this->request->request("captcha");

        if ($event)
        {
            $userinfo = User::getByEmail($email);
            if ($event == 'register' && $userinfo)
            {
                //已被注册
                $this->error(__('已被注册'));
            }
            else if (in_array($event, ['changeemail']) && $userinfo)
            {
                //被占用
                $this->error(__('已被占用'));
            }
            else if (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo)
            {
                //未注册
                $this->error(__('未注册'));
            }
        }
        $ret = Emslib::check($email, $captcha, $event);
        if ($ret)
        {
            $this->success(__('成功'));
        }
        else
        {
            $this->error(__('验证码不正确'));
        }
    }

}
