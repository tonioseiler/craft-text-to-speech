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

### Sections

Specify the sections where you want to use the plugin. You can set a different voice and language for each section and site. For a specific section, you have two options for generating the audio file:

#### Fields
Specify the fields that should be used to generate the audio files. This option is simpler but offers fewer customization possibilities.

#### Templates
For more complex structures such as Matrix or Table fields, or if you want to control audio generation using SSML syntax, you can use specialized Twig files to render the text to be converted.

#### Voices
Choose the voice you want to use for a specific section and site.

#### SSML
Specify whether the submitted text contains SSML syntax.
