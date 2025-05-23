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
 * @property int           $cms_company_id Компания
 * @property int           $cms_contractor_id Контрагент
 *
 * @property CmsContractor $cmsContractor
 * @property CmsCompany    $cmsCompany
 */
class CmsCompany2contractor extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_company2contractor}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_company_id', 'cms_contractor_id'], 'integer'],
            [['cms_company_id', 'cms_contractor_id'], 'required'],
            [['cms_company_id', 'cms_contractor_id'], 'unique', 'targetAttribute' => ['cms_company_id', 'cms_contractor_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_contractor_id' => Yii::t('app', 'Контрагент'),
            'cms_company_id'    => Yii::t('app', 'Компания'),
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
    public function getCmsContractor()
    {
        return $this->hasOne(CmsContractor::class, ['id' => 'cms_contractor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }
}