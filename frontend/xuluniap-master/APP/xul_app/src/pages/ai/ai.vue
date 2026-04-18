<template>
  <view class="ai-page">
    <!-- 对话列表 -->
    <scroll-view 
      class="chat-list" 
      scroll-y 
      :scroll-top="scrollTop"
      @scrolltoupper="loadMoreHistory"
    >
      <!-- 欢迎消息 -->
      <view class="welcome-card" v-if="messages.length === 0">
        <image class="welcome-avatar" src="/static/ai-avatar.png" mode="aspectFit" />
        <view class="welcome-title">AI 助手</view>
        <view class="welcome-desc">基于区块链知识的智能助手，可以帮你分析合约、解读交易、探索 DApp</view>
        <view class="quick-actions">
          <view class="quick-btn" @click="quickAsk('什么是 ERC-4337 账户抽象？')">
            <text>💡 账户抽象是什么？</text>
          </view>
          <view class="quick-btn" @click="quickAsk('帮我分析这个合约的安全性')">
            <text>🔍 合约安全分析</text>
          </view>
          <view class="quick-btn" @click="quickAsk('什么是 Gas 优化？')">
            <text>⛽ Gas 优化建议</text>
          </view>
          <view class="quick-btn" @click="quickAsk('推荐一些热门 DApp')">
            <text>🔥 热门 DApp 推荐</text>
          </view>
        </view>
      </view>

      <!-- 消息列表 -->
      <view class="message-item" v-for="(msg, idx) in messages" :key="idx">
        <view class="message-avatar" :class="msg.role">
          <image v-if="msg.role === 'user'" src="/static/user-avatar.png" mode="aspectFit" />
          <image v-else src="/static/ai-avatar.png" mode="aspectFit" />
        </view>
        <view class="message-content" :class="msg.role">
          <text class="message-text" :selectable="true">{{ msg.content }}</text>
          <!-- 交易/地址识别 -->
          <view class="detected-links" v-if="msg.detectedLinks?.length">
            <view 
              class="link-item" 
              v-for="(link, lidx) in msg.detectedLinks" 
              :key="lidx"
              @click="handleLink(link)"
            >
              <text>{{ link.type }}: {{ shortText(link.value, 12) }}</text>
              <text class="link-action">查看 →</text>
            </view>
          </view>
          <!-- 操作按钮 -->
          <view class="msg-actions" v-if="msg.role === 'assistant'">
            <text class="action-btn" @click="copyText(msg.content)">复制</text>
            <text class="action-btn" @click="regenerate(idx)">重新生成</text>
          </view>
        </view>
      </view>

      <!-- 正在输入 -->
      <view class="message-item typing" v-if="isTyping">
        <view class="message-avatar assistant">
          <image src="/static/ai-avatar.png" mode="aspectFit" />
        </view>
        <view class="message-content assistant">
          <view class="typing-indicator">
            <view class="dot"></view>
            <view class="dot"></view>
            <view class="dot"></view>
          </view>
        </view>
      </view>
    </scroll-view>

    <!-- 输入区域 -->
    <view class="input-area">
      <view class="input-wrap">
        <textarea 
          class="input-field" 
          v-model="inputText" 
          placeholder="输入消息或粘贴交易/地址..." 
          :auto-height="true"
          :maxlength="2000"
          @confirm="sendMessage"
        />
        <view class="input-actions">
          <text class="input-action" @click="pasteFromClipboard">📋</text>
          <text class="input-action" @click="clearChat">🗑️</text>
        </view>
      </view>
      <view class="send-btn" :class="{ active: inputText.trim() }" @click="sendMessage">
        <text>发送</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue'

interface Message {
  role: 'user' | 'assistant'
  content: string
  detectedLinks?: { type: string; value: string }[]
}

const messages = ref<Message[]>([])
const inputText = ref('')
const isTyping = ref(false)
const scrollTop = ref(0)

// API 配置 (TODO: 从配置读取)
const API_URL = 'https://open.bigmodel.cn/api/paas/v4/chat/completions'
const API_KEY = '' // 需要配置

function shortText(text: string, len: number): string {
  if (text.length <= len * 2) return text
  return text.slice(0, len) + '...' + text.slice(-len)
}

function quickAsk(question: string) {
  inputText.value = question
  sendMessage()
}

async function sendMessage() {
  const text = inputText.value.trim()
  if (!text || isTyping.value) return

  // 添加用户消息
  const userMsg: Message = { role: 'user', content: text }
  
  // 检测链接
  const links = detectLinks(text)
  if (links.length) {
    userMsg.detectedLinks = links
  }
  
  messages.value.push(userMsg)
  inputText.value = ''
  
  // 滚动到底部
  scrollToBottom()

  // 模拟 AI 响应 (TODO: 接入真实 API)
  isTyping.value = true
  
  try {
    // 检查是否是区块查询
    if (text.match(/^0x[a-fA-F0-9]{64}$/)) {
      // 交易哈希
      await simulateResponse(`检测到交易哈希。\n\n正在查询交易详情...\n\n这是 XUL Chain 上的一笔交易，您可以在区块浏览器中查看详情。\n\n交易状态：成功 ✅\n区块确认：已确认`)
    } else if (text.match(/^0x[a-fA-F0-9]{40}$/)) {
      // 地址
      await simulateResponse(`检测到钱包地址。\n\n正在查询地址信息...\n\n该地址在 XUL Chain 上有活动记录。\n\n您可以在区块浏览器中查看该地址的完整交易历史和资产信息。`)
    } else {
      // 普通对话
      const response = await callAI(text)
      messages.value.push({ role: 'assistant', content: response })
    }
  } catch (e) {
    messages.value.push({ 
      role: 'assistant', 
      content: '抱歉，处理您的请求时出现了错误。请稍后再试。' 
    })
  }
  
  isTyping.value = false
  scrollToBottom()
}

function detectLinks(text: string): { type: string; value: string }[] {
  const links: { type: string; value: string }[] = []
  
  // 交易哈希 (64 hex)
  const txMatch = text.match(/0x[a-fA-F0-9]{64}/g)
  if (txMatch) {
    txMatch.forEach(tx => links.push({ type: '交易', value: tx }))
  }
  
  // 地址 (40 hex)
  const addrMatch = text.match(/0x[a-fA-F0-9]{40}/g)
  if (addrMatch) {
    addrMatch.forEach(addr => {
      if (!txMatch?.includes(addr)) {
        links.push({ type: '地址', value: addr })
      }
    })
  }
  
  return links
}

function handleLink(link: { type: string; value: string }) {
  if (link.type === '交易') {
    uni.navigateTo({ url: `/pages/history/detail?tx=${link.value}` })
  } else if (link.type === '地址') {
    // 打开区块浏览器
    const url = `https://scan.rswl.ai/address/${link.value}`
    uni.setClipboardData({ data: url, success: () => uni.showToast({ title: '链接已复制' }) })
  }
}

async function simulateResponse(text: string) {
  // 逐字输出效果
  const msg: Message = { role: 'assistant', content: '' }
  messages.value.push(msg)
  
  for (let i = 0; i < text.length; i++) {
    await new Promise(r => setTimeout(r, 20))
    msg.content += text[i]
  }
}

async function callAI(prompt: string): Promise<string> {
  // TODO: 接入真实 AI API
  // 目前返回模拟响应
  
  const responses: Record<string, string> = {
    '什么是 ERC-4337 账户抽象？': `ERC-4337 是以太坊账户抽象标准，它通过 EntryPoint 合约实现了智能合约钱包的统一标准。

主要优势：
• 社交恢复 - 不用担心私钥丢失
• Gas 代付 - Paymaster 可以帮你支付 Gas
• 批量交易 - 一次签名执行多个操作
• 无需 ETH - 可以用其他代币支付 Gas

您的 XUL Wallet 就是一个 ERC-4337 兼容的智能合约钱包！`,
    
    '帮我分析这个合约的安全性': `好的，请提供合约地址，我可以帮您：

1. 检查合约是否已验证
2. 分析主要函数权限
3. 检查是否存在常见漏洞模式
4. 查看合约审计状态

⚠️ 注意：AI 分析仅供参考，重要资产操作请务必进行专业审计。`,
    
    '什么是 Gas 优化？': `Gas 优化是降低智能合约执行成本的技术。

常见优化方法：
• 使用 calldata 代替 memory
• 批量处理减少循环次数
• 使用 unchecked 跳过溢出检查（安全前提下）
• 合理使用 storage vs memory
• 短路评估优化条件判断

在 XUL Chain 上，由于 Gas 费用较低，优化压力相对较小，但良好习惯仍然重要！`,
    
    '推荐一些热门 DApp': `以下是目前热门的 DApp 类别：

💰 DeFi
• Uniswap - 去中心化交易所
• Aave - 借贷协议
• Lido - 质押服务

🖼️ NFT
• OpenSea - NFT 市场
• Blur - 专业 NFT 交易

🎮 GameFi
• Axie Infinity - 边玩边赚
• Stepn - 跑步挖矿

您可以在 DApp 浏览器中探索更多应用！`,
  }
  
  // 查找匹配响应
  for (const key in responses) {
    if (prompt.includes(key) || key.includes(prompt)) {
      return responses[key]
    }
  }
  
  // 默认响应
  return `您好！我是 XUL AI 助手，可以帮您：

• 📖 解释区块链概念
• 🔍 分析交易和地址
• 💡 智能合约安全建议
• 🔗 推荐优质 DApp

有什么我可以帮助您的吗？`
}

async function loadMoreHistory() {
  // TODO: 加载历史对话
}

function pasteFromClipboard() {
  uni.getClipboardData({
    success: (res) => {
      inputText.value = res.data
    }
  })
}

function copyText(text: string) {
  uni.setClipboardData({ data: text, success: () => uni.showToast({ title: '已复制' }) })
}

function clearChat() {
  uni.showModal({
    title: '清除对话',
    content: '确定要清除所有对话记录吗？',
    success: (res) => {
      if (res.confirm) {
        messages.value = []
      }
    }
  })
}

function regenerate(idx: number) {
  // 找到对应的用户消息
  const assistantMsg = messages.value[idx]
  if (assistantMsg.role !== 'assistant') return
  
  // 移除当前回复，重新生成
  messages.value.splice(idx, 1)
  
  // 找到上一个用户消息
  for (let i = idx - 1; i >= 0; i--) {
    if (messages.value[i].role === 'user') {
      const userPrompt = messages.value[i].content
      // 重新发送
      const originalInput = inputText.value
      inputText.value = userPrompt
      sendMessage()
      inputText.value = originalInput
      break
    }
  }
}

function scrollToBottom() {
  nextTick(() => {
    scrollTop.value = 9999999
  })
}
</script>

<style scoped>
.ai-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f5f5f5;
}

.chat-list {
  flex: 1;
  padding: 24rpx;
  padding-bottom: 180rpx;
}

.welcome-card {
  text-align: center;
  padding: 60rpx 40rpx;
  background: white;
  border-radius: 24rpx;
  margin-bottom: 40rpx;
}

.welcome-avatar {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  margin-bottom: 24rpx;
}

.welcome-title {
  font-size: 36rpx;
  font-weight: 600;
  margin-bottom: 16rpx;
}

.welcome-desc {
  font-size: 26rpx;
  color: #666;
  line-height: 1.6;
  margin-bottom: 40rpx;
}

.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20rpx;
}

.quick-btn {
  padding: 20rpx 16rpx;
  background: #f5f8ff;
  border-radius: 16rpx;
  font-size: 24rpx;
  color: #007AFF;
}

.message-item {
  display: flex;
  margin-bottom: 32rpx;
}

.message-avatar {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.message-avatar.user {
  margin-left: 20rpx;
  order: 2;
}

.message-avatar.assistant {
  margin-right: 20rpx;
}

.message-avatar image {
  width: 100%;
  height: 100%;
}

.message-content {
  max-width: 70%;
  padding: 24rpx;
  border-radius: 20rpx;
  font-size: 28rpx;
  line-height: 1.6;
}

.message-content.user {
  background: #007AFF;
  color: white;
  border-bottom-right-radius: 4rpx;
}

.message-content.assistant {
  background: white;
  color: #333;
  border-bottom-left-radius: 4rpx;
}

.detected-links {
  margin-top: 20rpx;
  border-top: 1rpx solid #eee;
  padding-top: 16rpx;
}

.link-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12rpx 0;
  font-size: 24rpx;
  color: #666;
}

.link-action {
  color: #007AFF;
}

.msg-actions {
  display: flex;
  gap: 24rpx;
  margin-top: 16rpx;
  padding-top: 16rpx;
  border-top: 1rpx solid #eee;
}

.action-btn {
  font-size: 24rpx;
  color: #999;
}

/* 正在输入动画 */
.typing-indicator {
  display: flex;
  gap: 8rpx;
  padding: 8rpx 0;
}

.dot {
  width: 12rpx;
  height: 12rpx;
  background: #ccc;
  border-radius: 50%;
  animation: typing 1s infinite;
}

.dot:nth-child(2) { animation-delay: 0.2s; }
.dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-10rpx); }
}

/* 输入区域 */
.input-area {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: flex-end;
  gap: 16rpx;
  padding: 20rpx 24rpx;
  background: white;
  border-top: 1rpx solid #eee;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.input-wrap {
  flex: 1;
  display: flex;
  align-items: flex-end;
  background: #f5f5f5;
  border-radius: 24rpx;
  padding: 12rpx 20rpx;
}

.input-field {
  flex: 1;
  font-size: 28rpx;
  max-height: 160rpx;
  background: transparent;
}

.input-actions {
  display: flex;
  gap: 16rpx;
  margin-left: 16rpx;
}

.input-action {
  font-size: 36rpx;
}

.send-btn {
  padding: 20rpx 32rpx;
  background: #ccc;
  color: white;
  border-radius: 24rpx;
  font-size: 28rpx;
  transition: background 0.2s;
}

.send-btn.active {
  background: #007AFF;
}
</style>
