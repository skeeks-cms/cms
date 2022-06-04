<?php

namespace skeeks\cms\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int           $id
 * @property int           $created_by
 * @property int           $updated_by
 * @property int           $created_at
 * @property int           $updated_at
 * @property int           $cms_user_id Пользователь
 * @property int           $cms_contractor_id Контакт
 *
 * @property CmsContractor $cmsContractor
 * @property CmsUser       $cmsUser
 */
class CmsContractorMap extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_contractor_map}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_user_id', 'cms_contractor_id'], 'integer'],
            [['cms_user_id', 'cms_contractor_id'], 'required'],
            [['cms_user_id', 'cms_contractor_id'], 'unique', 'targetAttribute' => ['cms_user_id', 'cms_contractor_id']],

            [['cms_contractor_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsContractor::class, 'targetAttribute' => ['cms_contractor_id' => 'id']],
            [['cms_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::class, 'targetAttribute' => ['cms_user_id' => 'id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_contractor_id' => Yii::t('app', 'Контрагент'),
            'cms_user_id'       => Yii::t('app', 'Пользователь'),
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
    public function getCrmContact()
    {
        return $this->hasOne(CmsContractor::class, ['id' => 'cms_contractor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        $userClass = \Yii::$app->user->identityClass;
        return $this->hasOne($userClass, ['id' => 'cms_user_id']);
    }
}