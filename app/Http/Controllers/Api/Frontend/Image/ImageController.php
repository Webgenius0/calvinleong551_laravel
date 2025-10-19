<?php

namespace App\Http\Controllers\Api\Frontend\Image;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ImageController extends Controller
{
   // Gemini API config
    private string $apiKey;
    private string $apiEndpoint;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');

        // Gemini image generation endpoint
         $this->apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateImage';

        if (!$this->apiKey) {
            throw new Exception("GEMINI_API_KEY not set in .env");
        }
    }

    /**
     * Generate image from prompt
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
            'size' => 'nullable|string|in:256x256,512x512,1024x1024'
        ]);

        $prompt = $request->input('prompt');
        $size = $request->input('size', '512x512');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json'
            ])->post($this->apiEndpoint, [
                'prompt' => $prompt,
                'size' => $size
            ]);

            Log::info("Gemini API Response: " . $response->body());

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Gemini API returned an error',
                'details' => $response->body()
            ], $response->status());
        } catch (Exception $e) {
            Log::error("Gemini Image Generation Failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
