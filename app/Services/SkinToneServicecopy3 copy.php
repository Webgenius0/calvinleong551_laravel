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

            // Step 4: Generate season theme image, palette, description
            $seasonData = $this->generateSeasonTheme($season, $brideData, $groomData, $bridePath, $groomPath);

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
     * Generate season theme data (image, palette, description)
     */
    private function generateSeasonTheme(string $season, array $brideData, array $groomData, string $bridePath, string $groomPath): array
    {
        // Generate season image with face integration
        $seasonImageBase64 = $this->generateSeasonImage($season, $brideData, $groomData, $bridePath, $groomPath);

        // Generate palette (using suggestion model)
        $palettePrompt = $this->getSeasonPalettePrompt($season, $brideData, $groomData);
        $paletteResponse = $this->callSuggestionModel($palettePrompt);
        $palette = json_decode($paletteResponse ?? '[]', true) ?: $this->getSeasonFallbackColors($season);

        // Description
        $descriptionPrompt = $this->getSeasonDescriptionPrompt($season, $brideData, $groomData);
        $descriptionResponse = $this->callSuggestionModel($descriptionPrompt);

        return [
            'name' => ucfirst($season),
            'image' => $seasonImageBase64 ? "data:image/png;base64," . $seasonImageBase64 : null,
            'palette' => array_slice($palette, 0, 4),
            'description' => trim($descriptionResponse ?? $this->getSeasonDescription($season))
        ];
    }

    /**
     * Generate season image with bride/groom faces and outfit changes
     */
    private function generateSeasonImage(string $season, array $brideData, array $groomData, string $bridePath, string $groomPath): ?string
    {
        Log::info("Starting season image generation for {$season} with face integration", [
            'bride_tone' => $brideData['skin_tone'] ?? 'neutral',
            'groom_tone' => $groomData['skin_tone'] ?? 'neutral',
            'colors' => array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? [])
        ]);

        $prompt = $this->getSeasonImagePromptWithFaces($season, $brideData, $groomData);

        // Encode images for inline_data
        $brideMimeType = mime_content_type($bridePath);
        $groomMimeType = mime_content_type($groomPath);
        $brideBase64 = base64_encode(file_get_contents($bridePath));
        $groomBase64 = base64_encode(file_get_contents($groomPath));

        // Contents with multiple parts: text prompt + bride image + groom image
        $contents = [
            [
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inlineData' => [
                            'mimeType' => $brideMimeType,
                            'data' => $brideBase64
                        ]
                    ],
                    [
                        'inlineData' => [
                            'mimeType' => $groomMimeType,
                            'data' => $groomBase64
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json'
            ])
            ->post("{$this->baseUrl}/models/{$this->imageModel}:generateContent?key={$this->apiKey}", [
                'contents' => $contents,
                'generationConfig' => [
                    'response_modalities' => ['TEXT', 'IMAGE'],
                    'temperature' => 0.6,  // Slightly lower for consistent editing
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 8192
                ]
            ]);

        Log::info("Season image API response status: " . $response->status(), [
            'body_preview' => substr($response->body(), 0, 500)
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['candidates'][0]['content']['parts'])) {
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['inlineData']['data'])) {
                        $imageData = $part['inlineData']['data'];
                        Log::info("Successfully generated season image with faces", ['length' => strlen($imageData)]);
                        return $imageData;
                    }
                }
                Log::error("No inlineData in response", ['response' => $data]);
            }
        } else {
            Log::error("Season image API failed", ['status' => $response->status(), 'body' => $response->body()]);
        }

        // Fallback: Generate without faces if editing fails
        return $this->generateFallbackSeasonImage($season);
    }

    /**
     * Fallback season image generation with simpler prompt
     */
    private function generateFallbackSeasonImage(string $season): ?string
    {
        Log::info("Trying fallback season image generation for {$season}");

        $fallbackPrompt = "Generate a simple romantic wedding scene for {$season} season: Sunny beach with couple silhouette, photorealistic PNG 1024x1024, base64 only.";

        $contents = [
            [
                'parts' => [
                    ['text' => $fallbackPrompt]
                ]
            ]
        ];

        $response = Http::timeout(60)
            ->post("{$this->baseUrl}/models/{$this->imageModel}:generateContent?key={$this->apiKey}", [
                'contents' => $contents,
                'generationConfig' => [
                    'response_modalities' => ['TEXT', 'IMAGE'],
                    'temperature' => 0.5
                ]
            ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['candidates'][0]['content']['parts'])) {
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['inlineData']['data'])) {
                        return $part['inlineData']['data'];
                    }
                }
            }
        }

        Log::error("Fallback season image also failed");
        return null;
    }

    /**
     * Prompt for season image with bride/groom faces and outfit changes
     */
    private function getSeasonImagePromptWithFaces(string $season, array $brideData, array $groomData): string
    {
        $brideTone = $brideData['skin_tone'] ?? 'neutral';
        $groomTone = $groomData['skin_tone'] ?? 'neutral';
        $colors = implode(', ', array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []));

        // Season-specific outfit changes
        $outfitChanges = match ($season) {
            'spring' => 'Change outfits to light pastel dresses and suits with floral patterns, add spring flowers',
            'summer' => 'Change outfits to lightweight linen summer wear with tropical prints, add beach accessories like leis',
            'autumn' => 'Change outfits to cozy knit sweaters and velvet accents in earthy tones, add leaf motifs',
            'winter' => 'Change outfits to elegant wool coats and scarves with fur trim, add evergreen details',
            default => 'Adapt outfits to seasonal theme with harmonious colors'
        };

        $seasonDetails = match ($season) {
            'spring' => 'blossoming garden path',
            'summer' => 'sunny tropical beach at sunset',
            'autumn' => 'golden autumn forest glade',
            'winter' => 'snowy winter wonderland with lights',
            default => 'romantic seasonal landscape'
        };

        return "Using the provided bride and groom images, generate a photorealistic 1024x1024 PNG romantic wedding scene for {$season} season in Nano Banana style. Preserve the bride and groom's facial features, expressions, and hair exactly from the images. {$outfitChanges}. Incorporate skin tones: bride {$brideTone}, groom {$groomTone}. Use dominant colors: {$colors} in the scene accents. Scene: Couple in {$seasonDetails}, embracing joyfully. Composition: Faces in sharp focus foreground, dreamy bokeh background, golden hour lighting, ultra-detailed, emotional vibe. No text or distortions. Output base64 PNG image only.";
    }

    private function getSeasonPalettePrompt(string $season, array $brideData, array $groomData): string
    {
        $colors = implode(', ', array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []));

        return "For a {$season} wedding theme, based on these colors {$colors}, suggest a 4-color palette with hex codes suitable for decor and outfits. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\"]";
    }

    private function getSeasonDescriptionPrompt(string $season, array $brideData, array $groomData): string
    {
        return "Write a short, romantic description (2-3 sentences) of a {$season} wedding theme, incorporating neutral skin tones and white colors for harmony. Make it inspiring and concise.";
    }

    /**
     * Generate 4 color palettes as objects with titles and descriptions
     */
    private function generateAllResponse(string $season, array $brideData, array $groomData): array
    {
        $palettes = [];
        $brideTone = $brideData['skin_tone'] ?? 'neutral';
        $groomTone = $groomData['skin_tone'] ?? 'neutral';
        $existingColors = array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []);
        
        try {
            for ($i = 1; $i <= 4; $i++) {
                // Generate unique title and description in one call - weddingly interactive
                $palettePrompt = "Generate a beautiful, engaging wedding-themed title and romantic description for a {$season} season wedding color palette. Incorporate bride skin tone ({$brideTone}) and groom skin tone ({$groomTone}) for harmony. Title: 5 words max, interactive and inviting like 'Ignite Your Summer Vows'. Description: 2-3 sentences, wedding-focused, inspiring for outfits, decor, and emotional moments. Respond ONLY in JSON: {\"title\": \"Elegant Summer Glow\", \"description\": \"This palette captures the sun-kissed romance of summer, where soft peaches and sky blues embrace the couple's neutral tones, creating a canvas for vows that echo eternal warmth and joy.\"}";
                
                $paletteInfo = $this->callSuggestionModelWithFallback($palettePrompt, [
                    'title' => ucfirst($season) . " Wedding Bliss",
                    'description' => "This interactive palette invites you to envision a {$season} wedding filled with love, where colors harmonize with the couple's skin tones for a personalized, romantic celebration."
                ]);

                // Generate unique colors adjusted for bride, groom, season
                $colorPrompt = "Suggest 4 unique hex colors for a {$season} theme wedding palette, adjusted for bride skin tone {$brideTone}, groom {$groomTone}, and harmonious with these existing colors: " . implode(', ', $existingColors) . ". Vary from previous palettes for diversity. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\"]";
                $colors = $this->callSuggestionModelWithFallback($colorPrompt, $this->getVariedFallbackColors($season, $i), true);

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
     * Get varied fallback colors for each palette index
     */
    private function getVariedFallbackColors(string $season, int $index): array
    {
        $base = $this->getSeasonFallbackColors($season);
        $variations = [
            1 => [$base[0], $base[1], '#FFE4B5', '#DDA0DD'], // Variation 1
            2 => [$base[0], $base[2], '#FF69B4', '#87CEEB'], // Variation 2
            3 => [$base[1], $base[3], '#FFD700', '#90EE90'], // Variation 3
            4 => [$base[2], $base[0], '#FF6F61', '#66CDAA'], // Variation 4
        ];
        return $variations[$index] ?? $base;
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
                if ($decoded !== null && ($isColors ? is_array($decoded) : true)) {
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
                'title' => ucfirst($season) . " Eternal Vows",
                'description' => "Ignite your love story with these vibrant hues that dance like summer sunsets, perfectly complementing neutral skin tones for a ceremony full of warmth and wonder.",
                'colors' => $this->getVariedFallbackColors($season, 1),
                'images' => []
            ],
            [
                'title' => ucfirst($season) . " Blissful Embrace",
                'description' => "Wrap your special day in these soothing shades, where ocean blues meet golden sands, creating an interactive canvas for personalized vows and unforgettable moments.",
                'colors' => $this->getVariedFallbackColors($season, 2),
                'images' => []
            ],
            [
                'title' => ucfirst($season) . " Radiant Promises",
                'description' => "Let these lively colors spark joy in every detail, from floral arches to bridal bouquets, harmonizing with your couple's essence for a wedding that's truly yours.",
                'colors' => $this->getVariedFallbackColors($season, 3),
                'images' => []
            ],
            [
                'title' => ucfirst($season) . " Timeless Serenade",
                'description' => "Experience the magic of these elegant tones, evoking lazy afternoons and starry nights, inviting guests to celebrate your union in style and serenity.",
                'colors' => $this->getVariedFallbackColors($season, 4),
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
}