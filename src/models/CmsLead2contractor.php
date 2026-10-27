<?php

namespace skeeks\cms\models;

use yii\helpers\ArrayHelper;

/**
 * Requisites of a lead: a link to a contractor (legal entity or sole
 * proprietor), the same as {@see CmsCompany2contractor} for a company.
 *
 * @property int           $id
 * @property int           $cms_lead_id
 * @property int           $cms_contractor_id
 *
 * @property CmsLead       $cmsLead
 * @property CmsContractor $cmsContractor
 */
class CmsLead2contractor extends \skeeks\cms\base\ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_lead2contractor}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_lead_id', 'cms_contractor_id'], 'integer'],
            [['cms_lead_id', 'cms_contractor_id'], 'required'],
            [['cms_lead_id', 'cms_contractor_id'], 'unique', 'targetAttribute' => ['cms_lead_id', 'cms_contractor_id'],
                'message' => 'Эти реквизиты уже добавлены к сделке.'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_contractor_id' => 'Контрагент',
            'cms_lead_id'       => 'Сделка',
        ]);
    }

    public function getCmsContractor()
    {
        return $this->hasOne(CmsContractor::class, ['id' => 'cms_contractor_id']);
    }

    public function getCmsLead()
    {
        return $this->hasOne(CmsLead::class, ['id' => 'cms_lead_id']);
    }
}
