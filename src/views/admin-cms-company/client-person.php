<?php
/**
 * "Сделка как услуга": the access of one person of the company — the role in
 * the company (all services or only the chosen ones), what is given in each
 * service of the company and the person's channels.
 *
 * @var $this yii\web\View
 * @var $link \skeeks\cms\models\CmsCompany2user
 */

use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsClientAccess;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\widgets\admin\ClientChannelsWidget;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use yii\helpers\Html;
use yii\helpers\Json;

\skeeks\cms\assets\admin\ClientAccessAsset::register($this);

$company = $link->cmsCompany;
$person = $link->cmsUser;

$grants = CmsClientAccess::find()->with(['cmsDealRole', 'areaRows'])
    ->andWhere(['cms_user_id' => (int)$person->id, 'cms_company_id' => (int)$company->id])
    ->all();
$companyGrant = null;
$dealGrants = [];
foreach ($grants as $grant) {
    if ($grant->cms_deal_id) {
        $dealGrants[(int)$grant->cms_deal_id] = $grant;
    } else {
        $companyGrant = $grant;
    }
}
$isFull = $companyGrant && $companyGrant->is_full;

//Active services and those where something is given to the person; no limit, so none is silently cut off.
$deals = CmsDeal::find()->forManager()->with('dealType')
    ->andWhere([CmsDeal::tableName().'.cms_company_id' => (int)$company->id])
    ->andWhere(['or', [CmsDeal::tableName().'.is_active' => 1], [CmsDeal::tableName().'.id' => array_keys($dealGrants)]])
    ->orderBy([CmsDeal::tableName().'.is_active' => SORT_DESC, CmsDeal::tableName().'.id' => SORT_DESC])
    ->all();

$windowUrl = static function (array $route): string {
    return (string)BackendUrlHelper::createByParams($route)->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url;
};
$button = static function (string $label, array $route, string $variant = 'secondary') use ($windowUrl): string {
    $url = $windowUrl($route);
    return Html::a(Html::encode($label), $url, [
        'class'     => 'sx-button sx-button--'.$variant.' sx-button--sm',
        'data-pjax' => '0',
        'onclick'   => 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $url, 'size' => 'small']).').go(); return false;',
    ]);
};
$dealLink = static function (CmsDeal $deal): string {
    return \skeeks\cms\backend\widgets\BackendEntityLink::widget([
        'controllerId' => '/cms/admin-cms-deal',
        'modelId'      => $deal->id,
        'label'        => $deal->name,
    ]);
};
?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title'      => 'Роль в компании',
        'titleTag'   => 'h2',
        'raised'     => true,
        'responsive' => true,
        'options'    => ['class' => 'sx-surface--compact'],
    ]); ?>
        <div class="sx-contact-row">
            <div class="sx-contact-body">
                <?= \skeeks\cms\widgets\admin\CmsUserViewWidget::widget(['cmsUser' => $person]); ?>
                <?php if ($link->comment) : ?><span class="sx-contact-note"><?= Html::encode($link->comment); ?></span><?php endif; ?>
            </div>
        </div>
        <p style="margin: 10px 0 6px;">
            <?php if ($isFull) : ?>
                <span class="sx-deal-badge sx-deal-badge--accent">Полный доступ ко всем услугам</span>
                <span class="sx-deal-access__muted">все сделки компании, в том числе будущие, и все области доступа</span>
            <?php elseif ($companyGrant) : ?>
                <span class="sx-deal-badge sx-deal-badge--accent">Во всех услугах: <?= Html::encode($companyGrant->roleAsText); ?></span>
                <span class="sx-deal-access__muted"><?= Html::encode($companyGrant->levelsAsText); ?></span>
            <?php else : ?>
                <span class="sx-deal-badge">Только выбранные услуги</span>
                <span class="sx-deal-access__muted">доступ выдаётся в каждой сделке отдельно</span>
            <?php endif; ?>
        </p>
        <div class="sx-person-access__actions">
            <?php if ($companyGrant) : ?>
                <?= $button('Изменить роль в компании', ['/cms/admin-cms-client-access/update', 'pk' => $companyGrant->id]); ?>
                <?= CrmContactBlockWidget::menu('/cms/admin-cms-client-access', (int)$companyGrant->id, 'Убрать роль в компании'); ?>
            <?php else : ?>
                <?= $button('Дать доступ ко всем услугам', ['/cms/admin-cms-client-access/create', 'CmsClientAccess' => ['cms_company_id' => $company->id, 'cms_user_id' => $person->id]]); ?>
            <?php endif; ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin([
        'title'      => 'Услуги компании',
        'titleTag'   => 'h2',
        'raised'     => true,
        'responsive' => true,
        'options'    => ['class' => 'sx-surface--compact'],
        'actions'    => CrmContactBlockWidget::hintIcon('Что человек видит в каждой сделке компании. Итоговые права по областям — в карточке сделки, в блоке «Доступ и уведомления».'),
    ]); ?>
        <div class="sx-person-access__deals">
            <?php foreach ($deals as $deal) : ?>
                <?php
                $grant = $dealGrants[(int)$deal->id] ?? null;
                if ($isFull) {
                    $text = 'Полный доступ по роли в компании';
                } elseif ($grant) {
                    $text = 'Выдано в сделке: '.$grant->roleAsText.' — '.$grant->levelsAsText;
                } elseif ($companyGrant) {
                    $text = 'По роли в компании: '.$companyGrant->roleAsText;
                } else {
                    $text = 'Нет доступа';
                }
                if (!$deal->is_active && !$grant) {
                    continue;
                }
                ?>
                <div class="sx-person-access__deal<?= $deal->is_active ? '' : ' sx-person-access__deal--inactive'; ?>">
                    <div>
                        <div class="sx-person-access__deal-name"><?= $dealLink($deal); ?></div>
                        <div class="sx-deal-access__muted">
                            <?= Html::encode(($deal->dealType ? $deal->dealType->name.' · ' : '').($deal->is_active ? '' : 'не активна · ')); ?><?= Html::encode($text); ?>
                        </div>
                    </div>
                    <div class="sx-person-access__actions">
                        <?php if ($grant) : ?>
                            <?= $button('Изменить', ['/cms/admin-cms-client-access/update', 'pk' => $grant->id, 'deal' => $deal->id]); ?>
                        <?php elseif (!$isFull) : ?>
                            <?= $button('Выдать роль', ['/cms/admin-cms-client-access/create', 'CmsClientAccess' => ['cms_deal_id' => $deal->id, 'cms_user_id' => $person->id], 'deal' => $deal->id]); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$deals) : ?>
                <div class="sx-contact-empty">У компании пока нет сделок</div>
            <?php endif; ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>

    <?= ClientChannelsWidget::widget(['user' => $person]); ?>
</div>
