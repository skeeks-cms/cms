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
     * plain tab unless `choice` => true; `choiceTitle` heads the choice);
     * `more` => true puts the tab into "Ещё".
     * {@see contactTabs()} builds "Позвонить" and "Письмо" of a card.
     *
     * @var array[]
     */
    public array $actions = [];

    /** @var bool add the tabs of packages ({@see CrmCardBlocksWidget::actions()}) */
    public bool $packageActions = true;

    /** @var int action tabs in the row besides "Примечание"; the rest go to "Ещё" */
    public int $rowLimit = 6;

    /**
     * The note of an employee who works with the client only through his
     * deals ({@see ownDeals()}): it goes to the feed of his deal — one is
     * taken, several are chosen over the editor, none offers "Создать сделку".
     * Null — the note goes to {@see $commentUrl} of the card record.
     *
     * @var \skeeks\cms\models\CmsLead[]|null
     */
    public ?array $noteDeals = null;

    /** @var string|null a muted line under the tabs: whose deal the actions go to */
    public ?string $dealHint = null;

    /** @var string|null the create window of a deal with the client chosen (no deals of {@see $noteDeals}) */
    public ?string $createDealUrl = null;

    /**
     * The block of a company or a client card in one line: the note, "Задача"
     * and "Сделка" with the record chosen, "Позвонить" and "Письмо"
     * ({@see contactTabs()}), the tabs of packages, and in "Ещё" the records
     * of the card tabs ({@see recordTabs()}). For an employee who works with the client only
     * through his deals the block looks the same, and the deal is chosen when
     * an action is taken ({@see dealTabs()}; the records — {@see leadRecordTabs()}).
     *
     * @param \skeeks\cms\models\CmsCompany|\skeeks\cms\models\User $record
     */
    public static function client($record, iterable $phones = [], iterable $emails = []): string
    {
        $phones = is_array($phones) ? $phones : iterator_to_array($phones, false);
        $emails = is_array($emails) ? $emails : iterator_to_array($emails, false);
        $isCompany = $record instanceof \skeeks\cms\models\CmsCompany;
        $deals = self::ownDeals($record);
        if ($deals !== null) {
            $createUrl = self::createDealUrl($record);
            $hint = !$deals ? 'Открытых сделок с клиентом у вас нет: действие начнётся с новой сделки'
                : (count($deals) === 1 ? 'Действия — по сделке '.self::dealLabel(reset($deals)) : 'Действия — по вашим сделкам: сделку выберете при действии');

            return self::widget([
                'model' => $record,
                'commentUrl' => ['/cms/admin-cms-lead/add-comment'],
                'notePlaceholder' => 'Напишите примечание к сделке…',
                'noteDeals' => $deals,
                'createDealUrl' => $createUrl,
                'dealHint' => $hint,
                'packageActions' => false,
                'actions' => self::withLeadTab($record, self::dealTabs($record, $deals, $phones, $emails, $createUrl)),
            ]);
        }
        // An employee card: only the note.
        $isClient = $isCompany || !$record->is_worker;
        $actions = [];
        if ($isClient && \Yii::$app->user->can('cms/admin-task')) {
            $actions['task'] = self::taskTab(['CmsTask' => [$isCompany ? 'cms_company_id' : 'cms_user_id' => (int)$record->id]]);
        }
        if ($isClient) {
            $actions = self::withLeadTab($record, $actions + self::contactTabs($record, $phones, $emails)) + self::recordTabs($record);
        }

        return self::widget(['model' => $record, 'commentUrl' => ['/cms/admin-cms-log/add-comment'], 'actions' => $actions]);
    }

    /** The create window of a deal with the client chosen; after the save the deal card opens (no `noa`). */
    protected static function createDealUrl($record): string
    {
        return (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
            '/cms/admin-cms-lead/create',
            'CmsLead' => [$record instanceof \skeeks\cms\models\CmsCompany ? 'cms_company_id' : 'cms_user_id' => (int)$record->id],
        ])->enableEmptyLayout()->url;
    }

    /** @var array[] "class:id" => the actions of recordActions() in this request */
    private static array $_recordActions = [];

    /**
     * The create actions of a card for the button «Новая …» over its list
     * tabs (\skeeks\cms\crm\CrmCardTabs) — the same as the block, so a tab and
     * the block never differ: a company or a person client — "lead", "task",
     * "service", "bill", "payment", "document" ({@see recordTabs()}; on
     * «Свои сделки» and "edit" through his open deals, {@see dealTabs()}), a
     * person also "bonus"; a deal — "task" and {@see leadRecordTabs()} for who
     * works with it (CrmAccess::isLeadWorker()).
     *
     * @param \skeeks\cms\models\CmsCompany|\skeeks\cms\models\User|\skeeks\cms\models\CmsLead $record
     */
    public static function recordActions($record): array
    {
        $key = (int)\Yii::$app->user->id.':'.get_class($record).':'.(int)$record->id;
        if (array_key_exists($key, self::$_recordActions)) {
            return self::$_recordActions[$key];
        }
        $actions = [];
        $canTask = \Yii::$app->user->can('cms/admin-task');
        if ($record instanceof \skeeks\cms\models\CmsLead) {
            if (!\Yii::$app->user->isGuest && \skeeks\cms\crm\CrmAccess::instance()->isLeadWorker(\Yii::$app->user->identity, $record)) {
                $actions = ($canTask ? ['task' => self::taskTab(['CmsTask' => ['cms_lead_id' => (int)$record->id]])] : [])
                    + self::leadRecordTabs($record);
            }
        } elseif ($record instanceof \skeeks\cms\models\CmsCompany || !$record->is_worker) {
            $deals = self::ownDeals($record);
            if ($deals !== null) {
                $actions = self::withLeadTab($record, self::dealTabs($record, $deals, [], [], self::createDealUrl($record)));
            } else {
                $actions = self::withLeadTab($record, $canTask
                    ? ['task' => self::taskTab(['CmsTask' => [$record instanceof \skeeks\cms\models\CmsCompany ? 'cms_company_id' : 'cms_user_id' => (int)$record->id]])]
                    : []) + self::recordTabs($record);
                if (!$record instanceof \skeeks\cms\models\CmsCompany) {
                    $actions['bonus'] = self::createTab('Бонусы', '', '/shop/admin-bonus-transaction', ['ShopBonusTransaction' => ['cms_user_id' => (int)$record->id]]);
                }
            }
        }

        return self::$_recordActions[$key] = array_filter($actions);
    }

    /**
     * Open deals of the employee by a company or a client when he acts on it
     * only through his deals ("Свои сделки", "edit"): the server refuses such
     * actions without one of them (CrmAccess::canActOnClient()), the list is
     * CrmAccess::ownOpenLeads() — the same rule as the feed and the checks.
     * Null — he acts on the client itself (or it is an employee card): the
     * usual block.
     *
     * @return \skeeks\cms\models\CmsLead[]|null
     */
    public static function ownDeals($record): ?array
    {
        if (!\Yii::$app->has('user') || \Yii::$app->user->isGuest) {
            return null;
        }
        $access = \skeeks\cms\crm\CrmAccess::instance();
        $user = \Yii::$app->user->identity;
        if (\skeeks\cms\crm\CrmAccess::clientRecordOf($record) === null || $access->canActOnClient($user, $record)) {
            return null;
        }

        return $access->ownOpenLeads($user, $record)
            ->orderBy([\skeeks\cms\models\CmsLead::tableName().'.id' => SORT_DESC])
            ->limit(20)
            ->all();
    }

    /** "№927 · Ромашка" */
    protected static function dealLabel($lead): string
    {
        return '№'.(int)$lead->id.($lead->name ? ' · '.$lead->name : '');
    }

    /**
     * The usual tabs of a client card for an employee who works only through
     * his deals: each action is that of a deal (as in the deal card: a task of
     * the deal, a call with data-lead-id, a letter in the context of the deal,
     * package tabs of the deal). One deal — the tab acts at once; several —
     * the tab opens "Выберите сделку"; none — the tab offers "Создать сделку".
     *
     * @param \skeeks\cms\models\CmsLead[] $deals
     */
    protected static function dealTabs($record, array $deals, array $phones, array $emails, string $createUrl): array
    {
        $canTask = \Yii::$app->user->can('cms/admin-task');
        $tabsOf = static function ($owner, array $call) use ($canTask, $phones, $emails, $record): array {
            $tabs = [];
            if ($canTask) {
                $tabs['task'] = self::taskTab(['CmsTask' => $owner === $record
                    ? [$record instanceof \skeeks\cms\models\CmsCompany ? 'cms_company_id' : 'cms_user_id' => (int)$record->id]
                    : ['cms_lead_id' => (int)$owner->id]]);
            }

            return $tabs + self::contactTabs($owner, $phones, $emails, $call) + CrmCardBlocksWidget::actions($owner)
                + ($owner === $record ? [] : self::leadRecordTabs($owner));
        };

        if (!$deals) {
            // The same tabs; each offers to create the deal first.
            $tabs = [];
            foreach ($tabsOf($record, []) as $id => $tab) {
                $tabs[$id] = ['label' => $tab['label'], 'icon' => $tab['icon'] ?? null, 'choice' => true,
                    'choiceTitle' => 'Нет открытой сделки с клиентом',
                    'items' => [['label' => 'Создать сделку', 'url' => $createUrl]]];
            }

            return $tabs;
        }

        $single = count($deals) === 1;
        $tabs = [];
        foreach ($deals as $lead) {
            foreach ($tabsOf($lead, ['data-lead-id' => (int)$lead->id]) as $id => $tab) {
                // A payment is of the client (tied to a deal through its bill): once, without the choice of a deal.
                if (!empty($tab['ofClient'])) {
                    $tabs[$id] = $tabs[$id] ?? $tab;
                    continue;
                }
                $tabs[$id] = $tabs[$id] ?? ['label' => $tab['label'], 'icon' => $tab['icon'] ?? null, 'items' => [],
                    'choiceTitle' => $single ? null : 'Выберите сделку'] + array_intersect_key($tab, ['more' => 1]);
                $subs = !empty($tab['items']) ? $tab['items'] : [array_intersect_key($tab, ['url' => 1, 'options' => 1, 'window' => 1]) + ['label' => '']];
                foreach ($subs as $sub) {
                    $sub['label'] = $single ? ($sub['label'] !== '' ? $sub['label'] : $tab['label'])
                        : self::dealLabel($lead).($sub['label'] !== '' ? ' — '.$sub['label'] : '');
                    $tabs[$id]['items'][] = $sub;
                }
            }
        }

        return $tabs;
    }

    /**
     * "Сделка" after "Задача": the create window of a deal with the client
     * chosen; after the save the deal card opens (no `noa`). It is also the
     * only record tab of "Свои сделки" and "edit": a new deal is their own.
     */
    protected static function withLeadTab($record, array $tabs): array
    {
        if (!\Yii::$app->user->can('cms/admin-lead') || !($tab = self::createTab('Сделка', 'handshake', '/cms/admin-cms-lead',
                ['CmsLead' => [$record instanceof \skeeks\cms\models\CmsCompany ? 'cms_company_id' : 'cms_user_id' => (int)$record->id]], [], false))) {
            return $tabs;
        }
        $at = array_search('task', array_keys($tabs), true);
        $at = $at === false ? 0 : $at + 1;

        return array_slice($tabs, 0, $at, true) + ['lead' => $tab] + array_slice($tabs, $at, null, true);
    }

    /**
     * The records of the card tabs that the client itself owns — "Услуга",
     * "Счёт", "Платёж", "Документ" — in "Ещё", each opening the create form of
     * that tab with the client chosen. Shown under the same rule as the card
     * tab (cms/admin-company/manage, cms/admin-user/manage) and the create
     * action of its controller. "Свои сделки" and "edit" get the records of
     * their deals instead ({@see leadRecordTabs()} in {@see dealTabs()}).
     */
    protected static function recordTabs($record): array
    {
        $isCompany = $record instanceof \skeeks\cms\models\CmsCompany;
        if (!\Yii::$app->user->can($isCompany ? 'cms/admin-company/manage' : 'cms/admin-user/manage', ['model' => $record])) {
            return [];
        }
        $id = (int)$record->id;
        $more = ['more' => true];
        $payment = static fn(int $debit) => ['ShopPayment' => [$isCompany ? 'cms_company_id' : 'cms_user_id' => $id, 'is_debit' => $debit]];
        $tabs = [];
        if ($isCompany) {
            $tabs['service'] = self::createTab('Услуга', 'tools', '/cms/admin-cms-deal', ['CmsDeal' => ['cms_company_id' => $id]], $more);
            $tabs['bill'] = self::createTab('Счёт', 'invoice', '/cms/admin-cms-bill', ['ShopBill' => ['cms_company_id' => $id]], $more);
        } else {
            // The CRM bills: /shop/admin-bill is the read-only list of the order bills.
            $tabs['bill'] = self::createTab('Счёт', 'invoice', '/cms/admin-cms-bill', ['ShopBill' => ['cms_user_id' => $id]], $more);
        }
        $in = self::createTab('Заплатили нам', '', '/shop/admin-payment', $payment(1));
        $out = self::createTab('Мы заплатили', '', '/shop/admin-payment', $payment(0));
        if ($in && $out) {
            $tabs['payment'] = ['label' => 'Платёж', 'icon' => 'credit-card', 'items' => [$in, $out]] + $more;
        }
        if ($isCompany && self::canCreate('/cms/admin-cms-document')) {
            $items = [];
            foreach (\skeeks\cms\shop\models\ShopDocument::optionsForType() as $type => $label) {
                $route = $type === \skeeks\cms\shop\models\ShopDocument::TYPE_RECONCILIATION_ACT
                    ? ['/cms/admin-cms-document/create-reconciliation', 'company_id' => $id]
                    : ['/cms/admin-cms-document/create', 'type' => $type, 'cms_company_id' => $id];
                $items[] = ['label' => $label, 'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams($route)
                    ->enableEmptyLayout()->enableNoActions()->url];
            }
            $tabs['document'] = ['label' => 'Документ', 'icon' => 'file-text', 'items' => $items] + $more;
        }

        return array_filter($tabs);
    }

    /**
     * The records of a deal (owner's decision 29.10.2026, «Полноценная работа в
     * рамках своих сделок») in "Ещё": "Услуга", "Счёт", "Документ" with the deal
     * chosen (`<Form>[cms_lead_id]`, the form takes its client:
     * LeadLinkField::applyLeadClient()) and "Платёж" of its client — a payment
     * is tied to the deal through its bill (`ofClient`). Each needs the deal
     * column (CrmAccess::leadLinkReady()) and the create action of its section;
     * none when the server refuses the deal (CrmAccess::canActOnClient(): on
     * «Свои сделки» and "edit" only an own open deal). The reconciliation act is
     * of the company, not of a deal: only for who acts on the company itself.
     */
    public static function leadRecordTabs(\skeeks\cms\models\CmsLead $lead): array
    {
        if (!\Yii::$app->has('user') || \Yii::$app->user->isGuest) {
            return [];
        }
        $access = \skeeks\cms\crm\CrmAccess::instance();
        $user = \Yii::$app->user->identity;
        // These are records of the client: a deal without one (not sorted yet) has none.
        $client = $lead->cms_company_id ? $lead->company : ($lead->cms_user_id ? $lead->client : null);
        if (!$client || !$access->canActOnClient($user, $client, (int)$lead->id)) {
            return [];
        }
        $id = (int)$lead->id;
        $more = ['more' => true];
        $tabs = [];
        if (\skeeks\cms\crm\CrmAccess::leadLinkReady('cms_deal')) {
            $tabs['service'] = self::createTab('Услуга', 'tools', '/cms/admin-cms-deal', ['CmsDeal' => ['cms_lead_id' => $id]], $more);
        }
        if (\skeeks\cms\crm\CrmAccess::leadLinkReady('shop_bill')) {
            $tabs['bill'] = self::createTab('Счёт', 'invoice', '/cms/admin-cms-bill', ['ShopBill' => ['cms_lead_id' => $id]], $more);
        }
        $payment = static fn(int $debit) => ['ShopPayment' => [$lead->cms_company_id ? 'cms_company_id' : 'cms_user_id' => (int)$client->id, 'is_debit' => $debit]];
        $in = self::createTab('Заплатили нам', '', '/shop/admin-payment', $payment(1));
        $out = self::createTab('Мы заплатили', '', '/shop/admin-payment', $payment(0));
        if ($in && $out) {
            $tabs['payment'] = ['label' => 'Платёж', 'icon' => 'credit-card', 'items' => [$in, $out], 'ofClient' => true] + $more;
        }
        if (\skeeks\cms\crm\CrmAccess::leadLinkReady('shop_document') && self::canCreate('/cms/admin-cms-document')) {
            $items = [];
            foreach (\skeeks\cms\shop\models\ShopDocument::optionsForType() as $type => $label) {
                if ($type === \skeeks\cms\shop\models\ShopDocument::TYPE_RECONCILIATION_ACT) {
                    if (!$lead->cms_company_id || !$access->canActOnClient($user, $client)) {
                        continue;
                    }
                    $route = ['/cms/admin-cms-document/create-reconciliation', 'company_id' => (int)$lead->cms_company_id];
                } else {
                    $route = ['/cms/admin-cms-document/create', 'type' => $type, 'ShopDocument' => ['cms_lead_id' => $id]];
                }
                $items[] = ['label' => $label, 'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams($route)
                    ->enableEmptyLayout()->enableNoActions()->url];
            }
            if ($items) {
                $tabs['document'] = ['label' => 'Документ', 'icon' => 'file-text', 'items' => $items] + $more;
            }
        }

        return array_filter($tabs);
    }

    /**
     * A tab opening the create form of a backend controller ($controllerRoute,
     * e.g. /cms/admin-cms-bill) with GET parameters; null when its create
     * action is not allowed to the employee ({@see canCreate()}).
     */
    protected static function createTab(string $label, string $icon, string $controllerRoute, array $params, array $extra = [], bool $noActions = true): ?array
    {
        if (!self::canCreate($controllerRoute)) {
            return null;
        }
        $url = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(array_merge([$controllerRoute.'/create'], $params))->enableEmptyLayout();
        if ($noActions) {
            $url->enableNoActions();
        }

        return array_filter(['label' => $label, 'icon' => $icon]) + ['url' => (string)$url->url] + $extra;
    }

    /** @var bool[] controller route => its create action is allowed to the current employee */
    private static array $_canCreate = [];

    /** The create action of a backend controller is allowed: its permissions and accessCallback decide. */
    protected static function canCreate(string $controllerRoute): bool
    {
        $key = (int)\Yii::$app->user->id.':'.$controllerRoute;
        if (!array_key_exists($key, self::$_canCreate)) {
            $allowed = false;
            try {
                $result = \Yii::$app->createController(ltrim($controllerRoute, '/'));
                $controller = $result ? $result[0] : null;
                $create = $controller ? $controller->createAction('create') : null;
                $allowed = $create
                    && (!$controller instanceof \skeeks\cms\IHasPermissions || $controller->isAllow)
                    && (!$create instanceof \skeeks\cms\IHasPermissions || $create->isAllow);
            } catch (\Throwable $e) {
                \Yii::warning('CrmCardActionsWidget: '.$controllerRoute.': '.$e->getMessage(), self::class);
            }
            self::$_canCreate[$key] = (bool)$allowed;
        }

        return self::$_canCreate[$key];
    }

    /** "Задача": the create window of a task with the given GET parameters (CmsTask[...]). */
    protected static function taskTab(array $params): array
    {
        return ['label' => 'Задача', 'icon' => 'plus', 'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(
            array_merge(['/cms/admin-cms-task/create'], $params)
        )->enableEmptyLayout()->enableNoActions()->url];
    }

    /** A backend window over the card. */
    public static function windowOnClick(string $url): string
    {
        return 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $url]).').go(); return false;';
    }

    /**
     * The note form under the tabs: of the card record, or of the deal of
     * {@see $noteDeals} (a select of the deals over the editor; without deals
     * "Создать сделку" instead of the editor).
     */
    protected function renderNoteForm(): string
    {
        $form = static fn($model, $url, $placeholder, $pin) => CmsCommentWidget::widget([
            'model' => $model,
            'backend_url' => $url,
            'isShowPin' => $pin,
            'placeholder' => $placeholder,
            'isShowCancel' => true,
        ]);
        if ($this->noteDeals === null) {
            return $form($this->model, $this->commentUrl, $this->notePlaceholder, $this->isShowPin);
        }
        if (!$this->noteDeals) {
            return Html::tag('div',
                Html::tag('span', 'Примечание пишется в ленту вашей сделки, а открытых сделок с клиентом нет.', ['class' => 'sx-crm-actions__deals-text'])
                .Html::a($this->icon('plus').Html::tag('span', 'Создать сделку'), (string)$this->createDealUrl, [
                    'class' => 'sx-button sx-button--primary sx-button--sm',
                    'data-pjax' => '0',
                    'onclick' => self::windowOnClick((string)$this->createDealUrl),
                ])
                .Html::button('Отмена', ['class' => 'sx-button sx-button--secondary sx-button--sm', 'data-sx-comment-cancel' => true]),
                ['class' => 'sx-crm-actions__deals-empty']
            );
        }
        $choices = [];
        $groups = '';
        foreach (array_values($this->noteDeals) as $i => $lead) {
            $choices[(int)$lead->id] = self::dealLabel($lead);
            $groups .= Html::tag('div', $form($lead, ['/cms/admin-cms-lead/add-comment', 'pk' => (int)$lead->id], $this->notePlaceholder, $this->isShowPin),
                ['data-sx-crm-actions-deal' => (int)$lead->id, 'hidden' => $i > 0]);
        }
        $head = count($choices) === 1
            ? Html::tag('span', 'В ленту сделки '.Html::encode(reset($choices)), ['class' => 'sx-crm-actions__deals-text'])
            : Html::label('В ленту сделки', $this->getId().'-deal', ['class' => 'sx-crm-actions__deals-text']).' '
                .Html::dropDownList('', null, $choices, ['id' => $this->getId().'-deal', 'class' => 'sx-crm-actions__deals-select', 'data-sx-crm-actions-deal-select' => true]);

        return Html::tag('div', $head, ['class' => 'sx-crm-actions__deals-head']).$groups;
    }

    public function run()
    {
        $items = [];
        foreach ($this->actions as $id => $action) {
            if (!is_array($action) || trim((string)($action['label'] ?? '')) === '') {
                continue;
            }
            // A choice of one is a plain tab with the label of the tab.
            if (!empty($action['items']) && count($action['items']) === 1 && empty($action['choice'])) {
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
                .Html::tag('div', $this->renderNoteForm(), ['class' => 'sx-crm-actions__form', 'hidden' => true]),
                ['class' => 'sx-crm-actions__body']
            );
        }

        return Html::tag('div',
            Html::tag('div', $tabs, ['class' => 'sx-crm-actions__tabs', 'role' => 'toolbar', 'aria-label' => 'Действия'])
            .($this->dealHint !== null ? Html::tag('div', Html::encode($this->dealHint), ['class' => 'sx-crm-actions__hint']) : '')
            .$body,
            ['id' => $this->getId(), 'class' => 'sx-surface sx-surface--raised sx-crm-actions', 'data-sx-crm-actions' => true]
        );
    }

    /** A tab of the row; a tab with `items` opens the choice under it. */
    protected function renderTab(string $id, array $item): string
    {
        if (empty($item['items'])) {
            return $this->renderItem($id, $item, 'sx-crm-actions__tab', ['data-sx-crm-actions-fit' => $id]);
        }
        $choice = !empty($item['choiceTitle']) ? Html::tag('div', Html::encode($item['choiceTitle']), ['class' => 'sx-crm-actions__menu-title']) : '';
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
