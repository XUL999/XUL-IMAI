/**
 * 签名接口 - H5 / App 原生统一抽象
 */

export interface SignerInfo {
  address: string
}

// ─── H5 环境：BrowserProvider (MetaMask / WalletConnect) ─────────────────

export function isH5(): boolean {
  // #ifdef H5
  return true
  // #endif
  return false
}

export function isApp(): boolean {
  // #ifdef APP-PLUS
  return true
  // #endif
  return false
}

/**
 * 连接 H5 浏览器钱包
 * 触发 MetaMask / WalletConnect 等注入式 provider
 */
export async function connectBrowserWallet(): Promise<SignerInfo> {
  // #ifdef H5
  const win = window as any
  let provider: any

  // 1. 优先 WalletConnect v2 (手机浏览器)
  if (win.walletconnect) {
    provider = win.walletconnect
  }
  // 2. MetaMask / 其他注入 provider
  else if (win.ethereum) {
    provider = win.ethereum
  } else {
    throw new Error('未检测到钱包，请安装 MetaMask 或打开 WalletConnect')
  }

  // 请求连接
  const accounts: string[] = await provider.request({
    method: 'eth_requestAccounts',
  })

  if (!accounts || accounts.length === 0) {
    throw new Error('用户拒绝连接')
  }

  return { address: accounts[0] }
}

/**
 * H5 环境发送交易
 */
export async function h5SendTransaction(
  provider: any,
  tx: { to: string; value: string; data: string; gas?: string }
): Promise<string> {
  const from = await provider.request({ method: 'eth_requestAccounts' }).then((a: string[]) => a[0])

  const txHash: string = await provider.request({
    method: 'eth_sendTransaction',
    params: [
      {
        from,
        to: tx.to,
        value: tx.value || '0x0',
        data: tx.data || '0x',
        gas: tx.gas || undefined,
      },
    ],
  })

  return txHash
}

/**
 * H5 环境签名消息
 */
export async function h5SignMessage(provider: any, address: string, message: string): Promise<string> {
  return provider.request({
    method: 'personal_sign',
    params: [message, address],
  })
}

// ─── App 原生签名桥 ────────────────────────────────────────────────────────

/**
 * App 原生签名（通过 uni-app nativeplugins 扩展）
 * 需要配合 App 端原生代码（iOS: SecureEnclave, Android: AndroidKeyStore）
 *
 * uni-app 插件市场有现成方案：
 * - web3-wallet uni插件（推荐，基于 ethers.js + 原生桥）
 * - 钱包联盟插件
 *
 * 这里定义接口，实际由原生层实现
 */

export interface AppSignerBridge {
  /** 连接钱包 */
  connect(): Promise<SignerInfo>
  /** 签名消息（ECDSA） */
  signMessage(address: string, message: string): Promise<string>
  /** 发送交易（已签名） */
  sendTransaction(tx: { to: string; value: bigint; data: string }): Promise<string>
  /** 断开连接 */
  disconnect(): void
}

// App 原生签名桩 - 等待原生实现后替换
export const appSignerBridge: AppSignerBridge = {
  async connect() {
    // TODO: 调用原生插件
    throw new Error('App 原生钱包未配置，请先安装 web3-wallet 插件')
  },
  async signMessage(address, message) {
    throw new Error('App 原生签名未实现')
  },
  async sendTransaction(tx) {
    throw new Error('App 原生交易未实现')
  },
  disconnect() {},
}
