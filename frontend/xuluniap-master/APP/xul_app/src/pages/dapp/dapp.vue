<template>
  <view class="dapp-page">
    <!-- 地址栏 -->
    <view class="url-bar">
      <view class="url-input-wrap">
        <text class="url-icon">🔒</text>
        <input 
          class="url-input" 
          v-model="inputUrl" 
          placeholder="输入 DApp 地址或搜索" 
          @confirm="navigate"
        />
        <text class="clear-btn" v-if="inputUrl" @click="inputUrl = ''">✕</text>
      </view>
      <view class="url-actions">
        <text class="action-btn" @click="goBack">←</text>
        <text class="action-btn" @click="goForward">→</text>
        <text class="action-btn" @click="refresh">↻</text>
      </view>
    </view>

    <!-- DApp 分类 -->
    <view class="dapp-content" v-if="!showBrowser">
      <!-- 推荐应用 -->
      <view class="section">
        <view class="section-title">🔥 热门 DApp</view>
        <scroll-view scroll-x class="dapp-scroll">
          <view class="dapp-card" v-for="app in hotDApps" :key="app.url" @click="openDApp(app.url)">
            <image class="dapp-icon" :src="app.icon" mode="aspectFit" />
            <text class="dapp-name">{{ app.name }}</text>
            <text class="dapp-tag">{{ app.tag }}</text>
          </view>
        </scroll-view>
      </view>

      <!-- DeFi -->
      <view class="section">
        <view class="section-title">💰 DeFi</view>
        <view class="dapp-grid">
          <view class="dapp-item" v-for="app in defiDApps" :key="app.url" @click="openDApp(app.url)">
            <image class="dapp-icon-lg" :src="app.icon" mode="aspectFit" />
            <text class="dapp-name">{{ app.name }}</text>
          </view>
        </view>
      </view>

      <!-- NFT -->
      <view class="section">
        <view class="section-title">🖼️ NFT</view>
        <view class="dapp-grid">
          <view class="dapp-item" v-for="app in nftDApps" :key="app.url" @click="openDApp(app.url)">
            <image class="dapp-icon-lg" :src="app.icon" mode="aspectFit" />
            <text class="dapp-name">{{ app.name }}</text>
          </view>
        </view>
      </view>

      <!-- 游戏 -->
      <view class="section">
        <view class="section-title">🎮 游戏</view>
        <view class="dapp-grid">
          <view class="dapp-item" v-for="app in gameDApps" :key="app.url" @click="openDApp(app.url)">
            <image class="dapp-icon-lg" :src="app.icon" mode="aspectFit" />
            <text class="dapp-name">{{ app.name }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 浏览器视图 -->
    <web-view 
      v-if="showBrowser" 
      :src="currentUrl" 
      @message="onMessage"
      @error="onError"
    ></web-view>

    <!-- 钱包连接弹窗 -->
    <view class="connect-modal" v-if="showConnectModal">
      <view class="modal-content">
        <view class="modal-title">连接钱包</view>
        <view class="modal-site">{{ connectingSite }}</view>
        <view class="modal-perms">
          <text>该应用请求访问：</text>
          <view class="perm-item">✓ 查看钱包地址</view>
          <view class="perm-item">✓ 请求交易签名</view>
        </view>
        <view class="modal-actions">
          <button class="btn-cancel" @click="rejectConnect">拒绝</button>
          <button class="btn-confirm" @click="approveConnect">连接</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const inputUrl = ref('')
const currentUrl = ref('')
const showBrowser = ref(false)
const showConnectModal = ref(false)
const connectingSite = ref('')
const connectedSites = ref<Set<string>>(new Set())

// 热门 DApp
const hotDApps = ref([
  { name: 'Uniswap', icon: '/static/dapp/uniswap.png', url: 'https://app.uniswap.org', tag: 'DeFi' },
  { name: 'OpenSea', icon: '/static/dapp/opensea.png', url: 'https://opensea.io', tag: 'NFT' },
  { name: 'Aave', icon: '/static/dapp/aave.png', url: 'https://app.aave.com', tag: '借贷' },
])

// DeFi 应用
const defiDApps = ref([
  { name: 'Uniswap', icon: '/static/dapp/uniswap.png', url: 'https://app.uniswap.org' },
  { name: 'SushiSwap', icon: '/static/dapp/sushi.png', url: 'https://sushi.com' },
  { name: 'Aave', icon: '/static/dapp/aave.png', url: 'https://app.aave.com' },
  { name: 'Compound', icon: '/static/dapp/compound.png', url: 'https://compound.finance' },
  { name: 'Curve', icon: '/static/dapp/curve.png', url: 'https://curve.fi' },
  { name: 'Lido', icon: '/static/dapp/lido.png', url: 'https://lido.fi' },
])

// NFT 应用
const nftDApps = ref([
  { name: 'OpenSea', icon: '/static/dapp/opensea.png', url: 'https://opensea.io' },
  { name: 'Blur', icon: '/static/dapp/blur.png', url: 'https://blur.io' },
  { name: 'Magic Eden', icon: '/static/dapp/magiceden.png', url: 'https://magiceden.io' },
  { name: 'LooksRare', icon: '/static/dapp/looksrare.png', url: 'https://looksrare.org' },
])

// 游戏
const gameDApps = ref([
  { name: 'Axie', icon: '/static/dapp/axie.png', url: 'https://app.axieinfinity.com' },
  { name: 'Stepn', icon: '/static/dapp/stepn.png', url: 'https://stepn.com' },
  { name: 'Illuvium', icon: '/static/dapp/illuvium.png', url: 'https://illuvium.io' },
  { name: 'Decentraland', icon: '/static/dapp/decentraland.png', url: 'https://play.decentraland.org' },
])

function navigate() {
  let url = inputUrl.value.trim()
  if (!url) return
  
  // 自动补全协议
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    // 检查是否是域名
    if (url.includes('.') && !url.includes(' ')) {
      url = 'https://' + url
    } else {
      // 作为搜索关键词
      url = `https://www.google.com/search?q=${encodeURIComponent(url)}`
    }
  }
  
  openDApp(url)
}

function openDApp(url: string) {
  currentUrl.value = url
  inputUrl.value = url
  showBrowser.value = true
}

function goBack() {
  // WebView 返回需要在 web-view 组件上实现
  uni.showToast({ title: '返回上一页', icon: 'none' })
}

function goForward() {
  uni.showToast({ title: '前进', icon: 'none' })
}

function refresh() {
  if (currentUrl.value) {
    // 重新加载当前 URL
    const temp = currentUrl.value
    currentUrl.value = ''
    setTimeout(() => {
      currentUrl.value = temp
    }, 100)
  }
}

function onMessage(e: any) {
  // 处理来自 WebView 的消息（钱包连接请求等）
  const data = e.detail?.data?.[0]
  if (data?.type === 'WALLET_CONNECT_REQUEST') {
    connectingSite.value = data.site || currentUrl.value
    showConnectModal.value = true
  }
}

function onError(e: any) {
  uni.showToast({ title: '页面加载失败', icon: 'none' })
}

function approveConnect() {
  connectedSites.value.add(new URL(currentUrl.value).hostname)
  showConnectModal.value = false
  uni.showToast({ title: '已连接', icon: 'success' })
  // TODO: 向 WebView 发送连接成功消息
}

function rejectConnect() {
  showConnectModal.value = false
  uni.showToast({ title: '已拒绝', icon: 'none' })
}
</script>

<style scoped>
.dapp-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.url-bar {
  display: flex;
  align-items: center;
  padding: 20rpx 24rpx;
  background: white;
  border-bottom: 1rpx solid #eee;
  position: sticky;
  top: 0;
  z-index: 100;
}

.url-input-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  background: #f5f5f5;
  border-radius: 40rpx;
  padding: 16rpx 24rpx;
}

.url-icon {
  font-size: 28rpx;
  margin-right: 16rpx;
}

.url-input {
  flex: 1;
  font-size: 28rpx;
  background: transparent;
}

.clear-btn {
  font-size: 32rpx;
  color: #999;
  padding: 0 10rpx;
}

.url-actions {
  display: flex;
  gap: 16rpx;
  margin-left: 20rpx;
}

.action-btn {
  width: 60rpx;
  height: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36rpx;
  color: #666;
}

.dapp-content {
  padding: 24rpx;
}

.section {
  margin-bottom: 40rpx;
}

.section-title {
  font-size: 32rpx;
  font-weight: 600;
  margin-bottom: 20rpx;
  color: #333;
}

.dapp-scroll {
  white-space: nowrap;
  display: flex;
}

.dapp-card {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  width: 180rpx;
  padding: 24rpx;
  background: white;
  border-radius: 20rpx;
  margin-right: 20rpx;
  box-shadow: 0 4rpx 12rpx rgba(0,0,0,0.06);
}

.dapp-icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 20rpx;
  margin-bottom: 16rpx;
}

.dapp-name {
  font-size: 26rpx;
  color: #333;
  margin-bottom: 8rpx;
}

.dapp-tag {
  font-size: 20rpx;
  color: #007AFF;
  background: rgba(0,122,255,0.1);
  padding: 4rpx 12rpx;
  border-radius: 12rpx;
}

.dapp-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20rpx;
}

.dapp-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24rpx 12rpx;
  background: white;
  border-radius: 16rpx;
  box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.04);
}

.dapp-icon-lg {
  width: 72rpx;
  height: 72rpx;
  border-radius: 16rpx;
  margin-bottom: 12rpx;
}

/* 连接弹窗 */
.connect-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: flex-end;
  z-index: 1000;
}

.modal-content {
  width: 100%;
  background: white;
  border-radius: 32rpx 32rpx 0 0;
  padding: 48rpx 40rpx;
}

.modal-title {
  font-size: 36rpx;
  font-weight: 600;
  text-align: center;
  margin-bottom: 20rpx;
}

.modal-site {
  text-align: center;
  color: #007AFF;
  font-size: 28rpx;
  margin-bottom: 32rpx;
}

.modal-perms {
  background: #f9f9f9;
  border-radius: 16rpx;
  padding: 24rpx;
  margin-bottom: 32rpx;
}

.modal-perms text {
  font-size: 26rpx;
  color: #666;
}

.perm-item {
  font-size: 26rpx;
  color: #333;
  margin-top: 16rpx;
}

.modal-actions {
  display: flex;
  gap: 24rpx;
}

.btn-cancel {
  flex: 1;
  background: #f5f5f5;
  color: #666;
  border: none;
  border-radius: 50rpx;
  font-size: 30rpx;
  padding: 24rpx;
}

.btn-confirm {
  flex: 1;
  background: #007AFF;
  color: white;
  border: none;
  border-radius: 50rpx;
  font-size: 30rpx;
  padding: 24rpx;
}
</style>
