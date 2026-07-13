<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\Json;

class CmsOauthAccessToken extends ActiveRecord
{
    public static function tableName()
    {
        return 'cms_oauth_access_token';
    }

    public function rules()
    {
        $rules = [
            [['client_id', 'cms_user_id', 'token_hash', 'expires_at'], 'required'],
            [['client_id', 'cms_user_id', 'created_at', 'expires_at', 'revoked_at'], 'integer'],
            [['scopes'], 'string'],
            [['token_hash'], 'string', 'max' => 64],
        ];

        if ($this->hasAttribute('resource')) {
            $rules[] = [['resource'], 'string'];
        }

        return $rules;
    }

    public static function hashToken($token)
    {
        return hash('sha256', (string)$token);
    }

    public static function findActiveByToken($token)
    {
        return static::find()
            ->andWhere(['token_hash' => static::hashToken($token), 'revoked_at' => null])
            ->andWhere(['>', 'expires_at', time()])
            ->one();
    }

    public function hasScope($scope)
    {
        return in_array((string)$scope, (array)Json::decode((string)$this->scopes), true);
    }

    public function isForResource($resource)
    {
        if (!$this->hasAttribute('resource')) {
            return true;
        }

        $tokenResource = (string)$this->getAttribute('resource');
        if ($tokenResource === '') {
            return true;
        }

        return rtrim($tokenResource, '/') === rtrim((string)$resource, '/');
    }

    public function getClient()
    {
        return $this->hasOne(CmsOauthClient::class, ['id' => 'client_id']);
    }

    public function getCmsUser()
    {
        return $this->hasOne(CmsUser::class, ['id' => 'cms_user_id']);
    }
}
