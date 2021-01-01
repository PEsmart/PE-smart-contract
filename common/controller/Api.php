<?php

namespace app\common\controller;

use app\common\library\Appstatus;
use app\common\library\Auth;
use think\Config;
use think\Cookie;
use think\Exception;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Hook;
use think\Lang;
use think\Loader;
use think\Request;
use think\Response;

/**
 * API控制器基类
 */
class Api
{

    /**
     * @var Request Request 实例
     */
    protected $request;

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;

    /**
     * 默认响应输出类型,支持json/xml
     * @var string
     */
    protected $responseType = 'json';

    protected $rds = null;
    protected $opname = null;

    /**
     * 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
     * @var noEncryption
     */
    protected $noEncryption = [];

    /**
     * 接收的数据无需解密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
     * @var rNoEncryption
     */
    protected $rNoEncryption = [];

    /**
     *
     */
    protected $redis = null;

    protected $redis_key = null;


    /**
     * 构造方法
     * @access public
     * @param Request $request Request 对象
     */
    public function __construct(Request $request = null)
    {
        try {
            header("Access-Control-Allow-Origin: *");// 跨域访问
            header("Access-Control-Allow-Headers:*");
            header("Access-Control-Allow-Methods: *");
            header("Access-Control-Allow-Credentials",true);
            header("Access-Control-Max-Age:86401");
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        if (request()->isOptions()) { //这个不加也跨域有问题
            exit(1);
        }

        $this->request = is_null($request) ? Request::instance() : $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList)
        {
            foreach ($this->beforeActionList as $method => $options)
            {
                is_numeric($method) ?
                    $this->beforeAction($options) :
                    $this->beforeAction($method, $options);
            }
        }


    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
//        try {
//            header("Access-Control-Allow-Origin: *");// 跨域访问
//            header("Access-Control-Allow-Headers:*");
//            header("Access-Control-Allow-Methods: *");
//            header("Access-Control-Allow-Credentials",true);
//            header("Access-Control-Max-Age:86402");
//        } catch (Exception $e) {
//            echo $e->getMessage();
//        }
//        if (request()->isOptions()) { //这个不加也跨域有问题
//            exit(2);
//        }
        //判断站点是否开启
        if (!config('site.base_web_open')) {
            $this->error(config('site.base_web_close_remarks'), null, 9999);
        }

//        $res = $this->auth = Auth::status();
//        if ($res['status'] == 0){
//            $this->error('当前为服务器升级时间,请明天早上9:00-晚上10:00再进行使用!');
//        }
        //移除HTML标签
        $this->request->filter('strip_tags');
        $this->auth = Auth::instance();
        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());
        // token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));
        // $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', $this->getpm('token',\think\Cookie::get('token'))));
        // var_dump($this->getpm());die;
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin))
        {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin())
            {
                $this->error(__('Please login first'), null, 401);
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight))
            {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path))
                {
                    $this->error(__('You have no permission'), null, 403);
                }
            }
        }
        else
        {
            // 如果有传递token才验证是否登录状态
            if ($token)
            {
                $this->auth->init($token);
            }
        }
        $upload = \app\common\model\Config::upload();
        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);
        Config::set('upload', array_merge(Config::get('upload'), $upload));
        // 加载当前控制器语言包
        $this->loadlang($controllername);

        if($this->noEncryption && is_array($this->noEncryption)){
            foreach ($this->noEncryption as &$v) {
                $v = strtolower($v);
            }
            unset($v);
        }
        if($this->rNoEncryption && is_array($this->rNoEncryption)){
            foreach ($this->rNoEncryption as &$v) {
                $v = strtolower($v);
            }
            unset($v);
        }
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 操作成功返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为1
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
//    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
//    {
//        $this->result($msg, $data, $code, $type, $header);
//    }
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        // $data 为空统一视为不做加密处理
        // config 文件设置了全局的是否加密，$this->noEncryption 属性针对局部方法设置不加密
        if(!(empty($data) || $this->noEncryption == '*' || (is_array($this->noEncryption) && in_array($this->request->action(),$this->noEncryption)))){
            // 局部没有设置，则以全局的为标准
            if(config('return_encryption') === true){
                // 返回数据的接口统一进行加密
                $data = endata($data);
            }
        }
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为0
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];

        if (isset($data['page']) && isset($data['totalpage'])){
            //修改 cjj
            foreach ($data as $k=>$v) {
                $result[$k] = $v;
            }
            /*$result['data'] = $data['data'];
            $result['page'] = $data['page'];
            $result['totalpage'] = $data['totalpage'];*/
        }

        // 如果未设置类型则自动判断
        $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

        if (isset($header['statuscode']))
        {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        }
        else
        {
            //未设置状态码,根据code值判断
            $code = $code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = Response::create($result, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 前置操作
     * @access protected
     * @param  string $method  前置操作方法名
     * @param  array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only']))
        {
            if (is_string($options['only']))
            {
                $options['only'] = explode(',', $options['only']);
            }

            if (!in_array($this->request->action(), $options['only']))
            {
                return;
            }
        }
        elseif (isset($options['except']))
        {
            if (is_string($options['except']))
            {
                $options['except'] = explode(',', $options['except']);
            }

            if (in_array($this->request->action(), $options['except']))
            {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @param  mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate))
        {
            $v = Loader::validate();
            $v->rule($validate);
        }
        else
        {
            // 支持场景
            if (strpos($validate, '.'))
            {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // 批量验证
        if ($batch || $this->batchValidate)
            $v->batch(true);
        // 设置错误信息
        if (is_array($message))
            $v->message($message);
        // 使用回调验证
        if ($callback && is_callable($callback))
        {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data))
        {
            if ($this->failException)
            {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }

    //检测频繁操作
    protected function checkop($id=null){
        $this->redis = rds();
        if (!$id) {
            $id = $this->auth->id ? $this->auth->id : mt_rand(1000,100000);
        }
        $opname = $this->request->module().'_'.$this->request->controller().'_'.$this->request->action().'_'.$id;
        $this->redis_key = $opname;
        /*if ($this->redis->get($this->redis_key)) {
            $this->error('繁忙，请稍后再操作！');
        }
        $this->redis->set($this->redis_key,1);
        $this->redis->expire($this->redis_key,30);*/

        //使用lua减少网络IO并确保原子性
        $lua = <<<LUA
        if redis.call('get','{$opname}')
        then
            return 1
        else
            redis.call('set','{$opname}',1)
            redis.call('expire','{$opname}',30)
            return 0
        end
LUA;
        $res = $this->redis->eval($lua);
        if($res === 1){
            $this->error('频繁操作，请稍后重试');
        }elseif($res === false){
            $this->error('系统错误');
        }

        return true;
    }

    public function __destruct()
    {
        if ($this->redis != null) {
            if ($this->redis_key != null) {
                $this->redis->del($this->redis_key);
            }
            $this->redis->close();
        }
    }

    /**
     * 获取输入数据 支持默认值和过滤
     * 加密的数据默认放在 post 的 data 里面,自定义的需要手动传 $key
     * @param string    $key 获取的变量名
     * @param mixed     $default 默认值
     * @param string    $filter 过滤方法
     * @return mixed
     */
    protected function getpm($key = '', $default = null, $filter = 'htmlspecialchars,addslashes,strip_tags'){
        // config 文件设置了全局的是否加密，$this->rNoEncryption 属性针对局部方法设置接收不加密的数据
        if($this->rNoEncryption == '*' || (is_array($this->rNoEncryption) && in_array($this->request->action(),$this->rNoEncryption))){
            return input($key,$default,$filter);
        }else{
            // 局部没有设置，则以全局的为标准
            if(config('upload_encryption') === true){
                // 加密，对应上传的参数进行简单的时间验证
                if($key){
                    $res = dedata($key);
                }else{
                    $res = dedata();
                }
                $tm = (ise($res,'vtime'));
                // 如果超过10秒钟则视为过期
                if(!$tm || time()-$tm >= 10){
                    $this->error('无效参数！');
                }
                return $res;
            }else{
                // 不加密
                return input($key,$default,$filter);
            }
        }
    }

    /**
     * 判断参数是否存在
     * @param $data 数据
     * @param $isarr 验证
     * @return bool
     */
    public function ret_isset($data, $isarr) {
        if (!is_array($data) || !is_array($isarr)) {
            $this->error('参数有误');
        }

        foreach ($isarr as $key => $val) {
            if (!isset($data[$val])) {
                $this->error('参数有误');
            }
        }

        return true;
    }

    /**
     * 检测矿池状态是否处于重启中
     */
    public function check_kc(){
        //方案1
        if($this->redis == null) $this->redis = rds();
        $lua = <<<LUA
        if redis.call('get',KEYS[1]) == ARGV[1]
        then
            return 1
        else
            return 0
        end
LUA;
        $ret = $this->redis->eval($lua,['kc_restart',1],1);
        if($ret === 1){
            $this->error(__('页面重启中，无法操作'));
        }elseif($ret === false){
            $this->error(__('系统错误'),null,444);
        }

        //方案2
        /*$transaction = gettransaction();
        if($transaction['kc_balance'] <= 0){
            $where = [
                'type' => 0,
                'status' => 1,
            ];
            $ok = \db('tron_order')->where($where)->count();
            if($ok > 0) $this->error('页面重启中，无法操作');
        }*/
    }


}
