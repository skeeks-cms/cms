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

use Imagine\Image\ManipulatorInterface;
use skeeks\cms\authclient\models\UserAuthClient;
use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasSubscribes;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\user\UserEmail;
use skeeks\cms\rbac\models\CmsAuthAssignment;
use skeeks\cms\validators\PhoneValidator;
use Yii;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%cms_user}}".
 *
 * @property integer $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $image_id
 * @property integer $first_name
 * @property integer $last_name
 * @property integer $patronymic
 *
 * @property string $gender
 * @property string $active
 * @property integer $updated_by
 * @property integer $created_by
 * @property integer $logged_at
 * @property integer $last_activity_at
 * @property integer $last_admin_activity_at
 * @property string $email
 * @property string $phone
 * @property integer $email_is_approved
 * @property integer $phone_is_approved
 *
 * ***
 *
 * @property string $name
 * @property string $lastActivityAgo
 * @property string $lastAdminActivityAgo
 *
 * @property CmsStorageFile $image
 * @property string $avatarSrc
 * @property string $profileUrl
 *
 * @property CmsUserEmail[] $cmsUserEmails
 * @property CmsUserPhone[] $cmsUserPhones
 * @property UserAuthClient[] $cmsUserAuthClients
 *
 * @property \yii\rbac\Role[] $roles
 * @property []   $roleNames
 *
 * @property string $displayName
 * @property string $shortDisplayName
 * @property string $isOnline Пользователь онлайн?
 *
 * @property CmsContentElement2cmsUser[] $cmsContentElement2cmsUsers
 * @property CmsContentElement[] $favoriteCmsContentElements
 * @property CmsAuthAssignment[] $cmsAuthAssignments
 *
 */
class User
    extends Core
    implements IdentityInterface
{
    use HasRelatedPropertiesTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user}}';
    }

    /**
     * Логины которые нельзя удалять, и нельзя менять
     * @return array
     */
    public static function getProtectedUsernames()
    {
        return ['root', 'admin'];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_UPDATE, [$this, "_cmsCheckBeforeSave"]);

        $this->on(self::EVENT_AFTER_INSERT, [$this, "_cmsAfterSave"]);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, "_cmsAfterSave"]);

        $this->on(self::EVENT_BEFORE_DELETE, [$this, "checkDataBeforeDelete"]);
    }

    public function _cmsCheckBeforeSave($e) {
        if (!\Yii::$app->user && !\Yii::$app->user->identity) {
            return true;
        }

        if ($this->active == "N" && $this->id == \Yii::$app->user->identity->id) {
            throw new Exception(\Yii::t('skeeks/cms', 'Нельзя деактивировать себя'));
        }
    }

    public function _cmsAfterSave($e)
    {
        if ($this->_roleNames !== null) {
            if ($this->roles) {
                foreach ($this->roles as $roleExist) {
                    if (!in_array($roleExist->name, (array)$this->_roleNames)) {
                        \Yii::$app->authManager->revoke($roleExist, $this->id);
                    }
                }
            }

            foreach ((array)$this->_roleNames as $roleName) {
                if ($role = \Yii::$app->authManager->getRole($roleName)) {
                    try {
                        //todo: добавить проверку
                        \Yii::$app->authManager->assign($role, $this->id);
                    } catch (\Exception $e) {
                        \Yii::error("Ошибка назначения роли: " . $e->getMessage(), self::class);
                        //throw $e;
                    }
                } else {
                    \Yii::warning("Роль {$roleName} не зарегистрированна в системе", self::class);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function checkDataBeforeDelete($e)
    {
        if (in_array($this->username, static::getProtectedUsernames())) {
            throw new Exception(\Yii::t('skeeks/cms', 'This user can not be removed'));
        }

        if ($this->id == \Yii::$app->user->identity->id) {
            throw new Exception(\Yii::t('skeeks/cms', 'You can not delete yourself'));
        }
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [

            TimestampBehavior::class,

            HasStorageFile::class =>
                [
                    'class' => HasStorageFile::class,
                    'fields' => ['image_id']
                ],

            HasRelatedProperties::class =>
                [
                    'class' => HasRelatedProperties::class,
                    'relatedElementPropertyClassName' => CmsUserProperty::class,
                    'relatedPropertyClassName' => CmsUserUniversalProperty::class,
                ],

        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['active', 'default', 'value' => Cms::BOOL_Y],
            ['gender', 'default', 'value' => 'men'],
            ['gender', 'in', 'range' => ['men', 'women']],

            [['created_at', 'updated_at', 'email_is_approved', 'phone_is_approved'], 'integer'],

            [['image_id'], 'safe'],
            [
                ['image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'maxFiles' => 1,
                'maxSize' => 1024 * 1024 * 5,
                'minSize' => 1024,
            ],

            [['gender'], 'string'],
            [
                ['username', 'password_hash', 'password_reset_token', 'email', 'first_name', 'last_name', 'patronymic'],
                'string',
                'max' => 255
            ],
            [['auth_key'], 'string', 'max' => 32],

            [['phone'], 'string', 'max' => 64],
            [['phone'], PhoneValidator::class],
            [['phone'], 'unique'],
            [['phone', 'email'], 'default', 'value' => null],


            [['email'], 'unique'],
            [['email'], 'email'],

            //[['username'], 'required'],
            ['username', 'string', 'min' => 3, 'max' => 25],
            [['username'], 'unique'],
            [['username'], \skeeks\cms\validators\LoginValidator::class],

            [['logged_at'], 'integer'],
            [['last_activity_at'], 'integer'],
            [['last_admin_activity_at'], 'integer'],

            [
                ['username'],
                'default',
                'value' => function(self $model) {
                    $userLast = static::find()->orderBy("id DESC")->limit(1)->one();
                    return "id" . ($userLast->id + 1);
                }
            ],

            [['email_is_approved', 'phone_is_approved'], 'default', 'value' => 0],

            [
                ['auth_key'],
                'default',
                'value' => function(self $model) {
                    return \Yii::$app->security->generateRandomString();
                }
            ],

            [
                ['password_hash'],
                'default',
                'value' => function(self $model) {
                    return \Yii::$app->security->generatePasswordHash(\Yii::$app->security->generateRandomString());
                }
            ],

            [['roleNames'], 'safe'],
            [['roleNames'], 'default', 'value' => \Yii::$app->cms->registerRoles]
        ];
    }

    public function extraFields()
    {
        return [
            'displayName',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeeks/cms', 'ID'),
            'username' => Yii::t('skeeks/cms', 'Login'),
            'auth_key' => Yii::t('skeeks/cms', 'Auth Key'),
            'password_hash' => Yii::t('skeeks/cms', 'Password Hash'),
            'password_reset_token' => Yii::t('skeeks/cms', 'Password Reset Token'),
            'email' => Yii::t('skeeks/cms', 'Email'),
            'phone' => Yii::t('skeeks/cms', 'Phone'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'name' => \Yii::t('skeeks/cms/user', 'Name'), //Yii::t('skeeks/cms', 'Name???'),
            'first_name' => \Yii::t('skeeks/cms', 'First name'),
            'last_name' => \Yii::t('skeeks/cms', 'Last name'),
            'patronymic' => \Yii::t('skeeks/cms', 'Patronymic'),
            'gender' => Yii::t('skeeks/cms', 'Gender'),
            'logged_at' => Yii::t('skeeks/cms', 'Logged At'),
            'last_activity_at' => Yii::t('skeeks/cms', 'Last Activity At'),
            'last_admin_activity_at' => Yii::t('skeeks/cms', 'Last Activity In The Admin At'),
            'image_id' => Yii::t('skeeks/cms', 'Image'),
            'roleNames' => Yii::t('skeeks/cms', 'Группы'),
            'email_is_approved' => Yii::t('skeeks/cms', 'Email is approved'),
            'phone_is_approved' => Yii::t('skeeks/cms', 'Phone is approved'),
        ];
    }


    /**
     * Установка последней активности пользователя. Больше чем в настройках.
     * @return $this
     */
    public function lockAdmin()
    {
        $this->last_admin_activity_at = \Yii::$app->formatter->asTimestamp(time()) - (\Yii::$app->admin->blockedTime + 1);
        $this->save(false);

        return $this;
    }

    /**
     * Время проявления последней активности на сайте
     *
     * @return int
     */
    public function getLastAdminActivityAgo()
    {
        $now = \Yii::$app->formatter->asTimestamp(time());
        return (int)($now - (int)$this->last_admin_activity_at);
    }

    /**
     * Обновление времени последней актиности пользователя.
     * Только в том случае, если время его последней актиности больше 10 сек.
     * @return $this
     */
    public function updateLastAdminActivity()
    {
        $now = \Yii::$app->formatter->asTimestamp(time());

        if (!$this->lastAdminActivityAgo || $this->lastAdminActivityAgo > 10) {
            $this->last_activity_at = $now;
            $this->last_admin_activity_at = $now;

            $this->save(false);
        }

        return $this;
    }


    /**
     * Время проявления последней активности на сайте
     *
     * @return int
     */
    public function getLastActivityAgo()
    {
        $now = \Yii::$app->formatter->asTimestamp(time());
        return (int)($now - (int)$this->last_activity_at);
    }

    /**
     * Обновление времени последней актиности пользователя.
     * Только в том случае, если время его последней актиности больше 10 сек.
     * @return $this
     */
    public function updateLastActivity()
    {
        $now = \Yii::$app->formatter->asTimestamp(time());

        if (!$this->lastActivityAgo || $this->lastActivityAgo > 10) {
            $this->last_activity_at = $now;
            $this->save(false);
        }

        return $this;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(StorageFile::class, ['id' => 'image_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStorageFiles()
    {
        return $this->hasMany(StorageFile::class, ['created_by' => 'id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserAuthClients()
    {
        return $this->hasMany(UserAuthClient::class, ['user_id' => 'id']);
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->name ? $this->name : $this->username;
    }


    /**
     *
     * TODO: Is depricated > 2.7.1
     *
     * @param string $action
     * @param array $params
     * @return string
     */
    public function getPageUrl($action = 'view', $params = [])
    {
        return $this->getProfileUrl($action, $params);
    }


    /**
     * @param string $action
     * @param array $params
     * @return string
     */
    public function getProfileUrl($action = 'view', $params = [])
    {
        $params = ArrayHelper::merge([
            "cms/user/" . $action,
            "username" => $this->username
        ], $params);

        return \Yii::$app->urlManager->createUrl($params);
    }

    /**
     * @return string
     * @deprecated
     */
    public function getName()
    {
        $data = [];

        if ($this->last_name) {
            $data[] = $this->last_name;
        }

        if ($this->first_name) {
            $data[] = $this->first_name;
        }

        if ($this->patronymic) {
            $data[] = $this->patronymic;
        }

        return $data ? implode(" ", $data) : null;
    }


    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'active' => Cms::BOOL_Y]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException(\Yii::t('skeeks/cms', '"findIdentityByAccessToken" is not implemented.'));
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'active' => Cms::BOOL_Y]);
    }

    /**
     * Finds user by email
     *
     * @param $email
     * @return static
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'active' => Cms::BOOL_Y]);
    }

    /**
     * @param $phone
     * @return null|CmsUser
     */
    public static function findByPhone($phone)
    {
        return static::findOne(['phone' => $phone, 'active' => Cms::BOOL_Y]);

        return null;
    }


    /**
     * Поиск пользователя по email или логину
     * @param $value
     * @return User
     */
    public static function findByUsernameOrEmail($value)
    {
        if ($user = static::findByUsername($value)) {
            return $user;
        }

        if ($user = static::findByEmail($value)) {
            return $user;
        }

        return null;
    }

    /**
     * @param string|array $assignments
     * @return \skeeks\cms\query\CmsActiveQuery
     */
    public static function findByAuthAssignments($assignments) {
        return static::find()->joinWith('cmsAuthAssignments as cmsAuthAssignments')
            ->where(['cmsAuthAssignments.item_name' => $assignments]);
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
            'active' => Cms::BOOL_Y,
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
        $expire = Yii::$app->cms->passwordResetTokenExpire;
        $parts = explode('_', $token);
        $timestamp = (int)end($parts);
        return $timestamp + $expire >= time();
    }


    /**
     * Заполнить модель недостающими данными, которые необходимы для сохранения пользователя
     * @return $this
     */
    public function populate()
    {
        $password = \Yii::$app->security->generateRandomString(6);

        $this->generateUsername();
        $this->setPassword($password);
        $this->generateAuthKey();

        return $this;
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
     * Генерация логина пользователя
     * @return $this
     */
    public function generateUsername()
    {
        /*if ($this->email)
        {
            $userName = \skeeks\cms\helpers\StringHelper::substr($this->email, 0, strpos() );
        }*/

        $userLast = static::find()->orderBy("id DESC")->limit(1)->one();
        $this->username = "id" . ($userLast->id + 1);

        if (static::find()->where(['username' => $this->username])->limit(1)->one()) {
            $this->username = $this->username . "_" . \skeeks\cms\helpers\StringHelper::substr(md5(time()), 0, 6);
        }

        return $this;
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

    /**
     * @param int $width
     * @param int $height
     * @param $mode
     * @return mixed|null|string
     */
    public function getAvatarSrc($width = 50, $height = 50, $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND)
    {
        if ($this->image) {
            return \Yii::$app->imaging->getImagingUrl($this->image->src,
                new \skeeks\cms\components\imaging\filters\Thumbnail([
                    'w' => $width,
                    'h' => $height,
                    'm' => $mode,
                ]));
        }
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUserAuthClients()
    {
        return $this->hasMany(UserAuthClient::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUserEmails()
    {
        return $this->hasMany(CmsUserEmail::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUserPhones()
    {
        return $this->hasMany(CmsUserPhone::class, ['user_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsAuthAssignments()
    {
        return $this->hasMany(CmsAuthAssignment::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\rbac\Role[]
     */
    public function getRoles()
    {
        return \Yii::$app->authManager->getRolesByUser($this->id);
    }


    protected $_roleNames = null;

    /**
     * @return array
     */
    public function getRoleNames()
    {
        if ($this->_roleNames !== null) {
            return $this->_roleNames;
        }

        $this->_roleNames = (array)ArrayHelper::map($this->roles, 'name', 'name');
        return $this->_roleNames;
    }

    /**
     * @param array $roleNames
     * @return $this
     */
    public function setRoleNames($roleNames = [])
    {
        $this->_roleNames = $roleNames;

        return $this;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElement2cmsUsers()
    {
        return $this->hasMany(CmsContentElement2cmsUser::class, ['cms_user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavoriteCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::class, ['id' => 'cms_content_element_id'])
            ->via('cmsContentElement2cmsUsers');
    }


    /**
     * Пользователь онлайн?
     * @return bool
     */
    public function getIsOnline()
    {
        $time = \Yii::$app->formatter->asTimestamp(time()) - $this->last_activity_at;
        if ($time <= \Yii::$app->cms->userOnlineTime)
        {
            return true;
        } else
        {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getShortDisplayName()
    {
        if ($this->last_name || $this->first_name) {
            return implode(" ", [$this->last_name, $this->first_name]);
        } else {
            return $this->displayName;
        }
    }
}
