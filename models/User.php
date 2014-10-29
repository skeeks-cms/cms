<?php
/**
 * User
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\db\ActiveRecord;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

use skeeks\cms\models\behaviors\HasSubscribes;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property integer $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property integer $role
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property string $city
 * @property string $address
 * @property string $info
 * @property string $image
 * @property string $image_cover
 * @property string $gender
 *
 * @property Comment[] $comments
 * @property Publication[] $publications
 * @property StorageFile[] $storageFiles
 * @property Subscribe[] $subscribes
 * @property StorageFile $imageCover
 * @property StorageFile $image0
 * @property UserAuthclient[] $userAuthclients
 * @property Vote[] $votes
 */
class User
    extends ActiveRecord
    implements IdentityInterface
{
    use behaviors\traits\HasSubscribes;
    use behaviors\traits\HasFiles;

    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const ROLE_USER = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            HasSubscribes::className(),

            [
                "class"  => behaviors\Implode::className(),
                "fields" =>  [
                    "users_subscribers",
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::className(), ['created_by' => 'id']);
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPublications()
    {
        return $this->hasMany(Publication::className(), ['created_by' => 'id']);
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStorageFiles()
    {
        return $this->hasMany(StorageFile::className(), ['created_by' => 'id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscribes()
    {
        return $this->hasMany(Subscribe::className(), ['created_by' => 'id']);
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserAuthclients()
    {
        return $this->hasMany(UserAuthclient::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotes()
    {
        return $this->hasMany(Vote::className(), ['created_by' => 'id']);
    }




    /**
     * @param $name
     * @return \yii\db\ActiveQuery|null
     */
    public function getSubscribesModels($name)
    {
        $subscribes = $this->getSubscribes()->where(["linked_to_model" => $name::className()])->all();
        if ($subscribes)
        {
            $ids = [];
            foreach ($subscribes as $subscribe)
            {
                $ids[] = $subscribe->getAttribute("linked_to_value");
            }

            if ($ids)
            {
                return $name::find()->where(["id" => $ids]);
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->name ? $this->name : $this->username;
    }

    public function getPageUrl()
    {
        return \Yii::$app->urlManager->createUrl(["user/view", "username" => $this->username]);
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

        return Yii::$app->params["noimage"];
    }


    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

}
