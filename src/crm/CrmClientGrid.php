<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\db\BaseActiveRecord;
use yii\helpers\Html;

/**
 * The CRM part of the company and client lists (owner's decision 29.10.2026):
 * one column «Ответственный и сотрудники», the filters by the responsible,
 * the employee, the protection and the creation date, and the bulk actions of
 * the access — the same for `admin-cms-company` and `admin-user`. A controller
 * merges them into its index action; the visibility stays
 * CrmAccess::applyClientVisibility().
 */
class CrmClientGrid
{
    /** The names of the filter fields (filterFields()). */
    public static function filterNames(): array
    {
        return ['crm_responsible', 'crm_protection', 'crm_main_user', 'crm_employee', 'crm_created'];
    }

    /**
     * The filter fields of a client list.
     *
     * @param string $recordCode CmsCrmAccessRequest::RECORD_COMPANY|RECORD_CLIENT
     * @param string $table the table of the listed records, e.g. "cms_company"
     */
    public static function filterFields(string $recordCode, string $table): array
    {
        $idColumn = $table.'.id';
        $workers = static fn($word = '') => $word ? CmsUser::find()->isWorker()->search($word) : CmsUser::find()->isWorker();

        return [
            'crm_responsible' => [
                'class' => SelectField::class,
                'label' => 'Ответственные',
                'items' => array_intersect_key(CrmAccess::responsibleFilterItems(), array_flip(['my_main', 'no_main', 'no_responsible'])),
                'on apply' => function (QueryFiltersEvent $e) use ($recordCode, $idColumn) {
                    CrmAccess::instance()->applyResponsibleFilter($e->dataProvider->query, $recordCode, (string)$e->field->value,
                        $idColumn, (int)\Yii::$app->user->id);
                },
            ],
            'crm_protection' => [
                'class' => SelectField::class,
                'label' => 'Защита',
                'items' => CrmProtection::ready() ? CrmProtection::states() : [],
                'on apply' => function (QueryFiltersEvent $e) use ($table) {
                    if ($e->field->value && CrmProtection::ready()) {
                        $e->dataProvider->query->andWhere([$table.'.crm_protection' => (string)$e->field->value]);
                    }
                },
            ],
            'crm_main_user' => [
                'class' => WidgetField::class,
                'label' => 'Основной ответственный',
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => ['modelClass' => CmsUser::class, 'searchQuery' => $workers],
                'on apply' => function (QueryFiltersEvent $e) use ($recordCode, $idColumn) {
                    CrmAccess::instance()->applyEmployeeFilter($e->dataProvider->query, $recordCode, (int)$e->field->value, true, $idColumn);
                },
            ],
            'crm_employee' => [
                'class' => WidgetField::class,
                'label' => 'Сотрудник работает',
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => ['modelClass' => CmsUser::class, 'searchQuery' => $workers],
                'on apply' => function (QueryFiltersEvent $e) use ($recordCode, $idColumn) {
                    CrmAccess::instance()->applyEmployeeFilter($e->dataProvider->query, $recordCode, (int)$e->field->value, false, $idColumn);
                },
            ],
            'crm_created' => [
                'class' => WidgetField::class,
                'label' => 'Дата создания',
                'widgetClass' => DaterangeInputWidget::class,
                'widgetConfig' => ['options' => ['placeholder' => 'Диапазон дат']],
                'on apply' => function (QueryFiltersEvent $e) use ($table) {
                    if ($e->field->value && ($range = DaterangeInputWidget::parseRange($e->field->value))) {
                        $e->dataProvider->query->andWhere(['between', $table.'.created_at', (int)$range[0], (int)$range[1]]);
                    }
                },
            ],
        ];
    }

    /** The column «Ответственный и сотрудники»: the main responsible first and marked, then the others with their levels. */
    public static function managersColumn(string $recordCode): array
    {
        return [
            'format' => 'raw',
            'label' => 'Ответственный и сотрудники',
            'value' => static fn(BaseActiveRecord $record) => self::managersCell($recordCode, $record),
        ];
    }

    public static function managersCell(string $recordCode, BaseActiveRecord $record): string
    {
        if ($record->hasAttribute('is_worker') && $record->is_worker) {
            return '';
        }
        [$class, $recordColumn, $userColumn] = CrmAccess::linkTable($recordCode);
        $links = $class::find()->andWhere([$recordColumn => (int)$record->id])->all();
        if (!$links) {
            return '';
        }
        $users = CmsUser::find()->andWhere(['id' => array_map(static fn($link) => (int)$link->{$userColumn}, $links)])->indexBy('id')->all();
        $levels = CrmAccess::levels();
        $mainReady = CrmAccess::mainReady();
        $rows = [];
        foreach ($links as $link) {
            $user = $users[(int)$link->{$userColumn}] ?? null;
            if (!$user) {
                continue;
            }
            $isMain = $mainReady && $link->is_main;
            $level = $link->hasAttribute('access_level') ? (string)$link->access_level : CrmAccess::LEVEL_FULL;
            $rows[] = [
                'sort' => [$isMain ? 1 : 0, CrmAccess::levelRank($level)],
                'html' => Html::tag('div',
                    CmsWorkerViewWidget::widget(['user' => $user, 'isSmall' => true]).
                    Html::tag('span', $isMain ? 'Ответственный' : Html::encode($levels[$level] ?? $level), [
                        'class' => 'sx-status'.($isMain ? ' sx-status--success' : ''),
                    ]),
                    ['class' => 'sx-crm-managers__item']),
            ];
        }
        usort($rows, static fn($a, $b) => $b['sort'] <=> $a['sort']);
        \skeeks\cms\assets\admin\CrmShieldAsset::register(\Yii::$app->view);

        return Html::tag('div', implode('', array_column($rows, 'html')), ['class' => 'sx-crm-managers']);
    }

    /** The bulk actions of the access over the marked rows or «для всех» (CrmBulkAccessAction). */
    public static function bulkActions(string $recordCode): array
    {
        $base = ['class' => CrmBulkAccessAction::class, 'recordCode' => $recordCode];

        return [
            'crm-bulk-main' => $base + ['operation' => CrmBulkAccessForm::MAIN,
                    'name' => 'Сменить ответственного', 'icon' => 'fas fa-user-check', 'priority' => 310, 'successMessage' => 'Основной ответственный назначен.'],
            'crm-bulk-grant' => $base + ['operation' => CrmBulkAccessForm::GRANT,
                    'name' => 'Добавить сотрудника', 'icon' => 'fas fa-user-plus', 'priority' => 320, 'successMessage' => 'Доступ выдан.'],
            'crm-bulk-protection' => $base + ['operation' => CrmBulkAccessForm::PROTECTION,
                    'name' => 'Сменить защиту', 'icon' => 'fas fa-shield-alt', 'priority' => 330, 'successMessage' => 'Защита изменена.'],
        ];
    }

    /** The noun of the list in a count: «компаний», «клиентов». */
    public static function countNoun(string $recordCode): string
    {
        return $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? 'компаний' : 'клиентов';
    }
}
