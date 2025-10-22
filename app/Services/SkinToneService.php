<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SkinToneService
{
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private $suggestionModel = 'gemini-2.5-flash';  // For analysis & suggestions
    private $imageModel = 'gemini-2.5-flash-image';  // Nano Banana for image gen

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        if (empty($this->apiKey)) {
            throw new \Exception('GEMINI_API_KEY not set in .env');
        }
    }

    /**
     * Analyze skin tones, generate season theme (edited images removed).
     */
    public function analyzeSkinTone(string $bridePath, string $groomPath, string $season): array
    {
        try {
            // Step 1: Bride analysis
            $brideData = $this->analyzeSingleImage($bridePath, 'bride');

            // Step 2: Groom analysis
            $groomData = $this->analyzeSingleImage($groomPath, 'groom');

            // Step 3: Generate season theme image, palette, description
            $seasonData = $this->generateSeasonTheme($season, $brideData, $groomData, $bridePath, $groomPath);

            Log::info('SkinToneService Analysis Complete', [
                'bride' => ['skin_tone' => $brideData['skin_tone'] ?? 'neutral', 'colors' => $brideData['colors'] ?? []],
                'groom' => ['skin_tone' => $groomData['skin_tone'] ?? 'neutral', 'colors' => $groomData['colors'] ?? []],
                'season' => [
                    'image' => $seasonData['image'] ? 'generated' : null,
                    'palette' => $seasonData['palette'] ?? [],
                    'description' => substr($seasonData['description'] ?? '', 0, 100) . '...'
                ]
            ]);

            return [
                'bride' => [
                    'skin_tone' => $brideData['skin_tone'] ?? 'neutral',
                    'color_code' => array_slice($brideData['colors'] ?? ['#ffffff'], 0, 4),  // Limit to 4 colors
                    'matching_colors' => $this->generateMatchingColors($brideData['colors'] ?? [], $season)
                ],
                'groom' => [
                    'skin_tone' => $groomData['skin_tone'] ?? 'neutral',
                    'color_code' => array_slice($groomData['colors'] ?? ['#ffffff'], 0, 4),  // Limit to 4 colors
                    'matching_colors' => $this->generateMatchingColors($groomData['colors'] ?? [], $season)
                ],
                'season' => [
                    'name' => ucfirst($season),
                    'palette' => array_slice($seasonData['palette'] ?? [], 0, 4),  // Limit to 4 colors
                    'description' => $seasonData['description'] ?? $this->getSeasonDescription($season),
                    'image' => $seasonData['image'] ? "data:image/png;base64," . $seasonData['image'] : null
                ]
            ];
        } catch (\Exception $e) {
            Log::error('SkinToneService Analyze Error: ' . $e->getMessage());
            return [
                'bride' => ['skin_tone' => 'neutral', 'color_code' => ['#ffffff', '#f0f0f0', '#e0e0e0', '#d0d0d0'], 'matching_colors' => []],
                'groom' => ['skin_tone' => 'neutral', 'color_code' => ['#ffffff', '#f0f0f0', '#e0e0e0', '#d0d0d0'], 'matching_colors' => []],
                'season' => ['name' => ucfirst($season), 'palette' => ['#FFD700', '#87CEEB', '#98FB98', '#FFB6C1'], 'description' => '', 'image' => null]
            ];
        }
    }

    /**
     * Test Gemini & Nano Banana models.
     */
    public function testImageGeneration(): array
    {
        try {
            // Test suggestion model
            $suggestionTest = $this->testModel($this->suggestionModel, 'Test: Describe a summer wedding in one sentence.');

            // Test image model
            $imageTest = $this->testModel($this->imageModel, 'Generate a simple test image: A red rose on white background.', true);

            return [
                'suggestion_model' => $this->suggestionModel,
                'suggestion_test' => $suggestionTest,
                'image_model' => $this->imageModel,
                'image_test' => $imageTest
            ];
        } catch (\Exception $e) {
            Log::error('SkinToneService Test Error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ==============================
    // Private Methods
    // ==============================

    /**
     * Analyze single image for skin tone & colors.
     */
    private function analyzeSingleImage(string $imagePath, string $type): array
    {
        $promptTemplate = $type === 'bride' ? $this->getBridePrompt() : $this->getGroomPrompt();
        $prompt = str_replace('{type}', $type, $promptTemplate);

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

        $response = Http::post("{$this->baseUrl}/models/{$this->suggestionModel}:generateContent?key={$this->apiKey}", [
            'contents' => $contents
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            return json_decode($text, true) ?: ['skin_tone' => 'neutral', 'colors' => ['#ffffff']];
        }

        return ['skin_tone' => 'neutral', 'colors' => ['#ffffff']];
    }

    /**
     * Generate season theme data (image, palette, description).
     */
    private function generateSeasonTheme(string $season, array $brideData, array $groomData, string $bridePath, string $groomPath): array
    {
        // Generate season image with face integration
        $seasonImageBase64 = $this->generateSeasonImage($season, $brideData, $groomData, $bridePath, $groomPath);

        // Generate palette (using suggestion model)
        $palettePrompt = $this->getSeasonPalettePrompt($season, $brideData, $groomData);
        $paletteResponse = $this->callSuggestionModel($palettePrompt);
        $palette = json_decode($paletteResponse ?? '[]', true) ?: [];

        // Description
        $descriptionPrompt = $this->getSeasonDescriptionPrompt($season, $brideData, $groomData);
        $descriptionResponse = $this->callSuggestionModel($descriptionPrompt);

        return [
            'image' => $seasonImageBase64,
            'palette' => $palette,
            'description' => trim($descriptionResponse ?? $this->getSeasonDescription($season))
        ];
    }

    /**
     * Generate matching colors (simple logic or Gemini).
     */
    private function generateMatchingColors(array $colors, string $season): array
    {
        // Simple fallback: Add season-based complements
        $complements = [
            'spring' => ['#98FB98', '#FFB6C1'],  // Pastel green, pink
            'summer' => ['#FFD700', '#87CEEB'],  // Gold, sky blue
            'autumn' => ['#D2691E', '#CD853F'],  // Chocolate, peru
            'winter' => ['#E0FFFF', '#B0E0E6'],  // Light cyan, powder blue
        ];
        return array_slice(array_merge($colors, $complements[$season] ?? ['#ffffff']), 0, 4);  // Limit to 4 colors
    }

    /**
     * Test single model.
     */
    private function testModel(string $model, string $prompt, bool $isImage = false): array
    {
        $contents = [['parts' => [['text' => $prompt]]]];
        $config = $isImage ? ['generationConfig' => ['response_modalities' => ['TEXT', 'IMAGE']]] : [];

        $response = Http::post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", array_merge([
            'contents' => $contents
        ], $config));

        return [
            'status' => $response->successful() ? 'success' : 'failed',
            'response' => $response->successful() ? $response->json() : $response->body()
        ];
    }

    /**
     * Call suggestion model for text response.
     */
    private function callSuggestionModel(string $prompt): ?string
    {
        $contents = [['parts' => [['text' => $prompt]]]];
        $response = Http::post("{$this->baseUrl}/models/{$this->suggestionModel}:generateContent?key={$this->apiKey}", [
            'contents' => $contents
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        return null;
    }

    // ==============================
    // Prompt Templates
    // ==============================

    private function getBridePrompt(): string
    {
        return "Analyze this image of a bride. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 4 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"warm\", \"colors\": [\"#ffcc99\", \"#ffffff\", \"#d4a574\", \"#f0f0f0\"]}";
    }

    private function getGroomPrompt(): string
    {
        return "Analyze this image of a groom. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 4 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"cool\", \"colors\": [\"#a8e6cf\", \"#000000\", \"#4a90e2\", \"#e0e0e0\"]}";
    }

    /**
     * Prompt for season image with bride/groom faces and outfit changes.
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

    /**
     * Revised season image generation: Integrates bride/groom faces with outfit changes for season theme.
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
        return $this->generateFallbackSeasonImage($season, $brideData, $groomData);
    }

    /**
     * Fallback season image generation with simpler prompt.
     */
    private function generateFallbackSeasonImage(string $season, array $brideData, array $groomData): ?string
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
            ->post("{$this->baseUrl}/models/{$this->suggestionModel}:generateContent?key={$this->apiKey}", [
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

    private function getSeasonPalettePrompt(string $season, array $brideData, array $groomData): string
    {
        $colors = implode(', ', array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []));

        return "For a {$season} wedding theme, based on these colors {$colors}, suggest a 4-color palette with hex codes suitable for decor and outfits. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\"]";
    }

    private function getSeasonDescriptionPrompt(string $season, array $brideData, array $groomData): string
    {
        return "Write a short, romantic description (2-3 sentences) of a {$season} wedding theme, incorporating neutral skin tones and white colors for harmony. Make it inspiring and concise.";
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

    // all response 

    public function generateAllVariantImages(string $apiKey, string $season, string $groomImageUrl, string $brideImageUrl): array
    {
        $results = [];

        try {
            // 🧠 Step 1: Get wedding color palette suggestion
            $prompt = "Analyze these bride and groom skin tones and suggest a {$season}-themed wedding color palette, outfit ideas, and decoration style.";
            $suggestion = $this->generateText($apiKey, $prompt);
            $results['suggestion'] = $suggestion;

            // 🖼 Step 2: Download both input images
            $inlineGroom = $this->getInlineDataFromImage($groomImageUrl);
            $inlineBride = $this->getInlineDataFromImage($brideImageUrl);

            // 🧍‍♂️ Groom Image Generation
            $groomPrompt = "Generate a realistic groom wearing a {$season}-themed wedding outfit based on this reference.";
            $groomImage = $this->generateImageWithPrompt($apiKey, $this->imageModel, $groomPrompt, [$inlineGroom]);
            $results['groom_styled'] = $groomImage ? $this->saveBase64ToPublic($groomImage, 'groom_' . $season, 'groom') : null;

            // 👰 Bride Image Generation
            $bridePrompt = "Generate a realistic bride wearing a {$season}-themed wedding dress based on this reference.";
            $brideImage = $this->generateImageWithPrompt($apiKey, $this->imageModel, $bridePrompt, [$inlineBride]);
            $results['bride_styled'] = $brideImage ? $this->saveBase64ToPublic($brideImage, 'bride_' . $season, 'bride') : null;

            // 💞 Couple Image Generation
            $couplePrompt = "Generate a realistic wedding photo of the bride and groom together with a {$season}-themed background.";
            $coupleImage = $this->generateImageWithPrompt($apiKey, $this->imageModel, $couplePrompt, [$inlineGroom, $inlineBride]);
            $results['couple_styled'] = $coupleImage ? $this->saveBase64ToPublic($coupleImage, 'couple_' . $season, 'couple') : null;
        } catch (Exception $e) {
            Log::error('AI Wedding Service Error: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }


    /**
     * ✨ Generate text response (suggestions)
     */
    private function generateText(string $apiKey, string $prompt): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->suggestionModel}:generateContent", [
                    'contents' => [[
                        'parts' => [['text' => $prompt]],
                    ]],
                ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text');
            }

            Log::error('Text generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Exception $e) {
            Log::error('Text generation exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * 🖼 Generate image based on a prompt and inline images
     */
    private function generateImageWithPrompt(string $apiKey, string $model, string $prompt, array $inlineImages): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [[
                        'parts' => array_merge(
                            [['text' => $prompt]],
                            array_map(fn($img) => ['inlineData' => $img], $inlineImages)
                        ),
                    ]],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? null;
            }

            Log::error('Gemini image generation failed', [
                'model' => $model,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
        } catch (Exception $e) {
            Log::error('Image generation exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * 📥 Convert image URL → base64 inlineData
     */
    private function getInlineDataFromImage(string $url): ?array
    {
        try {
            $imageData = file_get_contents($url);
            $mimeType = mime_content_type($url);

            if ($imageData) {
                return [
                    'mimeType' => $mimeType,
                    'data' => base64_encode($imageData),
                ];
            }
        } catch (Exception $e) {
            Log::error('Failed to read image from URL: ' . $url);
        }

        return null;
    }

    /**
     * 💾 Save base64 image to /public/images/weddings
     */
    private function saveBase64ToPublic(string $base64Data, string $name): ?string
    {
        try {
            $folder = public_path('images/weddings');
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $filePath = "{$folder}/{$name}.jpg";
            file_put_contents($filePath, base64_decode($base64Data));

            return asset("uploads/images/weddings/{$name}.jpg");
        } catch (Exception $e) {
            Log::error('Failed to save image to public path: ' . $e->getMessage());
        }

        return null;
    }
}
