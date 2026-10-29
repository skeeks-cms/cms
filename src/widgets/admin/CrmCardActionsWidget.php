<?php

namespace skeeks\cms\widgets\admin;

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\widgets\assets\CrmCardActionsAsset;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * One block of actions over the feed of a CRM card, as "Дело / Комментарий /
 * Сообщение / Задача / Ещё" of Bitrix24: a row of action tabs and the field
 * of a note under them. "Примечание" is the default tab: a one-line field
 * that opens into the comment editor with "Отправить" and "Отмена". Other
 * tabs open their action as the card buttons did (a backend window, a call,
 * a letter); rare ones go to "Ещё".
 *
 * Tabs of packages come from {@see CrmCardBlocksWidget::EVENT_ACTIONS}, the
 * same event as before; the card gives its own tabs in {@see $actions}.
 *
 * ```php
 * <?= CrmCardActionsWidget::widget(['model' => $model, 'commentUrl' => ['/cms/admin-cms-lead/add-comment', 'pk' => $model->id], 'actions' => $actions]) ?>
 * ```
 *
 * Put it inside the pjax container of the feed: a sent note reloads it.
 */
class CrmCardActionsWidget extends Widget
{
    /** @var \yii\db\ActiveRecord the card record */
    public $model;

    /** @var array|string|null the add-comment action of the card; null — no "Примечание" tab */
    public $commentUrl = null;

    public string $noteLabel = 'Примечание';

    public string $notePlaceholder = 'Напишите примечание…';

    /** @var bool "Закрепить" in the note form */
    public bool $isShowPin = true;

    /**
     * Tabs of the card before the tabs of packages, in their order:
     * id => `label` (plain text), `icon` (BackendIcon name), and either
     * `url` (a backend window opened over the card; `window` — extra options
     * of sx.classes.backend.widgets.Action) or `options` (attributes of a
     * button: onclick, data-*) or `items` (a choice under the tab, e.g. one of
     * several phones: id => `label`, `url` or `options`; one item makes a
     * plain tab); `more` => true puts the tab into "Ещё".
     * {@see contactTabs()} builds "Позвонить" and "Письмо" of a card.
     *
     * @var array[]
     */
    public array $actions = [];

    /** @var bool add the tabs of packages ({@see CrmCardBlocksWidget::actions()}) */
    public bool $packageActions = true;

    /** @var int action tabs in the row besides "Примечание"; the rest go to "Ещё" */
    public int $rowLimit = 6;

    /** @var bool the block is a raised surface; false — inside another block (a deal of {@see $deals}) */
    public bool $framed = true;

    /**
     * Company or client card of an employee who works with the client only
     * through his deals ({@see ownDeals()}): his open deals; every action goes
     * to a deal — one is taken as is, several are chosen first, none gives
     * "Создать сделку". Null — the usual block of the card.
     *
     * @var \skeeks\cms\models\CmsLead[]|null
     */
    public ?array $deals = null;

    /** @var array phones of the client for the deals of {@see $deals} */
    public array $phones = [];

    /** @var array emails of the client for the deals of {@see $deals} */
    public array $emails = [];

    /**
     * The block of a company or a client card in one line: the note, "Задача"
     * with the record chosen, "Позвонить" and "Письмо" ({@see contactTabs()})
     * and the tabs of packages; for an employee who works with the client only
     * through his deals — the same actions of his deal ({@see $deals}).
     *
     * @param \skeeks\cms\models\CmsCompany|\skeeks\cms\models\User $record
     */
    public static function client($record, iterable $phones = [], iterable $emails = []): string
    {
        $phones = is_array($phones) ? $phones : iterator_to_array($phones, false);
        $emails = is_array($emails) ? $emails : iterator_to_array($emails, false);
        $deals = self::ownDeals($record);
        if ($deals !== null) {
            return self::widget(['model' => $record, 'deals' => $deals, 'phones' => $phones, 'emails' => $emails]);
        }
        $isCompany = $record instanceof \skeeks\cms\models\CmsCompany;
        // An employee card: only the note.
        $isClient = $isCompany || !$record->is_worker;
        $actions = [];
        if ($isClient && \Yii::$app->user->can('cms/admin-task')) {
            $actions['task'] = self::taskTab(['CmsTask' => [$isCompany ? 'cms_company_id' : 'cms_user_id' => (int)$record->id]]);
        }
        if ($isClient) {
            $actions += self::contactTabs($record, $phones, $emails);
        }

        return self::widget(['model' => $record, 'commentUrl' => ['/cms/admin-cms-log/add-comment'], 'actions' => $actions]);
    }

    /**
     * Open deals of the employee by a company or a client when he works with
     * it only through his deals: levels "Свои сделки" and "Свои сделки и
     * данные компании" (CrmAccess: the client is seen, not in full —
     * seesWholeClient()); his deals are those the deal scope shows him, as in
     * CrmAccess::feedCondition(). Null — he sees the whole client (or it is
     * an employee card): the usual block.
     *
     * @return \skeeks\cms\models\CmsLead[]|null
     */
    public static function ownDeals($record): ?array
    {
        if (!\Yii::$app->has('user') || \Yii::$app->user->isGuest) {
            return null;
        }
        $code = \skeeks\cms\crm\CrmAccess::clientRecordOf($record);
        if ($code === null || ($record instanceof \skeeks\cms\models\User && $record->is_worker)) {
            return null;
        }
        $access = \skeeks\cms\crm\CrmAccess::instance();
        $user = \Yii::$app->user->identity;
        if ($access->clientDataLevel($user, $code[0], (int)$code[1]) === null
            || \skeeks\cms\crm\CrmAccess::seesWholeClient($access->wholeClientLevel($user, $code[0], (int)$code[1]))) {
            return null;
        }
        $table = \skeeks\cms\models\CmsLead::tableName();

        return \skeeks\cms\models\CmsLead::find()->forManager($user)
            ->andWhere([$table.'.'.($record instanceof \skeeks\cms\models\CmsCompany ? 'cms_company_id' : 'cms_user_id') => (int)$record->id])
            ->andWhere([$table.'.status' => [\skeeks\cms\models\CmsLead::STATUS_NEW, \skeeks\cms\models\CmsLead::STATUS_IN_WORK]])
            ->orderBy([$table.'.id' => SORT_DESC])
            ->limit(20)
            ->all();
    }

    /** "Задача": the create window of a task with the given GET parameters (CmsTask[...]). */
    protected static function taskTab(array $params): array
    {
        return ['label' => 'Задача', 'icon' => 'plus', 'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(
            array_merge(['/cms/admin-cms-task/create'], $params)
        )->enableEmptyLayout()->enableNoActions()->url];
    }

    /** A backend window over the card. */
    protected static function windowOnClick(string $url): string
    {
        return 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $url]).').go(); return false;';
    }

    /**
     * The block of {@see $deals}: "по сделке №…" or the choice of the deal over
     * the actions of each deal, or "Создать сделку" without deals (its card
     * opens after the save, and the work goes on there).
     */
    protected function renderDeals(): string
    {
        CrmCardActionsAsset::register($this->view);
        $isCompany = $this->model instanceof \skeeks\cms\models\CmsCompany;
        if (!$this->deals) {
            if (!\Yii::$app->user->can('cms/admin-lead')) {
                return '';
            }
            $url = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                '/cms/admin-cms-lead/create',
                'CmsLead' => [$isCompany ? 'cms_company_id' : 'cms_user_id' => (int)$this->model->id],
            ])->enableEmptyLayout()->url;
            $body = Html::tag('div',
                Html::tag('span', 'С этим клиентом вы работаете через свои сделки. Открытых сделок нет.', ['class' => 'sx-crm-actions__deals-text'])
                .Html::a($this->icon('plus').Html::tag('span', 'Создать сделку'), $url, [
                    'class' => 'sx-button sx-button--primary sx-button--sm',
                    'data-pjax' => '0',
                    'onclick' => self::windowOnClick($url),
                ]),
                ['class' => 'sx-crm-actions__deals-empty']
            );

            return Html::tag('div', $body, ['id' => $this->getId(), 'class' => 'sx-surface sx-surface--raised sx-crm-actions sx-crm-actions--deals']);
        }

        $canTask = \Yii::$app->user->can('cms/admin-task');
        $groups = '';
        $choices = [];
        foreach (array_values($this->deals) as $i => $lead) {
            $id = (int)$lead->id;
            $actions = $canTask ? ['task' => self::taskTab(['CmsTask' => ['cms_lead_id' => $id]])] : [];
            $actions += self::contactTabs($lead, $this->phones, $this->emails, ['data-lead-id' => $id]);
            $groups .= Html::tag('div', self::widget([
                'model' => $lead,
                'framed' => false,
                'commentUrl' => ['/cms/admin-cms-lead/add-comment', 'pk' => $id],
                'notePlaceholder' => 'Примечание к сделке №'.$id.'…',
                'actions' => $actions,
            ]), ['data-sx-crm-actions-deal' => $id, 'hidden' => $i > 0]);
            $choices[$id] = '№'.$id.($lead->name ? ' · '.$lead->name : '');
        }

        if (count($choices) === 1) {
            $id = (int)key($choices);
            $url = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-cms-lead/view', 'pk' => $id])
                ->enableEmptyLayout()->url;
            $head = Html::tag('span', 'по сделке', ['class' => 'sx-crm-actions__deals-text']).' '
                .Html::a(Html::encode(reset($choices)), $url, ['data-pjax' => '0', 'onclick' => self::windowOnClick($url)]);
        } else {
            $head = Html::label('По сделке', $this->getId().'-deal', ['class' => 'sx-crm-actions__deals-text']).' '
                .Html::dropDownList('', null, $choices, [
                    'id' => $this->getId().'-deal',
                    'class' => 'sx-crm-actions__deals-select',
                    'data-sx-crm-actions-deal-select' => true,
                ]);
        }

        return Html::tag('div',
            Html::tag('div', $head, ['class' => 'sx-crm-actions__deals-head'])
            .$groups,
            ['id' => $this->getId(), 'class' => 'sx-surface sx-surface--raised sx-crm-actions sx-crm-actions--deals']
        );
    }

    public function run()
    {
        if ($this->deals !== null) {
            return $this->renderDeals();
        }

        $items = [];
        foreach ($this->actions as $id => $action) {
            if (!is_array($action) || trim((string)($action['label'] ?? '')) === '') {
                continue;
            }
            // A choice of one is a plain tab with the label of the tab.
            if (!empty($action['items']) && count($action['items']) === 1) {
                $action = array_merge($action, array_intersect_key(reset($action['items']), ['url' => 1, 'options' => 1, 'window' => 1]));
                unset($action['items']);
            }
            if (!empty($action['items']) || trim((string)($action['url'] ?? '')) !== '' || !empty($action['options'])) {
                $items[$id] = $action;
            }
        }
        if ($this->packageActions) {
            $items += CrmCardBlocksWidget::actions($this->model);
        }
        if (!$items && $this->commentUrl === null) {
            return '';
        }

        CrmCardActionsAsset::register($this->view);

        $row = [];
        $more = [];
        foreach ($items as $id => $item) {
            if (empty($item['more']) && count($row) < $this->rowLimit) {
                $row[$id] = $item;
            } else {
                $more[$id] = $item;
            }
        }

        $tabs = '';
        if ($this->commentUrl !== null) {
            $tabs .= Html::button($this->icon('message').Html::tag('span', Html::encode($this->noteLabel)), [
                'class' => 'sx-crm-actions__tab is-active',
                'aria-pressed' => 'true',
                'data-sx-crm-actions-open' => true,
            ]);
        }
        // Every row tab has a hidden copy in "Ещё": the script moves the tabs that do not fit the
        // width of the card into the menu, as Bitrix24 does.
        $menu = '';
        foreach ($row as $id => $item) {
            $tabs .= $this->renderTab((string)$id, $item);
            $menu .= $this->renderMenuItems((string)$id, $item, ['hidden' => true, 'data-sx-crm-actions-copy' => (string)$id]);
        }
        foreach ($more as $id => $item) {
            $menu .= $this->renderMenuItems((string)$id, $item);
        }
        if ($menu !== '') {
            $tabs .= Html::tag('details',
                Html::tag('summary', Html::tag('span', 'Ещё').$this->icon('chevron-down'), ['class' => 'sx-crm-actions__tab'])
                .Html::tag('div', $menu, ['class' => 'sx-crm-actions__menu', 'role' => 'menu']),
                ['class' => 'sx-crm-actions__more', 'data-sx-crm-actions-menu' => true, 'data-sx-crm-actions-more' => true, 'hidden' => !$more]
            );
        }

        $body = '';
        if ($this->commentUrl !== null) {
            $body = Html::tag('div',
                Html::button(Html::encode($this->notePlaceholder), ['class' => 'sx-crm-actions__placeholder', 'data-sx-crm-actions-open' => true])
                .Html::tag('div', CmsCommentWidget::widget([
                    'model' => $this->model,
                    'backend_url' => $this->commentUrl,
                    'isShowPin' => $this->isShowPin,
                    'placeholder' => $this->notePlaceholder,
                    'isShowCancel' => true,
                ]), ['class' => 'sx-crm-actions__form', 'hidden' => true]),
                ['class' => 'sx-crm-actions__body']
            );
        }

        return Html::tag('div',
            Html::tag('div', $tabs, ['class' => 'sx-crm-actions__tabs', 'role' => 'toolbar', 'aria-label' => 'Действия'])
            .$body,
            ['id' => $this->getId(), 'class' => $this->framed ? 'sx-surface sx-surface--raised sx-crm-actions' : 'sx-crm-actions sx-crm-actions--plain', 'data-sx-crm-actions' => true]
        );
    }

    /** A tab of the row; a tab with `items` opens the choice under it. */
    protected function renderTab(string $id, array $item): string
    {
        if (empty($item['items'])) {
            return $this->renderItem($id, $item, 'sx-crm-actions__tab', ['data-sx-crm-actions-fit' => $id]);
        }
        $choice = '';
        foreach ($item['items'] as $subId => $sub) {
            $choice .= $this->renderItem($id.'.'.$subId, ['icon' => null] + $sub, 'sx-crm-actions__menu-item', ['role' => 'menuitem']);
        }

        return Html::tag('details',
            Html::tag('summary', (!empty($item['icon']) ? $this->icon((string)$item['icon']) : '')
                .Html::tag('span', Html::encode($item['label'])).$this->icon('chevron-down'), ['class' => 'sx-crm-actions__tab'])
            .Html::tag('div', $choice, ['class' => 'sx-crm-actions__menu', 'role' => 'menu']),
            ['class' => 'sx-crm-actions__more', 'data-sx-crm-actions-menu' => true, 'data-sx-crm-actions-fit' => $id]
        );
    }

    /** Entries of "Ещё": a tab, or each item of its choice as "Позвонить: Рабочий, +7 …". */
    protected function renderMenuItems(string $id, array $item, array $options = []): string
    {
        $options['role'] = 'menuitem';
        if (empty($item['items'])) {
            return $this->renderItem($id, $item, 'sx-crm-actions__menu-item', $options);
        }
        $html = '';
        foreach ($item['items'] as $subId => $sub) {
            $sub['label'] = $item['label'].': '.$sub['label'];
            $html .= $this->renderItem($id.'.'.$subId, ['icon' => $item['icon'] ?? null] + $sub, 'sx-crm-actions__menu-item', $options);
        }

        return $html;
    }

    /**
     * "Позвонить" and "Письмо" of a card with its phones and emails (models
     * with `name` and `value`), as the call and letter buttons of the contact
     * rows ({@see CrmContactBlockWidget::phoneItems()}): a call through
     * `sx-telephony-btn`, a letter in the CRM mail window when a package gives
     * one for the address ({@see CrmContactBlockWidget::EVENT_CONTACT_ACTIONS}),
     * else `mailto:`. Several phones or emails give a choice under the tab.
     *
     * @param array $call extra attributes of the call buttons (data-lead-id)
     * @return array[] id => tab for {@see $actions}
     */
    public static function contactTabs($owner, iterable $phones, iterable $emails, array $call = []): array
    {
        $choice = static function ($contact): string {
            $name = trim((string)$contact->name);

            return ($name !== '' ? $name.', ' : '').$contact->value;
        };
        $tabs = [];
        foreach ($phones as $phone) {
            $value = trim((string)$phone->value);
            if ($value !== '') {
                $tabs['call']['items'][] = ['label' => $choice($phone), 'options' => array_merge([
                    'class' => 'sx-telephony-btn',
                    'data-value' => $value,
                    'title' => 'Позвонить на '.$value,
                ], $call)];
            }
        }
        foreach ($emails as $email) {
            $value = trim((string)$email->value);
            if ($value === '') {
                continue;
            }
            $html = CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_EMAIL, $value, $owner, []);
            $onclick = preg_match('/onclick="([^"]+)"/', $html, $match) ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5) : null;
            $tabs['mail']['items'][] = ['label' => $choice($email), 'options' => [
                'onclick' => $onclick ?: 'window.location.href = '.Json::encode('mailto:'.$value).'; return false;',
                'title' => 'Письмо на '.$value,
            ]];
        }
        if (isset($tabs['call'])) {
            $tabs['call'] += ['label' => 'Позвонить', 'icon' => 'phone'];
        }
        if (isset($tabs['mail'])) {
            $tabs['mail'] += ['label' => 'Письмо', 'icon' => 'mail'];
        }

        return $tabs;
    }

    protected function renderItem(string $id, array $item, string $class, array $options = []): string
    {
        $label = (!empty($item['icon']) ? $this->icon((string)$item['icon']) : '').Html::tag('span', Html::encode($item['label']));
        $options['data-sx-crm-action'] = $id;
        $url = trim((string)($item['url'] ?? ''));
        if ($url !== '') {
            $action = Json::encode(array_merge(['isOpenNewWindow' => true, 'url' => $url], (array)($item['window'] ?? [])));

            return Html::a($label, $url, array_merge($options, [
                'class' => $class,
                'data-pjax' => '0',
                'onclick' => 'new sx.classes.backend.widgets.Action('.$action.').go(); return false;',
            ]));
        }
        $options = array_merge($options, (array)$item['options']);
        Html::addCssClass($options, $class);

        return Html::button($label, $options);
    }

    protected function icon(string $name): string
    {
        return BackendIcon::renderConfigured($name, ['size' => 16]);
    }
}
