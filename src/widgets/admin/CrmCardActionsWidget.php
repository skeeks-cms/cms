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
     * button: onclick, data-*); `more` => true puts the tab into "Ещё".
     *
     * @var array[]
     */
    public array $actions = [];

    /** @var bool add the tabs of packages ({@see CrmCardBlocksWidget::actions()}) */
    public bool $packageActions = true;

    /** @var int action tabs in the row besides "Примечание"; the rest go to "Ещё" */
    public int $rowLimit = 6;

    public function run()
    {
        $items = array_filter($this->actions, static fn($a) => is_array($a) && trim((string)($a['label'] ?? '')) !== ''
            && (trim((string)($a['url'] ?? '')) !== '' || !empty($a['options'])));
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
            $tabs .= $this->renderItem((string)$id, $item, 'sx-crm-actions__tab', ['data-sx-crm-actions-fit' => true]);
            $menu .= $this->renderItem((string)$id, $item, 'sx-crm-actions__menu-item', ['role' => 'menuitem', 'hidden' => true, 'data-sx-crm-actions-copy' => true]);
        }
        foreach ($more as $id => $item) {
            $menu .= $this->renderItem((string)$id, $item, 'sx-crm-actions__menu-item', ['role' => 'menuitem']);
        }
        if ($menu !== '') {
            $tabs .= Html::tag('details',
                Html::tag('summary', Html::tag('span', 'Ещё').$this->icon('chevron-down'), ['class' => 'sx-crm-actions__tab'])
                .Html::tag('div', $menu, ['class' => 'sx-crm-actions__menu', 'role' => 'menu']),
                ['class' => 'sx-crm-actions__more', 'data-sx-crm-actions-menu' => true, 'hidden' => !$more]
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
            ['id' => $this->getId(), 'class' => 'sx-surface sx-surface--raised sx-crm-actions', 'data-sx-crm-actions' => true]
        );
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
