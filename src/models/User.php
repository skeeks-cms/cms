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
use skeeks\cms\base\ActiveRecord;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasSubscribes;
use skeeks\cms\models\behaviors\HasTableCache;
use skeeks\cms\models\behaviors\HasUserLog;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\queries\CmsUserQuery;
use skeeks\cms\models\user\UserEmail;
use skeeks\cms\rbac\models\CmsAuthAssignment;
use skeeks\cms\shop\models\ShopBonusTransaction;
use skeeks\cms\validators\PhoneValidator;
use Yii;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\AfterSaveEvent;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%cms_user}}".
 *
 * @property integer                     $id
 * @property string                      $username
 * @property string                      $auth_key
 * @property string                      $password_hash
 * @property string                      $password_reset_token
 * @property integer                     $created_at
 * @property integer                     $updated_at
 * @property integer                     $image_id
 * @property string                      $first_name
 * @property string                      $last_name
 * @property string                      $patronymic
 * @property integer                     $is_company
 * @property string                      $company_name
 * @property integer                      $birthday_at
 *
 * @property string                      $gender
 * @property string                      $alias
 * @property integer                     $is_active
 * @property integer                     $updated_by
 * @property integer                     $created_by
 * @property integer                     $logged_at
 * @property integer                     $last_activity_at
 * @property integer                     $last_admin_activity_at
 * @property string                      $email
 * @property string                      $phone
 * @property integer                     $cms_site_id
 *
 * ***
 *
 * @property string                      $name
 * @property string                      $lastActivityAgo
 * @property string                      $lastAdminActivityAgo
 *
 * @property CmsStorageFile              $image
 * @property string                      $avatarSrc
 * @property string                      $profileUrl
 *
 * @property CmsUserAddress[]            $cmsUserAddresses
 * @property CmsUserEmail[]              $cmsUserEmails
 * @property CmsUserPhone[]              $cmsUserPhones
 * @property UserAuthClient[]            $cmsUserAuthClients
 *
 * @property \yii\rbac\Role[]            $roles
 * @property []   $roleNames
 *
 * @property string                      $displayName
 * @property string                      $shortDisplayName
 * @property string                      $shortDisplayNameWithAlias
 * @property string                      $isOnline Пользователь онлайн?
 *
 * @property CmsContentElement2cmsUser[] $cmsContentElement2cmsUsers
 * @property CmsContentElement[]         $favoriteCmsContentElements
 * @property CmsAuthAssignment[]         $cmsAuthAssignments
 *
 * @property CmsUserEmail                $mainCmsUserEmail
 * @property CmsUserPhone                $mainCmsUserPhone
 * @property CmsContractor[]             $cmsContractors
 * @property CmsContractorMap[]          $cmsContractorMaps
 * @property ShopBonusTransaction[]      $bonusTransactions
 * @property float                       $bonusBalance
 *
 */
class User
    extends ActiveRecord
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
        return ['root'];
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

    public function _cmsCheckBeforeSave($e)
    {
        if (\Yii::$app instanceof Application) {
            if (!isset(\Yii::$app->user)) {
                return true;
            }

            if (!\Yii::$app->user && !\Yii::$app->user->identity) {
                return true;
            }

            if (!$this->is_active && $this->id == \Yii::$app->user->identity->id) {
                throw new Exception(\Yii::t('skeeks/cms', 'Нельзя деактивировать себя'));
            }

        }


        if ($this->isAttributeChanged('image_id')) {
            if ($this->image) {
                if (!$this->image->cmsSite->is_default) {
                    $img = $this->image;
                    $site = CmsSite::find()->default()->one();
                    $img->cms_site_id = $site->id;
                    $img->update(false, ['cms_site_id']);

                }
            }
        }


    }

    public function _cmsAfterSave(AfterSaveEvent $e)
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
                        if (!\Yii::$app->authManager->getAssignment($roleName, $this->id)) {
                            \Yii::$app->authManager->assign($role, $this->id);
                        }
                    } catch (\Exception $e) {
                        \Yii::error("Ошибка назначения роли: ".$e->getMessage(), self::class);
                        //throw $e;
                    }
                } else {
                    \Yii::warning("Роль {$roleName} не зарегистрированна в системе", self::class);
                }
            }
        }

        //Если пытаюсь поменять главный email
        if ($this->_mainEmail) {
            $value = trim(StringHelper::strtolower($this->_mainEmail));
            if ($this->mainCmsUserEmail) {
                $cmsUserEmail = $this->mainCmsUserEmail;
                if ($cmsUserEmail->value != $value) {
                    $cmsUserEmail->value = $value;
                    if (!$cmsUserEmail->save()) {
                        throw new Exception("Email не обновлен! ".print_r($cmsUserEmail->errors, true));
                    }
                }
            } else {
                $cmsUserEmail = new CmsUserEmail();
                $cmsUserEmail->value = $value;
                $cmsUserEmail->cms_site_id = $this->cms_site_id;
                $cmsUserEmail->cms_user_id = $this->id;
                if (!$cmsUserEmail->save()) {
                    throw new Exception("Email не добавлен! ".print_r($cmsUserEmail->errors, true));
                }
            }
        }
        //Если пытаюсь поменять главный телефон
        if ($this->_mainPhone) {
            $value = trim(StringHelper::strtolower($this->_mainPhone));
            if ($this->mainCmsUserPhone) {
                $cmsUserPhone = $this->mainCmsUserPhone;
                if ($cmsUserPhone->value != $value) {
                    $cmsUserPhone->value = $value;
                    if (!$cmsUserPhone->save()) {
                        throw new Exception("Телефон не обновлен! ".print_r($cmsUserPhone->errors, true));
                    }
                }
            } else {
                $cmsUserPhone = new CmsUserPhone();
                $cmsUserPhone->cms_site_id = $this->cms_site_id;
                $cmsUserPhone->value = $value;
                $cmsUserPhone->cms_user_id = $this->id;
                if (!$cmsUserPhone->save()) {
                    throw new Exception("Телефон не обновлен! ".print_r($cmsUserPhone->errors, true));
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

        if (isset(\Yii::$app->user)) {
            if ($this->id == \Yii::$app->user->identity->id) {
                throw new Exception(\Yii::t('skeeks/cms', 'You can not delete yourself'));
            }
        }

    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = array_merge(parent::behaviors(), [

            TimestampBehavior::class,

            /*HasUserLog::class => [
                'class'  => HasUserLog::class,
                'no_log_attributes' => [
                    'created_at', 'updated_at', 'updated_by', 'last_admin_activity_at', 'last_activity_at', 'created_by'
                ]
            ],*/

            HasStorageFile::class => [
                'class'  => HasStorageFile::class,
                'fields' => ['image_id'],
            ],

            HasRelatedProperties::class => [
                'class'                           => HasRelatedProperties::class,
                'relatedElementPropertyClassName' => CmsUserProperty::class,
                'relatedPropertyClassName'        => CmsUserUniversalProperty::class,
            ],
        ]);

        if (isset($behaviors[HasTableCache::class])) {
            unset($behaviors[HasTableCache::class]);
        }
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['birthday_at', 'default', 'value' => null],
            ['alias', 'default', 'value' => null],
            ['is_active', 'default', 'value' => 1],
            /*['gender', 'default', 'value' => 'men'],
            ['gender', 'in', 'range' => ['men', 'women']],*/

            [['company_name'], 'string'],
            [
                ['company_name'],
                'required',
                'when' => function () {
                    return $this->is_company;
                },
            ],

            [['birthday_at'], 'integer'],
            [['is_company'], 'integer'],
            ['is_company', 'default', 'value' => 0],

            [['created_at', 'updated_at', 'cms_site_id', 'is_active'], 'integer'],
            [['alias'], 'string'],

            [
                ['username'],
                'default',
                'value' => null,
                /*'value' => function (self $model) {
                    $userLast = static::find()->orderBy("id DESC")->limit(1)->one();
                    return "id".($userLast->id + 1);
                },*/
            ],

            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::class, 'targetAttribute' => ['cms_site_id' => 'id']],

            [['image_id'], 'safe'],
            [
                ['image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'webp'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 5,
                'minSize'     => 1024,
            ],

            [['gender'], 'string'],
            [
                ['username', 'password_hash', 'password_reset_token', 'first_name', 'last_name', 'patronymic'],
                'string',
                'max' => 255,
            ],
            [['auth_key'], 'string', 'max' => 32],

            [['phone'], 'string', 'max' => 64],
            [['phone'], PhoneValidator::class],
            [['phone'], "filter", 'filter' => 'trim'],
            [
                ['phone'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ],
            [
                ['phone'],
                function ($attribute) {
                    $value = StringHelper::strtolower(trim($this->{$attribute}));

                    if ($this->isNewRecord) {
                        if (CmsUserPhone::find()->cmsSite($this->cms_site_id)->andWhere(['value' => $value])->one()) {
                            $this->addError($attribute, "Этот телефон уже занят");
                        }
                    } else {
                        if (CmsUserPhone::find()
                            ->cmsSite($this->cms_site_id)
                            ->andWhere(['value' => $value])
                            ->andWhere(['!=', 'cms_user_id', $this->id])
                            ->one()) {
                            $this->addError($attribute, "Этот телефон уже занят");
                        }
                        //todo: доработать в будущем
                        /*if ($this->mainCmsUserPhone && $this->mainCmsUserPhone->is_approved && $this->mainCmsUserPhone->value != $value) {
                            $this->addError($attribute, "Этот телефон подтвержден, и его менять нельзя. Добавьте другой телефон, а после удалите этот!");
                            return false;
                        }*/
                    }
                },
            ],


            [['email'], 'string', 'max' => 64],
            [['email'], 'email'],
            [['email'], "filter", 'filter' => 'trim'],
            [
                ['email'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ],
            [
                ['email'],
                function ($attribute) {

                    $value = StringHelper::strtolower(trim($this->{$attribute}));

                    if ($this->isNewRecord) {
                        if (CmsUserEmail::find()->cmsSite($this->cms_site_id)->andWhere(['value' => $value])->one()) {
                            $this->addError($attribute, "Этот email уже занят");
                            return false;
                        }
                    } else {
                        if (CmsUserEmail::find()
                            ->cmsSite($this->cms_site_id)
                            ->andWhere(['value' => $value])
                            ->andWhere(['!=', 'cms_user_id', $this->id])
                            ->one()) {

                            $this->addError($attribute, "Этот email уже занят");
                            return false;
                        }
                        
                        if ($this->mainCmsUserEmail) {
                            $this->mainCmsUserEmail->value = StringHelper::strtolower($this->mainCmsUserEmail->value);
                            $value = StringHelper::strtolower($value);
                            if ($this->mainCmsUserEmail && $this->mainCmsUserEmail->is_approved && $this->mainCmsUserEmail->value != $value) {
                                $this->addError($attribute, "Этот email подтвержден, и его менять нельзя. Добавьте другой email, а после удалите этот!");
                                return false;
                            }
                        }
                    }
                },
            ],

            ['username', 'string', 'min' => 3, 'max' => 25],
            [['username'], 'unique', 'targetAttribute' => ['cms_site_id', 'username'], 'message' => 'Этот логин уже занят'],
            [['username'], \skeeks\cms\validators\LoginValidator::class],

            [['logged_at'], 'integer'],
            [['last_activity_at'], 'integer'],
            [['last_admin_activity_at'], 'integer'],


            [
                ['auth_key'],
                'default',
                'value' => function (self $model) {
                    return \Yii::$app->security->generateRandomString();
                },
            ],

            [
                ['password_hash'],
                'default',
                'value' => null,
            ],

            [['roleNames'], 'safe'],
            [['roleNames'], 'default', 'value' => \Yii::$app->cms->registerRoles],

            [['first_name', 'last_name'], 'trim'],
        ];
    }

    public function extraFields()
    {
        return [
            'displayName',
            'shortDisplayName',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'alias' => "Короткое альтернативное название, необязательно к заполнениею.",
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                     => Yii::t('skeeks/cms', 'ID'),
            'username'               => Yii::t('skeeks/cms', 'Login'),
            'auth_key'               => Yii::t('skeeks/cms', 'Auth Key'),
            'password_hash'          => Yii::t('skeeks/cms', 'Password Hash'),
            'password_reset_token'   => Yii::t('skeeks/cms', 'Password Reset Token'),
            'email'                  => Yii::t('skeeks/cms', 'Email'),
            'phone'                  => Yii::t('skeeks/cms', 'Phone'),
            'is_active'              => Yii::t('skeeks/cms', 'Active'),
            'alias'                  => "Псевдоним",
            'created_at'             => Yii::t('skeeks/cms', 'Created At'),
            'updated_at'             => Yii::t('skeeks/cms', 'Updated At'),
            'name'                   => \Yii::t('skeeks/cms/user', 'Name'), //Yii::t('skeeks/cms', 'Name???'),
            'first_name'             => \Yii::t('skeeks/cms', 'First name'),
            'last_name'              => \Yii::t('skeeks/cms', 'Last name'),
            'patronymic'             => \Yii::t('skeeks/cms', 'Patronymic'),
            'gender'                 => Yii::t('skeeks/cms', 'Gender'),
            'birthday_at'                 => Yii::t('skeeks/cms', 'Дата рождения'),
            'logged_at'              => Yii::t('skeeks/cms', 'Logged At'),
            'last_activity_at'       => Yii::t('skeeks/cms', 'Last Activity At'),
            'last_admin_activity_at' => Yii::t('skeeks/cms', 'Last Activity In The Admin At'),
            'image_id'               => Yii::t('skeeks/cms', 'Image'),
            'roleNames'              => Yii::t('skeeks/cms', 'Группы'),
            'is_company'             => "Тип аккаунта",
            'company_name'           => "Название компании",
        ];
    }


    /**
     * Все возможные свойства связанные с моделью
     * @return \yii\db\ActiveQuery
     */
    public function getRelatedProperties()
    {
        $q = CmsUserUniversalProperty::find()->cmsSite()->sort();
        $q->multiple = true;
        return $q;
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

        if ($this->name) {
            return $this->name;
        }

        if ($this->email) {
            return $this->email;
        }

        if ($this->phone) {
            return $this->phone;
        }

        if ($this->username) {
            return $this->username;
        }

        return $this->id;
    }


    /**
     *
     * TODO: Is depricated > 2.7.1
     *
     * @param string $action
     * @param array  $params
     * @return string
     */
    public function getPageUrl($action = 'view', $params = [])
    {
        return $this->getProfileUrl($action, $params);
    }


    /**
     * @param string $action
     * @param array  $params
     * @return string
     */
    public function getProfileUrl($action = 'view', $params = [])
    {
        $params = ArrayHelper::merge([
            "cms/user/".$action,
            "username" => $this->username,
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

        if ($this->is_company) {
            $data[] = $this->company_name;
        } else {
            if ($this->last_name) {
                $data[] = $this->last_name;
            }

            if ($this->first_name) {
                $data[] = $this->first_name;
            }

            if ($this->patronymic) {
                $data[] = $this->patronymic;
            }
        }


        return $data ? implode(" ", $data) : null;
    }


    public function asText()
    {
        return $this->shortDisplayName;
        if ($this->name) {
            return parent::asText();
        }

        $lastName = $this->username;
        return parent::asText()."{$lastName}";
    }

    /**
     * @return CmsUserQuery|\skeeks\cms\query\CmsActiveQuery
     */
    public static function find()
    {
        return (new CmsUserQuery(get_called_class()));
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::find()->cmsSite()->active()->andWhere(['id' => $id])->one();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException(\Yii::t('skeeks/cms', '"findIdentityByAccessToken" is not implemented.'));
    }

    /**
     * @param string $username
     * @return static
     * @deprecated
     *
     * Finds user by username
     *
     */
    public static function findByUsername($username)
    {
        return static::find()->cmsSite()->active()->username($username)->one();
    }

    /**
     * Finds user by email
     *
     * @param $email
     * @return static
     */
    public static function findByEmail($email)
    {
        return static::find()->cmsSite()->active()->email($email)->one();
    }

    /**
     * @param $phone
     * @return null|CmsUser
     * @deprecated
     *
     */
    public static function findByPhone($phone)
    {
        return static::find()->cmsSite()->active()->phone($phone)->one();
    }


    /**
     * @param $value
     * @return User
     * @deprecated
     *
     * Поиск пользователя по email или логину
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
    public static function findByAuthAssignments($assignments)
    {
        return static::find()
            ->cmsSite()
            ->joinWith('cmsAuthAssignments as cmsAuthAssignments')
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

        return static::find()->cmsSite()->active()->andWhere(['password_reset_token' => $token])->one();
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

        //$this->generateUsername();
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
        return Yii::$app->security->validatePassword($password, (string) $this->password_hash);
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
        $userLast = static::find()->orderBy("id DESC")->limit(1)->one();
        $this->username = "id".($userLast->id + 1);

        if (static::find()->where(['username' => $this->username])->limit(1)->one()) {
            $this->username = $this->username."_".\skeeks\cms\helpers\StringHelper::substr(md5(time()), 0, 6);
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
        $this->password_reset_token = Yii::$app->security->generateRandomString().'_'.time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * @param int    $width
     * @param int    $height
     * @param string $mode
     * @return string|null
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

        return null;
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
    public function getMainCmsUserEmail()
    {
        $query = $this->getCmsUserEmails()->limit(1);
        $query->multiple = false;

        return $query;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMainCmsUserPhone()
    {
        $query = $this->getCmsUserPhones()->limit(1);
        $query->multiple = false;

        return $query;
    }

    /**
     * @var null
     */
    protected $_mainEmail = null;
    /**
     * @var null
     */
    protected $_mainPhone = null;

    /**
     * @return string
     */
    public function getEmail()
    {
        if ($this->_mainEmail !== null) {
            return $this->_mainEmail;
        }

        if ($this->mainCmsUserEmail) {
            return $this->mainCmsUserEmail->value;
        }

        return '';
    }

    /**
     * @return string
     */
    public function setEmail(string $email)
    {
        $this->_mainEmail = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        if ($this->_mainPhone !== null) {
            return $this->_mainPhone;
        }

        if ($this->mainCmsUserPhone) {
            return $this->mainCmsUserPhone->value;
        }

        return '';
    }

    /**
     * @return string
     */
    public function setPhone(string $phone)
    {
        $this->_mainPhone = $phone;
        return $this;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUserAddresses()
    {
        return $this->hasMany(CmsUserAddress::class, ['cms_user_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUserEmails()
    {
        return $this->hasMany(CmsUserEmail::class, ['cms_user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUserPhones()
    {
        return $this->hasMany(CmsUserPhone::class, ['cms_user_id' => 'id']);
        //->via('cmsUserPhones')
        //
        ;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsAuthAssignments()
    {
        return $this->hasMany(CmsAuthAssignment::class, ['cms_user_id' => 'id']);
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
    public function getBonusTransactions()
    {
        return $this->hasMany(ShopBonusTransaction::class, ['cms_user_id' => 'id']);
    }

    public function getBonusBalance()
    {
        $result = $this->getBonusTransactions()->addSelect([
            "result" => new Expression("SUM(IF(is_debit, value * -1, value))"),
        ])->asArray()->one();

        return (float)ArrayHelper::getValue($result, "result");

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
        if ($time <= \Yii::$app->cms->userOnlineTime) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getShortDisplayName()
    {
        if ($this->is_company) {
            return $this->company_name;
        } else {
            if ($this->last_name || $this->first_name) {
                return implode(" ", [$this->last_name, $this->first_name]);
            }
        }

        return $this->displayName;
    }
    /**
     * @return string
     */
    public function getShortDisplayNameWithAlias()
    {
        if ($this->alias) {
            return $this->shortDisplayName." ({$this->alias})";
        }

        return $this->shortDisplayName;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContractorMaps()
    {
        return $this->hasMany(CmsContractorMap::class, ['cms_user_id' => 'id'])
            ->from(['cmsContractorMaps' => CmsContractorMap::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContractors()
    {
        return $this->hasMany(CmsContractor::class, ['id' => 'cms_contractor_id'])
            ->via('cmsContractorMaps');;
    }
}
