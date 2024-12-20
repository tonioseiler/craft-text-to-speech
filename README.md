# Google Text‐to‐Speech AI for Craft CMS

The Text-to-Speech Plugin is a powerful tool designed to enhance accessibility and user engagement on your Craft CMS website. With this plugin, you can seamlessly convert written content into natural-sounding speech, creating an inclusive experience for users who prefer or require audio content.

## Requirements

This plugin requires Craft CMS 4.40.0 or later and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or via Composer.

#### From the Plugin Store

1. Go to the Plugin Store in your project’s Control Panel.  
2. Search for “Text-to-Speech.”  
3. Press **Install**.

#### With Composer

Open your terminal and run the following commands:

```bash
# Navigate to the project directory
cd /path/to/my-project.test

# Tell Composer to load the plugin
composer require furbo/craft-text-to-speech

# Tell Craft to install the plugin
./craft plugin/install text-to-speech
```

## Configuration

The plugin generates audio files using Google Text-to-Speech AI. You can configure a voice and language for each section and site. To generate the audio files, press the button on the entry edit page or generate all at once using the button in the **Utilities** section.

### Google API Access
To use the plugin, create a project in the Google Cloud Console and add the Text-to-Speech AI Library to the project. You’ll also need to create a service account and download the JSON file containing the credentials for this account. Enter the contents of this file in the plugin settings.

### Storage directory for mp3 files

Specify a Craft CMS filesystem and folder where the generated audio files will be saved.

#### Voices
Choose the voice and language you want to use for a specific site.

### Sections

Specify the sections where you want to use the plugin. For a specific section, you have two options for generating the audio files
 1. Fields
 2. Template

#### Fields
Specify the fields that should be used to generate the audio files. This option is simpler but offers fewer customization possibilities.

#### Templates
For more complex structures such as Matrix or Table fields, or if you want to control audio generation using [SSML](https://cloud.google.com/text-to-speech/docs/ssml?hl=de) syntax, you can use specialized Twig files to render the text to be converted.

<img width="1552" alt="Screenshot 2024-12-17 at 12 19 50" src="https://github.com/user-attachments/assets/7818421c-6325-4ba5-9db0-92b55f22d5a4" />
<img width="1552" alt="Screenshot 2024-12-17 at 12 19 46" src="https://github.com/user-attachments/assets/d229a076-59e2-4c64-96a4-d8c1baa880da" />
<img width="1552" alt="Screenshot 2024-12-17 at 12 19 24" src="https://github.com/user-attachments/assets/8011a78e-40cb-4752-9272-9682807333a1" />
<img width="1552" alt="Screenshot 2024-12-17 at 12 19 07" src="https://github.com/user-attachments/assets/39b6326a-5c81-45ac-b001-5a8ed8e1cad2" />


