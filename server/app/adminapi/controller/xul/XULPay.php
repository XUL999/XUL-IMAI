<?php
/**
 * XUL 代币支付控制器
 * 
 * 功能：XUL/POWER 代币支付
 */

namespace app\adminapi\controller\xul;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\xul\XULPayLogic;

/**
 * XUL 代币支付
 * Class XULPay
 * @package app\adminapi\controller\xul
 */
class XULPay extends BaseAdminController
{
    /**
     * 获取支付地址
     * 
     * @notes 获取系统收款地址
     * @return \think\response\Json
     */
    public function getPayAddress()
    {
        $result = (new XULPayLogic())->getPayAddress();
        return $this->data($result);
    }

    /**
     * 创建支付订单
     * 
     * @notes 创建代币支付订单
     * @return \think\response\Json
     */
    public function createOrder()
    {
        $params = input('post.');
        $result = (new XULPayLogic())->createOrder($this->adminInfo, $params);
        return $this->data($result);
    }

    /**
     * 支付结果查询
     * 
     * @notes 查询链上支付是否到账
     * @return \think\response\Json
     */
    public function checkPayment()
    {
        $orderNo = input('order_no', '');
        $result = (new XULPayLogic())->checkPayment($orderNo);
        return $this->data($result);
    }

    /**
     * 取消订单
     * 
     * @return \think\response\Json
     */
    public function cancelOrder()
    {
        $orderNo = input('order_no', '');
        $result = (new XULPayLogic())->cancelOrder($this->adminInfo, $orderNo);
        return $this->data($result);
    }

    /**
     * 获取用户余额
     * 
     * @notes 获取用户在平台的钱包余额
     * @return \think\response\Json
     */
    public function getBalance()
    {
        $result = (new XULPayLogic())->getBalance($this->adminInfo);
        return $this->data($result);
    }

    /**
     * 充值（链上转账确认）
     * 
     * @notes 用户转账后调用，确认充值
     * @return \think\response\Json
     */
    public function confirmDeposit()
    {
        $params = input('post.');
        $result = (new XULPayLogic())->confirmDeposit($this->adminInfo, $params);
        return $this->data($result);
    }

    /**
     * 获取支付二维码
     * 
     * @notes 生成支付二维码内容
     * @return \think\response\Json
     */
    public function getPayQR()
    {
        $orderNo = input('order_no', '');
        $result = (new XULPayLogic())->getPayQR($orderNo);
        return $this->data($result);
    }
}
