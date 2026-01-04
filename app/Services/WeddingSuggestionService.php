<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Log;

class WeddingSuggestionService
{
    private $apiKey;
    private $client;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->client = new Client();
    }

    /**
     * Generate complete wedding suggestions with professional AI
     */
    public function generateWeddingSuggestions($bridePhoto, $groomPhoto, $season)
    {
        try {
            // 1️⃣ Upload original images to uploads directory
            $bridePath = Helper::fileUpload($bridePhoto, 'uploads/wedding-images', $this->getFileName($bridePhoto));
            $groomPath = Helper::fileUpload($groomPhoto, 'uploads/wedding-images', $this->getFileName($groomPhoto));

            // 2️⃣ Analyze skin tones using AI
            $brideAnalysis = $this->analyzeSkinToneWithAI(public_path($bridePath));
            $groomAnalysis = $this->analyzeSkinToneWithAI(public_path($groomPath));

            // 3️⃣ Generate professional groom and bride images with AI
            $professionalImages = $this->generateProfessionalImagesWithAI(
                $bridePath,
                $groomPath,
                $season,
                $brideAnalysis,
                $groomAnalysis
            );

            // 4️⃣ Get season image & color combinations
            $seasonData = $this->getSeasonImageAndCombination($season, $bridePath, $groomPath, $brideAnalysis, $groomAnalysis);

            // 5️⃣ Generate multiple color palettes with AI
            $colorPalettes = $this->generateMultipleColorPalettesWithAI(
                $brideAnalysis['skin_tone'],
                $groomAnalysis['skin_tone'],
                $season
            );

            // 6️⃣ Generate palette suggestions with AI images
            $weddingPaletteSuggestions = $this->generatePaletteSuggestionsWithAI(
                $colorPalettes,
                $season,
                $professionalImages['enhanced_bride'],
                $professionalImages['enhanced_groom'],
                $brideAnalysis,
                $groomAnalysis
            );

            return [
                'success' => true,
                'data' => [
                    'skin_tone_analysis' => [
                        'bride' => array_merge($brideAnalysis, [
                            'original_image' => $bridePath,
                            'enhanced_image' => $professionalImages['enhanced_bride'],
                            'color_codes' => $professionalImages['bride_colors']
                        ]),
                        'groom' => array_merge($groomAnalysis, [
                            'original_image' => $groomPath,
                            'enhanced_image' => $professionalImages['enhanced_groom'],
                            'color_codes' => $professionalImages['groom_colors']
                        ]),
                        'season_colors' => $this->getSeasonColors($season),
                        'season_image' => $seasonData['season_image'],
                        'combination' => $seasonData['combination'],
                    ],
                    'wedding_palette_suggestions' => $weddingPaletteSuggestions
                ]
            ];
        } catch (Exception $e) {
            if (isset($bridePath)) Helper::fileDelete(public_path($bridePath));
            if (isset($groomPath)) Helper::fileDelete(public_path($groomPath));

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate professional groom and bride images with AI enhancement
     */
    private function generateProfessionalImagesWithAI($bridePath, $groomPath, $season, $brideAnalysis, $groomAnalysis)
    {
        // Generate enhanced groom image with professional styling
        $groomPrompt = "Create a professional individual wedding portrait of this groom standing elegantly with:
        - Original face features preserved
        - Professional wedding attire matching {$season} season
        - Colors that complement skin tone: {$groomAnalysis['skin_tone']}
        - Background matching {$season} theme
        - Enhanced but realistic appearance
        - Confident and joyful pose, no physical contact with anyone
        - Wedding-appropriate styling";

        $enhancedGroom = $this->generateAIImage($groomPrompt, $groomPath);

        // Generate enhanced bride image with professional styling
        $bridePrompt = "Create a professional individual wedding portrait of this bride standing elegantly with:
        - Original face features preserved
        - Professional wedding attire matching {$season} season
        - Colors that complement skin tone: {$brideAnalysis['skin_tone']}
        - Background matching {$season} theme
        - Enhanced but realistic appearance
        - Graceful and joyful pose, no physical contact with anyone
        - Wedding-appropriate styling and makeup";

        $enhancedBride = $this->generateAIImage($bridePrompt, $bridePath);

        // Generate color recommendations for groom and bride
        $groomColors = $this->generateIndividualColorPalette($groomAnalysis['skin_tone'], $season, 'groom');
        $brideColors = $this->generateIndividualColorPalette($brideAnalysis['skin_tone'], $season, 'bride');

        return [
            'enhanced_groom' => $enhancedGroom,
            'enhanced_bride' => $enhancedBride,
            'groom_colors' => $groomColors,
            'bride_colors' => $brideColors
        ];
    }

    /**
     * Generate individual color palette for groom/bride
     */
    private function generateIndividualColorPalette($skinTone, $season, $role)
    {
        $prompt = "Generate 4 professional wedding color codes for {$role} with:
        - Skin tone: {$skinTone}
        - Season: {$season}
        - Role: {$role}
        
        Return JSON format:
        [
            {'color': 'hex1', 'name': 'color name 1', 'usage': 'specific usage'},
            {'color': 'hex2', 'name': 'color name 2', 'usage': 'specific usage'},
            {'color': 'hex3', 'name': 'color name 3', 'usage': 'specific usage'},
            {'color': 'hex4', 'name': 'color name 4', 'usage': 'specific usage'}
        ]";

        $response = $this->callGeminiAPI($prompt);

        if ($response) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && count($data) === 4) {
                return $data;
            }
        }

        // Fallback colors
        return $this->getFallbackColors($skinTone, $season, $role);
    }

    /**
     * Generate multiple color palettes with AI
     */
    private function generateMultipleColorPalettesWithAI($brideSkinTone, $groomSkinTone, $season)
    {
        $palettes = [];

        for ($i = 1; $i <= 3; $i++) {
            $prompt = "Create wedding color palette combination {$i} for {$season} that works for:
            - Bride skin tone: {$brideSkinTone}
            - Groom skin tone: {$groomSkinTone}
            - Should include attire, decoration, and accessory colors
            
            Return exactly 4 colors in JSON:
            [
                {'color': 'hex1', 'name': 'color name 1', 'usage': 'specific wedding element'},
                {'color': 'hex2', 'name': 'color name 2', 'usage': 'specific wedding element'},
                {'color': 'hex3', 'name': 'color name 3', 'usage': 'specific wedding element'},
                {'color': 'hex4', 'name': 'color name 4', 'usage': 'specific wedding element'}
            ]";

            $response = $this->callGeminiAPI($prompt);

            if ($response) {
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && count($data) === 4) {
                    $palettes[] = $data;
                    continue;
                }
            }

            // Fallback to predefined palette if AI fails
            $palettes[] = $this->generateSinglePalette($brideSkinTone, $groomSkinTone, $season, $i);
        }

        return $palettes;
    }

    /**
     * Generate palette suggestions with AI images
     */
    private function generatePaletteSuggestionsWithAI($colorPalettes, $season, $bridePath, $groomPath, $brideAnalysis, $groomAnalysis)
    {
        $suggestions = [];

        foreach ($colorPalettes as $i => $palette) {
            $paletteNumber = $i + 1;

            // Generate AI descriptions for this palette
            $paletteInfo = $this->generatePaletteInfoWithAI($palette, $season, $paletteNumber);

            // Generate AI images for this palette combination
            $combinationImages = $this->generateCombinationImagesWithAI(
                $bridePath,
                $groomPath,
                $palette,
                $season,
                $paletteNumber,
                $brideAnalysis,
                $groomAnalysis
            );

            $suggestions[] = [
                'title' => $paletteInfo['title'],
                'description' => $paletteInfo['description'],
                'color_codes' => $palette,
                'combination_details' => [
                    'bride_groom_together' => [
                        'bride_image' => $combinationImages['couple_image'],
                        'groom_image' => $groomPath, // original groom
                        'combination_colors' => array_column($palette, 'color'),
                        'color_explanation' => $paletteInfo['color_explanation']
                    ],
                    'bridesmaids' => [$combinationImages['bridesmaids_group']],
                    'groomsmen' => [$combinationImages['groomsmen_group']],
                    'event_preview' => [$combinationImages['event_decor']],
                ]
            ];
        }

        return $suggestions;
    }
    /**
     * Generate combination images with AI
     */
    private function generateCombinationImagesWithAI($bridePath, $groomPath, $palette, $season, $paletteNumber, $brideAnalysis, $groomAnalysis)
    {
        $colorNames = array_column($palette, 'name');
        $colors = implode(', ', array_column($palette, 'color'));

        // 1. Couple image (bride and groom together)
        $couplePrompt = "Create a realistic professional wedding couple portrait:
        - Bride and groom standing side by side or nearby in {$season} setting, looking at each other or the camera with loving joyful expressions
        - No hugging, kissing, embracing, or any physical contact
        - Using color palette: {$colors}
        - Bride skin tone: {$brideAnalysis['skin_tone']}
        - Groom skin tone: {$groomAnalysis['skin_tone']}
        - Modest, elegant, and romantic poses with personal space
        - Professional wedding photography style
        - Natural and joyful vibe";

        $coupleImage = $this->generateAIImage($couplePrompt);

        // 2. Groomsmen group image
        $groomsmenPrompt = "Create a realistic groomsmen group photo:
        - Groom with 3-4 friends standing together in wedding attire, no physical contact like arms around shoulders
        - Using color palette: {$colors}
        - Groom's original face features
        - Friends with diverse appearances
        - {$season} background theme
        - Professional group photography, elegant and celebratory poses";

        $groomsmenImage = $this->generateAIImage($groomsmenPrompt, $groomPath);

        // 3. Bridesmaids group image  
        $bridesmaidsPrompt = "Create a realistic bridesmaids group photo:
        - Bride with 3-4 friends standing together in coordinated dresses, no physical contact like arms around each other
        - Using color palette: {$colors}
        - Bride's original face features
        - Friends with diverse appearances
        - {$season} background theme
        - Professional group photography, elegant and joyful poses";

        $bridesmaidsImage = $this->generateAIImage($bridesmaidsPrompt, $bridePath);

        // 4. Event decoration image
        $eventPrompt = "Create a realistic wedding event decoration preview:
        - Complete wedding venue setup for {$season}
        - Using color palette: {$colors}
        - Show table settings, floral arrangements, lighting
        - Include subtle couple elements
        - Professional event photography style";

        $eventImage = $this->generateAIImage($eventPrompt);

        return [
            'couple_image' => $coupleImage,
            'groomsmen_group' => [
                'image' => $groomsmenImage,
                'colors' => array_column($palette, 'color'),
                'description' => $this->generateAIDescription("groomsmen group in {$colors} for {$season} wedding")
            ],
            'bridesmaids_group' => [
                'image' => $bridesmaidsImage,
                'colors' => array_column($palette, 'color'),
                'description' => $this->generateAIDescription("bridesmaids group in {$colors} for {$season} wedding")
            ],
            'event_decor' => [
                'image' => $eventImage,
                'colors' => array_column($palette, 'color'),
                'description' => $this->generateAIDescription("wedding event decoration using {$colors} palette for {$season}")
            ]
        ];
    }

    /**
     * Analyze skin tone using Gemini AI
     */
    private function analyzeSkinToneWithAI($imagePath)
    {
        $prompt = "Analyze this person's skin tone for wedding planning and provide:
        {
            'skin_tone': 'hex color code',
            'complementary_colors': ['hex1', 'hex2', 'hex3', 'hex4'],
            'description': 'professional skin tone analysis with undertones for wedding attire planning'
        }";

        $response = $this->callGeminiAPI($prompt, $imagePath);

        if ($response) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        // Fallback to mock data if AI fails
        return $this->getMockSkinToneAnalysis();
    }

    /**
     * Generate palette info using AI
     */
    private function generatePaletteInfoWithAI($palette, $season, $paletteNumber)
    {
        $colorNames = array_column($palette, 'name');
        $colors = implode(', ', $colorNames);

        $prompt = "For a {$season} wedding color palette {$paletteNumber} with colors: {$colors}, provide:
        {
            'title': 'creative professional palette title',
            'description': 'detailed professional description of this wedding palette',
            'color_explanation': 'professional explanation of color harmony and wedding application'
        }";

        $response = $this->callGeminiAPI($prompt);

        if ($response) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        // Fallback
        return [
            'title' => "Professional {$season} Palette {$paletteNumber}",
            'description' => "Expertly curated {$season} colors: {$colors} for your perfect wedding",
            'color_explanation' => "This professional palette creates perfect harmony for {$season} weddings"
        ];
    }

    /**
     * Generate AI image (placeholder - implement with your preferred AI image generation API)
     */
    private function generateAIImage($prompt, $referenceImagePath = null)
    {
        $client = new \GuzzleHttp\Client();
        $apiToken = env('REPLICATE_API_TOKEN');

        $body = [
            'version' => 'stability-ai/sdxl:latest',
            'input' => ['prompt' => $prompt],
        ];

        if ($referenceImagePath && file_exists($referenceImagePath)) {
            $body['input']['image'] = base64_encode(file_get_contents($referenceImagePath));
        }

        $response = $client->post('https://api.replicate.com/v1/predictions', [
            'headers' => [
                'Authorization' => 'Token ' . $apiToken,
                'Content-Type' => 'application/json'
            ],
            'json' => $body
        ]);

        $data = json_decode($response->getBody(), true);
        $imageUrl = $data['output'][0] ?? null;

        if ($imageUrl) {
            $imageContents = file_get_contents($imageUrl);
            $fileName = 'uploads/ai-generated/' . uniqid() . '.jpg';
            file_put_contents(public_path($fileName), $imageContents);
            return $fileName;
        }

        return null;
    }


    /**
     * Generate AI description
     */
    private function generateAIDescription($context)
    {
        $prompt = "Write a professional, beautiful description for wedding: {$context}";
        $response = $this->callGeminiAPI($prompt);
        return $response ?: "Professionally styled {$context}";
    }

    /**
     * Call Gemini AI API
     */
    private function callGeminiAPI($prompt, $imagePath = null)
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=" . $this->apiKey;

            $parts = [['text' => $prompt]];

            if ($imagePath && file_exists($imagePath)) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => 'image/jpeg',
                        'data' => base64_encode(file_get_contents($imagePath))
                    ]
                ];
            }

            $response = $this->client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [
                        [
                            'parts' => $parts
                        ]
                    ]
                ],
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } catch (Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Existing helper methods (keep for fallback)
     */
    private function getMockSkinToneAnalysis()
    {
        $skinTones = [
            ['skin_tone' => '#F5D0C4', 'description' => 'Light warm skin tone with peach undertones'],
            ['skin_tone' => '#E8C2A8', 'description' => 'Medium warm skin tone with golden undertones'],
            ['skin_tone' => '#D2B48C', 'description' => 'Tan skin tone with neutral undertones'],
            ['skin_tone' => '#A52A2A', 'description' => 'Deep skin tone with rich undertones']
        ];

        $selected = $skinTones[array_rand($skinTones)];

        return [
            'skin_tone' => $selected['skin_tone'],
            'complementary_colors' => $this->getComplementaryColors($selected['skin_tone']),
            'description' => $selected['description']
        ];
    }

    private function getFallbackColors($skinTone, $season, $role)
    {
        // Basic fallback color palettes
        return [
            ['color' => '#87CEEB', 'name' => 'Sky Blue', 'usage' => "{$role}'s primary attire"],
            ['color' => '#98FB98', 'name' => 'Mint Green', 'usage' => "{$role}'s accessories"],
            ['color' => '#FFFACD', 'name' => 'Lemon Cream', 'usage' => "{$role}'s accent elements"],
            ['color' => '#FFB6C1', 'name' => 'Light Pink', 'usage' => "{$role}'s floral arrangements"]
        ];
    }

    private function getSeasonImageAndCombination($season, $bridePath, $groomPath, $brideAnalysis, $groomAnalysis)
    {
        $seasonImages = [
            'spring' => 'uploads/season-images/spring_theme.jpg',
            'summer' => 'uploads/season-images/summer_theme.jpg',
            'autumn' => 'uploads/season-images/autumn_theme.jpg',
            'winter' => 'uploads/season-images/winter_theme.jpg',
        ];

        $seasonImage = $seasonImages[$season] ?? 'uploads/season-images/default_theme.jpg';

        $mixedColors = $this->generateMixedColors($brideAnalysis, $groomAnalysis, $season);

        $combinationImage = $this->generateCombinationImage($bridePath, $groomPath, $season, $mixedColors);

        return [
            'season_image' => $seasonImage,
            'combination' => [
                'season_image' => $seasonImage,
                'bride_image' => $bridePath,
                'groom_image' => $groomPath,
                'combined_image' => $combinationImage,
                'colors_used' => $mixedColors,
                'description' => $this->getCombinationDescription($season, $brideAnalysis, $groomAnalysis, $mixedColors)
            ]
        ];
    }

    private function generateMixedColors($brideAnalysis, $groomAnalysis, $season)
    {
        $brideColors = $brideAnalysis['complementary_colors'];
        $groomColors = $groomAnalysis['complementary_colors'];
        $seasonColors = $this->getSeasonColors($season);

        $unique = array_unique(array_merge($brideColors, $groomColors, $seasonColors));

        return [
            'bride_complementary' => $brideColors,
            'groom_complementary' => $groomColors,
            'season_colors' => $seasonColors,
            'mixed_palette' => array_slice($unique, 0, 6),
        ];
    }

    private function generateCombinationImage($bridePath, $groomPath, $season, $mixedColors)
    {
        $timestamp = time();
        $colorString = implode('_', array_slice($mixedColors['mixed_palette'], 0, 3));
        return "uploads/generated-images/combination_{$season}_{$colorString}_{$timestamp}.jpg";
    }

    private function getCombinationDescription($season, $brideAnalysis, $groomAnalysis, $mixedColors)
    {
        return "AI-enhanced combination blending {$season} theme with professional color harmony for both skin tones.";
    }

    private function generateSinglePalette($brideSkinTone, $groomSkinTone, $season, $paletteNumber)
    {
        $palettes = $this->getPredefinedSeasonPalettes();
        return $palettes[$season][$paletteNumber] ?? $palettes[$season][1];
    }

    private function getSeasonColors($season)
    {
        return [
            'spring' => ['#FFB6C1', '#98FB98', '#87CEEB', '#FFFACD'],
            'summer' => ['#FF69B4', '#00CED1', '#9370DB', '#F0E68C'],
            'autumn' => ['#FF4500', '#DAA520', '#8B4513', '#CD853F'],
            'winter' => ['#FFFFFF', '#000080', '#800080', '#00BFFF'],
        ][$season] ?? [];
    }

    private function getFileName($file)
    {
        return time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    }

    private function getComplementaryColors($skinTone)
    {
        $map = [
            '#F5D0C4' => ['#87CEEB', '#98FB98', '#FFFACD', '#FFB6C1'],
            '#E8C2A8' => ['#4682B4', '#32CD32', '#F0E68C', '#FF69B4'],
            '#D2B48C' => ['#4169E1', '#3CB371', '#FFD700', '#FF1493'],
            '#A52A2A' => ['#000080', '#006400', '#FF8C00', '#8B008B']
        ];
        return $map[$skinTone] ?? ['#87CEEB', '#98FB98', '#FFFACD', '#FFB6C1'];
    }

    private function getPredefinedSeasonPalettes()
    {
        // Your existing palette definitions
        return [
            'spring' => [
                1 => [
                    ['color' => '#FFB6C1', 'name' => 'Blush Pink', 'usage' => 'Bride bouquet and accessories'],
                    ['color' => '#98FB98', 'name' => 'Mint Green', 'usage' => 'Groom tie and pocket square'],
                    ['color' => '#87CEEB', 'name' => 'Sky Blue', 'usage' => 'Bridesmaids dresses'],
                    ['color' => '#FFFACD', 'name' => 'Lemon Cream', 'usage' => 'Table settings']
                ],

            ],

        ];
    }
}
