<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\helpers\StringHelper;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_site}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property integer $cms_site_id сайт
 * @property string  $domain название домена
 * @property boolean $is_https работает по https?
 * @property boolean $is_main основной домен для сайта?
 *
 * @property string $url
 * @property CmsSite $cmsSite
 */
class CmsSiteDomain extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site_domain}}';
    }

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_UPDATE, [$this, '_checkIsMainDomain']);
        $this->on(self::EVENT_BEFORE_INSERT, [$this, '_checkIsMainDomain']);
    }

    public function _checkIsMainDomain($e)
    {
        $mainDomainForSite = $this->cmsSite->cmsSiteMainDomain;
        if ($mainDomainForSite && $mainDomainForSite->id != $this->id) {
            $mainDomainForSite->is_main = null;
            $mainDomainForSite->save();
        }
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id'          => Yii::t('skeeks/cms', 'ID'),
            'created_by'  => Yii::t('skeeks/cms', 'Created By'),
            'updated_by'  => Yii::t('skeeks/cms', 'Updated By'),
            'created_at'  => Yii::t('skeeks/cms', 'Created At'),
            'updated_at'  => Yii::t('skeeks/cms', 'Updated At'),
            'cms_site_id' => Yii::t('skeeks/cms', 'Site'),
            'domain'      => Yii::t('skeeks/cms', 'Domain'),
            'is_https'      => Yii::t('skeeks/cms', 'Работает по https?'),
            'is_main'      => Yii::t('skeeks/cms', 'Основной домен для сайта?'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['cms_site_id', 'domain'], 'required'],
            [['is_main', 'is_https'], 'boolean'],
            
            [['is_main', 'is_https'], 'default', 'value' => null],
            [['is_main', 'is_https'], function($attribute) {
                if (((int) $this->$attribute) == 0) {
                    $this->$attribute = null;
                }
            }],
            
            //TODO: добавить для null [['is_main', 'cms_site_id'], 'unique', 'targetAttribute' => ['is_main', 'cms_site_id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],
            
            [['domain'], 'string', 'max' => 255],
            [['domain'], 'unique'],

            [['domain'], 'trim'],
            [
                ['domain'],
                function ($attribute) {
                    $this->domain = StringHelper::strtolower($this->domain);
                },
            ],
            [
                ['domain'],
                function ($attribute) {
                    if(filter_var($this->domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                        return true;
                    }
                    $this->addError($attribute, "Доменное имя указано неверно");
                    return false;
                },
            ],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::class, ['id' => 'cms_site_id']);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return ($this->is_https ? "https://" : "http://") . $this->domain;
    }
}