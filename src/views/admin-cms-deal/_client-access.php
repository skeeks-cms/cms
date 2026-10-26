<?php
/**
 * "Сделка как услуга": who of the client has access to this deal, and its resources.
 *
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsDeal
 */

use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use yii\helpers\Html;

/** @var \skeeks\cms\deal\DealAccess $dealAccess */
$dealAccess = \Yii::$app->get('dealAccess');
$grants = $dealAccess->dealGrants($model);
$resources = $dealAccess->resources($model);
$canEdit = \Yii::$app->user->can('cms/admin-company');

$openAction = static function (string $label, array $route, string $variant = 'secondary'): string {
    $url = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams($route)->enableEmptyLayout()->enableNoActions()->url;
    $action = \yii\helpers\Json::encode(['isOpenNewWindow' => true, 'url' => $url, 'size' => 'small']);
    return Html::a($label, $url, [
        'class'     => 'sx-button sx-button--'.$variant.' sx-button--sm',
        'data-pjax' => '0',
        'onclick'   => 'new sx.classes.backend.widgets.Action('.$action.').go(); return false;',
    ]);
};

$this->registerCss(<<<CSS
.sx-deal-access__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.sx-deal-access__list { display: grid; gap: 8px; margin-top: 10px; }
.sx-deal-access__row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 10px 12px; }
.sx-deal-access__person { font-weight: 600; }
.sx-deal-access__meta { font-size: 13px; color: var(--sx-text-muted, #6b7280); margin-top: 2px; }
.sx-deal-access__badge { display: inline-block; font-size: 12px; padding: 1px 8px; border-radius: 999px; background: var(--sx-surface-muted, rgba(127,127,127,.12)); margin-right: 6px; font-weight: 400; }
.sx-deal-access__body { flex: 1 1 auto; min-width: 0; }
.sx-deal-access__grants { display: grid; gap: 6px; margin-top: 8px; padding-left: 60px; }
.sx-deal-access__grant { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.sx-deal-access__grant--covered { opacity: .6; }
.sx-deal-access__grant--editable { cursor: pointer; border-radius: 8px; margin: 0 -6px; padding: 4px 6px; }
.sx-deal-access__grant--editable:hover { background: var(--sx-surface-muted, rgba(127,127,127,.08)); }
@media (max-width: 575px) { .sx-deal-access__grants { padding-left: 0; } }
.sx-deal-access__empty { color: var(--sx-text-muted, #6b7280); margin-top: 8px; }
CSS
);
//A click on a grant opens its edit window; the "⋮" menu and links keep their own behavior.
$this->registerJs(<<<JS
$(document).off('click.sxAccessEdit').on('click.sxAccessEdit', '[data-sx-access-edit]', function (e) {
    if ($(e.target).closest('a, button, .sx-icon-action, [data-sx-ajax-actions], .dropdown-menu').length) {
        return;
    }
    new sx.classes.backend.widgets.Action({isOpenNewWindow: true, url: $(this).data('sx-access-edit'), size: 'small'}).go();
});
JS
);
?>
<section class="sx-deal-section sx-deal-access">
    <div class="sx-deal-access__head">
        <h3 class="sx-deal-section-title">Доступ клиента</h3>
        <?php if ($canEdit) : ?>
            <?= $openAction('+ Дать доступ', ['/cms/admin-cms-client-access/create', 'CmsClientAccess' => ['cms_deal_id' => $model->id], 'deal' => $model->id]); ?>
        <?php endif; ?>
    </div>
    <?php
    //One row per person; under it every grant that acts on this deal.
    $people = [];
    if (!$model->cms_company_id && $model->user) {
        $people[(int)$model->cms_user_id] = ['user' => $model->user, 'owner' => true, 'grants' => []];
    }
    foreach ($grants as $grant) {
        $userId = (int)$grant->cms_user_id;
        if (!isset($people[$userId])) {
            $people[$userId] = ['user' => $grant->cmsUser, 'owner' => false, 'grants' => []];
        }
        $people[$userId]['grants'][] = $grant;
    }
    ?>
    <div class="sx-deal-access__list">
        <?php foreach ($people as $userId => $person) : ?>
            <?php
            $fullByCompany = false;
            foreach ($person['grants'] as $grant) {
                if (!$grant->cms_deal_id && $grant->is_full) {
                    $fullByCompany = true;
                }
            }
            $isFull = $person['owner'] || $fullByCompany;
            ?>
            <div class="sx-surface sx-deal-access__row">
                <div class="sx-deal-access__body">
                    <?php if ($person['user']) : ?>
                        <?= \skeeks\cms\widgets\admin\CmsUserViewWidget::widget([
                            'cmsUser' => $person['user'],
                            'append'  => $person['owner'] ? Html::tag('span', 'владелец', ['class' => 'sx-deal-access__badge']) : '',
                        ]); ?>
                    <?php else : ?>
                        <div class="sx-deal-access__person">#<?= (int)$userId; ?></div>
                    <?php endif; ?>
                    <div class="sx-deal-access__grants">
                        <?php if ($person['owner']) : ?>
                            <div class="sx-deal-access__grant">
                                <div class="sx-deal-access__meta">Полный доступ: сделка оформлена на этого человека</div>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($person['grants'] as $grant) : ?>
                            <?php $isCovered = $isFull && ($grant->cms_deal_id || !$grant->is_full); ?>
                            <?php
                            $editUrl = $canEdit
                                ? (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-cms-client-access/update', 'pk' => $grant->id, 'deal' => $model->id])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url
                                : '';
                            ?>
                            <div class="sx-deal-access__grant<?= $isCovered ? ' sx-deal-access__grant--covered' : ''; ?><?= $editUrl ? ' sx-deal-access__grant--editable' : ''; ?>"<?= $editUrl ? ' data-sx-access-edit="'.Html::encode($editUrl).'" title="Изменить доступ"' : ''; ?>>
                                <div>
                                    <div class="sx-deal-access__meta">
                                        <span class="sx-deal-access__badge"><?= $grant->cms_deal_id ? 'в этой сделке' : 'во всех услугах компании'; ?></span>
                                        <?= Html::encode($grant->roleAsText); ?> — <?= Html::encode($grant->levelsAsText); ?>
                                    </div>
                                    <?php if ($isCovered) : ?>
                                        <div class="sx-deal-access__meta">Не действует, пока есть полный доступ<?= $person['owner'] ? ' владельца' : ' по компании'; ?></div>
                                    <?php endif; ?>
                                    <?php if ($grant->comment) : ?><div class="sx-deal-access__meta"><?= Html::encode($grant->comment); ?></div><?php endif; ?>
                                </div>
                                <?php if ($canEdit) : ?>
                                    <div><?= CrmContactBlockWidget::menu('/cms/admin-cms-client-access', (int)$grant->id, 'Изменить доступ'); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$grants && ($model->cms_company_id || !$model->user)) : ?>
            <div class="sx-deal-access__empty">Никому из клиента доступ не выдан: услуга не видна в личном кабинете.</div>
        <?php endif; ?>
    </div>
</section>

<?php if ($resources) : ?>
    <section class="sx-deal-section">
        <h3 class="sx-deal-section-title">Ресурсы</h3>
        <div class="sx-deal-access__list">
            <?php foreach ($resources as $resource) : ?>
                <?php $provider = $dealAccess->getProvider($resource->type); ?>
                <div class="sx-surface sx-deal-access__row">
                    <div>
                        <div class="sx-deal-access__person">
                            <?php if ($resource->url) : ?>
                                <?php
                                //Employee card: the standard backend window, like other entity links.
                                $adminUrl = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams($resource->url)->enableEmptyLayout()->url;
                                ?>
                                <?= Html::a(Html::encode($resource->title), $adminUrl, [
                                    'data-pjax' => '0',
                                    'onclick'   => 'new sx.classes.backend.widgets.Action('.\yii\helpers\Json::encode(['isOpenNewWindow' => true, 'url' => $adminUrl]).').go(); return false;',
                                ]); ?>
                            <?php else : ?>
                                <?= Html::encode($resource->title); ?>
                            <?php endif; ?>
                        </div>
                        <div class="sx-deal-access__meta">
                            <?= Html::encode($provider ? $provider->label() : $resource->type); ?><?= $resource->status ? ' — '.Html::encode($resource->status) : ''; ?>
                            <?php if ($clientUrl = $dealAccess->clientUrl($resource->clientUrl)) : ?>
                                · <?= Html::a('в кабинете клиента', $clientUrl, ['data-pjax' => '0', 'target' => '_blank', 'title' => 'Так эту услугу видит клиент в личном кабинете']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
