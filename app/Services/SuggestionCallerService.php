<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SuggestionCallerService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $model = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Calls suggestion model for text response.
     * 
     * @param string $prompt Prompt
     * @return string|null Response text
     */
    public function call(string $prompt): ?string
    {
        $contents = [['parts' => [['text' => $prompt]]]];
        $response = Http::post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
            'contents' => $contents
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        return null;
    }
}