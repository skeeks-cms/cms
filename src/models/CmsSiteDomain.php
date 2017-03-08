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
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\traits\ValidateRulesTrait;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\base\Event;
use yii\db\BaseActiveRecord;

/**
 * This is the model class for table "{{%cms_site}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $site_code
 * @property string $domain
 *
 *  @property CmsSite $cmsSite
 */
class CmsSiteDomain extends Core
{
    use ValidateRulesTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site_domain}}';
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
            'site_code' => Yii::t('skeeks/cms', 'Site'),
            'domain' => Yii::t('skeeks/cms', 'Domain'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['site_code', 'domain'], 'required'],
            [['site_code'], 'string', 'max' => 15],
            [['domain'], 'string', 'max' => 255],
            [['domain', 'site_code'], 'unique', 'targetAttribute' => ['domain', 'site_code'], 'message' => \Yii::t('skeeks/cms','The combination of Site Code and Domain has already been taken.')]
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::className(), ['code' => 'site_code']);
    }
}