<?php

namespace furbo\crafttexttospeech;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use furbo\crafttexttospeech\behaviors\TextToSpeechBehavior;
use furbo\crafttexttospeech\models\Settings;
use furbo\crafttexttospeech\services\TextToSpeechService;
use furbo\crafttexttospeech\utilities\TextToSpeechUtility;
use yii\base\Event;

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
        // Register event handlers here ...
        // (see https://craftcms.com/docs/5.x/extend/events.html to get started)
        \yii\base\Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $event) {
                try {
                    $section = $event->sender->getSection();
                    $sectionSettings = TextToSpeech::getInstance()->getSettings()->getSectionByHandle($section->handle);
                    if(isset($sectionSettings['template'])) {
                        $event->behaviors[$section->handle] = TextToSpeechBehavior::class;
                    }
                }catch (\Exception $e){
                }
            }
        );

        //After save entry, generate audio file in a job only for sections with a template
        Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, function($event) {
            $entry = $event->sender;
            if(!$entry->section){
                return;
            }
            if (!$entry->getIsDraft() && !$entry->getIsRevision()) {
                $sectionSettings = TextToSpeech::getInstance()->getSettings()->getSectionByHandle($entry->section->handle);
                if (isset($sectionSettings['template'])) {
                    TextToSpeech::getInstance()->textToSpeechService->generateAudioFromTemplate($entry);
                }
            }
        });


        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITIES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = TextToSpeechUtility::class;
        });
    }
}
