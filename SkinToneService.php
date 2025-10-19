<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SkinToneService
{
    private const ANALYSIS_MODEL = 'gemini-2.5-flash';
    private const IMAGE_MODEL = 'gemini-2.5-flash-image';
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';
    private const TIMEOUT = 180; // Increased to 180s for slower image gen

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        Log::info('🤖 Gemini Service Initializing');
        if (empty($this->apiKey)) {
            throw new Exception('GEMINI_API_KEY not set in .env');
        }
    }

    // ===================== Main Analysis =====================
    public function analyzeSkinTone(string $brideBase64, string $groomBase64, string $season): array
    {
        $prompt = $this->buildPrompt($season);

        Log::info('🎨 Gemini: Analyzing wedding images', [
            'season' => $season,
            'bride_size' => strlen($brideBase64),
            'groom_size' => strlen($groomBase64),
        ]);

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post(self::BASE_URL . '/' . self::ANALYSIS_MODEL . ':generateContent', [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => $brideBase64
                            ]],
                            ['inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => $groomBase64
                            ]]
                        ]
                    ]
                ]
            ]);

        if ($response->failed()) {
            Log::error('❌ Gemini API Error', ['response' => $response->body()]);
            throw new Exception('Gemini API Error: ' . $response->body());
        }

        $decodedResponse = $response->json();
        if (!is_array($decodedResponse)) {
            throw new Exception('Invalid JSON response from Gemini analysis');
        }

        $rawText = $this->extractText($decodedResponse);
        Log::info('📝 Gemini Raw Text Response', ['raw' => substr($rawText, 0, 500) . '...']); // Truncate for log

        if (!$rawText) {
            throw new Exception('No text response from Gemini analysis');
        }

        $jsonString = $this->extractJson($rawText);
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE || !$data || !isset($data['bride'], $data['groom'])) {
            Log::error('Invalid JSON from Gemini', ['json_error' => json_last_error_msg(), 'json_string' => $jsonString]);
            throw new Exception('Invalid JSON response from Gemini: ' . json_last_error_msg());
        }

        // Ensure season is an array with defaults
        if (!isset($data['season']) || !is_array($data['season'])) {
            $data['season'] = [
                'theme' => ucfirst($season) . ' Wedding Theme',
                'palette' => $this->getDefaultSeasonPalette($season)
            ];
        } elseif (!isset($data['season']['palette'])) {
            $data['season']['palette'] = $this->getDefaultSeasonPalette($season);
        }

        // Ensure bride and groom have matching_colors
        $data['bride']['matching_colors'] = $data['bride']['matching_colors'] ?? $this->extractColorsFromPalette($data['bride']['palette'] ?? []);
        $data['groom']['matching_colors'] = $data['groom']['matching_colors'] ?? $this->extractColorsFromPalette($data['groom']['palette'] ?? []);

        // ✅ Generate Edited Images based on skin tones, colors, and season
        $data['bride']['edited_image'] = $this->generateEditedImage($brideBase64, 'bride', $data['bride'], $season);
        $data['groom']['edited_image'] = $this->generateEditedImage($groomBase64, 'groom', $data['groom'], $season);
        $data['season']['image'] = $this->generateSeasonImage($season, $data['season']['palette']);

        Log::info('✅ Analysis complete', ['has_images' => [
            'bride' => !empty($data['bride']['edited_image']),
            'groom' => !empty($data['groom']['edited_image']),
            'season' => !empty($data['season']['image'])
        ]]);

        return $data;
    }

    // ===================== Edited Bride/Groom Image =====================
    private function generateEditedImage(string $base64, string $role, array $analysis, string $season): ?string
    {
        try {
            $matchingColors = json_encode($analysis['matching_colors']);
            $prompt = "Edit this image of the {$role} to enhance their appearance for a beautiful wedding in the {$season} season. " .
                      "Use these matching colors for skin tone harmony: {$matchingColors}. " .
                      "Make it realistic, elegant, high-quality portrait with subtle makeup and attire adjustments. " .
                      "Focus on natural lighting and seasonal vibes. Return only the edited image.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => $base64
                            ]]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseModalities' => ['Image']
                ]
            ];

            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post(self::BASE_URL . '/' . self::IMAGE_MODEL . ':generateContent', $payload);

            if ($response->failed()) {
                Log::error("❌ Edited {$role} image API Error", ['response' => $response->body()]);
                return null;
            }

            $decodedResponse = $response->json();
            if (!is_array($decodedResponse)) {
                Log::error("❌ Invalid response for edited {$role} image", ['body' => $response->body()]);
                return null;
            }

            $base64Image = $this->extractBase64Image($decodedResponse);
            Log::info("✅ Generated edited {$role} image", ['size' => strlen($base64Image ?? '')]);

            return $base64Image;
        } catch (Exception $e) {
            Log::error("Failed to generate edited {$role} image", ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ===================== Season Theme Image =====================
    private function generateSeasonImage(string $season, array $palette): ?string
    {
        try {
            $paletteJson = json_encode($palette);
            $prompt = "Generate a beautiful, realistic wedding theme background image for the {$season} season. " .
                      "Incorporate elegant decorations, flowers, and ambiance using this color palette: {$paletteJson}. " .
                      "High-quality, romantic atmosphere suitable for bride and groom portraits. Return only the image.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseModalities' => ['Image']
                ]
            ];

            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post(self::BASE_URL . '/' . self::IMAGE_MODEL . ':generateContent', $payload);

            if ($response->failed()) {
                Log::error('❌ Season image API Error', ['response' => $response->body()]);
                return null;
            }

            $decodedResponse = $response->json();
            if (!is_array($decodedResponse)) {
                Log::error('❌ Invalid response for season image', ['body' => $response->body()]);
                return null;
            }

            $base64Image = $this->extractBase64Image($decodedResponse);
            Log::info('✅ Generated season theme image', ['size' => strlen($base64Image ?? '')]);

            return $base64Image;
        } catch (Exception $e) {
            Log::error('Failed to generate season image', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ===================== Prompt Builder =====================
    private function buildPrompt(string $season): string
    {
        return <<<PROMPT
You are a professional wedding stylist, color analyst, and skin tone expert.

The first image is the BRIDE, the second image is the GROOM.

Analyze their skin tones (e.g., warm, cool, neutral; fair, medium, deep), undertones, and suggest harmonious color palettes for wedding attire, makeup, and accessories that complement BOTH individuals' skin tones.

Consider the {$season} season for seasonal vibes (e.g., warm tones for autumn, pastels for spring).

Respond with **ONLY VALID JSON** - no other text, markdown, or explanations. Do not wrap in code blocks.

JSON structure must be exact:
{
  "bride": {
    "skin_tone": "description",
    "undertone": "warm/cool/neutral",
    "palette": ["#hex1", "#hex2", "#hex3"],
    "suggestions": "brief wedding style tips",
    "matching_colors": ["primary", "accent1", "accent2"]
  },
  "groom": {
    "skin_tone": "description",
    "undertone": "warm/cool/neutral",
    "palette": ["#hex1", "#hex2", "#hex3"],
    "suggestions": "brief wedding style tips",
    "matching_colors": ["primary", "accent1", "accent2"]
  },
  "season": {
    "theme": "description of seasonal wedding theme",
    "palette": ["#hex1", "#hex2", "#hex3"]
  }
}

Use hex codes for palettes. Ensure season is an object with theme and palette.
PROMPT;
    }

    // ===================== Extract Text =====================
    private function extractText(array $response): ?string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    // ===================== Extract Base64 =====================
    private function extractBase64Image(array $response): ?string
    {
        // Handle possible structures for image output
        if (isset($response['candidates'][0]['content']['parts'][0]['inline_data']['data'])) {
            return $response['candidates'][0]['content']['parts'][0]['inline_data']['data'];
        }
        // Fallback if in a different part
        foreach ($response['candidates'][0]['content']['parts'] ?? [] as $part) {
            if (isset($part['inline_data']['data'])) {
                return $part['inline_data']['data'];
            }
        }
        return null;
    }

    // ===================== Extract JSON =====================
    private function extractJson(string $text): string
    {
        // Strip markdown code blocks if present
        $text = preg_replace('/^```json\s*\n?|\n?```$/s', '', $text);
        $text = trim($text);

        // Find the first complete JSON object using regex for nested
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            return $matches[0];
        }
        throw new Exception('No valid JSON found in response');
    }

    // ===================== Helpers =====================
    private function getDefaultSeasonPalette(string $season): array
    {
        $palettes = [
            'summer' => ['#87CEEB', '#98FB98', '#F0E68C', '#DDA0DD'],
            
            'winter' => ['#E0F6FF', '#B0E0E6', '#F0F8FF', '#ADD8E6'],
            'autumn' => ['#D2691E', '#CD853F', '#F4A460', '#DEB887'],
            'spring' => ['#FFB6C1', '#98FB98', '#FFE4E1', '#F0E68C']
        ];
        return $palettes[$season] ?? ['#FFFFFF', '#000000'];
    }

    private function extractColorsFromPalette(array $palette): array
    {
        return array_slice($palette, 0, 3); // Take first 3 as matching
    }

    // ===================== Test Models =====================
    public function testImageGeneration(): array
    {
        return [self::ANALYSIS_MODEL, self::IMAGE_MODEL];
    }
}