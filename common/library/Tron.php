<?php

namespace app\common\library;

use app\common\library\tron\Tron as Trons;
use app\common\library\tron\Provider\HttpProvider;
use think\Log;


class Tron
{
    protected $_error = '';

    protected $tron = null;

    protected $config = [];

    public function __construct()
    {
        $this->config = $config = Config('transaction');
        $fullNode = new HttpProvider($config['fullNode_url']);
        $solidityNode = new HttpProvider($config['solidityNode_url']);
        $eventServer = new HttpProvider($config['eventServer_url']);
        try {
            $this->tron = new Trons($fullNode, $solidityNode, $eventServer);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取交易结果
     * @param $txid
     * @return bool|int
     */
    public function getTransaction($txid){
        try {
            $res = $this->tron->getTransaction($txid);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return 2;
        }

        if($res['ret'][0]['contractRet'] == 'SUCCESS'){
            return true;
        }else{
            return false;
        }
    }



    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }

}
