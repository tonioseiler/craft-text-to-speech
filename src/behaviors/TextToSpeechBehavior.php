<?php

namespace furbo\crafttexttospeech\behaviors;

use craft\elements\Asset;
use furbo\crafttexttospeech\TextToSpeech;
use yii\base\Behavior;

/**
 * Tex To Speech behavior
 */
class TextToSpeechBehavior extends Behavior
{
    public function getTTSAudio(): string{
        $section = $this->owner->section;
        $entry = $this->owner;

        $siteHandle = $entry->site->handle;
        $filename = TextToSpeech::getInstance()->textToSpeechService->getFileName($entry) . ".mp3";

        // Find asset by filename
        $asset = Asset::find()
            ->filename($filename)
            ->one();

        if(is_null($asset)){
            return "";
        }

        return $asset->getUrl() . "?v=" . $asset->dateModified->getTimestamp();
    }

    public function getTTSContent(): string
    {
        $content = '';
        $entry = $this->owner;
        $sectionSettings = TextToSpeech::getInstance()->getSettings()->getSectionByHandle($entry->section->handle);
        if ($sectionSettings['type'] === 'template') {
            $content = TextToSpeech::getInstance()->textToSpeechService->getContentFromTemplate($entry);
        } elseif ($sectionSettings['type'] === 'fields') {
            $fields = explode(',', $sectionSettings['fields']);
            $content = TextToSpeech::getInstance()->textToSpeechService->getContentFromFields($entry, $fields);
        }

        return $content;
    }

    /**
     * @throws \Throwable
     */
    public function deleteTTSAudio()
    {
        $section = $this->owner->section;
        $entry = $this->owner;

        $siteHandle = $entry->site->handle;
        $filename = TextToSpeech::getInstance()->textToSpeechService->getFileName($entry) . ".mp3";

        // Find asset by filename
        $asset = Asset::find()
            ->filename($filename)
            ->one();

        if(!is_null($asset)){
            return \Craft::$app->getElements()->deleteElementById($asset->id);
        }

        return false;
    }
}
