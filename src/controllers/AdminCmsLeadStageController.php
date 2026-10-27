<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\admin\CmsLeadStatusWidget;
use skeeks\cms\widgets\ColorInput;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Site stages inside the lead status "in work" (columns of the lead kanban).
 */
class AdminCmsLeadStageController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Этапы сделок';
        $this->modelShowAttribute = 'name';
        $this->modelClassName = CmsLeadStage::class;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;
        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'emptyState' => [
                    'title' => 'Этапов пока нет',
                    'description' => 'Этапы делят статус «В работе» на шаги, например «Пытаемся связаться», «КП отправлено», «Думает». Они становятся колонками канбана сделок.',
                    'icon' => 'fas fa-columns',
                    'action' => ['backendAction' => 'create', 'label' => 'Добавить этап'],
                ],
                'filters' => false,
                'grid' => [
                    'on init' => static function (Event $event) {
                        $query = $event->sender->dataProvider->query;
                        $query->cmsSite();
                        $query->addSelect([
                            CmsLeadStage::tableName().'.*',
                            'countLeads' => CmsLead::find()
                                ->select([new Expression('count(1)')])
                                ->andWhere(['cms_lead_stage_id' => new Expression(CmsLeadStage::tableName().'.id')])
                                ->andWhere(['status' => CmsLead::STATUS_IN_WORK]),
                        ]);
                    },
                    'defaultOrder' => ['sort' => SORT_ASC],
                    'visibleColumns' => ['checkbox', 'actions', 'name', 'sort', 'countLeads'],
                    'columns' => [
                        'name' => [
                            'class' => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-lead-stage',
                            'attribute' => 'name',
                            'content' => static fn(CmsLeadStage $model) => CmsLeadStatusWidget::renderStage($model),
                        ],
                        'sort' => [
                            'value' => static fn(CmsLeadStage $model) => \Yii::$app->formatter->asInteger($model->sort),
                        ],
                        'countLeads' => [
                            'label' => 'Сделок в работе',
                            'format' => 'raw',
                            'value' => static fn(CmsLeadStage $model) => Html::tag('span',
                                Html::tag('strong', \Yii::$app->formatter->asInteger((int)$model->raw_row['countLeads'])),
                                ['class' => 'sx-collection-cell--metric']
                            ),
                        ],
                    ],
                ],
            ],
            'create' => ['fields' => [$this, 'updateFields']],
            'update' => ['fields' => [$this, 'updateFields']],
        ]);
    }

    public function updateFields($action)
    {
        return [
            'name',
            'sort' => ['class' => NumberField::class],
            'color' => [
                'class' => WidgetField::class,
                'widgetClass' => ColorInput::class,
                'widgetConfig' => [
                    'saveValueAs' => ColorInput::SAVE_VALUE_TO_HEX_STRING,
                    'options' => ['placeholder' => 'Без цвета'],
                    'pluginOptions' => [
                        'preferredFormat' => 'hex',
                        'allowEmpty' => true,
                        'showInput' => true,
                    ],
                ],
            ],
        ];
    }
}
