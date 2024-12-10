<?php

namespace furbo\crafttexttospeech\jobs;

use Craft;
use craft\queue\BaseJob;
use furbo\crafttexttospeech\TextToSpeech;

/**
 * Generate Tts Job queue job
 */
class GenerateTTSJob extends BaseJob
{

    public string $content;
    public string $siteHandle;
    public string $filename;

    function execute($queue): void
    {
        TextToSpeech::$plugin->textToSpeechService->generateAudio($this->content, $this->siteHandle, $this->filename);
    }

    protected function defaultDescription(): ?string
    {
        return "Generate TTS for $this->filename";
    }
}
