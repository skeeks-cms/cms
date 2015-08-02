<?php

namespace skeeks\cms\models;

use Yii;

/**
 * This is the model class for table "{{%cms_event_email_template}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $event_name
 * @property string $active
 * @property string $email_from
 * @property string $email_to
 * @property string $subject
 * @property string $message
 * @property string $body_type
 * @property string $bcc
 * @property string $reply_to
 * @property string $cc
 * @property string $in_reply_to
 * @property string $priority
 * @property string $field1_name
 * @property string $field1_value
 * @property string $field2_name
 * @property string $field2_value
 * @property string $message_php
 * @property string $template
 * @property string $additional_field
 *
 * @property CmsEventEmailTemplateSite[] $cmsEventEmailTemplateSites
 * @property CmsSite[] $siteCodes
 */
class CmsEventEmailTemplate extends \skeeks\cms\models\Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_event_email_template}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['event_name'], 'required'],
            [['message', 'bcc', 'message_php', 'additional_field'], 'string'],
            [['event_name', 'email_from', 'email_to', 'subject', 'reply_to', 'cc', 'in_reply_to', 'field1_value', 'field2_value', 'template'], 'string', 'max' => 255],
            [['active'], 'string', 'max' => 1],
            [['body_type'], 'string', 'max' => 6],
            [['priority', 'field1_name', 'field2_name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'event_name' => Yii::t('app', 'Event Name'),
            'active' => Yii::t('app', 'Active'),
            'email_from' => Yii::t('app', 'Email From'),
            'email_to' => Yii::t('app', 'Email To'),
            'subject' => Yii::t('app', 'Subject'),
            'message' => Yii::t('app', 'Message'),
            'body_type' => Yii::t('app', 'Body Type'),
            'bcc' => Yii::t('app', 'Bcc'),
            'reply_to' => Yii::t('app', 'Reply To'),
            'cc' => Yii::t('app', 'Cc'),
            'in_reply_to' => Yii::t('app', 'In Reply To'),
            'priority' => Yii::t('app', 'Priority'),
            'field1_name' => Yii::t('app', 'Field1 Name'),
            'field1_value' => Yii::t('app', 'Field1 Value'),
            'field2_name' => Yii::t('app', 'Field2 Name'),
            'field2_value' => Yii::t('app', 'Field2 Value'),
            'message_php' => Yii::t('app', 'Message Php'),
            'template' => Yii::t('app', 'Template'),
            'additional_field' => Yii::t('app', 'Additional Field'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsEventEmailTemplateSites()
    {
        return $this->hasMany(CmsEventEmailTemplateSite::className(), ['event_email_template_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSiteCodes()
    {
        return $this->hasMany(CmsSite::className(), ['code' => 'site_code'])->viaTable('{{%cms_event_email_template_site}}', ['event_email_template_id' => 'id']);
    }
}