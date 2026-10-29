<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\crm;

use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\widgets\admin\CrmCardActionsWidget;
use skeeks\cms\widgets\assets\CrmCardActionsAsset;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * The tabs of the CRM cards — a company, a person client, a deal (owner's
 * decision 30.10.2026): one order by the `priority` of the backend actions
 * (the constants below; a tab hidden from an employee does not shift the
 * others), and over every list tab the button «Новая …» — the same action as
 * in the block of the card ({@see CrmCardActionsWidget::recordActions()}):
 * the form opens with the company, the client or the deal chosen, only when
 * the server lets the employee create the record, and on «Свои сделки» and
 * "edit" with the choice of his open deal.
 */
class CrmCardTabs
{
    // Профиль / Карточка сделки — the view action, before these.
    const PRIORITY_LEADS = 100;
    const PRIORITY_SERVICES = 200;
    const PRIORITY_BILLS = 300;
    const PRIORITY_PAYMENTS = 400;
    const PRIORITY_DOCUMENTS = 500;
    // Tabs of a person client near the finance: checks, bonuses, orders.
    const PRIORITY_CHECKS = 520;
    const PRIORITY_BONUS = 540;
    const PRIORITY_ORDERS = 560;
    const PRIORITY_TASKS = 600;
    const PRIORITY_CALLS = 700;
    const PRIORITY_STAGE_HISTORY = 750;
    const PRIORITY_PROJECTS = 800;
    const PRIORITY_ACCESS = 890;
    const PRIORITY_EDIT = 900;
    const PRIORITY_ACTIVITY = 1000;

    /**
     * In the `on gridInit` of a related list tab: the button «$label» of the
     * card action $key instead of the standard «Добавить».
     *
     * @param \skeeks\cms\models\CmsCompany|\skeeks\cms\models\User|\skeeks\cms\models\CmsLead $record the card record
     */
    public static function createOver(BackendGridModelRelatedAction $action, $record, string $key, string $label): void
    {
        $action->isStandartBeforeRender = false;
        self::attach($action->relatedIndexAction, $record, $key, $label);
    }

    /**
     * The same over a list action a card renders itself (a callback tab):
     * the button over the grid and the action of its empty state.
     */
    public static function attach(BackendGridModelAction $index, $record, string $key, string $label): void
    {
        $item = CrmCardActionsWidget::recordActions($record)[$key] ?? null;
        $url = self::singleUrl($item);
        // A choice (deals, document types) has no single link: the empty state then has no button.
        $emptyAction = $url ? [
            'label' => $label,
            'url' => $url,
            'icon' => 'fa fa-plus',
            'variant' => 'primary',
            'options' => ['data-pjax' => '0', 'onclick' => new JsExpression(CrmCardActionsWidget::windowOnClick($url))],
        ] : false;
        $index->emptyState['action'] = $emptyAction;
        // A tab with its own empty state (grid.emptyState) gets the same button.
        if (is_array($index->grid['emptyState'] ?? null)) {
            $index->grid['emptyState']['action'] = $emptyAction;
        }
        $index->on('beforeRender', function ($event) use ($item, $label) {
            $event->content = self::button($item, $label);
        });
    }

    /**
     * The button over a list tab: one link — a button, several (deals,
     * document types, «Заплатили нам / Мы заплатили») — the button opening
     * their menu, as the menu of the block.
     */
    public static function button(?array $item, string $label): string
    {
        if (!$item) {
            return '';
        }
        $text = Html::tag('span', '', ['class' => 'fa fa-plus sx-action-icon']).' '.Html::encode($label);
        if ($url = self::singleUrl($item)) {
            return Html::tag('ul', Html::tag('li', Html::button($text, [
                'class' => 'btn btn-primary',
                'onclick' => CrmCardActionsWidget::windowOnClick($url),
            ])), ['class' => 'nav nav-pills sx-nav']).'<br>';
        }
        $menu = !empty($item['choiceTitle']) ? Html::tag('div', Html::encode($item['choiceTitle']), ['class' => 'sx-crm-actions__menu-title']) : '';
        foreach ((array)($item['items'] ?? []) as $sub) {
            if (!empty($sub['url'])) {
                $menu .= Html::a(Html::encode($sub['label'] ?? $label), $sub['url'], [
                    'class' => 'sx-crm-actions__menu-item',
                    'role' => 'menuitem',
                    'data-pjax' => '0',
                    'onclick' => CrmCardActionsWidget::windowOnClick($sub['url']),
                ]);
            }
        }
        if ($menu === '') {
            return '';
        }
        CrmCardActionsAsset::register(\Yii::$app->view);

        return Html::tag('details',
            Html::tag('summary', $text.' '.Html::tag('span', '', ['class' => 'fa fa-angle-down']), ['class' => 'btn btn-primary'])
            .Html::tag('div', $menu, ['class' => 'sx-crm-actions__menu', 'role' => 'menu']),
            ['class' => 'sx-crm-actions__more sx-crm-tab-create', 'data-sx-crm-actions-menu' => true]
        ).'<br>';
    }

    /** The link of an action that needs no choice, null for a choice. */
    protected static function singleUrl(?array $item): ?string
    {
        if (!$item) {
            return null;
        }
        if (!empty($item['items'])) {
            return count($item['items']) === 1 && empty($item['choice']) ? (array_values($item['items'])[0]['url'] ?? null) : null;
        }

        return $item['url'] ?? null;
    }
}
