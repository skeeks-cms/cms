<?php
/**
 * «Доступы» — a tab of «Компании» (owner's decision 29.10.2026): the journal
 * of the access to clients (crm\CrmAccessLog) by what the viewer may see.
 * «Ждут моего решения» by default when there are such requests, «Все»; the
 * kind of the event, the employee, the client, the dates.
 * ClientTransferActions::clientAccessLog().
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $provider
 * @var array $filters scope, event, employee, company, dates
 * @var int $waiting requests waiting for the viewer's decision
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\CrmAccessLog;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

\skeeks\cms\assets\admin\LeadIdentityAsset::register($this);
?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => 'Доступы к клиентам',
        'titleTag' => 'h2',
        'hint' => 'Кто, когда и на каком основании получил доступ к клиенту: запросы и решения, работа по своей сделке, взятие из общего пула, выдача и изменение уровня, передача, возврат в пул. Видны события по вашим клиентам и клиентам ваших подчинённых, ваши собственные; администратору — все.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <?= Html::beginForm(Url::current(['scope' => null, 'event' => null, 'employee' => null, 'company' => null, 'dates' => null, 'page' => null]), 'get', ['class' => 'sx-crm-access-log__filters', 'data-pjax' => 0]); ?>
            <div class="sx-crm-access__actions">
                <?= Html::dropDownList('scope', $filters['scope'], [
                    'waiting' => 'Ждут моего решения'.($waiting ? ' ('.$waiting.')' : ''),
                    'all' => 'Все',
                ], ['class' => 'form-control', 'aria-label' => 'Какие события']); ?>
                <?= Html::dropDownList('event', $filters['event'], CrmAccessLog::events(), ['class' => 'form-control', 'prompt' => 'Любое событие', 'aria-label' => 'Событие']); ?>
                <?= AjaxSelectModel::widget([
                    'id' => 'sx-crm-access-log-employee',
                    'name' => 'employee',
                    'value' => $filters['employee'] ?: null,
                    'modelClass' => CmsUser::class,
                    'modelShowAttribute' => 'shortDisplayName',
                    'searchQuery' => static function ($word = '') {
                        $query = CmsUser::find()->isWorker()->cmsSite();

                        return $word ? $query->search($word) : $query;
                    },
                    'options' => ['placeholder' => 'Сотрудник'],
                ]); ?>
                <?= AjaxSelectModel::widget([
                    'id' => 'sx-crm-access-log-company',
                    'name' => 'company',
                    'value' => $filters['company'] ?: null,
                    'modelClass' => CmsCompany::class,
                    'searchQuery' => static function ($word = '') {
                        $query = CmsCompany::find();
                        \skeeks\cms\crm\CrmAccess::applyClientVisibility($query);

                        return $word ? $query->search($word) : $query;
                    },
                    'options' => ['placeholder' => 'Компания'],
                ]); ?>
                <?= DaterangeInputWidget::widget(['name' => 'dates', 'value' => $filters['dates'], 'options' => ['placeholder' => 'Диапазон дат', 'class' => 'form-control']]); ?>
                <?= Html::submitButton('Показать', ['class' => 'sx-button sx-button--primary sx-button--sm']); ?>
            </div>
        <?= Html::endForm(); ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin(['raised' => true, 'responsive' => true]); ?>
        <?= $this->render('@skeeks/cms/views/crm/_access-log-table', ['events' => $provider->getModels(), 'withClient' => true]); ?>
        <?= LinkPager::widget(['pagination' => $provider->pagination]); ?>
    <?php BackendSurfaceWidget::end(); ?>
</div>
