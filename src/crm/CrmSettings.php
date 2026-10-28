<?php

namespace skeeks\cms\crm;

use skeeks\cms\base\Component;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use yii\helpers\ArrayHelper;

/**
 * Settings of the site CRM (component `crmSettings`, «Настройки → Компоненты»,
 * also «Повторы клиентов» in the settings menu of the deals kanban). Kept per
 * site, never per user; a site that never saved them works in the strict mode.
 *
 * «Повторы клиентов» (П2а): what a strong match — a phone, an email or an INN
 * of another company — does. Strict: saved only with a reason. Free (as in
 * amoCRM and Bitrix24): saved without a reason, the employee is only warned.
 * In both modes the pair is marked as a possible duplicate, the hints and the
 * requests stay, a hidden client stays hidden. Read by CrmDuplicateGuard only.
 */
class CrmSettings extends Component
{
    public const DUPLICATES_STRICT = 'strict';
    public const DUPLICATES_FREE = 'free';

    public $duplicateMode = self::DUPLICATES_STRICT;

    /** П2д: a payment protects the client this many months (crm\CrmProtection). */
    public $protectionMonths = 12;

    /** The site decides, not a user. */
    protected $_overridePath = [self::OVERRIDE_DEFAULT, self::OVERRIDE_SITE];

    public static function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'CRM: клиенты и сделки',
            'description' => 'Как CRM относится к повторам клиентов',
        ]);
    }

    public static function duplicateModes(): array
    {
        return [
            self::DUPLICATES_STRICT => 'Строгий — нельзя сохранить телефон, e-mail или ИНН другой компании без причины',
            self::DUPLICATES_FREE => 'Свободный — можно, система только предупреждает',
        ];
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['duplicateMode'], 'required'],
            [['duplicateMode'], 'in', 'range' => array_keys(self::duplicateModes())],
            [['protectionMonths'], 'integer', 'min' => 1, 'max' => 120],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'duplicateMode' => 'Повторы клиентов',
            'protectionMonths' => 'Защита после платежа, месяцев',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'duplicateMode' => 'В обоих режимах при вводе показываются совпадения в базе, а новая запись отмечается как возможный повтор, чтобы её потом можно было объединить. Чужой клиент остаётся скрытым по уровню доступа.',
            'protectionMonths' => 'Клиент, который платил за это время или у которого есть действующая услуга, — «Под защитой»: другой менеджер работает с ним только по согласованию с основным ответственным. Остальные — «Свободные».',
        ]);
    }

    public function getConfigFormFields()
    {
        return [
            'duplicates' => [
                'class' => FieldSet::class,
                'name' => 'Повторы клиентов',
                'fields' => [
                    'duplicateMode' => [
                        'class' => SelectField::class,
                        'items' => self::duplicateModes(),
                        'allowNull' => false,
                    ],
                ],
            ],
            'protection' => [
                'class' => FieldSet::class,
                'name' => 'Защита клиента',
                'fields' => [
                    'protectionMonths' => [
                        'class' => \skeeks\yii2\form\fields\NumberField::class,
                    ],
                ],
            ],
        ];
    }
}
