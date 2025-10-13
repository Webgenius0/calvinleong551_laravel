<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SkinToneService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'timeout'  => 120,
        ]);
    }

    /**
     * Bride & Groom skin tone detect + season palette + season image using Gemini
     */
    public function analyzeSkinTone($brideImageBase64, $groomImageBase64, $season)
    {
        try {
            // ===== 1. Analyze skin tones with Gemini =====
            $prompt = "You are a professional wedding stylist and color analyst. 
Analyze the given bride and groom photos and provide:
1. Their skin tone name and HEX color code.
2. Based on the selected season ($season), provide a suitable seasonal palette and a short description.
Return ONLY valid JSON strictly in this exact format:

{
  \"bride\": {\"skin_tone\": \"Warm Beige\", \"color_code\": \"#E3B18C\"},
  \"groom\": {\"skin_tone\": \"Olive Tan\", \"color_code\": \"#C68642\"},
  \"season\": {\"name\": \"$season\", \"palette\": [\"#F5E3C3\", \"#C4A484\", \"#9B7653\"], \"description\": \"Light warm autumn palette\"}
}";

            $body = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg', 
                                    'data' => $brideImageBase64
                                ]
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg', 
                                    'data' => $groomImageBase64
                                ]
                            ],
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 4096,
                ]
            ];

            if (!$this->apiKey) {
                throw new Exception('Gemini API key not found');
            }

            // Use correct model names that actually exist
            $modelsToTry = [
                'gemini-1.5-flash',
                'gemini-1.0-pro-vision', 
                'gemini-pro',
                'gemini-pro-vision'
            ];

            $result = null;
            $lastError = null;

            foreach ($modelsToTry as $model) {
                try {
                    $url = "models/{$model}:generateContent?key={$this->apiKey}";
                    Log::info("Trying Gemini model: {$model}");
                    
                    $response = $this->client->post($url, [
                        'json' => $body,
                        'timeout' => 120
                    ]);

                    $data = json_decode($response->getBody(), true);
                    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if ($rawText) {
                        Log::info("Success with model: {$model}");
                        $result = $rawText;
                        break;
                    }
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning("Model {$model} failed: " . $e->getMessage());
                    continue;
                }
            }

            if (!$result) {
                throw new Exception('All Gemini models failed: ' . $lastError);
            }

            Log::info('Gemini Raw Response: ' . $result);

            $json = $this->extractJson($result);
            $parsed = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from Gemini: ' . json_last_error_msg());
            }

            // ===== 2. Get random seasonal image from Unsplash =====
            $seasonImage = $this->getRandomSeasonalImage($season);
            $parsed['season']['image'] = $seasonImage;

            Log::info('Season image generated successfully: ' . $seasonImage);

            return json_encode($parsed);

        } catch (Exception $e) {
            Log::error('SkinToneService Error: ' . $e->getMessage());
            
            // Return fallback data with random seasonal image
            return json_encode([
                'error' => $e->getMessage(),
                'bride' => ['skin_tone' => 'Fair', 'color_code' => '#F5E3C3'],
                'groom' => ['skin_tone' => 'Medium', 'color_code' => '#C68642'],
                'season' => [
                    'name' => $season, 
                    'palette' => $this->getFallbackPalette($season),
                    'description' => 'Beautiful ' . $season . ' wedding theme',
                    'image' => $this->getRandomSeasonalImage($season)
                ]
            ]);
        }
    }

    /**
     * Get random seasonal image from Unsplash based on season
     */
    private function getRandomSeasonalImage($season)
    {
        try {
            $seasonImages = [
                'spring' => [
                    'https://images.unsplash.com/photo-1531512073830-ba890ca4eba2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80',
                    'https://images.unsplash.com/photo-1521334884684-d80222895322?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1490750967868-88aa4486c946?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1476820865390-c52aeebb9891?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80'
                ],
                'summer' => [
                    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80',
                    'https://images.unsplash.com/photo-1505142468610-359e7d316be0?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80',
                    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80',
                    'https://images.unsplash.com/photo-1518837695005-2083093ee35b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80'
                ],
                'autumn' => [
                    'https://images.unsplash.com/photo-1506197603052-3cc9c3a201bd?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80',
                    'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80',
                    'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2071&q=80'
                ],
                'winter' => [
                    'https://images.unsplash.com/photo-1483664852095-d6cc6870702d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1476820865390-c52aeebb9891?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1418985991508-e47386d96a71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1453306458620-5bbef13a5bca?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
                    'https://images.unsplash.com/photo-1483921020237-2ff51e8e4b22?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80'
                ]
            ];

            $images = $seasonImages[strtolower($season)] ?? $seasonImages['summer'];
            $randomImage = $images[array_rand($images)];
            
            Log::info("Selected random {$season} image: " . $randomImage);
            return $randomImage;

        } catch (Exception $e) {
            Log::error('Failed to get random seasonal image: ' . $e->getMessage());
            return $this->getDefaultSeasonImage($season);
        }
    }

    /**
     * Get default seasonal image as fallback
     */
    private function getDefaultSeasonImage($season)
    {
        $defaultImages = [
            'spring' => 'https://images.unsplash.com/photo-1531512073830-ba890ca4eba2?ixlib=rb-4.0.3&auto=format&fit=crop&w=2074&q=80',
            'summer' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
            'autumn' => 'https://images.unsplash.com/photo-1506197603052-3cc9c3a201bd?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
            'winter' => 'https://images.unsplash.com/photo-1483664852095-d6cc6870702d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80'
        ];

        return $defaultImages[strtolower($season)] ?? $defaultImages['summer'];
    }

    /**
     * Get fallback color palette
     */
    private function getFallbackPalette($season)
    {
        $palettes = [
            'spring' => ['#FFB6C1', '#98FB98', '#FFFF99'],
            'summer' => ['#87CEEB', '#40E0D0', '#FFFFFF'],
            'autumn' => ['#D2691E', '#FF8C00', '#8B4513'],
            'winter' => ['#B0E0E6', '#F0F8FF', '#FFFAFA']
        ];
        
        return $palettes[strtolower($season)] ?? $palettes['summer'];
    }

    /**
     * Convert HEX to RGB
     */
    private function hexToRgb($hex)
    {
        $hex = str_replace("#", "", $hex);
        
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        
        return [$r, $g, $b];
    }

    /**
     * Extract JSON from text
     */
    private function extractJson($text)
    {
        // Remove markdown code blocks if present
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            return $matches[0];
        }
        
        return $text;
    }
}