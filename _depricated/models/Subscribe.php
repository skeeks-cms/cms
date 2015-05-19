<?php
/**
 * Subscribe
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\CanBeLinkedToModel;
use skeeks\sx\models\Ref;
use yii\base\Event;
use Yii;


/**
 * This is the model class for table "{{%cms_subscribe}}".
 *
 * @property integer $id
 * @property string $linked_to_model
 * @property string $linked_to_value
 */
class Subscribe extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_subscribe}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            CanBeLinkedToModel::className()
        ]);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['linked_to_model', 'linked_to_value'], 'required'],
            [['linked_to_model', 'linked_to_value'], 'string', 'max' => 255]
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'value' => Yii::t('app', 'Value'),
            'linked_to_model' => Yii::t('app', 'Linked To Model'),
            'linked_to_value' => Yii::t('app', 'Linked To Value'),
        ]);
    }
}
