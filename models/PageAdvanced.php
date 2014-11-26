<?php
/**
 * Расширенная модель страницы сайта.
 *
 * У каждой страницы добавляется, возможности:
 *  - добавлять главный image
 *  - добавлять второстепенный image_cover
 *  - добавлять много images
 *  - добавлять много files
 *  - можно голлосовать
 *  - можно комментировать
 *  - можно подписываться
 *  - добавляется полное и краткое описание страницы
 *
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\HasAdultStatus;
use skeeks\cms\models\behaviors\HasComments;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\HasSubscribes;
use skeeks\cms\models\behaviors\HasVotes;

use skeeks\cms\models\behaviors\traits\HasDescriptionsTrait;
use skeeks\cms\models\behaviors\traits\HasFiles as THasFiles;
use skeeks\cms\models\behaviors\traits\HasSubscribes as THasSubscribes;
use skeeks\cms\models\behaviors\traits\HasVotes as THasVotes;
use skeeks\cms\models\behaviors\traits\HasComments as THasComments;

use Yii;

/**
 * @property string $image
 * @property string $image_cover
 * @property string $images
 * @property string $files
 * @property integer $count_comment
 * @property integer $count_subscribe
 * @property string $users_subscribers
 * @property integer $count_vote
 * @property integer $result_vote
 * @property string $users_votes_up
 * @property string $users_votes_down
 *
 * Class PageAdvanced
 * @package skeeks\cms\base\models
 */
abstract class PageAdvanced extends Page
{
    use THasComments;
    use THasSubscribes;
    use THasVotes;
    use THasFiles;
    use HasDescriptionsTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [

            HasComments::className(),
            HasSubscribes::className(),
            HasVotes::className(),
            HasDescriptionsBehavior::className(),
            HasAdultStatus::className() => HasAdultStatus::className(),
            HasStatus::className() => HasStatus::className(),
            behaviors\HasFiles::className() =>
            [
                "class"  => HasFiles::className(),
                "groups" =>
                [
                    "image" =>
                    [
                        'name'      => 'Главное изображение',
                        'config'    =>
                        [
                            HasFiles::MAX_SIZE            => 1*2048, //1Mb
                            HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                            HasFiles::MAX_COUNT_FILES     => 1,
                            HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                        ]
                    ],

                    "images" =>
                    [
                        'name'      => 'Изображения',
                        'config' =>
                        [
                            HasFiles::MAX_SIZE            => 1*2048, //1Mb
                            HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                            HasFiles::MAX_COUNT_FILES     => 50,
                            HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                        ]
                    ],

                    "files" =>
                    [
                        'name'      => 'Файлы',
                        'config'    =>
                        [
                            HasFiles::MAX_SIZE            => 1*2048, //1Mb
                            HasFiles::MAX_COUNT_FILES     => 50,
                        ]
                    ],
                ]
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'image' => Yii::t('app', 'Image'),
            'image_cover' => Yii::t('app', 'Image Cover'),
            'images' => Yii::t('app', 'Images'),
            'files' => Yii::t('app', 'Files'),
            'count_comment' => Yii::t('app', 'Count Comment'),
            'count_subscribe' => Yii::t('app', 'Count Subscribe'),
            'users_subscribers' => Yii::t('app', 'Users Subscribers'),
            'count_vote' => Yii::t('app', 'Count Vote'),
            'result_vote' => Yii::t('app', 'Result Vote'),
            'users_votes_up' => Yii::t('app', 'Users Votes Up'),
            'users_votes_down' => Yii::t('app', 'Users Votes Down'),
            'description_short' => Yii::t('app', 'Description Short'),
            'description_full' => Yii::t('app', 'Description Full'),
            'status' => Yii::t('app', 'Status'),
            'status_adult' => Yii::t('app', 'Status Adult'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['description_short', 'description_full'], 'string'],
            [["users_subscribers", "users_votes_up", "users_votes_down"], 'safe'],
            [["images", "files", "image_cover", "image"], 'safe'],
            [['count_comment', 'count_subscribe', 'count_vote', 'status', 'status_adult'], 'integer'],
        ]);
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
