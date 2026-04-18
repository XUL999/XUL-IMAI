# XUL Wallet App - AA 钱包 + DApp 浏览器 + AI 助手

## 项目路径
```
C:\Users\31230\XUL-DAPP-SOURCE\frontend\xuluniap-master\APP\xul_app
```

---

## 功能模块

### 1. 💰 AA 钱包
- ERC-4337 账户抽象钱包
- 智能合约账户创建
- Gas 存款管理
- 交易记录查询
- 余额显示

### 2. 🌐 DApp 浏览器
- WebView 加载任意 DApp
- 钱包连接授权弹窗
- 热门 DApp 推荐
- 分类浏览（DeFi/NFT/GameFi）
- 收藏夹管理

### 3. 🤖 AI 助手
- 智能问答
- 合约安全分析
- 交易/地址识别
- 区块链知识科普
- 快捷操作建议

---

## 快速开始

### 1. 安装 HBuilderX
下载：https://www.dcloud.io/hbuilderx.html

### 2. 导入项目
HBuilderX → 文件 → 导入 → 从本地目录导入 → 选择 `xul_app`

### 3. 运行测试
- H5：运行 → 运行到浏览器 → Chrome
- App：运行 → 运行到手机或模拟器

---

## 目录结构

```
src/
├── App.vue                 # 根组件
├── main.ts                 # 入口
├── manifest.json           # App 配置
├── pages.json              # 路由配置
│
├── pages/
│   ├── wallet/wallet.vue   # 💰 钱包首页
│   ├── dapp/dapp.vue       # 🌐 DApp 浏览器
│   ├── ai/ai.vue           # 🤖 AI 助手
│   ├── transfer/transfer.vue # 转账
│   ├── history/history.vue # 交易记录
│   └── settings/settings.vue # 设置
│
├── uni_modules/
│   └── aa-wallet/          # AA SDK
│       └── sdk/
│
├── static/
│   ├── tabbar/             # TabBar 图标
│   ├── dapp/               # DApp 图标
│   ├── ai-avatar.png       # AI 头像
│   └── user-avatar.png     # 用户头像
│
└── signing/
    └── xul-wallet.p12      # Android 签名
```

---

## TabBar 配置

| Tab | 页面 | 功能 |
|-----|------|------|
| 钱包 | pages/wallet/wallet | AA 账户管理 |
| DApp | pages/dapp/dapp | Web3 应用浏览 |
| AI | pages/ai/ai | 智能助手对话 |
| 设置 | pages/settings/settings | 应用设置 |

---

## 签名配置

### Android
- 文件：`signing\xul-wallet.p12`
- 密码：`XULWallet2026Pass`
- Alias：`xul-wallet`

### 打包步骤
1. HBuilderX → 发行 → 原生App-云打包
2. 选择 Android
3. 上传 keystore 配置
4. 打包下载 APK

---

## API 配置（待完成）

### AI 接口
需要在 `pages/ai/ai.vue` 中配置 AI API：
- 智谱清言（推荐）：https://open.bigmodel.cn
- 或其他 AI API

### 区块链 RPC
- XUL Chain：`https://pro.rswl.ai`
- Chain ID：12309

---

## 待完成

- [ ] AI API 对接
- [ ] 钱包全局状态管理
- [ ] WebView 钱包注入脚本
- [ ] iOS 签名配置
- [ ] DApp 收藏功能
- [ ] 交易详情页

---

## 相关链接

- 官网：https://xul.rswl.ai
- 区块浏览器：https://scan.rswl.ai
- Rayshine：https://www.rayshine.me
