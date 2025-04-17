<?php

namespace furbo\crafttexttospeech;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineHtmlEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\services\Utilities;
use craft\web\View;
use furbo\crafttexttospeech\behaviors\TextToSpeechBehavior;
use furbo\crafttexttospeech\elements\actions\DeleteAudio;
use furbo\crafttexttospeech\elements\actions\GenerateAudio;
use furbo\crafttexttospeech\models\Settings;
use furbo\crafttexttospeech\records\ProcessLogRecord;
use furbo\crafttexttospeech\services\TextToSpeechService;
use furbo\crafttexttospeech\utilities\TextToSpeechUtility;
use yii\base\Event;
use craft\queue\Queue;
use yii\queue\PushEvent;
use yii\queue\ExecEvent;

/**
 * Text‐to‐Speech plugin
 *
 * @method static TextToSpeech getInstance()
 * @method Settings getSettings()
 * @author Furbo GmbH <support@furbo.ch>
 * @copyright Furbo GmbH
 * @license MIT
 * @property-read TextToSpeechService $textToSpeechService
 */
class TextToSpeech extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static $plugin;

    public static function config(): array
    {
        return [
            'components' => ['textToSpeechService' => TextToSpeechService::class],
        ];
    }

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('text-to-speech/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {

        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_ACTIONS,
            function (RegisterElementActionsEvent $event) {
                $event->actions[] = GenerateAudio::class;
                $event->actions[] = DeleteAudio::class;
            }
        );

        // Register the new column
        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function (RegisterElementTableAttributesEvent $event) {
                $event->tableAttributes['ttsAudio'] = ['label' => 'TTS Audio'];
            }
        );

        // Define the logic to return "Yes" or "No"
        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ATTRIBUTE_HTML,
            function (Event $event) {
                if ($event->attribute === 'ttsAudio') {
                    $entry = $event->sender;
                    $event->html = $entry->getTTSAudio() ? '<span class="status green"></span>' : '<span class="status red"></span>';
                }
            }
        );

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_SIDEBAR_HTML,
            function (DefineHtmlEvent $event) {
                $entry = $event->sender;
                $sectionSettings = TextToSpeech::getInstance()->getSettings()->getSectionByHandle($entry->section->handle);
                if($sectionSettings['enabled']) {
                    //Render template
                    $view = Craft::$app->view->renderTemplate('text-to-speech/_edit-entry', [
                        'entry' => $event->sender
                    ]);
                    $event->html .= $view;
                }
            }

        );

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $event) {
                try {
                    $section = $event->sender->getSection();
                    $sectionSettings = TextToSpeech::getInstance()->getSettings()->getSectionByHandle($section->handle);
                    if($sectionSettings['enabled']) {
                        $key = $section->handle . 'TTS';
                        $event->behaviors[$key] = TextToSpeechBehavior::class;
                    }
                }catch (\Exception $e){
                }
            }
        );


        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITIES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = TextToSpeechUtility::class;
        });


        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_PUSH,
            function (PushEvent $event) {
                $job = $event->job;
                if ($job instanceof jobs\GenerateTTSJob) {
                    $entry = Entry::find()->id($job->entryId)->one();
                    if($entry) {
                        $processLog = new ProcessLogRecord();
                        $processLog->entryId = $entry->id;
                        $processLog->siteId = $entry->siteId;
                        $processLog->status = ProcessLogRecord::STATUS_PENDING;
                        $processLog->job = $job->job;
                        $processLog->characters = strlen($job->content);
                        $processLog->save();
                    }
                }
            }
        );

        Event::on(
            Queue::class,
            Queue::EVENT_BEFORE_EXEC,
            function (ExecEvent $event) {
                $job = $event->job;
                if ($job instanceof jobs\GenerateTTSJob) {
                    $processLog = ProcessLogRecord::find()->where(['job' => $job->job])->one();
                    if($processLog) {
                        $processLog->status = ProcessLogRecord::STATUS_PROCESSING;
                        $processLog->save();
                    }
                }
            }
        );

        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_EXEC_AND_RELEASE,
            function (ExecEvent $event) {
                $job = $event->job;
                if ($job instanceof jobs\GenerateTTSJob) {
                    $processLog = ProcessLogRecord::find()->where(['job' => $job->job])->one();
                    if($processLog) {
                        $processLog->status = ProcessLogRecord::STATUS_COMPLETED;
                        $processLog->save();
                    }
                }
            }
        );

        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_ERROR,
            function (ExecEvent $event) {
                $job = $event->job;
                if ($job instanceof jobs\GenerateTTSJob) {
                    $processLog = ProcessLogRecord::find()->where(['job' => $job->job])->one();
                    if($processLog) {
                        $processLog->status = ProcessLogRecord::STATUS_FAILED;
                        //$processLog->message = $event->result;
                        $processLog->save();
                    }
                }
            }
        );

    }
}
