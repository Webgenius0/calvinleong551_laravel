<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ImageAnalyzerService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $model = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Analyzes image for skin tone and colors.
     * 
     * @param string $imagePath Image path
     * @param string $type 'bride' or 'groom'
     * @return array Analysis data
     */
    public function analyze(string $imagePath, string $type): array
    {
        $prompt = $type === 'bride' ? $this->getPrompt($type) : $this->getPrompt($type);

        $mimeType = mime_content_type($imagePath);
        $base64Image = base64_encode(file_get_contents($imagePath));

        $contents = [
            [
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64Image
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
            'contents' => $contents
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            return json_decode($text, true) ?: ['skin_tone' => 'neutral', 'colors' => ['#ffffff']];
        }

        return ['skin_tone' => 'neutral', 'colors' => ['#ffffff']];
    }

    private function getPrompt(string $type): string
    {
        $prompts = [
            'bride' => "Analyze this image of a bride. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 3-5 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"warm\", \"colors\": [\"#ffcc99\", \"#ffffff\"]}",
            'groom' => "Analyze this image of a groom. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 3-5 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"cool\", \"colors\": [\"#a8e6cf\", \"#000000\"]}"
        ];
        return $prompts[$type] ?? '';
    }
}