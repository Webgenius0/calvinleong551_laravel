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
     * Analyze skin tones, generate season theme WITH image
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

            // Step 4: Generate season theme WITH image
            $seasonData = $this->generateSeasonTheme($season, $brideData, $groomData, $bridePath, $groomPath);

           $combinedColors = $this->generateCombinedColors(
                $brideData['colors'] ?? [],
                $groomData['colors'] ?? [],
                $seasonData['palette'] ?? []
            );
            Log::info('Combined colors generated', ['count' => count($combinedColors)]);

            Log::info('SkinToneService Analysis Complete', [
                'bride_skin_tone' => $brideData['skin_tone'] ?? 'neutral',
                'groom_skin_tone' => $groomData['skin_tone'] ?? 'neutral',
                'season' => $seasonData['name'],
                'color_palettes_count' => count($allResponse),
                'season_image_generated' => !empty($seasonData['image']),
                'combined_colors_count' => count($combinedColors)
            ]);

            return [
                'success' => true,
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
                'all_responses' => $allResponse,
                'combined_colors' => $combinedColors
            ];

        } catch (\Exception $e) {
            Log::error('SkinToneService Analyze Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Analysis failed',
                'data' => $this->getFallbackResponse($season)
            ];
        }
    }


    /**
     * Generate combined colors from bride, groom, and season palettes using AI
     */
    private function generateCombinedColors(array $brideColors, array $groomColors, array $seasonColors): array
    {
        try {
            // Merge all colors first
            $allColors = array_unique(array_merge($brideColors, $groomColors, $seasonColors));

            // Use AI to suggest a refined 6-color combined palette
            $combinedPrompt = "Based on these wedding colors from bride ({$this->colorsToString($brideColors)}), groom ({$this->colorsToString($groomColors)}), and season ({$this->colorsToString($seasonColors)}), suggest a combined 6-color palette (hex codes) that harmonizes them for a cohesive wedding theme. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\", \"#96ceb4\", \"#ffeaa7\"]";

            $combinedResponse = $this->callSuggestionModel($combinedPrompt);
            $combined = $this->safeJsonDecode($combinedResponse ?? '[]', true);

            if (is_array($combined) && count($combined) >= 4) {
                Log::info('AI combined colors generated successfully', ['colors' => $combined]);
                return array_slice($combined, 0, 4); // Limit to 4
            }

            // Fallback: Unique merge of all colors, limited to 4
            Log::warning('Using fallback combined colors');
            return array_slice($allColors, 0, 4);

        } catch (\Exception $e) {
            Log::error('Error generating combined colors: ' . $e->getMessage());
            // Fallback: Simple merge
            $allColors = array_unique(array_merge($brideColors, $groomColors, $seasonColors));
            return array_slice($allColors, 0, 4);
        }
    }

    /**
     * Helper to convert colors array to string for prompt
     */
    private function colorsToString(array $colors): string
    {
        return implode(', ', $colors);
    }

    /**
     * Generate season theme data WITH image generation
     */
    private function generateSeasonTheme(string $season, array $brideData, array $groomData, string $bridePath, string $groomPath): array
    {
        // Generate season image with face integration
        $seasonImageBase64 = $this->generateSeasonImageWithFaces($season, $brideData, $groomData, $bridePath, $groomPath);

        // Generate palette (using suggestion model)
        $palettePrompt = $this->getSeasonPalettePrompt($season, $brideData, $groomData);
        $paletteResponse = $this->callSuggestionModel($palettePrompt);
        $palette = $this->safeJsonDecode($paletteResponse ?? '[]', true) ?: $this->getSeasonFallbackColors($season);

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
    private function generateSeasonImageWithFaces(string $season, array $brideData, array $groomData, string $bridePath, string $groomPath): ?string
    {
        try {
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
                        'temperature' => 0.6,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 8192
                    ]
                ]);

            Log::info("Season image API response status: " . $response->status());

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
                    Log::error("No inlineData in response");
                }
            } else {
                Log::error("Season image API failed", ['status' => $response->status()]);
            }

        } catch (\Exception $e) {
            Log::error('Error generating season image with faces: ' . $e->getMessage());
        }

        // Fallback: Generate without faces if editing fails
        return $this->generateSimpleSeasonImage($season, $brideData, $groomData);
    }

    /**
     * Simple season image generation without face integration
     */
    private function generateSimpleSeasonImage(string $season, array $brideData, array $groomData): ?string
    {
        try {
            Log::info("Trying simple season image generation for {$season}");

            $prompt = $this->getSimpleSeasonImagePrompt($season, $brideData, $groomData);

            $contents = [
                [
                    'parts' => [
                        ['text' => $prompt]
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
                            Log::info("Successfully generated simple season image");
                            return $part['inlineData']['data'];
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error generating simple season image: ' . $e->getMessage());
        }

        Log::error("All season image generation methods failed");
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
            'spring' => 'blossoming garden path with cherry blossoms',
            'summer' => 'sunny tropical beach at sunset with palm trees',
            'autumn' => 'golden autumn forest glade with falling leaves',
            'winter' => 'snowy winter wonderland with pine trees and lights',
            default => 'romantic seasonal landscape'
        };

        return "Using the provided bride and groom images, generate a photorealistic 1024x1024 PNG romantic wedding scene for {$season} season. Preserve the bride and groom's facial features, expressions, and hair exactly from the images. {$outfitChanges}. Incorporate skin tones: bride {$brideTone}, groom {$groomTone}. Use dominant colors: {$colors} in the scene accents. Scene: Couple in {$seasonDetails}, embracing joyfully. Composition: Faces in sharp focus foreground, dreamy bokeh background, golden hour lighting, ultra-detailed, emotional vibe. No text or distortions. Output base64 PNG image only.";
    }

    /**
     * Simple prompt for season image without face integration
     */
    private function getSimpleSeasonImagePrompt(string $season, array $brideData, array $groomData): string
    {
        $brideTone = $brideData['skin_tone'] ?? 'neutral';
        $groomTone = $groomData['skin_tone'] ?? 'neutral';
        $colors = implode(', ', array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []));

        $seasonDetails = match ($season) {
            'spring' => 'blossoming garden with cherry blossoms and pastel flowers',
            'summer' => 'tropical beach at sunset with palm trees and ocean waves',
            'autumn' => 'golden forest with falling leaves and cozy atmosphere',
            'winter' => 'snowy wonderland with pine trees and twinkling lights',
            default => 'romantic landscape'
        };

        return "Generate a photorealistic 1024x1024 PNG romantic wedding scene for {$season} season. A happy couple in wedding attire embracing in {$seasonDetails}. Incorporate colors: {$colors}. The couple has skin tones: bride {$brideTone}, groom {$groomTone}. Golden hour lighting, dreamy atmosphere, emotional and romantic vibe. No text or distortions. Output base64 PNG image only.";
    }

    // ... (keep all other methods the same, but ensure generateSeasonTheme now accepts image paths)

    /**
     * Safe JSON decode that handles both strings and arrays
     */
    private function safeJsonDecode($input, $assoc = false): mixed
    {
        if (is_array($input)) {
            return $input;
        }
        
        if (!is_string($input)) {
            $input = json_encode($input);
        }
        
        return json_decode($input, $assoc);
    }

    /**
     * Analyze single image for skin tone & colors
     */
    private function analyzeSingleImage(string $imagePath, string $type): array
    {
        try {
            $promptTemplate = $type === 'bride' ? $this->getBridePrompt() : $this->getGroomPrompt();
            
            // Check if file exists
            if (!file_exists($imagePath)) {
                throw new \Exception("Image file not found: {$imagePath}");
            }

            $mimeType = mime_content_type($imagePath);
            $imageContent = file_get_contents($imagePath);
            
            if ($imageContent === false) {
                throw new \Exception("Failed to read image file: {$imagePath}");
            }

            $base64Image = base64_encode($imageContent);

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
                'contents' => $contents,
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                $result = $this->safeJsonDecode($text, true);
                
                if (is_array($result) && isset($result['skin_tone']) && isset($result['colors'])) {
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
     * Generate 4 color palettes as objects with titles and descriptions
     */
    private function generateAllResponse(string $season, array $brideData, array $groomData): array
    {
        $palettes = [];
        $brideTone = $brideData['skin_tone'] ?? 'neutral';
        $groomTone = $groomData['skin_tone'] ?? 'neutral';
        
        try {
            for ($i = 1; $i <= 4; $i++) {
                // Generate unique title and description
                $palettePrompt = "Generate a beautiful, engaging wedding-themed title and romantic description for a {$season} season wedding color palette. Title: 5 words max. Description: 2-3 sentences. Respond ONLY in JSON: {\"title\": \"Elegant Summer Glow\", \"description\": \"This palette captures the sun-kissed romance of summer.\"}";
                
                $paletteResponse = $this->callSuggestionModel($palettePrompt);
                $paletteInfo = $this->safeJsonDecode($paletteResponse ?? '{}', true) ?: [
                    'title' => ucfirst($season) . " Wedding Bliss",
                    'description' => "This interactive palette invites you to envision a {$season} wedding filled with love."
                ];

                // Generate unique colors
                $colorPrompt = "Suggest 4 unique hex colors for a {$season} theme wedding palette. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\"]";
                $colorsResponse = $this->callSuggestionModel($colorPrompt);
                $colors = $this->safeJsonDecode($colorsResponse ?? '[]', true) ?: $this->getVariedFallbackColors($season, $i);

                $palettes[] = [
                    'title' => $paletteInfo['title'] ?? "Palette {$i} for " . ucfirst($season),
                    'description' => $paletteInfo['description'] ?? "A beautiful color palette for {$season} wedding.",
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

    private function getVariedFallbackColors(string $season, int $index): array
    {
        $base = $this->getSeasonFallbackColors($season);
        $variations = [
            1 => [$base[0], $base[1], '#FFE4B5', '#DDA0DD'],
            2 => [$base[0], $base[2], '#FF69B4', '#87CEEB'],
            3 => [$base[1], $base[3], '#FFD700', '#90EE90'],
            4 => [$base[2], $base[0], '#FF6F61', '#66CDAA'],
        ];
        return $variations[$index] ?? $base;
    }

    private function callSuggestionModel(string $prompt): ?string
    {
        try {
            $contents = [['parts' => [['text' => $prompt]]]];
            
            $response = Http::timeout(30)->post("{$this->baseUrl}/models/{$this->suggestionModel}:generateContent?key={$this->apiKey}", [
                'contents' => $contents,
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.7
                ]
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
        return [
            [
                'title' => ucfirst($season) . " Eternal Vows",
                'description' => "Ignite your love story with these vibrant hues that dance like summer sunsets.",
                'colors' => $this->getVariedFallbackColors($season, 1),
                'images' => []
            ],
            [
                'title' => ucfirst($season) . " Blissful Embrace", 
                'description' => "Wrap your special day in these soothing shades for an unforgettable celebration.",
                'colors' => $this->getVariedFallbackColors($season, 2),
                'images' => []
            ],
            [
                'title' => ucfirst($season) . " Radiant Promises",
                'description' => "Let these lively colors spark joy in every detail of your wedding.",
                'colors' => $this->getVariedFallbackColors($season, 3),
                'images' => []
            ],
            [
                'title' => ucfirst($season) . " Timeless Serenade",
                'description' => "Experience the magic of these elegant tones for a wedding that's truly yours.",
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

    private function getSeasonPalettePrompt(string $season, array $brideData, array $groomData): string
    {
        $colors = implode(', ', array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []));
        return "For a {$season} wedding theme, based on these colors {$colors}, suggest a 4-color palette with hex codes suitable for decor and outfits. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\"]";
    }

    private function getSeasonDescriptionPrompt(string $season, array $brideData, array $groomData): string
    {
        return "Write a short, romantic description (2-3 sentences) of a {$season} wedding theme, incorporating neutral skin tones and white colors for harmony. Make it inspiring and concise.";
    }
}