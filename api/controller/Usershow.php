<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/1 0001
 * Time: 17:27
 */

namespace app\api\controller;
use app\admin\model\Show;
use app\common\controller\Api;

class Usershow extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = '*';
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];
    
    public function add(){
        
        if ($this->request->isPost()) {
            
            if($this->request->request('type')==1){
                $this->post();
                
            }
        }
    }
    
    protected function post(){
    
        if ($this->request->isPost()) {
            $mid = $this->request->request('id');
            $img = $this->request->request('img');
            $show=new Show();
            $show->save([
                'mid'=>$mid,
                'img'=>$img,
            ]);
        
           if($show){
               $this->success(__('提交成功'));
           }else{
               $this->error('提交失败');
           }
            
            
        }
       
    }
}