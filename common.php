<?php

// 公共助手函数

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name)
            return $name;
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }

}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int $size 大小
     * @param string $delimiter 分隔符
     * @return string
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++)
            $size /= 1024;
        return round($size, 2) . $delimiter . $units[$i];
    }

}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int $time 时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }

}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time 时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }

}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string $url 资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $url = preg_match("/^https?:\/\/(.*)/i", $url) ? $url : \think\Config::get('upload.cdnurl') . $url;
        if ($domain && !preg_match("/^(http:\/\/|https:\/\/)/i", $url)) {
            if (is_bool($domain)) {
                $public = \think\Config::get('view_replace_str.__PUBLIC__');
                $url = rtrim($public, '/') . $url;
                if (!preg_match("/^(http:\/\/|https:\/\/)/i", $url)) {
                    $url = request()->domain() . $url;
                }
            } else {
                $url = $domain . $url;
            }
        }
        return $url;
    }

}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param    string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE) {
                return FALSE;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        } elseif (!is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE) {
            return FALSE;
        }
        fclose($fp);
        return TRUE;
    }

}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname 目录
     * @param bool $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname))
            return false;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }

}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest 目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }

}

if (!function_exists('mb_ucfirst')) {

    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }

}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items 数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields)
            return $items;
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = isset($v['field']) ? $v['field'] : $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = isset($v['display']) ? $v['display'] : str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = isset($v['primary']) ? $v['primary'] : '';
            $v['column'] = isset($v['column']) ? $v['column'] : 'name';
            $v['model'] = isset($v['model']) ? $v['model'] : '';
            $v['table'] = isset($v['table']) ? $v['table'] : '';
            $v['name'] = isset($v['name']) ? $v['name'] : str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = $model->where($primary, 'in', $ids[$v['field']])->column("{$primary},{$v['column']}");
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $v[$fieldsArr[$n]['display']] = implode(',', array_intersect_key($result[$n], $curr));
                }
            }
        }
        return $items;
    }

}

if (!function_exists('var_export_short')) {

    /**
     * 返回打印数组结构
     * @param string $var 数组
     * @param string $indent 缩进字符
     * @return string
     */
    function var_export_short($var, $indent = "")
    {
        switch (gettype($var)) {
            case "string":
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : var_export_short($key) . " => ")
                        . var_export_short($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case "boolean":
                return $var ? "TRUE" : "FALSE";
            default:
                return var_export($var, TRUE);
        }
    }
}

if (!function_exists('create_order_sn')){
    /**
     * 返回订单号
     * @param string $prefix
     * @return string
     */
    function create_order_sn($prefix = "cc"){
        $length = 6;
        $numeric = true;
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        if ($numeric) {
            $hash = '';
        } else {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $prefix.date('YmdHis').$hash;
    }
}

//日志记录方法
if (!function_exists('plog')){
    /**
     * @param string $msg 日志描述
     * @param string $type 日志类型:info、error等等，可自定义
     * @param array $params 相关参数
     */
    function plog($source,$msg,$type='Error',$params=null){
        //基础路径
        $basepath = RUNTIME_PATH . 'plog/';

        //判断改目录是否存在，如果不存在则创建
        !is_dir($basepath) && mkdir($basepath, 0755, true);
        //日志文件名
        $log_filename = $basepath . date('Y-m-d') . ".log";
        //资源句柄
        $myfile = fopen($log_filename, "a+");

        $time = date('Y-m-d H:i:s');

        if ($params) {
            $params = json_encode($params);
        }

        //写入的内容
        $txt = "/*************************************************** start ************************************/\r\n日志来源：【{$source}】\r\n日志类型：【{$type}】\r\n日志描述: 【{$msg}】\r\n相关参数：【{$params}】\r\n添加时间：【{$time}】\r\n/*************************************************** end ************************************/\r\n";
        //写入内容
        fwrite($myfile, $txt);

        //关闭资源句柄
        fclose($myfile);//关闭该操作
    }
}

//实例化Redis
if (!function_exists('rds')){
    function rds($host=null,$port=null){
        if (empty($host)) {
            $host = config('redis.host');
        }
        if (empty($port)) {
            $port = config('redis.port');
        }
        //redis数据出队操作,从redis中将请求取出
        $redis = new \Redis();
        $redis->connect($host,$port);
        return $redis;
    }
}

if (!function_exists('enase')){
    /**
     * ase 加密
     * @param $str 加密明文
     * @param string $method 加密方法
     * @param string $key 加密密钥
     * @param int $options  数据格式选项（可选）【选项有：】0,1,2,3 默认为0，base64
     * @param string $iv    初始化向量（可选）
     *
     * @return string
     */
    function enase($str,$method = '',$key = '',$options = 0,$iv = ''){
        aseDD($method,$key,$options,$iv);
        return openssl_encrypt($str,$method,$key,$options,$iv);
    }
}

if (!function_exists('dease')){
    /**
     * ase解密
     * @param $str 解密字符串
     * @param string $method 解密方法
     * @param string $key 解密密钥
     * @param int $options  数据格式选项（可选）【选项有：】0,1,2,3，默认为0，base64
     * @param string $iv    初始化向量（可选）
     *
     * @return string
     */
    function dease($str,$method = '',$key = '',$options =0,$iv = ''){
        aseDD($method,$key,$options,$iv);
        return openssl_decrypt($str,$method,$key,$options,$iv);
    }
}

if (!function_exists('dedata')){
    /**
     * 获取前端上传的数据进行解密
     * @param string $type input()方法的参数
     * @param string $method 解密的方法
     * @param string $key 解密密钥
     * @param int $options 解密的数据格式
     * @param string $iv 解密的偏移量
     *
     * @return mixed
     */
    function dedata($type = 'post.data/s',$method = '',$key = '',$options = 0,$iv = ''){
        $filter = 'htmlspecialchars,addslashes,strip_tags';
        $data = input($type,'',$filter);
        if(!is_string($data) || empty($data)) return $data;
        $data = json_decode(dease($data,$method,$key,$options,$iv),true);
        if(is_array($data)){
            array_walk_recursive($data, 'hd', $filter);
            reset($data);
//            foreach ($data as $dkey => &$dval) {
//                $dval = addslashes($dval);
//                $dval = strip_tags($dval);
//                $dval = htmlspecialchars($dval);
//            }
        }
        return $data;
    }
}

if (!function_exists('endata')){
    /**
     * 对输出给前端的数据进行加密
     * @param Array $data 需要加密的数据
     * @param string $method 加密的方法
     * @param string $key 加密密钥
     * @param int $options 加密的数据格式
     * @param string $iv 加密的偏移量
     *
     * @return mixed
     */
    function endata($data,$method = '',$key = '',$options = 0,$iv = ''){
        // json_encode() JSON_UNESCAPED_SLASHES 不转义 /
        return enase(json_encode($data,JSON_UNESCAPED_SLASHES),$method,$key,$options,$iv);
    }
}

if (!function_exists('aseDD')){
    /**
     * 当ase参数为空时，获取ase加密的默认参数
     * @param $method 方法
     * @param $key 密钥
     * @param $options 数据格式
     * @param $iv 偏移量
     */
    function aseDD(&$method,&$key,&$options,&$iv){
        $method = $method ? $method : config('ase.method');
        $key = $key ? $key : config('ase.key');
        $options = $options ? $options : config('ase.options');
        $iv = $iv ? $iv : config('ase.iv');
    }
}

//判断数组中该键名的值是否存在
if (!function_exists('ise')){
    function ise(array $data,$name){
        return isset($data[$name]) ? $data[$name] : null;
    }
}

if (!function_exists('hd')){
    function hd(&$value, $key, $filters){
        $filters = explode(',', $filters);
//        $default = array_pop($filters);
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                // 调用函数或者方法过滤
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (false !== strpos($filter, '/')) {
                    // 正则过滤
                    if (!preg_match($filter, $value)) {
                        // 匹配不成功返回默认值
//                        $value = $default;
                        $value = '';
                        break;
                    }
                } elseif (!empty($filter)) {
                    // filter函数不存在时, 则使用filter_var进行过滤
                    // filter为非整形值时, 调用filter_id取得过滤id
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
//                        $value = $default;
                        $value = '';
                        break;
                    }
                }
            }
        }
        return filterExp($value);
    }
}
if (!function_exists('filterExp')){
    function filterExp(&$value)
    {
        // 过滤查询特殊字符
        if (is_string($value) && preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT LIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOT EXISTS|NOTEXISTS|EXISTS|NOT NULL|NOTNULL|NULL|BETWEEN TIME|NOT BETWEEN TIME|NOTBETWEEN TIME|NOTIN|NOT IN|IN)$/i', $value)) {
            $value .= ' ';
        }
        // TODO 其他安全过滤
    }
}

if (!function_exists('get_number_name')){

    //随机生成会员编号
    function get_number_name()
    {
        $string = config('site.number_name');
        $pattern = "123567890";
        for ($i = 0; $i < 10; $i++) {
            $string .= $pattern{rand(0, 8)};
        }

        while(true){
            $count = \db('user')->where('username',$string)->count();
            if(empty($count)){
                break;
            }
        }

        return $string;
    }
}


