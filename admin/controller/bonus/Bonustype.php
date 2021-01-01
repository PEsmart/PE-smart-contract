<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 10:04
 */
namespace app\admin\controller\bonus;

use app\common\controller\Backend;
use think\Db;

class Bonustype extends Backend
{
    protected $noNeedLogin = [];
    protected $noNeedRight = [];

    protected $model = null;
    public function _initialize(){

        parent::_initialize();
        $this->model = model('Bonustype');
    }
    /**
     * 编辑
     */
//public function index()
//{
//    echo  2;die;
//}

    public function edit($ids = NULL)
    {
        $row=db('bonus_type')->where('id',$ids)->find();
        $data=unserialize($row['data']);

        $levelList=\db('user_level')->order("level","asc")->select();
        $identity = db('user_identity_level')->where('enabled',1)->select();
        if($this->request->isPost()){
            $params = $this->request->post();
//            foreach ($levelList as $item){
//                var_dump($params[$item['levelname']]);
//            }

//            var_dump($params);
//            die;
            $update['bname']=$params['bname'];

            if ($params['bkey'] == 'sqsl' && $params['data']) {
                $arr = null;
                foreach ($params['data']['zt'] as $key=>$val) {
                    $arr[$params['data']['ds'][$key]] = [
                        'zt' => $val,
                        'ds' => $params['data']['ds'][$key],
                        'bl' => $params['data']['bl'][$key],
                    ];
                }
                $params['data'] = $arr;
            }
            if (isset($params['data'])){
                $update['data'] =serialize($params['data']);
            }else{
                $update['data'] =serialize(array());
            }
            db('bonus_type')->where('bkey',$params['bkey'])->update($update);
            $this->success();
        }
        $this->view->assign("row", $row);
        $this->view->assign("data", $data);
        $this->view->assign("level_list",$levelList);
        $this->view->assign("identity_list",$identity);
        return $this->view->fetch();

    }




}