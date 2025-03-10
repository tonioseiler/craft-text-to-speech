<?php

namespace furbo\crafttexttospeech\migrations;

use Craft;
use craft\db\Migration;

/**
 * m250305_092709_create_table_logs migration.
 */
class m250305_092709_create_table_logs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%text_to_speech_process_logs}}', [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'job' => $this->string()->notNull(),
            'status' => $this->string()->notNull(),
            'message' => $this->text(),
            'characters' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);

        $this->createIndex(null, '{{%text_to_speech_process_logs}}', ['entryId', 'siteId']);
        $this->addForeignKey(null, '{{%text_to_speech_process_logs}}', ['entryId'], '{{%entries}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%text_to_speech_process_logs}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTable('{{%text_to_speech_process_logs}}');

        return false;
    }
}
