<template>
  <view class="login-page">
    <view class="logo-area">
      <view class="logo-text">
        <text class="title">XUL</text>
        <text class="sub">Wallet</text>
      </view>
      <text class="desc">ERC-4337 账户抽象钱包</text>
    </view>

    <view class="tab-bar">
      <view
        :class="['tab', { active: tab === 'import' }]"
        @click="tab = 'import'"
      >导入钱包</view>
      <view
        :class="['tab', { active: tab === 'create' }]"
        @click="tab = 'create'"
      >创建钱包</view>
    </view>

    <!-- 导入钱包 -->
    <view class="form" v-if="tab === 'import'">
      <view class="input-group">
        <text class="label">私钥</text>
        <textarea
          class="pk-input"
          v-model="privateKey"
          placeholder="输入 64 位私钥或 66 位带 0x"
          placeholder-class="placeholder"
          :maxlength="66"
        />
      </view>

      <view class="input-group">
        <text class="label">钱包名称（选填）</text>
        <input
          class="text-input"
          v-model="walletName"
          placeholder="例如：我的钱包"
          placeholder-class="placeholder"
        />
      </view>

      <view class="warning">
        ⚠️ 私钥一旦泄露，资产无法找回。请确认在安全环境下操作。
      </view>

      <button class="btn-primary" :loading="importing" @click="handleImport">
        导入钱包
      </button>
    </view>

    <!-- 创建钱包 -->
    <view class="form" v-if="tab === 'create'">
      <view class="input-group">
        <text class="label">钱包名称（选填）</text>
        <input
          class="text-input"
          v-model="walletName"
          placeholder="例如：我的钱包"
          placeholder-class="placeholder"
        />
      </view>

      <view class="warning">
        ⚠️ 创建新钱包将生成全新私钥，请务必安全备份。
      </view>

      <button class="btn-primary" :loading="creating" @click="handleCreate">
        创建钱包
      </button>
    </view>

    <view class="error" v-if="error">{{ error }}</view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const tab = ref<'import' | 'create'>('import')
const privateKey = ref('')
const walletName = ref('')
const importing = ref(false)
const creating = ref(false)
const error = ref('')

function validatePrivateKey(pk: string): boolean {
  const cleaned = pk.trim()
  return (
    (cleaned.length === 64 || cleaned.length === 66) &&
    /^0x[0-9a-fA-F]+$/.test(cleaned)
  )
}

async function handleImport() {
  error.value = ''
  if (!validatePrivateKey(privateKey.value)) {
    error.value = '私钥格式错误，应为 64 位十六进制字符串'
    return
  }

  importing.value = true
  try {
    const pk = privateKey.value.trim().startsWith('0x')
      ? privateKey.value.trim()
      : '0x' + privateKey.value.trim()

    // 保存到本地存储（实际生产应该加密存储）
    uni.setStorageSync('wallet_private_key', pk)
    if (walletName.value) {
      uni.setStorageSync('wallet_name', walletName.value)
    }

    // TODO: 初始化 AA SDK，建立 AA 账户
    // const sdk = new XULWalletSDK(...)
    // await sdk.init()

    uni.switchTab({ url: '/pages/wallet/wallet' })
  } catch (e: any) {
    error.value = e.message || '导入失败'
  }
  importing.value = false
}

async function handleCreate() {
  error.value = ''
  creating.value = true
  try {
    // 生成随机私钥（实际应该用 crypto.getRandomValues）
    const randomBytes = new Uint8Array(32)
    crypto.getRandomValues(randomBytes)
    const pk = '0x' + Array.from(randomBytes).map(b => b.toString(16).padStart(2, '0')).join('')

    uni.setStorageSync('wallet_private_key', pk)
    if (walletName.value) {
      uni.setStorageSync('wallet_name', walletName.value)
    }

    uni.showModal({
      title: '重要：请立即备份！',
      content: `私钥:\n${pk}\n\n请安全备份后继续。`,
      showCancel: false,
      success: () => {
        uni.switchTab({ url: '/pages/wallet/wallet' })
      },
    })
  } catch (e: any) {
    error.value = e.message || '创建失败'
  }
  creating.value = false
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #0a0a1a 0%, #1a1a3e 100%);
  padding: 100rpx 50rpx 50rpx;
}

.logo-area {
  text-align: center;
  margin-bottom: 80rpx;
}

.logo-text {
  display: flex;
  justify-content: center;
  align-items: baseline;
  gap: 8rpx;
  margin-bottom: 16rpx;
}

.title {
  font-size: 80rpx;
  font-weight: 900;
  color: #007AFF;
  letter-spacing: 4rpx;
}

.sub {
  font-size: 48rpx;
  font-weight: 300;
  color: rgba(255,255,255,0.8);
}

.desc {
  color: rgba(255,255,255,0.4);
  font-size: 28rpx;
}

.tab-bar {
  display: flex;
  background: rgba(255,255,255,0.05);
  border-radius: 50rpx;
  padding: 8rpx;
  margin-bottom: 60rpx;
}

.tab {
  flex: 1;
  text-align: center;
  padding: 20rpx;
  border-radius: 44rpx;
  font-size: 30rpx;
  color: rgba(255,255,255,0.5);
  transition: all 0.3s;
}

.tab.active {
  background: #007AFF;
  color: white;
  font-weight: 600;
}

.form {
  background: white;
  border-radius: 32rpx;
  padding: 50rpx 40rpx;
}

.input-group {
  margin-bottom: 40rpx;
}

.label {
  display: block;
  font-size: 28rpx;
  color: #666;
  margin-bottom: 16rpx;
}

.pk-input {
  width: 100%;
  height: 160rpx;
  border: 1rpx solid #e0e0e0;
  border-radius: 20rpx;
  padding: 24rpx;
  font-size: 28rpx;
  font-family: monospace;
  background: #fafafa;
  box-sizing: border-box;
}

.placeholder {
  color: #ccc;
  font-family: monospace;
}

.text-input {
  width: 100%;
  border: 1rpx solid #e0e0e0;
  border-radius: 20rpx;
  padding: 24rpx;
  font-size: 30rpx;
  background: #fafafa;
  box-sizing: border-box;
}

.warning {
  background: #fff8e1;
  border: 1rpx solid #ffe082;
  border-radius: 16rpx;
  padding: 24rpx;
  font-size: 26rpx;
  color: #f57c00;
  margin-bottom: 40rpx;
  line-height: 1.6;
}

.btn-primary {
  width: 100%;
  background: #007AFF;
  color: white;
  border-radius: 50rpx;
  padding: 28rpx;
  font-size: 34rpx;
  font-weight: 600;
  border: none;
}

.error {
  margin-top: 20rpx;
  color: #ff3b30;
  font-size: 26rpx;
  text-align: center;
}
</style>
