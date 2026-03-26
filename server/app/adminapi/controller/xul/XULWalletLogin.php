<?php
/**
 * XUL 链钱包登录控制器
 * 
 * 功能：钱包签名验证登录
 * 
 * 流程：
 * 1. 用户提供钱包地址 + 签名消息 + 签名
 * 2. 服务端验证签名来自该钱包地址
 * 3. 验证通过则登录/注册用户
 */

namespace app\adminapi\controller\xul;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\xul\XULWalletLogic;
use app\adminapi\validate\xul\XULWalletValidate;

/**
 * XUL 钱包登录
 * Class XULWalletLogin
 * @package app\adminapi\controller\xul
 */
class XULWalletLogin extends BaseAdminController
{
    public array $notNeedLogin = ['login', 'register', 'verify'];

    /**
     * 钱包登录/注册
     * 
     * @notes 通过钱包签名验证登录
     * @return \think\response\Json
     */
    public function login()
    {
        $params = (new XULWalletValidate())->post()->goCheck('login');
        return $this->data((new XULWalletLogic())->walletLogin($params));
    }

    /**
     * 验证签名
     * 
     * @notes 验证钱包签名是否有效
     * @return \think\response\Json
     */
    public function verify()
    {
        $params = (new XULWalletValidate())->post()->goCheck('verify');
        $result = (new XULWalletLogic())->verifySignature(
            $params['address'],
            $params['message'],
            $params['signature']
        );
        return $this->data(['valid' => $result]);
    }

    /**
     * 获取登录挑战消息
     * 
     * @notes 生成一次性挑战消息用于签名，防止重放攻击
     * @return \think\response\Json
     */
    public function challenge()
    {
        $address = input('address', '');
        if (empty($address)) {
            return $this->fail('钱包地址不能为空');
        }
        
        $result = (new XULWalletLogic())->generateChallenge($address);
        return $this->data($result);
    }

    /**
     * 绑定钱包地址到现有账号
     * 
     * @notes 用户已登录状态下绑定钱包
     * @return \think\response\Json
     */
    public function bindWallet()
    {
        $params = (new XULWalletValidate())->post()->goCheck('bind');
        $result = (new XULWalletLogic())->bindWallet($this->adminInfo, $params);
        return $this->data($result);
    }
}
