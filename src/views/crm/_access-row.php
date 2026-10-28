<?php
/**
 * One employee of a client — the same row in «Доступ» and in the card block
 * «Работают с …»: the avatar and name, the post, the level in words. Who
 * manages the access: in the tab — the level select and «Убрать», in the card
 * — a cross shown on hover. The main responsible is never removed (only
 * transferred).
 *
 * @var yii\web\View $this
 * @var array $employee id, name, level, main, origin (CrmAccess::accessRows())
 * @var string $mode "tab" | "card"
 * @var bool $canManage
 * @var string $accessUrl the «Доступ» action (its POST changes the access)
 */

use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use yii\helpers\Html;

$levels = CrmAccess::levels();
$level = $employee['main']
    ? Html::tag('span', 'Основной', ['class' => 'sx-deal-badge sx-deal-badge--accent']).' полный доступ'
    : Html::encode($levels[$employee['level']] ?? $employee['level']).($mode === 'tab' ? ' · '.Html::encode($employee['origin']) : '');
$user = CmsUser::findOne((int)$employee['id']);
$remove = [
    'data-sx-crm-access' => 'remove',
    'data-user-id' => $employee['id'],
    'data-url' => $accessUrl,
    'data-sx-confirm' => 'Сотрудник '.$employee['name'].' больше не будет работать с клиентом. Открытые сделки останутся у этого сотрудника.',
];
?>
<li class="sx-crm-access__row sx-crm-access__row--<?= Html::encode($mode); ?>">
    <div class="sx-crm-access__who">
        <?= $user ? CmsWorkerViewWidget::widget([
            'user' => $user,
            'append' => Html::tag('div', $level, ['class' => 'sx-preview-card__meta sx-collection-cell__secondary sx-crm-access__level']),
        ]) : Html::encode($employee['name']); ?>
    </div>
    <?php if ($canManage && !$employee['main']) : ?>
        <?php if ($mode === 'tab') : ?>
            <div class="sx-crm-access__actions">
                <?= Html::dropDownList('level', $employee['level'], $levels, [
                    'class' => 'form-control',
                    'aria-label' => 'Уровень доступа '.$employee['name'],
                    'data-sx-crm-access' => 'level',
                    'data-user-id' => $employee['id'],
                    'data-url' => $accessUrl,
                ]); ?>
                <?= Html::button('Убрать', ['class' => 'sx-button sx-button--secondary sx-button--sm'] + $remove); ?>
            </div>
        <?php else : ?>
            <?= CrmContactBlockWidget::iconButton('close', 'Убрать сотрудника', ['class' => 'sx-crm-access__remove'] + $remove); ?>
        <?php endif; ?>
    <?php endif; ?>
</li>
