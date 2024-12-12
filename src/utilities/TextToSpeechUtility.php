<?php

namespace furbo\crafttexttospeech\utilities;

use Craft;
use craft\base\Utility;

/**
 * Text To Speech Utility utility
 */
class TextToSpeechUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('text-to-speech', 'Text-To-Speech');
    }

    static function id(): string
    {
        return 'text-to-speech-utility';
    }

    static function contentHtml(): string
    {
        return Craft::$app->view->renderTemplate('text-to-speech/_utilities.twig');
    }
}
