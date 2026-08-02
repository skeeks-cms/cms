# Customer cabinets on SkeekS CMS sites

## Contents

- [Core principle](#core-principle)
- [Responsibility boundaries](#responsibility-boundaries)
- [Application shell](#application-shell)
- [Controller selection](#controller-selection)
- [Access and query scoping](#access-and-query-scoping)
- [Collection presentation](#collection-presentation)
- [Forms and actions](#forms-and-actions)
- [Theme and assets](#theme-and-assets)
- [Verification](#verification)

## Core principle

Build a customer cabinet as another consumer of the reusable SkeekS backend
framework, not as a parallel collection of unrelated frontend CRUD pages.

Reuse:

- `BackendComponent` for the cabinet environment and menu;
- `BackendController` for dashboards and non-model pages;
- `BackendModelController` only for genuinely custom model workflows;
- `BackendModelStandartController` for ordinary model collections, filters,
  create, view, update and delete actions;
- `BackendGridModelAction` presentation settings for page headings, actions,
  empty states, adaptive filters and renderer selection.

This approach must work for the administration, the current client cabinet,
future role-specific cabinets and new sites built around other services or
entities.

## Responsibility boundaries

Put reusable framework behavior in shared packages:

- controller/action contracts;
- filters and data-provider integration;
- table and `List/items` renderers;
- empty/no-results states;
- standard action-opening behavior;
- structural and responsive component CSS;
- semantic light/dark theme inputs.

Keep site-specific behavior in the application:

- menu structure and labels;
- site logo and cabinet wording;
- customer access queries;
- company/project/service relationships;
- domain-specific fields and validation;
- item partials and business copy;
- theme variable values and exceptional layout.

An explicitly project-owned domain workflow may keep its complete established
layout and scoped CSS. The current `skeeks.com` GPD/store screens are such a
case: preserve their geometry and behavior under `sx-gpd-*`/`sx-store-*`
hooks. Their `sx-*` prefix records the cleaned namespace, not ownership by
`cms-backend`. Move only separately identified primitives that are already
needed by another administration or cabinet.

Move application behavior into a package only after its contract is reusable
across sites or cabinet types.

## Application shell

Configure a dedicated backend component such as `upaBackend` in the
application:

```php
'components' => [
    'upaBackend' => [
        'menu' => [
            'data' => [
                'home' => [
                    'name' => 'Обзор',
                    'url' => ['/upa-home'],
                    'icon' => 'fas fa-home',
                ],
                'services' => [
                    'name' => 'Услуги',
                    'icon' => 'fas fa-layer-group',
                    'items' => [],
                ],
                'support' => [
                    'name' => 'Задачи и поддержка',
                    'url' => ['/upa-support'],
                    'icon' => 'far fa-comment-dots',
                ],
            ],
        ],
        'on beforeRun' => function () {
            $theme = new ClientPortalTheme();
            $theme->logoHref = \yii\helpers\Url::to(['/upa-home']);
            \Yii::$app->view->theme = $theme;
            ClientPortalAsset::register(\Yii::$app->view);
        },
    ],
],
```

Adapt this example to the site's existing component registration. Do not copy
an entire project configuration blindly.

Derive a cabinet theme directly from the shared backend theme and override
view lookup through `pathMap`:

```php
use skeeks\cms\backend\themes\BackendTheme;

class ClientPortalTheme extends BackendTheme
{
    public $pathMap = [
        '@app/views' => [
            '@common/themes/backend/views',
            '@skeeks/cms/backend/views',
        ],
    ];
}
```

Use the project path first and the shared backend views as fallback. Set the
cabinet asset bundle and the small set of header/sidebar hooks through
`BackendTheme`; do not make a new cabinet depend on `UnifyThemeAdmin`.
Customize brand values and genuinely exceptional layout in the project
without forking model actions or list mechanics.

Use the parallel `AdminTheme` path for a project administration shell. Its
project app asset should depend on `BackendAdminAppAsset` and an optional
brand-token asset, not on `UnifyAdminAllAsset`. Module-specific `beforeRun`
hooks that already render semantic `sx-*` markup should select the same project
administration theme instead of registering a compatibility bundle at
`EVENT_END_BODY`. Verify the module beside a normal admin route and UPA in both
themes. Do not apply this migration to the retired `skeeks/crm` package or its
legacy module.

## Controller selection

Use `BackendController` for an overview page that aggregates counts, status and
shortcuts:

```php
class UpaHomeController extends BackendController
{
    public function actionIndex()
    {
        return $this->render('index', [
            'siteCount' => $this->clientSitesQuery()->count(),
        ]);
    }
}
```

Use `BackendModelStandartController` for a normal customer entity:

```php
class UpaSupportController extends BackendModelStandartController
{
    public function init()
    {
        $this->modelClassName = CmsTask::class;
        $this->modelDefaultAction = 'view';
        $this->modelHeader = '';
        $this->permissionName = Cms::UPA_PERMISSION;
        $this->generateAccessActions = false;

        parent::init();
    }
}
```

Prefer the standard controller even when columns, filters, create fields and
detail rendering are custom. Drop to `BackendModelController` only when the
standard action lifecycle cannot express the workflow.

The verified `skeeks.com` sites cabinet follows this boundary: its collection
query, domain/monitoring/usage/price/date cells and customer scope live in
`UpaSitesController`, while `BackendGridModelAction` owns the page heading,
adaptive search, table shell, true-empty state and filtered no-results state.
Its existing `view`, `checks` and authorization workflows remain ordinary
model actions. A migration like this may leave the old custom index view in
place temporarily for comparison, but the active index action must not render
or duplicate that view.

## Access and query scoping

A cabinet permission grants entry to the cabinet; it does not prove access to
every model record.

Apply the same customer scope to:

- index data providers;
- `getModel()` and detail pages;
- update and delete actions;
- comments and related AJAX actions;
- relationship selectors;
- create-time company, project and service IDs.

Example:

```php
protected function clientTasksQuery(): ActiveQuery
{
    return CmsTask::find()->andWhere([
        'or',
        [CmsTask::tableName().'.created_by' => \Yii::$app->user->id],
        [CmsTask::tableName().'.cms_project_id' => $this->clientProjectIdsQuery()],
    ]);
}

public function getModel()
{
    if ($this->_model === null && $pk = \Yii::$app->request->get($this->requestPkParamName)) {
        $this->_model = $this->clientTasksQuery()
            ->andWhere([$this->modelPkAttribute => $pk])
            ->one();

        if (!$this->_model) {
            throw new NotFoundHttpException('Запись не найдена.');
        }
    }

    return $this->_model;
}
```

Return `404` for an inaccessible record instead of revealing its existence.
Never accept a posted company, project or service ID without resolving it
through a query scoped to the current user. Assign owner, creator, safe status
and other server-controlled fields in code.

## Collection presentation

Choose presentation by collection shape:

- zero records: page title, explanation, icon and one clear action;
- one primary service or record: prefer a focused detail/card page with the
  most useful next action;
- two to five records: show the collection without redundant search and
  filters for a regular customer;
- larger collections: expose search, filters, sorting and pagination;
- managers of backend showings: preserve filter and representation controls.

Start cabinet collections with:

```php
'index' => [
    'presentationMode' => 'auto',
    'pageHeader' => [
        'title' => 'Задачи и поддержка',
        'description' => 'Следите за задачами и общайтесь с командой.',
    ],
    'emptyState' => [
        'title' => 'Задач пока нет',
        'description' => 'Опишите вопрос или создайте первую задачу.',
        'icon' => 'far fa-comment-dots',
        'action' => [
            'backendAction' => 'create',
            'label' => 'Создать задачу',
        ],
    ],
    'hideFiltersOnSmallLists' => true,
    'smallListLimit' => 5,
],
```

Use `GridViewWidget` for services, tasks and other records with stable
comparable fields. A customer-facing Grid is not required to look like a
dense administration table: set `presentation => 'client'`, keep a clear
entity cell on the left and place compact understandable metrics on the
right. Keep table headers when comparison benefits from them; when a very
small service collection intentionally hides the header, render a short label
inside every metric cell.

Use `ListViewWidget` for genuinely varied card/item content that does not map
cleanly to stable columns. Keep the shared empty state and filter policy
independent of that choice.
`ListViewWidget` owns the structural `sx-list-view`, `sx-backend-list`,
`sx-list-items` and `sx-list-item` classes and registers `BackendAsset` even
when rendered directly from a custom cabinet view. Custom container and item
options must extend these classes rather than replace them. Use the
`--sx-list-*` variables for the collection surface, item padding, separators
and hover state; keep each item's business markup in the project partial.
Both `GridViewWidget::rowOptions` and `ListViewWidget::itemOptions`
automatically retain the semantic `sx-collection-item` class, including when
the controller supplies an options callback. Grid rows also retain
`sx-grid-row`; list items retain `sx-list-item`.

`GridViewWidget::presentation = 'client'` adds
`sx-backend-grid--client`. Its entity and metric geometry belongs to the
shared `cms-backend` asset:

- `sx-collection-cell--entity` combines media and stacked copy;
- `sx-collection-cell--metric` combines a prominent value and a quiet label.

Do not recreate that geometry in `portal.css`. The project may only change
the corresponding semantic variables. When migrating an existing route,
assign a versioned action `configKey` (for example
`upa-client-v2/services`) if old stored column choices would otherwise hide
the new primary entity column. Do not delete the old saved showing.

Both renderers expose the same collection shell:

- `sx-collection-toolbar` with `__start` and `__end` slots;
- `sx-collection-body` around the table or list items;
- `sx-collection-footer` with `__pager`, `__per-page` and `__summary`;
- `collectionToolbarLeft` and `collectionToolbarRight` for optional List or
  Grid toolbar content.

`TCollectionViewPresentation` owns the shared page-size control and footer
behavior. `GridViewWidget` keeps `beforeTableLeft` and `beforeTableRight` as
backward-compatible aliases, and its historical `sx-before-table`,
`sx-table-wrapper` and `sx-table-additional` classes remain on the same
elements. Do not copy a separate project footer or pagination implementation.
Configure geometry through `--sx-collection-toolbar-*`,
`--sx-collection-footer-*`, `--sx-collection-pagination-*` and
`--sx-collection-page-size-height`.

Inside that project partial, prefer the shared semantic row slots:

- `sx-list-item__layout` for the complete row;
- `sx-list-item__primary` for the clickable leading/content area;
- `sx-list-item__leading` for an icon, avatar or compact visual marker;
- `sx-list-item__content`, `sx-list-item__title` and `sx-list-item__meta`;
- `sx-list-item__aside` for status, relation, amount, date or other comparable
  metadata;
- `sx-list-item__field`, with `--compact` or `--wide` modifiers where needed.
- `sx-collection-item__relations` and `sx-collection-item__date` for common
  comparable metadata;
- `sx-collection-item__actions` with `sx-collection-item__action` and optional
  `--icon` for row-local actions.

Use presentation-independent cell primitives inside both Grid and List
business markup:

- `sx-collection-cell` as the min-width-safe cell/content root;
- `sx-collection-cell--stack` or `--inline` for compact internal layout;
- `sx-collection-cell__primary`, `__secondary` and `__subtle` for text
  hierarchy;
- `sx-collection-cell__media`, optionally `--small` or `--large`, for an
  avatar, icon, image or initials placeholder;
- `sx-collection-cell__relations`, `__amount` and `__date` for common
  comparable values.

The historical `sx-collection-item__relations` and
`sx-collection-item__date` remain supported aliases. New project partials
should use the cell names so the same markup can move between Grid and List.
Use a native checkbox and the existing `sx-grid-checkbox` hook; the shared
backend styles its size, focus, disabled state and theme-aware `accent-color`.
Do not recreate avatars, primary/secondary text or checkbox colors in a
cabinet stylesheet.

Ordinary form checkboxes and radios use native inputs inside
`sx-backend-form` or `sx-form-admin`, or the explicit
`sx-selection-control` hook outside those forms. Bootstrap
`custom-checkbox`, `custom-radio` and `custom-switch` markup remains a
supported compatibility renderer. All variants consume the shared
`--sx-selection-control-*` and `--sx-switch-*` tokens; layout themes and
projects must not assign their own checked, focus or disabled palette.
Historical Unify `.checkbox` markup may keep its label pseudo-element, but its
input must remain keyboard-focusable and its visual states must use the same
tokens. Collection toolbar, header and row checkboxes share this contract
through the `--sx-collection-checkbox-*` aliases.

Reusable compact model previews use:

- `sx-preview-card` with a domain modifier such as `--person`, `--project` or
  `--task`;
- `sx-preview-card__media` and `__media-link`;
- `sx-preview-card__content`, `__title` and `__meta`;
- `sx-preview-card__meta--danger` only for genuinely exceptional metadata.

Keep the historical `sx-photo`, `sx-no-photo`, `sx-main-info`, `sx-employee`,
`sx-task-info` and `sx-task-status` hooks while migrating existing widgets.
Their reusable widget must register `BackendAsset` itself, including when it
returns cached markup. Do not register structural CSS from a PHP widget and do
not use layout-theme utility classes such as `g-brd-*` or inline gray/white
colors in package preview views. The shared contract owns media geometry,
text hierarchy, work-state rings and light/dark colors.

The CMS and CRM person/contractor, worker, project and task preview widgets
share this same contract. Keep their domain-specific PHP rendering in the
owning package, but keep avatar, placeholder, metadata and worker-state CSS in
`cms-backend`. Do not restore the historical `.sx-preview-card` or
`.sx-worker-card` geometry block in `cms-theme-unify-v2`; a layout theme must
not be required for these reusable widgets to render correctly.

Use `sx-collection-item--interactive` only when the row has an actual primary
action. Explicit state modifiers are `--selected`, `--disabled`, `--success`,
`--warning`, `--danger` and `--info`; `aria-selected` and `aria-disabled` are
also supported. The historical Grid selection class `sx-active` remains a
selected-state alias. Do not infer a row tone from an arbitrary domain status:
map it explicitly in `rowOptions` or `itemOptions` when the whole-row tone is
meaningful.

The shared renderer owns alignment, responsive stacking, focus, hover and
light/dark colors. The project partial owns business values, links and action
behavior. `ListViewWidget` keeps `showOnEmpty` enabled so its configured rich
empty state is rendered instead of Yii's plain `emptyText`. Empty detection
must happen after filters have modified the data provider; do not query or
cache `totalCount` from widget initialization for this purpose.

Outside a Grid or List, use `.sx-interactive-surface` for a link, compact card
or navigation row with a real primary action. It is a lightweight
`BackendUiAsset` primitive and owns default, hover, active, keyboard-focus,
selected/current and disabled states. State inputs are
`.sx-interactive-surface--selected`, `.sx-interactive-surface--disabled`,
`aria-selected`, `aria-current="page"` and `aria-disabled`.
`sx-collection-item--interactive` consumes the same
`--sx-interactive-surface-*` token family, so dashboard links and collection
rows do not develop different interaction palettes. Keep structure and
domain-specific content in the project; customize only semantic variables.
Exclude `.sx-interactive-surface` from broad project rules such as
`a:not(.btn)` so those rules cannot replace the shared states.
When a project remaps root semantic values for both themes, declare them on
`:root, html[data-sx-theme]` (or an equivalent selector with the same
specificity as the vendor theme root). Otherwise the vendor
`html[data-sx-theme="dark"]` palette can win in dark mode even when the project
stylesheet is loaded later. Runtime-check both the resolved token and the
actual `:focus-visible` ring; checking only the source declaration misses this
cascade failure.

Use the shared `sx-status` contract for compact statuses in grids and lists.
The neutral state uses `sx-status`; semantic variants add one of
`sx-status--success`, `--warning`, `--danger`, `--info` or `--accent`.
Reusable domain widgets should map their business states to these variants
while preserving existing classes for backward compatibility. Keep their
colors, border, geometry and light/dark behavior in `cms-backend` tokens rather
than in a cabinet item partial. If a status widget normally loads related data
for a tooltip, provide a way to disable that detail in collections to avoid an
N+1 query; the task status widget exposes `showScheduleDetails` for this.
Historical active-task rows may retain `g-bg-in-work`, which is adapted by the
shared backend CSS to semantic row tokens.

## Forms and actions

Reuse standard controller actions through `backendAction` so the same create,
view, update or report action keeps its configured page, drawer, modal or
new-window behavior.

Keep customer forms intentionally smaller than administration forms:

- expose only fields the customer understands and may control;
- populate owner, status and routing fields server-side;
- scope AJAX selectors to the current customer;
- validate selected relationships again during save;
- keep file uploads and comments attached to an already authorized model.

Use `BackendFormAsset` for shared presentation. It depends on
`BackendUiAsset` and maps native controls, validation states, disabled fields
and Krajee Select2 to `--sx-form-*` tokens. Project cabinet CSS may override
token values for branding, but should not restyle `.form-control`,
`.select2-selection` or detached Select2 dropdowns independently.
Select2 model selectors may also appear outside an ActiveForm, for example in
a detail-card relation block. Their universal closed control, detached
dropdown, search field, options, disabled state and single/multiple geometry
belong to `BackendFormAsset`. The shared SkeekS `Select` wrapper must register
that asset after its Krajee bundle, and universal Select2 selectors must start
with `html[data-sx-theme]`; this keeps the adapter conditional on a real
Select2 consumer while ensuring it outranks third-party theme CSS. Keep
form-qualified validation rules alongside this universal control adapter, and
do not make a standalone selector depend on a project or domain stylesheet to
become dark-theme safe.
Select2's inline multiple-search field is a textarea in current releases. It
must be exempted from the ordinary form textarea minimum height: keep an empty
multiple selector at `--sx-form-control-height`, let selected chips wrap, and
allow the selection container to grow only as content creates new rows. Verify
empty, one-chip and wrapped multi-chip states rather than only the dropdown.
The 2026-07-31 task-card check verified the standalone relation selector
closed and open in both themes with zero horizontal overflow.
Legacy `sx-form-admin` forms use the same validation contract through
`AdminFormAsset`, which depends on `BackendUiAsset`. Error summaries, required
markers, field hover, help text and invalid focus rings must consume
`--sx-form-error-*`, `--sx-form-required-color`,
`--sx-form-field-*` and `--sx-form-hint-*`; do not retain fixed red or pale
blue validation rules in an admin theme or project CSS.
Control geometry is part of the same shared contract. Keep control height,
padding, radius, textarea minimum height, label weight and help-text size in
`cms-backend` through `--sx-form-control-*` and related `--sx-form-*` tokens.
A project may assign those variables but must not copy generic
`.form-control`, `.control-label`, `.help-block`, `.select2-selection` or
Chosen selectors.

Legacy Chosen remains a supported compatibility consumer while old controllers
still use it. Its universal adapter belongs to `BackendFormAsset`, starts with
`html[data-sx-theme]`, and must work for both `sx-backend-form` and
`sx-form-admin` even though `chosen.bootstrap.min.css` is registered later.
Theme the closed control, open dropdown, search/results/highlight, multiple
tags, focus and disabled states through shared form/color/popup tokens. Test a
real legacy form in both themes in closed, open and selected-tag states; project
CSS must not carry a parallel Chosen palette.

The same rule applies to fieldsets. Keep historical
`.sx-form-fieldset`, `.sx-form-fieldset-title` and
`.sx-form-fieldset-content` markup, but let `BackendUiAsset` present it through
`--sx-form-section-*`. Collapse behavior remains in `FieldSetAsset` and toggle
icons remain with the backend form asset. A layout or project must not add a
fixed white title, silver border or light-only shadow.

`SelectModelDialogWidget` is also a shared form component. Its asset must
depend on `BackendUiAsset`, and the widget root carries
`.sx-select-model-dialog`. Selected single and multiple model previews consume
the `--sx-model-selection-*` contract. Keep their surface, border, link,
remove-action and focus states in `cms-backend`; projects may override token
values but must not copy `.sx-view-cms-content` selectors.

Form container geometry uses `--sx-form-background`,
`--sx-form-border-color`, `--sx-form-border-radius`, `--sx-form-padding` and
`--sx-form-max-width`. A form opened as a standard action may widen through
`--sx-form-action-window-max-width` without introducing a separate form
template.
Fixed form actions use `--sx-form-actions-background`,
`--sx-form-actions-border-color` and `--sx-form-actions-shadow`. Do not set a
white background from the fixed-button JavaScript: JavaScript controls
positioning, while the asset CSS owns presentation.

Use a single dominant CTA in an empty state. Put frequent create actions at the
right side of the page heading. Keep multiple section-level actions as tabs or
secondary actions rather than presenting several equally dominant buttons.

## Theme and assets

Use this ownership boundary:

- `skeeks/cms-backend`: the neutral light/dark palette, historical variable
  aliases, buttons and disabled actions, surfaces, forms, filters, tables,
  list/items, shared pagination, empty states, Bootstrap
  alerts/menus/modals/popovers, bare code blocks, legacy neutral-background
  helpers and date/time picker presentation;
- reusable widget packages, such as `skeeks/yii2-ajax-file-upload`: their own
  geometry, mapped to shared semantic inputs with safe standalone fallbacks;
- `BackendTheme` and `BackendAppAsset`: early no-flash initialization,
  persistent switcher and the shared shell/runtime;
- `BackendCabinetAsset`: the opt-in reference customer-cabinet presentation
  over that runtime; projects depend on it instead of copying the common
  header/sidebar/footer and standard page CSS;
- `cms-backend-admin\AdminTheme`: the standard administration consumer and
  its admin-only header/footer/quick-access slots;
- `cms-theme-unify-v2`: an explicit compatibility adapter only for products
  that still render Unify layouts or utilities;
- the site project: brand values, such as accent, gradient, radii and genuinely
  exceptional layout.

Cabinet and administration themes compose the shared header through the same
`brand`, `context`, `actions` and `profile` slots. Keep each product's
`_header.php` as a small orchestrator and put the slot contents in focused
partials. Account dropdowns and quick-create menus use the shared
`sx-shell-header__menu*` contract; do not restore a cabinet-only profile-menu
geometry or duplicate the admin dropdown rules in project CSS.

Keep vendor component CSS structural. Map project identity into semantic
variables instead of copying component selectors:

```css
:root,
html[data-sx-theme] {
    --sx-color-accent-gradient:
        linear-gradient(135deg, #ee4d7d 0%, #efd740 100%);

    --sx-shell-header-background: #171923;
    --sx-shell-sidebar-width: 264px;

    --sx-button-primary-background: var(--sx-color-accent-gradient);
    --sx-button-primary-color: var(--sx-color-accent-contrast);
    --sx-collection-theme-action-background: var(--sx-color-accent-gradient);
    --sx-collection-theme-action-color: var(--sx-color-accent-contrast);
}
```

The standard orange `--sx-color-accent*` family already belongs to
`cms-backend/theme.css`. Do not repeat those values in a project cabinet.
The example overrides only the project gradient and shell geometry; projects
with a different brand may still replace the semantic accent family directly.

Assign the shared `--sx-*` inputs directly. Do not introduce `--portal-*` or
another project token graph merely to alias it back into the backend contract.

Button geometry and states also belong to `cms-backend`. Configure ordinary
button height, padding, weight, primary border and hover treatment through
`--sx-button-*` tokens. Keep `.btn-sm` and `.btn-xs` compact and verify that
disabled bulk actions remain legible. A project may map its accent or gradient
into those variables, but must not maintain parallel `.btn`,
`.btn-primary`, `.btn-default` or historical `.u-btn-*` component rules.
Migrate project aliases such as `portal-button` to the standard classes. Scope
generic project link rules with `a:not(.btn)` so they cannot override button
colors. Do not assume that white is readable on a branded gradient: expose a
brand contrast token and verify it against every gradient stop.

Lightweight primitives needed on ordinary pages and inside action iframes,
including `.sx-status`, belong to `BackendUiAsset`/`theme.css`. Keep
collection-only structure and compatibility adapters such as
`.g-bg-in-work` in `BackendAsset`/`backend.css`. This avoids pulling grid
JavaScript into a detail page merely to render a semantic status.
`BackendUiAsset` loads `theme.css` for semantic values and `ui.css` for
universal component adapters that must also work on pages without a Grid or
List widget. Keep widget-specific structure in `backend.css`; do not make an
ordinary detail page register the complete `BackendAsset` only to receive
colors.

Old package views may retain `.sx-bg-primary`, `.sx-bg-secondary` and
`.sx-bg-gray-light`; `BackendUiAsset` maps them to the neutral surface token.
If a reusable layout's historical CSS loads later, its final semantic adapter
must repeat that token mapping. Keep fixed helper backgrounds out of project
CSS. A layout-only `body.sx-empty` background remains a shell adapter (Unify
uses `--sx-color-canvas`) and must not be confused with the reusable
`.sx-empty-state` component.

Render an ordinary cabinet page with no records through
`EmptyStateWidget`, not a project-specific `.portal-empty-state` copy. The
widget registers `BackendUiAsset` itself; its generic `.sx-empty-state`
structure lives in `ui.css`, while collection compatibility aliases and
collection-token mapping remain available through `backend.css`.

Use the shared key/value contract for model details:

- existing Yii `DetailView` tables receive it through `table.detail-view`;
- new package markup may opt in with `.sx-detail-view` or
  `.sx-key-value-view`;
- labels, values, links, code blocks, borders, spacing and mobile stacking use
  the `--sx-detail-view-*` tokens;
- on narrow screens each label/value row becomes a self-contained stacked
  surface without horizontal page overflow.

Do not copy `.detail-view` colors into a package or layout theme. Override
tokens when a product needs different geometry, and keep domain-specific value
rendering in the owning view.

Bare `pre` diagnostics use the shared `--sx-code-*` tokens as well. Do not
keep a project-level fixed light code surface; verify both ordinary and
DetailView code blocks in light and dark modes.

Use the shared tabs contract for forms, detail sections and alternate
representations:

- preserve Bootstrap `.nav-tabs`, `.nav-item`, `.nav-link`, `.tab-content` and
  `.tab-pane` for backward compatibility;
- new or migrated widgets add `.sx-tabs`, `.sx-tabs__nav` and
  `.sx-tabs__content`;
- the legacy `ActiveFormUseTab` emits both sets of classes, so old
  `sx-form-admin` screens receive the same light/dark behavior without
  controller changes;
- colors, active/hover/focus/disabled states, spacing and content surfaces
  consume `--sx-tabs-*`;
- semantic form tabs remain on one horizontally scrollable row at narrow
  widths instead of wrapping or creating page-level overflow;
- manager `sx-backend-showing-tabs` use the same palette and their own
  horizontally scrollable strip from `BackendAsset`.

Keep generic tab palette and semantic-form responsiveness in
`BackendUiAsset`/`ui.css`. Keep manager-showing layout in
`BackendAsset`/`backend.css`. A layout theme may position a tab area, but must
not restore fixed white or `#f5f9f9` active/content backgrounds. Project
branding should override variables rather than `.nav-tabs` selectors.

The legacy `.sx-preview-card`, `.sx-photo` and `.sx-no-photo` avatar contract
is adapted by `cms-backend` as well, so existing CMS widgets do not depend on
`cms-theme-unify-v2` merely for media geometry or light/dark colors. Preserve
those classes while migrating old widgets; use `sx-collection-cell__media` in
new code.

Controller callbacks that compose a person/account cell must emit the semantic
preview structure (`sx-preview-card__media`, `sx-preview-card__content`,
`sx-preview-card__title` and `sx-preview-card__statuses`) instead of assembling
avatar borders, spacing and role badges with inline styles. Render roles and
other compact labels with `.sx-status` variants. This keeps real populated
grids themeable and prevents repeated `silver`/white badges from bypassing the
shared palette.

Use the same contract for file cells with `sx-preview-card--file`: place an
image or extension placeholder in `sx-preview-card__media`, names in
`sx-preview-card__title`/`__meta`, and extension/MIME labels in semantic
`.sx-status` elements. File-specific geometry belongs to the shared modifier,
not to callback-level `style` attributes. Icons inside semantic buttons must
inherit the button foreground instead of forcing white inline.

Domain widgets with substantial reusable presentation, such as the worker task
calendar, must register a dedicated package asset instead of embedding a
light-only stylesheet in their view. The widget asset owns its scoped geometry
and state selectors, depends on `BackendUiAsset`, and maps headers, surfaces,
unavailable overlays, drag handles and success/info/warning/danger rows to the
shared `--sx-color-*` contract. Preserve keyboard focus and provide a
`prefers-reduced-motion` fallback for temporary attention animations.
The calendar action strip uses `sx-task-calendar__toolbar`,
`__toolbar-start` and `__toolbar-end`; its spacing, responsive stacking and
alignment belong to the shared `WorkerTasksCalendarAsset`. Do not rebuild
this strip with Bootstrap `row`/`col`/`pull-*`, inline margins or Unify
`g-mb-*` helpers. The 2026-07-31 administration check preserved the existing
desktop geometry in both themes, kept zero horizontal overflow and removed
the final live `g-*` token from the CMS calendar page without changing the
legacy `skeeks/crm` package.
When equivalent widgets exist in several domain packages, keep their queries
and markup in those packages but move the shared presentation asset to
`skeeks/cms-backend`. Every domain widget registers that same asset; do not
copy a calendar stylesheet into `skeeks/cms`, `skeeks/crm` or a project.
For CMS-only widget families such as comments and activity logs, register one
domain asset from every entry widget. Keep reusable interactive states on
backend primitives (`.sx-chip`, `.sx-icon-action`), while the CMS asset owns
only editor, log and attachment geometry. Telephony and backend web
notifications each register their own CMS domain asset, both depending on
`BackendUiAsset`; do not put their panel/modal geometry into Unify or project
CSS. Map notification popovers, browser-permission panels, work-reminder
dialogs and call panels to `--sx-color-*`, radius, shadow, focus and button
tokens. The current-user schedule/timer control follows the same rule:
`CmsUserScheduleAsset` owns its responsive geometry, refresh behavior and
reduced-motion fallback, while the PHP view provides only the current state
and `data-sx-schedule-refresh` configuration. Pass URLs and localized
messages through semantic markup/data attributes so behavior can live in the
external asset instead of per-view `registerJs` blocks.

Use the shared surface primitives instead of project-specific card shells:

- `.sx-surface` is the flat bordered surface for cabinet sections;
- `.sx-panel` uses the same contract with the shared raised-panel shadow;
- `.sx-surface--padded` / `.sx-panel--padded` apply semantic inner spacing;
- `.sx-surface--clip` / `.sx-panel--clip` clip child rows and tables to the
  shared radius;
- `.sx-panel__header`, `.sx-panel__title`, `.sx-panel__hint` and
  `.sx-panel__action` are the reusable heading slots;
- `.sx-panel__header--bordered` separates that heading from the panel body.

Their palette, radius, padding and shadow come from `--sx-surface-*` and
`--sx-panel-*`. A project may override those variables for brand geometry, but
must not keep aliases such as `.portal-panel` that duplicate the complete
surface or header implementation.

Existing package views may still render `.sx-block`. Treat it as the
`BackendUiAsset` compatibility panel configured through `--sx-block-*`; it
must work even when no project stylesheet is present. New cabinet and admin
views should choose `.sx-surface` for a flat bordered section or `.sx-panel`
for a raised section, adding `--padded` and `--clip` intentionally. Never
reintroduce a project-wide `.sx-block` shadow, radius or light background.

For compact dashboard summaries use `.sx-metrics` with `.sx-metric`,
`.sx-metric__value` and `.sx-metric__label`. Choose `.sx-metrics--3` or
`.sx-metrics--4` for the intended desktop column count. The shared component
owns separators and responsive folding: four columns become two and then one;
three columns become one on a narrow screen. Projects may tune
`--sx-metric-*`, but must not copy a parallel `.portal-summary` component.

Prepare light and dark themes by changing semantic values, not component
selectors. Preserve accessible contrast, focus states and reduced-motion
behavior.

Filter widgets consume `--sx-filter-*` for their wrapper, panel, labels,
manager controls, search icon and applied-filter chips. Their native and
Select2 fields reuse `--sx-form-control-*` through the shared filter palette
adapter. A cabinet project may change the variable values, but must not copy
`.sx-filters-block`, `.sx-fast-filter-btn` or Select2 selectors. Historical
`AdminFiltersForm` tabs, dropdown rows and hover states also consume these
tokens. Keep filter actions on semantic button classes rather than inline
neutral colors.

The Unify quick-access shell consumes the main `--sx-color-*` palette. Its
project/client markers may be branded through
`--sx-quick-access-project-background` and
`--sx-quick-access-client-background`; do not copy the panel or row selectors
into a project.

Render cabinet CTAs with the shared `.sx-button` primitive. Collection page
headers and empty states add `.sx-collection-action` only as a compatibility
and placement hook; they still carry `.sx-button` plus the matching semantic
variant. Keep common height, padding, radius, typography, hover, active,
keyboard-focus, disabled and loading behavior in `cms-backend`. A cabinet
theme should normally set only `--sx-button-primary-*` brand values, including
contrast and active-state values. Remove late project `.btn-primary`
state selectors with fixed colors, and verify computed styles after every
vendor, layout and project asset has loaded.

Expose only light and dark to the user. On the first visit, when no explicit
choice exists, read `prefers-color-scheme` and resolve it to one of those two
values before stylesheets render. Persist an explicit selection in local
storage so the administration and customer cabinet use the same choice on the
same origin.

Register cabinet CSS through a dedicated asset bundle that depends on the
normal backend application asset. Verify the resulting order: the historical
base stylesheet of a reusable layout must load first, its structural CSS next
and its semantic adapter last. A project compatibility asset must depend on
the reusable application asset instead of publishing the same CSS files again.
`GridViewAsset` must precede `BackendAsset`, while project variable overrides
must follow the reusable theme palette. `GridViewWidget` is responsible for
registering `BackendAsset` even when used directly in a custom cabinet view.
Keep project CSS free of copied component rules. Brand colors, header contrast,
popup shadows, drawer shadows and backdrops belong in project-root variables;
selectors should consume those variables. Project styles may retain only
brand values and genuinely exceptional shell geometry that cannot be shared
by another administration or cabinet.
Do not repeatedly delete asset directories when asset URLs already include a
changing key; reload and verify the published URL first.

For actions opened through `sx.classes.Window`, theme both layers: the
Fancybox drawer in the parent layout and the empty-layout document rendered in
its iframe. Keep common Fancybox geometry in the `cms-backend` action-window
adapter and map its palette to shared `--sx-overlay-*`, `--sx-modal-*` and
`--sx-action-window-*` values. A reusable layout theme may adapt only shell
placement that is unique to that layout. Do not copy drawer selectors into
each cabinet project. Keep the iframe flush with the drawer edge and place the
close action over the document's safe inner gutter; do not reserve a
full-height side rail only for the close action.

The optional Unify administration shell is an adapter over the shared
`cms-backend` contract, not the standard administration owner and not a second
palette. Keep its remaining header, sidebar and drawer compatibility values in
`cms-theme-unify-v2` under narrowly scoped `--sx-unify-*`,
tokens. Standard quick access, the theme switcher and semantic header/sidebar
belong to `cms-backend`/`cms-backend-admin`. Map ordinary body text, menus,
controls, tables, notifications and status colors back to the shared
`--sx-color-*`, `--sx-menu-*`, `--sx-form-*`, `--sx-collection-*` and
`--sx-status-*` families. An opt-in Unify header/sidebar asset must depend on
its root compatibility asset so `BackendUiAsset` always loads first.
Historical variables such as `--primary-color`, `--bg-color` and
`--bg-block-color` are compatibility aliases only; they must resolve to the
semantic palette and must not become a new customization layer.

Every standalone layout that can render outside the main administration shell
(authorization, 403/error pages and iframe/empty layouts) must initialize the
same theme attributes before its first stylesheet. Reuse the layout's shared
theme bootstrap partial instead of copying localStorage and
`prefers-color-scheme` logic. Its asset must depend on `BackendUiAsset`, load
the same semantic theme adapter and use shared button, form, surface and error
tokens; do not keep a separate fixed authorization palette or inline light
preloader/footer colors.

Profile and personal-account screens are CMS domain components. Keep their
shared presentation and behavior in `skeeks/cms`, not in a project stylesheet
and not as a second generic backend palette. Register `CmsProfileAsset` for
profile summaries and password controls. Register `CmsProfilePhoneAsset` only
on editable screens that need the optional phone-mask dependency. Views expose
behavior through `data-sx-password-*` and `data-sx-phone-mask` attributes;
they must not copy password generators, use hard-coded input IDs, initialize
the mask plugin inline or position controls with inline styles. Password
visibility is an actual button with an accessible label and pressed state.
Keep action-specific AJAX success callbacks in the view when they are part of
that action rather than reusable profile behavior.

## Verification

Test at least:

1. a customer with zero records;
2. a customer with one to five records;
3. a customer with a larger collection;
4. an active search returning no matches;
5. a privileged administrator or showing manager;
6. direct access to another customer's record ID;
7. forged company/project/service IDs during create or update;
8. create, view and comment action opening behavior;
9. desktop and mobile navigation;
10. light and dark themes, first-visit OS preference and persisted manual
    selection;
11. loaded stylesheet order for direct grids;
12. legacy fixed-color components such as cookie notices and theme-specific
    side rails;
13. a create/update form in both admin and cabinet, including focus, disabled
    and validation states;
14. closed and open Select2 controls in both themes, including detached
    dropdown surfaces and highlighted options;
15. Bootstrap dropdowns and modals in both themes;
16. `BackendAction` page/drawer behavior from both admin and cabinet, including
    the parent overlay, close action and iframe theme;
17. console errors after clicking icon/SVG descendants of global action
    handlers;
18. row action popovers and date/time pickers in both themes, including a
    picker opened inside a `BackendAction` iframe;
19. file uploader toolbar/dropdown and, when a safe disposable file is
    explicitly authorized, preview, drop-zone, progress and error states;
20. Unify quick-access edge and opened panel in both themes, including rows,
    empty state, close action, status dots and backdrop;
21. disabled bulk actions in a populated grid in both themes;
22. grid and `ListViewWidget` pagination, including active and disabled pages;
23. default/info/success/warning/danger alerts without layout-theme color
    overrides;
24. fixed form actions while scrolling in both themes;
25. a drawer edge with no separate close-button rail on desktop and mobile;
26. search-only and expanded filter variants in both themes, including native
    fields, Select2, manager controls and an applied-filter chip;
27. an empty and populated `SelectModelDialogWidget` in both themes, including
    single and multiple previews, choose/deselect actions and keyboard focus;
28. a populated `ListViewWidget` row and both its true-empty and filtered
    no-results states in light and dark themes; confirm that the primary row
    action still opens through the standard backend action behavior;
29. neutral, success, warning, danger, info and accent statuses in both themes;
    check text contrast and confirm that status widgets do not animate
    continuously or issue avoidable per-row relationship queries;
30. a direct `EmptyStateWidget` on an ordinary cabinet page without Grid/List;
    confirm the surface, icon, copy and CTA in both themes and confirm that the
    page does not require project-specific empty-state geometry;
30. flat and raised surfaces, a bordered panel header and padded content in
    both themes;
31. three- and four-column metric summaries on desktop and at mobile width,
    including separator direction and horizontal overflow;
32. legacy `g-bg-in-work` rows in both themes, including cell backgrounds and
    readable text;
33. primary, secondary and danger `.btn`, `.sx-button` and collection actions
    in both themes: default, hover, active, keyboard-focus, disabled and
    opt-in loading states, compact/ordinary sizes and a project-branded
    primary CTA;
34. native, Select2 and Chosen control geometry in both themes, including
    textarea minimum height, empty/one-chip/wrapped Select2 multiple height and
    help/validation text;
35. the common Grid/List toolbar, body and footer in both themes, including
    the preserved legacy class aliases;
36. the page-size selector, active and disabled pager items, summary, mobile
    stacking and the absence of horizontal page overflow.
37. default, hover, selected and disabled collection items in Grid and List,
    including callback-based options, legacy `sx-active` selection and
    row-local actions in both themes.
38. primary/secondary cell text, media placeholders, dates, amounts and
    native grid checkboxes in both themes; include a legacy
    `sx-preview-card` rendered without relying on Unify palette rules.
39. person, worker, project and task preview widgets in both themes, including
    real and placeholder media, compact variants, danger metadata, worker
    state rings and the standard model action opening from the title/media.
40. CMS and CRM preview widgets on real populated grids after removing the
    legacy Unify avatar block; confirm the widgets register `BackendAsset`,
    expose no package-level fixed palette and remain readable in both themes.
41. ordinary Yii `DetailView` and explicit `.sx-key-value-view` tables in both
    themes and at mobile width; confirm label/value contrast, long-value
    wrapping, code-block readability, universal `ui.css` loading and no
    horizontal page overflow.
42. legacy `ActiveFormUseTab`, semantic `.sx-tabs` and manager
    `.sx-backend-showing-tabs` in both themes; switch a real tab, verify
    active/inactive/hover/focus contrast, and confirm that long tab sets scroll
    inside one row on mobile without page-level overflow.
43. submit invalid real `sx-backend-form` and `sx-form-admin` screens in both
    themes; verify error summary, required marker, invalid border/focus ring,
    help text and field hover use the same semantic validation palette.
44. exercise native form checkbox/radio, Bootstrap custom checkbox/radio/switch
    and collection toolbar/header/row checkboxes in both themes; verify checked,
    keyboard-focus, disabled and label states use one semantic palette.
45. exercise `.sx-interactive-surface` on an ordinary cabinet link/card and
    `sx-collection-item--interactive` in a populated List in both themes;
    verify default, hover, active, keyboard-focus, selected/current and disabled
    states consume one token family, and confirm broad project anchor rules do
    not override the primitive.
46. exercise legacy `.sx-bg-primary`, `.sx-bg-secondary` and
    `.sx-bg-gray-light`, a bare `pre` block and an Unify `body.sx-empty` page in
    both themes; confirm no fixed light surface survives the final project
    stylesheet.
47. exercise a legacy package `.sx-block`, a canonical `.sx-surface` and a
    canonical `.sx-panel` in admin and UPA; verify padding, border, radius,
    background and shadow in both themes without a project `.sx-block` rule.
48. exercise expanded and collapsed `.sx-form-fieldset` sections in a real
    `sx-backend-form` and legacy `sx-form-admin`; verify title/link contrast,
    border, radius, shadow, content surface and toggle state in both themes,
    with no project fieldset or generic placeholder rules.
49. exercise a model update/view header with and without media, external ID
    status, metadata, back link and delete action in both themes; verify there
    are no inline palette values.
50. exercise an admin dashboard card and a real `BlockTitleWidget` form
    section in both themes; verify surface, border, heading and secondary
    action contrast.
51. exercise a page using jQuery Scrollbar in both themes; verify outer
    surface, track, thumb, hover and drag colors are provided by
    `--sx-scrollbar-*` without editing the third-party stylesheet.
52. inspect the final project stylesheets for copied grid, filter, form,
    sidebar, header and button palettes; confirm that remaining fixed brand
    values exist only in root variables and that shell selectors consume them.
53. exercise the complete Unify shell in both themes: desktop/mobile header,
    expanded/collapsed sidebar, nested active navigation, quick-access edge,
    dropdowns, theme switcher, drawer and loading overlay; verify that layout
    assets load after `BackendUiAsset`, shell-specific tokens remain scoped to
    Unify and common component colors come from the shared semantic contract.
54. open an autonomous authorization/403 page with no stored choice under both
    OS preferences, then with each stored manual choice; verify the shared
    bootstrap runs before CSS, the preloader/card/form/footer use semantic
    tokens and no light-theme flash or separate pink/white palette remains.
55. inspect a populated user/account grid with several roles in both themes;
    verify each row uses semantic preview/status markup, real and placeholder
    avatars share the media contract and the rendered HTML contains no inline
    color, background or border palette.
56. inspect populated worker-role and storage-file grids in both themes;
    verify role labels use semantic statuses, image and non-image files use
    `sx-preview-card--file`, extension/MIME labels remain readable, shared
    button icons inherit their foreground, and rendered HTML contains no
    inline color, background or border palette.
57. inspect both CMS and CRM worker task calendars with ordinary, planned,
    expired, completed, unfinished and unavailable tasks in both themes;
    verify both widgets register the single `skeeks/cms-backend` presentation
    asset, it loads after `BackendUiAsset`, contains no fixed palette,
    header/drag links have visible keyboard focus, reduced motion disables the
    attention pulse, and rendered HTML contains no inline palette.
58. inspect task workflow actions for accepted, canceled, in-work, check,
    paused and ready states in both CMS and CRM; verify the views use
    `.sx-button` plus one shared semantic variant, their form uses
    `.sx-button-group`, no Unify color helper or local status palette remains,
    and keyboard focus plus foreground contrast work in both themes.
59. inspect an empty and populated CMS activity log plus its comment form in
    both themes; verify the shared CMS activity asset loads once after
    `BackendUiAsset`, comment/log views contain no embedded CSS/JS or inline
    palette, pin/share/value actions use semantic chip/icon classes with ARIA
    state, CKEditor/files/telephony consume shared tokens, copied/highlight
    motion respects reduced-motion preference, CKEditor iframe colors follow a
    live `sx:themechange`, and PJAX keeps handlers active.
60. inspect backend notifications and idle/stale work dialogs in both themes;
    verify `CmsWebNotifyAsset` loads after `BackendUiAsset`, the popup and modal
    surfaces resolve from semantic tokens, browser-permission states remain
    legible, no embedded fixed-color CSS remains and opening/closing does not
    introduce console errors or horizontal overflow.
61. inspect the telephony call panel in both themes for a user with an active
    telephony account; verify `TelephonyAsset` loads its scoped stylesheet
    after `BackendUiAsset`, panel/avatar/status/close states use semantic
    tokens, the panel remains within a mobile viewport and no inline
    fixed-color CSS remains.
62. inspect the current-user schedule control in desktop and mobile-sidebar
    layouts; verify `CmsUserScheduleAsset` loads once after `BackendUiAsset`,
    `data-sx-schedule-ready` appears, a full refresh interval completes without
    console errors, PJAX keeps one timer per container, no view-local CSS/JS
    remains and reduced motion disables the attention animation.
63. inspect personal/profile update, profile summary and all password-change
    variants in admin and UPA in both themes; verify `CmsProfileAsset` owns the
    shared geometry, `CmsProfilePhoneAsset` initializes each marked input only
    once after direct load and PJAX, password reveal/generation is scoped to
    its nearest control, keyboard and ARIA states work, and no copied
    CSS/generator/mask script or inline geometry remains in the views.

When the project provides a safe user-switching route, use it to exercise real
authorized identities and restore the original identity after testing.
