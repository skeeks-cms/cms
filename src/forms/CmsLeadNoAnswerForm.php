<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\forms;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadRejectReason;
use yii\base\Model;

/**
 * A failed call to a lead: what happens next.
 *
 * Either the next call is planned (the manager's nearest open task on the lead
 * is moved, otherwise a new task is created) or the lead is closed as
 * unreachable. The writes are performed by CmsLeadNoAnswerService.
 */
class CmsLeadNoAnswerForm extends Model
{
    public const NEXT_IN_2_HOURS = 'in_2_hours';
    public const NEXT_TOMORROW = 'tomorrow';
    public const NEXT_IN_2_DAYS = 'in_2_days';
    public const NEXT_CUSTOM = 'custom';
    public const NEXT_REJECT = 'reject';

    /** Morning hour for "tomorrow" and "in 2 days" in the site time zone. */
    public const MORNING_HOUR = 10;

    /** @var CmsLead */
    public $lead;

    public $next = self::NEXT_TOMORROW;
    /** @var int|string|null Unix time from the date control. */
    public $callback_at;
    public $comment;
    /** @var int|string|null Reason from the site list when the lead is closed. */
    public $reject_reason_id;

    public function init()
    {
        parent::init();
        if ($this->reject_reason_id === null && $this->lead instanceof CmsLead) {
            $this->reject_reason_id = $this->noAnswerReasonId();
        }
    }

    /** The site reason marked for this form, if any. */
    private function noAnswerReasonId(): ?int
    {
        try {
            $id = CmsLeadRejectReason::find()->cmsSite((int)$this->lead->cms_site_id)
                ->andWhere(['is_no_answer' => 1])->select(['id'])->scalar();
        } catch (\yii\db\Exception $e) {
            return null;
        }

        return $id ? (int)$id : null;
    }

    public static function nextOptions(): array
    {
        return [
            self::NEXT_IN_2_HOURS => 'Перезвонить через 2 часа',
            self::NEXT_TOMORROW => 'Перезвонить завтра в '.self::MORNING_HOUR.':00',
            self::NEXT_IN_2_DAYS => 'Перезвонить через 2 дня в '.self::MORNING_HOUR.':00',
            self::NEXT_CUSTOM => 'Перезвонить в другое время',
            self::NEXT_REJECT => 'Закрыть сделку: не удалось дозвониться',
        ];
    }

    public function rules()
    {
        $isCustomClient = 'function () { return $("#'.\yii\helpers\Html::getInputId($this, 'next').'").val() === "'.self::NEXT_CUSTOM.'"; }';

        return [
            [['callback_at', 'comment'], 'filter', 'filter' => static function ($value) {
                return is_string($value) && trim($value) === '' ? null : $value;
            }],
            [['next'], 'required'],
            [['next'], 'in', 'range' => array_keys(self::nextOptions())],
            [['next'], 'validateLead'],
            [['callback_at'], 'required', 'when' => fn() => $this->next === self::NEXT_CUSTOM,
                'whenClient' => $isCustomClient, 'message' => 'Укажите, когда перезвонить.'],
            [['callback_at'], 'integer'],
            [['callback_at'], 'compare', 'compareValue' => time() - 300, 'operator' => '>=', 'type' => 'number',
                'when' => fn() => $this->next === self::NEXT_CUSTOM, 'whenClient' => $isCustomClient,
                'message' => 'Время звонка уже прошло.'],
            [['comment'], 'string', 'max' => 5000],
            [['reject_reason_id'], 'filter', 'filter' => static function ($value) {
                return $value === '' ? null : $value;
            }],
            [['reject_reason_id'], 'integer'],
            [['reject_reason_id'], 'required', 'message' => 'Выберите причину отказа.',
                'when' => fn() => $this->next === self::NEXT_REJECT && $this->lead instanceof CmsLead
                    && CmsLeadRejectReason::siteHasReasons($this->lead->cms_site_id),
                'whenClient' => 'function () { return $("#'.\yii\helpers\Html::getInputId($this, 'next').'").val() === "'.self::NEXT_REJECT.'"; }'],
            [['reject_reason_id'], 'validateReason'],
        ];
    }

    public function validateReason($attribute)
    {
        if ($this->next !== self::NEXT_REJECT || !$this->reject_reason_id || !$this->lead instanceof CmsLead) {
            return;
        }
        if (!CmsLeadRejectReason::find()->cmsSite((int)$this->lead->cms_site_id)->andWhere(['id' => (int)$this->reject_reason_id])->exists()) {
            $this->addError($attribute, 'Причина не найдена на этом сайте.');
        }
    }

    public function attributeLabels()
    {
        return [
            'next' => 'Что дальше',
            'callback_at' => 'Когда перезвонить',
            'comment' => 'Комментарий',
            'reject_reason_id' => 'Причина отказа',
        ];
    }

    public function validateLead($attribute)
    {
        if (!$this->lead instanceof CmsLead || $this->lead->status !== CmsLead::STATUS_IN_WORK) {
            $this->addError($attribute, 'Отметить звонок можно только у сделки в работе.');
            return;
        }
        if (!$this->lead->canBeWorkedBy((int)\Yii::$app->user->id)) {
            $this->addError($attribute, 'Сделка закреплена за другим менеджером.');
        }
    }

    public function getIsReject(): bool
    {
        return $this->next === self::NEXT_REJECT;
    }

    /** The planned call time, or null when the lead is being closed. */
    public function getCallbackTime(): ?int
    {
        $timeZone = new \DateTimeZone(\Yii::$app->formatter->timeZone ?: \Yii::$app->timeZone);
        $now = new \DateTimeImmutable('now', $timeZone);

        switch ($this->next) {
            case self::NEXT_IN_2_HOURS:
                return $now->modify('+2 hours')->getTimestamp();
            case self::NEXT_TOMORROW:
                return $now->modify('+1 day')->setTime(self::MORNING_HOUR, 0)->getTimestamp();
            case self::NEXT_IN_2_DAYS:
                return $now->modify('+2 days')->setTime(self::MORNING_HOUR, 0)->getTimestamp();
            case self::NEXT_CUSTOM:
                return (int)$this->callback_at;
        }

        return null;
    }
}
