<?php
use yii\helpers\Html;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\widgets\assets\CmsProfileAsset;

CmsProfileAsset::register($this);
$formButton = static function ($route, array $params, string $label, string $confirm = '') {
    return Html::beginForm([$route], 'post').implode('', array_map(static function ($key) use ($params) {
        return Html::hiddenInput($key, $params[$key]);
    }, array_keys($params))).Html::submitButton($label, [
        'class' => 'sx-button sx-button--secondary', 'data-confirm' => $confirm ?: null,
    ]).Html::endForm();
};
?>
<div class="sx-surface-stack">
<?php BackendSurfaceWidget::begin([
    'title' => 'Устройства и сеансы',
    'hint' => 'Здесь сохранён вход в ваш аккаунт. Приложение и браузер на одном телефоне могут отображаться отдельно.',
]); ?>
<div class="sx-button-group">
<?= $formButton('revoke-device', ['mode' => 'others'], 'Выйти на остальных устройствах', 'Завершить все сеансы, кроме текущего?'); ?>
<?= $formButton('revoke-device', ['mode' => 'all'], 'Выйти на всех устройствах', 'Вы выйдете из аккаунта и на этом устройстве. Продолжить?'); ?>
</div>
<?php BackendSurfaceWidget::end(); ?>
<?php foreach ($sessions as $session): ?>
<?php BackendSurfaceWidget::begin([
    'title' => $session->label.((int)$session->id === (int)$current->id ? ' — это устройство' : ''),
    'hint' => 'Последняя активность: '.Yii::$app->formatter->asDatetime($session->last_seen_at),
]); ?>
<p>Вход выполнен: <?= Html::encode(Yii::$app->formatter->asDatetime($session->created_at)); ?></p>
<?= Yii::$app->userSessions->renderDetails($this, $session); ?>
<?= $formButton('revoke-device', ['mode' => 'one', 'id' => $session->id], 'Выйти на этом устройстве', 'Завершить этот сеанс?'); ?>
<?php BackendSurfaceWidget::end(); ?>
<?php endforeach; ?>
</div>
