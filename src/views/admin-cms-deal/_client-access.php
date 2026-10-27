<?php
/**
 * "Сделка как услуга" in the deal card: «Доступ и уведомления» (what the deal
 * opens to the client, client people with their effective rights and where
 * they come from, the place of subscriptions) and «Ресурсы».
 *
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsDeal
 */

use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\deal\DealAccess;
use skeeks\cms\widgets\admin\ClientChannelsWidget;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use skeeks\cms\widgets\admin\DealAccessNotifyEvent;
use yii\helpers\Html;
use yii\helpers\Json;

\skeeks\cms\assets\admin\ClientAccessAsset::register($this);

/** @var DealAccess $dealAccess */
$dealAccess = \Yii::$app->get('dealAccess');
$areas = $dealAccess->getAreaList();
$open = $dealAccess->openInfo($model);
$people = $dealAccess->explain($model);
$resources = $dealAccess->resources($model);
$canEdit = \Yii::$app->user->can('cms/admin-company');
$type = $model->dealType;
$currency = $model->currency_code ?: "RUB";

$windowUrl = static function (array $route): string {
    return (string)BackendUrlHelper::createByParams($route)->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url;
};
$windowOnClick = static function (string $url, string $size = 'small'): string {
    return 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $url, 'size' => $size]).').go(); return false;';
};
$button = static function (string $label, array $route, string $icon = 'plus') use ($windowUrl, $windowOnClick): string {
    $url = $windowUrl($route);
    return Html::a(CrmContactBlockWidget::icon($icon, 14).' '.Html::encode($label), $url, [
        'class'     => 'sx-button sx-button--secondary sx-button--sm',
        'data-pjax' => '0',
        'onclick'   => $windowOnClick($url),
    ]);
};

//A click on a row with data-sx-window opens it; the "⋮" menu and links keep their own behavior.
$this->registerJs(<<<JS
$(document).off('click.sxDealWindow').on('click.sxDealWindow', '[data-sx-window]', function (e) {
    if ($(e.target).closest('a, button, .sx-icon-action, [data-sx-ajax-actions], .dropdown-menu').length) {
        return;
    }
    new sx.classes.backend.widgets.Action({isOpenNewWindow: true, url: $(this).data('sx-window'), size: 'small'}).go();
});
JS
);

$sourceText = [
    'owner'   => 'владелец сделки',
    'company' => 'из роли в компании',
    'deal'    => 'выдано в сделке',
];
$levelText = static function (string $area, int $level) use ($dealAccess): string {
    return $level ? $dealAccess->levelName($area, $level) : 'Закрыто';
};
?>
<section class="sx-deal-section sx-deal-access">
    <div class="sx-deal-access__head">
        <h3 class="sx-deal-section-title">Доступ и уведомления</h3>
        <?php if ($canEdit) : ?>
            <?= $button('Дать доступ', ['/cms/admin-cms-client-access/create', 'CmsClientAccess' => ['cms_deal_id' => $model->id], 'deal' => $model->id]); ?>
        <?php endif; ?>
    </div>

    <div class="sx-deal-access__head">
        <div class="sx-deal-access__subtitle">Что открыто клиенту</div>
        <?php if ($canEdit) : ?>
            <?php $url = $windowUrl(['/cms/admin-cms-deal/client-open', 'pk' => $model->id]); ?>
            <?= Html::a('Настроить', $url, ['data-pjax' => '0', 'onclick' => $windowOnClick($url), 'class' => 'sx-deal-access__muted']); ?>
        <?php endif; ?>
    </div>
    <div class="sx-deal-open">
        <?php foreach ($areas as $area => $config) : ?>
            <?php
            $info = $open[$area];
            $title = $type ? 'Тип «'.$type->name.'» разрешает: '.mb_strtolower($levelText($area, $info['type'])) : 'Без типа сделки';
            if ($info['deal'] !== null && $info['deal'] < $info['type']) {
                $title .= '. В этой сделке сужено до: '.mb_strtolower($levelText($area, $info['deal']));
            }
            $class = 'sx-deal-open__chip';
            if (!$info['open']) {
                $class .= ' sx-deal-open__chip--closed';
            } elseif ($info['limit'] === 'deal') {
                $class .= ' sx-deal-open__chip--narrowed';
            }
            ?>
            <span class="<?= $class; ?>" title="<?= Html::encode($title); ?>">
                <b><?= Html::encode($config['name']); ?>:</b> <?= Html::encode(mb_strtolower($levelText($area, $info['open']))); ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="sx-deal-access__subtitle" style="margin-top: 18px;">Люди клиента</div>
    <?php if ($people) : ?>
        <?php $notify = DealAccessNotifyEvent::collect($model, $people); ?>
        <table class="sx-deal-people">
            <thead>
            <tr>
                <th>Человек</th>
                <?php foreach ($areas as $area => $config) : ?>
                    <th class="<?= $open[$area]['open'] ? '' : 'is-closed'; ?>" title="<?= $open[$area]['open'] ? '' : 'Закрыто в этой сделке'; ?>"><?= Html::encode($config['name']); ?></th>
                <?php endforeach; ?>
                <th>Каналы</th>
                <?php if ($notify->cells) : ?><th>Уведомления</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($people as $userId => $person) : ?>
                <?php
                $fullByCompany = false;
                foreach ($person['grants'] as $grant) {
                    if (!$grant->cms_deal_id && $grant->is_full) {
                        $fullByCompany = true;
                    }
                }
                ?>
                <tr>
                    <td class="sx-deal-people__person">
                        <?php if ($person['user']) : ?>
                            <?= \skeeks\cms\widgets\admin\CmsUserViewWidget::widget([
                                'cmsUser' => $person['user'],
                                'append'  => $person['owner'] ? ' '.Html::tag('span', 'владелец', ['class' => 'sx-deal-badge sx-deal-badge--accent']) : '',
                            ]); ?>
                        <?php else : ?>
                            #<?= (int)$userId; ?>
                        <?php endif; ?>
                        <div class="sx-deal-people__grants">
                            <?php if ($person['owner']) : ?>
                                <div class="sx-deal-grant"><span class="sx-deal-grant__text">Полный доступ: сделка оформлена на этого человека</span></div>
                            <?php endif; ?>
                            <?php foreach ($person['grants'] as $grant) : ?>
                                <?php
                                $covered = ($person['owner'] || $fullByCompany) && ($grant->cms_deal_id || !$grant->is_full);
                                $editUrl = $canEdit ? $windowUrl(['/cms/admin-cms-client-access/update', 'pk' => $grant->id, 'deal' => $model->id]) : '';
                                $text = Html::tag('span', $grant->cms_deal_id ? 'в сделке' : 'в компании', ['class' => 'sx-deal-badge'.($grant->cms_deal_id ? ' sx-deal-badge--success' : '')])
                                    .' '.Html::encode($grant->roleAsText);
                                if ($covered) {
                                    $text .= ' — не действует, пока есть полный доступ';
                                }
                                if ($grant->comment) {
                                    $text .= ' · '.Html::encode($grant->comment);
                                }
                                ?>
                                <div class="sx-deal-grant<?= $covered ? ' sx-deal-grant--covered' : ''; ?><?= $editUrl ? ' sx-deal-grant--editable' : ''; ?>"
                                    <?= $editUrl ? 'data-sx-window="'.Html::encode($editUrl).'" title="'.Html::encode(($grant->cms_deal_id ? 'Изменить доступ в этой сделке' : 'Изменить доступ во всех услугах компании').': '.$grant->levelsAsText).'"' : ''; ?>>
                                    <span class="sx-deal-grant__text"><?= $text; ?></span>
                                    <?php if ($canEdit) : ?>
                                        <?= CrmContactBlockWidget::menu('/cms/admin-cms-client-access', (int)$grant->id, 'Изменить доступ'); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <?php foreach ($areas as $area => $config) : ?>
                        <?php
                        $cell = $person['areas'][$area];
                        $from = $cell['source'] ? $sourceText[$cell['source']] : '';
                        $cut = '';
                        if ($cell['limit']) {
                            $where = $cell['limit'] === 'deal' ? 'в сделке' : 'типом сделки';
                            $cut = $cell['level'] ? 'сужено '.$where : 'закрыто '.$where;
                        }
                        $tip = $config['name'].': '.mb_strtolower($cell['level'] ? $dealAccess->levelName($area, $cell['level']) : 'нет доступа').'.';
                        if ($cell['grant']) {
                            $tip .= ' Право: '.$cell['grant']->roleAsText.' ('.($cell['grant']->cms_deal_id ? 'выдано в этой сделке' : 'роль во всех услугах компании').').';
                        } elseif ($cell['source'] === 'owner') {
                            $tip .= ' Сделка оформлена на этого человека.';
                        }
                        if ($cell['limit']) {
                            $tip .= ' По праву — '.mb_strtolower($dealAccess->levelName($area, $cell['granted'])).', но '.($cell['limit'] === 'deal' ? 'в этой сделке' : 'в типе сделки').' открыто: '.mb_strtolower($levelText($area, $cell['open'])).'.';
                        }
                        ?>
                        <td>
                            <div class="sx-deal-cell<?= $cell['level'] ? '' : ' sx-deal-cell--none'; ?><?= $cut ? ' sx-deal-cell--cut' : ''; ?>" title="<?= Html::encode($tip); ?>" tabindex="0">
                                <span class="sx-deal-cell__area"><?= Html::encode($config['name']); ?></span>
                                <span class="sx-deal-cell__level"><?= Html::encode($cell['level'] ? $dealAccess->levelName($area, $cell['level']) : '—'); ?></span>
                                <?php if ($cut || ($cell['level'] && $from)) : ?>
                                    <span class="sx-deal-cell__from"><?= Html::encode($cut ?: $from); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                    <td><?= $person['user'] ? ClientChannelsWidget::widget(['user' => $person['user'], 'compact' => true]) : ''; ?></td>
                    <?php if ($notify->cells) : ?><td><?= $notify->cells[$userId] ?? ''; ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="sx-deal-people__legend">Наведите на клетку, чтобы увидеть, откуда право. Итог — право человека, но не больше того, что открыто в сделке.</div>
        <?php if ($notify->handled) : ?>
            <?= $notify->footer; ?>
        <?php else : ?>
            <div class="sx-deal-notify-stub">
                <?= CrmContactBlockWidget::icon('bell', 16); ?>
                <span>Подписки на уведомления по этой услуге появятся вместе с уведомлениями клиентам. Сейчас права определяют, что человек видит в личном кабинете.</span>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="sx-deal-access__muted">Никому из клиента доступ не выдан: услуга не видна в личном кабинете.</div>
    <?php endif; ?>
</section>


<?php if ($resources) : ?>
    <section class="sx-deal-section">
        <h3 class="sx-deal-section-title">Ресурсы</h3>
        <div class="sx-deal-items">
            <?php foreach ($resources as $resource) : ?>
                <?php $provider = $dealAccess->getProvider($resource->type); ?>
                <div class="sx-deal-resource">
                    <div>
                        <div class="sx-deal-resource__title">
                            <?php if ($resource->url) : ?>
                                <?php $adminUrl = (string)BackendUrlHelper::createByParams($resource->url)->enableEmptyLayout()->url; ?>
                                <?= Html::a(Html::encode($resource->title), $adminUrl, [
                                    'data-pjax' => '0',
                                    'onclick'   => 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $adminUrl]).').go(); return false;',
                                ]); ?>
                            <?php else : ?>
                                <?= Html::encode($resource->title); ?>
                            <?php endif; ?>
                        </div>
                        <div class="sx-deal-access__muted">
                            <?= Html::encode($provider ? $provider->label() : $resource->type); ?><?= $resource->status ? ' — '.Html::encode($resource->status) : ''; ?>
                            <?php if ($clientUrl = $dealAccess->clientUrl($resource->clientUrl)) : ?>
                                · <?= Html::a('в кабинете клиента', $clientUrl, ['data-pjax' => '0', 'target' => '_blank', 'title' => 'Так эту услугу видит клиент в личном кабинете']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($canEdit) : ?>
                        <?= $button('Перенести в другую сделку', ['/cms/admin-cms-deal/move-resource', 'pk' => $model->id, 'type' => $resource->type, 'id' => $resource->id], 'forward'); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
