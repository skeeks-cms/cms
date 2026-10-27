<?php

use yii\db\Migration;

/**
 * Notifications, step У4a: what a recipient gets and where. A row is a mode
 * of a category or of a single kind in a channel ("all", "important", "off"),
 * or "mute" of a kind in every channel ("не уведомлять о таком"). No row —
 * the default: everything in the CRM, nothing elsewhere.
 * The profile keeps the chosen ready variant and quiet hours.
 */
class m261027_100100__create_tables__cms_notify_setting extends Migration
{
    public function safeUp()
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB' : null;

        $this->createTable('{{%cms_notify_setting}}', [
            'id' => $this->primaryKey(),
            'cms_user_id' => $this->integer()->notNull()->comment('Получатель'),
            'scope' => $this->string(16)->notNull()->comment('category или kind'),
            'code' => $this->string(64)->notNull()->comment('Категория или вид'),
            'channel' => $this->string(32)->notNull()->comment('Канал, * — все'),
            'mode' => $this->string(16)->notNull()->comment('all, important, off, mute'),
            'updated_at' => $this->integer()->null(),
        ], $options);
        $this->createIndex('cms_notify_setting__unique', '{{%cms_notify_setting}}', ['cms_user_id', 'scope', 'code', 'channel'], true);
        $this->addForeignKey('cms_notify_setting__cms_user_id', '{{%cms_notify_setting}}', 'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%cms_notify_profile}}', [
            'cms_user_id' => $this->integer()->notNull()->comment('Получатель'),
            'preset' => $this->string(32)->null()->comment('Готовый вариант'),
            'is_quiet' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Тихие часы включены'),
            'quiet_from' => $this->smallInteger()->notNull()->defaultValue(21 * 60)->comment('Начало, минут от полуночи'),
            'quiet_to' => $this->smallInteger()->notNull()->defaultValue(9 * 60)->comment('Конец, минут от полуночи'),
            'updated_at' => $this->integer()->null(),
        ], $options);
        $this->addPrimaryKey('cms_notify_profile__pk', '{{%cms_notify_profile}}', 'cms_user_id');
        $this->addForeignKey('cms_notify_profile__cms_user_id', '{{%cms_notify_profile}}', 'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_notify_profile}}');
        $this->dropTable('{{%cms_notify_setting}}');
    }
}
