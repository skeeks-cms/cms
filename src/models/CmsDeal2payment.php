<?php

namespace skeeks\cms\models;

use skeeks\cms\shop\models\ShopPayment;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int         $id
 * @property int         $created_by
 * @property int         $created_at
 * @property int         $cms_deal_id Сделка
 * @property int         $shop_payment_id Платеж
 *
 * @property CmsDeal     $deal
 * @property ShopPayment $payment
 */
class CmsDeal2payment extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_deal2payment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_deal_id', 'shop_payment_id'], 'integer'],
            [['cms_deal_id', 'shop_payment_id'], 'required'],
            [['cms_deal_id', 'shop_payment_id'], 'unique', 'targetAttribute' => ['cms_deal_id', 'shop_payment_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'shop_payment_id' => Yii::t('app', 'Контрагент'),
            'cms_deal_id'     => Yii::t('app', 'Компания'),
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
    public function getPayment()
    {
        return $this->hasOne(ShopPayment::class, ['id' => 'shop_payment_id']);
    }
}