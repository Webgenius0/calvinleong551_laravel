<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use App\Models\AISuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ApiResponseController extends Controller
{
   public function getUserAISuggestions()
{
    try {
        $userId = auth()->id();

        // Fetch all AI suggestions with related color themes for this user
        $suggestions = AISuggestion::with('colorThemes')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        if ($suggestions->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No AI suggestions found for this user.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'AI suggestions fetched successfully.',
            'data' => $suggestions,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Failed to fetch AI suggestions', ['error' => $e->getMessage()]);
        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

}
