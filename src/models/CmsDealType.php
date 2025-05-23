<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\ArrayHelper;
/**
 * @property int       $id
 * @property int       $created_by
 * @property int       $updated_by
 * @property int       $created_at
 * @property int       $updated_at
 * @property string    $name
 * @property string    $description
 * @property string    $period
 * @property int       $is_periodic Конечная или периодичная услуга?
 *
 * @property string    $periodAsText
 * @property CmsDeal[] $deals
 */
class CmsDealType extends ActiveRecord
{
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_deal_type}}';
    }
    /**
     * @return string
     */
    public function getPeriodAsText()
    {
        return (string)ArrayHelper::getValue(self::optionsForPeriod(), $this->period);
    }

    static public function optionsForPeriod()
    {
        return [
            self::PERIOD_MONTH => 'Мес',
            self::PERIOD_YEAR  => 'Год',
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'created_at', 'is_periodic'], 'integer'],
            [['name'], 'required'],
            [['description'], 'string'],
            [['period'], 'string'],
            [['name'], 'string', 'max' => 255],


            [
                'period',
                function ($attribute) {
                    if ($this->is_periodic && !$this->period) {
                        $this->addError($attribute, 'Нужно заполнять для периодических сделок');
                    }

                    if (!$this->is_periodic && $this->period) {
                        $this->addError($attribute, 'Для разовых сделок заполнять не нужно');
                    }
                },

            ],

            [
                'period',
                'default',
                'value' => function () {
                    if ($this->is_periodic) {
                        return self::PERIOD_MONTH;
                    } else {
                        return null;
                    }
                },
            ],

        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'is_periodic' => 'Периодическая сделка?',
            'period'      => 'Период',
            'name'        => 'Название',
            'description' => 'Комментарий',
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'is_periodic' => 'Сделки бывают разовые или постоянные',
            'period'      => 'Период действия сделки',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCrmDeals()
    {
        return $this->hasMany(CrmDeal::class, ['cms_deal_type_id' => 'id']);
    }

    /**
     * @return string
     */
    public function asText()
    {
        return $this->name;
    }
}