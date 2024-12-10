<?php

namespace furbo\crafttexttospeech\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use craft\web\View;
use furbo\crafttexttospeech\TextToSpeech;
use yii\web\Response;

/**
 * Text To Speech controller
 */
class TextToSpeechController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * text-to-speech/text-to-speech action
     */

    public function actionGenerateAllTts()
    {
        $this->requirePostRequest();

        $settings = TextToSpeech::$plugin->getSettings();

        foreach ($settings->getSectionsWithTemplate() as $handle => $section) {
            $template = $section['template'];
            $entries = Entry::find()->section($handle)->all();
            foreach ($entries as $entry) {
                $audio = TextToSpeech::$plugin->textToSpeechService->generateAudioFromTemplate($entry);
            }
        }

    }
}
