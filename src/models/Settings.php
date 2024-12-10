<?php

namespace furbo\crafttexttospeech\models;

use Craft;
use craft\base\Model;
use furbo\crafttexttospeech\TextToSpeech;

/**
 * Text‐to‐Speech settings
 */
class Settings extends Model
{
    public string $credentialsJson = '';
    public $attachmentVolumeId = 0;
    public string $folder = '';
    public $voices = [];

    public $sections = [];


    public function rules(): array
    {
        return [

        ];
    }

    public function getSectionByHandle($handle)
    {
        return $this->sections[$handle] ?? null;
    }

    public function getSectionsWithTemplate()
    {
        return array_filter($this->sections, function ($section) {
            return isset($section['template']) && $section['template'] !== '';
        });
    }

}
