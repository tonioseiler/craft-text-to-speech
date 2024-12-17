<?php

namespace furbo\crafttexttospeech\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use craft\web\View;
use furbo\crafttexttospeech\jobs\GenerateTTSJob;
use furbo\crafttexttospeech\TextToSpeech;
use yii\web\Response;
use craft\helpers\Queue;

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
                $content = $entry->getTTSContent();
                if(!empty($content)){
                    TextToSpeech::getInstance()->textToSpeechService->executeTTSJob($entry, $content);
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
                $content = $entry->getTTSContent();
                if(!empty($content)){
                    TextToSpeech::getInstance()->textToSpeechService->executeTTSJob($entry, $content);
                }
            }
        }
        exit();
    }
}
