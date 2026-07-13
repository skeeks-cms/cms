<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\Json;

class CmsOauthClient extends ActiveRecord
{
    public static function tableName()
    {
        return 'cms_oauth_client';
    }

    public function rules()
    {
        return [
            [['client_id', 'secret_hash', 'name'], 'required'],
            [['redirect_uris', 'scopes'], 'string'],
            [['is_active'], 'boolean'],
            [['client_id'], 'string', 'max' => 128],
            [['secret_hash', 'name'], 'string', 'max' => 255],
        ];
    }

    public static function findActiveByClientId($clientId)
    {
        return static::find()
            ->andWhere(['client_id' => (string)$clientId, 'is_active' => 1])
            ->one();
    }

    public function validateSecret($secret)
    {
        return password_verify((string)$secret, (string)$this->secret_hash);
    }

    public function allowsRedirectUri($redirectUri)
    {
        $redirectUris = $this->getRedirectUris();
        return in_array((string)$redirectUri, $redirectUris, true);
    }

    public function allowsScopes(array $scopes)
    {
        return !array_diff($scopes, $this->getScopes());
    }

    public function getRedirectUris()
    {
        return (array)Json::decode((string)$this->redirect_uris);
    }

    public function getScopes()
    {
        return (array)Json::decode((string)$this->scopes);
    }
}
