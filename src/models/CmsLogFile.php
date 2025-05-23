<?php

namespace skeeks\cms\models;

use Yii;

/**
 * This is the model class for table "{{%cms_content_element_file}}".
 *
 * @property integer        $id
 * @property integer        $created_by
 * @property integer        $updated_by
 * @property integer        $created_at
 * @property integer        $updated_at
 * @property integer        $storage_file_id
 * @property integer        $log_id
 * @property integer        $priority
 *
 * @property CmsLog         $cmsLog
 * @property CmsStorageFile $storageFile
 */
class CmsLogFile extends \skeeks\cms\models\Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_log_file}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at',
                    'storage_file_id',
                    'cms_log_id',
                    'priority',
                ],
                'integer',
            ],
            [['storage_file_id', 'cms_log_id'], 'required'],
            [
                ['storage_file_id', 'cms_log_id'],
                'unique',
                'targetAttribute' => ['storage_file_id', 'cms_log_id'],
                'message'         => \Yii::t('skeeks/cms',
                    'The combination of Storage File ID and Content Element ID has already been taken.'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                 => Yii::t('skeeks/cms', 'ID'),
            'created_by'         => Yii::t('skeeks/cms', 'Created By'),
            'updated_by'         => Yii::t('skeeks/cms', 'Updated By'),
            'created_at'         => Yii::t('skeeks/cms', 'Created At'),
            'updated_at'         => Yii::t('skeeks/cms', 'Updated At'),
            'storage_file_id'    => Yii::t('skeeks/cms', 'Storage File ID'),
            'cms_log_id' => Yii::t('skeeks/cms', 'Content Element ID'),
            'priority'           => Yii::t('skeeks/cms', 'Priority'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLog()
    {
        return $this->hasOne(CmsLog::className(), ['id' => 'cms_log_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStorageFile()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'storage_file_id']);
    }
}