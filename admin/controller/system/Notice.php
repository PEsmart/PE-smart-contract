<?php
/**
 * 公告栏.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 14:36
 */

namespace app\admin\controller\System;
use app\common\controller\Backend;
use app\common\core\TransL;
use think\Db;

class Notice extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = 'id,title';

    protected $model = null;
    protected static $category = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Notice');
        if(empty(self::$category)){
            self::$category = $this->model->getCategory();
        }
        $res = null;
        foreach(self::$category as $val){
            $res[$val['id']] = $val['name'];
        }
        $this->view->assign('category',$res);
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

            // $category = $this->model->getCategory();
            foreach($list as &$val){
                $val['type_text'] = self::$category[$val['type']]['name'];
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
            $row['createtime'] = date('Y-m-d H:i:s',$row['createtime']);
            $row['updatetime'] = date('Y-m-d H:i:s',$row['updatetime']);
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
//        public function  add()
//        {
//            if ($this->request->isPost()) {
//
//                $params = $this->request->post("row/a");
//
//                if ($params) {
//                    if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
//                        $params[$this->dataLimitField] = $this->auth->id;
//                    }
//                }
//                $baiDu=new TransL();
//                $en=$baiDu->translate($params["title"]."\n".$params['contents'],"zh","en");
//                $strEn="";
//                foreach($en['trans_result'] as $k=>$item){
//                    if($k>0){
//                        $strEn=$strEn.$item['dst'];
//                    }
//                }
//
//                $strEn=str_replace("& nbsp;","&nbsp;",$strEn);
//                $strEn=str_replace("< ;","<",$strEn);
//                $strEn=str_replace(" >;",">",$strEn);
//
//                sleep(1);
//                $bai=new TransL();
//                $cht=$bai->translate($params["title"]."\n".$params['contents']."\n","zh","cht");
//                $strCht="";
//                foreach($cht['trans_result'] as $k=>$item){
//                    if($k>0){
//                        $strCht=$strCht.$item['dst']."\n";
//                    }
//                }
//                $strCht=str_replace("&nbsp；","&nbsp;",$strCht);
//
//
//                db()->startTrans();
//                try{
//                    $arr=['pid'=>0,'title'=>$params["title"],'type'=>$params['type'],"contents"=>$params['contents'],'lang'=>"zh","createtime"=>time()];
//                    $getId=\db('notice')->insertGetId($arr);
//                    $data=[
//                        ['pid'=>intval($getId),'title'=>$en['trans_result'][0]["dst"],"type"=>$params['type'],"contents"=>$strEn,"lang"=>"en","createtime"=>time()],
//                        ['pid'=>intval($getId),'title'=>$cht['trans_result'][0]["dst"],"type"=>$params['type'],"contents"=>$strCht,"lang"=>"cht","createtime"=>time()],
//                    ];
//                    \db('notice')->insertAll($data);
//                    \db()->commit();
//                }catch (\Exception $e){
//                    db()->rollback();
//                    $this->error($e->getMessage());
//                }
//                $this->success("添加成功");
//            }
//            return $this->view->fetch();
//        }

}