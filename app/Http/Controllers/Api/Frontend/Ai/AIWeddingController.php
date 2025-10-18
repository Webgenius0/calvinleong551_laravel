<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use App\Models\AISuggestion;
use Illuminate\Http\Request;
use App\Services\SkinToneService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class AIWeddingController extends Controller
{
    protected SkinToneService $skinToneService;

    public function __construct(SkinToneService $skinToneService)
    {
        $this->skinToneService = $skinToneService;
    }

    public function generateSuggestion(Request $request)
    {
        $request->validate([
            'bride_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'groom_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'season' => 'required|string|in:spring,summer,autumn,winter',
        ]);

        try {
            // Upload original images
            $bridePath = $this->uploadFile($request->file('bride_image'), 'bride');
            $groomPath = $this->uploadFile($request->file('groom_image'), 'groom');

            // Convert to base64 for AI processing
            $brideBase64 = base64_encode(file_get_contents(public_path($bridePath)));
            $groomBase64 = base64_encode(file_get_contents(public_path($groomPath)));

            Log::info('Starting AI analysis for wedding suggestions', [
                'season' => $request->season,
                'bride_image_size' => strlen($brideBase64),
                'groom_image_size' => strlen($groomBase64)
            ]);

            // Get AI analysis
            $result = $this->skinToneService->analyzeSkinTone($brideBase64, $groomBase64, $request->season);

            // Log the analysis results
            Log::info('AI Analysis Results:', [
                'bride_skin_tone' => $result['bride']['skin_tone'] ?? 'Unknown',
                'groom_skin_tone' => $result['groom']['skin_tone'] ?? 'Unknown',
                'bride_colors' => $result['bride']['matching_colors'] ?? [],
                'groom_colors' => $result['groom']['matching_colors'] ?? [],
                'season_palette' => $result['season']['palette'] ?? []
            ]);

            // Save images (they might be null)
            $brideEditedPath = $this->saveBase64Image($result['bride']['edited_image'], 'bride_edited');
            $groomEditedPath = $this->saveBase64Image($result['groom']['edited_image'], 'groom_edited');
            $seasonThemePath = $this->saveBase64Image($result['season']['image'], 'season_theme');

            // Create database record
            $suggestion = AISuggestion::create([
                'bride_image' => $bridePath,
                'groom_image' => $groomPath,
                'bride_edited_image' => $brideEditedPath,
                'groom_edited_image' => $groomEditedPath,
                'season_theme_image' => $seasonThemePath,
                'bride_skin_tone' => $result['bride']['skin_tone'] ?? null,
                'bride_color_code' => json_encode($result['bride']['color_code'] ?? []),
                'bride_matching_colors' => json_encode($result['bride']['matching_colors'] ?? []),
                'groom_skin_tone' => $result['groom']['skin_tone'] ?? null,
                'groom_color_code' => json_encode($result['groom']['color_code'] ?? []),
                'groom_matching_colors' => json_encode($result['groom']['matching_colors'] ?? []),
                'season_name' => $result['season']['name'] ?? $request->season,
                'season_palette' => json_encode($result['season']['palette'] ?? []),
                'season_description' => $result['season']['description'] ?? '',
            ]);

            return response()->json([
                'success' => true, 
                'data' => $suggestion,
                'message' => 'Wedding style analysis completed successfully',
                'images_generated' => false
            ]);

        } catch (\Exception $e) {
            Log::error('AI Wedding generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Analysis service is temporarily unavailable. Please try again later.'
            ], 500);
        }
    }

    public function testModels(Request $request)
    {
        try {
            $results = $this->skinToneService->testAvailableModels();
            return response()->json([
                'success' => true,
                'models' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function uploadFile($file, $folder): string
    {
        $name = time() . "_" . Str::random(8) . "_{$folder}." . $file->getClientOriginalExtension();
        $directory = public_path("uploads/{$folder}");
        
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $path = "uploads/{$folder}/" . $name;
        $file->move($directory, $name);
        return $path;
    }

    private function saveBase64Image(?string $base64, string $folder): ?string
    {
        if (!$base64) {
            return null;
        }

        $directory = public_path("uploads/{$folder}");
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        if (strpos($base64, 'data:image') === 0) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        }

        $fileName = $folder . '_' . Str::random(12) . '.png';
        $filePath = "uploads/{$folder}/" . $fileName;

        try {
            $imageData = base64_decode($base64);
            if ($imageData === false) {
                return null;
            }

            file_put_contents(public_path($filePath), $imageData);
            return $filePath;

        } catch (\Exception $e) {
            return null;
        }
    }
}