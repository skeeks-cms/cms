<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\base\Theme;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\Serialize;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_site_theme".
 *
 * @property int            $id
 * @property int|null       $created_by
 * @property int|null       $updated_by
 * @property int|null       $created_at
 * @property int|null       $updated_at
 * @property int            $cms_site_id
 * @property string         $code Уникальный код темы
 *
 * @property string|null    $config Настройки темы
 *
 * @property string|null    $name Название темы
 * @property string|null    $description Описание темы
 *
 * @property int|null       $cms_image_id Фото
 * @property int            $priority
 * @property int|null       $is_active Активирована?
 *
 * ***
 *
 * @property string         $themeName
 * @property string         $themeDescription
 * @property string         $themeImageSrc
 *
 * @property Theme          $objectTheme
 * @property CmsStorageFile $cmsImage
 * @property CmsSite        $cmsSite
 */
class CmsTheme extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_theme}}';
    }

    public function init()
    {
        $this->on(static::EVENT_BEFORE_UPDATE, [$this, '_beforeUpdate']);
        return parent::init();
    }

    public function _beforeUpdate()
    {
        //Перед обновлением активности шаблона, нужно деактивировать другие у этого сайта.
        if ($this->is_active == 1 && $this->isAttributeChanged("is_active")) {
            static::updateAll(['is_active' => null], ['cms_site_id' => $this->cms_site_id]);
        }
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFile::className() => [
                'class'  => HasStorageFile::className(),
                'fields' => ['cms_image_id'],
            ],

            Serialize::class => [
                'class'  => Serialize::class,
                'fields' => ['config'],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('skeeks/cms', 'ID'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'cms_image_id', 'priority', 'is_active'], 'integer'],
            [['code'], 'required'],
            [['config'], 'safe'],
            [['code', 'name', 'description'], 'string', 'max' => 255],
            [['cms_site_id', 'code'], 'unique', 'targetAttribute' => ['cms_site_id', 'code']],
            //[['cms_site_id', 'is_active'], 'unique', 'targetAttribute' => ['cms_site_id', 'is_active']],
            [['cms_image_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsStorageFile::className(), 'targetAttribute' => ['cms_image_id' => 'id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['updated_by' => 'id']],
            [['is_active'], 'default', 'value' => null],

            //TODO: добавить для null [['is_main', 'cms_site_id'], 'unique', 'targetAttribute' => ['is_main', 'cms_site_id']],
            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        $class = \Yii::$app->skeeks->siteClass;
        return $this->hasOne($class, ['id' => 'cms_site_id']);
    }

    /**
     * @return string
     */
    public function getCmsImage()
    {
        return $this->hasOne(CmsStorageFile::class, ['id' => 'cms_image_id']);
    }

    protected $_themeObject = null;

    /**
     * @return false|\skeeks\cms\base\Theme
     * @throws \yii\base\InvalidConfigException
     */
    public function getObjectTheme()
    {
        if ($this->_themeObject === null) {
            $themes = \Yii::$app->view->availableThemes;
            $themeData = \yii\helpers\ArrayHelper::getValue($themes, $this->code);
            $theme = false;
            if ($themeData) {
                /**
                 * @var $theme \skeeks\cms\base\Theme
                 */
                $theme = \Yii::createObject($themeData);
                $this->loadConfigToTheme($theme);
            }

            $this->_themeObject = $theme;
        }

        return $this->_themeObject;
    }

    /**
     * @return string|null
     */
    public function getThemeName()
    {
        return $this->name ? $this->name : $this->objectTheme->descriptor->name;
    }

    /**
     * @return string|null
     */
    public function getThemeDescription()
    {
        return $this->description ? $this->description : $this->objectTheme->descriptor->description;
    }
    /**
     * @return string|null
     */
    public function getThemeImageSrc()
    {
        return $this->cmsImage ? $this->cmsImage->src : $this->objectTheme->descriptor->image;
    }


    /**
     * @param Theme $theme
     * @return $this
     */
    public function loadConfigToTheme(Theme $theme)
    {
        if ($this->config) {
            foreach ($this->config as $key => $value) {
                if ($theme->canSetProperty($key)) {
                    $theme->{$key} = $value;
                }
            }
        }
        return $this;
    }
}