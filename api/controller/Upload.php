<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/7/9
 * Time: 16:06
 */
namespace app\api\controller;

use app\common\controller\Api;
use think\Exception;
use think\Request;
use think\Validate;
use think\Db;
use think\Model;


/**
 * 上传文件
 */
class Upload extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['upFile','downFile','getVersion'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    public function __construct()
    {
        parent::__construct();
    }

    //上传问题件
    public function upFile(){

        if (request()->isPost()) {
            $file = request()->file('file');
            $vs = request()->post('version');
            if (empty($vs)) {
                $this->error('版本号不能为空');
            }
            if ($file) {
                // 移动到服务器的上传目录
                $ext = $file->checkExt('wgt');
                if (!$ext) {
                    $this->error('文件格式不正确');
                }
                $size = $file->checkSize(10*1024*1024*1024);
                if (!$size) {
                    $this->error('文件大小超过限制');
                }
                $arr = explode('.',$file->getInfo('name'));
                $ext = $arr[count($arr) - 1];
                array_pop($arr);
                $filename = implode($arr);

                $res = $file->move('uploads'. DS .'file',$filename.'('.$vs.').'.$ext);
                if ($res) {
                    $pathname = $res->getPathname();
                    $oldname = $file->getInfo('name');
                    $newname = $res->getSaveName();
                    db('app_version')->insert(['version' => $vs,'url' => $pathname,'old_name' => $oldname,'new_name' => $newname,'createtime' => time()]);
                    $this->success('上传成功',$res->getPathname());
                }else{
                    $this->error('上传失败');
                }

            }else{
                $this->error(__('参数不能为空'));
            }
        }else{
            $this->error(__('非法请求'));
        }

    }

    //下载文件
    public function downFile(){
        $vs = input('get.version');
        $info = [];
        if ($vs) {
            $info = db('app_version')->where('version',$vs)->field('url,new_name')->find();
        }else{
            $info = db('app_version')->where('id','>',0)->order('id','desc')->field('url,new_name')->find();
        }
        //获取最新的版本
        //$file_url = 'uploads'. DS .'file'. DS .'test.wgt';
        $file_url = $info['url'];
        //$file_name=basename($file_url);
        $file_type=explode('.',$file_url);
        $file_type=$file_type[count($file_type)-1];

        $file_type=fopen($file_url,'r'); //打开文件
        //输入文件标签
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: ".filesize($file_url));
        header("Content-Disposition: attachment; filename=".$info['new_name']);
        //输出文件内容
        echo fread($file_type,filesize($file_url));
        fclose($file_type);
    }

    //获取版本号
    public function getVersion(){
        $vs = db('app_version')->where('id','>',0)->order('id','desc')->field('version')->find();
        if ($vs) {
            $this->success('返回成功',$vs['version']);
        }else{
            $this->error('暂时还没有历史版本');
        }
    }
}