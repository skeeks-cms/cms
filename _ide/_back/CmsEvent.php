<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
namespace skeeks\cms\models;

use Yii;

/**
 * This is the model class for table "{{%cms_event}}".
 *
 * @property integer $id
 * @property string $event_name
 * @property string $name
 * @property string $description
 * @property integer $priority
 */
class CmsEvent extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_event}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_name'], 'required'],
            [['description'], 'string'],
            [['priority'], 'integer'],
            [['event_name'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 100],
            [['event_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'event_name' => Yii::t('app', 'Событие'),
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'priority' => Yii::t('app', 'Priority'),
        ];
    }
}