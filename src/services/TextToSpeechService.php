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
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechLongAudioSynthesizeClient;
use Google\Cloud\TextToSpeech\V1\ListVoicesRequest;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeLongAudioRequest;
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

    private array $credentials = [];

    const MAX_TEXT_LENGTH = 4500;

    public function __construct()
    {
        parent::__construct();

        if($this->settings->credentialsJson) {
            $this->settings = TextToSpeech::$plugin->getSettings();
            $this->credentials = json_decode($this->settings->credentialsJson, true);
        }

    }

    protected function initSynthesizeClient(): void
    {
        try {
            $this->client = new TextToSpeechClient([
                'credentials' => $this->credentials,
            ]);
        } catch (ValidationException $e) {
            Craft::error('Failed to create TextToSpeechClient: ' . $e->getMessage(), __METHOD__);
        } catch (\Exception $e) {
            Craft::error('An error occurred: ' . $e->getMessage(), __METHOD__);
        }
    }

    protected function initLongAudioSynthesizeClient(): void
    {
        try {
            $this->client = new TextToSpeechLongAudioSynthesizeClient([
                'credentials' => $this->credentials,
            ]);
        } catch (ValidationException $e) {
            Craft::error('Failed to create TextToSpeechClient: ' . $e->getMessage(), __METHOD__);
        } catch (\Exception $e) {
            Craft::error('An error occurred: ' . $e->getMessage(), __METHOD__);
        }
    }

    public function validateCredentials(): bool
    {
        $this->initSynthesizeClient();
        return $this->client !== null;
    }

    /**
     * @throws ApiException
     */
    public function getVoices(string $language = "" ): array
    {
        $this->initSynthesizeClient();

        if (!$this->client) {
            return [];
        }
        $request = new ListVoicesRequest();
        if ($language) {
            $request->setLanguageCode($language);
        }

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

    public function getLanguages() :array
    {
        $this->initSynthesizeClient();

        if (!$this->client) {
            return [];
        }
        $request = new ListVoicesRequest();

        $response = $this->client->listVoices($request);

        $voices = $response->getVoices();
        $languages = [];


        foreach ($voices as $voice) {
            $langs = $voice->getLanguageCodes();
            if(isset($langs[0])){
                $language = $langs[0];
                if(!in_array($language, $languages)){
                    $languages[] = $language;
                }
            }
        }

        $languages = array_map(function($language){
            return [
                'label' => $language,
                'value' => $language,
            ];
        }, $languages);

        return $languages;
    }

    public function getFileName(Entry $entry): string
    {
        return $entry->section->handle . "-" . $entry->slug . "-" . $entry->site->handle;
    }

    public function getContentFromTemplate(Entry $entry): string
    {
        $template = $this->settings->getSectionByHandle($entry->section->handle)['template'];
        $content = Craft::$app->view->renderTemplate($template, ['entry' => $entry], View::TEMPLATE_MODE_SITE);

        return $content;
    }

    public function getContentFromFields(Entry $entry, array $fields): string
    {
        $content = "";
        foreach ($fields as $field){
            if(isset($entry->{$field})) {
                $content .= $entry->{$field} . ". \n";
            }
        }
        //strip html tags if the <speak> tag is not present
        if(!str_contains($content, '<speak>'))
            $content = strip_tags($content);

        return $content;
    }

    public function executeTTSJob(Entry $entry, string $content)
    {
        $job = new GenerateTTSJob([
            'content' => $content,
            'siteHandle' => $entry->site->handle,
            'filename' => $this->getFileName($entry),
        ]);
        Queue::push($job);
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
        $contents = [];
        $dataStream = '';
        $content = html_entity_decode($content);
        $isSSML = str_contains($content, '<speak>');
        // Remove the SSML tags
        if($isSSML){
            $content = preg_replace('/<speak>|<\/speak>/', '', $content);
        }

        // Check if the $content is longer than MAX_TEXT_LENGTH bytes
        if(strlen($content) > self::MAX_TEXT_LENGTH){
            $maxLength = self::MAX_TEXT_LENGTH;

            while (strlen($content) > 0) {
                if (strlen($content) > $maxLength) {
                    $chunk = substr($content, 0, $maxLength);
                    $lastPeriodPos = strrpos($chunk, ".");
                    if ($lastPeriodPos !== false) {
                        $chunk = substr($chunk, 0, $lastPeriodPos + 1);
                    }
                } else {
                    $chunk = $content;
                }
                $contents[] = $chunk;
                $content = substr($content, strlen($chunk));
            }
        }else{
            $contents[] = $content;
        }


        $this->initSynthesizeClient();

        $site = Craft::$app->sites->getSiteByHandle($siteHandle);

        if(!$this->client) {
            return null;
        }


        foreach ($contents as $c) {

            $input = new SynthesisInput();

            //Check if content is SSML or plain text
            if ($isSSML) {
                $input->setSsml("<speak>" . $c . "</speak>");
            } else {
                $input->setText($c);
            }


            $voice = new VoiceSelectionParams();
            $voice->setLanguageCode($this->settings->voices[$siteHandle]['language'] ?? $site->language);
            $voice->setName($this->settings->voices[$siteHandle]['voice']);

            $audioConfig = new AudioConfig();
            $audioConfig->setAudioEncoding(AudioEncoding::MP3);

            $request = new SynthesizeSpeechRequest();

            $request->setInput($input);
            $request->setVoice($voice);
            $request->setAudioConfig($audioConfig);


            $response = $this->client->synthesizeSpeech($request);

            $dataStream .= $response->getAudioContent();
        }


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
