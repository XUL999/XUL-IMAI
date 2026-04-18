<template>
  <view class="settings-page">
    <view class="section">
      <view class="section-title">钱包</view>
      <view class="setting-item" @click="showPrivateKey">
        <text>导出私钥</text>
        <text class="arrow">→</text>
      </view>
      <view class="setting-item" @click="exportKeystore">
        <text>导出 Keystore</text>
        <text class="arrow">→</text>
      </view>
      <view class="setting-item" @click="switchWallet">
        <text>切换钱包</text>
        <text class="arrow">→</text>
      </view>
    </view>

    <view class="section">
      <view class="section-title">网络</view>
      <view class="setting-item" @click="switchNetwork">
        <text>切换网络</text>
        <view class="right">
          <text class="network-tag">{{ currentNetwork }}</text>
          <text class="arrow">→</text>
        </view>
      </view>
    </view>

    <view class="section">
      <view class="section-title">安全</view>
      <view class="setting-item" @click="setPIN">
        <text>设置 PIN 码</text>
        <text class="arrow">→</text>
      </view>
      <view class="setting-item" @click="enableBiometric">
        <view class="left">
          <text>生物识别</text>
          <text class="sub">使用指纹或面容解锁</text>
        </view>
        <switch :checked="biometricEnabled" @change="toggleBiometric" />
      </view>
    </view>

    <view class="section">
      <view class="section-title">关于</view>
      <view class="setting-item">
        <text>版本</text>
        <text class="value">1.0.0</text>
      </view>
      <view class="setting-item" @click="openGithub">
        <text>开源地址</text>
        <text class="arrow">→</text>
      </view>
    </view>

    <view class="danger-zone">
      <button class="btn-danger" @click="handleLock">锁定钱包</button>
      <button class="btn-danger-outline" @click="handleReset">重置钱包</button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const currentNetwork = ref('XUL Chain')
const biometricEnabled = ref(false)

function showPrivateKey() {
  uni.showModal({
    title: '导出私钥',
    content: '私钥一旦泄露，资产将无法找回。是否继续？',
    success: (res) => {
      if (res.confirm) {
        uni.navigateTo({ url: '/pages/export-pk/export-pk' })
      }
    },
  })
}

function exportKeystore() {
  uni.showToast({ title: '功能开发中', icon: 'none' })
}

function switchWallet() {
  uni.navigateTo({ url: '/pages/import/import' })
}

function switchNetwork() {
  uni.showToast({ title: '功能开发中', icon: 'none' })
}

function setPIN() {
  uni.navigateTo({ url: '/pages/pin/pin' })
}

function toggleBiometric(e: any) {
  biometricEnabled.value = e.detail.value
}

function enableBiometric() {}

function openGithub() {
  // #ifdef H5
  window.open('https://github.com/your-repo')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL('https://github.com/your-repo')
  // #endif
}

function handleLock() {
  uni.showModal({
    title: '锁定钱包',
    content: '确定要锁定钱包吗？',
    success: (res) => {
      if (res.confirm) {
        uni.removeStorageSync('wallet_locked')
        uni.reLaunch({ url: '/pages/login/login' })
      }
    },
  })
}

function handleReset() {
  uni.showModal({
    title: '危险操作',
    content: '重置将删除所有本地数据，包括私钥。确定要继续吗？',
    success: (res) => {
      if (res.confirm) {
        uni.clearStorageSync()
        uni.reLaunch({ url: '/pages/login/login' })
      }
    },
  })
}
</script>

<style scoped>
.settings-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30rpx;
}

.section {
  background: white;
  border-radius: 20rpx;
  margin-bottom: 30rpx;
  overflow: hidden;
}

.section-title {
  font-size: 24rpx;
  color: #888;
  padding: 24rpx 30rpx 12rpx;
  text-transform: uppercase;
  letter-spacing: 2rpx;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  font-size: 30rpx;
  color: #333;
  border-bottom: 1rpx solid #f5f5f5;
}

.setting-item:last-child {
  border-bottom: none;
}

.arrow {
  color: #ccc;
}

.right {
  display: flex;
  align-items: center;
  gap: 12rpx;
}

.network-tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 4rpx 16rpx;
  border-radius: 20rpx;
  font-size: 24rpx;
}

.value {
  color: #888;
  font-size: 28rpx;
}

.sub {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-top: 4rpx;
}

.left {
  display: flex;
  flex-direction: column;
}

.danger-zone {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
  margin-top: 40rpx;
}

.btn-danger {
  background: #ff3b30;
  color: white;
  border-radius: 50rpx;
  padding: 28rpx;
  font-size: 32rpx;
  border: none;
}

.btn-danger-outline {
  background: transparent;
  color: #ff3b30;
  border-radius: 50rpx;
  padding: 28rpx;
  font-size: 32rpx;
  border: 2rpx solid #ff3b30;
}
</style>
