<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;

class CmsOauthAuthorizationCode extends ActiveRecord
{
    public static function tableName()
    {
        return 'cms_oauth_authorization_code';
    }

    public function rules()
    {
        $rules = [
            [['client_id', 'cms_user_id', 'code_hash', 'expires_at'], 'required'],
            [['client_id', 'cms_user_id', 'created_at', 'expires_at', 'used_at'], 'integer'],
            [['redirect_uri', 'scopes'], 'string'],
            [['code_hash'], 'string', 'max' => 64],
        ];

        if ($this->hasAttribute('resource')) {
            $rules[] = [['resource'], 'string'];
        }
        if ($this->hasAttribute('code_challenge')) {
            $rules[] = [['code_challenge'], 'string', 'max' => 128];
        }
        if ($this->hasAttribute('code_challenge_method')) {
            $rules[] = [['code_challenge_method'], 'string', 'max' => 16];
        }

        return $rules;
    }

    public static function hashCode($code)
    {
        return hash('sha256', (string)$code);
    }

    public static function findActiveByCode($code)
    {
        return static::find()
            ->andWhere(['code_hash' => static::hashCode($code), 'used_at' => null])
            ->andWhere(['>', 'expires_at', time()])
            ->one();
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
