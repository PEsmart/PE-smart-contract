<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/18
 * Time: 14:24
 */
namespace app\api\controller;

use app\common\controller\Api;
use app\common\core\Get;
use think\Cookie;

class App extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    public $type = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->type=\think\Cookie::get('think_var');
    }

    public function info(){
        $path = ROOT_PATH.'public'.config('site.app_package');

        $about=Get::getLang("site.app_info_zh","site.app_info_en",$this->type);

        $data = array(
            'app_name' => config('site.app_name'),
            'icon'  => config('site.app_icon'),
            'app_version'  => config('site.app_version'),
            'app_package'  => config('site.app_package'),
            'package_size' => (!file_exists($path) ? 0 : round(filesize(iconv('UTF-8', 'GB2312', $path))/(1024*1024), 2)).'M',
            'app_ios_qrcode' => config('site.app_ios_qrcode'),
            'app_android_qrcode' => config('site.app_android_qrcode'),
            'about_us'=>config($about)
        );

        $this->success('', $data);
    }

    //前端获取多语言
    public function getLang(){
        //$type = input('get.type');
        //$lang = Cookie::get('think_var');
        //if (!$lang) {
        //    Cookie::set('think_var','en');
        //}
        $arr = [
            'menu',
            'register',
            'login',
            'currency',
            'mine',
            'find',
            'tradingcenter',
            'nodepower',
            'home',
            'prompt'
        ];
        $lang = null;
        foreach ($arr as $v) {
            $lang[$v] = __($v);
        }
        $this->success('success',$lang);
    }

    //切换语言
    public function setLanguage()
    {
        $type = $this->request->request('lang');
        if(empty($type)) $type = 'en';
        Cookie::set('think_var',$type,7600);
        $this->success($type,'','1');
    }
}