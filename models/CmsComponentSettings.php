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
            [['site_code', 'lang_code'], 'string', 'max' => 5],
            [['namespace'], 'string', 'max' => 50]
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id'            => Yii::t('app', 'ID'),
            'value'         => Yii::t('app', 'Значение'),
            'component'     => Yii::t('app', 'Компонент'),

            'site_code' => Yii::t('app', 'Site Code'),
            'user_id' => Yii::t('app', 'User ID'),
            'lang_code' => Yii::t('app', 'Lang Code'),
            'namespace' => Yii::t('app', 'Namespace'),
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
     * @return static
     */
    static public function createByComponent($component)
    {
        $settings      = static::fetchByComponent($component);

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
     * Получение настроек для компонента
     *
     * @param Component $component
     * @return static
     */
    static public function fetchByComponent($component)
    {
        return static::baseQuery($component)->one();
    }

    /**
     * Получение настроек для компонента, и пользователя.
     *
     * @param Component $component
     * @param int $user_id
     * @return static
     */
    static public function fetchByComponentUserId($component, $user_id)
    {
        return static::baseQuery($component)->andWhere(['user_id' => (int) $user_id])->one();
    }

    /**
     * Получение настроек для компонента, и сайта.
     *
     * @param Component $component
     * @param string $site_code
     * @return static
     */
    static public function fetchByComponentSiteCode($component, $site_code)
    {
        return static::baseQuery($component)->andWhere(['site_code' => (string) $site_code])->one();
    }
}
