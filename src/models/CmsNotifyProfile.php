<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

use yii\db\ActiveRecord;

/**
 * Personal notification profile: the ready variant chosen last and quiet hours
 * (minutes from midnight, the period may pass midnight). See notify\Notifications.
 *
 * @property int         $cms_user_id
 * @property string|null $preset
 * @property int         $is_quiet
 * @property int         $quiet_from
 * @property int         $quiet_to
 * @property int|null    $updated_at
 */
class CmsNotifyProfile extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_notify_profile}}';
    }

    public static function primaryKey()
    {
        return ['cms_user_id'];
    }

    public function rules()
    {
        return [
            [['is_quiet'], 'boolean'],
            [['quiet_from', 'quiet_to'], 'integer', 'min' => 0, 'max' => 24 * 60 - 1],
            [['preset'], 'string', 'max' => 32],
        ];
    }

    public function beforeSave($insert)
    {
        $this->updated_at = time();

        return parent::beforeSave($insert);
    }
}
