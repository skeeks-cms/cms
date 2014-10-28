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

use skeeks\cms\db\ActiveRecord;

use Yii;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "{{%publication}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property string $description_short
 * @property string $description_full
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $image
 * @property string $image_cover
 * @property string $seo_page_name
 * @property integer $game_id
 * @property integer $count_comment
 * @property integer $count_subscribe
 * @property integer $count_vote
 * @property integer $count_vote_up
 *
 * @property Game $game
 * @property StorageAlbum $albumFile
 * @property StorageAlbum $albumImage
 * @property StorageFile $imageCover
 * @property User $createdBy
 * @property StorageFile $image0
 * @property User $updatedBy
 */
class Publication extends ActiveRecord
{
    use behaviors\traits\HasComments;
    use behaviors\traits\HasSubscribes;
    use behaviors\traits\HasVotes;
    use behaviors\traits\HasFiles;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%publication}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            BlameableBehavior::className(),
            TimestampBehavior::className(),
            behaviors\SeoPageName::className(),
            behaviors\HasComments::className(),
            behaviors\HasSubscribes::className(),
            behaviors\HasVotes::className(),

            [
                "class"  => behaviors\Implode::className(),
                "fields" =>  [
                    "users_subscribers", "users_votes_up", "users_votes_down",
                    "image_cover", "image", "images", "files"
                ]
            ],

            [
                "class"  => behaviors\HasFiles::className(),
                "fields" =>
                [
                    "image" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 1*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 1,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "image_cover" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 1*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 1,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "images" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 15*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 10,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "files" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 15*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::MAX_COUNT_FILES     => 10,
                    ],
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
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'game_id', 'count_comment', 'count_subscribe', 'count_vote'], 'integer'],
            [['name'], 'required'],
            [['description_short', 'description_full', 'meta_description', 'meta_keywords'], 'string'],
            [['name', 'meta_title'], 'string', 'max' => 255],
            [['seo_page_name'], 'string', 'max' => 64],
            [['name'], 'unique'],
            [['image_cover', 'image'], 'default', 'value' => NULL],
            [['seo_page_name'], 'unique']
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
            'name' => Yii::t('app', 'Name'),
            'description_short' => Yii::t('app', 'Description Short'),
            'description_full' => Yii::t('app', 'Description Full'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_description' => Yii::t('app', 'Meta Description'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
            'image' => Yii::t('app', 'Image'),
            'image_cover' => Yii::t('app', 'Image Cover'),
            'seo_page_name' => Yii::t('app', 'Seo Page Name'),
            'game_id' => Yii::t('app', 'Game ID'),
            'count_comment' => Yii::t('app', 'Count Comment'),
            'count_subscribe' => Yii::t('app', 'Count Subscribe'),
            'count_vote' => Yii::t('app', 'Count Vote'),
            'count_vote_up' => Yii::t('app', 'Count Vote Up'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGame()
    {
        return $this->hasOne(Game::className(), ['id' => 'game_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }


    /**
     * @return array
     */
    public function getImages()
    {
        return (array) $this->images;
    }

    /**
     * @return string
     */
    public function getMainImage()
    {
        if ($this->image)
        {
            return (string) array_shift($this->image);
        }

        return \Yii::$app->params["noimage"];
    }

    /**
     * @return string
     */
    public function getImageCover()
    {
        if ($this->image)
        {
            return (string) array_shift($this->image_cover);
        }

        return \Yii::$app->params["noimage"];
    }
}
