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
 * «Входящие заявки» (п. 13б, InboundQueue): who sorts «Неразобранное» —
 * employees and departments — all of them or by turns, and whether a request
 * of a found client goes straight to its main responsible.
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
     * Who gets new companies of the flow, who gets its deals (П2д, «Исходящие
     * продажи»). OWNER_NONE only in old saved settings (п. 13а: no pool), read
     * as OWNER_SENDER.
     */
    public const OWNER_NONE = 'none';
    public const OWNER_SENDER = 'sender';
    public const OWNER_WORKER = 'worker';
    public const LEAD_TO_UNSORTED = 'unsorted';

    /**
     * «Исходящие продажи» (П2д): «my defaults» of the flow — a bot through
     * MCP/API or a mailing campaign. A campaign copies them when created and
     * may change them; a bot without a campaign works by them. The flow:
     * the responsible of NEW companies (the sender or an employee; a CRM
     * client always has a main responsible, п. 13а); what makes a deal besides a reply (a view of the
     * presentation or offer, a visit by a link); who gets the deal
     * («Неразобранное», the sender, an employee); the call task on a view
     * when a view makes no deal. The pipeline is $outboundPipelineId, its
     * stages «Смотрел» and «Ответил» take the events (OutboundSales).
     */
    public $outgoingNewCardOwner = self::OWNER_SENDER;
    /** @var int|null the employee of OWNER_WORKER */
    public $outgoingNewCardWorkerId;
    public $outgoingLeadOnView = 1;
    public $outgoingLeadOnVisit = 0;
    public $outgoingLeadTo = self::OWNER_SENDER;
    /** @var int|null the employee of OWNER_WORKER */
    public $outgoingLeadWorkerId;
    public $outgoingTaskOnView = 1;

    /** @var int[] «Входящие заявки»: employees who sort «Неразобранное» (InboundQueue) */
    public $inboundWorkerIds = [];
    /** @var int[] «Входящие заявки»: departments whose employees sort it */
    public $inboundDepartmentIds = [];
    /** @var string InboundQueue::MODE_ALL | MODE_ROUND */
    public $inboundMode = InboundQueue::MODE_ALL;
    /** @var int a request of a found client goes straight to its main responsible */
    public $inboundToMain = 0;

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
            [['outgoingTaskOnView', 'outgoingLeadOnView', 'outgoingLeadOnVisit'], 'boolean'],
            [['outgoingNewCardOwner'], 'in', 'range' => array_keys(self::newCardOwners())],
            [['outgoingLeadTo'], 'in', 'range' => array_keys(self::leadReceivers())],
            [['outgoingNewCardWorkerId', 'outgoingLeadWorkerId'], 'filter', 'filter' => static fn($value) => $value === '' ? null : $value],
            [['outgoingNewCardWorkerId', 'outgoingLeadWorkerId'], 'integer'],
            [['outgoingNewCardWorkerId'], 'required', 'when' => static fn(self $m) => $m->outgoingNewCardOwner === self::OWNER_WORKER,
                'whenClient' => "function () { return $('#crmsettings-outgoingnewcardowner').val() === '".self::OWNER_WORKER."'; }"],
            [['outgoingLeadWorkerId'], 'required', 'when' => static fn(self $m) => $m->outgoingLeadTo === self::OWNER_WORKER,
                'whenClient' => "function () { return $('#crmsettings-outgoingleadto').val() === '".self::OWNER_WORKER."'; }"],
            [['protectionMonths'], 'integer', 'min' => 1, 'max' => 120],
            [['outboundPipelineId', 'proposalSentStageId', 'proposalViewedStageId'], 'filter', 'filter' => static fn($value) => $value === '' ? null : $value],
            [['outboundPipelineId', 'proposalSentStageId', 'proposalViewedStageId'], 'integer'],
            [['inboundWorkerIds', 'inboundDepartmentIds'], 'filter', 'filter' => static fn($value) => array_values(array_filter(array_map('intval', (array)$value)))],
            [['inboundWorkerIds', 'inboundDepartmentIds'], 'each', 'rule' => ['integer']],
            [['inboundMode'], 'in', 'range' => array_keys(InboundQueue::modes())],
            [['inboundToMain'], 'boolean'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'duplicateMode' => 'Повторы клиентов',
            'outgoingNewCardOwner' => 'Ответственный за новые компании',
            'outgoingNewCardWorkerId' => 'Сотрудник для новых компаний',
            'outgoingLeadOnView' => 'Сделка при просмотре Презентации или КП',
            'outgoingLeadOnVisit' => 'Сделка при переходе по ссылке из письма',
            'outgoingLeadTo' => 'Куда сделка',
            'outgoingLeadWorkerId' => 'Сотрудник для сделок',
            'outgoingTaskOnView' => 'Задача «Позвонить» при просмотре',
            'protectionMonths' => 'Защита после платежа, месяцев',
            'outboundPipelineId' => 'Воронка личных предложений',
            'proposalSentStageId' => 'Этап при отправке',
            'proposalViewedStageId' => 'Этап при просмотре',
            'inboundWorkerIds' => 'Кто разбирает: сотрудники',
            'inboundDepartmentIds' => 'Кто разбирает: отделы',
            'inboundMode' => 'Как распределять',
            'inboundToMain' => 'Если клиент найден — сразу его основному ответственному',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'outgoingNewCardOwner' => 'Для компаний, которых ещё нет в CRM: бот или рассылка создаёт их с этим ответственным. Отправитель — тот, под чьей авторизацией работает бот. У клиента CRM всегда есть основной ответственный. Существующие компании не меняются.',
            'outgoingLeadOnView' => 'Ответ клиента создаёт сделку всегда. Отметьте, если и просмотр Презентации или КП — повод вести сделку. Открытие письма сделку не создаёт.',
            'outgoingTaskOnView' => 'Когда просмотр сделку не создаёт, ответственному ставится задача «Позвонить: смотрел Презентацию».',
            'outgoingLeadTo' => 'Отправитель — тот, от чьего имени ушло письмо. Если у компании нет основного ответственного, он им становится (как «Взять в работу»). Открытая сделка клиента дополняется, новая не создаётся.',
            'outboundPipelineId' => 'Воронка, в которой ведутся продажи по найденным компаниям. Отправка и просмотр Презентации или КП двигают этапы только у сделок этой воронки.',
            'proposalSentStageId' => 'Куда переходит сделка, когда из неё отправили Презентацию или КП. Сделка, которая уже дальше, остаётся на месте.',
            'proposalViewedStageId' => 'Куда переходит сделка, когда клиент впервые открыл Презентацию или КП.',
            'inboundWorkerIds' => 'Очередь по умолчанию: для воронок, где в настройках воронки своя очередь не задана. Новые заявки попадают в «Неразобранное» своей воронки. Их видят администраторы и эти сотрудники. Если никого не выбрать, заявку видят администраторы и сотрудники, которые ведут найденного клиента.',
            'inboundDepartmentIds' => 'Все сотрудники выбранных отделов тоже разбирают заявки.',
            'inboundMode' => '«Видят все из списка» — каждый видит все новые заявки и получает уведомление, первый берёт в работу. «По кругу» — заявка сразу назначается следующему сотруднику по очереди, разбирает её он.',
            'inboundToMain' => 'Если по телефону или e-mail заявки нашёлся клиент и у всех найденных записей один основной ответственный, сделка сразу уходит ему в работу, минуя «Неразобранное». Если ответственных несколько, заявка остаётся в «Неразобранном».',
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
            // «Входящие заявки» (п. 13б): who sorts «Неразобранное» and how (InboundQueue).
            'inbound' => [
                'class' => FieldSet::class,
                'name' => 'Входящие заявки',
                'fields' => [
                    'inboundWorkerIds' => ['class' => SelectField::class, 'items' => self::workerItems(), 'multiple' => true],
                    'inboundDepartmentIds' => ['class' => SelectField::class, 'items' => self::departmentItems(), 'multiple' => true],
                    'inboundMode' => ['class' => SelectField::class, 'items' => InboundQueue::modes(), 'allowNull' => false],
                    'inboundToMain' => ['class' => \skeeks\yii2\form\fields\BoolField::class, 'allowNull' => false],
                ],
            ],
            // «Исходящие продажи» (П2д): the mailing defaults here; the pipeline and the stage of personal offers join this set.
            'outgoing' => [
                'class' => FieldSet::class,
                'name' => 'Исходящие продажи',
                'fields' => [
                    'outgoingNewCardOwner' => ['class' => SelectField::class, 'items' => self::newCardOwners(), 'allowNull' => false],
                    'outgoingNewCardWorkerId' => ['class' => SelectField::class, 'items' => self::workerItems(), 'allowNull' => true],
                    'outgoingLeadOnView' => ['class' => \skeeks\yii2\form\fields\BoolField::class, 'allowNull' => false],
                    'outgoingLeadOnVisit' => ['class' => \skeeks\yii2\form\fields\BoolField::class, 'allowNull' => false],
                    'outgoingTaskOnView' => ['class' => \skeeks\yii2\form\fields\BoolField::class, 'allowNull' => false],
                    'outgoingLeadTo' => ['class' => SelectField::class, 'items' => self::leadReceivers(), 'allowNull' => false],
                    'outgoingLeadWorkerId' => ['class' => SelectField::class, 'items' => self::workerItems(), 'allowNull' => true],
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

    public static function newCardOwners(): array
    {
        return [self::OWNER_SENDER => 'Отправитель', self::OWNER_WORKER => 'Сотрудник'];
    }

    public static function leadReceivers(): array
    {
        return [self::LEAD_TO_UNSORTED => '«Неразобранное»', self::OWNER_SENDER => 'Отправитель', self::OWNER_WORKER => 'Сотрудник'];
    }

    /** Active employees, id => name. */
    public static function workerItems(): array
    {
        $result = [];
        foreach (\skeeks\cms\models\CmsUser::find()->isWorker()->andWhere([\skeeks\cms\models\CmsUser::tableName().'.is_active' => 1])->all() as $worker) {
            $result[(int)$worker->id] = (string)$worker->shortDisplayName;
        }
        asort($result);

        return $result;
    }

    /** Departments, id => name. */
    public static function departmentItems(): array
    {
        return ArrayHelper::map(\skeeks\cms\models\CmsDepartment::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name');
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
