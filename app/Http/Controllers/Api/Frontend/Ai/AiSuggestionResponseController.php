<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use id;
use App\Models\ColorTheme;
use App\Models\AISuggestion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AisuggestionResource;

class AiSuggestionResponseController extends Controller
{
    public function getAllSuggestions()
    {
        $userId = auth()->guard('api')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $aiSuggestions = AISuggestion::where('user_id', $userId)->latest()->get();

        if ($aiSuggestions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No AI suggestions found for the user.'
            ], 404);
        }

        $aiSuggestions = $aiSuggestions->map(function ($suggestion) {
            // Decode combined_colors safely (string or array)
            $combinedColors = is_string($suggestion->combined_colors)
                ? json_decode($suggestion->combined_colors, true)
                : $suggestion->combined_colors;

            return [
                'id' => $suggestion->id,
                'user_id' => $suggestion->user_id,
                'season_name' => $suggestion->season_name,
                'season_description' => $suggestion->season_description,
                'combined_colors' => $combinedColors ?? [],
                'bride_image_url' => $suggestion->bride_image_url,
                'groom_image_url' => $suggestion->groom_image_url,
                'season_image_url' => $suggestion->season_image_url,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'AI suggestions retrieved successfully.',
            'data' => $aiSuggestions
        ], 200);
    }

    // Get details of a specific AI suggestion including its color themes
    public function relatedColorthemes($id)
    {
        $userId = auth()->guard('api')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $aiSuggestion = AISuggestion::with('colorThemes')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$aiSuggestion) {
            return response()->json([
                'success' => false,
                'message' => 'AI suggestion not found.'
            ], 404);
        }

        $colorThemes = $aiSuggestion->colorThemes->map(function ($theme) {
            // Parse color codes if stored as JSON
            $colorCodes = is_string($theme->color_codes)
                ? json_decode($theme->color_codes, true)
                : $theme->color_codes;

            return [
                'id' => $theme->id,
                'title' => $theme->title,
                'description' => $theme->description,
                'outfit_generate' => !empty($colorCodes), // keep true if there are color codes
                'color_codes' => $colorCodes,
                'created_at' => $theme->created_at,
                'updated_at' => $theme->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Color themes retrieved successfully.',
            'data' => $colorThemes,
        ], 200);
    }



    // suggestion history

    // public function suggestionHistory()
    // {
    //     $userId = auth()->guard('api')->id();
    //     if (!$userId) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized'
    //         ], 401);
    //     }

    //     // Get all AI suggestions for the user
    //     $aiSuggestions = AISuggestion::with('colorThemes')
    //         ->where('user_id', $userId)
    //         ->latest()
    //         ->get();

    //     if ($aiSuggestions->isEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No AI suggestions found for the user.'
    //         ], 404);
    //     }

    //     // Filter AI suggestions: keep only those with at least one color theme having images
    //     $aiSuggestionsWithOutfit = $aiSuggestions->filter(function ($suggestion) {
    //         return $suggestion->colorThemes->contains(function ($theme) {
    //             $images = is_string($theme->images)
    //                 ? json_decode($theme->images, true)
    //                 : $theme->images;

    //             return !empty($images); // only keep suggestions with images
    //         });
    //     });

    //     if ($aiSuggestionsWithOutfit->isEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No AI suggestions with generated outfits found.'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'AI suggestions with generated outfits retrieved successfully.',
    //         'data' => AisuggestionResource::collection($aiSuggestionsWithOutfit)
    //     ], 200);
    // }

    public function suggestionHistory()
    {
        $userId = auth()->guard('api')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $colorThemes = ColorTheme::whereHas('aiSuggestion', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->get()
            ->filter(function ($theme) {
                $images = is_string($theme->images)
                    ? json_decode($theme->images, true)
                    : $theme->images;
                return !empty($images); // only include themes with images
            })
            ->map(function ($theme) {
                return [
                    'id' => $theme->id,
                    'title' => $theme->title,
                    'description' => $theme->description,
                    'color_codes' => is_string($theme->color_codes)
                        ? json_decode($theme->color_codes, true)
                        : $theme->color_codes,
                ];
            })
            ->values(); // 🔑 reindex array

        if ($colorThemes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No color themes found with generated images.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Color themes retrieved successfully.',
            'data' => $colorThemes
        ], 200);
    }

    // 

    

}
