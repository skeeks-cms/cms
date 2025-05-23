<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\behaviors\RelationalBehavior;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\models\queries\CmsCompanyQuery;
use skeeks\cms\models\queries\CmsTaskQuery;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopPayment;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "cms_contractor".
 *
 * @property int                   $id
 * @property int|null              $created_by
 * @property int|null              $created_at
 * @property string                $name Название
 * @property string|null           $description Название
 * @property int|null              $cms_image_id Картинка
 * @property string                $company_type Тип клиент/поставщик
 * @property int                   $cms_company_status_id Сатус компании
 *
 * @property CmsStorageFile|null   $cmsImage
 * @property CmsCompanyStatus|null $status
 *
 * @property CmsLog[]              $logs
 * @property CmsCompanyEmail[]     $emails
 * @property CmsCompanyPhone[]     $phones
 * @property CmsCompanyLink[]      $links
 * @property CmsCompanyAddress[]   $addresses
 * @property CmsContractor[]       $contractors
 * @property CmsUser[]             $users
 * @property CmsUser[]             $managers
 * @property CmsCompanyCategory[]  $categories
 *
 * @property CmsDeal[]             $deals
 * @property CmsProject[]          $projects
 * @property CmsTask[]             $tasks
 *
 * @property CmsCompany2user[]     $cmsCompany2users
 */
class CmsCompany extends ActiveRecord
{
    use HasLogTrait;

    const COMPANY_TYPE_CLIENT = 'client';
    const COMPANY_TYPE_SUPPLIERS = 'supplier';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_company';
    }

    /**
     * @return queries\CmsLogQuery
     */
    public function getCompanyLogs() {
        $q = CmsLog::find()
            ->andWhere([
                'and',
                ['model_code' => $this->skeeksModelCode],
                ['model_id' => $this->id],
            ]);

        $q->multiple = true;

        return $q;
    }

    /**
     * @return queries\CmsLogQuery
     */
    public function getLogs()
    {
        $q = CmsLog::find()
            ->andWhere([
                'and',
                ['model_code' => $this->skeeksModelCode],
                ['model_id' => $this->id],
            ])
            ->orWhere([
                'and',
                ['model_code' => (new CmsDeal())->skeeksModelCode],
                ['model_id' => $this->getDeals()->select(['id'])],
            ])
            ->orWhere([
                'and',
                ['model_code' => (new ShopBill())->skeeksModelCode],
                ['model_id' => $this->getBills()->select(['id'])],
            ])
            ->orWhere([
                'and',
                ['model_code' => (new ShopPayment())->skeeksModelCode],
                ['model_id' => $this->getPayments()->select(['id'])],
            ])
            ->orWhere([
                'and',
                ['model_code' => (new CmsTask())->skeeksModelCode],
                ['model_id' => $this->getTasks()->select(['id'])],
            ])
            ->orWhere([
                'and',
                ['model_code' => (new CmsProject())->skeeksModelCode],
                ['model_id' => $this->getProjects()->select(['id'])],
            ])
            ->orderBy(['created_at' => SORT_DESC]);

        $q->multiple = true;

        return $q;
    }


    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFile::class => [
                'class'  => HasStorageFile::class,
                'fields' => [
                    'cms_image_id',
                ],
            ],

            RelationalBehavior::class => [
                'class'         => RelationalBehavior::class,
                'relationNames' => [
                    'managers',
                    'users',
                    'contractors',
                    'categories',
                ],
            ],

            CmsLogBehavior::class => [
                'class'        => CmsLogBehavior::class,
                'relation_map' => [
                    'cms_company_status_id' => 'status',
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
            [['cms_company_status_id'], 'integer'],

            [['description'], 'string'],

            [['company_type'], 'string', 'max' => 255],
            /*[['company_type'], 'required'],*/
            [
                [
                    'company_type',
                ],
                'default',
                'value' => self::COMPANY_TYPE_CLIENT,
            ],

            [['name'], 'string', 'max' => 255],
            [['name'], 'required'],


            [
                [
                    'description',
                ],
                'default',
                'value' => null,
            ],

            [['cms_image_id'], 'safe'],

            [['managers'], 'safe'],
            [['users'], 'safe'],
            [['contractors'], 'safe'],
            [['categories'], 'safe'],

            [
                ['cms_image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'webp'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 10,
                'minSize'     => 256,
            ],


            /*[['email'], 'string', 'max' => 64],
            [['email'], 'email'],
            [['email'], "filter", 'filter' => 'trim'],
            [
                ['email'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ]*/
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name'                  => 'Название',
            'cms_image_id'          => 'Изображение',
            'description'           => 'Описание',
            'managers'              => 'Работают с компанией',
            'contractors'           => 'Реквизиты',
            'cms_company_status_id' => 'Статус',
            'categories'            => 'Сферы деятельности',
            'users'                 => 'Контакты',
            'company_type'          => 'Тип компании',
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
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(CmsCompanyStatus::class, ['id' => 'cms_company_status_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    /*public function getLogs()
    {
        return $this->hasMany(CmsLog::class, ['cms_company_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }*/

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmails()
    {
        return $this->hasMany(CmsCompanyEmail::class, ['cms_company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhones()
    {
        return $this->hasMany(CmsCompanyPhone::class, ['cms_company_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinks()
    {
        return $this->hasMany(CmsCompanyLink::class, ['cms_company_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(CmsCompanyAddress::class, ['cms_company_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDeals()
    {
        return $this->hasMany(CmsDeal::class, ['cms_company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBills()
    {
        return $this->hasMany(ShopBill::class, ['cms_company_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(ShopPayment::class, ['cms_company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany2managers()
    {
        return $this->hasMany(CmsCompany2manager::class, ['cms_company_id' => 'id'])
            ->from(['cmsCompany2managers' => CmsCompany2manager::tableName()]);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany2users()
    {
        return $this->hasMany(CmsCompany2user::class, ['cms_company_id' => 'id'])
            ->from(['cmsCompany2users' => CmsCompany2user::tableName()])/*->orderBy(['sort' => SORT_ASC])*/
            ;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany2contractors()
    {
        return $this->hasMany(CmsCompany2contractor::class, ['cms_company_id' => 'id'])
            ->from(['cmsCompany2contractors' => CmsCompany2contractor::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContractors()
    {
        return $this->hasMany(CmsContractor::class, ['id' => 'cms_contractor_id'])
            ->via('cmsCompany2contractors');;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        $class = \Yii::$app->user->identityClass;
        return $this->hasMany($class, ['id' => 'cms_user_id'])
            ->via('cmsCompany2users');;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManagers()
    {
        $class = \Yii::$app->user->identityClass;
        return $this->hasMany($class, ['id' => 'cms_user_id'])
            ->via('cmsCompany2managers');;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(CmsCompanyCategory::className(),
            ['id' => 'cms_company_category_id'])->viaTable(CmsCompany2category::tableName(), ['cms_company_id' => 'id']);
    }


    /**
     * @return \yii\db\ActiveQuery|CmsTaskQuery
     */
    public function getTasks()
    {
        return $this->hasMany(CmsTask::class, ['cms_company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery|CmsTaskQuery
     */
    public function getProjects()
    {
        return $this->hasMany(CmsProject::class, ['cms_company_id' => 'id']);
    }

    /**
     * @return CmsCompanyQuery|\skeeks\cms\query\CmsActiveQuery
     */
    public static function find()
    {
        return (new CmsCompanyQuery(get_called_class()));
    }
}