<?php

namespace App\Jobs;

use App\Models\AISuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GenerateSeasonImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $suggestionId;
    protected $bridePath;
    protected $groomPath;
    protected $brideSkinTone;
    protected $groomSkinTone;
    protected $season;
    protected $colors;

    public function __construct($suggestionId, $bridePath, $groomPath, $brideSkinTone, $groomSkinTone, $season, $colors)
    {
        $this->suggestionId = $suggestionId;
        $this->bridePath = $bridePath;
        $this->groomPath = $groomPath;
        $this->brideSkinTone = $brideSkinTone;
        $this->groomSkinTone = $groomSkinTone;
        $this->season = $season;
        $this->colors = $colors;
    }

    public function handle()
    {
        try {
            Log::info('Starting background season image generation', [
                'suggestion_id' => $this->suggestionId,
                'season' => $this->season
            ]);

            $seasonImagePath = $this->generateSeasonImage();

            if ($seasonImagePath) {
                AISuggestion::where('id', $this->suggestionId)->update([
                    'season_image' => $seasonImagePath
                ]);
                
                Log::info('Season image generated and saved successfully', [
                    'suggestion_id' => $this->suggestionId,
                    'image_path' => $seasonImagePath
                ]);
            } else {
                Log::warning('Season image generation failed', ['suggestion_id' => $this->suggestionId]);
            }

        } catch (\Exception $e) {
            Log::error('Background season image generation failed: ' . $e->getMessage(), [
                'suggestion_id' => $this->suggestionId
            ]);
        }
    }

    private function generateSeasonImage(): ?string
    {
        try {
            $apiKey = env('GEMINI_API_KEY');
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
            $model = 'gemini-2.0-flash-exp';

            $prompt = $this->buildSeasonImagePrompt();

            $response = Http::timeout(60)
                ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['inlineData']['data'])) {
                    $base64Data = $data['candidates'][0]['content']['parts'][0]['inlineData']['data'];
                    return $this->saveSeasonImage($base64Data);
                }
            }

            // Fallback: Create simple color palette image
            return $this->createColorPaletteImage();

        } catch (\Exception $e) {
            Log::error('Season image generation error: ' . $e->getMessage());
            return $this->createColorPaletteImage();
        }
    }

    private function buildSeasonImagePrompt(): string
    {
        $colorString = implode(', ', array_slice($this->colors, 0, 4));
        
        return "Create a beautiful romantic {$this->season} wedding scene with a couple. Use these colors: {$colorString}. 
                Style: photorealistic, soft lighting, romantic atmosphere. 
                The couple should have {$this->brideSkinTone} and {$this->groomSkinTone} skin tones.
                Scene should represent {$this->season} season beautifully. 
                Output as high-quality PNG image.";
    }

    private function saveSeasonImage(string $base64Data): string
    {
        $fileName = "season_{$this->season}_" . time() . "_" . Str::random(6) . ".png";
        $directory = public_path('uploads/season_theme');
        
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $filePath = "{$directory}/{$fileName}";
        $imageData = base64_decode($base64Data);
        
        if (file_put_contents($filePath, $imageData)) {
            return "uploads/season_theme/{$fileName}";
        }
        
        throw new \Exception('Failed to save season image');
    }

    private function createColorPaletteImage(): string
    {
        $width = 400;
        $height = 300;
        
        $image = imagecreate($width, $height);
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bgColor);
        
        // Draw color blocks
        $colorCount = min(4, count($this->colors));
        $blockWidth = $width / $colorCount;
        
        for ($i = 0; $i < $colorCount; $i++) {
            $hex = $this->colors[$i] ?? '#FFFFFF';
            $rgb = $this->hexToRgb($hex);
            $color = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
            imagefilledrectangle($image, $i * $blockWidth, 0, ($i + 1) * $blockWidth, $height, $color);
        }
        
        // Add text
        $textColor = imagecolorallocate($image, 100, 100, 100);
        $text = ucfirst($this->season) . " Palette";
        $font = 5; // Built-in font
        $textWidth = imagefontwidth($font) * strlen($text);
        $x = ($width - $textWidth) / 2;
        $y = $height - 30;
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Save image
        $fileName = "season_palette_{$this->season}_" . time() . ".png";
        $directory = public_path('uploads/season_theme');
        $filePath = "{$directory}/{$fileName}";
        
        imagepng($image, $filePath);
        imagedestroy($image);
        
        return "uploads/season_theme/{$fileName}";
    }

    private function hexToRgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return ['r' => $r, 'g' => $g, 'b' => $b];
    }
}