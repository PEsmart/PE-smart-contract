<?php
/**
 * 预约相关操作.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 16:17
 */
namespace app\api\controller\booking;

use app\common\controller\Api;


class  Booking extends Api{
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
    //预约记录表的实例化对象
    protected $db = null;


    public function __construct(){
        parent::__construct();
        //获取系统设置的每页显示的数据条数
        $ps = config('paginate.list_rows');
        if($ps > 0){
            $this->pagesize = $ps;
        }
        // 获取分页参数
        $this->page = input('get.page') ? intval(input('get.page')) : $this->page;
        //实例化预约记录表的对象
        $this->db = db('booking_log');
    }

    //获取预约记录
    public function getLog(){
        $username = $this->auth->username;
        if ($username) {
            //通过页码计算应该从第几条记录开始拿数据
            $index = ($this->page - 1) * $this->pagesize;

            //获取记录列表
            $list = $this->db->where('username',$username)->limit($index,$this->pagesize)->order('createtime desc')->select();
            if ($list) {
                $list = collection($list)->toArray();
            }
            //获取总页数
            $totalpage = $this->db->where('username',$username)->count('id');
            $res['data'] = $list;
            //把页码和总页数回传给前端
            $res['page'] = $this->page;
            $res['totalpage'] = $totalpage;
            $this->success(__('success'),$res);
        }else{
            $this->error(__('Please login'));
        }
    }

    //会员预约
    public function setBooking(){

        //预约矿的等级
        $level = input('get.level/d',0);

        if ($this->auth->level == 1) {
            $this->error(__("注册会员不能预约"));
        }else{
            //非注册会员要实名认证了之后并且支付信息绑定之后才能进行抢购的
            $ispay = db('user_detail')->where('uid',$this->auth->id)->field('isreal,alipayact,wechatact,bankact')->find();
            if (empty($ispay['isreal'])) {
                $this->error(__('您还没有实名认证，不能进行预约！'));
            }
            if (empty($ispay['alipayact']) && empty($ispay['wechatact']) && empty($ispay['bankact'])) {
                $this->error(__('您还没有绑定支付信息，不能进行预约！'));
            }
        }

        if ($level > 0) {
            //预约时间
            $time = time();

            //提取该等级的矿的开采时间，如果当前时间超过了开采时间，则不能预约
            $orelv = db('block_ore_level')->where('level',$level)->field('level,stime,money')->find();
            //需要单独拿出 时分 出来比较，不能直接拿读取到的时间戳来比较
            //后台有一个设置提前几分钟结束预约的设置，在这里需要做对应的处理
            $bstime = config('site.booking_stop_time') * 60;

            //拿开始抢购的时间减去提前结束的分钟数就是预约截止的时间
            $stime = date('Hi',$orelv['stime']-$bstime);
            $time2 = date('Hi',$time);
            if ($stime > $time2) {
                //时间段没有问题，进行矿机的检测，如果矿机不够，也不能预约
                if ($this->auth->credit1 < $orelv['money']) {
                    $this->error(__('Miner shortage'));
                }else{
                    //预约成功，扣除对应的矿机
                    //获取当前交易的期数，数据格式：年月日+预约矿的开采时段的开始时间+预约矿的等级
                    //例如：预约矿的开采时段为：16:30-17:30,预约矿等级为 2 当前时间为：2019年3月29日 组合成的期数为：20190329+1630+2=2019032916302
                    //$periods = getPer($orelv);
                    //预约期数改为当前日期加上等级
                    $periods = getPer($level);
                    $ishas = $this->db->where('periods',$periods)->where('username',$this->auth->username)->find();
                    if (empty($ishas)) {
                        $data = [
                            'periods' => $periods, // 期数
                            'username' => $this->auth->username, // 用户编号
                            'level' => $orelv['level'], // 矿的等级
                            'credit1' => $orelv['money'], // 预约消耗的矿机
                            'createtime' => $time // 预约时间
                        ];
                        $this->db->insert($data);
                        //预约成功，减去用户对应的矿机
                        setCc($this->auth->username,'credit1',-$orelv['money'],'预约成功，扣除'.config('site.credit1_text').'：'.$orelv['money']);
                        //用户预约的矿成功，消耗的矿机累加到给公司
                        //获取公司编号
                        /*$comp = db('user')->where('iscomp',1)->field('username')->find();
                        if (!empty($comp)) {
                            setCc($comp['username'],'credit1',$orelv['money'],$this->auth->username.'预约 '.$level.' 级'.config('site.ore_text').'成功，公司获得：'.$orelv['money'].config('site.credit1_text'));
                        }*/
                        $this->success(__('Successful appointment'));
                    }else{
                        $this->error(__("You have already made an appointment. You can't make another appointment."));
                    }

                }
            }else{
                //不在可预约的时间段内，不能预约
                $this->error(__("You can't make an appointment unless it's within the stipulated time."));
            }

        }else{
            $this->error(__('Parameter error'));
        }
    }

}