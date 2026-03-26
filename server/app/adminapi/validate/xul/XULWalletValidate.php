<?php
/**
 * XUL 钱包登录验证器
 */

namespace app\adminapi\validate\xul;

use think\Validate;

/**
 * XUL 钱包验证器
 * Class XULWalletValidate
 * @package app\adminapi\validate\xul
 */
class XULWalletValidate extends Validate
{
    protected $rule = [
        'address' => 'require|alphaNum|length:42',
        'message' => 'require',
        'signature' => 'require|length:130',
        'terminal' => 'require|in:pc,h5,ios,android'
    ];

    protected $message = [
        'address.require' => '钱包地址不能为空',
        'address.alphaNum' => '钱包地址格式错误',
        'address.length' => '钱包地址长度错误',
        'message.require' => '签名消息不能为空',
        'signature.require' => '签名不能为空',
        'signature.length' => '签名长度错误',
        'terminal.require' => '终端类型不能为空',
        'terminal.in' => '终端类型错误'
    ];

    protected $scene = [
        'login' => ['address', 'message', 'signature'],
        'verify' => ['address', 'message', 'signature'],
        'bind' => ['address', 'message', 'signature']
    ];
}
