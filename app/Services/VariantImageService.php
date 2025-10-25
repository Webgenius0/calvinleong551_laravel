<?php

namespace App\Services;

use App\Models\ColorTheme;
use App\Models\AISuggestion;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VariantImageService
{
    private const TIMEOUT = 120;
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
     * Generate variant images for a specific color theme.
     * Fetches color theme by ID, gets associated AI suggestion for images and season,
     * then generates 6 variant images based on the color codes.
     */
    public function generateVariantImagesForTheme(int $colorThemeId): array
    {
        try {
            Log::info('Generating variant images for color theme', ['color_theme_id' => $colorThemeId]);

            // Fetch ColorTheme
            $colorTheme = ColorTheme::findOrFail($colorThemeId);
            $colors = json_decode($colorTheme->color_codes, true) ?? [];
            if (empty($colors)) {
                throw new Exception('No colors found in color theme');
            }

            // Fetch associated AISuggestion
            $aiSuggestion = AISuggestion::findOrFail($colorTheme->ai_suggestion_id);
            $season = strtolower($aiSuggestion->season_name);
            $bridePath = public_path($aiSuggestion->bride_image);
            $groomPath = public_path($aiSuggestion->groom_image);

            if (!file_exists($bridePath) || !file_exists($groomPath)) {
                throw new Exception('Bride or groom image not found');
            }

            // Generate 6 different images for this palette
            $images = $this->generatePaletteImages($season, $colors, $bridePath, $groomPath, $colorThemeId);

            // Update the color_theme with image paths as JSON
            $colorTheme->images = json_encode($images);
            $colorTheme->save();

            Log::info('Variant images generated successfully and images field updated as JSON', [
                'color_theme_id' => $colorThemeId,
                'image_count' => count($images)
            ]);

            return [
                'success' => true,
                'message' => 'Variant images generated successfully',
                'data' => [
                    'color_theme_id' => $colorThemeId,
                    'colors' => $colors,
                    'season' => $season,
                    'images' => $images  // Each image has 'url' for access
                ]
            ];
        } catch (Exception $e) {
            Log::error('Error generating variant images: ' . $e->getMessage(), [
                'color_theme_id' => $colorThemeId
            ]);
            return [
                'success' => false,
                'message' => 'Failed to generate variant images',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate 6 different types of images for a palette.
     */
    private function generatePaletteImages(string $season, array $colors, string $bridePath, string $groomPath, int $themeId): array
    {
        $images = [];

        // 1. Groom and bride wedding image
        $images[] = $this->generateCoupleWeddingImage($season, $colors, $bridePath, $groomPath, $themeId);

        // 2. Groom individual season-based wedding image
        $images[] = $this->generateGroomIndividualImage($season, $colors, $groomPath, $themeId);

        // 3. Groom with friends (4 persons)
        $images[] = $this->generateGroomFriendsImage($season, $colors, $groomPath, $themeId);

        // 4. Bride with friends (4 persons)
        $images[] = $this->generateBrideFriendsImage($season, $colors, $bridePath, $themeId);

        // 5. Couple praying image with color code
        // $images[] = $this->generateCouplePrayingImage($season, $colors, $bridePath, $groomPath, $themeId);
        $images[] = $this->generateConventionHallImage($season, $colors, $bridePath, $groomPath, $themeId);

        // 6. Bride, groom and friends (6 persons)
        $images[] = $this->generateGroupFriendsImage($season, $colors, $bridePath, $groomPath, $themeId);

        return array_filter($images); // Remove any null values
    }

    /**
     * 1. Groom and bride wedding image
     */
    private function generateCoupleWeddingImage(string $season, array $colors, string $bridePath, string $groomPath, int $themeId): array
    {
        $prompt = "Create a romantic wedding photo of a bride and groom in {$season} season theme. They should be standing together in elegant wedding attire that complements these colors: " . implode(', ', $colors) . ". The scene should be photorealistic with soft lighting and romantic atmosphere. Focus on their joyful expressions and connection.";

        $imageData = $this->generateImageWithFaces($prompt, $bridePath, $groomPath);
        $folder = 'couple_wedding_images';
        $imagePath = $this->saveImageToPublic($imageData, "{$folder}/couple_wedding_{$season}_{$themeId}");

        return [
            'type' => 'couple_wedding',
            'description' => 'Bride and groom wedding portrait',
            'url' => $imagePath
        ];
    }

    /**
     * 2. Groom individual season-based wedding image
     */
    private function generateGroomIndividualImage(string $season, array $colors, string $groomPath, int $themeId): array
    {
        $prompt = "Create a professional wedding portrait of a groom in {$season} season theme. He should be wearing a wedding outfit that matches these colors: " . implode(', ', $colors) . ". The groom should look handsome and confident, with appropriate background for {$season}. Photorealistic style with excellent lighting.";

        $imageData = $this->generateImageWithFace($prompt, $groomPath);
        $folder = 'groom_individual_images';
        $imagePath = $this->saveImageToPublic($imageData, "{$folder}/groom_individual_{$season}_{$themeId}");

        return [
            'type' => 'groom_individual',
            'description' => 'Groom individual wedding portrait',
            'url' => $imagePath
        ];
    }

    /**
     * 3. Groom with friends (4 persons)
     */
    private function generateGroomFriendsImage(string $season, array $colors, string $groomPath, int $themeId): array
    {
        $prompt = "Create a group photo of a groom with 3 male friends in {$season} wedding theme. All should be wearing outfits that complement these colors: " . implode(', ', $colors) . ". They should be laughing and having a good time together. The groom should be the central focus. Photorealistic style with natural expressions.";

        $imageData = $this->generateImageWithFace($prompt, $groomPath);
        $folder = 'groom_friends_images';
        $imagePath = $this->saveImageToPublic($imageData, "{$folder}/groom_friends_{$season}_{$themeId}");

        return [
            'type' => 'groom_friends',
            'description' => 'Groom with 3 friends',
            'url' => $imagePath
        ];
    }

    /**
     * 4. Bride with friends (4 persons)
     */
    private function generateBrideFriendsImage(string $season, array $colors, string $bridePath, int $themeId): array
    {
        $prompt = "Create a group photo of a bride with 3 female friends in {$season} wedding theme. All should be wearing outfits that complement these colors: " . implode(', ', $colors) . ". They should be smiling and sharing a joyful moment. The bride should be the central focus. Photorealistic style with beautiful lighting.";

        $imageData = $this->generateImageWithFace($prompt, $bridePath);
        $folder = 'bride_friends_images';
        $imagePath = $this->saveImageToPublic($imageData, "{$folder}/bride_friends_{$season}_{$themeId}");


        return [
            'type' => 'bride_friends',
            'description' => 'Bride with 3 friends',
            'url' => $imagePath
        ];
    }

    /**
     * 5. Couple praying image with color code
     */
    // private function generateCouplePrayingImage(string $season, array $colors, string $bridePath, string $groomPath, int $themeId): array
    // {
    //     $colorString = implode(', ', $colors);
    //     $prompt = "Create a solemn and beautiful image of a bride and groom praying together in {$season} setting. Their outfits should match these colors: {$colorString}. Include a subtle display of the color palette in the corner of the image. The mood should be reverent and peaceful. Photorealistic style with soft lighting.";

    //     $imageData = $this->generateImageWithFaces($prompt, $bridePath, $groomPath);
    //     $folder = 'couple_praying_images';
    //     $imagePath = $this->saveImageToPublic($imageData, "{$folder}/couple_praying_{$season}_{$themeId}");

    //     return [
    //         'type' => 'couple_praying',
    //         'description' => 'Couple praying with color palette',
    //         'url' => $imagePath,
    //         'color_codes' => $colors
    //     ];
    // }

    private function generateConventionHallImage(string $season, array $colors, string $bridePath, string $groomPath, int $themeId): array
    {
        $colorString = implode(', ', $colors);
        $prompt = "Create a beautiful convention hall decorated for a {$season} wedding reception. Use these colors: {$colorString} for decorations, lighting, table settings, floral arrangements, and overall ambiance. Include elegant chandeliers, stage with backdrop, tables with centerpieces, and soft ambient lighting. The scene should be photorealistic, spacious, luxurious, and inviting, with a romantic wedding atmosphere. No people in the scene, focus on the decorated hall.";
        
        $imageData = $this->generateImageWithFaces($prompt, $bridePath, $groomPath); // Using faces for consistency, but prompt focuses on hall
        $folder = 'convention_hall_images';
        $imagePath = $this->saveImageToPublic($imageData, "{$folder}/convention_hall_{$season}_{$themeId}", 'hall');

        return [
            'type' => 'convention_hall',
            'description' => 'Beautiful convention hall with wedding decorations matching color palette',
            'url' => $imagePath,
            'color_codes' => $colors
        ];
    }

    /**
     * 6. Bride, groom and friends (6 persons)
     */
    private function generateGroupFriendsImage(string $season, array $colors, string $bridePath, string $groomPath, int $themeId): array
    {
        $prompt = "Create a large group photo of a bride, groom, and 4 friends (total 6 people) in {$season} wedding theme. Everyone should be wearing outfits that complement these colors: " . implode(', ', $colors) . ". The group should look happy and celebratory, with the bride and groom at the center. Photorealistic style with great composition.";

        $imageData = $this->generateImageWithFaces($prompt, $bridePath, $groomPath);
        $folder = 'group_friends_images';
        $imagePath = $this->saveImageToPublic($imageData, "{$folder}/group_friends_{$season}_{$themeId}");

        return [
            'type' => 'group_friends',
            'description' => 'Bride, groom and 4 friends',
            'url' => $imagePath
        ];
    }

    /**
     * Generate image with bride and groom faces.
     */
    private function generateImageWithFaces(string $prompt, string $bridePath, string $groomPath): ?string
    {
        try {
            $brideMimeType = mime_content_type($bridePath);
            $groomMimeType = mime_content_type($groomPath);
            $brideBase64 = base64_encode(file_get_contents($bridePath));
            $groomBase64 = base64_encode(file_get_contents($groomPath));

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

            $response = Http::timeout(self::TIMEOUT)
                ->post("{$this->baseUrl}/models/{$this->imageModel}:generateContent?key={$this->apiKey}", [
                    'contents' => $contents,
                    'generationConfig' => [
                        'response_modalities' => ['TEXT', 'IMAGE'],
                        'temperature' => 0.6
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
        } catch (\Exception $e) {
            Log::error('Image generation with faces failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate image with single face.
     */
    private function generateImageWithFace(string $prompt, string $facePath): ?string
    {
        try {
            $mimeType = mime_content_type($facePath);
            $base64Image = base64_encode(file_get_contents($facePath));

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

            $response = Http::timeout(self::TIMEOUT)
                ->post("{$this->baseUrl}/models/{$this->imageModel}:generateContent?key={$this->apiKey}", [
                    'contents' => $contents,
                    'generationConfig' => [
                        'response_modalities' => ['TEXT', 'IMAGE'],
                        'temperature' => 0.6
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
        } catch (\Exception $e) {
            Log::error('Image generation with face failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Save base64 image to public storage and return URL.
     */
    // private function saveImageToPublic(?string $base64Data, string $filename): ?string
    // {
    //     if (!$base64Data) {
    //         return null;
    //     }

    //     try {
    //         $publicPath = public_path('uploads/images/weddings');
    //         if (!file_exists($publicPath)) {
    //             mkdir($publicPath, 0755, true);
    //         }

    //         $filePath = "{$publicPath}/{$filename}.jpg";
    //         $imageData = base64_decode($base64Data);

    //         if (file_put_contents($filePath, $imageData)) {
    //             return "uploads/images/weddings/{$filename}.jpg";
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Failed to save image: ' . $e->getMessage());
    //     }

    //     return null;
    // }


    /**
 * Save base64 image to public storage and return URL.
 * Supports dynamic subfolders like "bride_friends_images", "groom_friends_images", etc.
 */
/**
 * Save base64 image to public storage and return full URL.
 */
private function saveImageToPublic(?string $base64Data, string $filename): ?string
{
    if (!$base64Data) {
        return null;
    }

    try {
        $pathInfo = pathinfo($filename);
        $relativeFolder = $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] : '';
        $baseFilename = $pathInfo['filename'];

        $publicFolder = public_path("uploads/images/weddings" . ($relativeFolder ? "/{$relativeFolder}" : ''));
        if (!file_exists($publicFolder)) {
            mkdir($publicFolder, 0755, true);
        }

        $filePath = "{$publicFolder}/{$baseFilename}.jpg";
        $imageData = base64_decode($base64Data);
        file_put_contents($filePath, $imageData);

        // ✅ Return full base URL (e.g. https://yourdomain.com/uploads/images/weddings/...)
        $relativePath = "uploads/images/weddings" . ($relativeFolder ? "/{$relativeFolder}" : '') . "/{$baseFilename}.jpg";
        return url($relativePath);

    } catch (\Exception $e) {
        Log::error('Failed to save image: ' . $e->getMessage());
        return null;
    }
}


}
