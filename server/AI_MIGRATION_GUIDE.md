# AI 服务迁移指南

## 概述

用 `AIService` 替换 `ToolsService`，实现 AI 能力直连各平台，绕过 imai.club 中间商。

---

## 第一步：配置 API Key

### 方式 A：在 .env 中配置（推荐）

```env
AI_DEEPSEEK_KEY=sk-xxxxxxxxxxxxxxxx
AI_ALIYUN_KEY=sk-xxxxxxxxxxxxxxxx
AI_OPENROUTER_KEY=sk-xxxxxxxxxxxxxxxx
AI_GEMINI_KEY=xxxxxxxxxxxxxxxx
```

### 方式 B：直接修改 `config/model.php`

```php
return [
    'deepseek' => 'sk-你的DeepSeek Key',
    'volc'     => 'sk-你的阿里云百炼Key',
    'openai'   => 'sk-你的OpenRouter Key',
    'gemini'   => '你的Gemini Key',
];
```

---

## 第二步：修改 ChatLogic（核心修改）

文件：`app/api/logic/ChatLogic.php`

### 改动 1：文件顶部引入

在 `use GuzzleHttp\Client;` 附近添加：

```php
use app\common\service\ai\AIService;
```

### 改动 2：`requestChatUrl` 方法（约第320行）

**找到这段代码：**
```php
$requestService = \app\common\service\ToolsService::Chat();

$request['user_id']     = $userId;
$request['chat_type']   = $tokenCode;
$request['now']         = time();

if ($scene == self::COMMON_CHAT) {
    $requestService->message($request);
} else if ($scene == self::OPENAI_CHAT) {
    $requestService->openaiMessage($request);
} else if ($scene == self::GEMINI_CHAT) {
    $requestService->geminiMessage($request);
} else {
    $requestService->sceneMessage($request);
}
```

**替换为：**
```php
$aiService = new AIService();

$request['user_id']     = $userId;
$request['chat_type']   = $tokenCode;
$request['now']         = time();

if ($scene == self::COMMON_CHAT) {
    $aiService->message($request);
} else if ($scene == self::OPENAI_CHAT) {
    $aiService->openaiMessage($request);
} else if ($scene == self::GEMINI_CHAT) {
    $aiService->geminiMessage($request);
} else {
    // 场景聊天走通用接口
    $aiService->message($request);
}
```

### 改动 3：`promptChat` 方法（约第280行）

**找到这段代码：**
```php
$response = \app\common\service\ToolsService::Chat()->message($request);
```

**替换为：**
```php
$response = (new AIService())->message($request);
```

---

## 第三步：配置算力单价

文件：`database/sql/xul_ai_token_config.sql`（需创建）

```sql
-- 算力计费配置：1 算力 = X tokens
INSERT INTO `x_config` (`group`, `key`, `value`, `name`) VALUES
('model', 'common_chat', '100',   '通用聊天 算力单价（1算力=100tokens）'),
('model', 'scene_chat',  '80',   '场景聊天 算力单价'),
('model', 'openai_chat', '20',   'OpenAI  算力单价（GPT贵所以单价低）'),
('model', 'gemini_chat',  '50',   'Gemini  算力单价');
```

> **算力逻辑：** 用户每次对话消耗 tokens，后台按 `tokens ÷ 单价 = 消耗算力` 扣费
> 例如：DeepSeek 回答用了 500 tokens，算力单价 100，则消耗 5 算力

---

## 模型支持列表

| 模型 | 平台 | 用途 | 价格参考 |
|------|------|------|---------|
| `deepseek-chat` | DeepSeek | 主力对话 | ¥1/百万token |
| `deepseek-reasoner` | DeepSeek | 推理（DeepSeek R1） | ¥16/百万token |
| `qwen-plus` | 阿里百炼 | 开源对话 | ¥2/百万token |
| `qwen-max` | 阿里百炼 | 高质量对话 | ¥120/百万token |
| `qwq-32b` | 阿里百炼 | 开源推理 | ¥10/百万token |
| `gpt-4o` | OpenRouter | GPT-4 | $3/百万token |
| `gemini-2.0-flash` | Google | 快速对话 | 免费额度 |

---

## 推荐起步配置

1. 先注册 **DeepSeek**（platform.deepseek.com），充值 ¥50 测试
2. 配置到 `.env`
3. 只改 `COMMON_CHAT` 一路，OpenAI/Gemini 暂不动
4. 观察日志确认工作后，再逐步替换其他场景

---

## 费用对比示例

| 场景 | 走 imai.club | 直连 DeepSeek | 节省 |
|------|-------------|--------------|------|
| 1000次对话，每次500 tokens | ~¥50/月 | ~¥2.5/月 | **~95%** |

---

## 故障排查

**1. "CURL 错误"**
→ 检查服务器能否访问外网（443端口）
→ 测试：`curl -I https://api.deepseek.com`

**2. "AI API 返回错误:Incorrect API key provided"**
→ API Key 配置错误或未填

**3. 流式输出不工作**
→ 需要 PHP 有 `ob_flush()` 和 `flush()` 权限
→ 检查 php.ini 的 `output_buffering = Off`

**4. 扣费不准**
→ 确认 `config/model.php` 的单价配置生效
→ 数据库 `x_config` 表是否有覆盖配置
