# Backend model controllers and collection UI

## Contents

- [Source of truth](#source-of-truth)
- [Implementation workflow](#implementation-workflow)
- [Index action settings](#index-action-settings)
- [Presentation modes](#presentation-modes)
- [Page header and actions](#page-header-and-actions)
- [Empty and no-results states](#empty-and-no-results-states)
- [Adaptive filters](#adaptive-filters)
- [Grid renderer](#grid-renderer)
- [List/items renderer](#listitems-renderer)
- [Theme contract](#theme-contract)
- [Verification matrix](#verification-matrix)

## Source of truth

Inspect the installed package code before relying on this reference:

- `skeeks/cms-backend/src/controllers/BackendModelStandartController.php`;
- `skeeks/cms-backend/src/actions/BackendGridModelAction.php`;
- `skeeks/cms-backend/src/widgets/GridViewWidget.php`;
- `skeeks/cms-backend/src/widgets/ListViewWidget.php`;
- `skeeks/cms-backend/src/widgets/EmptyStateWidget.php`;
- `skeeks/cms-backend/src/assets/BackendUiAsset.php`;
- `skeeks/cms-backend/src/assets/src/theme.css`;
- `skeeks/cms-backend/src/assets/src/backend.css`.

Use `ast-index` first for symbols and usages. Treat this document as the
intended contract, but let current executable code win when they differ.

`BackendModelStandartController` registers `index`, `create`, `update`,
`delete` and `delete-multi`. Configure the collection page under
`actions()['index']`; do not duplicate CRUD plumbing in each cabinet.

## Implementation workflow

1. Inspect the controller action set, permissions, opening behavior and current
   `grid` and `filters` callbacks.
2. Preserve `legacy` presentation unless the controller explicitly opts in.
3. Reuse standard backend actions through `backendAction`; do not reconstruct
   modal, drawer, page or new-window JavaScript by hand.
4. Keep collection state independent of the renderer. A table and a div/items
   list must share page header, filters, empty state and no-results behavior.
5. Put reusable markup, JS and geometry in `skeeks/cms-backend`. Expose colors,
   surfaces, borders, radii and shadows as semantic CSS variables.
6. Keep project CSS focused on variable values and exceptional project layout.
7. Verify empty, small, large and filtered collections with regular and manager
   users. Verify a multi-action admin controller separately.

## Index action settings

Configure these custom `BackendGridModelAction` properties:

| Setting | Default | Purpose |
| --- | --- | --- |
| `presentationMode` | `legacy` | Select legacy, page, tabs or automatic presentation |
| `navigationActionIds` | `null` | Preserve, hide or restrict upper controller actions |
| `pageHeader` | `null` | Configure H1, description, icon and right-side actions |
| `emptyState` | rich default | Present an empty collection |
| `noResultsState` | rich default | Present an empty filtered result |
| `hideFiltersOnSmallLists` | `true` | Hide unnecessary filters for regular users |
| `smallListLimit` | `5` | Define the small-collection threshold |
| `alwaysShowFiltersForManagers` | `true` | Preserve tools for showing managers |
| `grid` | GridView config | Configure the renderer and data provider |
| `filters` | FiltersWidget config | Configure search and structured filters |
| `isStandartAjaxPager` | `true` | Enable the historical table AJAX pager |

Inherited backend-action settings include `name`, `icon`, `priority`,
`isVisible`, `accessCallback`, `permissionName`, `generateAccess`,
`isOpenNewWindow`, `isDisplayBackendShowings`, `backendShowingKey`,
`backendShowingParam`, `configKey`, `callback`, `defaultView` and `size`.

## Presentation modes

Use one of:

- `legacy`: preserve the historical action navigation; this is the safe
  default for existing administration;
- `page`: render a page header, hide upper action navigation and move `create`
  into the header by default;
- `tabs`: keep visible controller actions as section navigation;
- `auto`: choose `page` for an `index`/`create` controller and `tabs` when
  other visible actions such as calendar or report exist.

```php
'index' => [
    'presentationMode' => 'auto',
],
```

Override navigation explicitly when automatic behavior is unsuitable:

```php
'navigationActionIds' => false,

'navigationActionIds' => [
    'index',
    'calendar',
    'report',
],
```

`null` preserves mode-driven behavior, `false` hides navigation, and an array
keeps only the listed action IDs.

## Page header and actions

Supported `pageHeader` keys:

- `title`: H1; falls back to the controller or action name;
- `description`: supporting copy;
- `icon`: Font Awesome class;
- `options`: HTML attributes for the header;
- `action`: legacy single-action form;
- `actions`: ordered action list.

Set `pageHeader` to `false` to disable it. In `page` mode, omitting both
`action` and `actions` automatically uses visible `create`. Set `actions` to
`false` to render no header actions.

```php
'pageHeader' => [
    'title' => 'Задачи и поддержка',
    'description' => 'Следите за задачами и общайтесь с командой.',
    'actions' => [
        [
            'backendAction' => 'create',
            'label' => 'Новая задача',
        ],
        [
            'backendAction' => 'report',
            'label' => 'Отчёт',
            'variant' => 'secondary',
        ],
    ],
],
```

An action supports:

- `backendAction`: standard controller action ID;
- `label`, `icon`, `url`;
- `variant`: built-in `primary` or `secondary`;
- `options`: HTML link attributes.

Prefer `backendAction` over a manual URL. It preserves visibility, URL,
`isOpenNewWindow` and the standard `sx.classes.backend.widgets.Action`
opening behavior. A string entry such as `'create'` is shorthand for
`['backendAction' => 'create']`.

## Empty and no-results states

`emptyState` applies when the unfiltered collection has no records.
`noResultsState` applies when active filters produce no records.

Both accept:

- `title`, `description`, `icon`;
- `options`: state container attributes;
- `action`: `label`, `url`, `icon`, `variant`, `options` and
  `backendAction`.

```php
'emptyState' => [
    'title' => 'Задач пока нет',
    'description' => 'Опишите вопрос или создайте первую задачу.',
    'icon' => 'far fa-comment-dots',
    'action' => [
        'backendAction' => 'create',
        'label' => 'Создать задачу',
    ],
],
'noResultsState' => [
    'title' => 'Задачи не найдены',
    'description' => 'Измените запрос или выбранные фильтры.',
    'icon' => 'fa fa-search',
],
```

When `emptyState.action` is omitted, visible `create` is used automatically.
Set a state to `false` to use the renderer's historical empty output.

## Adaptive filters

The default policy is:

- show filters when an active filter is present;
- show filters when the total collection exceeds `smallListLimit`;
- hide filters for a regular user when the collection is small;
- always show filters to users who can manage backend showings when
  `alwaysShowFiltersForManagers` is enabled.

Use:

```php
'hideFiltersOnSmallLists' => true,
'smallListLimit' => 5,
'alwaysShowFiltersForManagers' => true,
```

Set `hideFiltersOnSmallLists` to `false` to always show configured filters.
Set `filters` to `false` to disable them completely.

Common `filters` keys include `class`, `visibleFilters`, `isOpened`,
`filtersModel`, `activeForm` and `configBehaviorData`.
`filtersModel` commonly contains `formName`, `rules`, `attributeDefines` and
`fields`. Keep query mutation in each field's `on apply` callback or the
renderer/data-provider initialization callback.

## Grid renderer

The default renderer is `GridViewWidget`. Important settings include:

- `dataProvider`, `modelClassName`;
- `presentation`: set `client` for a customer-oriented table while retaining
  GridView semantics and behavior;
- `columns`, `visibleColumns`, `autoColumns`, `disableAutoColumns`;
- `columnConfigCallback`;
- `defaultOrder`, `sortAttributes`;
- `defaultPageSize`, `pageParam`, `pageSizeParam`,
  `pageSizeLimitMin`, `pageSizeLimitMax`;
- `tableOptions`, `rowOptions`, `options`, `layout`, `pager`, `summary`,
  `showHeader`, `emptyText`;
- `configBehaviorData`, `contextData`;
- `exportParam`, `exportFileName`;
- `beforeTableLeft`, `beforeTableRight`, `afterTableLeft`,
  `afterTableRight`;
- `collectionToolbarLeft`, `collectionToolbarRight`;
- `emptyState`;
- normal `yii\grid\GridView` properties and events.

Use `on init` when the controller must constrain or extend the generated query:

```php
'grid' => [
    'on init' => function (\yii\base\Event $event) {
        $query = $event->sender->dataProvider->query;
        $query->andWhere(['cms_site_id' => $this->cmsSite->id]);
    },
    'defaultPageSize' => 20,
    'defaultOrder' => ['created_at' => SORT_DESC],
],
```

Do not move table-only columns, exports or bulk-selection controls into the
shared collection-state API.

For customer services and tasks, prefer a real Grid when records have stable
comparable fields. Set `presentation => 'client'` and build the first column
with `sx-collection-cell--entity`, a shared `__media` marker and stacked
primary/secondary copy. Compact metric cells use
`sx-collection-cell--metric`; when `showHeader` is false, each metric must
carry its own short secondary label. This keeps the row understandable
without making it look like a dense administration table.

If a route previously stored a materially different Grid configuration,
version the action `configKey` rather than deleting saved
`BackendShowing` records. This gives the new presentation safe defaults while
still allowing showing managers to customize and save it.

## List/items renderer

Select the div/items renderer explicitly:

```php
use skeeks\cms\backend\widgets\ListViewWidget;

'grid' => [
    'class' => ListViewWidget::class,
    'itemView' => function ($model, $key, $index, $widget) {
        return $this->renderPartial('_item', ['model' => $model]);
    },
    'itemOptions' => ['class' => 'my-list-item'],
    'defaultPageSize' => 20,
    'defaultOrder' => ['created_at' => SORT_DESC],
],
```

Supported collection settings include `modelClassName`, `dataProvider`,
`itemView`, `itemOptions`, `viewParams`, `separator`, `layout`, `options`,
`pager`, `summary`, `sorter`, pagination settings, `defaultOrder`,
`sortAttributes`, `collectionToolbarLeft`, `collectionToolbarRight` and
`emptyState`.

The action must not inject table columns, CSV export, fullscreen controls or
bulk-selection UI into `ListViewWidget`. Filters, page header and collection
states remain shared.

Both renderers produce `sx-collection-toolbar`, `sx-collection-body` and
`sx-collection-footer`. The footer owns pager, per-page selector and summary;
do not replace the default layout merely to rearrange those three elements.
`ListViewWidget::dataProvider` may be a provider instance or a callable that
receives the initialized widget and returns one. A callable `itemOptions` must
also remain intact: the widget resolves it first and then appends
`sx-collection-item` and `sx-list-item`.

For a rich empty state, the List renderer keeps its structural body but hides
the toolbar and footer. Verify the footer is actually `display: none` and has
zero height; the static footer wrapper may still be present in the DOM.
Legacy Grid classes remain alongside the semantic classes for compatibility.
Both renderers also guarantee `sx-collection-item` on every row/item without
discarding array or callback options configured by the controller. Use
`sx-collection-item--interactive`, `--selected`, `--disabled`, `--success`,
`--warning`, `--danger` and `--info` for explicit presentation states.
`sx-active`, `aria-selected` and `aria-disabled` remain supported compatibility
or accessibility inputs. Shared metadata/action slots are
`sx-collection-item__relations`, `__date`, `__actions` and `__action`.

## Theme contract

Own the reusable UI contract in `skeeks/cms-backend`:

- `BackendUiAsset` provides neutral semantic tokens and shared button, disabled
  action, surface, status-alert, bare code-block, form-shell, standalone
  `.form-control`, fieldset and legacy neutral-background helper rules;
- `BackendFormAsset` depends on `BackendUiAsset` and owns reusable form,
  validation and composite-control presentation;
- legacy `AdminFormAsset` also depends on `BackendUiAsset` and adapts
  `sx-form-admin` validation, required markers, hints and field hover to the
  same semantic contract;
- `BackendUiAsset` also owns neutral Bootstrap dropdown/modal surfaces and
  overlay tokens, action popovers and date/time picker surfaces;
- `BackendAsset` provides backend collection, grid, list, shared pagination,
  filter and context menu presentation;
- `EmptyStateWidget` is the shared empty presentation for ordinary backend
  pages and collections. It registers `BackendUiAsset` itself and exposes the
  generic `.sx-empty-state` contract while preserving
  `.sx-collection-empty-state` and `.sx-grid-empty-state` aliases;
- `GridViewWidget` registers `BackendAsset` itself so direct widget usage has
  the same contract as `BackendGridModelAction`;
- `BackendAsset` depends on the base `GridViewAsset`. This order is important:
  historical `grid.css` contains fixed white surfaces and must load before the
  theme-aware backend overrides.

Do not move backend component geometry into an application theme. A theme such
as `cms-theme-unify-v2` may provide palette values, a layout adapter and the
theme switcher, but must not become the owner of grid, list, filter or empty
state component rules. Conversely, shell-only elements such as the Unify
header, sidebar and quick-access edge/panel remain owned by that application
theme and consume the same semantic palette.

A site project must not mirror those shared component or Unify shell rules in
its late application stylesheet. It may assign brand and shell values through
root variables and keep exceptional project-only geometry. Even permanent
dark-header contrast, popup shadows, drawer shadows and backdrop colors should
be named variables rather than fixed values repeated in selectors.

The historical `.sx-block` class is a shared compatibility panel, not a
project helper. `BackendUiAsset` owns its padding, margin, text, surface,
border, radius and shadow through `--sx-block-*`, derived from the canonical
surface/panel tokens. Preserve `.sx-block` in old package views, but use
`.sx-surface` or `.sx-panel` with explicit `--padded`/`--clip` modifiers in new
views. Do not keep a late project `.sx-block` shadow or radius.

Compact model-card matrices whose columns have equal roles use
`.sx-data-table-wrapper` and `table.sx-data-table`. This is distinct from the
vertical key/value `.sx-detail-view` contract and from collection/grid tables.
`BackendUiAsset` maps its header, values, borders, links and empty values to
the existing `--sx-detail-view-*` token family. Adjacent fact or description
blocks use `.sx-detail-section` and `.sx-detail-section__title`; meaningful
success state uses `--success`. Do not keep copied view-local `.sx-table`,
`.sx-info-block`, fixed `white`/`#f9f9f9` rules or inline `silver` empty
values. The product price/store/supplier card is the verified reference
consumer in both themes.

Legacy package views may continue to emit `.sx-bg-primary`,
`.sx-bg-secondary` and `.sx-bg-gray-light`. Their neutral background belongs
to `BackendUiAsset` and resolves through `--sx-color-surface-muted`. A layout
whose historical stylesheet loads later may reassert that mapping in its final
semantic adapter, but a site project must not define fixed light values for
those helpers. The `body.sx-empty` page-shell background is layout-specific:
Unify maps it to `--sx-color-canvas`; it is distinct from the reusable
`.sx-empty-state` component.

The historical `.sx-form-fieldset` markup is also a shared compatibility
component. `BackendUiAsset` owns its surface, border, radius, shadow, title and
content palette through `--sx-form-section-*`. `BackendFormAsset` and
`FieldSetAsset` keep only form-specific spacing, collapse behavior and toggle
icons. Do not retain fixed light fieldset shadows, title colors or text shadows
in Unify or project CSS.

`BlockTitleWidget` and the shared `_model_header` are legacy package
components, not layout-theme fragments. Their headings, back link, media,
metadata and semantic success/danger indicators consume the main
`--sx-color-*` and `--sx-form-section-*` families. Keep their structural hooks
in shared vendor code and do not restore inline gray, silver, red or green
colors.

Custom `_model_header.php` views must reuse the shared
`.sx-model-header`, `.sx-model-header__meta` and
`.sx-model-header__actions` hooks instead of copying margin, font-size,
metadata spacing or right-aligned action geometry inline. A small explanatory
icon next to a label uses `.sx-hint-icon`; its muted color and spacing belong
to `BackendUiAsset`, not to a controller view. The reusable
`.sx-quick-access-favorite-btn` geometry, hover and keyboard-focus states also
belong to `BackendUiAsset`; a shell theme may render the quick-access panel but
must not duplicate this button primitive or its palette.

For document-like headers with content on the left and status/actions on the
right, use `.sx-model-header--split`, `.sx-model-header__main`,
`.sx-model-header__side` and `.sx-model-header__status-stack`. Secondary due
date or completion copy uses `.sx-model-header__status-note` and its semantic
modifier; the primary state remains a shared `.sx-status` variant. Compact
button groups use `.sx-model-header__toolbar`, and disabled/inactive model
state inside the title uses `.sx-model-header__state` with a semantic status.
Do not create bill-, document-, contractor- or user-specific copies of this
responsive geometry.

Use semantic inputs instead of project selectors that override vendor geometry:

- `--sx-color-accent`, `--sx-color-accent-hover`,
  `--sx-color-accent-contrast`, `--sx-color-accent-soft`;
- `--sx-color-canvas`, `--sx-color-surface`,
  `--sx-color-surface-raised`, `--sx-color-surface-muted`,
  `--sx-color-surface-hover`;
- `--sx-color-text`, `--sx-color-text-muted`,
  `--sx-color-text-subtle`, `--sx-color-border`;
- `--sx-interactive-surface-*` for default, hover, active, focus, selected and
  disabled states shared by ordinary actionable rows/cards and collection
  items;
- `--sx-radius-control`, `--sx-radius-panel`, `--sx-shadow-panel`;
- `--sx-button-primary-*`, `--sx-button-secondary-*` and
  `--sx-button-danger-*`, `--sx-button-disabled-*`, button height/radius/gap,
  transition, focus and loading-indicator variables;
- `--sx-form-background`, `--sx-form-section-*`,
  `--sx-form-control-*`, `--sx-form-label-color`,
  `--sx-form-help-color`, `--sx-form-error-*`,
  `--sx-form-required-color`, `--sx-form-field-*`,
  `--sx-form-hint-*` and
  `--sx-form-actions-*`;
- `--sx-scrollbar-*` for native scrollbars and the compatibility adapter over
  third-party jQuery Scrollbar variants;
- `--sx-filter-wrapper-*`, `--sx-filter-panel-*`,
  `--sx-filter-label-color`, `--sx-filter-control-*`,
  `--sx-filter-search-icon-color` and `--sx-filter-chip-*`;
- `--sx-overlay-*`, `--sx-menu-*` and `--sx-modal-*`;
- `--sx-popover-*` and `--sx-calendar-*`;
- `--sx-alert-default-*`, `--sx-alert-info-*`, `--sx-alert-success-*`,
  `--sx-alert-warning-*` and `--sx-alert-danger-*`;
- `--sx-file-*` for reusable upload controls and their preview, drop zone,
  progress and error states;
- `--sx-backend-theme-accent`;
- `--sx-backend-theme-accent-contrast`;
- `--sx-backend-theme-accent-soft`;
- `--sx-backend-theme-border-color`;
- `--sx-backend-theme-row-border-color`;
- `--sx-backend-theme-header-color`;
- `--sx-backend-theme-text`;
- `--sx-backend-theme-muted`;
- `--sx-backend-theme-surface`;
- `--sx-backend-theme-hover`;
- `--sx-backend-theme-radius`;
- corresponding `--sx-collection-theme-*` action and collection variables.

Define values at the theme root. Keep component CSS based on derived
`--sx-backend-grid-*` and `--sx-collection-*` variables. This allows light and
dark themes to change values without rewriting components.

Use `.sx-interactive-surface` on an ordinary actionable link, compact card or
navigation row. It belongs to `BackendUiAsset`, so it is available without
registering Grid/List JavaScript. `sx-collection-item--interactive` maps its
collection-specific background and geometry onto the same token family.
Projects may set `--sx-interactive-surface-color` and related brand values on
a component, but must not duplicate its hover/focus/active selectors. Broad
project anchor rules must exclude `.sx-interactive-surface`.
Project-wide root mappings that must override both vendor palettes should use
`:root, html[data-sx-theme]` or an equivalently specific theme-root selector;
a lone `:root` can lose to the vendor dark-theme selector despite later asset
registration.

Use `.sx-button` as the renderer-independent button primitive and add exactly
one semantic variant: `.sx-button--primary`, `--secondary`, `--danger`,
`--success`, `--warning` or `--info`. Use the latter three for meaningful
workflow state transitions, not merely to decorate unrelated actions. Wrap
adjacent actions in `.sx-button-group` instead of adding margins to each
button.

Use `.sx-chip` for compact interactive toggles and filters, with
`.sx-chip--compact` for dense toolbars. State is expressed through
`aria-pressed="true"` and the compatibility `.is-active` class; loading uses
`aria-busy="true"` or `.is-loading`. Use `.sx-icon-action` for a square or
round icon-only action and always provide an accessible name. Both primitives
must keep visible `:focus-visible` treatment and consume the shared
`--sx-chip-*` / `--sx-icon-action-*` variables.
Standard Bootstrap/Unify `.btn` controls remain supported by the compatibility
adapter and consume the same token family. Collection actions deliberately
emit both contracts, for example
`.sx-button.sx-button--primary.sx-collection-action.sx-collection-action--primary`,
so existing collection geometry hooks keep working while state behavior stays
universal. Disabled controls must support native `disabled`,
`aria-disabled="true"` and historical disabled classes. Loading is opt-in
through `.sx-button--loading` or `aria-busy="true"`; do not invent a separate
project spinner.

Button default, hover, active, keyboard-focus, disabled and loading rules
belong to `cms-backend`. `cms-theme-unify-v2` may reassert those semantic
states after its historical Bootstrap stylesheet because it loads later, but
it must not define an independent component palette. Project CSS may override
brand variables such as the primary gradient, contrast, shadow and active
filter. It must not contain late `.btn-primary:hover/:focus/:active` rules
with fixed colors: those rules silently break both theme contrast and the
shared state contract.

The user-facing switch has exactly two choices: light and dark. With no stored
choice, the layout may initialize either value from
`prefers-color-scheme` before CSS loads. After an explicit choice, persist that
light/dark value and do not let an operating-system change override it. Apply
the initial `data-sx-theme` in an inline head bootstrap to prevent a flash of
the wrong palette.

When adapting an old component, first replace fixed colors with semantic
tokens. If a base asset loads fixed colors after `BackendAsset`, correct the
asset dependency order rather than escalating selector specificity.
Third-party field themes such as Krajee Select2 may be registered dynamically
after `BackendFormAsset`. Keep their narrowly scoped compatibility rules in
the backend form asset, qualify inline selections by `form.sx-backend-form`,
and qualify detached dropdowns by `html[data-sx-theme]`. Do not move those
rules into a site project or duplicate them in every layout theme.

Bootstrap alerts, dropdowns, modals, popovers and date/time picker colors
belong to `BackendUiAsset`. Do not keep fixed `.alert-*` colors in an
application layout theme. Grid and `ListViewWidget` pagination geometry belongs
to `BackendAsset` and must use the same collection palette. Their toolbar,
body and footer geometry also belongs there and is configured through the
shared `--sx-collection-*` tokens. The reusable
`skeeks/yii2-ajax-file-upload` package owns its upload geometry and consumes
the shared `--sx-file-*` contract with safe fallbacks, so it remains usable
without `cms-backend`. A concrete application theme may adapt its own window
implementation: for example,
`cms-theme-unify-v2` keeps Fancybox drawer geometry in `admin.css` and maps its
overlay, iframe surface, shadow and close button to the shared
`--sx-overlay-*` and `--sx-modal-*` tokens in `unify-theme.css`. The iframe
must be flush with the drawer edge; its close action overlays a safe content
gutter instead of occupying a separate full-height rail. The iframe document
must initialize the same stored light/dark choice independently, so verify both
the parent shell and the frame content.

Right-click row actions are a shared backend interaction owned by
`ControllerActionsColumn` and `AjaxControllerActionsWidget`. Resolve the trigger
from the row's direct `.sx-controller-actions-td`; never use the first nested
action trigger from an arbitrary cell because related entities may expose their
own action menus. A pointer-position anchor is only an invisible implementation
detail: remove presentation and minimum-size classes, remove its contents, keep
it non-interactive and delete it after the popover closes. Context menus and
action popovers use the shared `--sx-menu-*` palette in both themes and do not
show a Bootstrap arrow/notch. No technical caret proxy may remain visible in a
grid. Preserve explicit nested action clicks, ordinary links, PJAX and legacy
controller behavior.

Historical `grid.css` applies a light `text-shadow` to table headers.
`BackendAsset` must explicitly neutralize it on both the header cell and its
sorting link; otherwise dark-theme headings look blurred even when their color
is correct. Keep pagination adapters valid for both the canonical
`.sx-collection-view` root and direct legacy `.sx-backend-grid` renderers.

An ordinary page that builds a form directly with Bootstrap `ActiveForm`
instead of `BackendModelAction` must register `BackendFormAsset` and add the
`sx-backend-form` class. A class without the asset is insufficient: late
Krajee Select2 selections retain fixed white/gray surfaces. Page-specific
cards, report summaries and chart chrome may keep their own geometry, but
their colors must consume `--sx-color-*`, surface, border and panel tokens.

Stateful domain widgets may define semantic variables in `cms-backend` while
keeping their product markup in `skeeks/cms`. For the worker task calendar,
working days use the accent header, non-working days use the muted surface and
expired tasks use danger tokens. Variant selectors must be at least as
specific as the base `table.sx-calendar-day` selector, and all header
text-shadows must be neutralized. Tree widgets likewise keep hierarchy and
drag controls in their own asset, but text, hover, inactive, search and action
colors must use theme tokens rather than black, white or silver literals.

Bare backend `pre` diagnostics also belong to `BackendUiAsset` and use the
`--sx-code-*` contract. Detail-view code surfaces derive from the same
background token. Do not restore fixed `#333`, white or `#f5f5f5` code colors
in a layout or project stylesheet.

Fixed form-action JavaScript owns only sticky/fixed positioning. Its background,
border and shadow belong to `BackendFormAsset` through
`--sx-form-actions-*`; never write a literal light surface from JavaScript.

Both the legacy `BackendFiltersWidgetAsset` and the current
`BackendSearchAndFiltersWidgetAsset` depend on `BackendFormAsset`. Their shared
`backend-filter-theme.css` maps native and Select2 controls to
`--sx-form-control-*`; widget-specific files keep geometry and consume
`--sx-filter-*` for panels, labels, controls, search icons and applied-filter
chips. Legacy `AdminFiltersForm` uses the same token family with light
fallbacks for installations that load it without the modern backend asset.
Filter view actions must use shared button variants; do not set `silver`,
white or other palette values inline. Do not add light-only field colors to an
application theme.

Global action handlers must accept clicks originating from HTML and SVG
elements. Use jQuery predicates such as `closest()` and `hasClass()` instead
of assuming that DOM `className` is a string; SVG exposes a different
`className` shape.

## Verification matrix

At minimum verify:

| Scenario | Expected result |
| --- | --- |
| Zero records, regular user | Page header and rich empty state; no redundant filters |
| One to five records, regular user | Collection visible; filters hidden unless active |
| More than five records | Search and filters visible |
| Active query with zero matches | No-results state, not empty-collection copy |
| Showing manager | Filters and representation controls remain available |
| Simple `index`/`create` controller in `auto` | Page heading and right-side create action |
| Multi-action controller in `auto` | Tabs/actions preserved; no forced page heading |
| `ListViewWidget` | Items, pagination, filters and empty state render without grid-only UI |
| Light and dark variables | Text, surfaces, borders, actions and focus states remain legible |
| No saved theme choice | Initial light/dark value follows `prefers-color-scheme` without a flash |
| Saved theme choice | The same explicit theme survives reloads and is shared by admin and cabinet on the origin |
| Direct `GridViewWidget` | Base grid CSS loads before `BackendAsset`; table surfaces use semantic colors |
| Direct `EmptyStateWidget` | A page without Grid/List receives `BackendUiAsset`, rich empty markup and the semantic CTA in both themes |
| Legacy `.sx-block` | Shared CMS views receive the semantic panel without project CSS; new UPA/admin views use canonical surface/panel classes |
| Model-card data matrix | `sx-data-table` headers, values, borders, links and empty cells plus adjacent `sx-detail-section` blocks follow the active palette without fixed light surfaces |
| Custom model header | Title, metadata, split main/side layout, status stack, toolbar, inactive state, hint icons and actions reuse `sx-model-header__*`/`.sx-status`; no view-local CSS or inline `silver`/fixed palette remains in either theme |
| Disabled bulk actions | Labels, icons, borders and surfaces remain visibly disabled but readable in light and dark |
| Grid/List pagination | Normal, hover, active and disabled pages use the collection palette in both renderers |
| Bootstrap alerts | Default, info, success, warning and danger states remain legible without theme-specific fixed colors |
| Backend form, light and dark | Native controls, labels, help/error states and disabled controls remain legible |
| Legacy and backend validation | Real invalid submissions show semantic summary, required marker, invalid control/focus ring, help text and field hover in both themes |
| Selection controls | Native and Bootstrap checkbox/radio/switch variants plus Grid toolbar/header/row checkboxes share checked, focus and disabled states in both themes |
| Interactive surfaces | Ordinary actionable rows/cards and interactive List items share default, hover, active, focus, selected and disabled tokens |
| Select2, closed and open | Selection, search, options, selected/highlighted states and detached dropdown use the active palette |
| Bootstrap dropdown/modal | Menu items, modal sections, borders, close action and overlay use semantic surfaces |
| Row action popover | The direct row entity opens the correct menu at the pointer; links, border and shadow use the active palette; no Bootstrap notch or leaked caret anchor remains in either theme |
| Date/time picker | Popup, navigation, muted days, hover/today/active states remain legible in both themes and inside a window iframe |
| Ajax file uploader | Toolbar/dropdown and, without transmitting a real file, source-level preview/drop-zone/progress/error rules use `--sx-file-*` in both themes |
| Unify quick access | Edge, opened panel, rows, empty state, close action, status dots and backdrop follow the active palette |
| Unify BackendAction window | Fancybox overlay/drawer and its iframe content use the same active theme in admin and cabinet |
| Fixed form actions | The bar surface, border and shadow follow the active theme without inline white styles |
| Unify drawer edge | The iframe reaches the drawer edge and the close action does not create a separate vertical rail |
| Search and filters | Search, expanded panel, native/Select2 controls, manager actions and applied-filter chips follow the active palette |
| Action click on SVG/icon | No `className.indexOf` error; popover cleanup still runs |

Run `php -l` on changed PHP files and a runtime smoke test for a new renderer.
Use browser testing with multiple authorized users when the page behavior
depends on record count or permissions.
