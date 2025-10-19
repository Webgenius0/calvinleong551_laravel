<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SkinToneService
{
    private const ANALYSIS_MODEL = 'gemini-2.5-flash';
    private const IMAGE_MODEL = 'gemini-2.5-flash-image';
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';
    private const TIMEOUT = 300;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        Log::info('🤖 Gemini Service Initializing');
        if (empty($this->apiKey)) {
            throw new Exception('GEMINI_API_KEY not set in .env');
        }
    }

    public function analyzeSkinTone(string $brideBase64, string $groomBase64, string $season): array
    {
        $prompt = $this->buildPrompt($season);

        Log::info('🎨 Gemini: Analyzing wedding images', [
            'season' => $season,
            'bride_size' => strlen($brideBase64),
            'groom_size' => strlen($groomBase64),
        ]);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $brideBase64]],
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $groomBase64]]
                    ]
                ]
            ],
            'generationConfig' => ['maxOutputTokens' => 8192]
        ];

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post(self::BASE_URL . '/' . self::ANALYSIS_MODEL . ':generateContent', $payload);

        if ($response->failed()) {
            Log::error('❌ Gemini API Error', ['response' => $response->body()]);
            throw new Exception('Gemini API Error: ' . $response->body());
        }

        $decodedResponse = $response->json();
        if (!is_array($decodedResponse)) {
            throw new Exception('Invalid JSON response from Gemini analysis');
        }

        $rawText = $this->extractText($decodedResponse);
        Log::info('📝 Gemini Raw Text Response', ['raw' => $rawText]);

        if (!$rawText) {
            throw new Exception('No text response from Gemini analysis');
        }

        $jsonString = $this->extractJson($rawText);
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON from Gemini', ['json_error' => json_last_error_msg(), 'json_string' => $jsonString]);
            throw new Exception('Invalid JSON response from Gemini: ' . json_last_error_msg());
        }

        if (!isset($data['bride']) || !is_array($data['bride'])) {
            $data['bride'] = ['skin_tone' => 'Default fair skin tone', 'undertone' => 'neutral'];
        }
        if (!isset($data['groom']) || !is_array($data['groom'])) {
            $data['groom'] = ['skin_tone' => 'Default light-medium skin tone', 'undertone' => 'neutral'];
        }

        $this->normalizeData($data, $season);

        $data['bride']['edited_image'] = $this->generateEditedImage($brideBase64, 'bride', $data['bride'], $season);
        $data['groom']['edited_image'] = $this->generateEditedImage($groomBase64, 'groom', $data['groom'], $season);
        $data['season']['image'] = $this->generateSeasonImage($season, $data['season']['palette']);

        Log::info('✅ Analysis complete', ['has_images' => [
            'bride' => !empty($data['bride']['edited_image']),
            'groom' => !empty($data['groom']['edited_image']),
            'season' => !empty($data['season']['image'])
        ]]);

        return $data;
    }

    private function normalizeData(array &$data, string $season): void
    {
        $this->normalizePerson($data['bride'], $season);
        $this->normalizePerson($data['groom'], $season);

        if (!is_array($data['season'] ?? null)) {
            $data['season'] = [
                'theme' => ucfirst($season) . ' Wedding Theme',
                'palette' => $this->getDefaultSeasonPalette($season)
            ];
        } elseif (!isset($data['season']['palette'])) {
            $data['season']['palette'] = $this->getDefaultSeasonPalette($season);
        }
    }

    private function normalizePerson(array &$person, string $season): void
    {
        if (isset($person['color_palette'])) {
            $person['palette'] = $this->mapPaletteToHex($person['color_palette']);
            unset($person['color_palette']);
        } elseif (!isset($person['palette']) || empty($person['palette'])) {
            $person['palette'] = $this->getDefaultSeasonPalette($season);
        }

        if (isset($person['wedding_style_suggestions'])) {
            $person['suggestions'] = is_array($person['wedding_style_suggestions']) 
                ? implode(' | ', $person['wedding_style_suggestions']) 
                : $person['wedding_style_suggestions'];
            unset($person['wedding_style_suggestions']);
        } elseif (!isset($person['suggestions']) || empty($person['suggestions'])) {
            $person['suggestions'] = 'Default style tips for ' . $season . ' wedding.';
        }

        if (!isset($person['undertone']) || empty($person['undertone'])) {
            $person['undertone'] = $this->extractUndertone($person['skin_tone'] ?? '');
        }

        $person['matching_colors'] = array_slice($person['palette'], 0, 3);
    }

    private function generateEditedImage(string $base64, string $role, array $analysis, string $season): ?string
    {
        try {
            $matchingColors = implode(', ', $analysis['matching_colors']);
            $prompt = "Edit the provided image of the {$role} for a beautiful {$season} wedding. Enhance appearance using harmonious colors: {$matchingColors}. Add subtle makeup, elegant attire adjustments, natural seasonal lighting. Output the edited realistic high-quality portrait image.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $base64]]
                        ]
                    ]
                ]
            ];

            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post(self::BASE_URL . '/' . self::IMAGE_MODEL . ':generateContent', $payload);

            if ($response->failed()) {
                Log::error("❌ Edited {$role} image API Error", ['response' => $response->body()]);
                return null;
            }

            $decodedResponse = $response->json();
            if (!is_array($decodedResponse)) {
                Log::error("❌ Invalid response for edited {$role} image", ['body' => $response->body()]);
                return null;
            }

            $base64Image = $this->extractBase64Image($decodedResponse);
            Log::info("✅ Generated edited {$role} image", ['size' => strlen($base64Image ?? ''), 'response_keys' => array_keys($decodedResponse['candidates'][0]['content']['parts'][0] ?? [])]);

            return $base64Image;
        } catch (Exception $e) {
            Log::error("Failed to generate edited {$role} image", ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function generateSeasonImage(string $season, array $palette): ?string
    {
        try {
            $paletteStr = implode(', ', $palette);
            $prompt = "Generate a realistic wedding theme background image for {$season} season. Incorporate elegant decorations, flowers, romantic ambiance using colors: {$paletteStr}. High-quality, suitable for bride and groom portraits. Output the image.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]]
                    ]
                ]
            ];

            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post(self::BASE_URL . '/' . self::IMAGE_MODEL . ':generateContent', $payload);

            if ($response->failed()) {
                Log::error('❌ Season image API Error', ['response' => $response->body()]);
                return null;
            }

            $decodedResponse = $response->json();
            if (!is_array($decodedResponse)) {
                Log::error('❌ Invalid response for season image', ['body' => $response->body()]);
                return null;
            }

            $base64Image = $this->extractBase64Image($decodedResponse);
            Log::info('✅ Generated season theme image', ['size' => strlen($base64Image ?? ''), 'response_keys' => array_keys($decodedResponse['candidates'][0]['content']['parts'][0] ?? [])]);

            return $base64Image;
        } catch (Exception $e) {
            Log::error('Failed to generate season image', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildPrompt(string $season): string
    {
        return <<<PROMPT
Wedding analyst. Image 1: BRIDE. Image 2: GROOM.

Brief tones/undertones, 3 HEX palettes harmonizing for {$season} wedding.

ONLY JSON:

{
  "bride": {
    "skin_tone": "Brief",
    "undertone": "warm/cool/neutral",
    "palette": ["#HEX1","#HEX2","#HEX3"],
    "suggestions": "Tips"
  },
  "groom": {
    "skin_tone": "Brief",
    "undertone": "warm/cool/neutral",
    "palette": ["#HEX1","#HEX2","#HEX3"],
    "suggestions": "Tips"
  },
  "season": {
    "theme": "{$season} theme",
    "palette": ["#HEX1","#HEX2","#HEX3"]
  }
}
PROMPT;
    }

    private function extractText(array $response): ?string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    private function extractBase64Image(array $response): ?string
    {
        // Check first part
        if (isset($response['candidates'][0]['content']['parts'][0]['inline_data']['data'])) {
            return $response['candidates'][0]['content']['parts'][0]['inline_data']['data'];
        }
        // Check all parts
        foreach ($response['candidates'][0]['content']['parts'] ?? [] as $part) {
            if (isset($part['inline_data']['data'])) {
                return $part['inline_data']['data'];
            }
        }
        // Fallback text base64
        $text = $this->extractText($response);
        if ($text && preg_match('/data:image\/[a-z]+;base64,([A-Za-z0-9+\/=]+)/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extractJson(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*\n?|\n?```$/s', '', $text);
        $text = trim($text);
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            return $matches[0];
        }
        throw new Exception('No valid JSON found in response');
    }

    private function getDefaultSeasonPalette(string $season): array
    {
        $palettes = [
            'summer' => ['#87CEEB', '#98FB98', '#F0E68C'],
            'winter' => ['#E0F6FF', '#B0E0E6', '#F0F8FF'],
            'autumn' => ['#D2691E', '#CD853F', '#F4A460'],
            'spring' => ['#FFB6C1', '#98FB98', '#FFE4E1']
        ];
        return $palettes[strtolower($season)] ?? ['#FFFFFF', '#F5F5F5', '#E0E0E0'];
    }

    private function mapPaletteToHex(array $descriptivePalette): array
    {
        $colorMap = [
            'pale aqua' => '#B0E0E6', 'dusty lilac' => '#C8A2C8', 'muted rose' => '#D8BFD8',
            'deep' => '#2F4F4F', 'dark slate gray' => '#2F4F4F', 'slate blue' => '#6A5ACD', 'dark magenta' => '#8B008B'
        ];
        $hexes = [];
        foreach ($descriptivePalette as $color) {
            $color = trim(strtolower($color));
            $hex = $colorMap[$color] ?? '#FFFFFF';
            if (!str_starts_with($hex, '#')) $hex = '#FFFFFF';
            $hexes[] = $hex;
        }
        return array_slice(array_unique($hexes), 0, 3);
    }

    public function generatePaletteSections(array $analysis, string $season): array
    {
        $bridePalette = $analysis['bride']['palette'] ?? $this->getDefaultSeasonPalette($season);
        $groomPalette = $analysis['groom']['palette'] ?? $this->getDefaultSeasonPalette($season);
        $seasonPalette = $analysis['season']['palette'] ?? $this->getDefaultSeasonPalette($season);

        // Mix palettes for harmony
        $allColors = array_unique(array_merge($bridePalette, $groomPalette, $seasonPalette));
        $allColors = array_slice($allColors, 0, 12); // Limit to 12 unique for sections

        $sections = [
            [
                'title' => 'Soft Summer Glow',
                'description' => 'এই প্যালেট bride-এর light, fair complexion এবং groom-এর light-medium golden hue-এর সাথে মিলে soft, ethereal summer vibe তৈরি করে। Gentle blush tones এবং natural lighting-এর জন্য perfect, যা romantic garden wedding-এর জন্য আদর্শ।',
                'colors' => array_slice($allColors, 0, 4),
                'preview_outfits' => $this->generatePreviewOutfits($allColors, 'Soft Summer Glow', $analysis['bride']['undertone'], $analysis['groom']['undertone'], $season)
            ],
            [
                'title' => 'Golden Coastal Radiance',
                'description' => 'Golden, warm undertones-এর উপর ফোকাস করে এই প্যালেট golden hour lighting এবং coastal elements যোগ করে। Bride-এর rosiness এবং groom-এর olive hue-কে highlight করে effortless, sun-kissed look দেয় summer beach wedding-এর জন্য।',
                'colors' => array_slice(array_merge($allColors, ['#FFD700']), 4, 4), // Mix with extra golden
                'preview_outfits' => $this->generatePreviewOutfits($allColors, 'Golden Coastal Radiance', $analysis['bride']['undertone'], $analysis['groom']['undertone'], $season)
            ],
            [
                'title' => 'Neutral Harmony Blend',
                'description' => 'Neutral tones-এর সাথে balanced blush এবং earthy accents মিশিয়ে এই প্যালেট bride এবং groom-এর combined complexion-কে harmonize করে। Light, airy feel দেয় sophisticated outdoor summer ceremony-এর জন্য।',
                'colors' => array_slice($allColors, 8, 4),
                'preview_outfits' => $this->generatePreviewOutfits($allColors, 'Neutral Harmony Blend', $analysis['bride']['undertone'], $analysis['groom']['undertone'], $season)
            ],
            [
                'title' => 'Lavender Mist Romance',
                'description' => 'Soft lavender এবং misty pastels দিয়ে romantic, dreamy vibe তৈরি করে এই প্যালেট rosiness এবং golden hue-কে complement করে। Gentle blush এবং cream tones-এর সাথে ideal for enchanted summer evening wedding।',
                'colors' => ['#E6E6FA', '#D8BFD8', '#F8F1F1', '#E0BBE4'], // Lavender-themed
                'preview_outfits' => $this->generatePreviewOutfits($allColors, 'Lavender Mist Romance', $analysis['bride']['undertone'], $analysis['groom']['undertone'], $season)
            ]
        ];

        return $sections;
    }

    /**
     * Generate Preview Outfits for a Section
     * Returns 3-4 outfit previews with description, colors used, and placeholder image URL (or generate via API if needed)
     */
    private function generatePreviewOutfits(array $allColors, string $theme, string $brideUndertone, string $groomUndertone, string $season): array
    {
        $outfits = [
            [
                'id' => 1,
                'name' => 'Bridal Gown & Suite',
                'description' => 'Elegant A-line gown in soft ivory with golden accents for bride, paired with navy suit for groom. Perfect for golden hour sunset vows.',
                'colors_used' => array_slice($allColors, 0, 3),
                'image_url' => 'https://example.com/outfit1.jpg', // Replace with generated image URL from your API or placeholder
                'price_range' => '$1,200 - $2,500',
                'style' => 'Romantic Coastal'
            ],
            [
                'id' => 2,
                'name' => 'Bridesmaid Ensemble',
                'description' => 'Mismatched lavender dresses with floral crowns for bridesmaids, complementing bride\'s warm tones and groom\'s neutral skin.',
                'colors_used' => ['#E6E6FA', '#D8BFD8', '#F8F1F1'],
                'image_url' => 'https://example.com/outfit2.jpg',
                'price_range' => '$400 - $800 per dress',
                'style' => 'Boho Garden'
            ],
            [
                'id' => 3,
                'name' => 'Groom & Groomsmen Attire',
                'description' => 'Tailored charcoal suits with blush ties, harmonizing golden undertones for a sophisticated summer look.',
                'colors_used' => array_slice($allColors, 2, 2),
                'image_url' => 'https://example.com/outfit3.jpg',
                'price_range' => '$800 - $1,500 per suit',
                'style' => 'Modern Classic'
            ],
            [
                'id' => 4,
                'name' => 'Full Party Look',
                'description' => 'Complete theme with floral arches in season colors, tying bride and groom palettes for a cohesive, dreamy celebration.',
                'colors_used' => $allColors,
                'image_url' => 'https://example.com/outfit4.jpg',
                'price_range' => 'N/A (Full Setup)',
                'style' => $theme
            ],
        ];

        return $outfits;
    }

    private function extractUndertone(string $skinTone): string
    {
        $tone = strtolower($skinTone);
        if (str_contains($tone, 'warm')) return 'warm';
        if (str_contains($tone, 'cool')) return 'cool';
        return 'neutral';
    }

    public function testImageGeneration(): array
    {
        return [self::ANALYSIS_MODEL, self::IMAGE_MODEL];
    }

    
}