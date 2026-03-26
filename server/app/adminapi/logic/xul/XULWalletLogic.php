<?php
/**
 * XUL 链钱包登录逻辑
 * 
 * 功能：
 * 1. 生成一次性挑战消息
 * 2. 验证钱包签名
 * 3. 登录/注册用户
 * 4. 绑定钱包到账号
 */

namespace app\adminapi\logic\xul;

use app\common\logic\BaseLogic;
use app\common\model\auth\Admin;
use app\adminapi\service\AdminTokenService;
use app\common\service\xul\XULChainService;
use think\facade\Cache;
use think\facade\Config;

/**
 * XUL 钱包登录逻辑
 * Class XULWalletLogic
 * @package app\adminapi\logic\xul
 */
class XULWalletLogic extends BaseLogic
{
    /**
     * 挑战消息过期时间（秒）
     */
    const CHALLENGE_EXPIRE = 300; // 5分钟

    /**
     * 生成登录挑战消息
     * 
     * @param string $address 钱包地址
     * @return array
     */
    public function generateChallenge(string $address): array
    {
        // 生成随机挑战
        $challenge = bin2hex(random_bytes(32));
        
        // 构建签名消息
        $timestamp = time();
        $message = "XUL Login Challenge\n\nAddress: {$address}\nChallenge: {$challenge}\nTimestamp: {$timestamp}\n\nSign this message to verify wallet ownership.";
        
        // 缓存挑战用于后续验证
        $cacheKey = "xul_challenge:{$address}";
        Cache::set($cacheKey, [
            'challenge' => $challenge,
            'timestamp' => $timestamp,
            'message' => $message
        ], self::CHALLENGE_EXPIRE);
        
        return [
            'message' => $message,
            'timestamp' => $timestamp,
            'expires_in' => self::CHALLENGE_EXPIRE
        ];
    }

    /**
     * 验证签名
     * 
     * @param string $address 钱包地址
     * @param string $message 签名消息
     * @param string $signature 签名
     * @return bool
     */
    public function verifySignature(string $address, string $message, string $signature): bool
    {
        try {
            return XULChainService::verifySignature($address, $message, $signature);
        } catch (\Exception $e) {
            $this->logError('签名验证失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 钱包登录/注册
     * 
     * @param array $params
     * @return array
     */
    public function walletLogin(array $params): array
    {
        $address = strtolower($params['address']);
        
        // 验证签名
        if (!$this->verifySignature($params['address'], $params['message'], $params['signature'])) {
            return ['code' => 0, 'msg' => '签名验证失败'];
        }
        
        // 检查挑战是否过期或被使用
        $cacheKey = "xul_challenge:{$address}";
        $cached = Cache::get($cacheKey);
        if (!$cached) {
            return ['code' => 0, 'msg' => '挑战已过期，请重新获取'];
        }
        
        // 验证消息匹配
        if ($cached['message'] !== $params['message']) {
            return ['code' => 0, 'msg' => '签名消息不匹配'];
        }
        
        // 清除挑战
        Cache::delete($cacheKey);
        
        // 查找或创建用户
        $admin = Admin::where('wallet_address', '=', $address)->find();
        
        if (!$admin) {
            // 新用户注册
            $admin = new Admin();
            $admin->account = 'xul_' . substr($address, 2, 8);
            $admin->wallet_address = $address;
            $admin->create_time = time();
            $admin->login_time = time();
            $admin->login_ip = request()->ip();
            $admin->save();
        } else {
            // 老用户更新登录信息
            $admin->login_time = time();
            $admin->login_ip = request()->ip();
            $admin->save();
        }
        
        // 生成 Token
        $adminInfo = AdminTokenService::setToken($admin->id, $params['terminal'] ?? 'h5', false);
        
        return [
            'name' => $adminInfo['name'],
            'avatar' => $admin->avatar ?? Config::get('project.default_image.admin_avatar'),
            'role_name' => $adminInfo['role_name'],
            'token' => $adminInfo['token'],
            'wallet_address' => $address,
            'is_new_user' => !$admin->login_time || $admin->login_time == $admin->create_time
        ];
    }

    /**
     * 绑定钱包到现有账号
     * 
     * @param array $adminInfo 当前登录用户信息
     * @param array $params 参数
     * @return array
     */
    public function bindWallet(array $adminInfo, array $params): array
    {
        $address = strtolower($params['address']);
        
        // 验证签名
        if (!$this->verifySignature($params['address'], $params['message'], $params['signature'])) {
            return ['code' => 0, 'msg' => '签名验证失败'];
        }
        
        // 检查地址是否已被其他账号绑定
        $exists = Admin::where('wallet_address', '=', $address)
            ->where('id', '<>', $adminInfo['admin_id'])
            ->find();
        
        if ($exists) {
            return ['code' => 0, 'msg' => '该钱包地址已被其他账号绑定'];
        }
        
        // 更新绑定
        Admin::update(['wallet_address' => $address], ['id' => $adminInfo['admin_id']]);
        
        return [
            'code' => 1,
            'msg' => '绑定成功',
            'wallet_address' => $address
        ];
    }
}
