<template>
  <view class="transfer-page">
    <view class="form-card">
      <view class="form-title">发送 XUL</view>

      <view class="form-item">
        <text class="form-label">收款地址</text>
        <input
          class="form-input mono"
          v-model="toAddress"
          placeholder="0x..."
          placeholder-class="placeholder"
        />
        <view class="scan-btn" @click="scanQR">📷 扫码</view>
      </view>

      <view class="form-item">
        <text class="form-label">金额 (XUL)</text>
        <view class="amount-row">
          <input
            class="form-input amount"
            v-model="amount"
            type="digit"
            placeholder="0.0"
            placeholder-class="placeholder"
          />
          <view class="max-btn" @click="setMax">MAX</view>
        </view>
        <text class="balance-hint">可用: {{ balance }} XUL</text>
      </view>

      <view class="form-item">
        <text class="form-label">Gas 限制</text>
        <input
          class="form-input"
          v-model="gasLimit"
          type="number"
          placeholder="100000"
          placeholder-class="placeholder"
        />
      </view>

      <view class="fee-info" v-if="estimatedFee">
        <text>预估 Gas 费: ~{{ estimatedFee }} XUL</text>
      </view>

      <button class="btn-send" :loading="sending" :disabled="!canSend" @click="handleSend">
        {{ sending ? '发送中...' : '发送' }}
      </button>

      <view class="error-tip" v-if="error">{{ error }}</view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { parseXUL, formatXUL } from '../../uni_modules/aa-wallet/sdk'

const toAddress = ref('')
const amount = ref('')
const gasLimit = ref('100000')
const sending = ref(false)
const error = ref('')
const balance = ref('0.0000')
const estimatedFee = ref('')

const canSend = computed(() => {
  return toAddress.value.startsWith('0x') &&
    toAddress.value.length === 42 &&
    parseFloat(amount.value || '0') > 0 &&
    !sending.value
})

function setMax() {
  // 保留一点余额交 Gas
  const max = Math.max(0, parseFloat(balance.value) - 0.001)
  amount.value = max.toFixed(4)
}

function scanQR() {
  // TODO: 调用 uni.scanCode
  uni.scanCode({
    success: (res) => {
      toAddress.value = res.result || ''
    },
  })
}

async function handleSend() {
  error.value = ''
  sending.value = true

  try {
    const value = parseXUL(amount.value)
    // TODO: 调用 SDK sendTransaction
    // const txHash = await sdk.sendTransaction(toAddress.value, value)
    await new Promise(r => setTimeout(r, 1000))
    const txHash = '0x' + 'ab'.repeat(32)

    uni.showModal({
      title: '发送成功',
      content: `交易已提交: ${txHash.slice(0, 10)}...`,
      success: () => {
        uni.navigateBack()
      },
    })
  } catch (e: any) {
    error.value = e.message || '发送失败'
    uni.showToast({ title: '发送失败', icon: 'none' })
  }

  sending.value = false
}
</script>

<style scoped>
.transfer-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30rpx;
}

.form-card {
  background: white;
  border-radius: 24rpx;
  padding: 40rpx;
  box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.08);
}

.form-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 40rpx;
}

.form-item {
  margin-bottom: 40rpx;
}

.form-label {
  display: block;
  font-size: 28rpx;
  color: #666;
  margin-bottom: 16rpx;
}

.form-input {
  border: 1rpx solid #e0e0e0;
  border-radius: 16rpx;
  padding: 24rpx;
  font-size: 30rpx;
  background: #fafafa;
}

.form-input.mono {
  font-family: monospace;
}

.placeholder {
  color: #ccc;
}

.amount-row {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.amount {
  flex: 1;
  font-size: 48rpx;
  font-weight: 600;
}

.max-btn {
  background: #007AFF;
  color: white;
  padding: 16rpx 24rpx;
  border-radius: 16rpx;
  font-size: 26rpx;
}

.balance-hint {
  font-size: 24rpx;
  color: #888;
  margin-top: 10rpx;
}

.scan-btn {
  margin-top: 10rpx;
  color: #007AFF;
  font-size: 26rpx;
}

.fee-info {
  font-size: 26rpx;
  color: #888;
  text-align: center;
  margin-bottom: 30rpx;
}

.btn-send {
  width: 100%;
  background: #007AFF;
  color: white;
  border-radius: 50rpx;
  padding: 28rpx;
  font-size: 34rpx;
  font-weight: 600;
  border: none;
  margin-top: 20rpx;
}

.btn-send[disabled] {
  background: #ccc;
}

.error-tip {
  margin-top: 20rpx;
  color: #ff3b30;
  font-size: 26rpx;
  text-align: center;
}
</style>
