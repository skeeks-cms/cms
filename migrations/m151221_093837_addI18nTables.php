<?php
use yii\base\InvalidConfigException;
use yii\db\Migration;
use yii\db\Schema;
class m151221_093837_addI18nTables extends Migration
{
    /**
     * @return bool|void
     * @throws InvalidConfigException
     */
    public function safeUp()
    {
        $i18n = new \skeeks\cms\i18n\components\I18NDb();

        if (!isset($i18n->sourceMessageTable) || !isset($i18n->messageTable)) {
            throw new InvalidConfigException('You should configure i18n component');
        }
        $sourceMessageTable = $i18n->sourceMessageTable;
        $messageTable = $i18n->messageTable;
        $this->createTable($sourceMessageTable, [
            'id' => Schema::TYPE_PK,
            'category' => Schema::TYPE_STRING,
            'message' => Schema::TYPE_TEXT
        ]);
        $this->createTable($messageTable, [
            'id' => Schema::TYPE_INTEGER,
            'language' => Schema::TYPE_STRING,
            'translation' => Schema::TYPE_TEXT
        ]);
        $this->addPrimaryKey('id', $messageTable, ['id', 'language']);
        $this->addForeignKey('fk_source_message_message', $messageTable, 'id', $sourceMessageTable, 'id', 'cascade', 'restrict');
    }
    public function safeDown()
    {
        $i18n = new \skeeks\cms\i18n\components\I18NDb();

        if (!isset($i18n->sourceMessageTable) || !isset($i18n->messageTable)) {
            throw new InvalidConfigException('You should configure i18n component');
        }
        $this->dropTable($i18n->sourceMessageTable);
        $this->dropTable($i18n->messageTable);
        return true;
    }
}