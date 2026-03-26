<?php
/**
 * XUL 链服务
 * 
 * 功能：
 * 1. 连接 XUL 链 RPC
 * 2. 签名验证
 * 3. SBT 身份查询
 * 4. 代币支付
 */

namespace app\common\service\xul;

use think\facade\Cache;
use think\facade\Config;

/**
 * XUL 链服务
 * Class XULChainService
 * @package app\common\service\xul
 */
class XULChainService
{
    /**
     * XUL 链 RPC URL
     */
    const RPC_URL = 'https://pro.rswl.ai';

    /**
     * Chain ID
     */
    const CHAIN_ID = '12309';

    /**
     * SBT 合约地址
     */
    const SBT_CONTRACT = '0x4BBC7F4f6d0c14571f58619A0125EAE056F9fD6a';

    /**
     * HTTP 请求超时
     */
    const TIMEOUT = 10;

    /**
     * 发送 RPC 请求
     * 
     * @param string $method 方法名
     * @param array $params 参数
     * @return mixed
     */
    public static function rpc(string $method, array $params = [])
    {
        $ch = curl_init(self::RPC_URL);
        
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => time()
        ]);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => self::TIMEOUT
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new \Exception($result['error']['message'] ?? 'RPC Error');
        }
        
        return $result['result'] ?? null;
    }

    /**
     * 验证钱包签名
     * 
     * @param string $address 钱包地址
     * @param string $message 签名消息
     * @param string $signature 签名
     * @return bool
     */
    public static function verifySignature(string $address, string $message, string $signature): bool
    {
        try {
            // 使用 eth_personal_ecRecover 验证签名
            $hash = self::hashMessage($message);
            $recovered = self::rpc('personal_ecRecover', [$message, $signature]);
            
            if (!$recovered) {
                return false;
            }
            
            // 统一小写比较
            return strtolower($recovered) === strtolower($address);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 消息哈希
     * 
     * @param string $message 消息
     * @return string
     */
    public static function hashMessage(string $message): string
    {
        $prefix = "\x19Ethereum Signed Message:\n" . strlen($message);
        return hash('sha256', $prefix . $message);
    }

    /**
     * 获取链上信息
     * 
     * @return array
     */
    public static function getChainInfo(): array
    {
        $block = self::rpc('eth_getBlockByNumber', ['latest', false]);
        $network = self::rpc('eth_chainId');
        
        return [
            'chain_id' => hexdec($network),
            'block_number' => hexdec($block['number'] ?? '0x0'),
            'gas_limit' => hexdec($block['gasLimit'] ?? '0x0'),
            'timestamp' => hexdec($block['timestamp'] ?? '0x0')
        ];
    }

    /**
     * 获取地址余额
     * 
     * @param string $address 钱包地址
     * @return float
     */
    public static function getBalance(string $address): float
    {
        $balance = self::rpc('eth_getBalance', [$address, 'latest']);
        return hexdec($balance) / 1e18;
    }

    /**
     * 查询 SBT 身份
     * 
     * @param string $address 钱包地址
     * @return array|null
     */
    public static function getSBTIdentity(string $address): ?array
    {
        try {
            // 调用 SBT 合约的 getInfo 方法
            $data = self::callContract(
                self::SBT_CONTRACT,
                'getInfo(address)',
                [$address]
            );
            
            if (!$data || $data[3] == '0x0') {
                return null;
            }
            
            return [
                'name' => $data[0],
                'description' => $data[1],
                'avatar' => $data[2],
                'created' => hexdec($data[3]),
                'score' => hexdec($data[4]),
                'active' => $data[5] === '0x01'
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 调用合约（只读）
     * 
     * @param string $contract 合约地址
     * @param string $method 方法签名
     * @param array $params 参数
     * @return array|null
     */
    public static function callContract(string $contract, string $method, array $params = []): ?array
    {
        $funcSelector = substr(hash('sha256', $method), 0, 8);
        
        $data = '0x' . $funcSelector;
        foreach ($params as $param) {
            $data .= self::encodeParam($param);
        }
        
        return self::rpc('eth_call', [
            ['to' => $contract, 'data' => $data],
            'latest'
        ]);
    }

    /**
     * 编码参数
     * 
     * @param mixed $param
     * @return string
     */
    private static function encodeParam($param): string
    {
        if (is_int($param)) {
            return str_pad(dechex($param), 64, '0', STR_PAD_LEFT);
        }
        if (is_string($param) && strlen($param) == 42 && substr($param, 0, 2) == '0x') {
            return str_pad(substr($param, 2), 64, '0', STR_PAD_LEFT);
        }
        return str_pad('', 64, '0', STR_PAD_RIGHT);
    }

    /**
     * 发送代币（需要私钥）
     * 
     * @param string $from 发送方私钥
     * @param string $to 接收方地址
     * @param float $amount 金额
     * @return string 交易哈希
     */
    public static function sendTransaction(string $from, string $to, float $amount): string
    {
        $value = '0x' . dechex((int)($amount * 1e18));
        
        $tx = [
            'from' => self::privateKeyToAddress($from),
            'to' => $to,
            'value' => $value,
            'gas' => '0x5208',
            'gasPrice' => self::rpc('eth_gasPrice')
        ];
        
        // 签名交易（需要 eth_accounts 支持）
        // 这里需要服务端有私钥，谨慎使用
        return self::rpc('eth_sendTransaction', [$tx]);
    }

    /**
     * 私钥转地址
     * 
     * @param string $privateKey 私钥
     * @return string 地址
     */
    public static function privateKeyToAddress(string $privateKey): string
    {
        $pubkey = self::rpc('personal_importRawKey', [$privateKey, '']);
        return $pubkey;
    }
}
