<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\ArrayHelper;
/**
 * @property int           $id
 * @property int           $created_by
 * @property int           $created_at
 * @property int           $cms_contractor_id
 * @property string        $bank_name Банк
 * @property string        $bic БИК
 * @property string|null   $correspondent_account Корреспондентский счёт
 * @property string        $checking_account Расчетный счет
 * @property string        $bank_address Адрес банка
 * @property string        $comment Комментарий
 * @property int           $is_active
 * @property int           $sort
 *
 * @property CmsContractor $contractor
 */
class CmsContractorBank extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_contractor_bank';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'created_at', 'cms_contractor_id'], 'integer'],
            [['cms_contractor_id', 'bank_name', 'bic', 'checking_account'], 'required'],
            [['checking_account', 'correspondent_account'], 'string', 'max' => 20],
            [['bic'], 'string', 'max' => 12],
            [['comment'], 'string'],
            [['bank_name'], 'string', 'max' => 255],
            [['cms_contractor_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsContractor::class, 'targetAttribute' => ['cms_contractor_id' => 'id']],

            [['checking_account'], 'unique', 'targetAttribute' => ['checking_account', 'cms_contractor_id', 'bic']],

            [['is_active'], 'integer'],
            [['sort'], 'integer'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'id'                    => 'ID',
            'cms_contractor_id'     => 'Контрагент',
            'bank_name'             => 'Банк',
            'bic'                   => 'БИК',
            'correspondent_account' => 'Корреспондентский счёт',
            'checking_account'      => 'Расчетный счет',
            'bank_address'          => 'Адрес банка',
            'comment'               => 'Комментарий',
            'sort'                  => 'Сортировка',
            'is_active'             => 'Активность',
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContractor()
    {
        return $this->hasOne(CmsContractor::class, ['id' => 'cms_contractor_id']);
    }

    public function asText()
    {
        return "{$this->bank_name} / БИК {$this->bic} / Счет: {$this->checking_account}";
    }
}