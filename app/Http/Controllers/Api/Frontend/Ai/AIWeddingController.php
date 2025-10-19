<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use App\Http\Controllers\Controller;
use App\Models\AISuggestion;
use App\Services\SkinToneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIWeddingController extends Controller
{
    /** 
     * Bride, Groom, Seasonal ইমেজ রাখার জন্য কনস্ট্যান্ট ফোল্ডার পাথ
     */
    private const FOLDER_BRIDE         = 'uploads/bride';
    private const FOLDER_GROOM         = 'uploads/groom';
    private const FOLDER_BRIDE_EDITED  = 'uploads/bride_edited';
    private const FOLDER_GROOM_EDITED  = 'uploads/groom_edited';
    private const FOLDER_SEASON        = 'uploads/season_theme';

    protected SkinToneService $skinToneService;

    public function __construct(SkinToneService $skinToneService)
    {
        $this->skinToneService = $skinToneService;
    }

    /**
     * 🧠 Main Wedding Style Suggestion Generation
     * bride + groom look suggestion (based on season/theme/skin tone)
     */
    public function generateSuggestion(Request $request)
    {
        try {
            $season = $request->input('season', 'winter');
            $theme = $request->input('theme', 'traditional');
            $skinTone = $request->input('skin_tone', 'fair');

            // 👉 Suggest bridal & groom look using SkinToneService
            $look = $this->skinToneService->suggestLook($season, $theme, $skinTone);

            // 👉 Optional: Save suggestion for history/logging
            AISuggestion::create([
                'season'          => $season,
                'theme'           => $theme,
                'skin_tone'       => $skinTone,
                'suggestion_data' => $look,
            ]);

            return response()->json([
                'success' => true,
                'data' => $look
            ]);

        } catch (\Exception $e) {
            Log::error('Wedding Style Suggestion Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate wedding look suggestion'
            ], 500);
        }
    }
}
