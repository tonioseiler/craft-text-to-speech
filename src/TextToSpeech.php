<?php

namespace furbo\crafttexttospeech;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use furbo\crafttexttospeech\models\Settings;
use furbo\crafttexttospeech\services\TextToSpeechService;

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
    }
}
