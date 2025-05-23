<?php

namespace skeeks\cms\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int        $id
 * @property int        $created_by
 * @property int        $updated_by
 * @property int        $created_at
 * @property int        $updated_at
 * @property int        $cms_project_id Компания
 * @property int        $cms_user_id Пользователь
 *
 * @property CmsUser    $cmsUser
 * @property Cmsproject $cmsProject
 */
class CmsProject2manager extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_project2manager}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_project_id', 'cms_user_id'], 'integer'],
            [['cms_project_id', 'cms_user_id'], 'required'],
            [['cms_project_id', 'cms_user_id'], 'unique', 'targetAttribute' => ['cms_project_id', 'cms_user_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id'    => Yii::t('app', 'Контрагент'),
            'cms_project_id' => Yii::t('app', 'Проект'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        $userClass = \Yii::$app->user->identityClass;
        return $this->hasOne($userClass, ['id' => 'cms_user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsProject()
    {
        return $this->hasOne(Cmsproject::class, ['id' => 'cms_project_id']);
    }
}