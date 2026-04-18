<?php

namespace app\common\service\ai;

/**
 * AI 服务 - 直连各平台 API，绕过 imai.club 中间商赚差价
 *
 * 支持：DeepSeek / OpenAI 兼容 / Gemini / 火山引擎
 *
 * 用法：
 *   AIService::Chat()->message($params)        // DeepSeek (默认)
 *   AIService::Chat()->openaiMessage($params)  // OpenAI / 兼容 API
 *   AIService::Chat()->geminiMessage($params)  // Gemini
 */
class AIService
{
    const API_URL = 'https://openrouter.ai/api/v1';
    const API_KEY = ''; // TODO: 用户配置 API Key

    /** @var array 上游返回的 usage 信息 */
    private array $usage = [];

    /** @var string 错误信息 */
    private string $error = '';

    // ========== DeepSeek ==========
    const DEEPSEEK_API_URL = 'https://api.deepseek.com/v1/chat/completions';
    const DEEPSEEK_API_KEY = ''; // TODO: 用户配置

    // ========== 火山引擎 (字节) ==========
    const VOLC_API_URL = 'https://ark.cn-beijing.volces.com/api/v3/chat/completions';
    const VOLC_API_KEY  = ''; // TODO: 用户配置

    // ========== OpenRouter (聚合多模型) ==========
    const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    const OPENROUTER_API_KEY  = ''; // TODO: 用户配置

    // ========== Gemini ==========
    const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * 获取错误
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * 获取 usage
     */
    public function getUsage(): array
    {
        return $this->usage;
    }

    // ==================== Chat (通用/DeepSeek) ====================

    /**
     * 通用聊天 (默认走 DeepSeek)
     * 兼容 ChatLogic::requestChatUrl() 的调用方式
     *
     * @param array $params
     *   - messages: array  [{role: 'user'|'assistant'|'system', content: string}]
     *   - model: string  (默认 deepseek-chat)
     *   - temperature: float
     *   - max_tokens: int
     *   - stream: bool
     *   - user_id: int
     *   - task_id: string
     *   - chat_type: int
     * @return array 兼容 imai.club 返回格式
     */
    public function message(array $params): array
    {
        $model      = $params['model'] ?? 'deepseek-chat';
        $messages   = $params['messages'] ?? [];
        $stream     = !empty($params['stream']);
        $temperature = $params['temperature'] ?? 1.0;
        $maxTokens  = $params['max_tokens'] ?? 4096;

        // 按 model 判断走哪个平台
        $platform = $this->detectPlatform($model);

        if ($stream) {
            return $this->streamChat($platform, $params);
        }

        $body = [
            'model'       => $this->mapModel($platform, $model),
            'messages'    => $messages,
            'temperature' => (float)$temperature,
            'max_tokens'  => (int)$maxTokens,
            'stream'      => false,
        ];

        // DeepSeek 支持 reasoning
        if (!empty($params['open_reasoning']) && $platform === 'deepseek') {
            $body['reasoning'] = ['type' => 'reasoning', 'attention' => true];
        }

        $response = $this->request($platform, $body);

        return $this->normalizeResponse($response, $params);
    }

    /**
     * OpenAI 兼容接口 (GPT / OpenRouter)
     *
     * @param array $params
     * @return array
     */
    public function openaiMessage(array $params): array
    {
        $params['model'] = $params['model'] ?? 'gpt-4o';
        $platform = 'openai';

        if (!empty($params['stream'])) {
            return $this->streamChat($platform, $params);
        }

        $body = [
            'model'       => $params['model'],
            'messages'    => $params['messages'] ?? [],
            'temperature' => (float)($params['temperature'] ?? 1.0),
            'max_tokens'  => (int)($params['max_tokens'] ?? 4096),
            'stream'      => false,
        ];

        $response = $this->request($platform, $body);
        return $this->normalizeResponse($response, $params);
    }

    /**
     * Gemini 接口
     *
     * @param array $params
     * @return array
     */
    public function geminiMessage(array $params): array
    {
        $model    = $params['model'] ?? 'gemini-2.0-flash';
        $messages = $params['messages'] ?? [];

        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }

        $body = [
            'contents' => $contents,
        ];

        if (!empty($params['temperature'])) {
            $body['generationConfig'] = [
                'temperature'  => (float)$params['temperature'],
                'maxOutputTokens' => (int)($params['max_tokens'] ?? 2048),
            ];
        }

        $url = self::GEMINI_API_URL . "/{$model}:generateContent";
        if (!empty($params['api_key'])) {
            $url .= '?key=' . $params['api_key'];
        }

        $response = $this->httpRequest($url, $body, 'POST', [], 'gemini');
        return $this->normalizeGeminiResponse($response, $params);
    }

    // ==================== 流式输出 ====================

    /**
     * 流式聊天
     *
     * @param string $platform
     * @param array  $params
     * @return array  模拟非流式返回（ChatLogic 实际通过 exit 实现流式输出）
     */
    private function streamChat(string $platform, array $params): array
    {
        $body = [
            'model'       => $this->mapModel($platform, $params['model'] ?? 'deepseek-chat'),
            'messages'    => $params['messages'] ?? [],
            'temperature' => (float)($params['temperature'] ?? 1.0),
            'max_tokens'  => (int)($params['max_tokens'] ?? 4096),
            'stream'      => true,
        ];

        // 设置流式响应头（与 ChatLogic::exit 配合）
        if (!headers_sent()) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        }

        $ch = curl_init($this->getApiUrl($platform));
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);

        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS  => $json,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($params) {
                echo $data;
                if (ob_get_level()) ob_flush();
                flush();
                return strlen($data);
            },
            CURLOPT_HTTPHEADER => $this->buildHeaders($platform),
            CURLOPT_TIMEOUT     => 120,
        ]);

        curl_exec($ch);
        curl_close($ch);
        exit;
    }

    // ==================== 核心请求 ====================

    /**
     * 发送请求到对应平台
     */
    private function request(string $platform, array $body): array
    {
        $url     = $this->getApiUrl($platform);
        $headers = $this->buildHeaders($platform);

        $response = $this->httpRequest($url, $body, 'POST', $headers, $platform);

        if (isset($response['error'])) {
            $this->error = $response['error']['message'] ?? json_encode($response['error']);
            throw new \Exception('AI 请求失败: ' . $this->error);
        }

        return $response;
    }

    /**
     * HTTP 请求
     */
    private function httpRequest(string $url, array $body, string $method = 'POST', array $headers = [], string $platform = 'deepseek'): array
    {
        $ch = curl_init();

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);

        if (empty($headers)) {
            $headers = $this->buildHeaders($platform);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => ($method === 'POST'),
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('CURL 错误: ' . $error);
        }

        if ($httpCode >= 400) {
            $err = json_decode($response, true);
            $msg = $err['error']['message'] ?? $err['error']['error']['message'] ?? "HTTP {$httpCode}";
            throw new \Exception('AI API 返回错误: ' . $msg);
        }

        return json_decode($response, true) ?: [];
    }

    // ==================== 平台判断 ====================

    /**
     * 根据 model 名称判断走哪个平台
     */
    private function detectPlatform(string $model): string
    {
        $model = strtolower($model);

        if (strpos($model, 'deepseek') !== false) {
            return 'deepseek';
        }
        if (strpos($model, 'gemini') !== false) {
            return 'gemini';
        }
        if (strpos($model, 'gpt') !== false || strpos($model, 'claude') !== false) {
            return 'openai';
        }
        if (strpos($model, 'qwen') !== false || strpos($model, 'qwq') !== false) {
            return 'volc'; // 阿里云百炼
        }

        // 默认 DeepSeek
        return 'deepseek';
    }

    /**
     * 获取 API URL
     */
    private function getApiUrl(string $platform): string
    {
        return match ($platform) {
            'deepseek' => self::DEEPSEEK_API_URL,
            'volc'     => self::VOLC_API_URL,
            'openai'   => self::OPENROUTER_API_URL,
            'gemini'   => self::GEMINI_API_URL . '/gemini-pro:generateContent',
            default    => self::DEEPSEEK_API_URL,
        };
    }

    /**
     * 构建请求头
     */
    private function buildHeaders(string $platform): array
    {
        $headers = ['Content-Type: application/json'];

        $apiKey = $this->getApiKey($platform);
        if ($apiKey) {
            $headers[] = "Authorization: Bearer {$apiKey}";
        }

        // OpenRouter 需要额外头
        if ($platform === 'openai') {
            $headers[] = 'HTTP-Referer: https://xul.rswl.ai';
            $headers[] = 'X-Title: XUL Wallet';
        }

        return $headers;
    }

    /**
     * 获取各平台 API Key（从配置读取）
     */
    private function getApiKey(string $platform): string
    {
        $keys = config('model.keys', []);

        return match ($platform) {
            'deepseek' => $keys['deepseek'] ?? self::DEEPSEEK_API_KEY,
            'volc'     => $keys['volc']     ?? self::VOLC_API_KEY,
            'openai'   => $keys['openai']   ?? self::OPENROUTER_API_KEY,
            'gemini'   => $keys['gemini']   ?? '',
            default    => '',
        };
    }

    /**
     * 模型名映射（平台相关）
     */
    private function mapModel(string $platform, string $model): string
    {
        // DeepSeek
        if ($platform === 'deepseek') {
            return match (strtolower($model)) {
                'deepseek-chat', 'deepseek' => 'deepseek-chat',
                'deepseek-reasoner'         => 'deepseek-reasoner',
                default                     => 'deepseek-chat',
            };
        }

        // 火山引擎（阿里云百炼）
        if ($platform === 'volc') {
            return match (strtolower($model)) {
                'qwen-plus'   => 'qwen-plus',
                'qwen-max'    => 'qwen-max',
                'qwq-32b'     => 'qwq-32b',
                default        => 'qwen-plus',
            };
        }

        return $model;
    }

    // ==================== 响应格式化（兼容 imai.club 格式） ====================

    /**
     * 标准化 OpenAI 兼容响应
     */
    private function normalizeResponse(array $response, array $params): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        $usage   = $response['usage'] ?? [];

        $this->usage = $usage;

        // 兼容 ChatLogic 的计费格式
        $reply = [
            'data' => [
                'choices' => [
                    0 => [
                        'message' => [
                            'content' => $content,
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens'     => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens'  => $usage['completion_tokens'] ?? 0,
                    'total_tokens'       => $usage['total_tokens'] ?? 0,
                ],
            ],
        ];

        return $reply;
    }

    /**
     * Gemini 响应格式化
     */
    private function normalizeGeminiResponse(array $response, array $params): array
    {
        $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage   = $response['usageMetadata'] ?? [];

        $this->usage = $usage;

        return [
            'data' => [
                'choices' => [
                    0 => [
                        'message' => [
                            'content' => $content,
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens'     => $usage['promptTokenCount'] ?? 0,
                    'completion_tokens'  => $usage['candidatesTokenCount'] ?? 0,
                    'total_tokens'      => $usage['totalTokenCount'] ?? 0,
                ],
            ],
        ];
    }
}
