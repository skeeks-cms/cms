<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

/**
 * An access area a deal type may open to the client, up to `level`.
 * A type without rows may open every area.
 *
 * @property int    $id
 * @property int    $cms_deal_type_id
 * @property string $area
 * @property int    $level
 */
class CmsDealTypeArea extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_deal_type_area}}';
    }

    public function rules()
    {
        return [
            [['cms_deal_type_id', 'area', 'level'], 'required'],
            [['cms_deal_type_id', 'level'], 'integer'],
            [['area'], 'string', 'max' => 64],
        ];
    }
}
