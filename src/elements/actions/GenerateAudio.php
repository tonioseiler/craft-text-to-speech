<?php

namespace furbo\crafttexttospeech\elements\actions;

use Craft;
use craft\base\ElementAction;
use furbo\crafttexttospeech\TextToSpeech;

/**
 * Generate Audio element action
 */
class GenerateAudio extends ElementAction
{
    public static function displayName(): string
    {
        return Craft::t('text-to-speech', 'Generate Audio');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
            (() => {
                new Craft.ElementActionTrigger({
                    type: $type,

                    // Whether this action should be available when multiple elements are selected
                    bulk: true,

                    // Return whether the action should be available depending on which elements are selected
                    validateSelection: (selectedItems, elementIndex) => {
                      return true;
                    },

                    // Uncomment if the action should be handled by JavaScript:
                    // activate: (selectedItems, elementIndex) => {
                    //   elementIndex.setIndexBusy();
                    //   const ids = elementIndex.getSelectedElementIds();
                    //   // ...
                    //   elementIndex.setIndexAvailable();
                    // },
                });
            })();
        JS, [static::class]);

        return null;
    }

    public function performAction(Craft\elements\db\ElementQueryInterface $query): bool
    {
        $entries = $query->all();
        foreach ($entries as $entry) {
            $content = $entry->getTTSContent();
            if(!empty($content)){
                TextToSpeech::getInstance()->textToSpeechService->executeTTSJob($entry, $content);
            }
        }
        return true;
    }
}
