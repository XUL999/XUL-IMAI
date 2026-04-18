<template>
  <view class="wallet-page">
    <!-- 头部 -->
    <view class="header">
      <view class="network-tag">{{ chainName }}</view>
      <view class="balance-label">账户余额</view>
      <view class="balance-value">
        {{ balance }} <text class="symbol">XUL</text>
      </view>
      <view class="deposit-info" v-if="account">
        Gas 存款: {{ deposit }} XUL
      </view>
    </view>

    <!-- 账户信息 -->
    <view class="account-card">
      <view class="card-title">AA 账户</view>
      <view class="info-row">
        <text class="label">地址</text>
        <text class="value mono" @click="copy(account)">{{ shortAddr(account) }}</text>
      </view>
      <view class="info-row">
        <text class="label">Owner</text>
        <text class="value mono" @click="copy(owner)">{{ shortAddr(owner) }}</text>
      </view>
      <view class="info-row">
        <text class="label">Nonce</text>
        <text class="value">{{ nonce }}</text>
      </view>
    </view>

    <!-- 快捷操作 -->
    <view class="action-grid">
      <view class="action-item" @click="navTo('/pages/transfer/transfer')">
        <view class="action-icon">↑</view>
        <text>转账</text>
      </view>
      <view class="action-item" @click="navTo('/pages/history/history')">
        <view class="action-icon">☰</view>
        <text>记录</text>
      </view>
      <view class="action-item" @click="handleDeposit">
        <view class="action-icon">⬇</view>
        <text>存 Gas</text>
      </view>
      <view class="action-item" @click="openExplorer">
        <view class="action-icon">🔍</view>
        <text>浏览器</text>
      </view>
    </view>

    <!-- 快捷功能入口 -->
    <view class="feature-cards">
      <view class="feature-card dapp" @click="switchTab('/pages/dapp/dapp')">
        <view class="feature-icon">🌐</view>
        <view class="feature-info">
          <view class="feature-title">DApp 浏览器</view>
          <view class="feature-desc">探索 Web3 应用</view>
        </view>
        <text class="feature-arrow">→</text>
      </view>
      <view class="feature-card ai" @click="switchTab('/pages/ai/ai')">
        <view class="feature-icon">🤖</view>
        <view class="feature-info">
          <view class="feature-title">AI 助手</view>
          <view class="feature-desc">智能分析交易与合约</view>
        </view>
        <text class="feature-arrow">→</text>
      </view>
    </view>

    <!-- 未创建账户提示 -->
    <view class="create-tip" v-if="!account">
      <text>账户未创建，请先创建账户</text>
      <button class="btn-primary" :loading="loading" @click="createAccount">创建账户</button>
    </view>

    <!-- 加载提示 -->
    <view class="loading-mask" v-if="loading">
      <text>{{ loadingText }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, onShow } from 'vue'
import { onPullDownRefresh } from '@dcloudio/uni-app'
import { formatXUL, shortAddress, openExplorer as openExp } from '../../uni_modules/aa-wallet/sdk'

const chainName = 'XUL Chain'
const balance = ref('0.0000')
const deposit = ref('0.0000')
const nonce = ref('0')
const account = ref('')
const owner = ref('')
const loading = ref(false)
const loadingText = ref('')

function shortAddr(addr: string): string {
  return shortAddress(addr)
}

function copy(text: string) {
  uni.setClipboardData({ data: text, success: () => uni.showToast({ title: '已复制' }) })
}

function navTo(path: string) {
  uni.navigateTo({ url: path })
}

function openExplorer() {
  if (account.value) openExp(`address/${account.value}`)
}

function switchTab(path: string) {
  uni.switchTab({ url: path })
}

async function loadData() {
  // TODO: 从全局状态读取钱包数据
  // balance.value = formatXUL(store.balance)
  // deposit.value = formatXUL(store.deposit)
  // nonce.value = String(store.nonce)
  // account.value = store.account
  // owner.value = store.owner
}

async function createAccount() {
  loading.value = true
  loadingText.value = '创建中...'
  uni.showLoading({ title: '创建中' })
  try {
    // TODO: 调用 SDK createAccount
    await new Promise(r => setTimeout(r, 1000))
    uni.showToast({ title: '创建成功' })
    await loadData()
  } catch (e: any) {
    uni.showModal({ title: '错误', content: e.message })
  }
  loading.value = false
  uni.hideLoading()
}

async function handleDeposit() {
  uni.showModal({
    title: '存款到 EntryPoint',
    editable: true,
    placeholderText: '输入 XUL 数量',
    success: async (res) => {
      if (res.confirm && res.content) {
        uni.showLoading({ title: '处理中' })
        try {
          // TODO: 调用 SDK depositToEntryPoint
          await new Promise(r => setTimeout(r, 1000))
          uni.showToast({ title: '存款成功' })
          await loadData()
        } catch (e: any) {
          uni.showModal({ title: '错误', content: e.message })
        }
        uni.hideLoading()
      }
    },
  })
}

onShow(() => {
  loadData()
})

onPullDownRefresh(() => {
  loadData().finally(() => uni.stopPullDownRefresh())
})
</script>

<style scoped>
.wallet-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 120rpx;
}

.header {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  color: white;
  padding: 60rpx 40rpx 80rpx;
  text-align: center;
}

.network-tag {
  display: inline-block;
  background: rgba(255,255,255,0.15);
  border-radius: 20rpx;
  padding: 8rpx 24rpx;
  font-size: 24rpx;
  margin-bottom: 30rpx;
}

.balance-label {
  font-size: 28rpx;
  color: rgba(255,255,255,0.6);
  margin-bottom: 10rpx;
}

.balance-value {
  font-size: 72rpx;
  font-weight: bold;
}

.symbol {
  font-size: 32rpx;
  color: rgba(255,255,255,0.7);
  margin-left: 8rpx;
}

.deposit-info {
  margin-top: 16rpx;
  font-size: 26rpx;
  color: rgba(255,255,255,0.5);
}

.account-card {
  background: white;
  border-radius: 24rpx;
  margin: -40rpx 30rpx 30rpx;
  padding: 40rpx;
  box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.08);
}

.card-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 30rpx;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.info-row:last-child {
  border-bottom: none;
}

.label {
  color: #888;
  font-size: 28rpx;
}

.value {
  color: #333;
  font-size: 28rpx;
}

.mono {
  font-family: monospace;
  font-size: 24rpx;
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  background: white;
  border-radius: 24rpx;
  margin: 0 30rpx 30rpx;
  padding: 40rpx 20rpx;
  box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.08);
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
  font-size: 26rpx;
  color: #666;
}

.action-icon {
  width: 80rpx;
  height: 80rpx;
  background: #f0f4ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36rpx;
}

.feature-cards {
  margin: 0 30rpx 30rpx;
}

.feature-card {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 20rpx;
  padding: 32rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.08);
}

.feature-card.dapp {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.feature-card.ai {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
}

.feature-icon {
  font-size: 48rpx;
  margin-right: 24rpx;
}

.feature-info {
  flex: 1;
}

.feature-title {
  font-size: 32rpx;
  font-weight: 600;
  margin-bottom: 8rpx;
}

.feature-desc {
  font-size: 24rpx;
  opacity: 0.8;
}

.feature-arrow {
  font-size: 36rpx;
  opacity: 0.6;
}

.create-tip {
  margin: 30rpx;
  padding: 40rpx;
  background: white;
  border-radius: 24rpx;
  text-align: center;
  color: #888;
  box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.08);
}

.btn-primary {
  margin-top: 30rpx;
  background: #007AFF;
  color: white;
  border-radius: 50rpx;
  font-size: 30rpx;
  padding: 20rpx 60rpx;
  border: none;
}

.loading-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 28rpx;
}
</style>
