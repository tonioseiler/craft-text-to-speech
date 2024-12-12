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

        foreach ($settings->getSectionsEnabled() as $handle => $section) {
            $entries = Entry::find()->section($handle)->site('*')->all();
            foreach ($entries as $entry) {
                if($section['enabled']) {
                    if ($section['type'] === 'template') {
                        TextToSpeech::$plugin->textToSpeechService->generateAudioFromTemplate($entry);
                    } elseif ($section['type'] === 'fields') {
                        $fields = explode(',', $section['fields']);
                        TextToSpeech::$plugin->textToSpeechService->generateAudioFromFields($entry, $fields);
                    }
                }
            }
        }

    }

    public function actionGenerateTts()
    {
        $this->requirePostRequest();
        $params = Craft::$app->getRequest()->getBodyParams();

        $entry = Entry::find()->id($params['entryId'])->siteId($params['siteId'])->one();
        if($entry){
            $sectionSettings = TextToSpeech::getInstance()->getSettings()->getSectionByHandle($entry->section->handle);
            if($sectionSettings['enabled']) {
                if ($sectionSettings['type'] === 'template') {
                    TextToSpeech::getInstance()->textToSpeechService->generateAudioFromTemplate($entry);
                } elseif ($sectionSettings['type'] === 'fields') {
                    $fields = explode(',', $sectionSettings['fields']);
                    TextToSpeech::getInstance()->textToSpeechService->generateAudioFromFields($entry, $fields);
                }
            }
        }
        exit();
    }
}
