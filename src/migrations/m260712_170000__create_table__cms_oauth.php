<?php

use yii\db\Migration;

class m260712_170000__create_table__cms_oauth extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        if (!$this->db->getTableSchema('cms_oauth_client', true)) {
            $this->createTable('cms_oauth_client', [
                'id'            => $this->primaryKey(),
                'created_at'    => $this->integer(),
                'updated_at'    => $this->integer(),
                'client_id'     => $this->string(128)->notNull(),
                'secret_hash'   => $this->string(255)->notNull(),
                'name'          => $this->string(255)->notNull(),
                'redirect_uris' => $this->text(),
                'scopes'        => $this->text(),
                'is_active'     => $this->boolean()->notNull()->defaultValue(1),
            ], $tableOptions);

            $this->createIndex('cms_oauth_client__client_id', 'cms_oauth_client', 'client_id', true);
        }

        if (!$this->db->getTableSchema('cms_oauth_authorization_code', true)) {
            $this->createTable('cms_oauth_authorization_code', [
                'id'           => $this->primaryKey(),
                'created_at'   => $this->integer(),
                'client_id'    => $this->integer()->notNull(),
                'cms_user_id'  => $this->integer()->notNull(),
                'code_hash'    => $this->string(64)->notNull(),
                'redirect_uri' => $this->text(),
                'scopes'       => $this->text(),
                'resource'     => $this->text(),
                'code_challenge' => $this->string(128),
                'code_challenge_method' => $this->string(16),
                'expires_at'   => $this->integer()->notNull(),
                'used_at'      => $this->integer(),
            ], $tableOptions);

            $this->createIndex('cms_oauth_authorization_code__code_hash', 'cms_oauth_authorization_code', 'code_hash', true);
            $this->createIndex('cms_oauth_authorization_code__client_id', 'cms_oauth_authorization_code', 'client_id');
            $this->createIndex('cms_oauth_authorization_code__cms_user_id', 'cms_oauth_authorization_code', 'cms_user_id');
            $this->addForeignKey('cms_oauth_authorization_code__client', 'cms_oauth_authorization_code', 'client_id', 'cms_oauth_client', 'id', 'CASCADE', 'CASCADE');
            $this->addForeignKey('cms_oauth_authorization_code__cms_user', 'cms_oauth_authorization_code', 'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE');
        }

        if (!$this->db->getTableSchema('cms_oauth_access_token', true)) {
            $this->createTable('cms_oauth_access_token', [
                'id'          => $this->primaryKey(),
                'created_at'  => $this->integer(),
                'client_id'   => $this->integer()->notNull(),
                'cms_user_id' => $this->integer()->notNull(),
                'token_hash'  => $this->string(64)->notNull(),
                'scopes'      => $this->text(),
                'resource'    => $this->text(),
                'expires_at'  => $this->integer()->notNull(),
                'revoked_at'  => $this->integer(),
            ], $tableOptions);

            $this->createIndex('cms_oauth_access_token__token_hash', 'cms_oauth_access_token', 'token_hash', true);
            $this->createIndex('cms_oauth_access_token__client_id', 'cms_oauth_access_token', 'client_id');
            $this->createIndex('cms_oauth_access_token__cms_user_id', 'cms_oauth_access_token', 'cms_user_id');
            $this->addForeignKey('cms_oauth_access_token__client', 'cms_oauth_access_token', 'client_id', 'cms_oauth_client', 'id', 'CASCADE', 'CASCADE');
            $this->addForeignKey('cms_oauth_access_token__cms_user', 'cms_oauth_access_token', 'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE');
        }

        $secretHash = password_hash('_E0yZuW5wnlAbG_hHhecjwgEmm-cVLFVKLfHq6mPGv4', PASSWORD_DEFAULT);
        $now = time();
        $this->upsert('cms_oauth_client', [
            'created_at'    => $now,
            'updated_at'    => $now,
            'client_id'     => 'codex-mcp',
            'secret_hash'   => $secretHash,
            'name'          => 'Codex MCP',
            'redirect_uris' => json_encode(['http://127.0.0.1/codex-mcp/oauth/callback']),
            'scopes'        => json_encode(['cms.tasks.create']),
            'is_active'     => 1,
        ], [
            'updated_at'    => $now,
            'secret_hash'   => $secretHash,
            'name'          => 'Codex MCP',
            'redirect_uris' => json_encode(['http://127.0.0.1/codex-mcp/oauth/callback']),
            'scopes'        => json_encode(['cms.tasks.create']),
            'is_active'     => 1,
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('cms_oauth_access_token');
        $this->dropTable('cms_oauth_authorization_code');
        $this->dropTable('cms_oauth_client');
    }
}
