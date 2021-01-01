<?php

namespace app\common\model;

use think\Db;
use think\Exception;
use think\Model;
use think\Validate;

/**
 * 会员模型
 */
class User Extends Model
{
// 表名
    protected $name = 'user';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
//        'url',
    ];

    // 关联用户详细信息表
    public function userB(){
        return $this->hasOne('UserB', 'uid', 'id');
    }


    /**
     * 获取个人URL
     * @param   string  $value
     * @param   array   $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return "/u/" . $data['id'];
    }

    /**
     * 获取头像
     * @param   string    $value
     * @param   array     $data
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        return $value ? $value : '/assets/img/avatar.png';
    }

    /**
     * 获取会员的组别
     */
    public function getGroupAttr($value, $data)
    {
        return UserGroup::get($data['group_id']);
    }

    /**
     * 获取验证字段数组值
     * @param   string    $value
     * @param   array     $data
     * @return  object
     */
    public function getVerificationAttr($value, $data)
    {
        $value = array_filter((array) json_decode($value, TRUE));
        $value = array_merge(['email' => 0, 'mobile' => 0], $value);
        return (object) $value;
    }
    /**
     * 设置验证字段
     * @param mixed $value
     * @return string
     */
    public function setVerificationAttr($value)
    {
        $value = is_object($value) || is_array($value) ? json_encode($value) : $value;
        return $value;
    }

    /**
     * 变更会员余额
     * @param int $money    余额
     * @param int $user_id  会员ID
     * @param string $memo  备注
     */
    public static function money($money, $user_id, $memo)
    {
        $user = self::get($user_id);
        if ($user)
        {
            $before = $user->money;
            $after = $user->money + $money;
            //更新会员信息
            $user->save(['money' => $after]);
            //写入日志
            MoneyLog::create(['user_id' => $user_id, 'money' => $money, 'before' => $before, 'after' => $after, 'memo' => $memo]);
        }
    }

    /**
     * 变更会员积分
     * @param int $score    积分
     * @param int $user_id  会员ID
     * @param string $memo  备注
     */
    public static function score($score, $user_id, $memo)
    {
        $user = self::get($user_id);
        if ($user)
        {
            $before = $user->score;
            $after = $user->score + $score;
            $level = self::nextlevel($after);
            //更新会员信息
            $user->save(['score' => $after, 'level' => $level]);
            //写入日志
            ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
        }
    }

    /**
     * 根据积分获取等级
     * @param int $score 积分
     * @return int
     */
    public static function nextlevel($score = 0)
    {
        $lv = array(1 => 0, 2 => 30, 3 => 100, 4 => 500, 5 => 1000, 6 => 2000, 7 => 3000, 8 => 5000, 9 => 8000, 10 => 10000);
        $level = 1;
        foreach ($lv as $key => $value)
        {
            if ($score >= $value)
            {
                $level = $key;
            }
        }
        return $level;
    }

    /**
     * 获取用户当前拥有的计算机台数
     */
    public static function getMachineNum($uid){
        $currenttime = time();
        $num = \db('machine_order')
                ->where("uid = {$uid} and status = 'normal' and expiretime > {$currenttime}")
                ->count();
        return $num;
    }

    /*
     * 检测邮箱是否存在
     *
     */

    public  static  function  getByEmail($email){
        $email=\db('user')
            ->where('email',"{$email}")
            ->select();
        return $email;

    }

    /*
     *  检测用户名是否存在
     * */
    public static function getByUsername($username)
    {
        $username = \db('user')
            ->where("username","{$username}")
            ->select();
        return $username;
    }

    /*
     *  检测手机号是否存在
     * */
    public static function getByMobile($mobile)
    {
        $mobile = \db('user')
            ->where("mobile","{$mobile}")
            ->select();
        return $mobile;
    }



    /*
    *  获取推荐人详情
    * */
    public static function getDetail($tjid)
    {
        $user_detail = \db('user_detail')
            ->where('uid',"{$tjid}")
            ->select();
        return $user_detail;
    }

    /*
    *  获取用户详情
    * */
    public static function getDetail1($uid)
    {
        $user_detail = \db('user_detail')
            ->where('uid',"{$uid}")
            ->find();
        return $user_detail;
    }

    /**
     * 插入数据库
     * @return string
     */
    public static function inDetail($id,$update)
    {
        $res = \db('user_detail')
            ->where('uid',"{$id}")
            ->update($update);
        return $res;
    }
    /**
     * 查询用户币种
     * @return string
     */
    public static function


    seCc($id){
        $cc = \db('user_detail')
            ->field('csell,cfree,clock')
            ->where('uid',"{$id}")
            ->select();
        return $cc;
    }
    /**
     * 查询用户信息
     * @return string
     */
    public static function getInfo($id){
        $info = \db('user')
            ->alias('a')
            ->field('a.username,a.mobile,b.levelname,b.level,d.isreal,d.realname,d.creditid,d.realname,a.id')
            ->where('a.id',"{$id}")
            ->join('user_level b','a.level = b.level','LEFT')
            ->join('user_detail d', 'a.id = d.uid', 'LEFT')
//                ->fetchSql(true);
            ->select();

        return $info;
    }
    /**
     * 查询用户计算机
     * @return string
     */
    public static function getMainfo($id){
        $info = \db('user')
            ->alias('a')
            ->field('b.*,c.*')
            ->where('a.id',"{$id}")
            ->join('machine_order b','a.id = b.uid','LEFT')
            ->join('machine c','c.id = b.machineid','LEFT')
            ->order('b.createtime desc')
            ->select();
        return $info;
    }
    /**
     * 找回密码
     * @return string
     */
    public static function getpass($newpassword,$mobile){
//        查询会员数据库
        $where = [
            'password' => $newpassword
        ];
        $info = \db('user')
            ->where('mobile',$mobile)
            ->update($where);
        return $info;
    }
    /*
     * 支付信息查询
     * */
    public static function getpay($id){
        try{
            $info = \db('user_detail')
                ->field('alipayact,wechatact,alipayname,wechatname,alipay_url,wechat_url,bankact,bank,bankname')
                ->where('uid',$id)
                ->select();
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return $info;
    }
    /*
     * 修改支付信息
     * */
    public static function uppay($id,$alipayname,$alipayact,$alipayurl){
        $where = [
            'alipayname' => $alipayname,
            'alipayact' => $alipayact,
            'alipay_url' => $alipayurl
        ];

        $update = \db('user_detail')
            ->where('uid',$id)
            ->update($where);

        return $update;
    }
    /*
     * 修改微信信息
     * */
    public static function upwecha($id,$wechaname,$wechatact,$wechaturl){
        $where = [
            'wechatname' => $wechaname,
            'wechatact' => $wechatact,
            'wechat_url' => $wechaturl
        ];
        $update = \db('user_detail')
            ->where('uid',$id)
            ->update($where);
        return $update;
    }
    /*
     * 修改银行信息
     * */
    public static function upbank($id,$bank,$bankname,$bankuname,$bankact){
        $where = [
            'bank' => $bank,
            'bankname' => $bankname,
            'bankuname' => $bankuname,
            'bankact' => $bankact
        ];
        $update = \db('user_detail')
            ->where('uid',$id)
            ->update($where);
        return $update;
    }


    /*
     * 修改交易密码
     * */
    public static function uppaypwds($id,$paypwd){

        $where = [
            'paypwd' => $paypwd
        ];
        $update = \db('user_detail')
            ->where('uid',$id)
            ->update($where);
        return $update;
    }

    /**
     * 判断身份证是否可行
     */
    public static function creditidCheck($creditid = ''){
        $idpreg ="/^(\d{18,18}|\d{15,15}|\d{17,17}x|\d{17,17}X)$/";
        if (!preg_match($idpreg, $creditid)){
            throw new Exception("身份证格式错误");
        }

        $config_num = config('site.credit_real_num');
        $count = \db('user_detail')->where("creditid='{$creditid}'")->count();
        if ($count > $config_num){
            throw new Exception("同一身份证号最多能实名".$config_num."人");
        }
    }


    /*
    * 检查APP状态
    * */
    public static function status(){
        $where = [
            'name'=>'app_status'
        ];
        // 读取APP开关
//        $bank = \db('config')
//            ->field('value')
//            ->where($where)
//            ->select();
        $res['status']  = 1;
        return $res;
    }
    /*
    * 验证交易密码
    * */
    public static function valipays($id){
        $res = \db('user')
            ->alias('a')
            ->field('a.salt,b.paypwd')
            ->where('a.id',"{$id}")
            ->join('user_detail b','a.id = b.uid','LEFT')
            ->select();
        return $res;
    }

    public static function  getRecommendCode(){

        $code=self::createRecommendCode();
        $hasRecommendCode=\db('user')->where('recommend_code',$code)->find();
        for ($i=1;$code!=$hasRecommendCode;$i++){
            $code=self::createRecommendCode();
            $hasRecommendCode=\db('user')->where('recommend_code',$code)->find();
            if (!$hasRecommendCode) break;
        }
        return $code;
    }

    public static  function createRecommendCode(){
        $str="";
        for($i=0;$i<8;$i++){
            $num=rand(0,9);
            $str.=$num;
        }
        return $str;
    }

}
