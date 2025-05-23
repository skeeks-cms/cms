<?php

namespace skeeks\cms\models;

use skeeks\cms\shop\models\ShopBill;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int      $id
 * @property int      $created_by
 * @property int      $created_at
 * @property int      $cms_deal_id Сделка
 * @property int      $shop_bill_id Счет
 *
 * @property CmsDeal  $deal
 * @property ShopBill $bill
 */
class CmsDeal2bill extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_deal2bill}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_deal_id', 'shop_bill_id'], 'integer'],
            [['cms_deal_id', 'shop_bill_id'], 'required'],
            [['cms_deal_id', 'shop_bill_id'], 'unique', 'targetAttribute' => ['cms_deal_id', 'shop_bill_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'shop_bill_id' => Yii::t('app', 'Контрагент'),
            'cms_deal_id'  => Yii::t('app', 'Компания'),
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
    public function getDeal()
    {
        return $this->hasOne(CmsDeal::class, ['id' => 'cms_deal_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBill()
    {
        return $this->hasOne(ShopBill::class, ['id' => 'shop_bill_id']);
    }
}