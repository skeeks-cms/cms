<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

/**
 * @property int    $id
 * @property int    $cms_deal_role_id
 * @property string $area
 * @property int    $level
 */
class CmsDealRoleArea extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_deal_role_area}}';
    }

    public function rules()
    {
        return [
            [['cms_deal_role_id', 'area', 'level'], 'required'],
            [['cms_deal_role_id', 'level'], 'integer'],
            [['area'], 'string', 'max' => 64],
        ];
    }
}
