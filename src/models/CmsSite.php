<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_site}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $active
 * @property string $def
 * @property integer $priority
 * @property string $code
 * @property string $name
 * @property string $server_name
 * @property string $description
 * @property integer $image_id
 *
 * @property string $url
 *
 * @property CmsTree $rootCmsTree
 * @property CmsLang $cmsLang
 * @property CmsSiteDomain[] $cmsSiteDomains
 * @property CmsSiteDomain $cmsSiteMainDomain
 * @property CmsTree[] $cmsTrees
 * @property CmsStorageFile $image
 */
class CmsSite extends Core
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

            HasStorageFile::className() =>
                [
                    'class' => HasStorageFile::className(),
                    'fields' => ['image_id']
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
        if ($this->def == Cms::BOOL_Y) {
            static::updateAll(
                [
                    'def' => Cms::BOOL_N
                ],
                ['!=', 'id', $this->id]
            );

            $this->active = Cms::BOOL_Y; //сайт по умолчанию всегда активный
        }

    }

    /**
     * @param Event $e
     * @throws Exception
     */
    public function beforeInsertChecks(Event $e)
    {
        //Если этот элемент по умолчанию выбран, то все остальны нужно сбросить.
        if ($this->def == Cms::BOOL_Y) {
            static::updateAll([
                'def' => Cms::BOOL_N
            ]);

            $this->active = Cms::BOOL_Y; //сайт по умолчанию всегда активный
        }

    }

    public function createTreeAfterInsert(Event $e)
    {
        $tree = new Tree([
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
            'id' => Yii::t('skeeks/cms', 'ID'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'def' => Yii::t('skeeks/cms', 'Default'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'code' => Yii::t('skeeks/cms', 'Code'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'description' => Yii::t('skeeks/cms', 'Description'),
            'image_id' => Yii::t('skeeks/cms', 'Image'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority'], 'integer'],
            [['code', 'name'], 'required'],
            [['active', 'def'], 'string', 'max' => 1],
            [['code'], 'string', 'max' => 15],
            [['name', 'description'], 'string', 'max' => 255],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            ['def', 'default', 'value' => Cms::BOOL_N],
            [['image_id'], 'safe'],

            [
                ['image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'maxFiles' => 1,
                'maxSize' => 1024 * 1024 * 2,
                'minSize' => 1024,
            ],
        ]);
    }

    public function validateCode($attribute)
    {
        if (!preg_match('/^[a-zA-Z]{1}[a-zA-Z0-9-]{1,255}$/', $this->$attribute)) {
            $this->addError($attribute, \Yii::t('skeeks/cms',
                'Use only letters of the alphabet in lower or upper case and numbers, the first character of the letter (Example {code})',
                ['code' => 'code1']));
        }
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
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteMainDomain()
    {
        $query = $this->getCmsSiteDomains()->andWhere(['is_main' => 1]);
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
     * @return string
     */
    public function getUrl()
    {
        if ($this->cmsSiteMainDomain) {
            return (($this->cmsSiteMainDomain->is_https ? "https:" : "http:") . "//" . $this->cmsSiteMainDomain->domain);
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
}