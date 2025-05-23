<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\behaviors\RelationalBehavior;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\models\queries\CmsCompanyQuery;
use skeeks\cms\models\queries\CmsProjectQuery;
use skeeks\cms\models\queries\CmsTaskQuery;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "cms_contractor".
 *
 * @property int                 $id
 * @property int|null            $created_by
 * @property int|null            $created_at
 * @property string              $name Название
 * @property string|null         $description Название
 * @property int|null            $cms_image_id Картинка
 * @property int|null            $cms_company_id Компания
 * @property int|null            $cms_user_id Клиент
 * @property int                 $is_active Активность
 * @property int                 $is_private Закрытый?
 *
 * @property CmsStorageFile|null $cmsImage
 * @property CmsCompany|null     $cmsCompany
 * @property CmsUser|null        $cmsUser
 *
 * @property CmsUser[]           $users
 * @property CmsUser[]           $managers
 * @property CmsTask[]           $tasks
 */
class CmsProject extends ActiveRecord
{
    use HasLogTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_project';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            RelationalBehavior::class => [
                'class' => RelationalBehavior::class,
                'relationNames' => [
                    'managers',
                    'users',
                ],
            ],
            HasStorageFile::class     => [
                'class'  => HasStorageFile::class,
                'fields' => [
                    'cms_image_id',
                ],
            ],
            CmsLogBehavior::class     => [
                'class' => CmsLogBehavior::class,
                'relation_map' => [
                    'cms_company_id' => 'cmsCompany',
                    'cms_user_id' => 'cmsUser',
                ],
            ],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'created_at'], 'integer'],

            [['is_active'], 'integer'],
            [['is_private'], 'integer'],

            [['cms_company_id'], 'integer'],
            [['cms_user_id'], 'integer'],


            [['name'], 'string', 'max' => 255],
            [['name'], 'required'],

            [['description'], 'string'],
            [
                [
                    'description',
                ],
                'default',
                'value' => null,
            ],

            [['cms_image_id'], 'safe'],

            [
                ['cms_image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'webp'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 10,
                'minSize'     => 256,
            ],

            [['managers'], 'safe'],
            [['users'], 'safe'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name'         => 'Название',
            'cms_image_id' => 'Изображение',
            'description'  => 'Описание',

            'is_active'    => 'Активность',
            'is_private'   => 'Закрытый?',

            'managers' => 'Работают с проектом',
            'users'    => 'Клиенты',
            
            'cms_company_id'    => 'Компания',
            'cms_user_id'    => 'Клиент',
        ]);
    }


    /**
     * Gets query for [[CmsImage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsImage()
    {
        return $this->hasOne(CmsStorageFile::class, ['id' => 'cms_image_id']);
    }

    /**
     * Gets query for [[CmsImage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    /**
     * Gets query for [[CmsImage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'cms_user_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        $class = \Yii::$app->user->identityClass;
        return $this->hasMany($class, ['id' => 'cms_user_id'])
            ->via('cmsProject2users');;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManagers()
    {
        $class = \Yii::$app->user->identityClass;
        return $this->hasMany($class, ['id' => 'cms_user_id'])
            ->via('cmsProject2managers');;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsProject2managers()
    {
        return $this->hasMany(CmsProject2manager::class, ['cms_project_id' => 'id'])
            ->from(['cmsProject2managers' => CmsProject2manager::tableName()]);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsProject2users()
    {
        return $this->hasMany(CmsProject2user::class, ['cms_project_id' => 'id'])
            ->from(['cmsProject2users' => CmsProject2user::tableName()])/*->orderBy(['sort' => SORT_ASC])*/
            ;
    }

    /**
     * @return \yii\db\ActiveQuery|CmsTaskQuery
     */
    public function getTasks()
    {
        return $this->hasMany(CmsTask::class, ['cms_project_id' => 'id']);
    }

    /**
     * @return CmsCompanyQuery|\skeeks\cms\query\CmsActiveQuery
     */
    public static function find()
    {
        return (new CmsProjectQuery(get_called_class()));
    }
}