<?php

use yii\db\Migration;

class m260713_000000__oauth_mcp_pkce_resource extends Migration
{
    public function safeUp()
    {
        $authCodeTable = $this->db->getTableSchema('cms_oauth_authorization_code', true);
        if ($authCodeTable) {
            if (!isset($authCodeTable->columns['resource'])) {
                $this->addColumn('cms_oauth_authorization_code', 'resource', $this->text());
            }
            if (!isset($authCodeTable->columns['code_challenge'])) {
                $this->addColumn('cms_oauth_authorization_code', 'code_challenge', $this->string(128));
            }
            if (!isset($authCodeTable->columns['code_challenge_method'])) {
                $this->addColumn('cms_oauth_authorization_code', 'code_challenge_method', $this->string(16));
            }
        }

        $accessTokenTable = $this->db->getTableSchema('cms_oauth_access_token', true);
        if ($accessTokenTable) {
            if (!isset($accessTokenTable->columns['resource'])) {
                $this->addColumn('cms_oauth_access_token', 'resource', $this->text());
            }

            $this->delete('cms_oauth_access_token', [
                'token_hash' => 'ff279465cd3b36a81c6a5eac16a6ce896a1402d21e328c3726523c32f86e2210',
            ]);
        }
    }

    public function safeDown()
    {
        $accessTokenTable = $this->db->getTableSchema('cms_oauth_access_token', true);
        if ($accessTokenTable && isset($accessTokenTable->columns['resource'])) {
            $this->dropColumn('cms_oauth_access_token', 'resource');
        }

        $authCodeTable = $this->db->getTableSchema('cms_oauth_authorization_code', true);
        if ($authCodeTable) {
            if (isset($authCodeTable->columns['code_challenge_method'])) {
                $this->dropColumn('cms_oauth_authorization_code', 'code_challenge_method');
            }
            if (isset($authCodeTable->columns['code_challenge'])) {
                $this->dropColumn('cms_oauth_authorization_code', 'code_challenge');
            }
            if (isset($authCodeTable->columns['resource'])) {
                $this->dropColumn('cms_oauth_authorization_code', 'resource');
            }
        }
    }
}
