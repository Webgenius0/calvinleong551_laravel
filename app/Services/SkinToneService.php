<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SkinToneService
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        // ✅ Using direct .env
        $this->apiKey = env('GEMINI_API_KEY');
        $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    }

    public function analyzeSkinTone(string $brideBase64, string $groomBase64, string $season): string
    {
        if (!$this->apiKey) {
            throw new Exception('GEMINI_API_KEY not set in .env file');
        }

        $prompt = "You are a professional wedding stylist and color palette designer.
Analyze the bride and groom images and return JSON only with this structure:

{
  \"bride\": {
    \"skin_tone\": \"Warm Beige\",
    \"color_code\": \"#E3B18C\",
    \"matching_colors\": [\"#F8E1D4\", \"#E8BFAF\", \"#D89F8E\", \"#C17F6E\"]
  },
  \"groom\": {
    \"skin_tone\": \"Olive Tan\",
    \"color_code\": \"#C68642\",
    \"matching_colors\": [\"#EED8C2\", \"#D4A373\", \"#B97337\", \"#8E4B1D\"]
  },
  \"season\": {
    \"name\": \"$season\",
    \"palette\": [\"#F5E3C3\", \"#C4A484\", \"#9B7653\"],
    \"description\": \"Elegant warm seasonal palette.\"
  }
}

Return **only valid JSON**, no extra explanation.";

        $payload = [
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
                        ]],
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->endpoint . '?key=' . $this->apiKey, $payload);

        if ($response->failed()) {
            throw new Exception('Gemini API Error: ' . $response->body());
        }

        $data = $response->json();
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $json = $this->extractJson($rawText);
        $parsed = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON from Gemini: ' . json_last_error_msg());
        }

        return json_encode($parsed);
    }

    private function extractJson(string $text): string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new Exception('JSON not found in Gemini response');
        }

        return substr($text, $start, $end - $start + 1);
    }
}
