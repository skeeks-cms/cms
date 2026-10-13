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
use skeeks\cms\models\CmsLeadRejectReason;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\NumberField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Site list of lead reject reasons: the basis for counting lost leads.
 */
class AdminCmsLeadRejectReasonController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Причины отказа';
        $this->modelShowAttribute = 'name';
        $this->modelClassName = CmsLeadRejectReason::class;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;
        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'emptyState' => [
                    'title' => 'Причин отказа пока нет',
                    'description' => 'Когда список заполнен, при отклонении лида причину выбирают из него. Так видно, почему теряются лиды. Пока список пуст, причину пишут текстом.',
                    'icon' => 'fas fa-ban',
                    'action' => ['backendAction' => 'create', 'label' => 'Добавить причину'],
                ],
                'filters' => false,
                'grid' => [
                    'on init' => static function (Event $event) {
                        $query = $event->sender->dataProvider->query;
                        $query->cmsSite();
                        $query->addSelect([
                            CmsLeadRejectReason::tableName().'.*',
                            'countLeads' => CmsLead::find()
                                ->select([new Expression('count(1)')])
                                ->andWhere(['cms_lead_reject_reason_id' => new Expression(CmsLeadRejectReason::tableName().'.id')]),
                        ]);
                    },
                    'defaultOrder' => ['sort' => SORT_ASC],
                    'visibleColumns' => ['checkbox', 'actions', 'name', 'sort', 'is_no_answer', 'countLeads'],
                    'columns' => [
                        'name' => [
                            'class' => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-lead-reject-reason',
                            'attribute' => 'name',
                        ],
                        'sort' => [
                            'value' => static fn(CmsLeadRejectReason $model) => \Yii::$app->formatter->asInteger($model->sort),
                        ],
                        'is_no_answer' => [
                            'format' => 'raw',
                            'value' => static fn(CmsLeadRejectReason $model) => $model->is_no_answer
                                ? Html::tag('span', 'Да', ['class' => 'sx-status sx-status--info'])
                                : '',
                        ],
                        'countLeads' => [
                            'label' => 'Отклонено лидов',
                            'format' => 'raw',
                            'value' => static fn(CmsLeadRejectReason $model) => Html::tag('span',
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
            'is_no_answer' => ['class' => BoolField::class, 'allowNull' => false],
        ];
    }
}
