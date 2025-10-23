<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SkinToneService
{
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private $suggestionModel = 'gemini-2.5-flash';
    private $imageModel = 'gemini-2.5-flash-image';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        if (empty($this->apiKey)) {
            throw new \Exception('GEMINI_API_KEY not set in .env');
        }
    }

    /**
     * Analyze skin tones, generate season theme
     */
    public function analyzeSkinTone(string $bridePath, string $groomPath, string $season): array
    {
        try {
            Log::info('Starting skin tone analysis', [
                'bride_path' => $bridePath,
                'groom_path' => $groomPath,
                'season' => $season
            ]);

            // Step 1: Bride analysis
            $brideData = $this->analyzeSingleImage($bridePath, 'bride');
            Log::info('Bride analysis completed', $brideData);

            // Step 2: Groom analysis
            $groomData = $this->analyzeSingleImage($groomPath, 'groom');
            Log::info('Groom analysis completed', $groomData);

            // Step 3: Generate all responses (color palettes)
            $allResponse = $this->generateAllResponse($season, $brideData, $groomData);
            Log::info('All responses generated', ['count' => count($allResponse)]);

            // Step 4: Generate season data (without image for now to speed up)
            $seasonData = [
                'name' => ucfirst($season),
                'palette' => $this->getSeasonFallbackColors($season),
                'description' => $this->getSeasonDescription($season),
                'image' => null // Remove image generation for now to prevent timeout
            ];

            Log::info('SkinToneService Analysis Complete', [
                'bride_skin_tone' => $brideData['skin_tone'] ?? 'neutral',
                'groom_skin_tone' => $groomData['skin_tone'] ?? 'neutral',
                'season' => $seasonData['name'],
                'color_palettes_count' => count($allResponse)
            ]);

            return [
                'bride' => [
                    'skin_tone' => $brideData['skin_tone'] ?? 'neutral',
                    'color_code' => array_slice($brideData['colors'] ?? ['#ffffff'], 0, 4),
                    'matching_colors' => $this->generateMatchingColors($brideData['colors'] ?? [], $season)
                ],
                'groom' => [
                    'skin_tone' => $groomData['skin_tone'] ?? 'neutral',
                    'color_code' => array_slice($groomData['colors'] ?? ['#ffffff'], 0, 4),
                    'matching_colors' => $this->generateMatchingColors($groomData['colors'] ?? [], $season)
                ],
                'season' => $seasonData,
                'all_responses' => $allResponse
            ];

        } catch (\Exception $e) {
            Log::error('SkinToneService Analyze Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return proper fallback that matches expected structure
            return $this->getFallbackResponse($season);
        }
    }

    /**
     * Generate 4 color palettes as objects with titles and descriptions
     */
    private function generateAllResponse(string $season, array $brideData, array $groomData): array
    {
        $palettes = [];
        
        try {
            for ($i = 1; $i <= 4; $i++) {
                // Generate title and description in one call
                $palettePrompt = "Generate a beautiful title and description for wedding color palette #{$i} for {$season} season. Title: 5 words max. Description: 2 sentences. Respond ONLY in JSON: {\"title\": \"Elegant Summer Glow\", \"description\": \"This palette features warm tones...\"}";
                
                $paletteInfo = $this->callSuggestionModelWithFallback($palettePrompt, [
                    'title' => "Palette {$i} for " . ucfirst($season),
                    'description' => 'A harmonious color palette inspired by the season, perfect for wedding decor and outfits.'
                ]);

                // Generate colors
                $colorPrompt = "Suggest 4 hex colors for wedding palette #{$i} in {$season} theme. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\"]";
                $colors = $this->callSuggestionModelWithFallback($colorPrompt, $this->getSeasonFallbackColors($season), true);

                $palettes[] = [
                    'title' => $paletteInfo['title'],
                    'description' => $paletteInfo['description'],
                    'colors' => array_slice($colors, 0, 4),
                    'images' => []
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error generating color palettes: ' . $e->getMessage());
            // Return fallback palettes
            return $this->getFallbackColorPalettes($season);
        }

        return $palettes;
    }

    /**
     * Call suggestion model with fallback
     */
    private function callSuggestionModelWithFallback(string $prompt, $fallback, $isColors = false)
    {
        try {
            $response = $this->callSuggestionModel($prompt);
            if ($response) {
                $decoded = json_decode($response, true);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
        } catch (\Exception $e) {
            Log::error('API call failed, using fallback: ' . $e->getMessage());
        }
        
        return $fallback;
    }

    /**
     * Analyze single image for skin tone & colors
     */
    private function analyzeSingleImage(string $imagePath, string $type): array
    {
        try {
            $promptTemplate = $type === 'bride' ? $this->getBridePrompt() : $this->getGroomPrompt();
            
            $mimeType = mime_content_type($imagePath);
            $base64Image = base64_encode(file_get_contents($imagePath));

            $contents = [
                [
                    'parts' => [
                        ['text' => $promptTemplate],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ];

            $response = Http::timeout(60)->post("{$this->baseUrl}/models/{$this->suggestionModel}:generateContent?key={$this->apiKey}", [
                'contents' => $contents
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                $result = json_decode($text, true);
                
                if (is_array($result)) {
                    return $result;
                }
            }
            
            Log::warning("Image analysis failed for {$type}, using fallback");
            
        } catch (\Exception $e) {
            Log::error("Error analyzing {$type} image: " . $e->getMessage());
        }

        return [
            'skin_tone' => 'neutral', 
            'colors' => ['#ffffff', '#f0f0f0', '#e0e0e0', '#d0d0d0']
        ];
    }

    /**
     * Call suggestion model for text response
     */
    private function callSuggestionModel(string $prompt): ?string
    {
        try {
            $contents = [['parts' => [['text' => $prompt]]]];
            
            $response = Http::timeout(30)->post("{$this->baseUrl}/models/{$this->suggestionModel}:generateContent?key={$this->apiKey}", [
                'contents' => $contents
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }
            
            Log::warning('Suggestion model call failed', ['status' => $response->status()]);
            
        } catch (\Exception $e) {
            Log::error('Error calling suggestion model: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate matching colors
     */
    private function generateMatchingColors(array $colors, string $season): array
    {
        $complements = [
            'spring' => ['#98FB98', '#FFB6C1'],
            'summer' => ['#FFD700', '#87CEEB'],
            'autumn' => ['#D2691E', '#CD853F'],
            'winter' => ['#E0FFFF', '#B0E0E6'],
        ];
        return array_slice(array_merge($colors, $complements[$season] ?? ['#ffffff']), 0, 4);
    }

    /**
     * Fallback responses
     */
    private function getFallbackResponse(string $season): array
    {
        return [
            'bride' => [
                'skin_tone' => 'neutral',
                'color_code' => ['#ffffff', '#f0f0f0', '#e0e0e0', '#d0d0d0'],
                'matching_colors' => $this->generateMatchingColors([], $season)
            ],
            'groom' => [
                'skin_tone' => 'neutral',
                'color_code' => ['#ffffff', '#f0f0f0', '#e0e0e0', '#d0d0d0'],
                'matching_colors' => $this->generateMatchingColors([], $season)
            ],
            'season' => [
                'name' => ucfirst($season),
                'palette' => $this->getSeasonFallbackColors($season),
                'description' => $this->getSeasonDescription($season),
                'image' => null
            ],
            'all_responses' => $this->getFallbackColorPalettes($season)
        ];
    }

    private function getFallbackColorPalettes(string $season): array
    {
        $baseColors = $this->getSeasonFallbackColors($season);
        
        return [
            [
                'title' => "Classic {$season} Elegance",
                'description' => "A beautiful color palette inspired by {$season} season, perfect for wedding celebrations.",
                'colors' => $baseColors,
                'images' => []
            ],
            [
                'title' => "{$season} Harmony Collection",
                'description' => "Harmonious colors that capture the essence of {$season} for your special day.",
                'colors' => array_reverse($baseColors),
                'images' => []
            ],
            [
                'title' => "Modern {$season} Bliss",
                'description' => "Contemporary color combinations that reflect the beauty of {$season}.",
                'colors' => array_slice($baseColors, 2, 2) + array_slice($baseColors, 0, 2),
                'images' => []
            ],
            [
                'title' => "{$season} Romance Palette",
                'description' => "Romantic colors that create the perfect ambiance for a {$season} wedding.",
                'colors' => $baseColors,
                'images' => []
            ]
        ];
    }

    private function getSeasonFallbackColors(string $season): array
    {
        $fallbacks = [
            'spring' => ['#98FB98', '#FFB6C1', '#F0E68C', '#DDA0DD'],
            'summer' => ['#FFD700', '#87CEEB', '#90EE90', '#FF69B4'],
            'autumn' => ['#D2691E', '#CD853F', '#DEB887', '#8B4513'],
            'winter' => ['#E0FFFF', '#B0E0E6', '#F8F8FF', '#DCDCDC'],
        ];
        return $fallbacks[$season] ?? ['#ffffff', '#f0f0f0', '#e0e0e0', '#d0d0d0'];
    }

    private function getSeasonDescription(string $season): string
    {
        $descriptions = [
            'spring' => 'Spring weddings bloom with fresh flowers and soft pastels, symbolizing new beginnings and joyful renewal.',
            'summer' => 'Summer celebrations shine with vibrant energy, beachside vows, and sun-kissed memories under endless blue skies.',
            'autumn' => 'Autumn nuptials embrace cozy warmth, golden foliage, and harvest hues for a timeless, earthy romance.',
            'winter' => 'Winter unions sparkle with elegant whites, twinkling lights, and heartfelt toasts amid a magical snowy embrace.'
        ];
        return $descriptions[$season] ?? 'A beautiful seasonal wedding theme.';
    }

    private function getBridePrompt(): string
    {
        return "Analyze this image of a bride. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 4 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"warm\", \"colors\": [\"#ffcc99\", \"#ffffff\", \"#d4a574\", \"#f0f0f0\"]}";
    }

    private function getGroomPrompt(): string
    {
        return "Analyze this image of a groom. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 4 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"cool\", \"colors\": [\"#a8e6cf\", \"#000000\", \"#4a90e2\", \"#e0e0e0\"]}";
    }

    // Remove all image generation methods for now to prevent timeout
}