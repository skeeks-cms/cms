<?php

use yii\db\Migration;

/** Core login registry. */
class m261007_140000_user_sessions extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%cms_user_session}}', [
            'id' => $this->primaryKey(),
            'realm' => $this->string(64)->notNull(),
            'cms_user_id' => $this->integer()->notNull(),
            'secret_hash' => $this->char(64)->notNull(),
            'auth_key_hash' => $this->char(64)->notNull(),
            'label' => $this->string(190)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'last_seen_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
            'absolute_expires_at' => $this->integer()->notNull(),
            'idle_timeout' => $this->integer()->notNull()->defaultValue(0),
            'revoked_at' => $this->integer(),
        ]);
        $this->createIndex('ux_user_session_secret', '{{%cms_user_session}}', 'secret_hash', true);
        $this->createIndex('ix_user_session_owner', '{{%cms_user_session}}', ['realm', 'cms_user_id', 'revoked_at']);
        $this->createIndex('ix_user_session_expiry', '{{%cms_user_session}}', ['realm', 'expires_at']);
        $this->createIndex('ix_user_session_revoked', '{{%cms_user_session}}', ['realm', 'revoked_at']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_user_session}}');
    }
}
