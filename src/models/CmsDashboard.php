<?php

namespace skeeks\cms\models;

use Yii;

/**
 * This is the model class for table "{{%cms_dashboard}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property integer $cms_user_id
 * @property integer $priority
 * @property string $columns
 * @property string $columns_settings
 *
 * @property CmsUser $cmsUser
 * @property CmsDashboardWidget[] $cmsDashboardWidgets
 */
class CmsDashboard extends \skeeks\cms\models\Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_dashboard}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_user_id', 'priority', 'columns'], 'integer'],
            [['name'], 'required'],
            [['columns_settings'], 'string'],
            [['name'], 'string', 'max' => 255],

            [['priority'], 'default', 'value' => 100],
            [['columns'], 'default', 'value' => 1],
            [['columns'], 'integer', 'max' => 6, 'min' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeeks/cms', 'ID'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'cms_user_id' => Yii::t('skeeks/cms', 'Cms User ID'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'columns' => Yii::t('skeeks/cms', 'Number of columns'),
            'columns_settings' => Yii::t('skeeks/cms', 'Columns Settings'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(CmsUser::className(), ['id' => 'cms_user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsDashboardWidgets()
    {
        return $this->hasMany(CmsDashboardWidget::className(), ['cms_dashboard_id' => 'id']);
    }

}