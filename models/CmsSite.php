<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Widget;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\models\Tree;
use skeeks\cms\traits\ValidateRulesTrait;
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
 * @property CmsTree[] $cmsTrees
 * @property CmsStorageFile $image
 */
class CmsSite extends Core
{
    use ValidateRulesTrait;

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
        foreach ($this->cmsTrees as $tree)
        {
            $tree->delete();
            /*if (!$tree->delete())
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
                'class'     => HasStorageFile::className(),
                'fields'    => ['image_id']
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
        if ($this->def == Cms::BOOL_Y)
        {
            static::updateAll(
                [
                    'def' => Cms::BOOL_N
                ],
                ['!=', 'id', $this->id]
            );

            $this->active   = Cms::BOOL_Y; //сайт по умолчанию всегда активный
        }

    }
    /**
     * @param Event $e
     * @throws Exception
     */
    public function beforeInsertChecks(Event $e)
    {
        //Если этот элемент по умолчанию выбран, то все остальны нужно сбросить.
        if ($this->def == Cms::BOOL_Y)
        {
            static::updateAll([
                'def' => Cms::BOOL_N
            ]);

            $this->active   = Cms::BOOL_Y; //сайт по умолчанию всегда активный
        }

    }

    public function createTreeAfterInsert(Event $e)
    {
        $tree = new Tree([
            'name'      => 'Главная страница',
            'site_code' => $this->code,
        ]);

        if (!$tree->save(false))
        {
            throw new Exception('Failed to create a section of the tree');
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
            'server_name' => Yii::t('skeeks/cms', 'Server Name'),
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
            [['name', 'server_name', 'description'], 'string', 'max' => 255],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            [['server_name'], 'validateServerName'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            ['def', 'default', 'value' => Cms::BOOL_N],
            [['image_id'], 'integer'],
        ]);
    }

    static public $sites = [];

    /**
     * @param (string) $code
     * @return static
     */
    static public function getByCode($code)
    {
        if (!array_key_exists($code, static::$sites))
        {
            static::$sites[$code] = static::find()->where(['code' => (string) $code])->active()->one();
        }

        return static::$sites[$code];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteDomains()
    {
        return $this->hasMany(CmsSiteDomain::className(), ['site_code' => 'code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTrees()
    {
        return $this->hasMany(CmsTree::className(), ['site_code' => 'code']);
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->server_name)
        {
            return '//' . $this->server_name;
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