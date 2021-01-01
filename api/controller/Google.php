<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/10/16
 * Time: 13:59
 */

namespace app\api\controller;

use app\common\controller\Api;
use app\common\core\Procevent;

/**
 * 示例接口
 */
class Google extends Api
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

    //翻译
    public function translation()
    {
        $base = APP_PATH . 'api' . DS . 'lang'. DS;
        $fielname = DS.'app.php';

        $data = require_once $base.'zh-cn'.$fielname;

        $arr = null;
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $key => $val) {
                    $tk = $this->TL($val);
                    $res = $this->google($val,$tk);
                    $arr[$k][$key] = $res['text'];
                }
            }else{
                $tk = $this->TL($v);
                $res = $this->google($v,$tk);
                $arr[$k] = $res['text'];
            }
        }
        $path = 'en'.$fielname;
        file_put_contents($base.$path, '<?php' . "\n\nreturn " . var_export($arr, true) . ";");
        echo '翻译完成';

    }


    //静态TKK，动态获取请使用另外一个方法
    public function TKK()
    {
        $a = 561666268;
        $b = 1526272306;
        return 406398 . '.' . ($a + $b);
    }

    //直接复制google
    public function TL($a,$tkk=null)
    {
        if (!$tkk) {
            $tkk = explode('.', $this->TKK());
            $b = $tkk[0];
        }else{
            $b = $tkk;
        }

        for($d = array(), $e = 0, $f = 0; $f < mb_strlen ( $a, 'UTF-8' ); $f ++) {
            $g = $this->charCodeAt ( $a, $f );
            if (128 > $g) {
                $d [$e ++] = $g;
            } else {
                if (2048 > $g) {
                    $d [$e ++] = $g >> 6 | 192;
                } else {
                    if (55296 == ($g & 64512) && $f + 1 < mb_strlen ( $a, 'UTF-8' ) && 56320 == ($this->charCodeAt ( $a, $f + 1 ) & 64512)) {
                        $g = 65536 + (($g & 1023) << 10) + ($this->charCodeAt ( $a, ++ $f ) & 1023);
                        $d [$e ++] = $g >> 18 | 240;
                        $d [$e ++] = $g >> 12 & 63 | 128;
                    } else {
                        $d [$e ++] = $g >> 12 | 224;
                        $d [$e ++] = $g >> 6 & 63 | 128;
                    }
                }
                $d [$e ++] = $g & 63 | 128;
            }
        }
        $a = $b;
        for($e = 0; $e < count ( $d ); $e ++) {
            $a += $d [$e];
            $a = $this->RL ( $a, '+-a^+6' );
        }
        $a = $this->RL ( $a, "+-3^+b+-f" );
        $a ^= $tkk[1];
        if (0 > $a) {
            $a = ($a & 2147483647) + 2147483648;
        }
        $a = fmod ( $a, pow ( 10, 6 ) );
        return $a . "." . ($a ^ $b);
    }

    //直接复制google
    public function RL($a, $b)
    {
        for($c = 0; $c < strlen($b) - 2; $c +=3) {
            $d = $b{$c+2};
            $d = $d >= 'a' ? $this->charCodeAt($d,0) - 87 : intval($d);
            $d = $b{$c+1} == '+' ? $this->shr32($a, $d) : $a << $d;
            $a = $b{$c} == '+' ? ($a + $d & 4294967295) : $a ^ $d;
        }
        return $a;
    }
    public function charCodeAt($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');

        if (mb_check_encoding($char, 'UTF-8'))
        {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        }
        else
        {
            return null;
        }
    }

    public function shr32($x, $bits)
    {

        if($bits <= 0){
            return $x;
        }
        if($bits >= 32){
            return 0;
        }

        $bin = decbin($x);
        $l = strlen($bin);

        if($l > 32){
            $bin = substr($bin, $l - 32, 32);
        }elseif($l < 32){
            $bin = str_pad($bin, 32, '0', STR_PAD_LEFT);
        }

        return bindec(str_pad(substr($bin, 0, 32 - $bits), 32, '0', STR_PAD_LEFT));
    }

    //获取tkk
    public function getTkeys()
    {
        $content = file_get_contents('https://translate.google.cn');
        if(preg_match("#tkk\:\'((\d*)\.(\d*))',#isU", $content, $arr)){
            return $arr[1];
        }else{
            exit(json_encode(array('status' => 1, 'error' => 'keysArray is null!')));
        }
    }

    /**
     * 谷歌翻译
     * @param $text 翻译的内容
     * @param string $sl 转入内容的语言
     * @param string $tl 需要翻译成的语言
     *
     * @return array ['text' => '翻译后的语言','result' => '接口返回的完整数据']
     */
    public function google($text,$tk,$sl='zh-CN',$tl='en'){
        $url = 'https://translate.google.cn/translate_a/single?client=webapp&sl='.$sl.'&tl='.$tl.'&hl=zh-CN&dt=at&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=gt&otf=2&ssel=0&tsel=0&kc=1&tk='.$tk.'&q='.urlencode($text);
        $ifpost = 0;
        $datafields = '';
        $cookiefile = '';
        $v = false;
        //构造随机ip
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        $ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        //模拟http请求header头
        $header = array("Connection: Keep-Alive","Accept: text/html, application/xhtml+xml, */*", "Pragma: no-cache", "Accept-Language: zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3","User-Agent: Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; WOW64; Trident/6.0)",'CLIENT-IP:'.$ip,'X-FORWARDED-FOR:'.$ip);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $v);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $ifpost && curl_setopt($ch, CURLOPT_POST, $ifpost);
        $ifpost && curl_setopt($ch, CURLOPT_POSTFIELDS, $datafields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $cookiefile && curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        $cookiefile && curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
        curl_setopt($ch,CURLOPT_TIMEOUT,60); //允许执行的最长秒数
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $ok = curl_exec($ch);
        eval('$ok='.$ok.';');
        curl_close($ch);
        unset($ch);
        return ['text' => $ok[0][0][0],'result' => $ok];
    }
}