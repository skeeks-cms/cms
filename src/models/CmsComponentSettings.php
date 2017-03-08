<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */
namespace skeeks\cms\models;
use skeeks\cms\base\Component;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use \Yii;
/**
 * This is the model class for table "{{%cms_component_settings}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $component
 * @property string $value
 * @property string $site_code
 * @property integer $user_id
 * @property string $lang_code
 * @property string $namespace
 *
 * @property CmsLang $lang
 * @property CmsSite $site
 * @property User $user
 */
class CmsComponentSettings extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_component_settings}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasJsonFieldsBehavior::className() =>
            [
                'class'     => HasJsonFieldsBehavior::className(),
                'fields'    => ['value']
            ]
        ]);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'user_id'], 'integer'],
            [['value'], 'safe'],
            [['component'], 'string', 'max' => 255],
            [['site_code'], 'string', 'max' => 15],
            [['lang_code'], 'string', 'max' => 5],
            [['namespace'], 'string', 'max' => 50]
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id'            => Yii::t('skeeks/cms', 'ID'),
            'value'         => Yii::t('skeeks/cms', 'Value'),
            'component'     => Yii::t('skeeks/cms', 'Component'),

            'site_code' => Yii::t('skeeks/cms', 'Site Code'),
            'user_id' => Yii::t('skeeks/cms', 'User ID'),
            'lang_code' => Yii::t('skeeks/cms', 'Lang Code'),
            'namespace' => Yii::t('skeeks/cms', 'Namespace'),
        ]);
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(CmsLang::className(), ['code' => 'lang_code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(CmsSite::className(), ['code' => 'site_code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(CmsUser::className(), ['id' => 'user_id']);
    }

    /**
     * @param Component $component
     * @return \yii\db\ActiveQuery
     */
    static public function baseQuery($component)
    {
        $query = static::find()->where([
            'component' => $component->className(),
        ]);

        if ($component->namespace)
        {
            $query->andWhere(['namespace' => $component->namespace]);
        }

        return $query;
    }


    /**
     * @param Component $component
     * @return \yii\db\ActiveQuery
     */
    static public function baseQuerySites($component)
    {
        $query = static::baseQuery($component)->andWhere([
            'or',
            ['!=', 'site_code', ""],
            ['not', ['site_code' => null]],
        ]);

        return $query;
    }

    /**
     * @param Component $component
     * @return \yii\db\ActiveQuery
     */
    static public function baseQueryUsers($component)
    {
        $query = static::baseQuery($component)->andWhere([
            'or',
            ['!=', 'user_id', ""],
            ['not', ['user_id' => null]],
        ]);

        return $query;
    }


    /**
     * @param Component $component
     * @return static
     */
    static public function createByComponentDefault($component)
    {
        $settings      = static::fetchByComponentDefault($component);

        if (!$settings)
        {
            $settings = new static([
                'component' => $component->className()
            ]);

            if ($component->namespace)
            {
                $settings->namespace = $component->namespace;
            }

            $settings->save();
        }

        return $settings;
    }

    /**
     * @param Component $component
     * @param int $user_id
     * @return static
     */
    static public function createByComponentUserId($component, $user_id)
    {
        $settings      = static::fetchByComponentUserId($component, $user_id);

        if (!$settings)
        {
            $settings = new static([
                'component' => $component->className(),
                'user_id'   => $user_id
            ]);

            if ($component->namespace)
            {
                $settings->namespace = $component->namespace;
            }

            $settings->save();
        }

        return $settings;
    }

    /**
     * @param Component $component
     * @param string $site_code
     * @return static
     */
    static public function createByComponentSiteCode($component, $site_code)
    {
        $settings      = static::fetchByComponentSiteCode($component, $site_code);

        if (!$settings)
        {
            $settings = new static([
                'component'     => $component->className(),
                'site_code'     => $site_code
            ]);

            if ($component->namespace)
            {
                $settings->namespace = $component->namespace;
            }

            $settings->save();
        }

        return $settings;
    }






    /**
     * Получение настроек для компонента
     *
     * @param Component $component
     * @return static
     */
    static public function fetchByComponentDefault($component)
    {
        return static::baseQuery($component)
            ->andWhere([
                'or',
                ['site_code' => ""],
                ['site_code' => null],
            ])
            ->andWhere([
                'or',
                ['lang_code' => ""],
                ['lang_code' => null],
            ])
            ->andWhere([
                'or',
                ['user_id' => ""],
                ['user_id' => null],
            ])->one()
        ;
    }


    /**
     * Получение настроек для компонента, и пользователя.
     *
     * @param Component $component  компонент с настройками
     * @param int $user_id          id пользователя
     * @return static
     */
    static public function fetchByComponentUserId($component, $user_id)
    {
        return static::baseQuery($component)->andWhere(['user_id' => (int) $user_id])->one();
    }

    /**
     * Получение настроек для компонента по коду сайта.
     *
     * @param Component $component  компонент с настройками
     * @param string $site_code     код сайта
     * @return static
     */
    static public function fetchByComponentSiteCode($component, $site_code)
    {
        return static::baseQuery($component)->andWhere(['site_code' => (string) $site_code])->one();
    }









    /**
     * Получение настроек для компонента по коду сайта.
     *
     * @param Component $component компонент с настройками
     * @param CmsSite $site код сайта
     * @return static
     */
    static public function fetchByComponentSite($component, $site)
    {
        return static::fetchByComponentSiteCode($component, $site->code);
    }

    /**
     * Получение настроек для компонента по коду сайта.
     *
     * @param Component $component компонент с настройками
     * @param User $site код сайта
     * @return static
     */
    static public function fetchByComponentUser($component, $user)
    {
        return static::fetchByComponentUserId($component, $user->id);
    }


    /**
     * @param $settingAttribute
     * @param $value
     * @return $this
     */
    public function setSettingValue($settingAttribute, $settingValue)
    {
        $values = $this->value;
        $values[$settingAttribute] = $settingValue;
        $this->value = $values;
        return $this;
    }
}
