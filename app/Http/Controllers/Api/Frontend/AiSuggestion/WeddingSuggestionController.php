<?php

namespace App\Http\Controllers\Api\Frontend\AiSuggestion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\WeddingSuggestionService;

class WeddingSuggestionController extends Controller
{
   private $weddingService;

    public function __construct(WeddingSuggestionService $weddingService)
    {
        $this->weddingService = $weddingService;
    }

    /**
     * Generate wedding suggestions
     */
    public function generateSuggestions(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'bride_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'groom_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'season' => 'required|in:spring,summer,autumn,winter'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Call service to generate suggestions
            $result = $this->weddingService->generateWeddingSuggestions(
                $request->file('bride_photo'),
                $request->file('groom_photo'),
                $request->season
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate suggestions: ' . $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wedding suggestions generated successfully',
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get suggestion by ID (if stored in database)
     */
    public function getSuggestion($id)
    {
        // Implementation for retrieving stored suggestion
        // You can store suggestions in database and retrieve here
        return response()->json([
            'success' => false,
            'message' => 'Not implemented yet'
        ], 501);
    }
}
