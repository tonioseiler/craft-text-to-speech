<?php

namespace furbo\crafttexttospeech\jobs;

use Craft;
use craft\elements\Entry;
use craft\queue\BaseJob;
use furbo\crafttexttospeech\records\ProcessLogRecord;
use furbo\crafttexttospeech\TextToSpeech;
use yii\db\Exception;

/**
 * Generate Tts Job queue job
 */
class GenerateTTSJob extends BaseJob
{

    public string $job;
    public int $entryId;
    public string $content;
    public string $siteHandle;
    public string $filename;

    /**
     * @throws Exception
     */
    function execute($queue): void
    {
        $queue->setProgress(20);
        TextToSpeech::$plugin->textToSpeechService->generateAudio($this->content, $this->siteHandle, $this->filename);
        $queue->setProgress(100);
    }

    protected function defaultDescription(): ?string
    {
        return "Generate TTS for $this->filename";
    }
}
