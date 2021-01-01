<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 20:15
 */

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 明细记录
 */
class Log extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['Ccdetails'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];
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

    public function __construct()
    {
        parent::__construct();
        //获取系统设置的每页显示的数据条数
        $ps = config('paginate.list_rows');
        if($ps > 0){
            $this->pagesize = $ps;
        }
        // 获取分页参数
        $this->page = input('get.page') ? intval(input('get.page')) : $this->page;
        $this->pagesize = Config('paginate.list_rows') ? Config('paginate.list_rows') : $this->pagesize;
    }

    /**
     * 获取币种变动的记录，账户明细
     * @throws \think\Exception
     */
    public function Ccdetails(){
        // 获取所有明细信息
        $type = input('get.type');

        $data = null;
        $index = ($this->page - 1)*$this->pagesize;
        $total = 0;
        if($type != 'all' && !empty($type)){
            $data = db('cc_detail_log')->field('id,username,type,num,remark,createtime')->where('username',$this->auth->username)->where('type',$type)->limit($index,$this->pagesize)->select();
            //获取数据的总条数
            $total = db('cc_detail_log')->where('username',$this->auth->username)->where('type',$type)->count('id');
        }else{
            $data = db('cc_detail_log')->field('id,username,type,num,remark,createtime')->where('username',$this->auth->username)->limit($index,$this->pagesize)->select();
            //获取数据的总条数
            $total = db('cc_detail_log')->where('username',$this->auth->username)->count('id');
        }
        foreach ($data as &$val) {
            $val['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
        }
        unset($val);

        $this->totalpage = ceil($total/$this->pagesize);
        $datas['page'] = $this->page;
        $datas['totalpage'] = $this->totalpage;
        $datas['data'] = $data;

        if(empty($datas)){
            $this->success('没有数据');
        }else{
            $this->success('返回成功',$datas);
        }
    }
}