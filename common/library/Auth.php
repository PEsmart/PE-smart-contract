<?php

namespace app\common\library;

use app\common\core\BonusSettle;
use app\common\core\Wallet;
use app\common\model\User;
use app\common\model\UserRule;
use fast\Random;
use think\Config;
use think\Db;
use think\Exception;
use think\Hook;
use think\Request;
use think\Validate;
use app\common\behavior\Walletapi;
class Auth
{

    protected static $instance = null;
    protected $_error = '';
    protected $_logined = FALSE;
    protected $_user = NULL;
    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;
    protected $requestUri = '';
    protected $rules = [];
    //默认配置
    protected $config = [];
    protected $options = [];
    protected $allowFields = ['id', 'username', 'nickname', 'mobile', 'avatar', 'score'];

    public function __construct($options = [])
    {
        if ($config = Config::get('user'))
        {
            $this->config = array_merge($this->config, $config);
        }
        $this->options = array_merge($this->config, $options);
    }

    /**
     * 
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 获取User模型
     * @return User
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * 兼容调用user模型的属性
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_user ? $this->_user->$name : NULL;
    }

    /**
     * 根据Token初始化
     *
     * @param string       $token    Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined)
        {
            return TRUE;
        }
        if ($this->_error)
            return FALSE;
        $data = Token::get($token);
        if (!$data)
        {
            return FALSE;
        }
        $user_id = intval($data['user_id']);
        if ($user_id > 0)
        {
            $user = User::get($user_id);
            if (!$user)
            {
                $this->setError('Account not exist');
                return FALSE;
            }
            if ($user['status'] != 'normal')
            {
                $this->setError('Account is locked');
                return FALSE;
            }
            $this->_user = $user;
            $this->_logined = TRUE;
            $this->_token = $token;

            //初始化成功的事件
            Hook::listen("user_init_successed", $this->_user);

            return TRUE;
        }
        else
        {
            $this->setError('You are not logged in');
            return FALSE;
        }
    }

    /**
     * 注册用户
     *
     * @param string $username  用户名
     * @param string $password  密码
     * @param string $email     邮箱
     * @param string $mobile    手机号
     * @param array $extend    扩展参数
     * @return boolean
     */
    public function register($username,$mobile = '',$tjid = '', $password = '', $paypwd = '',$realname,$tronAddr)
    {

//        // 检测用户名是否存在
        if (User::getByUsername($username))
        {
            $this->setError('Username already exist');
            return FALSE;
        }
//
        // 检测邮箱是否存在
//        if(User::getByEmail($email)){
//            $this->setError('Email alreadt exist');
//            return FALSE;
//        }

        if ($mobile && User::getByMobile($mobile))
        {
            $this->setError('Mobile already exist');
            return FALSE;
        }

        $ip = request()->ip();
        $time = time();

        $data = [
//            'email'    =>$email,
            'password' => $password,
            'username' => $username,
            'mobile'   => $mobile,

        ];

        $recommendCode=User::getRecommendCode();

        $params = array_merge($data, [

            'username' =>$username,
//            'email'    =>$email,
            'nickname'  => $realname,
            'salt'      => Random::alnum(),
            'jointime'  => $time,
            'joinip'    => $ip,
            'logintime' => $time,
            'loginip'   => $ip,
            'prevtime'  => $time,
            'status'    => 'normal',
            'recommend_code'=>$recommendCode,
            'level'    => 1,
            'tronAddr'  => $tronAddr
        ]);
        $params['password'] = self::getEncryptPassword($params['password'], $params['salt']);
//        $params = array_merge($params);
        ////////////////同步到Ucenter////////////////
//        if (defined('UC_STATUS') && UC_STATUS)
//        {
//            $uc = new \addons\ucenter\library\client\Client();
//            $user_id = $uc->uc_user_register($username);
//            // 如果小于0则说明发生错误
//            if ($user_id <= 0)
//            {
//                $this->setError($user_id > -4 ? 'Username is incorrect' : 'Email is incorrect');
//                return FALSE;
//            }
//            else
//            {
//                $params['id'] = $user_id;
//            }
//        }


        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try
        {
            $user = User::create($params);
            if($user){
                // 推荐人信息

                $tjinfo = User::getDetail($tjid);
                // 更新推荐人信息
                if($tjinfo){
                    $tjup['tjnum'] = $tjinfo[0]['tjnum'] + 1; // 更新推荐人数
//                    插入被推荐人的推荐路径
                    if (empty($tjinfo[0]['tjstr'])) $update['tjstr'] = $tjinfo[0]['uid'];
                    else$update['tjstr'] = $tjinfo[0]['uid'] . ',' . $tjinfo[0]['tjstr']; //推荐路径
                    $update['tjdept'] = substr_count($update['tjstr'], ',')+1; // 推荐深度
                }else{
                    return false;
                }


//                查询新用户主表
                $info_b = db('user')
                    ->field('id')
                    ->where('username',"{$username}")
//                    ->where('email',"{$email}")
                    ->select();

                $where_b = [
                    'uid'   => $info_b[0]['id'],
                    'tjid'  => $tjid,
                    'tjstr' => $update['tjstr'],
                    'tjdept' =>  $update['tjdept'],
                    'paypwd' => self::getEncryptPassword($paypwd, $params['salt']),
                    'realname'=>$realname
                ];


                //触发推荐奖金
//                BonusSettle::recommendBonus($info_b[0]['id']);

                //本地无钱包服务
                $hostname=$_SERVER['HTTP_HOST'];

                if (strpos($hostname,"80")==false){
                    //判断钱包是否启用
                    if (config('site.bcw_enable')) {
                        //初始化钱包
                        $where_b['walletaddr'] = Walletapi::createwallet($info_b[0]['id']);
                        //使用钱包地址生成二维码保存
                        $res = qrcode($where_b['walletaddr']);
                        if ($res['code'] == 1) {
                            $where_b['addr_img_url'] = $res['path'];
                        }
                    }
                }else{

                    //本地
                    $walletAddr=Db::name('user_detail')->where('uid',$info_b[0]['id'])->update(['credit2_url'=>"55255555"]);

                }
                $result_b = Db::name('user_detail')
                    ->insert($where_b);
                if($result_b == false){
                    die;
                }else{
                    $tjres = Db::name('user_detail')
                        ->where('uid',"{$tjid}")
                        ->update($tjup);
                }
            }


            Db::commit();
            // 此时的Model中只包含部分数据
            $this->_user = User::get($user->id);
            //设置Token
            $this->_token = Random::uuid();
            Token::set($this->_token, $user->id, $this->keeptime);
            //注册成功的事件
            Hook::listen("user_register_successed", $this->_user);
            return TRUE;
        }
        catch (Exception $e)
        {
            $this->setError($e->getMessage());
            Db::rollback();
            return FALSE;
        }
    }


    /**
     * 用户登录
     *
     * @param string    $account    账号,用户名、邮箱、手机号
     * @param string    $password   密码
     * @return boolean
     */
    public function login($account, $password)
    {
//        $field = Validate::is($account, 'email') ? 'email' : (Validate::regex($account, '/^1\d{10}$/') ? 'mobile' : 'username');

        $field = 'mobile';
        $user = User::get([$field => $account]);
        if (!$user)
        {
            $this->setError('Account is incorrect');
//            $this->setError('Mail is incorrect');
            return FALSE;
        }

        if ($user->status != 'normal')
        {
//            $this->setError('Mail is locked');
            $this->setError('Account is locked');
            return FALSE;
        }
        if ($user->password != self::getEncryptPassword($password, $user->salt))
        {
            $this->setError('Password is incorrect');
            return FALSE;
        }

        //删除之前的token，只允许单点登录
        db('user_token')->where('user_id',$user->id)->delete();

        //直接登录会员
        $this->direct($user->id);

        return TRUE;
    }

    /**
     * 注销
     * 
     * @return boolean
     */
    public function logout()
    {
        if (!$this->_logined)
        {
            $this->setError('You are not logged in');
            return false;
        }
        //设置登录标识
        $this->_logined = FALSE;
        //删除Token
        Token::delete($this->_token);
        //注销成功的事件
        Hook::listen("user_logout_successed", $this->_user);
        return TRUE;
    }

    /**
     * 修改密码
     * @param string    $newpassword        新密码
     * @param string    $oldpassword        旧密码
     * @param bool      $ignoreoldpassword  忽略旧密码
     * @return boolean
     */
    public function changepwd($newpassword, $oldpassword = '', $ignoreoldpassword = false)
    {
        if (!$this->_logined)
        {
            $this->setError('You are not logged in');
            return false;
        }
        //判断旧密码是否正确
        if ($this->_user->password == self::getEncryptPassword($oldpassword, $this->_user->salt) || $ignoreoldpassword)
        {
            $salt = Random::alnum();
            $newpassword = self::getEncryptPassword($newpassword, $salt);
            $this->_user->save(['password' => $newpassword, 'salt' => $salt]);

            Token::delete($this->_token);
            //修改密码成功的事件
            Hook::listen("user_changepwd_successed", $this->_user);
            return true;
        }
        else
        {
            $this->setError('Password is incorrect');
            return false;
        }
    }


    /**
     * 直接登录账号
     * @param int $user_id
     * @return boolean
     */
    public function direct($user_id)
    {
        $user = User::get($user_id);
        if ($user)
        {
            ////////////////同步到Ucenter////////////////
            if (defined('UC_STATUS') && UC_STATUS)
            {
                $uc = new \addons\ucenter\library\client\Client();
                $re = $uc->uc_user_login($this->user->id, $this->user->password . '#split#' . $this->user->salt, 3);
                // 如果小于0则说明发生错误
                if ($re <= 0)
                {
                    $this->setError('Username or password is incorrect');
                    return FALSE;
                }
            }

            $ip = request()->ip();
            $time = time();

            //判断连续登录和最大连续登录
            if ($user->logintime < \fast\Date::unixtime('day'))
            {
                $user->successions = $user->logintime < \fast\Date::unixtime('day', -1) ? 1 : $user->successions + 1;
                $user->maxsuccessions = max($user->successions, $user->maxsuccessions);
            }

            $user->prevtime = $user->logintime;
            //记录本次登录的IP和时间
            $user->loginip = $ip;
            $user->logintime = $time;

            $user->save();

            $this->_user = $user;

            $this->_token = Random::uuid();
            Token::set($this->_token, $user->id, $this->keeptime);

            $this->_logined = TRUE;

            //登录成功的事件
            Hook::listen("user_login_successed", $this->_user);
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * 检测是否是否有对应权限
     * @param string $path      控制器/方法
     * @param string $module    模块 默认为当前模块
     * @return boolean
     */
    public function check($path = NULL, $module = NULL)
    {
        if (!$this->_logined)
            return false;

        $ruleList = $this->getRuleList();
        $rules = [];
        foreach ($ruleList as $k => $v)
        {
            $rules[] = $v['name'];
        }
        $url = ($module ? $module : request()->module()) . '/' . (is_null($path) ? $this->getRequestUri() : $path);
        $url = strtolower(str_replace('.', '/', $url));
        return in_array($url, $rules) ? TRUE : FALSE;
    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined)
        {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取会员基本信息
     */
    public function getUserinfo()
    {
        $data = $this->_user->toArray();
        $allowFields = $this->getAllowFields();
        $userinfo = array_intersect_key($data, array_flip($allowFields));
        $userinfo = array_merge($userinfo, Token::get($this->_token));
        return $userinfo;
    }

    /**
     * 获取会员组别规则列表
     * @return array
     */
    public function getRuleList()
    {
        if ($this->rules)
            return $this->rules;
        $group = $this->_user->group;
        if (!$group)
        {
            return [];
        }
        $rules = explode(',', $group->rules);
        $this->rules = UserRule::where('status', 'normal')->where('id', 'in', $rules)->field('id,pid,name,title,ismenu')->select();
        return $this->rules;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 删除一个指定会员
     * @param int $user_id 会员ID
     * @return boolean
     */
    public function delete($user_id)
    {
        $user = User::get($user_id);
        if (!$user)
        {
            return FALSE;
        }

        ////////////////同步到Ucenter////////////////
        if (defined('UC_STATUS') && UC_STATUS)
        {
            $uc = new \addons\ucenter\library\client\Client();
            $re = $uc->uc_user_delete($user['id']);
            // 如果小于0则说明发生错误
            if ($re <= 0)
            {
                $this->setError('Account is locked');
                return FALSE;
            }
        }

        // 调用事务删除账号
        $result = Db::transaction(function($db) use($user_id) {
                    // 删除会员
                    User::destroy($user_id);
                    // 删除会员指定的所有Token
                    Token::clear($user_id);
                    return TRUE;
                });
        if ($result)
        {
            Hook::listen("user_delete_successed", $user);
        }
        return $result ? TRUE : FALSE;
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password  密码
     * @param string $salt      密码盐
     * @return string
     */
    public static function getEncryptPassword($password, $salt)
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr)
        {
            return FALSE;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr))
        {
            return TRUE;
        }

        // 没找到匹配
        return FALSE;
    }

    /**
     * 设置会话有效时间
     * @param int $keeptime 默认为永久
     */
    public function keeptime($keeptime = 0)
    {
        $this->keeptime = $keeptime;
    }

    /**
     * 渲染用户数据
     * @param array     $datalist   二维数组
     * @param mixed     $fields     加载的字段列表
     * @param string    $fieldkey   渲染的字段
     * @param string    $renderkey  结果字段
     * @return array
     */
    public function render(&$datalist, $fields = [], $fieldkey = 'user_id', $renderkey = 'userinfo')
    {
        $fields = !$fields ? ['id', 'nickname', 'level', 'avatar'] : (is_array($fields) ? $fields : explode(',', $fields));
        $ids = [];
        foreach ($datalist as $k => $v)
        {
            if (!isset($v[$fieldkey]))
                continue;
            $ids[] = $v[$fieldkey];
        }
        $list = [];
        if ($ids)
        {
            if (!in_array('id', $fields))
            {
                $fields[] = 'id';
            }
            $ids = array_unique($ids);
            $selectlist = User::where('id', 'in', $ids)->column($fields);
            foreach ($selectlist as $k => $v)
            {
                $list[$v['id']] = $v;
            }
        }
        foreach ($datalist as $k => &$v)
        {
            $v[$renderkey] = isset($list[$v[$fieldkey]]) ? $list[$v[$fieldkey]] : NULL;
        }
        unset($v);
        return $datalist;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }


    /**
     * 插入数据库
     * @return string
     */
    public static function setDetail($id,$update)
    {
        $res = User::inDetail($id,$update);
        return $res;
    }
    /**
     * 查询cc币
     * @return string
     */
    public static function getCc($id)
    {
        $res = User::seCc($id);
        return $res;
    }

    /**
     * 个人信息
     * @return string
     */
    public static function getReal($id)
    {
        $res = User::getInfo($id);
        return $res;
    }
    /**
     * 个人计算器
     * @return string
     */
    public static function getMachi($id)
    {
        $res = User::getMainfo($id);
        return $res;
    }
    /**
     * 找回密码 // 修改密码
     * @param string    $newpassword        新密码
     * @param string $captcha 验证码
     * @return boolean
     */
//
    public static function getpwd($newpassword,$mobile){
        $res = User::getpass($newpassword,$mobile);
        return $res;
    }
    /**
     * 获取支付信息
     * @param string
     * @return $res
     */
    public static function getpayinfo($id){
        $res = User::getpay($id);
        return $res;
    }
    /**
     * 修改支付宝信息
     * @param string
     * @return $res
     */
    public static function uppayinfo($id,$alipayname,$alipayact,$alipayurl){
        $res = User::uppay($id,$alipayname,$alipayact,$alipayurl);
        return $res;
    }
    /**
     * 修改微信信息
     * @param string
     * @return $res
     */
    public static function upwecha($id,$wechaname,$wechatact,$wechaturl){
        $res = User::upwecha($id,$wechaname,$wechatact,$wechaturl);
        return $res;
    }
    /**
     * 修改银行信息
     * @param string
     * @return $res
     */
    public static function upbank($id,$bank,$bankname,$bankuname,$bankact){
        $res = User::upbank($id,$bank,$bankname,$bankuname,$bankact);
        return $res;
    }
    /*
     * 修改交易密码
     * */
    public static function uppaypwd($id,$paypwd){

        $res = User::uppaypwds($id,$paypwd);
        return $res;
    }
    public static function status()
    {
        $res = User::status();
        return $res;
    }
//    验证交易密码
    public static function valiPay($id){

        $res = User::valipays($id);
        return $res;
    }

    public function getDetail($uid = 0)
    {
        if ($uid <= 0) {
            $uid = $this->_user['id'];
        }
        $res = User::getDetail1($uid);
        return $res;
    }
}
