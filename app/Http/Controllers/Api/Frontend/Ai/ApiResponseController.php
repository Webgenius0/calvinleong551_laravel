<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use App\Models\AISuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\ColorTheme;

class ApiResponseController extends Controller
{
    public function getUserAISuggestions()
    {
        try {
            $userId = auth()->id();

            $suggestions = AISuggestion::with(['colorThemes' => function($query) {
                $query->select('id', 'ai_suggestion_id', 'title', 'description', 'color_codes', 'images', 'created_at', 'updated_at');
            }])
            ->where('user_id', $userId)
            ->latest()
            ->get();

            // Transform the data to properly format all JSON fields
            $formattedData = $suggestions->map(function ($suggestion) {
                return [
                    'id' => $suggestion->id,
                    'user_id' => $suggestion->user_id,
                    'combined_colors' => $this->parseJsonField($suggestion->combined_colors),
                    'bride_skin_tone' => $suggestion->bride_skin_tone,
                    'bride_color_code' => $this->parseJsonField($suggestion->bride_color_code),
                    'groom_skin_tone' => $suggestion->groom_skin_tone,
                    'groom_color_code' => $this->parseJsonField($suggestion->groom_color_code),
                    'season_name' => $suggestion->season_name,
                    'season_palette' => $this->parseJsonField($suggestion->season_palette),
                    'season_description' => $suggestion->season_description,
                    'bride_image_url' => $suggestion->bride_image_url,
                    'groom_image_url' => $suggestion->groom_image_url,
                    'season_image_url' => $suggestion->season_image_url,                   
                    'color_themes' => $suggestion->colorThemes->map(function ($theme) {
                        return [
                            'id' => $theme->id,
                            'ai_suggestion_id' => $theme->ai_suggestion_id,
                            'title' => $theme->title,
                            'description' => $theme->description,
                            'color_codes' => $this->parseJsonField($theme->color_codes),                          
                            'created_at' => $theme->created_at,
                            'updated_at' => $theme->updated_at,
                        ];
                        $images = $this->parseImagesField($theme->getRawOriginal('images'));
                    if (!empty($images)) {
                        $themeData['images'] = $images;
                    }

                    return $themeData;
                    })
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'AI suggestions fetched successfully.',
                'data' => $formattedData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching AI suggestions: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch AI suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse JSON fields that might be stored as strings
     */
    private function parseJsonField($value)
    {
        // If already array, return as is
        if (is_array($value)) {
            return $value;
        }

        // If null or empty, return empty array
        if (empty($value)) {
            return [];
        }

        // Handle string values
        if (is_string($value)) {
            // Debug logging
            Log::debug('Parsing JSON field:', ['raw_value' => $value]);
            
            // Remove extra slashes and decode
            $cleaned = stripslashes($value);
            $decoded = json_decode($cleaned, true);
            
            // Check if decoding was successful
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                Log::debug('Successfully parsed JSON field:', ['parsed_value' => $decoded]);
                return $decoded;
            }
            
            // If first decode failed or returned string, try direct array conversion
            if (is_string($decoded)) {
                $secondDecode = json_decode($decoded, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($secondDecode)) {
                    Log::debug('Successfully parsed JSON field (second attempt):', ['parsed_value' => $secondDecode]);
                    return $secondDecode;
                }
            }
            
            // Fallback: try to manually parse the array format
            $manualParse = $this->manuallyParseColorArray($value);
            if (!empty($manualParse)) {
                Log::debug('Successfully manually parsed color array:', ['parsed_value' => $manualParse]);
                return $manualParse;
            }
            
            Log::debug('Failed to parse JSON field, returning empty array');
            return [];
        }

        return [];
    }

    /**
     * Manually parse color array from string format like "[\"#ffffff\", \"#f0f0f0\"]"
     */
    private function manuallyParseColorArray($value)
    {
        if (!is_string($value)) {
            return [];
        }

        // Remove brackets and quotes
        $cleaned = trim($value, '[]"');
        
        // Split by comma and clean each item
        $items = explode(',', $cleaned);
        $result = [];
        
        foreach ($items as $item) {
            $cleanItem = trim($item, ' "');
            if (!empty($cleanItem)) {
                $result[] = $cleanItem;
            }
        }
        
        return $result;
    }

    /**
     * Parse images field from double-encoded JSON to array
     */
    private function parseImagesField($value)
    {
        // If already array, return as is
        if (is_array($value)) {
            return $value;
        }

        // If empty or null, return empty array
        if (empty($value) || $value === '[]' || $value === '"[]"') {
            return [];
        }

        // Handle string values
        if (is_string($value)) {
            // Remove extra slashes and quotes
            $cleaned = trim($value, '"');
            $cleaned = stripslashes($cleaned);
            
            // First decode
            $firstDecode = json_decode($cleaned, true);
            
            // If first decode returns a string, decode again
            if (is_string($firstDecode)) {
                $secondDecode = json_decode($firstDecode, true);
                $result = is_array($secondDecode) ? $secondDecode : [];
            } 
            // If first decode returns array, use it
            elseif (is_array($firstDecode)) {
                $result = $firstDecode;
            }
            // Fallback
            else {
                $result = [];
            }

            // Ensure URLs are properly formatted
            return $this->formatImageUrls($result);
        }

        return [];
    }

    /**
     * Format image URLs to be absolute
     */
    private function formatImageUrls($images)
    {
        if (!is_array($images)) {
            return [];
        }

        return array_map(function ($image) {
            if (is_array($image) && isset($image['url'])) {
                // Ensure URL is properly formatted
                if (!empty($image['url'])) {
                    // Fix escaped URLs
                    $image['url'] = str_replace('\\/', '/', $image['url']);
                    
                    // Ensure it's absolute URL
                    if (!str_starts_with($image['url'], 'http')) {
                        $image['url'] = url($image['url']);
                    }
                }
            }
            return $image;
        }, $images);
    }


    // details


public function getAISuggestionDetails($id)
{
    $userId = auth()->id();
    if (!$userId) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $theme = ColorTheme::find($id);

    if (!$theme) {
        return response()->json([
            'status' => false,
            'message' => 'No color theme found for the given ID.',
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'Color theme fetched successfully.',
        'data' => [
            'id' => $theme->id,
            'ai_suggestion_id' => $theme->ai_suggestion_id,
            'title' => $theme->title,
            'description' => $theme->description,
            'color_codes' => $this->parseJsonField($theme->color_codes),
            'images' => $this->parseImagesField($theme->getRawOriginal('images')),
        ],
    ], 200);
}



}