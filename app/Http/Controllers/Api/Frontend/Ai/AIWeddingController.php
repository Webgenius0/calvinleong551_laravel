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

        $bridePath = null;
        $groomPath = null;

        try {
            // Increase timeout for this request
            set_time_limit(300); // 5 minutes
            
            Log::info('AI Wedding Suggestion: Starting Analysis', [
                'season' => $request->season,
                'user_id' => auth('api')->id()
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

            // 3️⃣ Check if analysis was successful
            if (!($result['success'] ?? false)) {
                $this->cleanupFiles([$bridePath, $groomPath]);
                
                Log::warning('Analysis failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'user_id' => auth('api')->id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Analysis failed: ' . ($result['error'] ?? 'Please try again later.'),
                    'error' => $result['error'] ?? 'Analysis failed'
                ], 500);
            }

            Log::info('Analysis completed', [
                'bride_skin_tone' => $result['bride']['skin_tone'] ?? 'unknown',
                'groom_skin_tone' => $result['groom']['skin_tone'] ?? 'unknown',
                'palettes_count' => count($result['all_responses'] ?? [])
            ]);

            // 4️⃣ Save season image if available
            $seasonImagePath = null;
            if (isset($result['season']['image']) && $result['season']['image']) {
                $seasonImagePath = $this->saveSeasonImage($result['season']['image']);
                Log::info('Season image saved', ['path' => $seasonImagePath]);
            }

            // 5️⃣ Save to database
            $suggestion = $this->saveToDatabase($result, $bridePath, $groomPath, $request->season, $seasonImagePath);

            // 6️⃣ Process response for API
            $processedResponse = $this->processApiResponse($suggestion);

            Log::info('AI Wedding Suggestion: Completed Successfully', [
                'ai_suggestion_id' => $suggestion->id
            ]);

            return response()->json([
                'success' => true,
                'message' => '🎉 Wedding style analysis completed successfully',
                'data' => [
                    'ai_suggestion' => $processedResponse,
                ],
            ]);

        } catch (\Exception $e) {
            // Clean up uploaded files on error
            $this->cleanupFiles([$bridePath, $groomPath]);
            
            Log::error('AI Wedding Suggestion Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth('api')->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '❌ An error occurred during wedding style analysis. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save season image from base64 data
     */
    private function saveSeasonImage(string $base64Image): ?string
    {
        try {
            // Remove data URI prefix if present
            if (strpos($base64Image, 'data:image') === 0) {
                $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
            }

            $imageData = base64_decode($base64Image);
            
            if ($imageData === false) {
                Log::error('Failed to decode season image base64');
                return null;
            }

            $fileName = time() . "_" . Str::random(8) . "_season.png";
            $directory = public_path('uploads/' . self::FOLDER_SEASON);
            
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $filePath = "{$directory}/{$fileName}";
            
            if (file_put_contents($filePath, $imageData)) {
                return "uploads/" . self::FOLDER_SEASON . "/{$fileName}";
            } else {
                Log::error('Failed to save season image to file');
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Error saving season image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save analysis result to database
     */
    private function saveToDatabase(array $result, string $bridePath, string $groomPath, string $season, ?string $seasonImagePath): AISuggestion
    {
        // Debug the result structure
        Log::debug('Saving to database - result structure', [
            'bride_color_code' => $result['bride']['color_code'] ?? 'NOT FOUND',
            'combined_colors' => $result['combined_colors'] ?? 'NOT FOUND',
            'groom_color_code' => $result['groom']['color_code'] ?? 'NOT FOUND', 
            'season_palette' => $result['season']['palette'] ?? 'NOT FOUND',
            'season_image_path' => $seasonImagePath
        ]);

        // Save main suggestion
        $suggestion = AISuggestion::create([
            'user_id' => auth('api')->id(),
            'bride_image' => $bridePath,
            'groom_image' => $groomPath,
            'season_image' => $seasonImagePath,
            'combined_colors' => $this->ensureJson($result['combined_colors'] ?? []),
            'bride_skin_tone' => $result['bride']['skin_tone'] ?? 'neutral',
            'bride_color_code' => $this->ensureJson($result['bride']['color_code'] ?? []),
            'groom_skin_tone' => $result['groom']['skin_tone'] ?? 'neutral',
            'groom_color_code' => $this->ensureJson($result['groom']['color_code'] ?? []),
            'season_name' => $result['season']['name'] ?? $season,
            'season_palette' => $this->ensureJson($result['season']['palette'] ?? []),
            'season_description' => $result['season']['description'] ?? '',
        ]);

        Log::info('AISuggestion created', [
            'id' => $suggestion->id,
            'bride_color_code_stored' => $suggestion->bride_color_code,
            'combined_colors_stored' => $suggestion->combined_colors,
            'groom_color_code_stored' => $suggestion->groom_color_code,
            'season_palette_stored' => $suggestion->season_palette
        ]);

        // Save color themes
        if (isset($result['all_responses']) && is_array($result['all_responses'])) {
            foreach ($result['all_responses'] as $index => $palette) {
                ColorTheme::create([
                    'ai_suggestion_id' => $suggestion->id,
                    'title' => $palette['title'] ?? 'Untitled Palette ' . ($index + 1),
                    'description' => $palette['description'] ?? 'No description available',
                    'color_codes' => $this->ensureJson($palette['colors'] ?? []),
                    'images' => json_encode($palette['images'] ?? []),
                ]);
            }
            Log::info('Color themes created', ['count' => count($result['all_responses'])]);
        }

        return $suggestion->load('colorThemes');
    }

    /**
     * Ensure value is JSON encoded for database storage
     */
    private function ensureJson($value): string
    {
        // If it's already a JSON string, return as is
        if (is_string($value)) {
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }
        
        // If it's empty, return empty array as JSON
        if (empty($value)) {
            return json_encode([]);
        }
        
        // Ensure we have an array and encode it
        if (!is_array($value)) {
            $value = (array)$value;
        }
        
        return json_encode($value);
    }

    /**
     * Process API response with proper formatting
     */
    private function processApiResponse(AISuggestion $suggestion): array
    {
        $response = $suggestion->toArray();
        
        // Generate full URLs for images
        $response['bride_image_url'] = $suggestion->bride_image ? asset($suggestion->bride_image) : null;
        $response['groom_image_url'] = $suggestion->groom_image ? asset($suggestion->groom_image) : null;
        $response['season_image_url'] = $suggestion->season_image ? asset($suggestion->season_image) : null;
        
        // Safely decode JSON fields - handle both arrays and JSON strings
        $response['bride_color_code'] = $this->safeDecode($suggestion->bride_color_code);
        $response['groom_color_code'] = $this->safeDecode($suggestion->groom_color_code);
        $response['season_palette'] = $this->safeDecode($suggestion->season_palette);
        $response['combined_colors'] = $this->safeDecode($suggestion->combined_colors);
        
        // Process color themes
        $response['color_themes'] = collect($response['color_themes'])->map(function ($theme) {
            return [
                'id' => $theme['id'],
                'ai_suggestion_id' => $theme['ai_suggestion_id'],
                'title' => $theme['title'],
                'description' => $theme['description'],
                'color_codes' => $this->safeDecode($theme['color_codes']),
                'created_at' => $theme['created_at'],
                'updated_at' => $theme['updated_at'],
            ];
        })->toArray();

        // Debug the processed response
        Log::debug('Processed API response', [
            'bride_color_code' => $response['bride_color_code'],
            'groom_color_code' => $response['groom_color_code'],
            'season_palette' => $response['season_palette'],
            'combined_colors' => $response['combined_colors'],
        ]);

        return $response;
    }

    /**
     * Safely decode JSON values that might be arrays or strings
     */
    private function safeDecode($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        return [];
    }

    /**
     * Clean up uploaded files on error
     */
    private function cleanupFiles(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if ($filePath && file_exists(public_path($filePath))) {
                unlink(public_path($filePath));
                Log::info('Cleaned up file', ['path' => $filePath]);
            }
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