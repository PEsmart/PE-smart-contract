<?php
/**
 * 公告栏.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 14:36
 */

namespace app\admin\controller\question;
use app\common\controller\Backend;
use app\common\core\TransL;
use app\common\model\Category as CategoryModel;

class Setting extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id,title';
    protected $model = null;
    protected static $category = null;

    public function _initialize()
    {
		parent::_initialize();
		$this->model = model('Question');
		$category = \db('question_category')->field('id,name')->select();
		$this->view->assign("typeList", $category);
    }


    // 查看
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach($list as &$val){
				$category = \db('question_category')->where(['id'=>$val['category_id']])->field('name')->find();
                $val['type_text'] = $category['name'];
            }
            unset($val);

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    public function detail($ids = null){
        $row = $this->model->get($ids);
        if(!empty($row)){
            $row = $row->toArray();
			$category = \db('category')->where(['type'=>'question','status'=>'normal','id'=>$row['category_id']])->field('name')->find();
			$row['type_text'] = $category['name'];
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }


//    public function  add()
//    {
//
//        if ($this->request->isPost()) {
//            $params = $this->request->post("row/a");
//            if ($params) {
//                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
//                    $params[$this->dataLimitField] = $this->auth->id;
//                }
//            }
//            $baiDu=new TransL();
//            $en=$baiDu->translate($params["title"]."\n".$params['contents'],"zh","en");
//            $strEn="";
//            foreach($en['trans_result'] as $k=>$item){
//                if($k>0){
//                    $strEn=$strEn.$item['dst'];
//                }
//            }
//
//            $strEn=str_replace("& nbsp;","&nbsp;",$strEn);
//            $strEn=str_replace("< ;","<",$strEn);
//            $strEn=str_replace(" >;",">",$strEn);
//
//            sleep(1);
//            $bai=new TransL();
//            $cht=$bai->translate($params["title"]."\n".$params['contents']."\n","zh","cht");
//            $strCht="";
//            foreach($cht['trans_result'] as $k=>$item){
//                if($k>0){
//                    $strCht=$strCht.$item['dst']."\n";
//                }
//            }
//            $strCht=str_replace("&nbsp；","&nbsp;",$strCht);
//
//            db()->startTrans();
//            try{
//                $arr=['pid'=>0,'title'=>$params["title"],"contents"=>$params['contents'],'category_id'=>$params['category_id'],'lang'=>"zh","createtime"=>time()];
//                $getId=\db('question')->insertGetId($arr);
//                $data=[
//                    ['pid'=>intval($getId),'title'=>$en['trans_result'][0]["dst"],"contents"=>$strEn,'category_id'=>$params['category_id'],"lang"=>"en","createtime"=>time()],
//                    ['pid'=>intval($getId),'title'=>$cht['trans_result'][0]["dst"],"contents"=>$strCht,'category_id'=>$params['category_id'],"lang"=>"cht","createtime"=>time()],
//                ];
//                \db('question')->insertAll($data);
//                \db()->commit();
//            }catch (\Exception $e){
//                db()->rollback();
//                $this->error($e->getMessage());
//            }
//            $this->success("添加成功");
//        }
//        return $this->view->fetch();
//    }

}