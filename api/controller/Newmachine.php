<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 20:41
 */
namespace app\api\controller;

use app\common\controller\Api;
use think\Cookie;

/**
 * 公告栏
 */
class Newmachine extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];

    public $type=null;
    public function _initialize()
    {
        parent::_initialize();
        $this->type=Cookie::get('think_var');
    }
    public function show(){
        if ($this->request->isPost()){
            $machine = db('newmachine')->where('status',1)->order('createtime desc')->select();
            foreach ($machine as &$v){
                if ($this->type=='en'){
                    $v['comment'] =$v['comment_en'];
                    $v['name'] =$v['name_en'];
                }elseif ($this->type=='zh-tw'){
                    $v['comment'] =$v['comment_tw'];
                    $v['name'] =$v['name_tw'];
                }
                $v['dayandnetincome']=unserialize($v['dayandnetincome']);
            }
            $this->success(1,['data'=>$machine]);
        }
    }
    public function single(){
        if ($this->request->isPost()){
            $id=input('id');
            $machine = db('newmachine')->where('status',1)->where('id',$id)->find();
            $machine['dayandnetincome']=unserialize($machine['dayandnetincome']);
            $machine['creetetime'] = date('Ymd H:i:s');
            $temp=[
                '0'=>__('Closed'),
                '1'=>__('Opening')
            ];
            if ($this->type=='en'){
                $machine['comment'] =$machine['comment_en'];
                $machine['name'] =$machine['name_en'];
            }elseif ($this->type=='zh-tw'){
                $machine['comment'] =$machine['comment_tw'];
                $machine['name'] =$machine['name_tw'];
            }
            $machine['status']=$temp[ $machine['status'] ];
            $this->success(1,['data'=>$machine]);
        }
    }




}