<?php
/**
 * The personal page "Уведомления": channels, ready variants, the table
 * "что и куда", quiet hours, muted kinds.
 *
 * @var \yii\web\View                        $this
 * @var int                                  $userId
 * @var \skeeks\cms\notify\Notifications     $notifications
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\notify\Notifications;
use yii\helpers\Html;

\skeeks\cms\widgets\assets\NotifySettingsAsset::register($this);

$channels = $notifications->channelList;
$tree = $notifications->settingsTree($notifications->audienceOf($userId));
$profile = $notifications->profile($userId);
$presets = $notifications->presetList();
$muted = $notifications->mutedKinds($userId);
$modeNames = [
    Notifications::MODE_ALL       => 'Всё',
    Notifications::MODE_IMPORTANT => 'Важное',
    Notifications::MODE_OFF       => 'Нет',
];
$formButton = static function (array $params, string $label, string $class = 'sx-button sx-button--secondary') {
    $html = Html::beginForm(['notifications'], 'post', ['class' => 'sx-notify-settings__inline-form']);
    foreach ($params as $key => $value) {
        $html .= Html::hiddenInput($key, $value);
    }

    return $html.Html::submitButton($label, ['class' => $class]).Html::endForm();
};
$minutes = static fn(int $value) => sprintf('%02d:%02d', intdiv($value, 60), $value % 60);
?>
<div class="sx-surface-stack sx-notify-settings">

<?php BackendSurfaceWidget::begin([
    'title' => 'Способы получения',
    'hint'  => 'Куда могут приходить уведомления. «Веб и браузер» работает всегда: колокольчик в шапке и, если вы разрешили, уведомления браузера.',
]); ?>
<div class="sx-notify-settings__channels">
    <?php foreach ($channels as $code => $channel) : ?>
        <div class="sx-notify-settings__channel">
            <div class="sx-notify-settings__channel-info">
                <div class="sx-notify-settings__channel-name"><?= Html::encode($channel->name); ?></div>
                <div class="sx-notify-settings__channel-status <?= $channel->isConnected($userId) ? 'sx-is-connected' : ''; ?>">
                    <?= Html::encode($channel->status($userId)); ?>
                </div>
                <?php if (($problem = $channel->problem($userId)) !== null) : ?>
                    <div class="sx-notify-settings__channel-problem" role="status"><?= Html::encode($problem); ?></div>
                <?php endif; ?>
            </div>
            <div class="sx-notify-settings__channel-actions"><?= $channel->renderActions($userId, $this); ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php BackendSurfaceWidget::end(); ?>

<?php if (count($presets) > 1) : ?>
<?php BackendSurfaceWidget::begin([
    'title' => 'Быстрая настройка',
    'hint'  => 'Готовый вариант заполнит таблицу ниже, потом её можно поправить. Отключённые виды уведомлений не меняются.',
]); ?>
<div class="sx-notify-settings__presets">
    <?php foreach ($presets as $code => $preset) : ?>
        <?= $formButton(['do' => 'preset', 'preset' => $code], $preset['name'],
            $profile->preset === $code ? 'sx-button sx-button--primary' : 'sx-button sx-button--secondary'); ?>
    <?php endforeach; ?>
</div>
<?php BackendSurfaceWidget::end(); ?>
<?php endif; ?>

<?php BackendSurfaceWidget::begin([
    'title' => 'Что и куда',
    'hint'  => '«Важное» — только важные и срочные события. Срочные приходят всегда, даже в тихие часы. Если для «Веб и браузер» выбрано «Нет», уведомление всё равно сохранится в колокольчике, но придёт тихо: уже прочитанным, без звука и всплывающего окна.',
]); ?>
<?= Html::beginForm(['notifications'], 'post'); ?>
<div class="sx-notify-settings__table-wrap">
    <table class="sx-notify-settings__table">
        <thead>
        <tr>
            <th scope="col">Раздел</th>
            <?php foreach ($channels as $code => $channel) : ?>
                <th scope="col">
                    <span class="sx-notify-settings__channel-head"><?= Html::encode($channel->name); ?></span>
                    <?php if ($code !== Notifications::CHANNEL_CRM) : ?>
                        <button type="submit" name="do" value="copy:<?= Html::encode($code); ?>" class="sx-notify-settings__copy" title="Всё, что приходит в веб и браузер, слать и сюда">как в вебе</button>
                    <?php endif; ?>
                </th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <?php foreach ($tree as $category => $data) : ?>
            <tbody class="sx-notify-settings__category">
            <tr class="sx-notify-settings__category-row">
                <th scope="row">
                    <?= Html::encode($data['name']); ?>
                    <button type="button" class="sx-notify-settings__expand" aria-expanded="false" data-sx-notify-expand="<?= Html::encode($category); ?>">по видам</button>
                </th>
                <?php foreach ($channels as $code => $channel) : ?>
                    <td data-label="<?= Html::encode($channel->name); ?>">
                        <?= Html::dropDownList("modes[{$category}][{$code}]", $notifications->categoryMode($userId, $category, $code), $modeNames, [
                            'class'      => 'sx-notify-settings__select',
                            'aria-label' => $data['name'].': '.$channel->name,
                        ]); ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($data['kinds'] as $kind => $config) : ?>
                <tr class="sx-notify-settings__kind-row" data-sx-notify-kind-of="<?= Html::encode($category); ?>" hidden>
                    <th scope="row">
                        <?= Html::encode($config['name']); ?>
                        <?php if ($config['importance'] >= Notifications::IMPORTANCE_URGENT) : ?>
                            <span class="sx-notify-settings__tag sx-notify-settings__tag--urgent">срочное</span>
                        <?php elseif ($config['importance'] >= Notifications::IMPORTANCE_IMPORTANT) : ?>
                            <span class="sx-notify-settings__tag">важное</span>
                        <?php endif; ?>
                        <?php if (in_array($kind, $muted, true)) : ?>
                            <span class="sx-notify-settings__tag sx-notify-settings__tag--muted">отключено</span>
                        <?php endif; ?>
                    </th>
                    <?php foreach ($channels as $code => $channel) : ?>
                        <td data-label="<?= Html::encode($channel->name); ?>">
                            <?php $inherited = $modeNames[$notifications->categoryMode($userId, $category, $code)] ?? ''; ?>
                            <?= Html::dropDownList("kinds[{$kind}][{$code}]", (string)$notifications->kindMode($userId, $kind, $code), ['' => 'Как в разделе ('.$inherited.')'] + $modeNames, [
                                'class'      => 'sx-notify-settings__select sx-notify-settings__select--kind',
                                'aria-label' => $config['name'].': '.$channel->name,
                            ]); ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        <?php endforeach; ?>
    </table>
</div>
<div class="sx-button-group">
    <button type="submit" name="do" value="save" class="sx-button sx-button--primary">Сохранить</button>
</div>
<?= Html::endForm(); ?>
<?php BackendSurfaceWidget::end(); ?>

<?php BackendSurfaceWidget::begin([
    'title' => 'Тихие часы',
    'hint'  => 'В это время уведомления не приходят в почту, мессенджеры и приложение: они уйдут, когда тихие часы закончатся, если вы ещё не прочитали их в колокольчике. Срочные приходят сразу.',
]); ?>
<?= Html::beginForm(['notifications'], 'post', ['class' => 'sx-notify-settings__quiet']); ?>
<?= Html::hiddenInput('do', 'quiet'); ?>
<label class="sx-notify-settings__check">
    <?= Html::checkbox('is_quiet', (bool)$profile->is_quiet); ?>
    <span>Включить тихие часы</span>
</label>
<label class="sx-notify-settings__time">с <?= Html::input('time', 'quiet_from', $minutes((int)$profile->quiet_from), ['class' => 'sx-notify-settings__time-input']); ?></label>
<label class="sx-notify-settings__time">до <?= Html::input('time', 'quiet_to', $minutes((int)$profile->quiet_to), ['class' => 'sx-notify-settings__time-input']); ?></label>
<button type="submit" class="sx-button sx-button--secondary">Сохранить</button>
<?= Html::endForm(); ?>
<?php BackendSurfaceWidget::end(); ?>

<?php if ($muted) : ?>
<?php BackendSurfaceWidget::begin([
    'title' => 'Отключённые уведомления',
    'hint'  => 'Вы выбрали «Не уведомлять о таком». Эти уведомления никуда не приходят.',
]); ?>
<div class="sx-notify-settings__muted">
    <?php foreach ($muted as $kind) : ?>
        <?php $config = $notifications->kind($kind); ?>
        <div class="sx-notify-settings__muted-row">
            <span><?= Html::encode($config ? $config['name'] : $kind); ?>
                <?php if ($config) : ?><span class="sx-notify-settings__muted-category"><?= Html::encode($notifications->categoryName($config['category'])); ?></span><?php endif; ?>
            </span>
            <?= $formButton(['do' => 'unmute', 'kind' => $kind], 'Вернуть'); ?>
        </div>
    <?php endforeach; ?>
</div>
<?php BackendSurfaceWidget::end(); ?>
<?php endif; ?>

<?php
$event = new \skeeks\cms\notify\NotifySettingsEvent(['userId' => $userId, 'view' => $this]);
$notifications->trigger(Notifications::EVENT_RENDER_SETTINGS, $event);
echo implode('', $event->html);
?>
</div>
