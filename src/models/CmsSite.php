<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\assets\CmsAsset;
use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\rbac\models\CmsAuthAssignment;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_site}}".
 *
 * @property integer                $id
 * @property integer                $created_by
 * @property integer                $updated_by
 * @property integer                $created_at
 * @property integer                $updated_at
 * @property integer                $is_active
 * @property integer                $is_default
 * @property integer                $priority
 * @property string                 $name
 * @property string                 $internal_name
 * @property string                 $description
 * @property integer                $image_id
 * @property integer                $favicon_storage_file_id
 * @property array                  $work_time
 *
 * @property string                 $url
 *
 * @property string                 $internalName
 * @property CmsAuthAssignment[]    $authAssignments
 * @property CmsTree                $rootCmsTree
 * @property CmsLang                $cmsLang
 * @property CmsSiteDomain[]        $cmsSiteDomains
 * @property CmsSiteDomain          $cmsSiteMainDomain
 * @property CmsTree[]              $cmsTrees
 * @property CmsContentElement[]    $cmsContentElements
 * @property CmsStorageFile         $image
 * @property CmsStorageFile         $favicon
 * @property CmsComponentSettings[] $cmsComponentSettings
 * @property CmsSiteEmail|null      $cmsSiteEmail
 * @property CmsSiteEmail|null      $cmsSitePhone
 * @property CmsSiteAddress|null    $cmsSiteAddress
 * @property CmsSiteEmail[]         $cmsSiteEmails
 * @property CmsSiteAddress[]       $cmsSiteAddresses
 * @property CmsSitePhone[]         $cmsSitePhones
 * @property CmsSiteSocial[]        $cmsSiteSocials
 * @property CmsSmsProvider[]       $cmsSmsProviders
 * @property CmsSmsProvider|null    $cmsSmsProvider
 *
 * @property string                 $faviconRootSrc
 * @property string                 $faviconUrl всегда вернет какую нибудь фавиконку, не важно задана она для сайта или нет
 * @property string                 $faviconType полный тип фивикон https://yandex.ru/support/webmaster/search-results/create-favicon.html
 */
class CmsSite extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site}}';
    }


    public function init()
    {
        parent::init();

        $this->on(BaseActiveRecord::EVENT_AFTER_INSERT, [$this, 'createTreeAfterInsert']);
        $this->on(BaseActiveRecord::EVENT_BEFORE_INSERT, [$this, 'beforeInsertChecks']);
        $this->on(BaseActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'beforeUpdateChecks']);

        $this->on(BaseActiveRecord::EVENT_BEFORE_DELETE, [$this, 'beforeDeleteRemoveTree']);

    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function beforeDeleteRemoveTree()
    {
        //Before delete site delete all tree
        foreach ($this->cmsTrees as $tree) {
            //$tree->delete();
            /*if (!$tree->deleteWithChildren())
            {
                throw new Exception('Not deleted tree');
            }*/
        }
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFile::className()        => [
                'class'  => HasStorageFile::className(),
                'fields' => ['image_id', 'favicon_storage_file_id'],
            ],
            HasJsonFieldsBehavior::className() => [
                'class'  => HasJsonFieldsBehavior::className(),
                'fields' => ['work_time'],
            ],
        ]);
    }

    /**
     * @param Event $e
     * @throws Exception
     */
    public function beforeUpdateChecks(Event $e)
    {
        //Если этот элемент по умолчанию выбран, то все остальны нужно сбросить.
        if ($this->is_default) {
            static::updateAll(
                [
                    'is_default' => null,
                ],
                ['!=', 'id', $this->id]
            );

            $this->is_active = 1; //сайт по умолчанию всегда активный
        }

    }

    /**
     * @param Event $e
     * @throws Exception
     */
    public function beforeInsertChecks(Event $e)
    {
        //Если этот элемент по умолчанию выбран, то все остальны нужно сбросить.
        if ($this->is_default) {
            static::updateAll([
                'is_default' => null,
            ]);

            $this->is_active = 1; //сайт по умолчанию всегда активный
        }

    }

    public function createTreeAfterInsert(Event $e)
    {
        $tree = new CmsTree([
            'name' => 'Главная страница',
        ]);

        $tree->makeRoot();
        $tree->cms_site_id = $this->id;

        try {
            if (!$tree->save()) {
                throw new Exception('Failed to create a section of the tree');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            die;
            throw $e;
        }

    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'is_active'               => Yii::t('skeeks/cms', 'Active'),
            'is_default'              => Yii::t('skeeks/cms', 'Default'),
            'priority'                => Yii::t('skeeks/cms', 'Priority'),
            'name'                    => Yii::t('skeeks/cms', 'Name'),
            'internal_name'           => Yii::t('skeeks/cms', 'Внутреннее название'),
            'description'             => Yii::t('skeeks/cms', 'Description'),
            'image_id'                => Yii::t('skeeks/cms', 'Логотип'),
            'favicon_storage_file_id' => Yii::t('skeeks/cms', 'Favicon'),
            'work_time'               => Yii::t('skeeks/cms', 'Рабочее время'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'name'                    => Yii::t('skeeks/cms', 'Основное название сайта, отображается в разных местах шаблона, в заголовках писем и других местах.'),
            'internal_name'           => Yii::t('skeeks/cms', 'Название сайта, чаще всего невидимое для клиента, но видимое администраторам и менеджерам.'),
            'favicon_storage_file_id' => Yii::t('skeeks/cms',
                'Формат: ICO (рекомендуемый), Размер: 16 × 16, 32 × 32 или 120 × 120 пикселей. Иконка сайта отображаемая в браузере, а так же в различных поисковиках. <br />Подробная документация <a href="https://yandex.ru/support/webmaster/search-results/favicon.html" target="_blank" data-pjax="0">https://yandex.ru/support/webmaster/search-results/favicon.html</a>'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority'], 'integer'],
            [['is_active'], 'integer'],
            [['is_default'], 'integer'],
            [['name', 'description'], 'string', 'max' => 255],
            [['internal_name'], 'string', 'max' => 255],
            ['priority', 'default', 'value' => 500],
            ['is_active', 'default', 'value' => 1],
            ['internal_name', 'default', 'value' => null],
            ['is_default', 'default', 'value' => null],
            /*[['is_default'], 'unique'],*/
            [['image_id'], 'safe'],
            [['work_time'], 'safe'],
            [['favicon_storage_file_id'], 'safe'],

            [
                ['image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'mimeTypes'   => [
                    'image/*',
                ],
                //'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'svg'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 2,
                'minSize'     => 1024,
            ],

            [
                ['favicon_storage_file_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                //'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'ico', 'svg'],
                'mimeTypes'   => [
                    'image/*',
                ],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 2,
                //'minSize'     => 1024,
            ],
        ]);
    }

    static public $sites = [];

    /**
     * @param (integer) $id
     * @return static
     */
    public static function getById($id)
    {
        if (!array_key_exists($id, static::$sites)) {
            static::$sites[$id] = static::find()->where(['id' => (integer)$id])->one();
        }

        return static::$sites[$id];
    }

    static public $sites_by_code = [];

    /**
     * @param (integer) $id
     * @return static
     */
    public static function getByCode($code)
    {
        if (!array_key_exists($code, static::$sites_by_code)) {
            static::$sites_by_code[$code] = static::find()->where(['code' => (string)$code])->one();
        }

        return static::$sites_by_code[$code];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteDomains()
    {
        return $this->hasMany(CmsSiteDomain::class, ['cms_site_id' => 'id']);
    }

    /**
     * Gets query for [[CmsSiteEmails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSmsProviders()
    {
        return $this->hasMany(CmsSmsProvider::className(), ['cms_site_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }
    /**
     * Gets query for [[CmsSiteEmails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSmsProvider()
    {
        return $this->getCmsSmsProviders()->default()->one();
    }

    /**
     * Gets query for [[CmsSiteEmails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteEmails()
    {
        return $this->hasMany(CmsSiteEmail::className(), ['cms_site_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * Gets query for [[CmsSiteEmails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteAddresses()
    {
        return $this->hasMany(CmsSiteAddress::className(), ['cms_site_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * Gets query for [[CmsSitePhones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSitePhones()
    {
        return $this->hasMany(CmsSitePhone::className(), ['cms_site_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * Главный телефон сайта
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSitePhone()
    {
        $q = $this->getCmsSitePhones()->limit(1);
        $q->multiple = false;
        return $q;
    }

    /**
     * Главный адрес сайта
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteAddress()
    {
        $q = $this->getCmsSiteAddresses()->limit(1);
        $q->multiple = false;
        return $q;
    }

    /**
     * Главный email сайта
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteEmail()
    {
        $q = $this->getCmsSiteEmails()->limit(1);
        $q->multiple = false;
        return $q;
    }

    /**
     * Gets query for [[CmsSiteSocials]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteSocials()
    {
        return $this->hasMany(CmsSiteSocial::className(), ['cms_site_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteMainDomain()
    {
        $query = $this->getCmsSiteDomains()
            ->andWhere(['is_main' => 1]);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTrees()
    {
        return $this->hasMany(CmsTree::class, ['cms_site_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::class, ['cms_site_id' => 'id']);
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->cmsSiteMainDomain) {
            return (($this->cmsSiteMainDomain->is_https ? "https:" : "http:")."//".$this->cmsSiteMainDomain->domain);
        }

        return \Yii::$app->urlManager->hostInfo;
    }

    /**
     * @return CmsTree
     */
    public function getRootCmsTree()
    {
        return $this->getCmsTrees()->andWhere(['level' => 0])->limit(1)->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'image_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavicon()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'favicon_storage_file_id']);
    }


    /**
     * Gets query for [[CmsComponentSettings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsComponentSettings()
    {
        return $this->hasMany(CmsComponentSettings::className(), ['cms_site_id' => 'id']);
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getFaviconUrl()
    {
        if ($this->favicon) {
            return $this->favicon->absoluteSrc;
        } else {
            return CmsAsset::getAssetUrl('favicon.ico');
        }
    }

    /**
     * @return string
     */
    public function getFaviconRootSrc()
    {
        if ($this->favicon) {
            return $this->favicon->getRootSrc();
        } else {
            return \Yii::getAlias('@skeeks/cms/assets/src/favicon.ico');
        }
    }

    /**
     * @return string
     * @see https://yandex.ru/support/webmaster/search-results/create-favicon.html
     */
    public function getFaviconType()
    {
        $data = pathinfo($this->faviconUrl);
        $extension = strtolower(ArrayHelper::getValue($data, "extension"));
        $last = 'x-icon';
        if (in_array($extension, ["png", "jpeg", "gif", "bmp"])) {
            $last = $extension;
        }
        if (in_array($extension, ["svg"])) {
            $last = "svg+xml";
        }
        return "image/".$last;
    }


    /**
     * Gets query for [[AuthAssignments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsAuthAssignments()
    {
        return $this->hasMany(CmsAuthAssignment::className(), ['cms_site_id' => 'id']);
    }

    /**
     * Внутреннее название сайта используемое в административной части
     * @return string
     */
    public function getInternalName()
    {
        if ($this->internal_name) {
            return $this->internal_name;
        }

        return $this->name;
    }

    /**
     * @return string
     */
    public function asText()
    {
        $name = $this->name;
        $this->name = $this->internalName;
        $result = parent::asText();
        $this->name = $name;
        return $result;
    }
}