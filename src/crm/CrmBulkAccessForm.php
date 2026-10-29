<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Model;
use yii\helpers\Html;

/**
 * Bulk actions over the company and client lists (owner's decision 29.10.2026):
 * «Сменить ответственного», «Добавить сотрудника», «Сменить защиту» — for the
 * marked rows or «для всех» of the filter. Who manages the access
 * (CrmAccess::canManageAccess()) per record; every change is written to the
 * journal (CrmAccessLog). CrmBulkAccessAction shows the count before applying.
 */
class CrmBulkAccessForm extends Model
{
    public const MAIN = 'main';
    public const GRANT = 'grant';
    public const PROTECTION = 'protection';

    /** @var string main|grant|protection — set by the action */
    public $operation = self::MAIN;

    /** @var string CmsCrmAccessRequest::RECORD_COMPANY|RECORD_CLIENT — set by the action */
    public $recordCode = CmsCrmAccessRequest::RECORD_COMPANY;

    public $user_id;
    public $level = CrmAccess::LEVEL_DEAL;
    public $protection = 'auto';

    public function rules()
    {
        return [
            [['user_id'], 'required', 'when' => fn() => $this->operation !== self::PROTECTION],
            [['user_id'], 'integer'],
            [['user_id'], 'exist', 'targetClass' => CmsUser::class, 'targetAttribute' => 'id', 'filter' => ['is_worker' => 1, 'is_active' => 1],
                'when' => fn() => $this->operation !== self::PROTECTION],
            [['level'], 'in', 'range' => array_keys(CrmAccess::levels())],
            [['protection'], 'in', 'range' => ['auto', 'always']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'user_id' => $this->operation === self::MAIN ? 'Основной ответственный' : 'Сотрудник',
            'level' => 'Уровень доступа',
            'protection' => 'Защита',
        ];
    }

    public function formName()
    {
        return 'CrmBulkAccessForm';
    }

    /** The fields of the window: the count, then what this operation asks. */
    public static function formFields($action = null): array
    {
        $count = $action instanceof CrmBulkAccessAction ? $action->selectedCount() : 0;
        $operation = $action instanceof CrmBulkAccessAction ? $action->operation : self::MAIN;
        $recordCode = $action instanceof CrmBulkAccessAction ? $action->recordCode : CmsCrmAccessRequest::RECORD_COMPANY;
        $text = [
            self::MAIN => 'Выбранный сотрудник станет основным ответственным. Прежний основной останется с уровнем «Свои сделки и данные компании».',
            self::GRANT => 'Сотрудник получит выбранный уровень доступа. У кого он уже есть — уровень изменится; основного ответственного это не касается.',
            self::PROTECTION => '«Авто» — защита по платежам и услугам; «Всегда по согласованию» — работа с клиентом только по согласованию с основным ответственным.',
        ][$operation];
        $fields = [
            'count' => ['class' => HtmlBlock::class, 'content' => Html::tag('p', Html::tag('strong', 'Будет изменено '.CrmClientGrid::countNoun($recordCode).': '.$count).'. '.$text, ['class' => 'form-group'])],
        ];
        if ($operation !== self::PROTECTION) {
            $fields['user_id'] = [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass' => CmsUser::class,
                    'modelShowAttribute' => 'shortDisplayName',
                    'searchQuery' => static function ($word = '') {
                        $query = CmsUser::find()->isWorker()->cmsSite()->andWhere([CmsUser::tableName().'.is_active' => 1]);

                        return $word ? $query->search($word) : $query;
                    },
                ],
            ];
        }
        if ($operation === self::GRANT) {
            $fields['level'] = ['class' => SelectField::class, 'items' => CrmAccess::levels(), 'allowNull' => false];
        }
        if ($operation === self::PROTECTION) {
            $fields['protection'] = ['class' => SelectField::class, 'items' => ['auto' => 'Авто: по платежам и услугам', 'always' => 'Всегда по согласованию'], 'allowNull' => false];
        }

        return $fields;
    }

    /** Applies the operation to one company or client; true — done or nothing to change. */
    public function apply(\yii\db\BaseActiveRecord $record): bool
    {
        $access = CrmAccess::instance();
        $actorId = (int)\Yii::$app->user->id;
        $id = (int)$record->id;
        $code = (string)$this->recordCode;
        $comment = 'массовое действие в списке '.($code === CmsCrmAccessRequest::RECORD_COMPANY ? 'компаний' : 'клиентов');
        switch ($this->operation) {
            case self::MAIN:
                $userId = (int)$this->user_id;
                $before = $access->mainResponsibleId($code, $id);
                if ($before !== $userId) {
                    $access->setMain($code, $id, $userId, CrmAccess::LEVEL_EDIT);
                    CrmAccessLog::write($before ? CrmAccessLog::TRANSFER : CrmAccessLog::MAIN_AUTO, $code, $id, [
                        'actor_id' => $actorId, 'user_id' => $userId, 'level_to' => CrmAccess::LEVEL_FULL, 'comment' => $comment,
                    ]);
                }
                break;
            case self::GRANT:
                $userId = (int)$this->user_id;
                if ($access->mainResponsibleId($code, $id) === $userId) {
                    break;
                }
                $before = $access->linkLevel($userId, $code, $id);
                if ($before === null) {
                    $access->grantLink($code, $id, $userId, (string)$this->level);
                    CrmAccessLog::write(CrmAccessLog::GRANT, $code, $id, ['actor_id' => $actorId, 'user_id' => $userId, 'level_to' => (string)$this->level, 'comment' => $comment]);
                } elseif ($before !== (string)$this->level) {
                    $access->setLevel($code, $id, $userId, (string)$this->level);
                    CrmAccessLog::write(CrmAccessLog::LEVEL, $code, $id, ['actor_id' => $actorId, 'user_id' => $userId, 'level_from' => $before,
                        'level_to' => (string)$this->level, 'comment' => $comment]);
                }
                break;
            case self::PROTECTION:
                $before = CrmProtection::state($code, $id);
                $access->setApprovalRequired($code, $id, $this->protection === 'always');
                $after = CrmProtection::state($code, $id);
                if ($before !== $after) {
                    CrmAccessLog::write(CrmAccessLog::PROTECTION, $code, $id, ['actor_id' => $actorId,
                        'comment' => CrmProtection::states()[$before].' → '.CrmProtection::states()[$after].', '.$comment]);
                }
                break;
        }

        return true;
    }
}
