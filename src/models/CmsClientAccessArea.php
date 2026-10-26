<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

/**
 * @property int    $id
 * @property int    $cms_client_access_id
 * @property string $area
 * @property int    $level
 */
class CmsClientAccessArea extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_client_access_area}}';
    }

    public function rules()
    {
        return [
            [['cms_client_access_id', 'area', 'level'], 'required'],
            [['cms_client_access_id', 'level'], 'integer'],
            [['area'], 'string', 'max' => 64],
        ];
    }
}
