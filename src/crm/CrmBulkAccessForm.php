<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Model;
use yii\helpers\Html;

/**
 * Bulk actions over the company list (owner's decision 29.10.2026): «Назначить
 * основного ответственного», «Добавить сотрудника с уровнем», «Сменить защиту»
 * — for the marked rows or «для всех» of the filter. Who manages the access
 * (CrmAccess::canManageAccess()) per company; every change is written to the
 * journal (CrmAccessLog). CrmBulkAccessAction shows the count before applying.
 */
class CrmBulkAccessForm extends Model
{
    public const MAIN = 'main';
    public const GRANT = 'grant';
    public const PROTECTION = 'protection';

    /** @var string main|grant|protection — set by the action */
    public $operation = self::MAIN;

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
        $text = [
            self::MAIN => 'Выбранный сотрудник станет основным ответственным. Прежний основной останется с уровнем «Свои сделки и данные компании».',
            self::GRANT => 'Сотрудник получит выбранный уровень доступа. У кого он уже есть — уровень изменится; основного ответственного это не касается.',
            self::PROTECTION => '«Авто» — защита по платежам и услугам; «Всегда по согласованию» — работа с клиентом только по согласованию с основным ответственным.',
        ][$operation];
        $fields = [
            'count' => ['class' => HtmlBlock::class, 'content' => Html::tag('p', Html::tag('strong', 'Будет изменено компаний: '.$count).'. '.$text, ['class' => 'form-group'])],
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

    /** Applies the operation to one company; true — done or nothing to change. */
    public function apply(CmsCompany $company): bool
    {
        $access = CrmAccess::instance();
        $actorId = (int)\Yii::$app->user->id;
        $id = (int)$company->id;
        switch ($this->operation) {
            case self::MAIN:
                $userId = (int)$this->user_id;
                $before = $access->mainResponsibleId('company', $id);
                if ($before !== $userId) {
                    $access->setMain('company', $id, $userId, CrmAccess::LEVEL_EDIT);
                    CrmAccessLog::write($before ? CrmAccessLog::TRANSFER : CrmAccessLog::MAIN_AUTO, 'company', $id, [
                        'actor_id' => $actorId, 'user_id' => $userId, 'level_to' => CrmAccess::LEVEL_FULL, 'comment' => 'массовое действие в списке компаний',
                    ]);
                }
                break;
            case self::GRANT:
                $userId = (int)$this->user_id;
                if ($access->mainResponsibleId('company', $id) === $userId) {
                    break;
                }
                $before = $access->linkLevel($userId, 'company', $id);
                if ($before === null) {
                    $access->grantLink('company', $id, $userId, (string)$this->level);
                    CrmAccessLog::write(CrmAccessLog::GRANT, 'company', $id, ['actor_id' => $actorId, 'user_id' => $userId, 'level_to' => (string)$this->level,
                        'comment' => 'массовое действие в списке компаний']);
                } elseif ($before !== (string)$this->level) {
                    $access->setLevel('company', $id, $userId, (string)$this->level);
                    CrmAccessLog::write(CrmAccessLog::LEVEL, 'company', $id, ['actor_id' => $actorId, 'user_id' => $userId, 'level_from' => $before,
                        'level_to' => (string)$this->level, 'comment' => 'массовое действие в списке компаний']);
                }
                break;
            case self::PROTECTION:
                $before = CrmProtection::state('company', $id);
                $access->setApprovalRequired('company', $id, $this->protection === 'always');
                $after = CrmProtection::state('company', $id);
                if ($before !== $after) {
                    CrmAccessLog::write(CrmAccessLog::PROTECTION, 'company', $id, ['actor_id' => $actorId,
                        'comment' => CrmProtection::states()[$before].' → '.CrmProtection::states()[$after].', массовое действие']);
                }
                break;
        }

        return true;
    }
}
