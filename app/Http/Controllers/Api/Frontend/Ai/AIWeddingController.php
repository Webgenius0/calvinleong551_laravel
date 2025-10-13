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
            'bride_image' => 'required|image',
            'groom_image' => 'required|image',
            'season' => 'required|string',
        ]);

        // ✅ 1. Bride & Groom image save locally
        if ($request->hasFile('bride_image')) {
            $brideImage = $request->file('bride_image');
            $brideName = time() . '_bride.' . $brideImage->getClientOriginalExtension();
            $brideImage->move(public_path('uploads/bride'), $brideName);
            $bridePath = 'uploads/bride/' . $brideName;
        }

        if ($request->hasFile('groom_image')) {
            $groomImage = $request->file('groom_image');
            $groomName = time() . '_groom.' . $groomImage->getClientOriginalExtension();
            $groomImage->move(public_path('uploads/groom'), $groomName);
            $groomPath = 'uploads/groom/' . $groomName;
        }


        // ✅ 2. Convert to Base64 for API
        $brideBase64 = base64_encode(file_get_contents($bridePath));
        $groomBase64 = base64_encode(file_get_contents($groomPath));

        // ✅ 3. Call Gemini API
        $resultJson = $this->skinToneService->analyzeSkinTone($brideBase64, $groomBase64, $request->season);
        Log::info('Gemini Raw Response: ' . $resultJson);

        $result = json_decode($resultJson, true);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse AI response',
            ], 500);
        }

        // ✅ 4. Save response + images to DB
        $suggestion = AISuggestion::create([
            'bride_image' => $bridePath,
            'groom_image' => $groomPath,
            'bride_skin_tone' => $result['bride']['skin_tone'],
            'bride_color_code' => $result['bride']['color_code'],
            'groom_skin_tone' => $result['groom']['skin_tone'],
            'groom_color_code' => $result['groom']['color_code'],
            'season_name' => $result['season']['name'],
            'season_palette' => json_encode($result['season']['palette']),
            'season_description' => $result['season']['description'],
            'season_image' => $result['season']['image'] ?? null, // 🆕 season image save
        ]);

        return response()->json([
            'success' => true,
            'data' => $suggestion,
        ]);
    }
}
