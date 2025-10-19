<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SkinToneService
{
    protected string $apiKey;
    protected string $model;

    private const BRIDE_EDITED_FOLDER = 'uploads/bride_edited';
    private const GROOM_EDITED_FOLDER = 'uploads/groom_edited';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = 'gemini-2.5-flash';

        if (!$this->apiKey) {
            throw new Exception('GEMINI_API_KEY is not set in .env');
        }
    }

    /**
     * ✅ Suggest bridal & groom look + Generate Edited Images
     */
    public function suggestLook(string $season = 'winter', string $theme = 'traditional', string $skinTone = 'fair'): array
    {
        try {
            $suggestion = $this->generateLookSuggestion($season, $theme, $skinTone);

            if (empty($suggestion)) {
                throw new Exception('No suggestion data generated.');
            }

            // ✅ Bride Edited Image Generate
            $brideEdited = $this->generateEditedImage('bride', $suggestion['bride']['image_reference'] ?? null);

            // ✅ Groom Edited Image Generate
            $groomEdited = $this->generateEditedImage('groom', $suggestion['groom']['image_reference'] ?? null);

            // ✅ Add Edited Image URLs into suggestion
            $suggestion['bride']['edited_image'] = $brideEdited;
            $suggestion['groom']['edited_image'] = $groomEdited;

            return $suggestion;
        } catch (Exception $e) {
            Log::error('Bridal Groom Look Suggestion Failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 🧠 Gemini Look Suggestion (Text only)
     */
    private function generateLookSuggestion(string $season, string $theme, string $skinTone): array
    {
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

        // Clean ```json blocks
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
    }

    /**
     * 🧠 Generate Edited Image for Bride or Groom
     * This can be replaced with AI editing / background removal API
     */
    private function generateEditedImage(string $type, ?string $imageUrl): ?string
    {
        try {
            if (!$imageUrl) {
                return null;
            }

            $editedFolder = $type === 'bride' ? self::BRIDE_EDITED_FOLDER : self::GROOM_EDITED_FOLDER;

            // Download original image
            $imageContent = Http::get($imageUrl)->body();
            $filename = $type . '_edited_' . Str::random(10) . '.jpg';
            $path = $editedFolder . '/' . $filename;

            // Save image locally
            file_put_contents(public_path($path), $imageContent);

            // 👉 If you want to apply editing (e.g. remove background), call API here
            // Example: remove.bg or local GD processing

            return asset($path);
        } catch (\Exception $e) {
            Log::error("Failed to generate edited {$type} image", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
