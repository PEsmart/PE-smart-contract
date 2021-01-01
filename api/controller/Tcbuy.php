<?php
/**
 * 套餐相关操作.
 * User: admin
 * Date: 2019/10/9
 * Time: 10:03
 */
namespace app\api\controller;
use app\common\controller\Api;
use app\common\core\Procevent;
use app\common\model\Identityup;
use think\Cookie;
use think\Db;

class Tcbuy extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    // 返回数据无需加密的方法,单个设置例如：['test','test2'],*表示全部,只对 $this->success()方法有效
    protected $noEncryption = '*';
    // 接收的数据无需解密的方法(只有调用Api类中的getpm()方法有效),单个设置例如：['test','test2'],*表示全部,只对 $this->getpm()方法有效
    protected $rNoEncryption = [];
    public $type = null;
    // 分页参数
    // 一个页面显示几条数据
    protected $pagesize = 8;
    // 页码
    protected $page = 1;
    // 总页数
    protected $totalpage = 0;
    // 从第几条数据开始
    protected $index = 0;

    public function _initialize()
    {
        parent::_initialize();
        $this->type = Cookie::get('think_var');
        // 获取分页参数
        $this->page = input('get.page') ? intval(input('get.page'))
            : $this->page;
        if ($this->page <= 0) {
            $this->page = 1;
        }
        $this->index = ($this->page - 1) * $this->pagesize;
    }

    //套餐列表
    public function getList(){
        $fieldname = 'id,name_cn,level,price,days,per,per2,per3,introduce,explain,risk,affirming,image';
        if ($this->type == 'en') {
            $fieldname = 'id,name_en as name_cn,level,price,days,per,per3,per2,introduce_en as introduce,risk_en as risk,affirming_en as affirming,explain_en as `explain`,image';
        }
        $data = db('tc_manage')->where('id','>',0)->field($fieldname)->select();
        $this->success('success',$data);
    }

    //套餐详情
    public function detail(){
        $id = input('get.id/d');
        $fieldname = 'id,name_cn,level,price,days,per,per3,per2,introduce,explain,risk,affirming,image';
        if ($this->type == 'en') {
            $fieldname = 'id,name_en as name_cn,level,price,days,per,per3,per2,introduce_en as introduce,risk_en as risk,affirming_en as affirming,explain_en as `explain`,image';
        }
        if ($id) {
            $data = db('tc_manage')->where('id',$id)->field($fieldname)->find();
            if ($data) {
                //获取差额
                $price = db('tc_order')->where('uid',$this->auth->id)->where('status',0)->order('price','desc')->value('price');
                $price = $data['price'] - $price;
                //差额
                $data['difference'] = $price > 0 ? $price : __('Unable to buy');
                $this->success('success',$data);
            }else{
                $this->error(__('params_error'));
            }
        }else{
            $this->error(__('params_error'));
        }
    }

    //购买算力套餐，每个套餐会员只能购买一次
    public function buyTc(){
        //防止重复点击
        //获取实例化的redis对象
        $redis = rds();
        if ($redis->get('buytc_'.$this->auth->id)) {
            $this->error(__('Frequent_operation'));
        }
        $redis->set('buytc_'.$this->auth->id,1);
        //设置为60秒过期，防止特殊情况没有修改到该值而导致锁死
        $redis->expireAt('buytc_'.$this->auth->id,60);

        $id = input('post.id/d');
        $paypwd = input('post.paypwd/s');
        $agreement = input('post.agreement/d');
        //需要同意某某协议
        if ($agreement == 0) {
            $redis->del('buytc_'.$this->auth->id);
            $redis->close();
            $this->error(__('Agreement'));
        }
        if ($id && $paypwd) {
            //首先判断密码是否正确
            $pwd = db('user_detail')->where('uid',$this->auth->id)->value('paypwd');
            if ($pwd == md5(md5($paypwd).$this->auth->salt)) {
                //获取该套餐的信息
                $info = db('tc_manage')->where('id',$id)->find();
                if ($info) {
                    //判断该会员是否已经购买过改套餐->where('tcid',$id)
                    $ishas = db('tc_order')->where('uid',$this->auth->id)->where('status',0)->value('id');
                    if (!$ishas) {
                        //可以购买
                        //获取用户当前的购买的最高套餐的价格
                        $price = db('tc_order')->where('uid',$this->auth->id)->where('status',0)->order('price','desc')->value('price');
                        $chajia = $info['price'] - $price;
                        if ($chajia > 0) {
                            //获取当前用户的usdt(credit2)
                            $credit2 = db('user')->where('id',$this->auth->id)->value('credit2');
                            if ($credit2 >= $chajia) {
                                $tm = time();
                                Db::startTrans();
                                try {
                                    setCc($this->auth->username,'credit2',-abs($chajia),'购买算力套餐['.$info['name_cn'].']，扣除 '.$chajia.config('site.creidt2_text'),'Buy arithmetic package ['.$info['name_cn'].'], deduct '.$chajia.config('site.credit2_text'),'buytc');
                                    $data = [
                                        'uid' => $this->auth->id,
                                        'tcid' => $info['id'],
                                        'username' => $this->auth->username,
                                        'price' => $info['price'],
                                        'price_d' => $chajia,
                                        'days' => $info['days'],
                                        'days_d' => $info['days'],
                                        'duetime' => $tm+$info['days']*86400,
                                        'createtime' => $tm,
                                        'updatetime' => $tm
                                    ];
                                    db('tc_order')->insert($data);

                                    //购买套餐直接升级
                                    if ($info['level'] > $this->auth->level) {
                                        db('user')->where('id',$this->auth->id)->update(['level' => $info['level']]);
                                        $this->auth->level = $info['level'];
                                    }

                                    //累加上级的业绩(团队投资总金额)
                                    $tjstr = db('user_detail')->where('uid',$this->auth->id)->value('tjstr');
                                    if ($tjstr) {
                                        $tjarr = explode(',',$tjstr);
                                        if ($tjarr) {
                                            $uinfo = null;
                                            $prefix = config('database.prefix');
                                            foreach ($tjarr as $val) {
                                                $uinfo[] = 'update '.$prefix.'user set team_usdt = team_usdt + '.$chajia.' where id = '.$val;
                                            }
                                            if ($uinfo) {
                                                db()->batchQuery($uinfo);
                                            }
                                        }
                                    }
                                    Db::commit();
                                } catch (\Exception $e) {
                                    Db::rollback();
                                    //日志记录
                                    plog($this->auth->username.'购买算力套餐['.$info['name_cn'].']',$e->getMessage());

                                    $redis->del('buytc_'.$this->auth->id);
                                    $redis->close();

                                    $this->error(__('buyfail'));
                                }

                                //触发身份升级
                                Identityup::autolevelup($this->auth->id);

                                //触发节点奖励
                                Procevent::dsell_event(['id' => $this->auth->id,'money' => $chajia],'jdjl');

                                //触发直推奖
                                Procevent::dsell_event(['id' => $this->auth->id,'money' => $chajia],'ztj');

                                $redis->del('buytc_'.$this->auth->id);
                                $redis->close();
                                $this->success(__('buysuccess'));
                            }else{
                                $redis->del('buytc_'.$this->auth->id);
                                $redis->close();
                                //余额不足
                                $this->error(__('nomoney'));
                            }
                        }else{
                            $redis->del('buytc_'.$this->auth->id);
                            $redis->close();
                            //不能购买低于当前等级的算力套餐
                            $this->error(__('nobuy'));
                        }
                    }else{
                        $redis->del('buytc_'.$this->auth->id);
                        $redis->close();
                        //已经购买过，不能再次购买
                        $this->error(__('purchased'));
                    }
                }else{
                    $redis->del('buytc_'.$this->auth->id);
                    $redis->close();
                    //参数错误，查不到套餐信息
                    $this->error(__('params_error'));
                }
            }else{
                $redis->del('buytc_'.$this->auth->id);
                $redis->close();
                //交易密码错误
                $this->error(__('password_error'));
            }
        }else{
            $redis->del('buytc_'.$this->auth->id);
            $redis->close();
            //参数错误
            $this->error(__('params_error'));
        }
    }

    //获取购买协议
    public function getAgreement(){
        $xy = config('site.jy_tc_xieyi');
        if ($this->type == 'en') {
            $xy = config('site.jy_tc_xieyi_en');
        }
        $this->success('success',['agreement' => $xy]);
    }

    //我的算力套餐
    public function mysellList(){
        //0是收益中，1是已过期，all为全部
        $status = input('get.status');
        $data = null;
        if ($status == 'all') {
            $data = db('tc_order')->where('uid',$this->auth->id)->order('id','desc')->limit($this->index,$this->pagesize)->select();
        }else{
            $status = input('get.status/d');
            $data = db('tc_order')->where('uid',$this->auth->id)->where('status',$status)->order('id','desc')->limit($this->index,$this->pagesize)->select();
        }
        $fieldname = 'id,name_cn,level,price,days,per,introduce,explain,risk,affirming,image';
        if ($this->type == 'en') {
            $fieldname = 'id,name_en as name_cn,level,price,days,per,introduce_en as introduce,risk_en as risk,affirming_en as affirming,explain_en as `explain`,image';
        }
        $tc = db('tc_manage')->column($fieldname,'id');
        foreach ($data as &$val) {
            $a = $tc[$val['tcid']];
            unset($a['id']);
            $val = array_merge($val,$a);
        }
        unset($val);

        //算力套餐总押金
        $total_money = db('tc_order')->where('uid',$this->auth->id)->sum('price_d');
        //合计收益
        $money = db('tc_order')->where('uid',$this->auth->id)->sum('money');
        $this->success('success',['data' => $data,'total_money' => $total_money,'money' => $money]);
    }

    //获取可升级的套餐
    public function getUpTc(){
        $info = db('tc_order')->where('status',0)->where('uid',$this->auth->id)->order('id','desc')->find();
        $level = db('tc_manage')->where('id',$info['tcid'])->value('level');
        $tc = null;
        if ($info) {
            $fieldname = 'id,name_cn,level,price,days,per,image';
            if ($this->type == 'en') {
                $fieldname = 'id,name_en as name_cn,level,price,days,per,image';
            }
            $tc = db('tc_manage')->where('level','>',$level)->field($fieldname)->select();
            foreach ($tc as &$val) {
                $val['difference'] = $val['price'] - $info['price'];
            }
            unset($val);
        }
        $zy = $this->type == 'en' ? config('site.tc_up_zy_en') : config('site.tc_up_zy');
        $this->success('success',['data' => $tc,'zy' => $zy]);
    }

    //升级套餐
    public function tcUp(){
        //防止重复点击
        //获取实例化的redis对象
        $redis = rds();
        if ($redis->get('tcUp_'.$this->auth->id)) {
            $this->error(__('Frequent_operation'));
        }
        $redis->set('tcUp_'.$this->auth->id,1);
        //设置为60秒过期，防止特殊情况没有修改到该值而导致锁死
        $redis->expireAt('tcUp_'.$this->auth->id,60);

        $id = input('post.id/d');
        $paypwd = input('post.paypwd/s');
        if ($id && $paypwd){
            $pwd = db('user_detail')->where('uid',$this->auth->id)->value('paypwd');
            if ($pwd == md5(md5($paypwd).$this->auth->salt)){
                //获取当前会员最高等级的套餐
                $to = db('tc_order')->where('status',0)->where('uid',$this->auth->id)->order('id','desc')->find();
                $tcm = db('tc_manage')->where('id','>',0)->column('level,days,per,price,name_cn','id');
                if ($tcm[$to['tcid']]['level'] < $tcm[$id]['level']) {
                    $chajia = $tcm[$id]['price'] - $to['price'];
                    if ($chajia > 0) {
                        //获取当前用户的余额
                        //获取当前用户的usdt(credit2)
                        $credit2 = db('user')->where('id',$this->auth->id)->value('credit2');
                        if ($credit2 >= $chajia) {
                            $tm = time();
                            Db::startTrans();
                            try {
                                setCc($this->auth->username,'credit2',-abs($chajia),'算力套餐升级为['.$tcm[$id]['name_cn'].']，扣除 '.$chajia.config('site.creidt2_text'),'The arithmetic package upgraded to ['.$tcm[$id]['name_cn'].'], deduct '.$chajia.config('site.credit2_text'),'buytc');
                                $data = [
                                    'uid' => $this->auth->id,
                                    'tcid' => $tcm[$id]['id'],
                                    'username' => $this->auth->username,
                                    'price' => $tcm[$id]['price'],
                                    'price_d' => $chajia,
                                    'days' => $tcm[$id]['days'],
                                    'days_d' => $tcm[$id]['days'],
                                    'duetime' => $tm+$tcm[$id]['days']*86400,
                                    'createtime' => $tm,
                                    'updatetime' => $tm
                                ];
                                db('tc_order')->insert($data);

                                //购买套餐直接升级
                                if ($tcm[$id]['level'] > $this->auth->level) {
                                    db('user')->where('id',$this->auth->id)->update(['level' => $tcm[$id]['level']]);
                                    $this->auth->level = $tcm[$id]['level'];
                                }

                                //累加上级的业绩(团队投资总金额)
                                $tjstr = db('user_detail')->where('uid',$this->auth->id)->value('tjstr');
                                if ($tjstr) {
                                    $tjarr = explode(',',$tjstr);
                                    if ($tjarr) {
                                        $uinfo = null;
                                        $prefix = config('database.prefix');
                                        foreach ($tjarr as $val) {
                                            $uinfo[] = 'update '.$prefix.'user set team_usdt = team_usdt + '.$chajia.' where id = '.$val;
                                        }
                                        if ($uinfo) {
                                            db()->batchQuery($uinfo);
                                        }
                                    }
                                }
                                Db::commit();
                            } catch (\Exception $e) {
                                Db::rollback();
                                //日志记录
                                plog($this->auth->username.'算力套餐升级['.$tcm[$id]['name_cn'].']',$e->getMessage());

                                $redis->del('tcUp_'.$this->auth->id);
                                $redis->close();

                                $this->error(__('upfail'));
                            }

                            //触发身份升级
                            Identityup::autolevelup($this->auth->id);

                            //触发节点奖励
                            Procevent::dsell_event(['id' => $this->auth->id,'money' => $chajia],'jdjl');

                            //触发直推奖
//                            Procevent::dsell_event($this->auth->id,'ztj');
                            Procevent::dsell_event(['id' => $this->auth->id,'money' => $chajia],'ztj');


                            $redis->del('tcUp_'.$this->auth->id);
                            $redis->close();
                            $this->success(__('upsuccess'));
                        }else{
                            $redis->del('tcUp_'.$this->auth->id);
                            $redis->close();
                            //余额不足
                            $this->error(__('nomoney'));
                        }
                    }else{
                        //差额为负数
                        $redis->del('tcUp_'.$this->auth->id);
                        $redis->close();
                        //参数错误
                        $this->error(__('params_error'));
                    }
                }else{
                    //升级的套餐等级比现有的套餐最高等级小
                    $redis->del('tcUp_'.$this->auth->id);
                    $redis->close();
                    //参数错误
                    $this->error(__('params_error'));
                }

            }else{
                $redis->del('tcUp_'.$this->auth->id);
                $redis->close();
                //交易密码错误
                $this->error(__('password_error'));
            }
        }else{
            $redis->del('tcUp_'.$this->auth->id);
            $redis->close();
            //参数错误
            $this->error(__('params_error'));
        }
    }

    //我的套餐详情
    public function myTcDetail(){
        $id = input('get.id/d');
        $fieldname = 'id,name_cn,level,price,days,per,per3,per2,introduce,explain,risk,affirming';
        if ($this->type == 'en') {
            $fieldname = 'id,name_en as name_cn,level,price,days,per,per3,per2,introduce_en as introduce,risk_en as risk,affirming_en as affirming,explain_en as `explain`';
        }
        if ($id) {
            $info = db('tc_order')->where('id',$id)->where('uid',$this->auth->id)->where('status',0)->find();
            if (!$info) {
                $this->error(__('params_error'));
            }
            $data = db('tc_manage')->where('id',$info['tcid'])->field($fieldname)->find();
            if ($data) {
                //获取差额
                $tcid = db('tc_order')->where('uid',$this->auth->id)->where('status',0)->order('price','desc')->value('tcid');
                $price = $data['price'] - $info['price'];
                //差额
                $data['difference'] = $price > 0 ? $price : __('Unable to buy');
                //是否可以升级
                $data['levelup'] = $tcid == $info['tcid'] ? 1 : 0;
                $data['fs'] = __('fbfs');

                $this->success('success',array_merge($info,$data));
            }else{
                $this->error(__('params_error'));
            }
        }else{
            $this->error(__('params_error'));
        }
    }
}
