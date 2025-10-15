<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use App\Models\AISuggestion;
use Illuminate\Http\Request;
use App\Services\SkinToneService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AIWeddingController extends Controller
{
    protected $skinToneService;

    public function __construct(SkinToneService $skinToneService)
    {
        $this->skinToneService = $skinToneService;
    }

    public function generateSuggestion(Request $request)
    {
        $request->validate([
            'bride_image' => 'required|image|mimes:jpg,jpeg,png',
            'groom_image' => 'required|image|mimes:jpg,jpeg,png',
            'season' => 'required|string',
        ]);

        // ✅ 1. Bride image upload & store
        $brideFile = $request->file('bride_image');
        $brideName = time() . '_bride.' . $brideFile->getClientOriginalExtension();
        $bridePath = 'uploads/bride/' . $brideName;
        $brideFile->move(public_path('uploads/bride'), $brideName);

        // ✅ 2. Groom image upload & store
        $groomFile = $request->file('groom_image');
        $groomName = time() . '_groom.' . $groomFile->getClientOriginalExtension();
        $groomPath = 'uploads/groom/' . $groomName;
        $groomFile->move(public_path('uploads/groom'), $groomName);

        // ✅ 3. Convert stored images to Base64 (not temporary)
        $brideBase64 = base64_encode(file_get_contents(public_path($bridePath)));
        $groomBase64 = base64_encode(file_get_contents(public_path($groomPath)));

        try {
            // ✅ 4. Call Gemini service with stored images
            $resultJson = $this->skinToneService->analyzeSkinTone(
                $brideBase64,
                $groomBase64,
                $request->season
            );

            Log::info('Gemini Raw Response: ' . $resultJson);

            $result = json_decode($resultJson, true);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse Gemini AI response',
                ], 500);
            }

            // ✅ 5. Save to DB
            $suggestion = AISuggestion::create([
                'bride_image' => $bridePath,
                'groom_image' => $groomPath,
                'bride_skin_tone' => $result['bride']['skin_tone'] ?? null,
                'bride_color_code' => $result['bride']['color_code'] ?? null,
                'groom_skin_tone' => $result['groom']['skin_tone'] ?? null,
                'groom_color_code' => $result['groom']['color_code'] ?? null,
                'season_name' => $result['season']['name'] ?? $request->season,
                'season_palette' => json_encode($result['season']['palette'] ?? []),
                'season_description' => $result['season']['description'] ?? '',
                'season_image' => $result['season']['image'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $suggestion,
            ]);

        } catch (\Exception $e) {
            Log::error('Gemini API error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gemini API error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
