<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 11:08
 */
namespace app\api\controller;

use app\common\controller\Api;
use app\common\core\Get;
use app\common\core\TransL;
use app\common\model\Config;
use app\common\model\Lang;
use think\Db;

class Question extends Api
{
    protected $noNeedLogin = ["getCategoryList","getAllList","getDetail"];
    protected $noNeedRight = '*';
	// 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
	protected $noEncryption = '*';
	// 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
	protected $rNoEncryption = [];
    public $type = null;

	public function _initialize()
	{
		parent::_initialize();
		$this->type=\think\Cookie::get('think_var');
	}
    //获取分类
	public function getCategoryList(){
		$page = $this->request->request('page');
		$page = !empty($page) ? $page : 1;
		$pageSize = config('page_rows');

		$str=Get::getLang(",name",",name_en as name",$this->type);

		$count = \db('question_category')->count();
		$data = \db('question_category')
			->field('id'.$str)
			->limit(($page-1)*$pageSize, $pageSize)
			->select();
		
		$this->success('1', ['data'=>$data, 'page'=>$page, 'totalpage'=>ceil($count/$pageSize)]);
	}
    //获取后台问题列表页
	public function getAllList()
	{
		$page = $this->request->request('page');
		$page = !empty($page) ? $page : 1;
		$pageSize = config('page_rows');

		$category_id = $this->request->request('category_id');


		$count = \db('question')->where(['category_id'=>$category_id])->count();

        $str=Get::getLang(",title",",title_en as title",$this->type);

		$data = \db('question')
			->where(['category_id'=>$category_id])
			->field('id'.$str)
			->limit(($page-1)*$pageSize, $pageSize)
			->select();

		$this->success('1', ['data'=>$data, 'page'=>$page, 'totalpage'=>ceil($count/$pageSize)]);
		
	}
	// 获取某一个问题的内容
	public function getDetail()
	{
	    $id =  $this->request->request('id');

        $str=Get::getLang("id,title,contents","id,title_en as title,contents_en as contents",$this->type);
        $data = \db('question')
			->field($str)
			->where(['id'=>$id])
		   ->find();
		if($data){
            $this->success('获取成功', ['data'=>$data]);

        }
		else{

            $this->error('获取失败');

        }
	}

	//上传反馈问题
	public function addFeedback(){
		$data['uid'] = $this->request->request('uid');
		$data['connect'] = $this->request->request('connect');
		$data['question'] = $this->request->request('question');
		$data['img'] = $this->request->request('img');
		$rs = \db('feedback')->insert($data);
		if($rs)
			$this->success('提交成功');
		else
			$this->error('提交失败');
	}
   	//获取客服中心文字
	public function getCenter()
	{
		$data = [];
		$param = Config::getSetting();
		$this->success('', $param['customer_center']);
	}
}