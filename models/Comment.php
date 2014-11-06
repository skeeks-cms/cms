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

use skeeks\cms\models\behaviors\CanBeLinkedToModel;
use skeeks\sx\models\Ref;
use Yii;

use yii\base\Event;

/**
 * This is the model class for table "{{%comment}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $content
 * @property string $linked_to
 * @property integer $count_subscribe
 * @property integer $count_vote
 * @property integer $count_vote_up
 *
 * @property User $updatedBy
 * @property User $createdBy
 */
class Comment extends Core
{
    use behaviors\traits\HasSubscribes;
    use behaviors\traits\HasVotes;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%comment}}';
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            CanBeLinkedToModel::className(),
            behaviors\HasVotes::className(),
        ]);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'count_subscribe', 'count_vote'], 'integer'],
            [['content'], 'string'],
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
            'content' => Yii::t('app', 'Content'),
            'linked_to_model' => Yii::t('app', 'Linked To Model'),
            'linked_to_value' => Yii::t('app', 'Linked To Value'),
            'count_subscribe' => Yii::t('app', 'Count Subscribe'),
            'count_vote' => Yii::t('app', 'Count Vote'),
            'count_vote_up' => Yii::t('app', 'Count Vote Up'),
        ]);
    }
}
