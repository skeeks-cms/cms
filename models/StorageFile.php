<?php

namespace skeeks\cms\models;
use skeeks\cms\base\db\ActiveRecord;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

use skeeks\sx\models\Ref;
use yii\base\Event;
/**
 * This is the model class for table "{{%cms_storage_file}}".
 *
 * @property integer $id
 * @property string $src
 * @property string $cluster_id
 * @property string $cluster_file
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $size
 * @property string $type
 * @property string $mime_type
 * @property string $extension
 * @property string $original_name
 * @property string $name_to_save
 * @property string $name
 * @property integer $status
 * @property string $description_short
 * @property string $description_full
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property integer $image_height
 * @property integer $image_width
 * @property integer $count_comment
 * @property integer $count_subscribe
 * @property string $users_subscribers
 * @property integer $count_vote
 * @property integer $result_vote
 * @property string $users_votes_up
 * @property string $users_votes_down
 * @property string $linked_to_model
 * @property string $linked_to_value
 *
 * @property User $updatedBy
 * @property User $createdBy
 */
class StorageFile extends Core
{
    use behaviors\traits\HasComments;
    use behaviors\traits\HasSubscribes;
    use behaviors\traits\HasVotes;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_storage_file}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            [['src'], 'required'],
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'size', 'status', 'image_height', 'image_width', 'count_comment', 'count_subscribe', 'count_vote', 'result_vote'], 'integer'],
            [['description_short', 'description_full', 'meta_description', 'meta_keywords', 'users_subscribers', 'users_votes_up', 'users_votes_down'], 'string'],
            [['src', 'cluster_file', 'original_name', 'name', 'meta_title', 'linked_to_model', 'linked_to_value'], 'string', 'max' => 255],
            [['cluster_id', 'type', 'mime_type', 'extension'], 'string', 'max' => 16],
            [['name_to_save'], 'string', 'max' => 32],
            [['src'], 'unique'],
            [['cluster_id', 'cluster_file'], 'unique', 'targetAttribute' => ['cluster_id', 'cluster_file'], 'message' => 'The combination of Cluster ID and Cluster Src has already been taken.']
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'src' => Yii::t('app', 'Src'),
            'cluster_id' => Yii::t('app', 'Cluster ID'),
            'cluster_file' => Yii::t('app', 'Cluster File'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'size' => Yii::t('app', 'Size'),
            'type' => Yii::t('app', 'Type'),
            'mime_type' => Yii::t('app', 'Mime Type'),
            'extension' => Yii::t('app', 'Extension'),
            'original_name' => Yii::t('app', 'Original Name'),
            'name_to_save' => Yii::t('app', 'Name To Save'),
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'description_short' => Yii::t('app', 'Description Short'),
            'description_full' => Yii::t('app', 'Description Full'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_description' => Yii::t('app', 'Meta Description'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
            'image_height' => Yii::t('app', 'Image Height'),
            'image_width' => Yii::t('app', 'Image Width'),
            'count_comment' => Yii::t('app', 'Count Comment'),
            'count_subscribe' => Yii::t('app', 'Count Subscribe'),
            'users_subscribers' => Yii::t('app', 'Users Subscribers'),
            'count_vote' => Yii::t('app', 'Count Vote'),
            'result_vote' => Yii::t('app', 'Result Vote'),
            'users_votes_up' => Yii::t('app', 'Users Votes Up'),
            'users_votes_down' => Yii::t('app', 'Users Votes Down'),
            'linked_to_model' => Yii::t('app', 'Linked To Model'),
            'linked_to_value' => Yii::t('app', 'Linked To Value'),
        ]);
    }


    //['status', 'default', 'value' => self::STATUS_ACTIVE],

    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;

    const TYPE_FILE     = "file";
    const TYPE_IMAGE    = "image";


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            behaviors\HasComments::className(),
            behaviors\HasSubscribes::className(),
            behaviors\HasVotes::className(),
        ]);
    }


    /**
     * @inheritdoc
     */
    public static function findIdentity($src)
    {
        return static::findOne(['src' => $src]);
    }


    /**
     * @return bool|int
     * @throws \Exception
     */
    public function delete()
    {
        //Сначала удалить файл
        /**
         * @var \common\components\storage\Storage $storage
         */
        try
        {
            $storage = Yii::$app->storage;
            $cluster = $storage->getCluster($this->cluster_id);
            $cluster->delete($this->cluster_file);
        } catch (\common\components\storage\Exception $e)
        {
            return false;
        }

        return parent::delete();
    }
}
