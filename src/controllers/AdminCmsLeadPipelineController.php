<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\admin\CmsLeadStatusWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * «Воронки и этапы» (П2б): sales pipelines of deals, each with its own stages
 * (the tab «Этапы» is the stage section filtered by the pipeline).
 */
class AdminCmsLeadPipelineController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Воронки и этапы';
        $this->modelShowAttribute = 'name';
        $this->modelClassName = CmsLeadPipeline::class;
        $this->modelDefaultAction = 'stages';
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;
        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'emptyState' => [
                    'title' => 'Воронок пока нет',
                    'description' => 'Сейчас все сделки идут по одному списку этапов. Добавьте воронку, если продажи разных видов идут по разным шагам: например «Входящие» и «Исходящие продажи». Текущие этапы и сделки окажутся в основной воронке.',
                    'icon' => 'fas fa-filter',
                    'action' => ['backendAction' => 'create', 'label' => 'Добавить воронку'],
                ],
                'filters' => false,
                'grid' => [
                    'on init' => static function (Event $event) {
                        $query = $event->sender->dataProvider->query;
                        $query->cmsSite();
                        $query->addSelect([
                            CmsLeadPipeline::tableName().'.*',
                            'countOpen' => CmsLead::find()
                                ->select([new Expression('count(1)')])
                                ->andWhere(['cms_lead_pipeline_id' => new Expression(CmsLeadPipeline::tableName().'.id')])
                                ->andWhere(['status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]]),
                        ]);
                    },
                    'defaultOrder' => ['sort' => SORT_ASC],
                    'visibleColumns' => ['actions', 'name', 'stages', 'success_rule', 'countOpen', 'sort'],
                    'columns' => [
                        'name' => [
                            'class' => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-lead-pipeline',
                            'action' => 'stages',
                            'attribute' => 'name',
                            'content' => static fn(CmsLeadPipeline $model) => Html::encode($model->name)
                                .($model->is_default ? ' '.Html::tag('span', 'основная', ['class' => 'sx-status sx-status--accent', 'title' => 'Сюда попадают новые обращения']) : ''),
                        ],
                        'stages' => [
                            'label' => 'Этапы',
                            'format' => 'raw',
                            'value' => static function (CmsLeadPipeline $model) {
                                $chips = [Html::tag('span', 'Неразобранное', ['class' => 'sx-status'])];
                                foreach ($model->stages as $stage) {
                                    $chips[] = CmsLeadStatusWidget::renderStage($stage, false);
                                }
                                $chips[] = Html::tag('span', 'Успешно', ['class' => 'sx-status sx-status--success']);
                                $chips[] = Html::tag('span', 'Отказ', ['class' => 'sx-status sx-status--danger']);

                                return Html::tag('div', implode(' → ', $chips), ['class' => 'sx-collection-cell sx-collection-cell--inline', 'style' => 'flex-wrap: wrap']);
                            },
                        ],
                        'success_rule' => [
                            'value' => static fn(CmsLeadPipeline $model) => $model->successRuleName,
                        ],
                        'countOpen' => [
                            'label' => 'Открытых сделок',
                            'format' => 'raw',
                            'value' => static fn(CmsLeadPipeline $model) => Html::a(
                                Html::tag('strong', \Yii::$app->formatter->asInteger((int)$model->raw_row['countOpen'])),
                                ['/cms/admin-cms-lead/kanban', 'pipeline' => $model->id],
                                ['data-pjax' => '0', 'title' => 'Открыть канбан воронки']
                            ),
                        ],
                    ],
                ],
            ],
            'stages' => [
                'class' => BackendGridModelRelatedAction::class,
                'name' => 'Этапы',
                'icon' => 'fas fa-columns',
                'priority' => 5,
                'controllerRoute' => '/cms/admin-cms-lead-stage',
                'relation' => ['cms_lead_pipeline_id' => 'id'],
                'on gridInit' => function ($e) {
                    $e->sender->relatedIndexAction->backendShowings = false;
                },
            ],
            'create' => ['fields' => [$this, 'updateFields']],
            'update' => ['fields' => [$this, 'updateFields']],
        ]);
    }

    public function updateFields($action)
    {
        $hints = '';
        foreach (CmsLeadPipeline::successRuleHints() as $rule => $hint) {
            $hints .= Html::tag('div', Html::tag('strong', Html::encode(CmsLeadPipeline::successRules()[$rule])).' — '.Html::encode($hint));
        }

        return [
            'name',
            'success_rule' => [
                'class' => SelectField::class,
                'items' => CmsLeadPipeline::successRules(),
                'allowNull' => false,
                'hint' => $hints.'Автоматический критерий только предлагает закрыть сделку, закрывает её менеджер.',
            ],
            'is_default' => [
                'class' => BoolField::class,
                'allowNull' => false,
            ],
            'sort' => ['class' => NumberField::class],
            'code',
        ] + (CmsLeadPipeline::inboundReady() ? [
            // The queue of «Неразобранное» of the pipeline (п. 13б, crm\InboundQueue); empty — the CRM settings.
            'inboundSourceTypes' => ['class' => SelectField::class, 'items' => \skeeks\cms\models\CmsLead::sources(), 'multiple' => true],
            'inboundWorkerIds' => ['class' => SelectField::class, 'items' => \skeeks\cms\crm\CrmSettings::workerItems(), 'multiple' => true],
            'inboundDepartmentIds' => ['class' => SelectField::class, 'items' => \skeeks\cms\crm\CrmSettings::departmentItems(), 'multiple' => true],
            'inbound_mode' => ['class' => SelectField::class, 'items' => \skeeks\cms\crm\InboundQueue::modes(), 'nullLabel' => 'Как в настройках CRM «Входящие заявки»'],
        ] : []);
    }
}
