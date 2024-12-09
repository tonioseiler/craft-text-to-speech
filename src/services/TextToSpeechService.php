<?php

namespace furbo\crafttexttospeech\services;

use Craft;
use furbo\crafttexttospeech\models\Settings;
use furbo\crafttexttospeech\TextToSpeech;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\ListVoicesRequest;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use yii\base\Component;

/**
 * Text To Speech Service service
 */
class TextToSpeechService extends Component
{

    private $client = null;

    private Settings $settings;

    public function __construct()
    {
        parent::__construct();

        try {
            $this->settings = TextToSpeech::$plugin->getSettings();

            $credentials = json_decode($this->settings->credentialsJson, true);

            $this->client = new TextToSpeechClient([
                'credentials' => $credentials,
            ]);
        } catch (ValidationException $e) {
            Craft::error('Failed to create TextToSpeechClient: ' . $e->getMessage(), __METHOD__);
        } catch (\Exception $e) {
            Craft::error('An error occurred: ' . $e->getMessage(), __METHOD__);
        }

    }

    /**
     * @throws ApiException
     */
    public function getVoices(string $language = 'en-US' ): array
    {
        if(!$this->client) {
            return [];
        }
        $request = new ListVoicesRequest();
        $request->setLanguageCode($language);

        $response = $this->client->listVoices($request);

        $voices = $response->getVoices();
        $voiceList = [];

        foreach ($voices as $voice) {
            $gender = match ($voice->getSsmlGender()){
                SsmlVoiceGender::MALE => 'Male',
                SsmlVoiceGender::FEMALE => 'Female',
                default => 'Unknown',
            };

            $voiceList[] = [
                'label' => $gender . " " . $voice->getName(),
                'value' => $voice->getName(),
            ];
        }

        return $voiceList;
    }
}
