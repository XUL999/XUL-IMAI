<?php
/**
 * XUL Agent SBT 逻辑
 * 
 * 功能：
 * 1. 查询链上 SBT 身份
 * 2. 管理本地 Agent 信息
 * 3. 同步链上/链下身份
 */

namespace app\adminapi\logic\xul;

use app\common\logic\BaseLogic;
use app\common\service\xul\XULChainService;
use think\facade\Db;

/**
 * XUL Agent SBT 逻辑
 * Class XULAgentSBTLogic
 * @package app\adminapi\logic\xul
 */
class XULAgentSBTLogic extends BaseLogic
{
    /**
     * SBT 合约地址
     */
    const SBT_CONTRACT = '0x4BBC7F4f6d0c14571f58619A0125EAE056F9fD6a';

    /**
     * 管理员地址（铸造权限）
     */
    const ADMIN_ADDRESS = '0xC2F803f72033210718dbF150301b5A88Bb2C12CC';

    /**
     * 获取 SBT 信息
     */
    public function getInfo(string $address = '', array $adminInfo = []): array
    {
        // 如果没有指定地址，用当前用户的
        if (empty($address)) {
            if (empty($adminInfo['wallet_address'])) {
                return ['code' => 0, 'msg' => '请先绑定钱包地址'];
            }
            $address = $adminInfo['wallet_address'];
        }

        // 查询链上身份
        $chainInfo = XULChainService::getSBTIdentity($address);

        // 查询本地记录
        $localInfo = Db::name('xul_sbt_identity')->where('wallet_address', $address)->find();

        if (!$chainInfo && !$localInfo) {
            return ['code' => 0, 'msg' => '该地址还没有 SBT 身份'];
        }

        return [
            'code' => 1,
            'data' => [
                'wallet_address' => $address,
                'chain' => $chainInfo,
                'local' => $localInfo,
                'is_synced' => !empty($localInfo) && $localInfo['chain_status'] == 1,
                'agent_id' => $chainInfo ? hexdec($chainInfo['agent_id'] ?? '0') : 0
            ]
        ];
    }

    /**
     * 获取技能列表
     */
    public function getSkills(string $address = ''): array
    {
        if (empty($address)) {
            return ['code' => 0, 'msg' => '地址不能为空'];
        }

        try {
            $data = XULChainService::callContract(self::SBT_CONTRACT, 'getSkills(address)', [$address]);
            
            if (!$data || $data === '0x') {
                return ['code' => 1, 'data' => []];
            }

            // 解析技能列表
            $skills = $this->decodeStringArray($data);
            
            return ['code' => 1, 'data' => $skills];
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => '查询失败'];
        }
    }

    /**
     * 更新 Agent 信息
     */
    public function updateInfo(array $adminInfo, array $params): array
    {
        if (empty($adminInfo['wallet_address'])) {
            return ['code' => 0, 'msg' => '请先绑定钱包地址'];
        }

        $address = $adminInfo['wallet_address'];

        // 检查是否已有身份
        $chainInfo = XULChainService::getSBTIdentity($address);
        if (!$chainInfo) {
            return ['code' => 0, 'msg' => '请先铸造 SBT 身份'];
        }

        // 更新本地记录
        $updateData = [];
        if (!empty($params['name'])) {
            $updateData['sbt_name'] = $params['name'];
        }
        if (!empty($params['description'])) {
            $updateData['description'] = $params['description'];
        }

        if (!empty($updateData)) {
            $updateData['update_time'] = time();
            Db::name('xul_sbt_identity')->where('wallet_address', $address)->update($updateData);
        }

        return ['code' => 1, 'msg' => '更新成功'];
    }

    /**
     * 添加技能
     */
    public function addSkill(array $adminInfo, string $skillName, string $skillDesc = ''): array
    {
        if (empty($adminInfo['wallet_address'])) {
            return ['code' => 0, 'msg' => '请先绑定钱包地址'];
        }

        $address = $adminInfo['wallet_address'];

        // 检查链上身份
        $chainInfo = XULChainService::getSBTIdentity($address);
        if (!$chainInfo) {
            return ['code' => 0, 'msg' => '请先铸造 SBT 身份'];
        }

        // 添加到本地
        Db::name('xul_sbt_skills')->insert([
            'user_id' => $adminInfo['admin_id'],
            'wallet_address' => $address,
            'skill_name' => $skillName,
            'skill_desc' => $skillDesc,
            'certified' => 0,
            'create_time' => time()
        ]);

        return ['code' => 1, 'msg' => '技能添加成功'];
    }

    /**
     * 移除技能
     */
    public function removeSkill(array $adminInfo, int $skillId): array
    {
        Db::name('xul_sbt_skills')
            ->where('id', $skillId)
            ->where('user_id', $adminInfo['admin_id'])
            ->delete();

        return ['code' => 1, 'msg' => '技能移除成功'];
    }

    /**
     * 铸造 SBT（管理员操作）
     */
    public function mintSBT(array $adminInfo, array $params): array
    {
        $toAddress = $params['address'] ?? '';
        $name = $params['name'] ?? '';
        $description = $params['description'] ?? '';
        $skills = $params['skills'] ?? [];

        if (empty($toAddress) || empty($name)) {
            return ['code' => 0, 'msg' => '地址和名称不能为空'];
        }

        // 验证管理员权限
        if (strtolower($adminInfo['wallet_address'] ?? '') !== strtolower(self::ADMIN_ADDRESS)) {
            return ['code' => 0, 'msg' => '没有铸造权限'];
        }

        // 检查是否已有身份
        $exists = XULChainService::getSBTIdentity($toAddress);
        if ($exists) {
            return ['code' => 0, 'msg' => '该地址已有 SBT 身份'];
        }

        // TODO: 调用合约铸造（需要私钥签名）
        // 这里先记录到本地，等部署签名服务后再上链
        
        Db::name('xul_sbt_identity')->insert([
            'user_id' => 0,
            'wallet_address' => $toAddress,
            'sbt_name' => $name,
            'sbt_score' => 500,
            'description' => $description,
            'chain_status' => 0, // 待上链
            'create_time' => time()
        ]);

        return [
            'code' => 1,
            'msg' => 'SBT 铸造请求已记录，等待上链',
            'data' => [
                'address' => $toAddress,
                'name' => $name,
                'status' => 'pending'
            ]
        ];
    }

    /**
     * 从链上同步身份到本地
     */
    public function syncFromChain(string $address, array $adminInfo = []): array
    {
        if (empty($address)) {
            if (empty($adminInfo['wallet_address'])) {
                return ['code' => 0, 'msg' => '地址不能为空'];
            }
            $address = $adminInfo['wallet_address'];
        }

        $chainInfo = XULChainService::getSBTIdentity($address);
        if (!$chainInfo) {
            return ['code' => 0, 'msg' => '链上暂无身份'];
        }

        // 更新本地记录
        $exists = Db::name('xul_sbt_identity')->where('wallet_address', $address)->find();
        
        $data = [
            'sbt_name' => $chainInfo['name'],
            'sbt_score' => $chainInfo['score'],
            'chain_status' => 1,
            'update_time' => time()
        ];

        if ($exists) {
            Db::name('xul_sbt_identity')->where('wallet_address', $address)->update($data);
        } else {
            $data['wallet_address'] = $address;
            $data['create_time'] = time();
            Db::name('xul_sbt_identity')->insert($data);
        }

        return ['code' => 1, 'msg' => '同步成功', 'data' => $chainInfo];
    }

    /**
     * 同步本地身份到链上
     */
    public function syncToChain(array $adminInfo): array
    {
        if (empty($adminInfo['wallet_address'])) {
            return ['code' => 0, 'msg' => '请先绑定钱包地址'];
        }

        $localInfo = Db::name('xul_sbt_identity')
            ->where('wallet_address', $adminInfo['wallet_address'])
            ->find();

        if (!$localInfo) {
            return ['code' => 0, 'msg' => '本地暂无身份记录'];
        }

        // TODO: 调用合约更新（需要私钥签名）
        // 标记为待同步
        Db::name('xul_sbt_identity')
            ->where('wallet_address', $adminInfo['wallet_address'])
            ->update(['chain_status' => 0]);

        return ['code' => 1, 'msg' => '同步请求已记录'];
    }

    /**
     * 解码字符串数组
     */
    private function decodeStringArray(string $data): array
    {
        // 简化处理，实际需要解析 ABI 编码
        if (empty($data) || $data === '0x') {
            return [];
        }

        // 这里需要根据实际的 ABI 编码解析
        // 暂时返回空数组，等前端调用时再处理
        return [];
    }
}
