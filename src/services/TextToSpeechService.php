<?php

namespace furbo\crafttexttospeech\services;

use Craft;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\errors\AssetException;
use craft\errors\ElementNotFoundException;
use craft\errors\FsException;
use craft\errors\FsObjectExistsException;
use craft\helpers\Queue;
use craft\models\VolumeFolder;
use craft\web\View;
use furbo\crafttexttospeech\jobs\GenerateTTSJob;
use furbo\crafttexttospeech\models\Settings;
use furbo\crafttexttospeech\TextToSpeech;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\ListVoicesRequest;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use yii\base\Component;
use yii\base\Exception;

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
                SsmlVoiceGender::NEUTRAL => 'Neutral',
                SsmlVoiceGender::SSML_VOICE_GENDER_UNSPECIFIED => 'Unknown',
            };

            $voiceList[] = [
                'label' => $gender . " " . $voice->getName(),
                'value' => $voice->getName(),
            ];
        }

        return $voiceList;
    }

    public function generateAudioFromTemplate(Entry $entry): void
    {
        $template = $this->settings->getSectionByHandle($entry->section->handle)['template'];
        $content = Craft::$app->view->renderTemplate($template, ['entry' => $entry], View::TEMPLATE_MODE_SITE);
        $siteHandle = $entry->site->handle;
        $filename = $entry->section->handle . "-" . $entry->slug . "-" . $siteHandle;

        // Call the job to generate the audio
        $job = new GenerateTTSJob([
            'content' => $content,
            'siteHandle' => $siteHandle,
            'filename' => $filename,
        ]);
        Queue::push($job);

    }

    public function generateAudioFromFields(Entry $entry, array $fields): void
    {

    }

    /**
     * @throws AssetException
     * @throws ElementNotFoundException
     * @throws FsObjectExistsException
     * @throws \Throwable
     * @throws FsException
     * @throws Exception
     * @throws ApiException
     */
    public function generateAudio(string $content, string $siteHandle, string $filename): ?Asset
    {
        $site = Craft::$app->sites->getSiteByHandle($siteHandle);
        if(!$this->client) {
            return null;
        }


        $input = new SynthesisInput();

        //Check if content is SSML or plain text
        if (str_contains($content, '<speak>')) {
            $input->setSsml($content);
        } else {
            $input->setText($content);
        }

        $voice = new VoiceSelectionParams();
        $voice->setLanguageCode($site->language);
        $voice->setName($this->settings->voices[$siteHandle]['voice']);

        $audioConfig = new AudioConfig();
        $audioConfig->setAudioEncoding(AudioEncoding::MP3);

        $request = new SynthesizeSpeechRequest();
        $request->setInput($input);
        $request->setVoice($voice);
        $request->setAudioConfig($audioConfig);


        $response = $this->client->synthesizeSpeech($request);

        $dataStream = $response->getAudioContent();

        //Check if the filename already exists
        $asset = Asset::find()
            ->filename($filename . ".mp3")
            ->volumeId($this->settings->attachmentVolumeId)
            ->one();

        if(is_null($asset)){
            $asset = new Asset();
        }

        $asset->setVolumeId($this->settings->attachmentVolumeId);
        $asset->setScenario(Asset::SCENARIO_CREATE);
        if($this->settings->folder) {
            // Create the folder if not exist
            $folder = Craft::$app->assets->findFolder([
                'volumeId' => $this->settings->attachmentVolumeId,
                'name' => $this->settings->folder,
            ]);

            if(is_null($folder)){
                // Create the folder
                $folder = new VolumeFolder([
                    'name' => $this->settings->folder,
                    'volumeId' => $this->settings->attachmentVolumeId,
                    'parentId' => $this->settings->attachmentVolumeId,
                    'path' => $this->settings->folder . '/',
                ]);
                Craft::$app->assets->createFolder($folder);
            }

            $asset->newFolderId = $folder->id;
        }
        $asset->filename = $filename . ".mp3";
        $asset->kind = 'audio';
        // Save the data stream to the asset's file
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $asset->filename;
        file_put_contents($tempPath, $dataStream);

        // Set the temp path to the asset
        $asset->tempFilePath = $tempPath;


        //save asset
        if(Craft::$app->elements->saveElement($asset)){
            return $asset;
        }

        return null;
    }

}
