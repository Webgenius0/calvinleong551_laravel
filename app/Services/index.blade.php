<?php

namespace App\Services;

class PromptProviderService
{
    /**
     * Gets bride analysis prompt.
     *
     * @return string Prompt
     */
    public function getBridePrompt(): string
    {
        return "Analyze this image of a bride. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 3-5 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"warm\", \"colors\": [\"#ffcc99\", \"#ffffff\"]}";
    }

    /**
     * Gets groom analysis prompt.
     *
     * @return string Prompt
     */
    public function getGroomPrompt(): string
    {
        return "Analyze this image of a groom. Classify skin tone as 'warm', 'cool', or 'neutral'. Extract top 3-5 dominant colors from skin/outfit (hex codes). Respond ONLY in JSON: {\"skin_tone\": \"cool\", \"colors\": [\"#a8e6cf\", \"#000000\"]}";
    }

    /**
     * Gets season image prompt.
     *
     * @param string $season Season
     * @param array $brideData Bride data
     * @param array $groomData Groom data
     * @return string Prompt
     */
    public function getSeasonImagePrompt(string $season, array $brideData, array $groomData): string
    {
        $brideTone = $brideData['skin_tone'] ?? 'neutral';
        $groomTone = $groomData['skin_tone'] ?? 'neutral';
        $colors = implode(', ', array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []));

        $seasonDetails = match ($season) {
            'spring' => 'blossoming garden with pastel flowers and soft sunlight',
            'summer' => 'sun-drenched beach with vibrant tropical blooms and gentle ocean waves',
            'autumn' => 'golden forest path with falling leaves and warm harvest tones',
            'winter' => 'serene snowy landscape with evergreen garlands and twinkling lights',
            default => 'beautiful seasonal romantic landscape'
        };

        return "Create a high-quality, photorealistic 1024x1024 PNG image of a romantic wedding scene for the {$season} season using Nano Banana style. 
        Incorporate skin tones: bride {$brideTone}, groom {$groomTone}. 
        Use dominant colors: {$colors} in outfits and accents. 
        Scene: {$seasonDetails}. 
        Composition: Bride and groom standing elegantly side by side with visible personal space between them (at least arm's length apart), facing the camera or looking at each other with loving and joyful smiles, hands relaxed at sides or clasped in front. 
        STRICTLY NO physical contact of any kind: no hugging, no embracing, no hand-holding, no arms around each other, no touching, no kissing, no foreheads touching, no close face-to-face posing. 
        Maintain a modest, elegant, and romantic atmosphere through expressions and composition only. 
        Dreamy bokeh background, golden hour lighting for warmth, ultra-detailed fabrics and nature elements, emotional and joyful vibe. 
        No text, logos, or distortions. 
        Generate an image as part of the response.";
    }

    /**
     * Gets season palette prompt.
     *
     * @param string $season Season
     * @param array $brideData Bride data
     * @param array $groomData Groom data
     * @return string Prompt
     */
    public function getSeasonPalettePrompt(string $season, array $brideData, array $groomData): string
    {
        $colors = implode(', ', array_merge($brideData['colors'] ?? [], $groomData['colors'] ?? []));

        return "For a {$season} wedding theme, based on these colors {$colors}, suggest a 5-color palette with hex codes suitable for decor and outfits. Include primary, secondary, accent, neutral, and highlight. Respond ONLY with JSON array: [\"#ff6b6b\", \"#4ecdc4\", \"#45b7d1\", \"#f9ca24\", \"#f0932b\"]";
    }

    /**
     * Gets season description prompt.
     *
     * @param string $season Season
     * @param array $brideData Bride data
     * @param array $groomData Groom data
     * @return string Prompt
     */
    public function getSeasonDescriptionPrompt(string $season, array $brideData, array $groomData): string
    {
        return "Write a short, romantic description (2-3 sentences) of a {$season} wedding theme, incorporating neutral skin tones and white colors for harmony. Make it inspiring and concise.";
    }

    /**
     * Gets static season description.
     *
     * @param string $season Season
     * @return string Description
     */
    public function getSeasonDescription(string $season): string
    {
        $descriptions = [
            'spring' => 'Spring weddings bloom with fresh flowers and soft pastels, symbolizing new beginnings and joyful renewal.',
            'summer' => 'Summer celebrations shine with vibrant energy, beachside vows, and sun-kissed memories under endless blue skies.',
            'autumn' => 'Autumn nuptials embrace cozy warmth, golden foliage, and harvest hues for a timeless, earthy romance.',
            'winter' => 'Winter unions sparkle with elegant whites, twinkling lights, and heartfelt toasts amid a magical snowy embrace.'
        ];

        return $descriptions[$season] ?? 'A beautiful seasonal wedding theme.';
    }
<div style="display:flex; gap:12px;">
  <div style="width:80px;height:80px;background:#6A5ACD;"></div>
  <div style="width:80px;height:80px;background:#DCDCDC;"></div>
  <div style="width:80px;height:80px;background:#800020;"></div>
  <div style="width:80px;height:80px;background:#F5DEB3;"></div>
  <div style="width:80px;height:80px;background:#F5F5F5;"></div>
</div>


}