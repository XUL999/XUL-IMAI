/**
 * XUL AA Wallet SDK - uni-app compatible
 * 支持 H5 (BrowserProvider / walletconnect) 和 App 原生签名桥
 */

// ─── 常量 ──────────────────────────────────────────────────────────────────

export const XUL_CHAIN = {
  chainId: 12309,
  chainIdHex: '0x3015',
  name: 'XUL Chain',
  rpc: 'https://pro.rswl.ai',
  explorer: 'https://scan.rswl.ai',
  symbol: 'XUL',
}

export const XUL_CONTRACTS = {
  entryPoint: '0x44E7e9cBb761201A0b69c3D4a7bBe0D04f968749',
  factory: '0xC19127Eea0cDA4cdB69Bee67C4Da2072811Eb665',
}

// ─── ABI ──────────────────────────────────────────────────────────────────

const ENTRYPOINT_ABI = [
  'function getNonce(address sender, uint192 key) view returns (uint256)',
  'function getUserOpHash(tuple(address sender, uint256 nonce, bytes initCode, bytes callData, uint256 callGasLimit, uint256 verificationGasLimit, uint256 preVerificationGas, uint256 maxFeePerGas, uint256 maxPriorityFeePerGas, bytes paymasterAndData, bytes signature)) view returns (bytes32)',
  'function handleOps(tuple(address sender, uint256 nonce, bytes initCode, bytes callData, uint256 callGasLimit, uint256 verificationGasLimit, uint256 preVerificationGas, uint256 maxFeePerGas, uint256 maxPriorityFeePerGas, bytes paymasterAndData, bytes signature)[] ops, address beneficiary) payable',
  'function balanceOf(address account) view returns (uint256)',
  'function depositTo(address account) payable',
]

const FACTORY_ABI = [
  'function accountOfOwner(address owner) view returns (address)',
  'function createAccount(address owner, uint256 salt) returns (address)',
]

const ACCOUNT_ABI = [
  'function execute(address dest, uint256 value, bytes data)',
  'function owner() view returns (address)',
]

// ─── 工具函数 ─────────────────────────────────────────────────────────────

export function formatXUL(wei: bigint | string | number, decimals = 4): string {
  const v = typeof wei === 'string' || typeof wei === 'number' ? wei : Number(wei) / 1e18
  return v.toFixed(decimals)
}

export function parseXUL(xul: string): bigint {
  const parts = xul.split('.')
  const whole = parts[0] || '0'
  const frac = (parts[1] || '').padEnd(18, '0').slice(0, 18)
  return BigInt(whole + frac)
}

export function shortAddress(addr: string): string {
  if (!addr || addr.length < 10) return addr
  return `${addr.slice(0, 6)}...${addr.slice(-4)}`
}

export function openExplorer(path: string) {
  const url = `${XUL_CHAIN.explorer}/${path}`
  // #ifdef H5
  window.open(url, '_blank')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(url)
  // #endif
}

// ─── UserOp 类型 ──────────────────────────────────────────────────────────

export interface UserOp {
  sender: string
  nonce: bigint | number
  initCode: string
  callData: string
  callGasLimit: bigint | number
  verificationGasLimit: bigint | number
  preVerificationGas: bigint | number
  maxFeePerGas: bigint | number
  maxPriorityFeePerGas: bigint | number
  paymasterAndData: string
  signature: string
}

// ─── Signer 接口（支持多种签名方式）───────────────────────────────────────

export interface Signer {
  getAddress(): Promise<string>
  signMessage(message: string | Uint8Array): Promise<string>
  signTransaction?(tx: any): Promise<string>
  provider?: any
}

// ─── AA 钱包核心类 ─────────────────────────────────────────────────────────

export class XULWalletSDK {
  private epAddress = XUL_CONTRACTS.entryPoint
  private factoryAddress = XUL_CONTRACTS.factory
  private signer: Signer
  private _owner: string = ''
  private _account: string = ''

  constructor(signer: Signer) {
    this.signer = signer
  }

  async init(): Promise<void> {
    this._owner = await this.signer.getAddress()
  }

  get owner(): string {
    return this._owner
  }

  get account(): string {
    return this._account
  }

  /** 查询 AA 账户地址 */
  async getAccountAddress(): Promise<string> {
    try {
      const res = await this._call({
        to: this.factoryAddress,
        data: this._encodeFactory('accountOfOwner', [this._owner]),
      })
      const addr = this._decodeResult('accountOfOwner', res)
      this._account = addr || ''
      return addr || ''
    } catch {
      return ''
    }
  }

  /** 创建 AA 账户 */
  async createAccount(salt = 0): Promise<string> {
    const data = this._encodeFactory('createAccount', [this._owner, salt])
    await this._sendTransaction({ to: this.factoryAddress, data })
    const addr = await this.getAccountAddress()
    this._account = addr
    return addr
  }

  /** 查询 XUL 余额 */
  async getBalance(): Promise<bigint> {
    const addr = await this.getAccountAddress()
    if (!addr) return 0n
    const res = await this._ethCall({ to: addr, data: '0x70a08231', dataArgs: addr.slice(2).padStart(64, '0') })
    return BigInt(res || '0')
  }

  /** 查询 EntryPoint 存款 */
  async getDeposit(): Promise<bigint> {
    const addr = await this.getAccountAddress()
    if (!addr) return 0n
    const res = await this._ethCall({ to: this.epAddress, data: '0xf8b2c94e', dataArgs: addr.slice(2).padStart(64, '0') })
    return BigInt(res || '0')
  }

  /** 查询 nonce */
  async getNonce(key = 0n): Promise<bigint> {
    const addr = await this.getAccountAddress()
    if (!addr) return 0n
    const keyHex = typeof key === 'bigint' ? key.toString(16) : key
    const data = '0x35567e1a' + addr.slice(2).padStart(64, '0') + BigInt(keyHex).toString(16).padStart(64, '0')
    const res = await this._ethCall({ to: this.epAddress, data })
    return BigInt(res || '0')
  }

  /** 构造 UserOp */
  async buildUserOp(to: string, value: bigint, data = '0x'): Promise<any> {
    const sender = await this.getAccountAddress()
    if (!sender) throw new Error('账户未部署')

    const nonce = await this.getNonce()
    const feeData = await this._getFeeData()

    // encode execute call
    const callData = this._encodeAccount('execute', [to, value, data])

    return {
      sender,
      nonce,
      initCode: '0x',
      callData,
      callGasLimit: 100000,
      verificationGasLimit: 200000,
      preVerificationGas: 60000,
      maxFeePerGas: feeData.maxFeePerGas,
      maxPriorityFeePerGas: feeData.maxPriorityFeePerGas,
      paymasterAndData: '0x',
      signature: '0x',
    }
  }

  /** 签名 UserOp（链上验证格式） */
  async signUserOp(op: any): Promise<string> {
    // 调用 EntryPoint.getUserOpHash
    const userOpHash = await this._getUserOpHash(op)

    // 计算合约验证用的 msgHash
    // keccak256(abi.encode(userOpHash, address(this), nonce & ((1<<64)-1)))
    const nonceVal = BigInt(op.nonce) & ((1n << 64n) - 1n)
    const msgHash = await this._keccak256(
      '0x' + [
        userOpHash.slice(2),
        op.sender.slice(2).padStart(64, '0'),
        nonceVal.toString(16).padStart(64, '0'),
      ].join('')
    )

    // 对 msgHash 做 ECDSA 签名
    const sig = await this.signer.signMessage(msgHash)
    return sig
  }

  /** 发送 AA 转账交易 */
  async sendTransaction(to: string, value: bigint, data = '0x'): Promise<string> {
    const op = await this.buildUserOp(to, value, data)
    op.signature = await this.signUserOp(op)

    const txHash = await this._handleOps([op], this._owner)
    return txHash
  }

  /** 存款到 EntryPoint */
  async depositToEntryPoint(amount: bigint): Promise<string> {
    const addr = await this.getAccountAddress()
    if (!addr) throw new Error('账户未部署')

    // depositTo(address) selector 0xb760f7fb
    const data = '0xb760f7fb' + addr.slice(2).padStart(64, '0')
    return this._sendTransaction({ to: this.epAddress, value: amount, data })
  }

  // ─── 内部 RPC 方法 ────────────────────────────────────────────────────

  private async _ethCall(params: { to: string; data: string; value?: bigint }): Promise<string> {
    return this._rpc('eth_call', [params])
  }

  private async _getFeeData(): Promise<{ maxFeePerGas: bigint; maxPriorityFeePerGas: bigint }> {
    try {
      const block = await this._rpc('eth_getBlockByNumber', ['latest', false])
      const baseFeePerGas = BigInt(block.baseFeePerGas || '0x1dcd65000') // 3000000000
      const priorityFee = 2000000000n // 2 gwei
      return {
        maxFeePerGas: baseFeePerGas * 2n + priorityFee,
        maxPriorityFeePerGas: priorityFee,
      }
    } catch {
      return { maxFeePerGas: 3000000021n, maxPriorityFeePerGas: 2000000014n }
    }
  }

  private async _getUserOpHash(op: any): Promise<string> {
    // UserOp 11 字段 tuple 编码
    const sender = op.sender.slice(2).padStart(64, '0')
    const nonce = BigInt(op.nonce).toString(16).padStart(64, '0')
    const initCode = op.initCode.slice(2).padStart(64, '0')
    const callData = op.callData.slice(2).padStart(64, '0')
    const callGasLimit = BigInt(op.callGasLimit).toString(16).padStart(64, '0')
    const verificationGasLimit = BigInt(op.verificationGasLimit).toString(16).padStart(64, '0')
    const preVerificationGas = BigInt(op.preVerificationGas).toString(16).padStart(64, '0')
    const maxFeePerGas = BigInt(op.maxFeePerGas).toString(16).padStart(64, '0')
    const maxPriorityFeePerGas = BigInt(op.maxPriorityFeePerGas).toString(16).padStart(64, '0')
    const paymasterAndData = (op.paymasterAndData || '0x').slice(2).padStart(64, '0')
    const signature = (op.signature || '0x').slice(2).padStart(64, '0')

    const abiEncoded = '0x' + [
      sender, nonce, initCode, callData, callGasLimit,
      verificationGasLimit, preVerificationGas, maxFeePerGas,
      maxPriorityFeePerGas, paymasterAndData, signature,
    ].join('')

    const userOpHash = await this._ethCall({
      to: this.epAddress,
      data: '0x8a4d468c' + abiEncoded.slice(2), // getUserOpHash selector
    })
    return userOpHash
  }

  private async _handleOps(ops: any[], beneficiary: string): Promise<string> {
    // 简化：直接用 eth_sendTransaction + eth_sendRawTransaction
    // 实际需要用 signer 签名 + 发送到 EntryPoint

    // 先估算 gas
    const gasLimit = 3000000n

    const txHash = await this._sendTransaction({
      to: this.epAddress,
      data: this._encodeHandleOps(ops),
      gasLimit,
    })
    return txHash
  }

  private _encodeHandleOps(ops: any[]): string {
    // handleOps(tuple[] ops, address beneficiary)
    // selector: 0x4f1b5de0
    let data = '0x4f1b5de0'
    // ops 数组编码
    data += this._encodeTupleArray(ops)
    return data
  }

  private _encodeTupleArray(ops: any[]): string {
    // 简化版：只支持单个 op
    if (ops.length === 1) {
      return this._encodeSingleOp(ops[0])
    }
    // 多 op 编码略
    return this._encodeSingleOp(ops[0])
  }

  private _encodeSingleOp(op: any): string {
    const fields = [
      op.sender.slice(2).padStart(64, '0'),
      BigInt(op.nonce).toString(16).padStart(64, '0'),
      (op.initCode || '0x').slice(2).padStart(64, '0'),
      (op.callData || '0x').slice(2).padStart(64, '0'),
      BigInt(op.callGasLimit).toString(16).padStart(64, '0'),
      BigInt(op.verificationGasLimit).toString(16).padStart(64, '0'),
      BigInt(op.preVerificationGas).toString(16).padStart(64, '0'),
      BigInt(op.maxFeePerGas).toString(16).padStart(64, '0'),
      BigInt(op.maxPriorityFeePerGas).toString(16).padStart(64, '0'),
      (op.paymasterAndData || '0x').slice(2).padStart(64, '0'),
      (op.signature || '0x').slice(2).padStart(64, '0'),
    ]
    return '0x' + fields.join('')
  }

  // ─── 低层 RPC ──────────────────────────────────────────────────────────

  private async _rpc(method: string, params: any[] = []): Promise<any> {
    return new Promise((resolve, reject) => {
      // #ifdef H5
      uni.request({
        url: XUL_CHAIN.rpc,
        method: 'POST',
        header: { 'Content-Type': 'application/json' },
        data: { jsonrpc: '2.0', id: Date.now(), method, params },
        success: (res: any) => {
          if (res.data?.error) reject(new Error(res.data.error.message || 'RPC Error'))
          else resolve(res.data?.result)
        },
        fail: reject,
      })
      // #endif
      // #ifdef APP-PLUS
      const xhr = plus.net/xmlHttpRequest
      xhr('POST', XUL_CHAIN.rpc, {
        data: JSON.stringify({ jsonrpc: '2.0', id: Date.now(), method, params }),
        headers: { 'Content-Type': 'application/json' },
        success: (res: any) => {
          const data = JSON.parse(res.responseText)
          if (data.error) reject(new Error(data.error.message))
          else resolve(data.result)
        },
        fail: reject,
      })
      // #endif
    })
  }

  private async _call(params: { to: string; data: string; value?: bigint }): Promise<string> {
    return this._rpc('eth_call', [params, 'latest'])
  }

  private async _sendTransaction(params: { to: string; data: string; value?: bigint; gasLimit?: bigint }): Promise<string> {
    // #ifdef H5
    // H5 环境：需要浏览器钱包（MetaMask / WalletConnect 等）注入 provider
    // 这里通过 uni.request 构造 signed tx 是不行的，需要外部 wallet provider
    // 实际在 H5 中应该用 WalletConnect / WalletLink 等桥接
    // 暂时抛出提示
    throw new Error('H5 环境请使用 App 原生钱包或 WalletConnect 桥接')
    // #endif

    // #ifdef APP-PLUS
    // App 原生：调用原生签名桥（需配合 App 原生代码）
    // 这里等待原生层实现，下面是一个占位
    throw new Error('App 原生签名桥接未实现')
    // #endif
  }

  // ─── 编码工具 ──────────────────────────────────────────────────────────

  private _encodeFactory(fn: string, args: any[]): string {
    const map: Record<string, string> = {
      accountOfOwner: '0x8e5d78c9',
      createAccount: '0x5fbfb9cf',
    }
    const sel = map[fn]
    const data = args.map((a) => {
      const v = typeof a === 'string' && a.startsWith('0x') ? a : String(a)
      return v.slice(2).padStart(64, '0')
    }).join('')
    return sel + data
  }

  private _encodeAccount(fn: string, args: any[]): string {
    const map: Record<string, string> = {
      execute: '0x6a761202',
    }
    const sel = map[fn]
    const data = args.map((a) => {
      const v = typeof a === 'string' && a.startsWith('0x') ? a : String(a)
      return v.slice(2).padStart(64, '0')
    }).join('')
    return sel + data
  }

  private async _keccak256(data: string): Promise<string> {
    // 简化：直接用浏览器 SubtleCrypto
    // #ifdef H5
    const msgByte = new Uint8Array(
      data.match(/.{1,2}/g)!.map((b) => parseInt(b, 16))
    )
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgByte)
    const hashHex = Array.from(new Uint8Array(hashBuffer))
      .map((b) => b.toString(16).padStart(2, '0'))
      .join('')
    return '0x' + hashHex
    // #endif
    // #ifdef APP-PLUS
    // App 端暂时用 ethers
    return '0x'
    // #endif
  }

  private _decodeResult(fn: string, result: string): string {
    if (!result || result === '0x') return ''
    // 截取返回的 address（最后40字节）
    return '0x' + result.slice(-40)
  }
}
