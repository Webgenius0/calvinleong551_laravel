<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use App\Models\AISuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\SkinToneService;

class AIWeddingController extends Controller
{
    /** 
     * Bride, Groom, Seasonal ইমেজ রাখার জন্য কনস্ট্যান্ট ফোল্ডার পাথ
     */
    private const FOLDER_BRIDE = 'bride';
    private const FOLDER_GROOM = 'groom';
    private const FOLDER_BRIDE_EDITED = 'bride_edited';
    private const FOLDER_GROOM_EDITED = 'groom_edited';
    private const FOLDER_SEASON = 'season_theme';

    protected SkinToneService $skinToneService;

    public function __construct(SkinToneService $skinToneService)
    {
        $this->skinToneService = $skinToneService;
    }

    /**
     * 🧠 Main Wedding Style Suggestion Generation
     */
    public function generateSuggestion(Request $request)
    {
        $request->validate([
            'bride_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'groom_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'season' => 'required|string|in:spring,summer,autumn,winter',
        ]);

        try {
            // 1️⃣ Upload Original Images
            $bridePath = $this->uploadFile($request->file('bride_image'), self::FOLDER_BRIDE);
            $groomPath = $this->uploadFile($request->file('groom_image'), self::FOLDER_GROOM);

            // 2️⃣ Convert to Base64 for Gemini
            $brideBase64 = base64_encode(file_get_contents(public_path($bridePath)));
            $groomBase64 = base64_encode(file_get_contents(public_path($groomPath)));

            Log::info('AI Wedding Suggestion: Starting Analysis', [
                'season' => $request->season,
                'bride_size' => strlen($brideBase64),
                'groom_size' => strlen($groomBase64),
            ]);

            // 3️⃣ Send to Gemini (SkinToneService)
            $result = $this->skinToneService->analyzeSkinTone($brideBase64, $groomBase64, $request->season);

            // 4️⃣ Optional Edited Images Save
            $brideEditedPath = $this->saveBase64Image($result['bride']['edited_image'] ?? null, self::FOLDER_BRIDE_EDITED);
            $groomEditedPath = $this->saveBase64Image($result['groom']['edited_image'] ?? null, self::FOLDER_GROOM_EDITED);
            $seasonThemePath = $this->saveBase64Image($result['season']['image'] ?? null, self::FOLDER_SEASON);

            // 5️⃣ Save to Database
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
                'message' => '🎉 Wedding style analysis completed successfully',
                'data' => $suggestion,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Wedding Suggestion Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Analysis service is temporarily unavailable. Please try again later.'
            ], 500);
        }
    }

    /**
     * 🧪 Gemini Model Testing — Multiple Model Use করতে পারবে এখানে
     */
    public function testModels()
    {
        try {
            $results = $this->skinToneService->testImageGeneration();
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

    // =============================================
    // 📁 File Handling Helper Methods
    // =============================================

    private function uploadFile($file, string $folder): string
    {
        $fileName = time() . "_" . Str::random(8) . "_{$folder}." . $file->getClientOriginalExtension();
        $directory = public_path("uploads/{$folder}");

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $fileName);
        return "uploads/{$folder}/{$fileName}";
    }

    private function saveBase64Image(?string $base64, string $folder): ?string
    {
        if (!$base64) return null;

        // Remove base64 header if exists
        if (strpos($base64, 'data:image') === 0) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        }

        $fileName = "{$folder}_" . Str::random(12) . ".png";
        $filePath = "uploads/{$folder}/{$fileName}";
        $directory = public_path("uploads/{$folder}");

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            file_put_contents(public_path($filePath), base64_decode($base64));
            return $filePath;
        } catch (\Exception $e) {
            Log::error("Failed to save base64 image", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
