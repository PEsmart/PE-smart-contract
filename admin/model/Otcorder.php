<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14
 * Time: 11:17
 */
namespace app\admin\model;

use think\Exception;
use think\Model;

class Otcorder extends Model
{
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';

    protected $table = 'fa_otc_order';

    public $error_ts = '';

    /**
     *  确认订单  定时任务也在调用 请勿随意更改
     *
     */
    public function dealOrder($order, $operation=0)
    {
        if (empty($order) || !is_array($order)) {
            return false;
        }

        if (!in_array($operation, [2,4])) {
            $this->error_ts = '操作方式有误';
            return false;
        }

        if (empty($order['buname'])) {
            $this->error_ts = '订单还未有人参与';
            return false;
        }

        if (!in_array($order['state'], [2,3,10])) {
            $this->error_ts = '当前状态不可完成';
            return false;
        }

        db()->startTrans();
        try {
            db('otc_order')
                ->where("id={$order['id']}")
                ->update(['state' => 4, 'dealtime' => time(), 'operation' => $operation]);

            $amount1 = $order['amount1'];   //卖出数量
            $amount2 = $order['amount2'];   //到账数量

            $credit2_text = config('site.credit2_text');

            //如果是买单
            if ($order['type'] == 1) {

                //发起人 加币 到账数量

                setCc($order['uuname'], 'credit2', $amount2, "编号为{$order['tradesn']}的订单交易成功，{$order['uuname']}增加{$amount2}".$credit2_text);

                //卖家 减少冻结币 卖出数量

                setCc($order['buname'], 'lock_credit2', -$amount1, "编号为{$order['tradesn']}的订单交易成功，{$order['buname']}减少{$amount1}锁定的".$credit2_text);

            }else if($order['type'] == 2){  //如果是卖单

                //发起人 减少冻结币 卖出数量;

                setCc($order['uuname'], 'lock_credit2', -$amount1, "编号为{$order['tradesn']}的订单交易成功，{$order['uuname']}减少{$amount1}锁定的".$credit2_text);

                //买家 加币 到账数量

                setCc($order['buname'], 'credit2', $amount2, "编号为{$order['tradesn']}的订单交易成功，{$order['buname']}增加{$amount2}".$credit2_text);
            }else{
                throw new Exception("订单类型有误");
            }

            db()->commit();
        } catch (\Exception $e) {
            db()->rollback();
            $this->error_ts = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * 订单失效
     * - 订单失效后，币回归原位
     */
    public function overdue($order, $operation=0)
    {
        if (empty($order) || !is_array($order)) {
            return false;
        }

        if (!in_array($operation, [1,3])) {
            $this->error_ts = '操作方式有误';
            return false;
        }

        if (!in_array($order['state'], [1,2,3,10])) {
            $this->error_ts = '当前状态不可取消';
            return false;
        }

        $amount1 = $order['amount1'];  //卖出数量
//        $amount2 = $order['amount2'];  //到手数量
        $tradesn = $order['tradesn'];
        $uuname = $order['uuname']; //发起人 买家/卖家
        $buname = $order['buname']; //委托人 卖家/买家

        db()->startTrans();
        try{
            db('otc_order')
                ->where("id={$order['id']}")
                ->update(['state'=>20, 'losetime' => time(), 'operation' => $operation]); #过期

            $credit2_text = config('site.credit2_text');

            if ($order['losetime'] <= 0) {
                //买单
                if ($order['type'] == 1) {
                    //此情况代表卖家已经发了币给买家 且没有完成过的
                    if ($order['ordertime'] > 0 && !empty($buname) && $order['dealtime'] <= 0) {

                        //委托人 卖家可售币返回amount1，冻结币减少amount1

                        setCc($buname, 'credit2', $amount1, "编号为{$tradesn}的订单取消，{$buname}返还{$amount1}".$credit2_text);
                        setCc($buname, 'lock_credit2', -$amount1, "编号为{$tradesn}的订单取消，{$buname}减少锁定的{$amount1}".$credit2_text);
                    }
                }else if ($order['type'] == 2) { // 卖单
                    //没有完成过的
                    if ($order['dealtime'] <= 0) {
                        //发起人可售币返回amount1，冻结币减少amount1

                        if ($order['state'] == 1) {
                            //如果是待交易状态 需要用剩余数量加上手续费得出要返还的数量
                            $amount1 = $order['remain_amount'] + $order['servicecharge'];
                        }

                        setCc($uuname, 'credit2', $amount1, "编号为{$tradesn}的订单取消，{$uuname}返还{$amount1}".$credit2_text);
                        setCc($uuname, 'lock_credit2', -$amount1, "编号为{$tradesn}的订单取消，{$uuname}减少锁定的{$amount1}".$credit2_text);
                    }
                }
            }

            db()->commit();
        }catch (\Exception $e){
            db()->rollback();
            $this->error_ts = $e->getMessage();
            return false;
        }

        return true;

    }


    /**
     * 撤单
     */
    public function revokeOrder($order)
    {
        if (empty($order) || !is_array($order)) {
            return false;
        }

        if ($order['state'] != 1) {
            $this->error_ts = '订单状态错误';
            return false;
        }

        $amount = $order['remain_amount'] + $order['servicecharge'];  //剩余数量

        $tradesn = $order['tradesn'];
        $uuname = $order['uuname']; //发起人 卖家

        db()->startTrans();
        try{
            db('otc_order')
                ->where("id={$order['id']}")
                ->update(['state'=>30, 'losetime' => time()]); #过期

            $credit2_text = config('site.credit2_text');

            if ($order['losetime'] <= 0) {
                //卖单
                if ($order['type'] == 2) {
                    setCc($uuname, 'credit2', $amount, "编号为{$tradesn}的订单撤单，{$uuname}返还{$amount}".$credit2_text);
                    setCc($uuname, 'lock_credit2', -$amount, "编号为{$tradesn}的订单撤单，{$uuname}减少锁定的{$amount}".$credit2_text);
                }
            }

            db()->commit();
        }catch (\Exception $e){
            db()->rollback();
            $this->error_ts = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * 到时取消订单 (卖单需要特殊处理)
     */
    public function sell_timing_cancel($order)
    {
        if (empty($order) || !is_array($order)) {
            return false;
        }

        if ($order['state'] != 2) {
            $this->error_ts = '订单状态错误';
            return false;
        }

        db()->startTrans();
        try{
            if ($order['type'] == 1) {
                $update = array(
                    'state' => 20,
                    'losetime' => time(),
                    'operation' => 5,
                );
                $amount1 = $order['amount1'];  //卖出数量
                $buname = $order['buname']; //委托人 卖家/买家
                $tradesn = $order['tradesn'];
                $credit2_text = config('site.credit2_text');
                
                //此情况代表卖家已经发了币给买家 且没有完成过的
                if ($order['ordertime'] > 0 && !empty($buname) && $order['dealtime'] <= 0) {

                    //委托人 卖家可售币返回amount1，冻结币减少amount1
                    setCc($buname, 'credit2', $amount1, "编号为{$tradesn}的订单到时取消，{$buname}返还{$amount1}".$credit2_text);
                    setCc($buname, 'lock_credit2', -$amount1, "编号为{$tradesn}的订单到时取消，{$buname}减少锁定的{$amount1}".$credit2_text);
                }

            }else if ($order['type'] == 2) {
                $update = array(
                    'state' => 1,
                    'amount1' => 0,
                    'amount2' => 0,
                    'price' => 0,
                    'ordertime' => 0,
                    'buname' => '',
                    'operation' => 5,
                    'remain_amount' => $order['amount2'],
                );

                //拆单
                if ($order['zoid'] > 0) {
                    $update['minimum'] = $order['amount2'];
                }

            }else{
                throw new Exception("订单类型有误");
            }

            db('otc_order')
                ->where("id={$order['id']}")
                ->update($update);

            db()->commit();
        }catch (\Exception $e){
            db()->rollback();
            $this->error_ts = $e->getMessage();
            return false;
        }

        return true;
    }
}


