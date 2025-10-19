<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SkinToneService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = 'gemini-2.5-flash'; // Fast text generation

        if (!$this->apiKey) {
            throw new Exception('GEMINI_API_KEY is not set in .env');
        }
    }

    /**
     * Suggest bridal & groom look based on season, theme, or skin tone
     */
  public function suggestLook(string $season = 'winter', string $theme = 'traditional', string $skinTone = 'fair'): array
{
    try {
        $prompt = <<<PROMPT
You are a professional wedding stylist. 
Suggest a perfect bridal and groom look for a Bangladeshi couple for a {$season} wedding with a {$theme} theme.
Skin tone: {$skinTone}

Return valid JSON ONLY in the exact format:
{
  "bride": {
    "skin_tone": "",
    "makeup_style": "",
    "hairstyle": "",
    "outfit": "",
    "color_palette": [],
    "accessories": [],
    "image_reference": ""
  },
  "groom": {
    "skin_tone": "",
    "hairstyle": "",
    "outfit": "",
    "color_palette": [],
    "accessories": [],
    "image_reference": ""
  }
}
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]]
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Gemini Look Suggestion API Error', ['body' => $response->body()]);
            throw new Exception('Gemini API request failed.');
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // ✅ Remove ```json and ``` wrappers
        $cleanText = preg_replace('/^```json|```$/m', '', $text);
        $cleanText = trim($cleanText);

        $parsed = json_decode($cleanText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini Invalid JSON', [
                'original_text' => $text,
                'clean_text'    => $cleanText,
                'json_error'    => json_last_error_msg()
            ]);
            throw new Exception('Invalid JSON returned from Gemini.');
        }

        return $parsed;
    } catch (Exception $e) {
        Log::error('Bridal Groom Look Suggestion Failed', ['error' => $e->getMessage()]);
        return [];
    }
}

}
