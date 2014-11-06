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
        return '{{%user_group}}';
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasRef::className() =>
            [
                'class'             => HasRef::className(),
                'savedClassName'    => self::className()
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
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],

            ['role', 'default', 'value' => self::ROLE_USER],
            ['role', 'in', 'range' => [self::ROLE_USER]],

            ['username', 'string', 'min' => 3, 'max' => 12],

            [['username', 'auth_key', 'password_hash', 'email'], 'required'],
            [['role', 'status', 'created_at', 'updated_at'], 'integer'],
            [['info', 'gender'], 'string'],
            [['username', 'password_hash', 'password_reset_token', 'email', 'name', 'city', 'address'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['image_cover', 'image'], 'default', 'value' => NULL],
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'auth_key' => Yii::t('app', 'Auth Key'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'password_reset_token' => Yii::t('app', 'Password Reset Token'),
            'email' => Yii::t('app', 'Email'),
            'role' => Yii::t('app', 'Role'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'name' => Yii::t('app', 'Name'),
            'city' => Yii::t('app', 'City'),
            'address' => Yii::t('app', 'Address'),
            'info' => Yii::t('app', 'Info'),
            'image' => Yii::t('app', 'Image'),
            'image_cover' => Yii::t('app', 'Image Cover'),
            'gender' => Yii::t('app', 'Gender'),
        ];
    }

}
