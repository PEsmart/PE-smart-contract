<?php
/**
 * 抢购矿的相关操作.
 * User: Administrator
 * Date: 2019/3/30
 * Time: 9:15
 */

namespace app\api\controller\mining;

use app\common\controller\Api;
use app\common\model\Sms;
use EasyWeChat\Core\Exception;
use think\Db;

class  Mining extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['handle','sendSms','miningLogs','clearOnly','getOreResult','getSucData']; //['handle'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    // 分页参数
    // 一个页面显示几条数据
    protected $pagesize = 8;
    // 页码
    protected $page = 1;
    // 总页数
    protected $totalpage = 0;
    //订单表的实例化对象
    protected $db = null;


    public function __construct()
    {
        parent::__construct();
        //获取系统设置的每页显示的数据条数
        $ps = config('paginate.list_rows');
        if ($ps > 0) {
            $this->pagesize = $ps;
        }
        // 获取分页参数
        $this->page = input('get.page') ? intval(input('get.page')) : $this->page;
        //实例化订单表的对象
        $this->db = db('ore_order');
    }

    //清除限制 测试使用
    public function clearOnly(){
        $redis = new \Redis();
        $redis->connect(config('redis.host'),config('redis.port'));
        $keys = $redis->keys('ol*');
        if ($keys) {
            foreach ($keys as $val) {
                $redis->del($val);
            }
        }
        $redis->close();
    }

    //抢矿
    public function index(){

        $level = input('get.level/d',0);

        if ($level > 0) {
            $tm = time();
            //获取期数 年月日+等级
            $periods = getPer($level);

            //抢购的数据先写入redis缓存
            $redis = new \Redis();
            $redis->connect(config('redis.host'),config('redis.port'));

            //当天结束时间
            $expireTime = mktime(23, 59, 59, date("m"), date("d"), date("Y"));

            //判断是否有该等级的信息，如果有，则不用去查询，没有则进行查询
            $lvs = json_decode($redis->hGet('block_level','lvs'.$level),true);
            if (!$lvs) {
                $lvs = db('block_ore_level')->where('level',$level)->field('stime,etime,money2,level,id')->find();
                $lstr = json_encode($lvs);
                $redis->hSet('block_level','lvs'.$level,$lstr);
                //设置等级信息当天有效
                $redis->expireAt('block_level', $expireTime);
            }


            //倒计时结束，处于抢购中的时间段
            if (date('His',$tm) >= date('His',$lvs['stime']) && date('His',$tm) <= date('His',$lvs['etime'])) {
                //可以抢购

                //一天一个矿只能点击一次立即抢购
                if ($redis->hGet('ol'.$periods,$this->auth->username)) {
                    $this->error(__('该'.config('site.ore_text').'今天已抢完，请明天再来~'));
                }else{
                    $redis->hSet('ol'.$periods,$this->auth->username,$this->auth->username);
                }

                try{
                    $goods_info = [
                        'id' => $this->auth->id,
                        'username' => $this->auth->username,
                        'weights' => $this->auth->weights,
                        'level' => $level,
                        'periods'   => date('Ymd').$level,
                        'times' => microtime(true)
                    ];
                    $redis->hSet($periods,$this->auth->username,json_encode($goods_info));

                    $this->success('您抢购的意向已记录，5分钟后会出结果，请留意！');
                }catch(Exception $e){
                    $this->error('服务器错误，请重试！',$e->getMessage()) ;
                }

            }
            else{
                if (date('His',$tm) < date('His',$lvs['stime'])) {
                    $res['status'] = 0;
                    $this->error('还没到抢购时间',$res);
                }
                if (date('His',$tm) > date('His',$lvs['etime'])) {
                    $res['status'] = -1;
                    $this->error('错过了抢购时间',$res);
                }
            }
            $redis->close();
        }else{
            $this->error('参数错误');
        }
    }

    //查询抢购的结果
    public function getOreResult(){
        $redis = new \Redis();
        $redis->connect(config('redis.host'),config('redis.port'));
        //判断数据是否处理完成
        $wc = $redis->get('dqwc');
        $run = $redis->get('runing');
        if ($wc == 1 && $run == 1) {
            $level = input('post.level/d',0);
            $periods = getPer($level);
            $ishas = $redis->get($periods.'_wc_'.$this->auth->username);
            if ($ishas == $this->auth->username) {
                //精确到微秒的暂停 usleep()单位是微秒，1秒 = 1000毫秒 ，1毫秒 = 1000微秒，即1微秒等于百万分之一秒
                usleep(mt_rand(1000,1000000));
                $res = db('ore_order')->where('buy_username',$this->auth->username)->where('periods',$periods)->field('id')->find();
                if ($res) {
                    $this->success(__('抢购成功'),$ishas);
                }else{
                    $this->success(__('抢购失败'),null,2);
                }
            }else{
                $this->success(__('抢购失败'),null,2);
            }
        }else{
            $this->error(__('正在处理数据，请稍后再查询'));
        }
    }

}