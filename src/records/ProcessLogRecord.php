<?php

namespace furbo\crafttexttospeech\records;

use Craft;
use craft\db\ActiveRecord;

/**
 * Process Log record
 */
class ProcessLogRecord extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];



    public static function tableName()
    {
        return '{{%text_to_speech_process_logs}}';
    }
}
