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

use skeeks\sx\models\Ref;
use Yii;

use yii\base\Event;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

use skeeks\cms\db\ActiveRecord;
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
class Comment extends ActiveRecord
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

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_AFTER_INSERT, [$this, "_reCalculateModel"]);
        $this->on(self::EVENT_AFTER_DELETE, [$this, "_reCalculateModel"]);
    }

    /**
     * После вставки комментария, необходимо найти, того к кому он добавился и обновить у него счетчик
     * @param Event $e
     * @return $this
     */
    protected function _reCalculateModel(Event $e)
    {
        $ref = new Ref($e->sender->linked_to_model, $e->sender->linked_to_value);
        $model = $ref->findModel();
        $model->calculateCountComments();
        return $this;
    }








    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            BlameableBehavior::className(),
            TimestampBehavior::className(),
            behaviors\HasVotes::className(),

            [
                "class"  => behaviors\Implode::className(),
                "fields" =>  [
                    "users_subscribers", "users_votes_up", "users_votes_down",
                     "images", "files"
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'count_subscribe', 'count_vote'], 'integer'],
            [['content'], 'string'],
            [['linked_to_model', 'linked_to_value'], 'required'],
            [['linked_to_model', 'linked_to_value'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'content' => Yii::t('app', 'Content'),
            'linked_to_model' => Yii::t('app', 'Linked To Model'),
            'linked_to_value' => Yii::t('app', 'Linked To Value'),
            'count_subscribe' => Yii::t('app', 'Count Subscribe'),
            'count_vote' => Yii::t('app', 'Count Vote'),
            'count_vote_up' => Yii::t('app', 'Count Vote Up'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }
}
