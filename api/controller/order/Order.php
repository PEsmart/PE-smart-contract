<?php
/**
 * 订单
 * User: Administrator
 * Date: 2019/4/2
 * Time: 10:33
 */
namespace app\api\controller\order;

use app\common\controller\Api;
use app\common\core\Procevent;
use app\common\library\Auth;
use think\Db;

class Order extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['getProfit','check_ore','check_paytime','check_finish'];
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
        $this->db = db('ore_order');
    }

    //获取订单记录
    protected function getOrder($status,$type){
        $fieldname = 'id,periods,ordersn,level,pcp,total_money,buy_username,sell_username,sell_ordersn,days,per,money,money2,credit2,credit4_per,credit4,credit5,images,success_time,pay_etime,pay_time,lock_stime,lock_etime,wc_time,due_time,delay_time,delay_credit5,status,status2,status3,status4,status5';
        $data = null;
        $totalpage = 0;

        //获取等级信息
        $lvs = db('block_ore_level')->where('status',1)->column('id,level,levelname,stime,etime','level');
        if ($status == 0 && $type == 'buy') {
            //抢矿中的状态
            $data = $this->db
                ->where(function($qr){
                    $qr->where('status',0)->whereOr('status',1);
                })
                ->where('buy_username',$this->auth->username)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',0)
                ->where('status5',0)
                ->order('success_time desc')
                ->field($fieldname)->limit($this->index,$this->pagesize)->select();

            if (!empty($data)) {
                $data = collection($data)->toArray();
            }
            //数据总条数
            $totalpage = $this->db
                ->where(function($qr){
                    $qr->where('status',0)->whereOr('status',1);
                })
                ->where('buy_username',$this->auth->username)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',0)
                ->where('status5',0)
                ->order('success_time desc')
                ->count('id');
        }elseif ($status == 2 && $type == 'buy') {
            //收益中
            $data = $this->db
                ->where('buy_username',$this->auth->username)
                ->where('status',$status)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',0)
                ->where('status5',0)
                ->order('success_time desc')
                ->field($fieldname)->limit($this->index,$this->pagesize)->select();
            if (!empty($data)) {
                $data = collection($data)->toArray();
            }
            //数据总条数
            $totalpage = $this->db
                ->where('buy_username',$this->auth->username)
                ->where('status',$status)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',0)
                ->where('status5',0)
                ->order('success_time desc')
                ->count('id');
        }elseif($status == 1 && $type == 'buy_appeal'){
            //申诉中
            $data = $this->db
                ->where('buy_username',$this->auth->username)
                ->where(function($qr){
                    $qr->where('status3',1)->where('status','<=',1)->where('status4',0)->whereor('status',99);
                })
                ->order('success_time desc')
                ->field($fieldname)->limit($this->index,$this->pagesize)->select();
            if (!empty($data)) {
                $data = collection($data)->toArray();
            }
            //数据总条数
            $totalpage = $this->db
                ->where('buy_username',$this->auth->username)
                ->where(function($qr){
                    $qr->where('status3',1)->where('status','<=',1)->where('status4',0)->whereor('status',99);
                })
                ->order('success_time desc')
                ->count('id');
        }elseif($status == 1 && $type == 'sell'){
            //待转让
            $data = $this->db
                ->where('status3',0)
                ->where('status4',$status)
                ->where('buy_username',$this->auth->username)
                ->where('status',3)
                ->where('status2',0)
                ->where('status5',0)
                ->order('due_time desc')
                ->field($fieldname)->limit($this->index,$this->pagesize)->select();
            if (!empty($data)) {
                $data = collection($data)->toArray();
            }
            //数据总条数
            $totalpage = $this->db
                ->where('status3',0)
                ->where('status4',$status)
                ->where('buy_username',$this->auth->username)
                ->where('status',3)
                ->where('status2',0)
                ->where('status5',0)
                ->order('due_time desc')
                ->count('id');
        }elseif($status == 2 && $type == 'sell'){
            //待转中
            $data = $this->db
                ->where('buy_username',$this->auth->username)
                ->where(function($qr){
                    $qr->where('status4',2)->whereOr('status4',3);
                })
                ->where('status',3)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status5',0)
                ->order('due_time desc')
                ->field($fieldname)->limit($this->index,$this->pagesize)->select();
            if (!empty($data)) {
                $data = collection($data)->toArray();
            }
            //数据总条数
            $totalpage = $this->db
                ->where('buy_username',$this->auth->username)
                ->where(function($qr){
                    $qr->where('status4',2)->whereOr('status4',3);
                })
                ->where('status',3)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status5',0)
                ->order('due_time desc')
                ->count('id');
        }elseif($status == 4 && $type == 'sell'){
            //转让交易完成
            $data = $this->db
                ->where('buy_username',$this->auth->username)
                ->where('status',3)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',$status)
                ->where('status5',0)
                ->order('due_time desc')
                ->field($fieldname)->limit($this->index,$this->pagesize)->select();
            if (!empty($data)) {
                $data = collection($data)->toArray();
            }
            //数据总条数
            $totalpage = $this->db
                ->where('buy_username',$this->auth->username)
                ->where('status',3)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',$status)
                ->where('status5',0)
                ->order('due_time desc')
                ->count('id');
        }elseif($status == 1 && $type == 'sell_appeal'){
            //转让的申诉中
            $data = $this->db
                ->where('buy_username',$this->auth->username)
                ->where('status3',$status)
                ->where('status',3)
                ->order('due_time desc')
                ->field($fieldname)->limit($this->index,$this->pagesize)->select();
            if (!empty($data)) {
                $data = collection($data)->toArray();
            }
            //数据总条数
            $totalpage = $this->db
                ->where('buy_username',$this->auth->username)
                ->where('status3',$status)
                ->where('status',3)
                ->order('due_time desc')
                ->count('id');
        }

        //返回数据
        if (empty($data)) {
            $this->success('没有数据');
        }else{
            foreach ($data as &$v) {
                if ($type == 'sell') {
                    $img = $this->db->where('o.sell_ordersn',$v['ordersn'])->alias('o')->join('user u',' u.username = o.buy_username')->field('o.images,u.mobile,u.username')->find();
                    $v['images2'] = $img['images'];
                    $v['buy_mobile2'] = $img['mobile'];
                    $v['buy_username2'] = $img['username'];
                }
                $v['stime'] = $lvs[$v['level']]['stime'];
                $v['etime'] = $lvs[$v['level']]['etime'];
                $v['levelname'] = $lvs[$v['level']]['levelname'];
            }
            $res = [
                'page' => $this->page,
                'totalpage' => ceil($totalpage/$this->pagesize),
                'data' => $data
            ];
            $this->success('返回成功',$res);
        }
    }

    // 抢矿中
    public function buyStutas(){
        $this->getOrder(0,'buy');
    }

    // 收益中
    public function buyStutas2(){
        $this->getOrder(2,'buy');
    }

    // 购买申诉中
    public function buyAppeal(){
        $this->getOrder(1,'buy_appeal');
    }

    // 待转让
    public function sellStatus(){
        $this->getOrder(1,'sell');
    }

    // 转让中
    public function sellStatus2(){
        $this->getOrder(2,'sell');
    }

    // 转让交易完成状态
    public function sellStatus4(){
        $this->getOrder(4,'sell');
    }

    // 转让申诉
    public function sellAppeal(){
        $this->getOrder(1,'sell_appeal');
    }

    //申诉详情
    public function getAppealDetail(){
        $ordersn = input('get.ordersn/s','');
        $type = input('get.type/s','');

        if (empty($type)) {
            $this->error(__('申诉人的类型不能为空！'));
        }
        if (!in_array($type,['buy','sell'])) {
            $this->error(__('申诉人的类型参数错误！'));
        }
        if ($ordersn && $type) {
            $info = null;
            if ($type == 'buy') {
                //买家
                $info = $this->db->where('o.ordersn',$ordersn)->alias('o')->join('order_appeal_log oal','o.ordersn = oal.ordersn')->field('o.pcp,o.ordersn,o.sell_username as username,oal.des')->find();
            }else{
                //卖家
                $info = $this->db->where('o.ordersn',$ordersn)->alias('o')->join('order_appeal_log oal','o.ordersn = oal.ordersn')->field('o.total_money,o.ordersn,oal.des')->find();
                $info2 = $this->db->where('sell_ordersn',$info['ordersn'])->field('sell_username')->find();
                $info['username'] = $info2['sell_username'];
            }
            $this->success(__('返回成功'),$info);
        }else{
            $this->error(__('参数错误'));
        }
    }

    //申诉
    public function setAppeal(){
        //订单编号
        $ordersn = input('post.ordersn/s','');
        //金额
        //$money = input('post.money/d',0);
        //对方的用户编号
        //$tousername = input('post.tousername/s','');
        //申诉理由
        $desc = input('post.des/s','');
        //凭证，图片地址
        $image = input('post.image/s','');
        $type = input('post.type/s',''); // buy 买家申诉，sell卖家申诉

        if (empty($ordersn)) {
            $this->error(__('请填写订单编号'));
        }
        if (empty($desc)) {
            $this->error(__('请填写申诉理由'));
        }
        if (empty($type)) {
            $this->error(__('申诉人的类型不能为空！'));
        }
        if (!in_array($type,['buy','sell'])) {
            $this->error(__('申诉人的类型参数错误！'));
        }
        $info = $this->db->where('ordersn',$ordersn)->find();
        if ($info) {
            //如果为买家则冻结卖家的订单，如果为卖家则冻结为买家的订单
            if ($type == 'buy') {
                //自己的改为申诉中
                $this->db->where('ordersn',$info['ordersn'])->update(['status3' => 1]);
                //冻结卖家的订单
                $this->db->where('ordersn',$info['sell_ordersn'])->update(['status2' => 1]);
                db('order_appeal_log')->insert(['ordersn' => $ordersn,'ordersn2' => $info['sell_ordersn'],'type' => $type,'des' => $desc,'image' => $image,'createtime' => time()]);
            }else{
                //自己的改为申诉中
                $this->db->where('ordersn',$info['ordersn'])->update(['status3' => 1]);
                // 冻结买家的订单
                $data = $this->db->where('sell_ordersn',$info['ordersn'])->where('status','<>',99)->where('status2',0)->where('status3',0)->where('status4',0)->where('status5',0)->field('id,ordersn')->find();
                //修改订单的状态为申诉中
                $this->db->where('id',$data['id'])->update(['status2' => 1]);
                db('order_appeal_log')->insert(['ordersn' => $ordersn,'ordersn2' => $data['ordersn'],'type' => $type,'des' => $desc,'image' => $image,'createtime' => time()]);
            }
            $this->success(__('操作成功'));
        }else{
            $this->error(__('找不到该订单的信息'));
        }
    }

    //取消申诉
    public function cancelAppeal(){
        //订单编号
        $ordersn = input('post.ordersn/s','');
        $type = input('post.type/s','');
        if (empty($ordersn)) {
            $this->error(__('请填写订单编号'));
        }
        if (empty($type)) {
            $this->error(__('申诉人的类型不能为空！'));
        }
        if (!in_array($type,['buy','sell'])) {
            $this->error(__('申诉人的类型参数错误！'));
        }
        $info = $this->db->where('ordersn',$ordersn)->where('status3',1)->find();
        if ($info) {
            //修改订单的状态为取消申诉的状态
            $this->db->where('id',$info['id'])->update(['status3' => 0]);
            db('order_appeal_log')->where('ordersn',$ordersn)->where('type',$type)->update(['ordersn' => $ordersn,'status' =>3,'updatetime' => time()]);
            $this->success(__('操作成功'));
        }else{
            $this->error(__('找不到该订单的信息'));
        }
    }

    //获取冻结的订单
    public function getLookOrder(){
        //$status = input('get.status/d',0);
        $fieldname = 'id,periods,ordersn,level,pcp,total_money,sell_username,days,per,credit2,credit4_per,credit4,credit5,images,success_time,pay_etime,pay_time,lock_stime,lock_etime,wc_time,due_time,delay_time,delay_credit5,status';
        $data = $this->db->where('buy_username',$this->auth->username)->where('status2',1)->field($fieldname)->limit($this->index,$this->pagesize)->select();

        if (empty($data)) {
            $this->success('没有数据');
        }else{
            $data = collection($data)->toArray();
            $totalpage = $this->db->where('buy_username',$this->auth->username)->where('status2',1)->count('id');
            $res = [
                'page' => $this->page,
                'totalpage' => ceil($totalpage/$this->pagesize),
                'data' => $data
            ];
            $this->success('返回成功',$res);
        }
    }

    //抢矿成功，买家确认付款 和卖家点击确认交易完成
    public function payment(){
        $sn = input('get.ordersn/s','');
        if (request()->isPost()) {
            $status = input('post.status/d',0);
            $ordersn = input('post.ordersn/s','');
            $password = input('post.password/s','');
            $tm = time();

            //支付密码
            if (empty($password)) {
                $this->error('密码不能为空！');
            }else{
                $pwd = Auth::getEncryptPassword($password,$this->auth->salt);
                $payword = db('user_detail')->where('uid',$this->auth->id)->field('paypwd')->find();
                if ($pwd != $payword['paypwd']) {
                    $this->error('密码错误');
                }
            }
            if ($ordersn && $status) {
                if ($status == 1) {
                    // 上传付款的凭证，需做图片处理
                    // 获取表单上传文件 例如上传了001.jpg
                   /* $file = request()->file('image');
                    // 移动到服务器的上传目录 并且使用md5规则
                    $images = $file->rule('md5')->validate(['size'=>20*1024*1024, 'ext'=>'gif,jpg,jpeg,bmp,png,swf'])->move('uploads'. DS .'pz');
                    if (empty($images)) {
                        $this->error($images->getError());
                    }*/
                    $image_url = request()->post('image');

                    //买家确认付款
                    $this->db->where('status',0)->where('status2',0)->where('status3',0)->where('status4',0)->where('status5',0)->where('ordersn',$ordersn)->where('buy_username',$this->auth->username)->update(['status' => 1,'pay_time' => $tm,'images'=>$image_url]);
                    $info = $this->db->where('ordersn',$ordersn)->find();
                    // 买家确认付款，修改卖家的订单状态
                    $this->db->where('ordersn',$info['sell_ordersn'])->where('status',3)->where('status2',0)->where('status3',0)->where('status4',2)->where('status5',0)->update(['status4'=>3]);
                    $this->success('操作成功');
                }elseif($status == 7){

                    //确认交易完成，对应的收益要发放给卖家
                    $info = $this->db->field('ordersn,orecode,credit2,credit4,credit5')->where('status',3)->where('status2',0)->where('status3',0)->where('status4',3)->where('status5',0)->where('ordersn',$ordersn)->where('buy_username',$this->auth->username)->find();

                    //卖家确认交易完成
                    $this->db->where('status',3)->where('status2',0)->where('status3',0)->where('status4',3)->where('status5',0)->where('ordersn',$ordersn)->where('buy_username',$this->auth->username)->update(['status4' => 4,'sell_time' => $tm]);

                    //发放卖家收益
                    if ($info['credit2'] > 0) {
                        setCc($this->auth->username,'credit2',$info['credit2'],config('site.ore_text').'到期，交易完成获得'.config('site.credit2_text').'：'.$info['credit2']);
                    }
                    if ($info['credit4'] > 0) {
                        setCc($this->auth->username,'credit4',$info['credit4'],config('site.ore_text').'到期，交易完成获得'.config('site.credit4_text').'：'.$info['credit4']);
                    }
                    /*if ($info['credit5'] > 0) {
                        setCc($this->auth->username,'credit5',$info['credit5'],'矿到期，交易完成获得法币收益：'.$info['credit5']);
                    }*/


                    // 卖家确认交易完成，修改买家的订单状态
                    $this->db->where('sell_ordersn',$ordersn)->where('status',1)->where('status2',0)->where('status3',0)->where('status4',0)->where('status5',0)->update(['status'=>2,'wc_time' => $tm]);

                    //修改矿的状态
                    //获取买家的信息
                    $buy_info = $this->db->where('sell_ordersn',$ordersn)->where('status',2)->where('status2',0)->where('status3',0)->where('status4',0)->where('status5',0)->find();
                    db('block_ore')->where('orecode',$info['orecode'])->update(['status2' => 2,'ap_username' => $buy_info['buy_username'],'ap_ordersn' => $buy_info['ordersn']]);

                    // 这里要调用触发奖金
                    $id = db('user')->where('username',$this->auth->username)->find();
                    $data[] = ['uid' => $id['id'],'amount' => $info['credit5'],'ordersn' => $info['ordersn']];
                    try{
                        Procevent::dsell_event($data,'tgj');
                        Procevent::dsell_event($data,'tdj');
                    }catch (\Exception $e){
                        $this->error($e->getMessage());
                    }

                    $this->success('操作成功');
                }
            }else{
                $this->error('参数错误');
            }
        }

        //获取订单信息
        $info = $this->db->where('ordersn',$sn)->find();
        if ($info['status4'] > 0) {
            //卖家
            $sell = $this->db->where('o.sell_ordersn',$info['ordersn'])->where('o.status','<>',99)->alias('o')->join('user u','o.buy_username = u.username')->field('o.images,u.mobile,u.username')->find();
            $info['images2'] = isset($sell['images']) ? $sell['images'] : '';
            $info['buy_mobile2'] = isset($sell['mobile']) ? $sell['mobile'] : '';
            $info['buy_username2'] = isset($sell['username']) ? $sell['username'] : '';
        }
        $uinfo = db('user')->alias('u')->join('user_detail ud','u.id = ud.uid')->where('u.username',$info['sell_username'])->field('ud.realname,u.mobile,ud.wechatact,ud.alipayact,ud.alipay_url,ud.wechat_url,ud.bankname,ud.bank,ud.bankuname,ud.bankact')->find();
        if ($info && $uinfo) {
            $info = array_merge($info,$uinfo);
        }
        $this->success(__('返回成功'),$info);
    }

    //续约 订单为收益中才能续约
    public function renewContract(){
        $ordersn = input('get.ordersn/s','');
        if ($ordersn) {
            $info = $this->db
                ->where('ordersn',$ordersn)
                ->where('status',2)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',0)
                ->where('status5',0)
                ->field('id,due_time,days,renew')
                ->find();
            if ($info['renew'] == 1) {
                $this->error(__('您已经续约过了，不能再次续约'));
            }
            //合约到期前5分总禁止续约
            if ($info['due_time'] <= (time()+300)) {
                $this->error(__('合约到期前5分总禁止续约'));
            }

            //直接续约
            /*$this->db
                ->where('ordersn',$ordersn)
                ->where('status',2)
                ->where('status2',0)
                ->where('status3',0)
                ->where('status4',0)
                ->where('status5',0)
                ->update(['due_time' => ($info['due_time'] + $info['days'] * 86400),'renew' => 1]);*/
            db('order_rc_log')->insert(['ordersn' => $ordersn,'username' => $this->auth->username,'status' => 0,'createtime' => time()]);
            $this->success(__('记录成功，等待系统审核'));
        }else{
            $this->error(__('参数错误'));
        }
    }

    //获取续约的记录
    public function getRcLog(){
        $data = db('order_rc_log')->where('username',$this->auth->username)->limit($this->index,$this->pagesize)->select();
        if ($data) {
            $data = collection($data)->toArray();
        }
        $this->success(__('返回成功'),$data);
    }
}