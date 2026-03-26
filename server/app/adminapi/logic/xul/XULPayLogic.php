<?php
/**
 * XUL 代币支付逻辑
 * 
 * 功能：
 * 1. 创建支付订单
 * 2. 链上支付验证
 * 3. 余额管理
 */

namespace app\adminapi\logic\xul;

use app\common\logic\BaseLogic;
use app\common\model\pay\PayOrder;
use app\common\service\xul\XULChainService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Config;

/**
 * XUL 代币支付逻辑
 * Class XULPayLogic
 * @package app\adminapi\logic\xul
 */
class XULPayLogic extends BaseLogic
{
    /**
     * 系统收款地址
     */
    const PAYMENT_ADDRESS = '0xC2F803f72033210718dbF150301b5A88Bb2C12CC';

    /**
     * 代币精度
     */
    const DECIMALS = 18;

    /**
     * 获取支付地址
     */
    public function getPayAddress(): array
    {
        return [
            'xul_address' => self::PAYMENT_ADDRESS,
            'chain_id' => 12309,
            'network' => 'XUL Mainnet',
            'symbol' => 'XUL'
        ];
    }

    /**
     * 创建支付订单
     */
    public function createOrder(array $adminInfo, array $params): array
    {
        $money = floatval($params['money'] ?? 0); // 人民币金额
        $payType = $params['pay_type'] ?? 'xul'; // xul 或 power
        
        if ($money <= 0) {
            return ['code' => 0, 'msg' => '金额必须大于0'];
        }

        // 根据支付类型计算代币数量
        if ($payType == 'xul') {
            // XUL 支付，按市价计算
            $rate = $this->getXULRate(); // 获取 XUL 对人民币汇率
            $amount = $money / $rate;
        } else {
            // POWER 代币，1:1000 铸造
            $amount = $money * 1000;
        }

        // 生成订单
        $orderNo = 'XUL' . date('YmdHis') . rand(1000, 9999);
        
        $orderData = [
            'order_no' => $orderNo,
            'user_id' => $adminInfo['admin_id'],
            'money' => $money,
            'pay_type' => $payType,
            'amount' => $amount, // 代币数量
            'from_address' => '', // 用户钱包地址（支付时填写）
            'status' => 0, // 0=待支付, 1=已支付, 2=已取消, 3=已过期
            'create_time' => time(),
            'expire_time' => time() + 3600, // 1小时过期
            'pay_address' => self::PAYMENT_ADDRESS
        ];

        Db::name('xul_pay_order')->insert($orderData);

        return [
            'order_no' => $orderNo,
            'amount' => round($amount, 6),
            'pay_address' => self::PAYMENT_ADDRESS,
            'pay_type' => $payType,
            'expire_time' => $orderData['expire_time'],
            'qr_data' => "{$payType}:{$orderNo}:{$amount}"
        ];
    }

    /**
     * 检查支付状态
     */
    public function checkPayment(string $orderNo): array
    {
        $order = Db::name('xul_pay_order')->where('order_no', $orderNo)->find();
        
        if (!$order) {
            return ['code' => 0, 'msg' => '订单不存在'];
        }

        // 如果已支付，直接返回
        if ($order['status'] == 1) {
            return [
                'status' => 'paid',
                'msg' => '支付成功'
            ];
        }

        // 如果已取消或过期
        if ($order['status'] == 2 || $order['status'] == 3) {
            return [
                'status' => $order['status'] == 2 ? 'cancelled' : 'expired',
                'msg' => $order['status'] == 2 ? '已取消' : '已过期'
            ];
        }

        // 检查链上是否到账
        if (!empty($order['from_address']) && !empty($order['tx_hash'])) {
            $txReceipt = XULChainService::getTransactionReceipt($order['tx_hash']);
            
            if ($txReceipt && $txReceipt['status'] === '0x1') {
                // 交易成功，更新订单
                Db::name('xul_pay_order')->where('order_no', $orderNo)->update([
                    'status' => 1,
                    'pay_time' => time()
                ]);
                
                return [
                    'status' => 'paid',
                    'msg' => '支付成功'
                ];
            }
        }

        return [
            'status' => 'pending',
            'msg' => '等待支付'
        ];
    }

    /**
     * 取消订单
     */
    public function cancelOrder(array $adminInfo, string $orderNo): array
    {
        $order = Db::name('xul_pay_order')
            ->where('order_no', $orderNo)
            ->where('user_id', $adminInfo['admin_id'])
            ->find();

        if (!$order) {
            return ['code' => 0, 'msg' => '订单不存在'];
        }

        if ($order['status'] != 0) {
            return ['code' => 0, 'msg' => '订单无法取消'];
        }

        Db::name('xul_pay_order')->where('order_no', $orderNo)->update([
            'status' => 2
        ]);

        return ['code' => 1, 'msg' => '取消成功'];
    }

    /**
     * 获取用户余额
     */
    public function getBalance(array $adminInfo): array
    {
        $user = Db::name('admin')->where('id', $adminInfo['admin_id'])->find();
        
        // 如果有绑定钱包，查链上余额
        $chainBalance = 0;
        if (!empty($user['wallet_address'])) {
            $chainBalance = XULChainService::getBalance($user['wallet_address']);
        }

        return [
            'wallet_address' => $user['wallet_address'] ?? '',
            'chain_balance' => round($chainBalance, 6), // 链上余额
            'platform_balance' => $user['xul_balance'] ?? 0, // 平台余额
            'power_balance' => $user['power_balance'] ?? 0 // POWER 代币余额
        ];
    }

    /**
     * 确认充值
     */
    public function confirmDeposit(array $adminInfo, array $params): array
    {
        $txHash = $params['tx_hash'] ?? '';
        $amount = floatval($params['amount'] ?? 0);
        
        if (empty($txHash) || $amount <= 0) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        // 验证交易
        try {
            $receipt = XULChainService::getTransactionReceipt($txHash);
            
            if (!$receipt || $receipt['status'] !== '0x1') {
                return ['code' => 0, 'msg' => '交易未确认或失败'];
            }

            // 验证收款地址和金额
            $to = $receipt['to'];
            $value = hexdec($receipt['value']) / pow(10, self::DECIMALS);

            if (strtolower($to) !== strtolower(self::PAYMENT_ADDRESS)) {
                return ['code' => 0, 'msg' => '收款地址错误'];
            }

            if ($value < $amount) {
                return ['code' => 0, 'msg' => '金额不足'];
            }

            // 增加用户余额
            Db::name('admin')->where('id', $adminInfo['admin_id'])->inc('xul_balance', $value)->update();

            return [
                'code' => 1,
                'msg' => '充值成功',
                'amount' => $value
            ];

        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => '验证失败: ' . $e->getMessage()];
        }
    }

    /**
     * 获取支付二维码数据
     */
    public function getPayQR(string $orderNo): array
    {
        $order = Db::name('xul_pay_order')->where('order_no', $orderNo)->find();
        
        if (!$order) {
            return ['code' => 0, 'msg' => '订单不存在'];
        }

        // 生成支付链接
        $payData = json_encode([
            'order' => $orderNo,
            'amount' => $order['amount'],
            'to' => self::PAYMENT_ADDRESS
        ]);

        return [
            'qr_content' => base64_encode($payData),
            'pay_address' => self::PAYMENT_ADDRESS,
            'amount' => $order['amount'],
            'symbol' => strtoupper($order['pay_type'])
        ];
    }

    /**
     * 获取 XUL 对人民币汇率
     */
    private function getXULRate(): float
    {
        // TODO: 从交易所或预言机获取实时汇率
        // 暂时返回固定值
        return 0.001; // 1 CNY = 1000 XUL
    }
}
