<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\models\queries\CmsDealQuery;
use skeeks\cms\money\models\MoneyCurrency;
use skeeks\cms\money\Money;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopPayment;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "{{%crm_pact}}".
 *
 * @property int           $id
 * @property int           $created_by
 * @property int           $created_at
 * @property int           $start_at Дата начала сделки
 * @property int           $end_at Дата завершения сделки
 * @property int           $cms_deal_type_id Тип сделки
 * @property int           $cms_company_id Компания
 * @property int           $cms_user_id Контакт
 * @property string        $name Название
 * @property string        $description
 * @property string        $amount Значение цены
 * @property string        $currency_code Валюта
 * @property string        $period Период действия для периодического договора
 * @property int           $is_active Активность
 * @property int           $is_periodic Конечная или периодичная услуга?
 * @property int           $is_auto Авто продление + уведомления
 *
 * @property MoneyCurrency $currencyCode
 * @property CmsDealType   $dealType
 * @property CmsCompany    $company
 * @property CmsUser       $user
 * @property Money         $money
 *
 * @property string        $moneyAsText
 * @property string        $asShortText
 *
 * @property ShopPayment[] $payments
 * @property ShopBill[]    $bills
 *
 * @property bool          $isEnded
 */
class CmsDeal extends ActiveRecord
{
    use HasLogTrait;
    /**
     * @return string
     */
    public function getAsShortText()
    {
        return "Сделка №{$this->id} «".$this->name."»";
    }

    /**
     * Уведомить после создания?
     * @var bool
     */
    public $isCreateNotify = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_deal}}';
    }

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_AFTER_FIND, [$this, '_normalizeData']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, '_notifyCreate']);

        $this->on(self::EVENT_BEFORE_DELETE, [$this, '_beforeDelete']);
    }

    public function _beforeDelete()
    {
        return true;
        //TODO::доработать
        if ($this->getCmsDeal2bills()->joinWith('bill as bill')->andWhere(['is not', 'bill.paid_at', null])->exists()) {
            throw new Exception("Нельзя удалить эту сделку, потому что по ней есть оплаченные счета");
        }

        if ($bill2Deals = $this->getCmsDeal2bills()->all()) {
            foreach ($bill2Deals as $bill2Deal) {
                $bill2Deal->delete();
            }
        }
    }

    public function _normalizeData()
    {
        $this->amount = (float)$this->amount;
    }


    /**
     * Уведомление о создании счета на оплату заказчику услуги
     */
    public function _notifyCreate()
    {
        return true;
        //TODO::доработать
        if (!$this->isCreateNotify) {
            return false;
        }

        $emails = [];

        if ($this->executorCrmContractor->email) {
            $emails[] = $this->executorCrmContractor->email;
        }

        if ($this->customerCrmContractor->email) {
            $emails[] = $this->customerCrmContractor->email;
        }

        if ($emails) {

            \Yii::$app->mailer->view->theme->pathMap['@app/mail'][] = '@skeeks/crm/mail';

            \Yii::$app->mailer->compose('pact/created', [
                'model' => $this,
            ])
                ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName.''])
                ->setTo($emails)
                ->setSubject('Договор №'.$this->id." от ".\Yii::$app->formatter->asDate($this->created_at)." «{$this->name}»")
                ->send();
        }
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            \skeeks\cms\behaviors\RelationalBehavior::class,

            CmsLogBehavior::class     => [
                'class' => CmsLogBehavior::class,
                'relation_map' => [
                    'cms_deal_type_id' => 'dealType',
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                [
                    'is_periodic',
                    'is_auto',
                    'created_by',
                    'created_at',
                    'start_at',
                    'end_at',
                    'cms_deal_type_id',
                    'cms_user_id',
                    'cms_company_id',
                    'is_active',
                ],
                'integer',
            ],
            [['cms_deal_type_id', 'name'], 'required'],
            [['description'], 'string'],

            [['amount'], 'number'],
            [['name'], 'string', 'max' => 255],
            [['currency_code'], 'string', 'max' => 3],
            [['period'], 'string', 'max' => 10],
            [['cms_deal_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsDealType::class, 'targetAttribute' => ['cms_deal_type_id' => 'id']],
            [['currency_code'], 'exist', 'skipOnError' => true, 'targetClass' => MoneyCurrency::class, 'targetAttribute' => ['currency_code' => 'code']],
            [['cms_company_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsCompany::class, 'targetAttribute' => ['cms_company_id' => 'id']],
            [['cms_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::class, 'targetAttribute' => ['cms_user_id' => 'id']],

            [['period'], 'string'],
            [['isCreateNotify'], 'integer'],

            ['start_at', 'default', 'value' => \Yii::$app->formatter->asTimestamp(time())],

            [
                'period',
                function ($attribute) {
                    if ($this->is_periodic && !$this->period) {
                        $this->addError($attribute, 'Нужно заполнять для периодических сделок');
                    }

                    if (!$this->is_periodic && $this->period) {
                        $this->addError($attribute, 'Для разовых сделок заполнять не нужно: '.$this->period);
                    }
                },

            ],
            [
                ['cms_company_id'],
                'required',
                'when' => function () {
                    return !$this->cms_user_id;
                },
            ],
            [
                ['cms_user_id'],
                'required',
                'when' => function () {
                    return !$this->cms_company_id;
                },
            ],

            [
                'is_periodic',
                'default',
                'value' => 0,
            ],

            [
                'period',
                'default',
                'value' => null,
            ],

            [
                ['period'],
                'required',
                'when' => function () {
                    return $this->is_periodic;
                },
            ],
            [['bills'], 'safe'],
            [['payments'], 'safe'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'start_at'         => 'Дата начала сделки',
            'end_at'           => 'Дата завершения сделки',
            'cms_deal_type_id' => 'Тип сделки',
            'cms_user_id'                 => "Клиент",
            'cms_company_id'   => 'Компания',
            'name'             => 'Название',
            'description'      => 'Комментарий',
            'amount'           => 'Сумма',
            'currency_code'    => 'Валюта',
            'period'           => 'Период действия для периодической сделки',
            'is_active'        => 'Активность',
            'is_periodic'      => 'Периодическая сделка?',
            'payments'         => 'Платежи',
            'bills'            => 'Счета',
            'is_auto'          => 'Авто продление + уведомления',
            'isCreateNotify'   => 'Уведомить после создания сделки?',
        ]);
    }


    /**
     * @return string
     */
    public function getPeriodAsText()
    {
        return (string)ArrayHelper::getValue(CmsDealType::optionsForPeriod(), $this->period);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'is_periodic' => 'Сделки бывают разовые или постоянные',
            'period'      => 'Период действия сделки',
            'is_auto'     => 'Если выбрано "да", то клиент будет получать уведомления перед завершением действия сделки.',
            'start_at'    => 'Если не заполнить, то поле будет заполнено текущим временем автоматически.',
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrencyCode()
    {
        return $this->hasOne(MoneyCurrency::class, ['code' => 'currency_code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(CmsUser::class, ['id' => 'cms_user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDealType()
    {
        return $this->hasOne(CmsDealType::class, ['id' => 'cms_deal_type_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsDealType()
    {
        return $this->hasOne(CmsDealType::class, ['id' => 'cms_deal_type_id']);
    }


    /**
     * @return Money
     */
    public function getMoney()
    {
        return new Money($this->amount, $this->currency_code);
    }


    /**
     * @return string
     */
    public function asText()
    {
        $name = '';
        if ($this->company) {
            $name = $this->company->name;
        } elseif ($this->user) {
            $name = $this->user->shortDisplayName;
        }

        return $this->asShortText." ({$name})";
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCrmPayment2pacts()
    {
        return $this->hasMany(CrmPayment2pact::class, ['crm_deal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCrmPayments()
    {
        return $this->hasMany(CrmPayment::class, ['id' => 'crm_payment_id'])
            ->viaTable(CrmPayment2pact::tableName(), ['crm_deal_id' => 'id']);
    }

    /**
     * Услуга закончилась?
     *
     * @return bool
     */
    public function getIsEnded()
    {
        return (\Yii::$app->formatter->asTimestamp(time()) > $this->end_at);
    }


    /**
     *
     * Создать счет для оплаты услуги
     *
     * @param null $period
     * @return CrmBill
     */
    public function createBill($period = null, $amount = null)
    {
        $bill = new CrmBill();

        //$bill->description = "Оплата по договору: «" . $this->asText . "»";

        $bill->crmDeals = [$this->id];
        //$bill->sender_crm_contractor_id = $this->cms_company_id;


        //Если заказчик Физ лицо то оплата через интернет-реквайринг
        /*if ($this->customerCrmContractor->isIndividual) {
            $bill->type = CrmBill::TYPE_INTERNET_ACQUIRING;
            $bill->receiver_crm_contractor_id = CrmContractor::ID_TINKOFF;
        } else {
            throw new Exception('Не готово!');
            //Если не физ лицо тогда банковский перевод
            //$bill->type = CrmBill::TYPE_BANK_TRANSFER;
            //$bill->receiver_crm_contractor_id = CrmContractor::ID_SEMENOV_IP;


            $bill->type = CrmBill::TYPE_INTERNET_ACQUIRING;
            $bill->receiver_crm_contractor_id = CrmContractor::ID_TINKOFF;
        }*/


        //Если услуга периодичная
        if ($this->is_periodic) {
            $period = (int)$period;
            if (!$period) {
                $period = 1;
            }

            if ($this->period == CrmService::PERIOD_MONTH) {
                $bill->extend_pact_to = $this->end_at + (60 * 60 * 24 * 30 * $period);
            }
            if ($this->period == CrmService::PERIOD_YEAR) {
                $bill->extend_pact_to = $this->end_at + (60 * 60 * 24 * 365 * $period);
            }

            $bill->amount = $this->amount * $period;

        } else {
            $bill->extend_pact_to = null;
            $bill->amount = $this->amount;
        }


        return $bill;
    }


    public function notifyEnd($days = 15)
    {

    }

    /**
     * @return string
     */
    public function getMoneyAsText()
    {
        return (string)$this->money.($this->is_periodic ? "/".\skeeks\cms\helpers\StringHelper::strtolower($this->periodAsText) : "");
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsDeal2bills()
    {
        return $this->hasMany(CmsDeal2bill::className(), ['shop_bill_id' => 'id']);
    }

    /**
     * Gets query for [[CrmPayment2deals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsDeal2payments()
    {
        return $this->hasMany(CmsDeal2payment::className(), ['shop_payment_id' => 'id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(ShopPayment::class, ['id' => 'shop_payment_id'])
            ->viaTable(CmsDeal2payment::tableName(), ['cms_deal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBills()
    {
        return $this->hasMany(ShopBill::class, ['id' => 'shop_bill_id'])
            ->viaTable(CmsDeal2bill::tableName(), ['cms_deal_id' => 'id']);
    }

    /**
     * @return CmsUserQuery|\skeeks\cms\query\CmsActiveQuery
     */
    public static function find()
    {
        return (new CmsDealQuery(get_called_class()));
    }


}