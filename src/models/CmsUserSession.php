<?php
namespace skeeks\cms\models;

/** High-write security record: deliberately no CMS activity/cache behaviors. */
class CmsUserSession extends \yii\db\ActiveRecord
{
    public static function tableName() { return '{{%cms_user_session}}'; }
    public function fields()
    {
        return ['id', 'label', 'created_at', 'last_seen_at', 'expires_at', 'revoked_at'];
    }
}
