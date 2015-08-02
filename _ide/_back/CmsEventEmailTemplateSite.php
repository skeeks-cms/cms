<?php

namespace skeeks\cms\models;

use Yii;

/**
 * This is the model class for table "{{%cms_event_email_template_site}}".
 *
 * @property integer $event_email_template_id
 * @property string $site_code
 *
 * @property CmsEventEmailTemplate $eventEmailTemplate
 * @property CmsSite $siteCode
 */
class CmsEventEmailTemplateSite extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_event_email_template_site}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_email_template_id', 'site_code'], 'required'],
            [['event_email_template_id'], 'integer'],
            [['site_code'], 'string', 'max' => 15],
            [['event_email_template_id', 'site_code'], 'unique', 'targetAttribute' => ['event_email_template_id', 'site_code'], 'message' => 'The combination of Event Email Template ID and Site Code has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'event_email_template_id' => Yii::t('app', 'Event Email Template ID'),
            'site_code' => Yii::t('app', 'Site Code'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventEmailTemplate()
    {
        return $this->hasOne(CmsEventEmailTemplate::className(), ['id' => 'event_email_template_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSiteCode()
    {
        return $this->hasOne(CmsSite::className(), ['code' => 'site_code']);
    }
}