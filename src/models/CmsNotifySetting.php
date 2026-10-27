<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

use yii\db\ActiveRecord;

/**
 * A personal notification setting: the mode of a category or of one kind in a
 * channel, or "mute" of a kind (channel "*"). See notify\Notifications.
 *
 * @property int         $id
 * @property int         $cms_user_id
 * @property string      $scope
 * @property string      $code
 * @property string      $channel
 * @property string      $mode
 * @property int|null    $updated_at
 */
class CmsNotifySetting extends ActiveRecord
{
    const SCOPE_CATEGORY = 'category';
    const SCOPE_KIND = 'kind';

    public static function tableName()
    {
        return '{{%cms_notify_setting}}';
    }

    public function beforeSave($insert)
    {
        $this->updated_at = time();

        return parent::beforeSave($insert);
    }
}
