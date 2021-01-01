<?php
/**
 * 矿的相关信息.
 * User: Administrator
 * Date: 2019/3/30
 * Time: 9:46
 */

namespace app\api\controller\ore;

use app\common\controller\Api;
use fast\Date;

class  Ore extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
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
        $this->page = input('get.page') ? intval(input('get.page'))
            : $this->page;
        //实例化矿记录表的对象
        $this->db = db('block_ore');
    }

    //获取矿的所有等级
    public function getOreLevel(){

        $redis = new \Redis();
        //暂时这样做来出来处理A6 和本地的切换
        $ips = explode('.',request()->ip());
        //if ($ips[0] == '192') {
        //    $redis->connect('192.168.105.2',6379);
        //}else{
            $redis->connect(config('redis.host'),config('redis.port'));
        //}
        //判断用户是否可以抢购
        $redis->set('isreal'.$this->auth->username,null);
        if (!$redis->get('isreal'.$this->auth->username)) {
            //应该是注册会员不可以抢矿，而且非注册会员要实名认证了之后并且支付信息绑定之后才能进行抢购的
            if ($this->auth->level <= 1) {
                $this->error(__("注册会员不能抢购"));
            }else{
                //非注册会员要实名认证了之后并且支付信息绑定之后才能进行抢购的
                $ispay = db('user_detail')->where('uid',$this->auth->id)->field('isreal,alipayact,wechatact,bankact')->find();
                if (empty($ispay['isreal'])) {
                    $this->error(__('您还没有实名认证，不能进行抢购！'));
                }
                if (empty($ispay['alipayact']) && empty($ispay['wechatact']) && empty($ispay['bankact'])) {
                    $this->error(__('您还没有绑定支付信息，不能进行抢购！'));
                }
            }
            $redis->set('isreal'.$this->auth->username,1);
        }

        if (request()->isGet()) {
            //当天结束时间，redis缓存保存一天
            $expireTime = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
            $levels = null;

                //没有缓存，去拿
                //获取矿的所有等级
                $levels = db('block_ore_level')->field('id,level,levelname,images,min_price,max_price,stime,etime,money,money2,days,per,credit2,credit4')->where('status',1)->select();
                $levels = collection($levels)->toArray();
                $uname = $this->auth->username;

                //根据预约记录表检测会员对每个等级矿的预约情况，组合矿的等级数据一起返回给前端
                //后台有一个设置提前几分钟结束预约的设置，在这里需要做对应的处理
                $bstime = config('site.booking_stop_time') * 60;

                if (!empty($levels)){
                    foreach ($levels as &$val) {
                        //预约期数
                        $periods = date('Ymd').$val['level'];

                        $val['level_id'] = 'lvid'.$val['id'];
                        //查询预约记录，判断会员是否已经预约
                        $ishas = db('booking_log')->field('id')->where('periods',$periods)->where('username',$uname)->find();

                        if (!empty($ishas)) {
                            //已经预约
                            $val['isbooking'] = 1;
                        }else{
                            //没有预约，可预约
                            $val['isbooking'] = 0;
                        }

                        //拿开始抢购的时间减去提前结束的分钟数就是预约截止的时间
                        //$t = strtotime(date('Ymd ').date('Hi',$val['stime']));
                        $stime = date('Hi',$val['stime']-$bstime);
                        $time2 = date('Hi',time());

                        if ($time2 >= $stime) {
                            //预约时间大于截止时间，不能预约
                            //$val['isbooking'] = 2;
                            //抢购的截止时间，如果大于抢购的截止时间，不能抢购,不能预约
                            $etime = date('Hi',$val['etime']);
                            if ($time2 >= $etime) {
                                //大于抢购的截止时间，不能抢购，不能预约，或者没有矿机
                                $val['isbooking'] = 3;
                            }else{
                                //不能预约，但是可以抢购
                                $val['isbooking'] = 4;
                            }
                        }

                        //一般情况下不需要这个判断
                        /*$ore = $this->db->where('level',$val['level'])->where('status',1)->where('status2',0)->select();
                        if (empty($ore)) {
                            // 矿已经抢完，不能抢购也不能预约
                            $val['isbooking'] = 3;
                        }*/

                        if ($val['level'] > 0) {
                            $tm = time();
                            //距离开始还剩几分钟，前端显示倒计时
                            $cdtime = config('site.count_down_time') * 60;
                            $lvs = db('block_ore_level')->where('level',$val['level'])->field('stime,etime,id')->find();
                            $time = date('His',$tm + $cdtime);
                            $val['cd_time'] = strtotime(date('Ymd ').date('His',$lvs['stime'] - $cdtime));
                            //进入倒计时
                            if ($time >= date('His',$lvs['stime']) && date('His',$tm) <= date('His',$lvs['stime'])) {
                                //把剩余时间返回给前端
                                $res = Date::span($tm,strtotime(date('Ymd '.date('His',$lvs['stime']))),'minutes,seconds');
                                $val['minutes'] = $res['minutes'];
                                $val['seconds'] = $res['seconds'];
                                //状态为0 表示还在倒计时
                                if ($res['minutes'] == 0 && $res['seconds'] == 0) {
                                    //1表示倒计时结束，开始抢购
                                    $val['cd_status'] = 1;
                                    //$this->success('开始抢购',$res);
                                }else{
                                    //0 表示还在倒计时
                                    $val['cd_status'] = 0;
                                    //$this->success('倒计时',$res);
                                }
                            }
                            else{
                                //倒计时结束，处于抢购中的时间段
                                if (date('His',$tm) >= date('His',$lvs['stime']) && date('His',$tm) <= date('His',$lvs['etime'])) {
                                    //倒计时结束，处于抢购中的时间段，可以抢购
                                    $val['cd_status'] = 2;
                                    $val['minutes'] = 0;
                                    $val['seconds'] = 0;
                                    //$this->success('抢购中',$res);
                                }else{
                                    //不在该时间段内，不能抢购
                                    $val['cd_status'] = -1;
                                    $val['minutes'] = 0;
                                    $val['seconds'] = 0;
                                    //$this->success('错过了抢购的时间',$res);
                                }
                            }
                        }
                    }
                    unset($val);
                }

            $this->success(__('success'),$levels);
        }
        $redis->close();
    }

    //抢矿倒计时
    public function countDown(){
        $level = input('post.level/d',0);
        if (request()->isPost()) {
            if ($level > 0) {
                $res = [];
                $tm = time();
                //距离开始还剩几分钟，前端显示倒计时
                $cdtime = config('site.count_down_time') * 60;
                $lvs = db('block_ore_level')->where('level',$level)->field('stime,etime,id')->find();
                $time = date('His',$tm + $cdtime);
                //进入倒计时
                if ($time >= date('His',$lvs['stime']) && date('His',$tm) <= date('His',$lvs['stime'])) {
                    //把剩余时间返回给前端
                    $res = Date::span($tm,strtotime(date('Ymd '.date('His',$lvs['stime']))),'minutes,seconds');
                    //状态为0 表示还在倒计时
                    if ($res['minutes'] == 0 && $res['seconds'] == 0) {
                        //1表示倒计时结束，开始抢购
                        $res['status'] = 1;
                        $this->success('开始抢购',$res);
                    }else{
                        //0 表示还在倒计时
                        $res['status'] = 0;
                        $this->success('倒计时',$res);
                    }
                }else{
                    //倒计时结束，处于抢购中的时间段
                    if (date('His',$tm) >= date('His',$lvs['stime']) && date('His',$tm) <= date('His',$lvs['etime'])) {
                        //倒计时结束，处于抢购中的时间段，可以抢购
                        $res['status'] = 2;
                        $res['minutes'] = 0;
                        $res['seconds'] = 0;
                        $this->success('抢购中',$res);
                    }else{
                        //不在该时间段内，不能抢购
                        $res['status'] = -1;
                        $res['minutes'] = 0;
                        $res['seconds'] = 0;
                        $this->success('错过了抢购的时间',$res);
                    }
                }
            }else{
                $this->error('参数错误');
            }
        }
    }
}