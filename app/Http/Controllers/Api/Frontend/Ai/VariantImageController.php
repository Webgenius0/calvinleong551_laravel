<?php

namespace App\Http\Controllers\Api\Frontend\Ai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\VariantImageService;

class VariantImageController extends Controller
{
   protected VariantImageService $variantImageService;

    public function __construct(VariantImageService $variantImageService)
    {
        $this->variantImageService = $variantImageService;
    }

    /**
     * Generate variant images for a specific color theme.
     */
    public function generateVariants(Request $request)
    {
        $request->validate([
            'color_theme_id' => 'required|integer|exists:color_themes,id',
        ]);

        try {
            set_time_limit(300); // 5 minutes for image generation

            $result = $this->variantImageService->generateVariantImagesForTheme($request->color_theme_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null
                ], 500);
            }

            Log::info('Variant images generation completed', [
                'color_theme_id' => $request->color_theme_id,
                'image_count' => count($result['data']['images'])
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Variant images generation error', [
                'color_theme_id' => $request->color_theme_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate variant images. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
