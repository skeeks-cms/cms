<?php
/**
 * «Доступ к клиенту» (П2а, owner's decision 28.10.2026): the main responsible
 * first, the employees with their levels («Свои сделки», «Свои сделки и
 * данные компании», «Полный») and «Доступ только по согласованию». Changed by
 * the main responsible, his leaders and administrators. Hiding by the levels
 * is the next step (П2а+): until then a level below «Полный» is only shown.
 * ClientTransferActions::clientAccess().
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCompany|skeeks\cms\models\CmsUser $model
 * @var array[] $employees id, name, level, main (CrmAccess::accessList())
 * @var bool $canManage
 * @var bool $approval
 * @var bool $canTransfer
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\CrmAccess;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
$levels = CrmAccess::levels();
$url = Url::current();
?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => 'Доступ к клиенту',
        'titleTag' => 'h2',
        'hint' => 'Основной ответственный одобряет запросы и получает уведомления о клиенте. «Свои сделки» — карточка клиента и всё по своим сделкам; «Свои сделки и данные компании» — плюс правка телефонов, e-mail, контактов, адресов и реквизитов; «Полный» — всё.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <ul class="sx-lead-requests">
            <?php foreach ($employees as $employee) : ?>
                <li class="sx-lead-hint">
                    <div class="sx-lead-triage__request">
                        <strong><?= Html::encode($employee['name']); ?></strong>
                        <span class="sx-lead-hint__meta">
                            <?= $employee['main'] ? 'Основной ответственный · полный доступ' : Html::encode('Доступ: '.mb_strtolower($levels[$employee['level']] ?? $employee['level'], 'UTF-8')); ?>
                        </span>
                    </div>
                    <?php if ($canManage && !$employee['main']) : ?>
                        <div class="sx-lead-hint__actions">
                            <?= Html::dropDownList('level', $employee['level'], $levels, [
                                'class' => 'form-control',
                                'aria-label' => 'Уровень доступа '.$employee['name'],
                                'data-sx-crm-access' => 'level',
                                'data-user-id' => $employee['id'],
                                'data-url' => $url,
                            ]); ?>
                            <?= Html::button('Убрать', [
                                'class' => 'sx-button sx-button--secondary sx-button--sm',
                                'data-sx-crm-access' => 'remove',
                                'data-user-id' => $employee['id'],
                                'data-url' => $url,
                                'data-sx-confirm' => 'Сотрудник '.$employee['name'].' больше не будет работать с клиентом. Его сделки останутся у него.',
                            ]); ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($canTransfer) : ?>
            <p class="sx-lead-identity__note">Сменить основного ответственного — «Передать клиента».</p>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Доступ только по согласованию',
        'titleTag' => 'h2',
        'hint' => 'Для ключевых клиентов. Выключено: сотрудник, который нашёл клиента, сразу работает по своей сделке, основному ответственному приходит уведомление. Включено: сначала уходит запрос, сделка создаётся после одобрения. Обращения клиента не ждут одобрения.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <label class="sx-lead-identity__choice">
            <?= Html::checkbox('approval', $approval, [
                'disabled' => !$canManage,
                'data-sx-crm-access' => 'approval',
                'data-url' => $url,
            ]); ?>
            Работать с клиентом только по согласованию с основным ответственным
        </label>
        <?php if (!$canManage) : ?>
            <p class="sx-lead-identity__note">Менять доступ может основной ответственный, его руководитель или администратор.</p>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>
</div>
