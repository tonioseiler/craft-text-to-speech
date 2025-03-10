<?php

namespace furbo\crafttexttospeech\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTables();

        return true;
    }

    public function safeDown(): bool
    {
        $this->removeTables();

        return true;
    }

    protected function createTables(): void
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
    }

    protected function removeTables(): void
    {
        $this->dropTableIfExists('{{%text_to_speech_process_logs}}');
    }
}