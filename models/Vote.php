<?php
/**
 * Game
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\base\models\Core;
use skeeks\cms\models\behaviors\CanBeLinkedTo;
use Yii;

use skeeks\sx\models\Ref;
use yii\base\Event;
/**
 * This is the model class for table "{{%vote}}".
 *
 * @property integer $id
 * @property integer $value
 * @property string $linked_to_model
 * @property string $linked_to_value
 */
class Vote extends Core
{
    public function init()
    {
        parent::init();

        $this->on(self::EVENT_AFTER_INSERT, [$this, "_reCalculateModel"]);
        $this->on(self::EVENT_AFTER_DELETE, [$this, "_reCalculateModel"]);
    }

    /**
     * После добавления подписки нун
     * @param Event $e
     * @return $this
     */
    protected function _reCalculateModel(Event $e)
    {
        $ref = new Ref($e->sender->linked_to_model, $e->sender->linked_to_value);
        $model = $ref->findModel();
        $model->calculateVotes();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%vote}}';
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            CanBeLinkedTo::className()
        ]);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['value'], 'integer'],
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
