<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

/**
 * A deal narrows an access area to `level` (0 — closed). No row — as the type allows.
 *
 * @property int    $id
 * @property int    $cms_deal_id
 * @property string $area
 * @property int    $level
 */
class CmsDealArea extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_deal_area}}';
    }

    public function rules()
    {
        return [
            [['cms_deal_id', 'area', 'level'], 'required'],
            [['cms_deal_id', 'level'], 'integer'],
            [['area'], 'string', 'max' => 64],
        ];
    }
}
