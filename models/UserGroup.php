<?php
/**
 * UserGroup
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasRef;
use Yii;

/**
 * Class Publication
 * @package skeeks\cms\models
 */
class UserGroup extends Core
{
    use behaviors\traits\HasFiles;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_group}}';
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasFiles::className() =>
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
                ]
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            ['groupname', 'string', 'min' => 3, 'max' => 12],

            [['groupname'], 'required'],
            [['description', 'groupname'], 'string'],
            [['groupname'], 'unique'],
            [["images", "files", "image_cover", "image"], 'safe'],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return  array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'groupname' => Yii::t('app', 'Groupname'),
            'description' => Yii::t('app', 'Description'),
            'image' => Yii::t('app', 'Image'),
            'image_cover' => Yii::t('app', 'Image Cover'),
            'images' => Yii::t('app', 'Images'),
            'files' => Yii::t('app', 'Files'),
        ]);
    }

}
