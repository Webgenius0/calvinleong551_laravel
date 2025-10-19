<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SkinToneService
{
    // ======================================================
    // 🧠 CONFIGURATION CONSTANTS
    // ======================================================
    private const OPENAI_BASE_URL = 'https://api.openai.com/v1';
    private const MODEL = 'gpt-4o';  // Supports vision
    private const TIMEOUT = 180; // Increased to 3 minutes

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY', '');

        Log::info('🤖 OpenAI Service Initializing');

        if (empty($this->apiKey)) {
            throw new Exception('OPENAI_API_KEY not set in .env file');
        }

        $this->validateApiKey();
    }

    /**
     * ✅ Validate OpenAI API Key
     */
    private function validateApiKey(): void
    {
        Log::info('🔍 Validating OpenAI API key');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post(self::OPENAI_BASE_URL . '/chat/completions', [
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hello']
                    ],
                    'max_tokens' => 5
                ]);

            if ($response->failed()) {
                $error = $response->json()['error'] ?? [];
                throw new Exception('Invalid OPENAI_API_KEY: ' . json_encode($error));
            }

            Log::info('✅ OpenAI API key validation successful');
        } catch (Exception $e) {
            Log::error('❌ OpenAI API validation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 🧠 Bride + Groom Skin Tone Analysis
     */
    public function analyzeSkinTone(string $brideBase64, string $groomBase64, string $season): array
    {
        $prompt = $this->buildPrompt($season);

        $payload = [
            'model' => self::MODEL,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $brideBase64,
                                'detail' => 'high'
                            ]
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $groomBase64,
                                'detail' => 'high'
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 2000,
            'temperature' => 0.2
        ];

        Log::info('🎨 OpenAI: Analyzing wedding images', [
            'season' => $season,
            'bride_size' => strlen($brideBase64),
            'groom_size' => strlen($groomBase64)
        ]);

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])
            ->post(self::OPENAI_BASE_URL . '/chat/completions', $payload);

        if ($response->failed()) {
            $error = $response->json()['error'] ?? $response->body();
            Log::error('❌ OpenAI API Error', ['error' => $error]);
            throw new Exception('OpenAI API Error: ' . json_encode($error));
        }

        $rawText = $response->json()['choices'][0]['message']['content'] ?? '';

        if (empty($rawText)) {
            throw new Exception('Empty response from OpenAI API');
        }

        Log::info('✅ OpenAI analysis completed');

        $json = $this->extractJson($rawText);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Parse Error', [
                'error' => json_last_error_msg(),
                'raw' => $rawText
            ]);
            throw new Exception('Invalid JSON from OpenAI: ' . json_last_error_msg());
        }

        // Validate response structure
        if (!isset($data['bride'], $data['groom'], $data['season'])) {
            throw new Exception('Invalid response structure from OpenAI');
        }

        return $data;
    }

    /**
     * 📝 Build Prompt for OpenAI
     */
    private function buildPrompt(string $season): string
    {
        Log::info('📝 Building prompt for season: ' . $season);

        return <<<PROMPT
You are a professional wedding stylist and color analyst with expertise in skin tone analysis.

Analyze the two images provided (bride first, groom second) and provide detailed wedding color recommendations.

**Requirements:**

1. **Bride Analysis:**
   - Accurate skin tone classification (Fair, Light, Medium, Tan, Deep, etc.)
   - 4 precise HEX color codes that match her skin undertones
   - 4 complementary wedding outfit colors (HEX codes)

2. **Groom Analysis:**
   - Accurate skin tone classification
   - 4 precise HEX color codes matching his undertones
   - 4 complementary wedding outfit colors (HEX codes)

3. **Seasonal Palette for {$season}:**
   - 6 harmonious HEX colors perfect for {$season} weddings
   - Brief description of the seasonal theme (2-3 sentences)

**Output Format (STRICT JSON - No markdown, no explanations):**

```json
{
  "bride": {
    "skin_tone": "string",
    "color_code": ["#HEX1", "#HEX2", "#HEX3", "#HEX4"],
    "matching_colors": ["#HEX1", "#HEX2", "#HEX3", "#HEX4"]
  },
  "groom": {
    "skin_tone": "string",
    "color_code": ["#HEX1", "#HEX2", "#HEX3", "#HEX4"],
    "matching_colors": ["#HEX1", "#HEX2", "#HEX3", "#HEX4"]
  },
  "season": {
    "name": "{$season}",
    "palette": ["#HEX1", "#HEX2", "#HEX3", "#HEX4", "#HEX5", "#HEX6"],
    "description": "string"
  }
}
```

Return ONLY valid JSON. No additional text or explanations.
PROMPT;
    }

    /**
     * 🔍 Extract JSON from OpenAI Response
     */
    private function extractJson(string $text): string
    {
        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
            return trim($matches[1]);
        }

        // Find JSON object boundaries
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new Exception('No valid JSON found in OpenAI response');
        }

        return substr($text, $start, $end - $start + 1);
    }

    /**
     * 🧪 Test OpenAI Connection
     */
    public function testConnection(): array
    {
        Log::info('🧪 Testing OpenAI connection');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post(self::OPENAI_BASE_URL . '/chat/completions', [
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "Hello World"']
                    ],
                    'max_tokens' => 10
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'model' => self::MODEL,
                    'message' => 'OpenAI connection successful'
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}