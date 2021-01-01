<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 20:41
 */
namespace app\api\controller;

use app\common\controller\Api;
use app\common\core\Get;

/**
 * 公告栏
 */
class Notice extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['getTitles','details'];
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


    public function _initialize()
    {
        parent::_initialize();
        $this->type=\think\Cookie::get('think_var');
    }

    public function __construct()
    {
        parent::__construct();
        $ps = config('paginate.list_rows');
        if($ps > 0){
            $this->pagesize = $ps;
        }
        // 获取分页参数
        $this->page = input('get.page') ? intval(input('get.page')) : $this->page;
    }

    /**
     * 首页公告kx，发现fx
     * @throws \think\Exception
     */
    public function getTitles(){
        $type = input('get.type') ? input('get.type') : 'kx';


        $str=Get::getLang("n.id,n.title,n.images","n.id,n.title_en as title,n.images ",$this->type);


        // 获取所有公告栏的标题
        $index = ($this->page - 1)*$this->pagesize;
        $data = db('notice')->field($str)->alias('n')->join('notice_category nc','n.type = nc.id')->where('nc.type',$type)->limit($index,$this->pagesize)->order('n.createtime','desc')->select();

        // 总条数
        $total = db('notice')->field('n.title')->alias('n')->join('notice_category nc','n.type = nc.id')->where('nc.type',$type)->count();


        if ($total>0){
            foreach ($data as &$item){

                        if ($item['images']!=""){
                    $item['images']=explode(",",$item['images']);
                }else{
                    $item['images']=[];
                }


            }

        }


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

    /**
     * 公告栏详情
     */
    public function details(){
        $id = input('get.id');
        // 判断是否有传参数、参数是否正确
        if(!empty($id) && is_numeric($id)){
            // 通过id查询对应的公告信息

            $str=Get::getLang("id,title,contents,createtime,images","id,title_en as title,contents_en as contents,createtime,images",$this->type);

            $data = db('notice')->field($str)->where('id',intval($id))->find();


                if ($data['images']!=""){
                    $data['images']=explode(",",$data['images']);
                }else{
                    $data['images']=[];
                }



            if(!empty($data)){
                // 成功查询到数据，返回对应的数据
//                $data[0]['createtime'] = date('Y-m-d H:i:s');
                $this->success('返回成功',$data);;
            }else{
                // 没有找到数据，返回对应提示
                $this->success('没有数据');
            }
        }else{
            // 参数不正确，返回对应的提示
            $this->error('参数错误');
        }
    }



}