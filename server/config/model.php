<?php

/**
 * AI 服务配置
 *
 * 用法：在 ThinkPHP 配置文件中引用，或在 .env 中设置
 *
 * 字段说明：
 *   - deepseek : DeepSeek API Key（推荐，默认主力模型，便宜效果好）
 *              申请地址：https://platform.deepseek.com/
 *   - volc     : 阿里云百炼 / 火山引擎 API Key（Qwen / 豆包模型）
 *              申请地址：https://bailian.console.aliyun.com/
 *   - openai   : OpenAI / OpenRouter API Key（GPT / Claude 等）
 *              OpenRouter 申请：https://openrouter.ai/keys
 *   - gemini   : Google Gemini API Key（免费额度）
 *              申请地址：https://aistudio.google.com/apikey
 */

return [
    // ======= DeepSeek（主力对话，推荐先配置） =======
    // 模型：deepseek-chat / deepseek-reasoner
    // 价格：约 ¥1/百万 token
    'deepseek' => env('AI_DEEPSEEK_KEY', ''),

    // ======= 阿里云百炼（开源模型 Qwen） =======
    // 模型：qwen-plus / qwen-max / qwq-32b
    // 价格：约 ¥2/百万 token
    'volc'     => env('AI_ALIYUN_KEY', ''),

    // ======= OpenRouter（GPT / Claude / 开源聚合） =======
    // 模型：gpt-4o / claude-sonnet-4-5 等
    // 价格：各平台官方价格 + OpenRouter 少许手续费
    'openai'   => env('AI_OPENROUTER_KEY', ''),

    // ======= Gemini（免费额度） =======
    // 模型：gemini-2.0-flash / gemini-2.5-pro
    // 价格：免费额度用完后按量计费
    'gemini'   => env('AI_GEMINI_KEY', ''),
];
