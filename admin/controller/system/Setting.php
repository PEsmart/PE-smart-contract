<?php
/**
 * 系统参数设置
 * User: Administrator
 * Date: 2019/3/27
 * Time: 11:32
 */

namespace app\admin\controller\System;
use app\common\controller\Backend;
use app\common\model\Config;


class Setting extends Backend
{
    // 定义快速搜索的字段
    protected $searchFields = '';

    protected $model = null;
    protected $bkey = 'system';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Setting');
    }

    public function index(){
        //获取系统参数
        $sys = getSys();
        if ($this->request->isPost()) {
            $input = request()->post('sys/a');
            setSys($input,$this->bkey);
            $this->success(__('success'));
        }
        return view(null,compact('sys'));
    }
}