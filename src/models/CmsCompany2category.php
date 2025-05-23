<?php

namespace skeeks\cms\models;

use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int                $id
 * @property int                $cms_company_id Компания
 * @property int                $cms_company_category_id Пользователь
 *
 * @property CmsCompany         $cmsCompany
 * @property CmsCompanyCategory $cmsCompanyCategory
 */
class CmsCompany2category extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_company2category}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_company_id', 'cms_company_category_id'], 'integer'],
            [['cms_company_id', 'cms_company_category_id'], 'required'],
            [['cms_company_id', 'cms_company_category_id'], 'unique', 'targetAttribute' => ['cms_company_id', 'cms_company_category_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_company_category_id' => "Категория",
            'cms_company_id'          => "Компания",
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
    public function getCmsCompanyCategory()
    {
        return $this->hasOne(CmsCompanyCategory::class, ['id' => 'cms_company_category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }
}