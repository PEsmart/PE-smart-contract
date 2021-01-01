<?php
/**
 * 收益出售.
 * User: admin
 * Date: 2019/4/12
 * Time: 10:43
 */

namespace app\api\controller\order;

use app\common\controller\Api;

class Sell extends Api
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
    // 从第几条数据开始
    protected $index = 0;
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
        $this->index = ($this->page - 1) * $this->pagesize;

        //实例化矿记录表的对象
        $this->db = db('profit_sell_log');
    }

    //收益出售申请
    public function setSellLog(){
        if (request()->isPost()) {
            $num = input('post.num/d',0);

            $minprice = db('block_ore_level')->where('status',1)->order('min_price asc')->field('min_price')->find();
            if ($num < $minprice['min_price']) {
                $this->error(__('出售数量不能小于'.config('site.ore_text').'的最小价格'));
            }

            //获取该用户是否存在待审核，如果有则不给申请
            $ishas = $this->db->where('username',$this->auth->username)->where('status',0)->field('id')->find();
            if ($ishas) {
                $this->error(__('您还有一条申请在待审核中，不能再次申请！'));
            }

            $level = db('block_ore_level')->where('status',1)->where('min_price','<=',$num)->order('min_price asc')->field('level')->find();
            if (empty($level)) {
                $this->error(__('出售数量不能小于'.config('site.ore_text').'的最小价格'));
            }

            $level = db('block_ore_level')->where('status',1)->where('max_price','>=',$num)->order('max_price asc')->field('level')->find();
            if (empty($level)) {
                $this->error(__('出售数量不能超过'.config('site.ore_text').'的最大价格'));
            }


            if ($num > 0) {
                $info = db('user')->where('username',$this->auth->username)->where('credit3','>=',$num)->field('id')->find();
                if (empty($info)) {
                    $this->error(__('出售收益不能大于现有收益'));
                }

                $per = date('Ymd');
                $ishas = $this->db->where('periods',$per)->where('username',$this->auth->username)->field('id')->find();
                if ($ishas) {
                    $this->error(__('一天只能申请一次，请明天再来'));
                }
                $data = [
                    'periods' => date('Ymd'),
                    'username' => $this->auth->username,
                    'num' => $num,
                    'level' => $level['level'],
                    'status' => 0,
                    'createtime' => time()
                ];
                $this->db->insert($data);
                $this->success(__('操作成功，等待系统审核'));
            }else{
                $this->error(__('数量不能小于等于0'));
            }
        }else{
            $this->error(__('非法请求'));
        }
    }

    //获取出售申请记录
    public function getSellLog(){
        //获取记录
        $log = $this->db->where('username',$this->auth->username)->limit($this->index,$this->pagesize)->select();
        $total = $this->db->where('username',$this->auth->username)->count('id');
        $totalpage = ceil($total/$this->pagesize);

        //获取等级的信息
        $lvs = db('block_ore_level')->where('status',1)->field('level,levelname,min_price,max_price')->select();
        //获取等级的最大值
        $maxprice = db('block_ore_level')->where('status',1)->order('max_price desc')->field('max_price')->find();
        $data = [
            'max_price' =>$maxprice,
            'level' => $lvs,
            'data' => $log,
            'page' => $this->index,
            'totalpage' => $totalpage,
        ];
        $this->success(__('返回成功'),$data);
    }
}