<?php

namespace furbo\crafttexttospeech\behaviors;

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
        $sectionSettings = TextToSpeech::getInstance()->getSettings()->getSectionByHandle($section->handle);
        $template = $sectionSettings['template'] ?? null;
        if($template){
            //only get the url
        }
        return "https://example.com/tts/" . $section->handle . "/" . $entry->slug;
    }
}
