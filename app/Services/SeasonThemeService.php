<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SeasonThemeService
{
    private SuggestionCallerService $suggestionCaller;
    private SeasonImageGeneratorService $seasonImageGenerator;
    private PromptProviderService $promptProvider;

    public function __construct(
        SuggestionCallerService $suggestionCaller,
        SeasonImageGeneratorService $seasonImageGenerator,
        PromptProviderService $promptProvider
    ) {
        $this->suggestionCaller = $suggestionCaller;
        $this->seasonImageGenerator = $seasonImageGenerator;
        $this->promptProvider = $promptProvider;
    }

    /**
     * Generates season theme data.
     * 
     * @param string $season Season name
     * @param array $brideData Bride data
     * @param array $groomData Groom data
     * @return array Theme data
     */
    public function generate(string $season, array $brideData, array $groomData): array
    {
        $seasonImageBase64 = $this->seasonImageGenerator->generate($season, $brideData, $groomData);

        $palettePrompt = $this->promptProvider->getSeasonPalettePrompt($season, $brideData, $groomData);
        $paletteResponse = $this->suggestionCaller->call($palettePrompt);
        $palette = json_decode($paletteResponse ?? '[]', true) ?: [];

        $descriptionPrompt = $this->promptProvider->getSeasonDescriptionPrompt($season, $brideData, $groomData);
        $descriptionResponse = $this->suggestionCaller->call($descriptionPrompt);

        return [
            'image' => $seasonImageBase64,
            'palette' => $palette,
            'description' => trim($descriptionResponse ?? $this->promptProvider->getSeasonDescription($season))
        ];
    }
}