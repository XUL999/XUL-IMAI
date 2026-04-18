<template>
  <view class="history-page">
    <view class="tab-bar">
      <view
        v-for="tab in tabs"
        :key="tab.key"
        :class="['tab', { active: activeTab === tab.key }]"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </view>
    </view>

    <view class="list" v-if="txList.length > 0">
      <view
        v-for="tx in txList"
        :key="tx.hash"
        class="tx-item"
        @click="openTx(tx.hash)"
      >
        <view class="tx-icon" :class="tx.type">
          {{ tx.type === 'in' ? '↓' : '↑' }}
        </view>
        <view class="tx-info">
          <view class="tx-title">
            {{ tx.type === 'in' ? '收款' : '发送' }}
          </view>
          <view class="tx-addr mono">{{ shortAddr(tx.to || tx.from) }}</view>
        </view>
        <view class="tx-amount" :class="tx.type">
          {{ tx.type === 'in' ? '+' : '-' }}{{ tx.amount }} XUL
        </view>
      </view>
    </view>

    <view class="empty" v-else>
      <text>暂无交易记录</text>
    </view>

    <view class="loading" v-if="loading">
      <text>加载中...</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, onShow } from 'vue'
import { onPullDownRefresh } from '@dcloudio/uni-app'
import { shortAddress } from '../../uni_modules/aa-wallet/sdk'

interface Tx {
  hash: string
  type: 'in' | 'out'
  amount: string
  to: string
  from: string
}

const tabs = [
  { key: 'all', label: '全部' },
  { key: 'in', label: '收款' },
  { key: 'out', label: '发送' },
]
const activeTab = ref('all')
const txList = ref<Tx[]>([])
const loading = ref(false)

function shortAddr(addr: string): string {
  return shortAddress(addr || '')
}

async function loadHistory() {
  loading.value = true
  try {
    // TODO: 从区块浏览器 API 获取交易历史
    // 示例：
    // const res = await fetch(`${XUL_CHAIN.explorer}/api?module=account&action=txlist&address=${account}`)
    // txList.value = res.data
    txList.value = []
  } finally {
    loading.value = false
  }
}

function openTx(hash: string) {
  // #ifdef H5
  window.open(`${XUL_CHAIN.explorer}/tx/${hash}`, '_blank')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(`${XUL_CHAIN.explorer}/tx/${hash}`)
  // #endif
}

onShow(() => {
  loadHistory()
})

onPullDownRefresh(() => {
  loadHistory().finally(() => uni.stopPullDownRefresh())
})
</script>

<style scoped>
.history-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 120rpx;
}

.tab-bar {
  display: flex;
  background: white;
  padding: 20rpx 40rpx;
  gap: 40rpx;
  border-bottom: 1rpx solid #eee;
}

.tab {
  font-size: 28rpx;
  color: #888;
  padding-bottom: 8rpx;
}

.tab.active {
  color: #007AFF;
  font-weight: 600;
  border-bottom: 4rpx solid #007AFF;
}

.list {
  padding: 20rpx 30rpx;
}

.tx-item {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 2rpx 10rpx rgba(0,0,0,0.05);
}

.tx-icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36rpx;
  margin-right: 24rpx;
}

.tx-icon.in {
  background: #e8f5e9;
  color: #4caf50;
}

.tx-icon.out {
  background: #fff3e0;
  color: #ff9800;
}

.tx-info {
  flex: 1;
}

.tx-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 8rpx;
}

.tx-addr {
  font-size: 24rpx;
  color: #999;
}

.tx-amount {
  font-size: 32rpx;
  font-weight: 600;
}

.tx-amount.in {
  color: #4caf50;
}

.tx-amount.out {
  color: #ff9800;
}

.empty {
  text-align: center;
  padding: 100rpx;
  color: #999;
  font-size: 28rpx;
}

.loading {
  text-align: center;
  padding: 40rpx;
  color: #888;
  font-size: 26rpx;
}
</style>
