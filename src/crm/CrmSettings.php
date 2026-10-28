<?php

namespace skeeks\cms\crm;

use skeeks\cms\base\Component;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStage;
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
 *
 * «Исходящие продажи» (П2д): also the pipeline of outbound sales and the
 * stages a personal offer sent from its deal moves the deal to
 * (OutboundSales). Empty — the pipeline with the code `outbound` and the
 * stages «КП отправлено» and «Смотрел» by name.
 */
class CrmSettings extends Component
{
    public const DUPLICATES_STRICT = 'strict';
    public const DUPLICATES_FREE = 'free';

    public $duplicateMode = self::DUPLICATES_STRICT;

    /** П2д: a payment protects the client this many months (crm\CrmProtection). */
    public $protectionMonths = 12;

    /**
     * «Исходящие продажи» (П2д): defaults of a new mailing campaign — a call
     * task when a recipient views the presentation or offer, a deal on that
     * view. A campaign copies them when created and may change them.
     */
    public $outgoingTaskOnView = 1;
    public $outgoingLeadOnView = 0;

    /** @var int|null personal offers: the pipeline; null — the one with the code `outbound` */
    public $outboundPipelineId;
    /** @var int|null the stage after a presentation or a КП is sent from the deal; null — «КП отправлено» */
    public $proposalSentStageId;
    /** @var int|null the stage after the client opened it; null — «Смотрел» */
    public $proposalViewedStageId;

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
            [['outgoingTaskOnView', 'outgoingLeadOnView'], 'boolean'],
            [['protectionMonths'], 'integer', 'min' => 1, 'max' => 120],
            [['outboundPipelineId', 'proposalSentStageId', 'proposalViewedStageId'], 'filter', 'filter' => static fn($value) => $value === '' ? null : $value],
            [['outboundPipelineId', 'proposalSentStageId', 'proposalViewedStageId'], 'integer'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'duplicateMode' => 'Повторы клиентов',
            'outgoingTaskOnView' => 'Задача при просмотре',
            'outgoingLeadOnView' => 'Сделка при просмотре в рассылках',
            'protectionMonths' => 'Защита после платежа, месяцев',
            'outboundPipelineId' => 'Воронка личных предложений',
            'proposalSentStageId' => 'Этап при отправке',
            'proposalViewedStageId' => 'Этап при просмотре',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'outgoingTaskOnView' => 'Когда получатель рассылки открыл Презентацию или КП, ответственному ставится задача «Позвонить». Умолчание новой рассылки — в рассылке его можно изменить.',
            'outboundPipelineId' => 'Воронка, в которой ведутся продажи по найденным компаниям. Отправка и просмотр Презентации или КП двигают этапы только у сделок этой воронки.',
            'proposalSentStageId' => 'Куда переходит сделка, когда из неё отправили Презентацию или КП. Сделка, которая уже дальше, остаётся на месте.',
            'proposalViewedStageId' => 'Куда переходит сделка, когда клиент впервые открыл Презентацию или КП.',
            'outgoingLeadOnView' => 'Просмотр Презентации или КП из рассылки сразу создаёт сделку (или дополняет открытую). Обычно сделку создаёт ответ или заявка клиента, а просмотр только делает его «тёплым».',
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
            // «Исходящие продажи» (П2д): the mailing defaults here; the pipeline and the stage of personal offers join this set.
            'outgoing' => [
                'class' => FieldSet::class,
                'name' => 'Исходящие продажи',
                'fields' => [
                    'outgoingTaskOnView' => ['class' => \skeeks\yii2\form\fields\BoolField::class, 'allowNull' => false],
                    'outgoingLeadOnView' => ['class' => \skeeks\yii2\form\fields\BoolField::class, 'allowNull' => false],
                    'outboundPipelineId' => [
                        'class' => SelectField::class,
                        'items' => self::pipelineItems(),
                        'nullLabel' => 'Воронка «Исходящие продажи»',
                    ],
                    'proposalSentStageId' => [
                        'class' => SelectField::class,
                        'items' => self::stageItems(),
                        'nullLabel' => 'Этап «'.OutboundSales::STAGE_SENT.'»',
                    ],
                    'proposalViewedStageId' => [
                        'class' => SelectField::class,
                        'items' => self::stageItems(),
                        'nullLabel' => 'Этап «'.OutboundSales::STAGE_VIEWED.'»',
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

    /** Pipelines of the site, id => name. */
    private static function pipelineItems(): array
    {
        return CmsLead::pipelineReady() ? ArrayHelper::map(CmsLeadPipeline::forSite(), 'id', 'name') : [];
    }

    /** Stages of every pipeline of the site, «Воронка: этап». */
    private static function stageItems(): array
    {
        $items = [];
        foreach (CmsLead::pipelineReady() ? CmsLeadPipeline::forSite() : [] as $pipeline) {
            foreach (CmsLeadStage::forPipeline($pipeline) as $stage) {
                $items[(int)$stage->id] = $pipeline->name.': '.$stage->name;
            }
        }

        return $items;
    }
}
