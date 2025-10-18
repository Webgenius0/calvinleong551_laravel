<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SkinToneService
{
    protected string $apiKey;
    protected string $apiEndpoint;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');

        if (!$this->apiKey) {
            throw new Exception('GEMINI_API_KEY not set in .env');
        }

        // Use the regular generateContent endpoint, NOT streamGenerateContent
        $this->apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        
        $this->validateApiKey();
    }

    private function validateApiKey(): void
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiEndpoint . '?key=' . $this->apiKey, [
                    'contents' => [['parts' => [['text' => 'Hello']]]]
                ]);

            if ($response->failed()) {
                $error = $response->json()['error'] ?? ['message' => 'Unknown error'];
                throw new Exception("Invalid GEMINI_API_KEY: " . json_encode($error));
            }

            Log::info('API Key validation successful');

        } catch (\Exception $e) {
            throw new Exception("API Key validation failed: " . $e->getMessage());
        }
    }

    /**
     * Main method to analyze skin tone
     */
    public function analyzeSkinTone(string $brideBase64, string $groomBase64, string $season): array
    {
        try {
            // Get skin tone analysis
            $analysis = $this->analyzeSkinTones($brideBase64, $groomBase64, $season);

            // Set image fields to null since image generation isn't working
            $analysis['bride']['edited_image'] = null;
            $analysis['groom']['edited_image'] = null;
            $analysis['season']['image'] = null;

            Log::info('Skin tone analysis completed successfully', [
                'bride_skin_tone' => $analysis['bride']['skin_tone'],
                'groom_skin_tone' => $analysis['groom']['skin_tone']
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Skin tone analysis failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze skin tones and get color recommendations
     */
    private function analyzeSkinTones(string $brideBase64, string $groomBase64, string $season): array
    {
        $prompt = "You are a professional wedding stylist and color analyst. 

ANALYZE these two images (first is bride, second is groom) and provide:

1. ACCURATE skin tone classification for both (e.g., Warm Light, Cool Medium, Olive Tan, Deep Rich)
2. EXACT HEX color codes that match their skin undertones (provide 4 colors for each)
3. 4 complementary wedding colors for each that would look harmonious
4. Seasonal color palette for {$season} wedding with 6 colors

CRITICAL: Return ONLY valid JSON format. No additional text or markdown.

REQUIRED JSON FORMAT:
{
  \"bride\": {
    \"skin_tone\": \"e.g., Warm Light Beige\",
    \"color_code\": [\"#HEX1\", \"#HEX2\", \"#HEX3\", \"#HEX4\"],
    \"matching_colors\": [\"Color1\", \"Color2\", \"Color3\", \"Color4\"],
    \"description\": \"Professional analysis of bride's skin tone and color recommendations\"
  },
  \"groom\": {
    \"skin_tone\": \"e.g., Cool Olive Tan\", 
    \"color_code\": [\"#HEX1\", \"#HEX2\", \"#HEX3\", \"#HEX4\"],
    \"matching_colors\": [\"Color1\", \"Color2\", \"Color3\", \"Color4\"],
    \"description\": \"Professional analysis of groom's skin tone and color recommendations\"
  },
  \"season\": {
    \"name\": \"{$season}\",
    \"palette\": [\"#HEX1\", \"#HEX2\", \"#HEX3\", \"#HEX4\", \"#HEX5\", \"#HEX6\"],
    \"description\": \"Beautiful {$season} wedding color palette that complements both skin tones\"
  }
}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => 'image/jpeg', 
                                'data' => $brideBase64
                            ]
                        ],
                        [
                            'inline_data' => [
                                'mime_type' => 'image/jpeg', 
                                'data' => $groomBase64
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 2048,
            ]
        ];

        Log::info('Sending analysis request to Gemini API', [
            'season' => $season,
            'endpoint' => $this->apiEndpoint
        ]);

        $response = Http::timeout(120)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiEndpoint . '?key=' . $this->apiKey, $payload);

        if ($response->failed()) {
            $error = $response->json()['error'] ?? ['message' => 'Unknown error'];
            Log::error('Gemini API Request Failed', [
                'status' => $response->status(),
                'error' => $error
            ]);
            throw new Exception('Gemini API Error: ' . json_encode($error));
        }

        $responseData = $response->json();
        
        // Debug the response structure
        Log::info('API Response keys:', array_keys($responseData));

        // Check if we have the expected response structure
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            Log::error('Unexpected API response structure', $responseData);
            throw new Exception('Unexpected response structure from Gemini API');
        }

        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'];
        
        if (empty($rawText)) {
            Log::error('Empty text in API response', $responseData);
            throw new Exception('Empty response text from Gemini API');
        }

        Log::info('Received response from Gemini API', [
            'response_length' => strlen($rawText),
            'response_preview' => substr($rawText, 0, 200)
        ]);
        
        // Extract JSON from response
        $json = $this->extractJson($rawText);
        $parsed = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Parse Error', [
                'error' => json_last_error_msg(),
                'raw_text' => $rawText,
                'extracted_json' => $json
            ]);
            throw new Exception('Invalid JSON from Gemini analysis: ' . json_last_error_msg());
        }

        return $parsed;
    }

    /**
     * Extract JSON from text response
     */
    private function extractJson(string $text): string
    {
        // First, try to find JSON between ```json ``` markers
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            return $matches[1];
        }

        // Then try between ``` ``` markers
        if (preg_match('/```\s*(.*?)\s*```/s', $text, $matches)) {
            return $matches[1];
        }

        // Finally, look for the first { and last }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        
        if ($start === false || $end === false) {
            throw new Exception('JSON not found in Gemini response. Response: ' . substr($text, 0, 500));
        }
        
        return substr($text, $start, $end - $start + 1);
    }

    /**
     * Test if gemini-2.5-flash-image works for image generation
     */
    public function testImageGeneration(): array
    {
        try {
            $imageEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent';
            
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Generate a simple image of a red apple on a table']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE']
                ]
            ];

            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($imageEndpoint . '?key=' . $this->apiKey, $payload);

            $result = [
                'model' => 'gemini-2.5-flash-image',
                'success' => $response->successful(),
                'status' => $response->status()
            ];

            if ($response->failed()) {
                $error = $response->json()['error'] ?? ['message' => 'Unknown error'];
                $result['error'] = $error;
            } else {
                $responseData = $response->json();
                $result['has_image_data'] = false;
                
                // Check if response contains image data
                if (isset($responseData['candidates'][0]['content']['parts'])) {
                    foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                        if (isset($part['inlineData'])) {
                            $result['has_image_data'] = true;
                            break;
                        }
                    }
                }
                
                $result['response_structure'] = array_keys($responseData);
            }

            Log::info('Image generation test result', $result);
            return $result;

        } catch (\Exception $e) {
            Log::error('Image generation test failed: ' . $e->getMessage());
            return [
                'model' => 'gemini-2.5-flash-image',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get API status
     */
    public function getApiStatus(): array
    {
        return [
            'text_analysis_model' => 'gemini-2.5-flash-image',
            'text_analysis_status' => 'working',
            'image_generation_status' => 'unavailable',
            'message' => 'Skin tone analysis is working. Image generation requires additional setup.'
        ];
    }
}