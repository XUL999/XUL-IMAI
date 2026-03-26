<?php
/**
 * XUL Agent SBT 控制器
 * 
 * 功能：AI Agent 链上身份管理
 */

namespace app\adminapi\controller\xul;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\xul\XULAgentSBTLogic;

/**
 * XUL Agent SBT 管理
 * Class XULAgentSBT
 * @package app\adminapi\controller\xul
 */
class XULAgentSBT extends BaseAdminController
{
    /**
     * 获取 Agent SBT 信息
     * 
     * @notes 获取当前用户或指定地址的链上 SBT 身份
     * @return \think\response\Json
     */
    public function getInfo()
    {
        $address = input('address', '');
        $result = (new XULAgentSBTLogic())->getInfo($address, $this->adminInfo);
        return $this->data($result);
    }

    /**
     * 获取 Agent 技能列表
     * 
     * @return \think\response\Json
     */
    public function getSkills()
    {
        $address = input('address', '');
        $result = (new XULAgentSBTLogic())->getSkills($address);
        return $this->data($result);
    }

    /**
     * 更新 Agent 信息
     * 
     * @notes 更新 Agent 名称、描述、头像
     * @return \think\response\Json
     */
    public function updateInfo()
    {
        $params = input('post.');
        $result = (new XULAgentSBTLogic())->updateInfo($this->adminInfo, $params);
        return $this->data($result);
    }

    /**
     * 添加技能
     * 
     * @return \think\response\Json
     */
    public function addSkill()
    {
        $skillName = input('skill_name', '');
        $skillDesc = input('skill_desc', '');
        $result = (new XULAgentSBTLogic())->addSkill($this->adminInfo, $skillName, $skillDesc);
        return $this->data($result);
    }

    /**
     * 移除技能
     * 
     * @return \think\response\Json
     */
    public function removeSkill()
    {
        $skillId = input('skill_id', 0);
        $result = (new XULAgentSBTLogic())->removeSkill($this->adminInfo, $skillId);
        return $this->data($result);
    }

    /**
     * 铸造 SBT（管理员）
     * 
     * @notes 为指定地址铸造 SBT 身份
     * @return \think\response\Json
     */
    public function mintSBT()
    {
        $params = input('post.');
        $result = (new XULAgentSBTLogic())->mintSBT($this->adminInfo, $params);
        return $this->data($result);
    }

    /**
     * 同步链上身份到本地
     * 
     * @return \think\response\Json
     */
    public function syncFromChain()
    {
        $address = input('address', '');
        $result = (new XULAgentSBTLogic())->syncFromChain($address, $this->adminInfo);
        return $this->data($result);
    }

    /**
     * 同步本地身份到链上
     * 
     * @return \think\response\Json
     */
    public function syncToChain()
    {
        $result = (new XULAgentSBTLogic())->syncToChain($this->adminInfo);
        return $this->data($result);
    }
}
