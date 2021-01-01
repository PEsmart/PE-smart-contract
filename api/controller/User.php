<?php

namespace app\api\controller;
use app\common\core\Get;
use app\common\controller\Api;
use app\common\core\BonusSettle;
use app\common\core\Procevent;
use app\common\core\Wallet;
use app\common\library\Auth;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Config;
use app\common\model\Lang;
use app\common\model\Levelup;
use app\common\model\User as CommonUser;
use Endroid\QrCode\QrCode;
use fast\Random;
use think\Log;
use think\Response;
use think\Validate;
use think\Db;
use think\Model;
use app\common\behavior\Walletapi;

use app\common\controller\Frontend;

use think\Cookie;
use think\Hook;
use think\Session;
use think\Exception;
use think\Cache;
/**
 * 会员接口
 */
class User extends Api
{

    protected $noNeedLogin = ['login','register','setLanguage','test'];
    protected $noNeedRight = '*';

    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = [];//['logout','profile','changemobile','resetpwd','changeisreal','getcity','getbank','getimg','getcustom','upload'];
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = ['upload','getCoinOrder'];

    public function _initialize()
    {
        parent::_initialize();
        $this->type=\think\Cookie::get('think_var');
        $auth = $this->auth;
        //监听注册登录注销的事件
        Hook::add('user_login_successed', function ($user) use ($auth) {
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $auth->getToken(), $expire);
        });
        Hook::add('user_register_successed', function ($user) use ($auth) {
            Cookie::set('uid', $user->id);
            Cookie::set('token', $auth->getToken());
        });
        Hook::add('user_delete_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
        Hook::add('user_logout_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });

    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);

    }

    public function register(){
        $data = $this->getpm();
        if (empty($data)) {
            $this->error(__('Illegal submission'));
        }

        $this->check_kc();//检测矿池余额

        $tronAddr = trim(ise($data,'tronAddr'));
        $tj_tronAddr = trim(ise($data,'tj_tronAddr'));
        if(empty($tronAddr)){
            $this->error(__('Member contract address cannot be empty'));//会员合约地址不能为空
        }

        if(empty($tj_tronAddr)){
            $where = ['iscomp'=>1];
        }else{
            $where = ['tronAddr'=>$tj_tronAddr];
        }
        $udb = \db('user');
        $tjuser = $udb->where($where)->field('id,iscomp')->find();
        if(empty($tjuser)){
            $this->error(__('There is no recommender in the system'));//系统不存在推荐人
        }
        if($udb->where('tronAddr',$tronAddr)->count()){
            $this->error(__('The contract address already exists'));//已存在该合约地址
        }
        //如果推荐人为普通会员 则需要检测是否进行投资
        if($tjuser['iscomp'] == 0){
            $towhere = [
                'uid' => $tjuser['id'],
                'type' => 0
            ];
            if(\db('tron_order')->where($towhere)->count() <= 0){
                $this->error(__('There is no recommender for users to invest in'));
            }
        }

        $username = get_number_name();
        $ret = $this->auth->register($username,'', $tjuser['id'], 'admin123', '123456','',$tronAddr);
        if($ret)
        {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        }
        else
        {
            Log::error($this->auth->getError());
            $this->error('login has failed');
        }
    }

    public function login(){
        $data = $this->getpm();
        if (empty($data)) {
            $this->error(__('Illegal submission'));
        }
        $tronAddr = trim(ise($data,'tronAddr'));
        if(empty($tronAddr)){
            $this->error(__('HronAddr can not be empty'));
        }
        $uid = \db('user')->where('tronAddr',$tronAddr)->value('id');
        if(empty($uid)){
            $this->error(__('Account not exist'));
        }

        $ret = $this->auth->direct($uid);
        if($ret)
        {
            $info = $this->auth->getUserinfo();
            $info = array_merge($info, ['token'=>$this->auth->getToken()]);
            $this->success(__('Logged in successful'), $info);
        }
        else
        {
            Log::error($this->auth->getError());
            $this->error('Logged in lose');
        }

    }

    /**
     * 注销登录
     */

    public function logout()
    {
        //注销本站
        $this->auth->logout();
        $this->success(__('Logout successful') , url('user/index'));
    }


    /**
     * 注销登录
     */
    /*public function logout()
    {
        if( $this->auth->logout()){
            session(null);
        }
        $this->success(__('Logout successful'));

    }*/

    /**
     * 个人信息
     * @param string
     */
    public function getinfo()
    {
//        $id = $this->request->request('id');
        $info = $this->auth->getReal($this->auth->id);
        if($info){
            $this->success(__('查询成功'), $info);
        }else{
            $this->error(__('查询失败'));
        }
    }


    /**
     * 上传图片
     */
    public function upload()
    {
        $img = $this->request->post('image');
        //图片路径地址
        $basedir = ROOT_PATH.'public'.DS.'uploads';
        $fullpath = $basedir;
        if(!is_dir($fullpath)){
            mkdir($fullpath,0777,true);
        }
        $types = empty($types)? array('jpg', 'gif', 'png', 'jpeg'):$types;
        $img = str_replace(array('_','-'), array('/','+'), $img);
        $b64img = substr($img, 0,100);
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $b64img, $matches))
        {
            $type = $matches[2];
            if(!in_array($type, $types)){
                $this->error('图片格式不正确，只支持 jpg、gif、png、jpeg哦！');
            }
            $img = str_replace($matches[1], '', $img);
            $img = base64_decode($img);
            $photo = '/'.md5(date('YmdHis').rand(1000, 9999)).'.'.$type;
            file_put_contents($fullpath.$photo, $img);
            $this->success('','/uploads/'.$photo);
        }
        $this->error('请选择要上传的图片');
    }

    //获取各种展示数据
    public function get_information(){
        $prefix = config('database.prefix');
        $system = gettransaction();
        $uid = $this->auth->id;
        $redis = rds();
        $server_name = Config('redis_header')?$_SERVER['SERVER_NAME'].'_':'';
        $rds_sy = keeDecimal($redis->hGet($server_name.'user_sy','user_'.$uid),4);
        $where = [
            't.status' => 1,
            't.isout' => 0,
            't.type' => 0,
            'u.credit1'=>['>',0],
            'uid' => $uid
        ];
        $cangetsy = \db('user')
            ->alias('u')
            ->join('tron_order t','u.id=t.uid')
            ->where($where)
            ->sum('t.max_profit-t.profit');
        if($cangetsy > 0 && $cangetsy < $rds_sy){
            //因为有可能缓存的收益比可获得的最多收益还多 所以判断最多可以提现多少
            //如果缓存的收益 大于 能获得的最多收益 则只有能获得的收益可以提现
            $rds_sy = $cangetsy;
        }elseif($cangetsy == 0 || $rds_sy < 0){
            $rds_sy = 0;
        }

        $user = \db('user')->where('id',$uid)->field('credit2,untxday')->find();
        $untxday = $user['untxday'];
        $credit2 = keeDecimal($user['credit2'],4);
        $tdb = \db('tron_order');
        $uddb = \db('user_detail');
        $where = [
            'type' => 0,
            'status' => 1,
        ];
        $tron_orders = $tdb->where($where)->where('uid',$uid)->field('amt,profit,isout,max_profit')->select();
        $my_inv = 0;
        $wait_get = 0;
        $taken_sy = 0;
        foreach($tron_orders as $order){
            $my_inv += $order['amt'];
            $taken_sy += $order['profit'];
            if(!$order['isout']){
                $wait_get += $order['max_profit'] - $order['profit'];
            }
        }
        unset($tron_orders);
        $tjdept = $uddb->where('uid',$uid)->value('tjdept');
        $max_dept = $tjdept + 3;
        $field = '';
        for($i=1;$i<=3;$i++){
            $dept = $tjdept + $i;
            $field .= ",sum(case when tjdept={$dept} then 1 else 0 end) as tdnum{$i}";
        }
        $field = trim($field,',');
        $depts = \db()->query("select {$field} from {$prefix}user_detail where find_in_set({$uid},tjstr) and tjdept<={$max_dept}");
        $td_total = $uddb
            ->alias('ud')
            ->join('tron_order t','t.uid=ud.uid')
            ->where("find_in_set({$uid},tjstr) and t.type=0 and t.status=1 and t.isout=0")
            ->sum('amt');

        $td_num = $uddb->where("find_in_set({$uid},tjstr)")->count();

        //最后质押数据
        $last_tron = $tdb->where($where)->order('id','desc')->field('txid,amt')->limit(4)->select();

        //新奖池比例
        $pv = floatval(config('site.new_jackpot_pv'));
        $bi = config('site.new_jackpot_bi');
        $offset = strlen(substr(strrchr($bi, "."), 1));//获取配置的比例中小数位
        $peBi = bcmul(bcdiv($td_total,$pv,0),$bi,$offset);

        $uninvbi = bcmul(0.1,$untxday,2);

        $return = [
            'total' => $tdb->where($where)->sum('amt'),//总投资金额
            'people_num' => $tdb->where($where)->group('uid')->count('*'),//活动参与者总数
            'kc_balance' => keeDecimal($system['kc_balance'],4),//矿池余额
            'my_inv' => $my_inv,//我的质押
            'wait_get' => keeDecimal($wait_get-$rds_sy,4),//未释放数量
            'can_withdraw' => bcadd($credit2,$rds_sy,4),//可提取数量
            'taken_sy' => keeDecimal($taken_sy+$rds_sy,4),//赚取总量
            'kc_jackpot' => keeDecimal($system['kc_jackpot'],4),//奖池余额
            'kc_restart_num' => $system['kc_restart_num'],//重启次数
            'tjdept_one' => empty($depts[0]['tdnum1'])?0:$depts[0]['tdnum1'],//团队人数1层
            'tjdept_two' => empty($depts[0]['tdnum2'])?0:$depts[0]['tdnum2'],//团队人数2层
            'tjdept_three' => empty($depts[0]['tdnum3'])?0:$depts[0]['tdnum3'],//团队人数3层
            'td_total' => $td_total,//团队投资数
            'td_num' => $td_num,//团队人数
            'last_tron' => $last_tron,//最后的4笔投资订单
            'uninvbi' => $uninvbi,//不提款奖励比例
            'sumbi' => bcadd($uninvbi,$peBi,2)+1,
            'jackpot_bi' => $peBi,//新奖池个人比例
        ];

        $this->success('',$return);
    }

    /*
     * 设置cookie
     * zh-cn中文   en英文
     */
    public function setLanguage()
    {
        $type = $this->request->request('type');
        if(empty($type)) $type = 'zh-cn';

        if (!in_array($type, array('zh-cn','zh-en'))) {
            $this->error(__('Setup failed'));
        }

        Cookie::set('think_var',$type,7600);
        $a = Cookie::get('think_var');
        if($a){
            $this->success($type,'','1');
        }else{
            $this->error(__('Setup failed'));
        }
    }



}
