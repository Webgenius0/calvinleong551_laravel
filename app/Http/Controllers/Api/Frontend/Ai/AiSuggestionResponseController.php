<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use id;
use App\Models\Favourite;
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

    // Step 1: Get all favourite color_theme_ids for this user
    $favouriteIds = Favourite::where('user_id', $userId)
        ->pluck('color_theme_id')
        ->toArray();

    // Step 2: Build the response with extra flags
    $colorThemes = $aiSuggestion->colorThemes->map(function ($theme) use ($favouriteIds) {
        $colorCodes = is_string($theme->color_codes)
            ? json_decode($theme->color_codes, true)
            : $theme->color_codes;

        $colorImages = is_string($theme->images)
            ? json_decode($theme->images, true)
            : $theme->images;

        return [
            'id' => $theme->id,
            'title' => $theme->title,
            'description' => $theme->description,
            'outfit_generate' => !empty($colorImages),
            'is_favourite' => in_array($theme->id, $favouriteIds), // 👈 check favourite
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

    public function response($id)
    {
        $userId = auth()->guard('api')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $theme = AISuggestion::where('id', $id)->where('user_id', $userId)->first();

        if (!$theme) {
            return response()->json([
                'success' => false,
                'message' => 'Color theme not found.'
            ], 404);
        }
        // Prepare cleaned data
        $data = [
            'id' => $theme->id,
            'bride_skin_tone' => $theme->bride_skin_tone ?? null,
            'bride_image_url' => $theme->bride_image_url ?? null,
            'bride_color_code' => $theme->bride_color_code ? json_decode($theme->bride_color_code, true) : [],
            'groom_skin_tone' => $theme->groom_skin_tone ?? null,
            'groom_image_url' => $theme->groom_image_url ?? null,
            'groom_color_code' => $theme->groom_color_code ? json_decode($theme->groom_color_code, true) : [],
            'season_name' => $theme->season_name ?? null,
            'season_image_url' => $theme->season_image_url ?? null,
            'season_description' => $theme->season_description ?? null,
            'season_palette' => $theme->season_palette ? json_decode($theme->season_palette, true) : [],
            'combined_colors' => $theme->combined_colors ? json_decode($theme->combined_colors, true) : [],
        ];

        // Remove null image fields
        $data = array_filter($data, function ($value, $key) {
            if (str_ends_with($key, '_image_url')) {
                return !empty($value);
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        return response()->json([
            'success' => true,
            'message' => 'Color theme retrieved successfully.',
            'data' => $data
        ], 200);
    }

    // favourite color theme function
    // public function addFavouriteColorTheme(Request $request)
    // {
    //     $userId = auth()->guard('api')->id();
    //     if($userId === null) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized'
    //         ], 401);
    //     }

    //     if (!$userId) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized'
    //         ], 401);
    //     }

    //     $request->validate([
    //         'color_theme_id' => 'required|string',
    //     ]);

    //     $favouriteExists = Favourite::where('user_id', $userId)
    //         ->where('color_theme_id', $request->color_theme_id)
    //         ->exists();

    //     if ($favouriteExists) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Color theme already added to favourites.'
    //         ], 409);
    //     }

    //     $favourite = Favourite::create([
    //         'user_id' => $userId,
    //         'color_theme_id' => $request->color_theme_id,
    //     ]);

    //     $data=[
    //         'id' => $favourite->id,
    //         'user_id' => $favourite->user_id,
    //         'color_theme_id' => $favourite->color_theme_id,
    //     ];

    //     return response()->json([
    //         'success' => true,
    //         'code' => 200,
    //         'message' => 'Color theme added to favourites successfully.',
    //         'data' => $data,
    //     ], 201);
    // }

    public function addFavouriteColorTheme(Request $request)
{
    $userId = auth()->guard('api')->id();

    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'color_theme_id' => 'required|string',
    ]);

    // Check if already favourited
    $existingFavourite = Favourite::where('user_id', $userId)
        ->where('color_theme_id', $request->color_theme_id)
        ->first();

    if ($existingFavourite) {

        // Delete if exists (REMOVE favourite)
        $existingFavourite->delete();

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Color theme removed from favourites.',
            'data' => [
                'color_theme_id' => $request->color_theme_id,
                'is_favourite' => false
            ]
        ], 200);
    }

    // Add new favourite
    $favourite = Favourite::create([
        'user_id' => $userId,
        'color_theme_id' => $request->color_theme_id,
    ]);

    return response()->json([
        'success' => true,
        'code' => 201,
        'message' => 'Color theme added to favourites successfully.',
        'data' => [
            'id' => $favourite->id,
            'user_id' => $favourite->user_id,
            'color_theme_id' => $favourite->color_theme_id,
            'is_favourite' => true
        ],
    ], 201);
}


    // get favourite color themes

    public function getFavouriteColorThemes()
    {
        $userId = auth()->guard('api')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $favourites = Favourite::where('user_id', $userId)->with('colorTheme')->get();

        $favouriteThemes = $favourites->map(function ($favourite) {
            $theme = $favourite->colorTheme;
            return [
                'id' => $favourite->id,
                'theme_id' => $theme->id,
                'title' => $theme->title,
                'is_favourite' =>true,
                'description' => $theme->description,
                'color_codes' => is_string($theme->color_codes)
                    ? json_decode($theme->color_codes, true)
                    : $theme->color_codes,
                'outfit_generate' => !empty($theme->images),
            ];
        });

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Favourite color themes retrieved successfully.',
            'data' => $favouriteThemes
        ]);
    }

    // Remove favourite color theme function (optional)
    public function removeFavouriteColorTheme(Request $request, $id)
    {
        $userId = auth()->guard('api')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $favourite = Favourite::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$favourite) {
            return response()->json([
                'success' => false,
                'message' => 'Favourite not found.'
            ], 404);
        }

        $favourite->delete();

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Favourite color theme removed successfully.'
        ]);
    }
}
