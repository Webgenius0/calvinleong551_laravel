<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use App\Models\AISuggestion;
use App\Models\ColorTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\SkinToneService;

class AIWeddingController extends Controller
{
    private const FOLDER_BRIDE = 'bride';
    private const FOLDER_GROOM = 'groom';
    private const FOLDER_SEASON = 'season_theme';

    protected SkinToneService $skinToneService;

    public function __construct(SkinToneService $skinToneService)
    {
        $this->skinToneService = $skinToneService;
    }

    /**
     * Generate wedding style suggestion
     */
    public function generateSuggestion(Request $request)
    {
        $request->validate([
            'bride_image' => 'required|image',
            'groom_image' => 'required|image',
            'season' => 'required|string|in:spring,summer,autumn,winter',
        ]);

        try {
            // Increase timeout for this request
            set_time_limit(300); // 5 minutes
            
            Log::info('AI Wedding Suggestion: Starting Analysis', [
                'season' => $request->season,
                'user_id' => auth()->id()
            ]);

            // 1️⃣ Upload original images
            $bridePath = $this->uploadFile($request->file('bride_image'), self::FOLDER_BRIDE);
            $groomPath = $this->uploadFile($request->file('groom_image'), self::FOLDER_GROOM);

            Log::info('Images uploaded successfully', [
                'bride_path' => $bridePath,
                'groom_path' => $groomPath
            ]);

            // 2️⃣ Analyze skin tone & generate suggestions
            $result = $this->skinToneService->analyzeSkinTone(
                public_path($bridePath),
                public_path($groomPath),
                $request->season
            );

            Log::info('Analysis completed', [
                'bride_skin_tone' => $result['bride']['skin_tone'] ?? 'unknown',
                'groom_skin_tone' => $result['groom']['skin_tone'] ?? 'unknown',
                'palettes_count' => count($result['all_responses'] ?? [])
            ]);

            // 3️⃣ Save season image if available
            $seasonImagePath = null;
            if (isset($result['season']['image']) && $result['season']['image']) {
                $base64Image = substr($result['season']['image'], strpos($result['season']['image'], ",") + 1);
                $imageData = base64_decode($base64Image);
                
                if ($imageData !== false) {
                    $fileName = time() . "_" . Str::random(8) . "_season.png";
                    $directory = public_path(self::FOLDER_SEASON);
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    $filePath = "{$directory}/{$fileName}";
                    
                    if (file_put_contents($filePath, $imageData)) {
                        $seasonImagePath = "uploads/" . self::FOLDER_SEASON . "/{$fileName}";
                        Log::info('Season image saved successfully', ['path' => $seasonImagePath]);
                    } else {
                        Log::error('Failed to save season image to file');
                    }
                } else {
                    Log::error('Failed to decode season image base64');
                }
            } else {
                Log::info('No season image available in analysis result');
            }

            // 4️⃣ Save main data to AISuggestion table
            $suggestion = AISuggestion::create([
                'user_id' => auth()->id(),
                'bride_image' => $bridePath,
                'groom_image' => $groomPath,
                'season_image' => $seasonImagePath,
                'bride_skin_tone' => $result['bride']['skin_tone'] ?? 'neutral',
                'bride_color_code' => json_encode($result['bride']['color_code'] ?? []),
                'groom_skin_tone' => $result['groom']['skin_tone'] ?? 'neutral',
                'groom_color_code' => json_encode($result['groom']['color_code'] ?? []),
                'season_name' => $result['season']['name'] ?? $request->season,
                'season_palette' => json_encode($result['season']['palette'] ?? []),
                'season_description' => $result['season']['description'] ?? '',
            ]);

            Log::info('AISuggestion created', ['id' => $suggestion->id, 'season_image' => $seasonImagePath]);

            // 5️⃣ Save color palettes to color_themes table
            $colorThemes = [];
            if (isset($result['all_responses']) && is_array($result['all_responses'])) {
                foreach ($result['all_responses'] as $index => $palette) {
                    $colorTheme = ColorTheme::create([
                        'ai_suggestion_id' => $suggestion->id,
                        'title' => $palette['title'] ?? 'Untitled Palette ' . ($index + 1),
                        'description' => $palette['description'] ?? 'No description available',
                        'color_codes' => json_encode($palette['colors'] ?? []),
                        'images' => json_encode($palette['images'] ?? []),
                    ]);
                    $colorThemes[] = $colorTheme;
                }
                Log::info('Color themes created', ['count' => count($colorThemes)]);
            } else {
                Log::warning('No color palettes found in response');
            }

            // 6️⃣ Load the relationship for response - avoid duplicate by not returning separate array
            $suggestion->load('colorThemes');

            Log::info('AI Wedding Suggestion: Completed Successfully', [
                'ai_suggestion_id' => $suggestion->id,
                'color_themes_count' => count($colorThemes)
            ]);

            return response()->json([
                'success' => true,
                'message' => '🎉 Wedding style analysis completed successfully',
                'data' => [
                    'ai_suggestion' => $suggestion, // Includes loaded colorThemes
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('AI Wedding Suggestion Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '❌ An error occurred during wedding style analysis. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==============================
    // File Handling Helpers
    // ==============================

    private function uploadFile($file, string $folder): string
    {
        $fileName = time() . "_" . Str::random(8) . "_{$folder}." . $file->getClientOriginalExtension();
        $directory = public_path("uploads/{$folder}");
        if (!file_exists($directory)) mkdir($directory, 0755, true);
        $file->move($directory, $fileName);
        return "uploads/{$folder}/{$fileName}";
    }
}