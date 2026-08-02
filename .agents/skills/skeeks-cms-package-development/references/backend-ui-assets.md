# Backend UI assets and payload

Use this reference when changing CSS/JS ownership, backend or cabinet shells,
theme assets, icon providers, or page-level asset registration.

Executable AssetBundle classes remain the source of truth. Measurements below
describe the verified `skeeks.com` backend/UPA state on 2026-07-29 and should be
remeasured after structural changes.

## Current dependency boundary

The shared semantic UI entry point is `skeeks/cms-backend`:

- `BackendThemeAsset` publishes `theme.css`;
- `BackendCoreAsset` owns the product-neutral Yii/SkeekS JavaScript and
  Bootstrap behavior providers;
- `BackendUiAsset` publishes `ui.css` and depends on
  `BackendCoreAsset` and `BackendThemeAsset`;
- `BackendShellAsset` publishes shared content/shell geometry from
  `shell.css`;
- `BackendAppAsset` publishes the common shell application behavior
  (`backend-blocker.js`, PJAX/tooltip/form behavior and data-driven quick
  access) and depends on the shell plus native backend-window compatibility;
- `BackendShellHeaderAsset` and `BackendShellMenuAsset` publish the common
  header and sidebar/menu layers;
- `BackendLegacyIconAsset` is the opt-in Font Awesome bridge for controllers
  not yet migrated to `BackendIcon`; it is compatibility, not the icon
  contract for new UI;
- `BackendAsset` publishes `backend.css` and depends on
  `BackendUiAsset`, the grid asset and the SkeekS JS foundation;
- form and filter bundles depend on the semantic UI but remain
  widget-level assets.

The default backend accent palette is shared by administration and new
cabinet themes. Its verified light values are `#ed7044` accent,
`#d95a32` hover/active, `#10141a` contrast, `#fff4ea` soft and `#f0b9a6`
border. Dark mode uses `#f08a62`, `#ff9d79`, `#3b2b27` and `#84523f`.
Keep these values in `theme.css`, including matching focus/action fallbacks.
A project that wants this standard orange palette must not repeat it in its
theme file; override only genuinely different brand values such as a custom
gradient. Verify the computed palette in both administration and UPA whenever
the shared defaults change.

`BackendUiAsset` is the compatibility entry point. Existing controllers and
widgets may keep depending on it. Do not make consumers register both
`BackendThemeAsset` and `BackendUiAsset`.

Keep the Bootstrap dependency explicit through
`BackendUiAsset -> BackendCoreAsset`. Domain widgets
may register semantic UI before the main layout is rendered; without this
edge, their registration order can publish `theme.css` and `ui.css` before
Bootstrap and let the later base stylesheet overwrite button, form, dropdown
or modal adapters. The active theme may replace Yii's `BootstrapAsset`, but
the resolved base provider must still precede both semantic files.

The administration compatibility graph remains rooted in
`UnifyAdminAppAsset`, but the reusable shell CSS is owned by `cms-backend`.
The ordered chain is:

1. `UnifyAdminAsset` and shared JS/plugin providers;
2. `BackendThemeAsset` and `BackendUiAsset`;
3. `UnifyAdminLegacyAsset`;
4. `BackendShellAsset`;
5. `UnifyAdminThemeAdapterAsset`;
6. optional `BackendShellHeaderAsset` and `BackendShellMenuAsset`.

`UnifyAdminCompatibilityAsset` owns that legacy ordering.
`UnifyAdminCoreAsset` is now only a compatibility composition of
`BackendCoreAsset` and `BackendLegacyIconAsset`; it must not own another copy
of Yii/SkeekS, Bootstrap or icon behavior. Fancybox is not a core shell
provider. The Malihu scrollbar provider is legacy-only and belongs to
`UnifyAdminLegacyAsset`.

`UnifyAdminThemeAdapterAsset` must contain only real Unify/Fancybox bridges.
Shared radii, historical color aliases, neutral background helpers, buttons,
page navigation and quick-access tokens/styles belong to `cms-backend` even
when the Unify adapter happens to load after them. On 2026-07-30 the adapter
was reduced to its Unify variable aliases, Fancybox palette and legacy
`u-side-nav*` dark-mode selectors; its source dropped to 4,842 bytes. Preserve
computed light/dark values before removing a duplicate rule.

The historical opt-in compact Unify chain was:

1. `BackendCoreAsset` plus the opt-in `BackendLegacyIconAsset` bridge;
2. `BackendThemeAsset`, `BackendUiAsset`, `BackendShellAsset` and
   `BackendAppAsset`;
3. `UnifyAdminThemeAdapterAsset`;
4. `UnifyAdminCompactAppAsset`;
5. optional `UnifyAdminCompactHeaderAsset` and
   `UnifyAdminCompactLeftMenuAsset`.

It omits `UnifyAdminLegacyAsset`, `unify-admin.min.css`, remote Open Sans and
HS Admin Icons. Migrated layout/menu markup emits only semantic
`sx-shell-menu*` hooks. Old `u-side-nav*` selectors may remain only in the
temporary compatibility asset for HTML that has not yet migrated; do not emit
both class families from the shared renderer.

The standard administration chain is now:

1. `BackendAdminAppAsset`;
2. `BackendAppAsset` plus the explicit `BackendLegacyIconAsset` bridge;
3. `BackendAdminHeaderAsset` and `BackendAdminMenuAsset`;
4. shared `BackendShellHeaderAsset` and `BackendShellMenuAsset`.

It receives jQuery/Bootstrap/Sortable providers, the native `BackendWindow`,
the semantic theme and shell directly from `cms-backend`. It does not publish
`unify-theme.css` or Unify JavaScript on the standard signed-in shell. The
package dependency and final path-map fallback remain temporarily because
`unauthorized`, `main-empty` and `AuthWidget` are still owned by
`cms-theme-unify-v2`; move those three contracts before removing the
dependency itself.

Only the legacy compatibility chain additionally pulls in the custom
scrollbar, `unify-admin.min.css`, remote Open Sans and HS Admin Icons.

Project assets then add brand/project CSS. On `skeeks.com`, both the normal
administration and client cabinet are verified direct backend consumers.
`ClientPortalTheme` extends
`BackendTheme`, its project path map falls back directly to
`@skeeks/cms/backend/views`, and
`ClientPortalAsset -> BackendAppAsset + BackendLegacyIconAsset`. The rendered
UPA collections publish no Unify CSS or JavaScript. `Blocker`, the shell
application runtime, native action window, jQuery/Bootstrap providers, shared
select field and scroll pager all come from `cms-backend`. Font Awesome
remains an explicit temporary icon-compatibility edge, not an Unify theme
dependency.

`BackendBrandAsset` must contain only brand values and exceptional
project geometry. On 2026-07-29 the `skeeks.com` backend stopped publishing
its historical project `app.css`: 58 of its 71 selectors were already owned
by shared `shell.css`, and the remaining reusable empty-layout, breadcrumb,
task-description and worker-preview contracts were moved into
`cms-backend`. The universal `.sx-now-hide` utility moved into `ui.css`.
The final `.u-header-logo-toggler` adjustment had no matching node in the
rendered administration, hosting or UPA shells and was removed on 2026-07-31.
The brand bundle is intentionally empty until the project has an actual brand
token or exceptional rule; it must not publish a placeholder stylesheet.
Together these changes removed the historical project stylesheet from both
UPA and administration without clearing published assets. Populated UPA
services, the administration task controller, the hosting server collection
and all five true-empty UPA service screens returned `200` without it.

Do not keep a project stylesheet merely because an old layout historically
registered it. Compare its selectors with the shared shell, move only
reusable missing contracts to their owning package, remove the dependency
edge, and verify the final HTML no longer publishes the file. Leave genuinely
project-specific brand rules in the project bundle.

Screen-specific portal CSS must follow the same ownership rule. The
`skeeks.com` client dashboard styles are published by
`ClientPortalDashboardAsset`, which depends on the global
`ClientPortalAsset` and is registered only by `upa-home`. Moving the dashboard,
service-summary, support-card and account-link selectors reduced the global
`client-portal.css` from 36,581 to 32,816 raw bytes; the 3,836-byte dashboard
file is absent from sites, bills and support. Verify both themes after a split
and restore the user's original theme preference when the check is complete.

The GPD and supplier-store detail screens use the same pattern.
`ClientPortalGpdAsset` depends on `ClientPortalAsset` and owns the
`sx-gpd-*`, `sx-store-*` and minimum-quantity presentation. It is
registered by the GPD model actions and the supplier-store card, but not by
their standard collection indexes or unrelated UPA sections. This reduced the
global `client-portal.css` from 32,816 to 25,265 raw bytes; the conditional GPD
file is about 7.6 KB raw. Repeated GPD action styles must be consolidated in
that asset instead of emitting identical per-view `<style>` blocks. The
verified `upa-gpd` and `upa-gpd-store` cards render correctly in both themes,
while both collection indexes omit the conditional stylesheet.

The old `UnifyAdminAppAsset`, `UnifyAdminHeaderAsset` and
`UnifyAdminLeftMenuAsset` class names remain compatibility entry points.
`UnifyThemeAdmin` remains an explicit opt-in subclass for products that still
need legacy Unify layout adapters.

Every backend theme exposes these shell hooks:

- `appAssetClass`;
- `headerAssetClass`;
- `leftMenuAssetClass`.

`BackendTheme` defaults them to shared backend assets. `AdminTheme` defaults
them to the thin `cms-backend-admin` entry points. A compatibility theme must
override all related edges consistently; overriding only the root bundle while
leaving legacy header/menu bundles can pull the old graph back in.

The `cms-backend-admin` administration path map overrides the theme header
view with its own `src/views/layouts/_header.php`. That view must resolve and
register `$theme->headerAssetClass`. Its fallback is
`BackendAdminHeaderAsset`; never hard-code `UnifyAdminHeaderAsset` into the
standard administration view.

Widget-owned compatibility CSS must consume the same semantic variables.
For example, the shared `cms` DualSelect owns `.sx-sortable-list`; its list,
items, borders, hover state and separator use `--sx-color-*` tokens with
legacy color fallbacks. Verify DualSelect inside the filter-settings
`BackendWindow` in both themes: hard-coded white list backgrounds make the
dark-theme labels unreadable even though the drawer itself is correct.

Small model-card data matrices belong to `BackendUiAsset`, not to a shop,
CRM or project view. Use `.sx-data-table-wrapper` plus
`table.sx-data-table` for horizontal price, stock, supplier and similar fact
tables; use `.sx-detail-section` for adjacent characteristics or description
blocks. They deliberately reuse the `--sx-detail-view-*` and canonical
surface tokens so a cabinet can customize the shared palette without another
component-specific token graph. The 2026-07-30 product-card check preserved
the original light white/`#f9f9f9` presentation and resolved dark headers,
cells, borders, empty values and section surfaces semantically.

Remove `cms-theme-unify-v2` from a consumer only after every provider and view
edge is owned elsewhere. The verified order was: move jQuery/Bootstrap,
SelectField, ScrollPager, action windows and selective jQuery UI Sortable into
`cms-backend`; move administration slots/assets into `cms-backend-admin`;
switch the theme class and path map; then remove the Composer dependency.
Keep `UnifyThemeAdmin` available for explicit legacy consumers.

## Verified payload baseline

The measured `upa-support` page contained about:

- 1,331 KB known global CSS, about 166 KB gzip, excluding the unavailable
  local measurement of Font Awesome and the remote Open Sans files;
- 141 KB screen/widget CSS, about 25 KB gzip, for the grid, filters,
  Select2, JQuery UI and related widgets;
- roughly 1.47 MB known CSS total, about 192 KB gzip, before Font Awesome.

The dominant global file was:

- `unify-admin.min.css`: 873,439 bytes, about 93,090 bytes gzip.

Other notable global files were:

- Bootstrap: 153,188 bytes, about 22,975 bytes gzip;
- `theme.css`: 62,516 bytes, about 8,350 bytes gzip;
- project `client-portal.css`: 47,410 bytes, about 7,529 bytes gzip;
- custom scrollbar: 42,839 bytes, about 3,983 bytes gzip;
- shared `shell.css`: 35,506 bytes, about 7,242 bytes gzip;
- `ui.css`: 31,638 bytes, about 5,259 bytes gzip.

A conservative source scan found 6,645 `g-*`/`u-*` classes in
`unify-admin.min.css`, while only 317 were referenced across the inspected
backend, theme and project sources. This is evidence for creating a compact
compatibility layer, not permission to purge selectors automatically:
classes may be supplied by installed packages, saved configuration or dynamic
markup.

After enabling the compact chain for UPA, verified local responses measured:

- empty `upa-support`: 18 CSS files and 538,606 raw bytes;
- populated/manager `upa-support`: 27 CSS files and 643,675 raw bytes;
- create form: 22 CSS files;
- none included `unify-admin.min.css`, HS Admin Icons or remote Open Sans;
- the normal admin task controller still included both legacy bundles.

The subsequently migrated `upa-sites` collection confirmed that screen assets
remain conditional:

- a true-empty customer response loaded 18 CSS and 21 JS files;
- a regular customer with one site loaded 19 CSS and 32 JS files and did not
  render the search widget;
- a showing manager with 50 rendered sites loaded 24 CSS and 55 JS files,
  including filters and manager/grid configuration behavior;
- the populated manager response contained 613,077 raw CSS bytes and 716,517
  raw JS bytes; it still omitted `unify-admin.min.css` and HS Admin Icons.

Do not compare only the largest manager screen with the empty shell. The
request-count difference is expected evidence that Grid, filter, sortable and
manager assets are being registered by their owning widgets rather than by the
cabinet globally.

The switch removes 873,439 raw bytes / about 93,090 gzip bytes from
`unify-admin.min.css` and 20,715 raw / about 3,058 gzip bytes from HS Admin
Icons, plus the external font request. The semantic icon/avatar/menu additions
were about 2.6 KB raw. Remeasure after further component migrations.

## Global versus conditional assets

Keep globally only what every shell render needs.

The following are screen/widget assets and should stay registered by their
own widgets:

- grid and backend collection behavior;
- search and filter forms;
- Select2 and Krajee adapters;
- JQuery UI sortable/theme;
- context menus;
- file input and specialized editors.

Keep legacy vendor CSS that belongs to only one such widget out of the global
backend payload. Put its semantic adapter in the same widget asset after the
vendor file, map it to backend variables with safe legacy fallbacks, and test
the open state in both themes. `DaterangeInputWidgetAsset`, for example, owns
the daterangepicker popup adapter for its surface, arrows, calendars, range
options and active/in-range states through `--sx-calendar-*`, form and accent
tokens.

Current Select2 renders the inline search of a multiple selector as a
textarea. The backend form adapter must override the ordinary textarea minimum
height for that internal field: an empty selector and a one-chip selector stay
at `--sx-form-control-height`, while additional chips wrap and grow the
selection container by content. Verify all three states and page overflow.

Active legacy Chosen consumers are covered by the same shared form boundary.
Keep their semantic adapter in `BackendFormAsset`, conditional on
`html[data-sx-theme]`, and cover both `sx-backend-form` and historical
`sx-form-admin` markup. The adapter must outrank the late
`chosen.bootstrap.min.css` asset and map the closed control, open dropdown,
inline search, results/highlight, multiple tags, focus and disabled states to
`--sx-form-*`, `--sx-color-*` and popup-shadow tokens. Verify closed, open and
selected-tag states in both themes on a real legacy consumer; do not move
Chosen styling into a project theme or require old forms to change markup.

`ScrollAndSpPager` must not register its IAS and simple-pagination bundles
when `pagination.pageCount <= 1`. The parent `kop\y2sp\ScrollPager` registers
IAS during `init()`, so the SkeekS subclass guards both `registerAssets()` and
`run()`. On the verified one-site cabinet this reduced the response from
19 CSS / 32 JS files to 18 CSS / 23 JS files, removing 6,204 raw CSS bytes and
about 50,230 raw JS bytes. A two-page collection retained IAS,
`AjaxLinkPager`, the pagination container and page-two rendering.

Keep the historical `grid-view` class on every standard grid in addition to
the semantic `sx-grid-view`, `sx-backend-grid` and `sx-collection-view`
classes. The installed IAS integration still targets selectors such as
`.grid-view tbody` and `.grid-view .pagination`; replacing rather than
augmenting that class silently removes the `Показать ещё` trigger while the
table itself continues to render. On 2026-07-29 this contract was verified in
both the administration task list and the UPA sites collection: a trigger
loaded the next page without a full navigation.

`BackendGridModelAction` already avoids rendering the filter widget when a
small list has no active filters and the current user does not manage backend
showings. Preserve this behavior: an unrendered widget must not register its
asset bundle.

`backendShowings=false` is an explicit opt-out, including for grids embedded
inside another model action. The shared grid view must neither render the
saved-showing tabs nor offer creation of a showing in that state. Guard both
the creation control and the tab iteration; do not rely on casting `false` to
an array after the display condition has already become true.

Fancybox is conditional and must be registered by the feature that actually
calls its API. Do not add it back to `UnifyAdminCoreAsset`. Known intentional
owners include the hosting legacy application, cashier, opt-in CMS toolbar
window, `UnifyHsPopupAsset`, Form2 popup flows and image/gallery views. A view
that calls `$.fancybox`, `.fancybox()` or emits `data-fancybox` behavior must
register `FancyboxAssets` itself; the shop collection and CMS content-element
gallery views are reference examples.

The separate legacy `cms-backend-admin\AdminAsset` still owns Fancybox
globally. Treat that package as an explicit migration boundary and verify its
generic previews/windows before removing the dependency there. Historical
Fancybox `preload` settings apply only to those remaining Fancybox flows; the
native BackendAction drawer does not use that option.

PJAX, filter and ordinary AJAX waiting states are owned by
`sx.classes.Blocker`, not by Fancybox. Theme their shared `.blockOverlay` in
`cms-backend` through `--sx-loading-overlay-background`,
`--sx-loading-indicator-color` and
`--sx-loading-indicator-track-color`. The overlay must remain translucent
enough to preserve page context, include one centered activity indicator,
support both themes and respect `prefers-reduced-motion`. Its background sweep
is a shared left-to-right progress cue; do not replace it with a project-level
opaque blocker or a fixed light overlay.

The Malihu custom scrollbar has already moved from `UnifyAdminCoreAsset` to
`UnifyAdminLegacyAsset`. Compact cabinets use native overflow while old admin
markup keeps the plugin. The removed compact payload is 84,905 raw bytes /
16,561 gzip bytes across its CSS, plugin JS and HS wrapper. When removing the
plugin from a compact shell, explicitly verify that long sidebars retain
`overflow-y: auto` and horizontal model-action rows retain native
`overflow-x: auto`.

## Icon providers

Do not keep an icon-font bundle globally for one or two legacy glyphs.

Use `skeeks\cms\backend\helpers\BackendIcon` for new reusable backend controls.
It renders small semantic inline SVG icons with `currentColor`, an accessible
label when requested and no font or additional request. The shared registry
currently covers search, settings, download, expand, plus, close and vertical
movement. Extend the registry by semantic name instead of pasting view-local
SVG markup.

The saved-view tabs, filter search/settings controls and grid
download/settings/fullscreen controls are verified consumers. The expanded
filter editor also uses semantic SVG controls for reorder, remove and add;
do not restore Font Awesome there. Keep their hit-target geometry in shared
component CSS (`sx-icon-action` or the owning filter/grid control), not in the
SVG helper. Event handlers must accept clicks whose target is the nested SVG
or path.

## Navigation surfaces

Saved collection views and model-action navigation no longer depend on
Bootstrap `nav-tabs`. Use their semantic markup directly:

- collection showings: `sx-backend-showing-tabs`, `sx-tab`,
  `sx-showing-tab`;
- model actions: `sx-nav-model`, `sx-nav-model__item`,
  `sx-nav-model__link`.

This avoids Bootstrap borders and active-state geometry clipping wrapped
buttons. The backend default for model actions is one white rounded surface
with calm links and a fully rounded soft-accent active item. Icons and labels
use a shared flex gap. This is a `cms-backend` default for administration and
client cabinets; a project may change tokens, but should not recreate the
component geometry.

When embedding a standard controller action inside another model card, obtain
the required action directly with `$controller->createAction('index')` or
`$controller->createAction('create')`. Do not read it from
`$controller->actions`: model controllers intentionally expose model actions
through a separate collection, so that getter can omit `index` and `create`
and turn a valid nested tab into an access error.

The `skeeks.com` backend/UPA shell stopped registering
`UnifyIconSimpleLineAsset` globally after the remaining backend menu and
dashboard `icon-*` usages were migrated to the already loaded Font Awesome
provider. This removes:

- `simple-line-icons.css` (13,761 bytes, about 2,755 bytes gzip);
- the corresponding font preload;
- the 30,064-byte Simple Line WOFF2 transfer.

When removing another icon provider:

1. search PHP configuration and views, not only CSS;
2. migrate menu icon values and dynamically generated class strings;
3. register the old provider locally if an isolated legacy screen still
   needs it;
4. verify missing-glyph placeholders in both the admin and UPA shell.

Font Awesome is still widely used by legacy controller actions, navigation,
status controls and installed widgets. Do not remove its global bundle merely
because the first migrated collection page renders correctly. Treat its
removal as a separate payload stage:

1. inventory PHP configuration, generated class names, views and JavaScript;
2. migrate shared controls to `BackendIcon`;
3. move the old provider to narrow legacy bundles for remaining screens;
4. verify administration and UPA shells, simple and multi-action controllers,
   forms, drawers and third-party widgets;
5. remove the global bundle only after the compatibility usage reaches zero.

The 2026-07-29 source baseline found Font Awesome-related strings in 25 files
under `cms-backend`, 34 under `cms-theme-unify-v2` and 98 under `cms`, plus 52
project PHP files across the inspected `common` and `frontend` trees. The
published Font Awesome CSS was 59,305 raw bytes. A modern browser can select
the three WOFF2 files (brands, regular and solid), which together were 168,224
raw bytes; do not sum every EOT/TTF/WOFF/SVG fallback listed in the stylesheet
as if one browser downloads all formats. These counts justify migration, but
also show that removal is not yet a safe one-screen change.

The Fancybox-to-BackendWindow migration preserves the complete BackendAction
contract: right drawer and overlay, close behavior, browser history/URL state,
nested actions, PJAX and form submissions, responsive/mobile behavior and
light/dark synchronization between the parent document and iframe.

`BackendWindowAsset` in `skeeks/cms-backend` publishes
`backend-window.css`/`backend-window.js` and exposes
`sx.classes.BackendWindow`. Production shells also register
`BackendWindowCompatibilityAsset`, which maps `sx.classes.Window` to the native
implementation. Existing callers therefore keep using the standard
`sx.classes.backend.widgets.Action(...).go()` path; controller/view code must
not instantiate Fancybox or a project-specific drawer.

The isolated 2026-07-30 smoke test verified:

- a right-side same-origin iframe drawer without a `.fancybox-container`;
- opener widget events and closing from the iframe;
- two nested drawers with independent z-order and stack offset;
- restoration of page overflow and opener focus after the final close;
- semantic light and dark canvas/button colors;
- no JavaScript console errors from the native asset.

The close control belongs to the drawer shell, not to iframe content. On
desktop it is placed completely outside the left edge of its panel: the
button's right edge touches the panel's left edge, so it never covers the
action header or model content. Every nested drawer moves a small step to the
right, becomes correspondingly narrower and carries its own close control with
it. On narrow/mobile viewports the control must move inside the panel so it
cannot leave the screen. The production compatibility check must cover:

- the first and second drawer geometry;
- `Escape` closing only the top drawer;
- backdrop closing the remaining drawer;
- body scroll restoration after the stack becomes empty.

The production switch was verified on 2026-07-30 on ordinary
`upa-support` and administration task pages: neither page loaded Fancybox CSS
or JavaScript; a real support-task click opened one native iframe drawer with
no `.fancybox-container`. Continue regression checks for outside-click,
Escape, history/back-forward restoration, create/update form success, PJAX
refresh, mobile width and representative object-card links.

Old `.sx-fancy-container` rules and historical Window sources may remain while
the separate legacy shell is supported. Remove them only after a usage check
shows that no retained Fancybox/legacy window flow consumes them.

Fontello is not part of the backend Font Awesome contract. The historical
`FontAwesomeAsset` also loads `FontAwesomeIconsAsset`, whose separate font
contains only the compatibility glyphs `fa-max`, `fa-dzen` and `fa-rutube`.
Keep that public asset behavior for site themes and social links, but make
backend shells depend on `FontAwesomeCoreAsset`. The core bundle owns
`all.min.css` and the three Font Awesome preloads without the optional
Fontello dependency.

This boundary was verified on 2026-07-30:

- no `fa-max`, `fa-dzen` or `fa-rutube` usage existed in `cms-backend`,
  the Unify admin shell, `skeeks/cms` backend sources or the inspected
  `skeeks.com` UPA/backend sources;
- UPA home, populated UPA support and the admin task controller retained
  Font Awesome and omitted `fontello.css`;
- UPA and administration were checked in both themes and restored to the
  original dark preference;
- the public `FontAwesomeAsset` still depends on `FontAwesomeIconsAsset`.

The split removes one stylesheet request and 1,905 raw / about 857 gzip bytes
from every backend shell response. The 3,068-byte Fontello WOFF2 can also no
longer be requested by backend markup; browsers previously fetched it only
when one of its three glyphs was actually rendered.

## Verify the active presentation owner before splitting CSS

A controller converted to `BackendModelStandartController` may no longer render
its historical project view even when that view remains in the repository.
Do not register a route-specific asset in a view merely because its selectors
and markup names match the route. First inspect the actual response DOM and the
controller action configuration.

The UPA support route is the reference case. Its active collection is configured
by `UpaSupportController::actions()` and renders the shared semantic
`sx-collection-cell*` contract. The old
`frontend/views/upa-support/index.php` still contains
semantic `sx-*` fallback markup, but is not the owner of the current index
response. Registering an asset there produces no stylesheet in the live page.

Once the active DOM was verified, the obsolete support-only selectors were
removed from global `client-portal.css` instead of being moved into another
bundle. This reduced the global file from 25,265 to 23,127 raw bytes without
adding a request. The populated support collection retained its 42px circular
entity media, compact stacked title/caption and client presentation in both
light and dark themes.

For every proposed route split:

1. inspect the live DOM and loaded stylesheets;
2. identify the PHP action/widget that produced that DOM;
3. search selectors outside published `web/assets`;
4. distinguish an active route owner from an archived view;
5. only then choose between conditional ownership and deletion.

The same rule applies to project branding bundles. On skeeks.com,
`BackendBrandAsset` is now an empty extension point: the last 223-byte
`custom-theme.css` targeted only the retired `.u-header-logo-toggler` and had
no live administration, hosting or UPA consumer. `ClientPortalAsset` therefore
does not depend on it. The project `BackendAppAsset` depends directly on
`BackendAdminAppAsset` plus this optional brand hook, never on
`UnifyAdminAllAsset`; an empty hook must not create a request.

Empty-layout model cards are another conditional layer. The UPA backend
`beforeRun` hook can inspect
`BackendUrlHelper::setBackendParamsByCurrentRequest()->isEmptyLayout` before
rendering. Register `ClientPortalActionAsset` only when that flag is true.
The action asset depends on `ClientPortalAsset` and owns the `body.sx-empty`
model title, action tabs and drawer spacing. Keep the corresponding
`body:not(.sx-empty) .sx-content-model-actions` rules in the global file for
full-page model presentations.

On skeeks.com this split reduced global `client-portal.css` from 23,127 to
22,201 raw bytes (3,847 gzip). The 1,589-byte action file (526 gzip) is absent
from collection/dashboard responses and present on direct view/create URLs
with the empty-layout backend parameters. Verify at least one object card and
one create form in both themes; checking the list alone cannot validate this
contract.

Small route-specific compatibility fixes follow the same pattern. The
Krajee datepicker adjustments for `.field-user-birthday_at` belong to
`ClientPortalProfileAsset`, not to every cabinet page. The UPA backend
`beforeRun` event fires before the current controller is always available, so
project routing conditions at that point must use the stable request
`pathInfo` (for example `/upa-personal/`) rather than assuming
`Yii::$app->controller->id` is already set.

This profile split reduced global `client-portal.css` again from 22,201 to
20,701 raw bytes (3,591 gzip). The 1,499-byte profile file (494 gzip) is loaded
only by `upa-personal` routes. Verify the birthday input padding and both
42px calendar/remove controls in light and dark themes after changing the
route condition.

## Target ownership

The package boundary is:

- `skeeks/cms-backend`: theme tokens, semantic controls, collection/grid/list
  presentation, forms, filters and the reusable backend/cabinet shell,
  including header/menu CSS, menu behavior and semantic menu renderers;
- `skeeks/cms-theme-unify-v2`: temporary Unify compatibility and legacy
  adapters while old markup is being migrated; it is not a target dependency
  for the semantic shell and must not receive new reusable UI behavior or
  renderers;
- project: brand token values, client-specific shell composition and genuinely
  unique product blocks.

Moving a file between packages without changing the dependency graph does not
reduce payload. First establish the owning asset, then migrate consumers, then
remove the compatibility dependency.

## Shared shell geometry

Keep shell structure in `cms-backend` and parameterize product differences
through these root variables:

- `--sx-shell-top-offset`;
- `--sx-shell-sidebar-width`;
- `--sx-shell-main-end-gutter`;
- `--sx-shell-content-max-width`;
- `--sx-shell-content-padding`;
- `--sx-shell-main-bottom-padding`;
- `--sx-shell-mobile-content-top-padding`.

The default values preserve the administration geometry. A compact cabinet
may override them from its brand/theme variable asset without copying the
shared `.sx-main`, `.sx-main-col`, `.sx-content-wrapper`, `.sx-sidebar` or
`.sx-sidebar-inner` implementation. Set the main end gutter to zero when the
cabinet does not render the administration quick-access rail; otherwise the
historical `64px` reservation remains as empty space.

The 2026-07-30 runtime check on `skeeks.com` verified the same token contract
on `/~upa/upa-support` and `/~sxx/cms/admin-cms-task/index`. UPA resolved to a
`64px` top offset, `264px` sidebar, `1440px` content maximum and no end gutter;
administration retained `65px`, `250px`, no content maximum and a `64px`
quick-access gutter. Both pages switched dark to light and back to the stored
dark choice without horizontal overflow or console errors.

Treat the current `skeeks.com` UPA visual presentation as a regression
baseline while moving shell ownership into `cms-backend`. This stage is an
architectural refactor, not a redesign. Preserve:

- the dark `64px` header, logo/cabinet caption and right-side action order;
- the white `264px` sidebar, calm menu density, soft-accent active item and
  bottom help block;
- the `1440px` centered content canvas, heading hierarchy and whitespace;
- the search/filter row, raised collection surface, compact table density,
  status chips and branded primary action;
- current light/dark colors, radii, borders, shadows and responsive behavior.

Shared renderers may replace project partials and CSS only when before/after
screenshots and computed geometry show that the visible result remains
equivalent. Any intentional visual change is a separate product decision and
must not be bundled into shell extraction.

## Backend theme and outer layout ownership

The reusable theme PHP contract is
`skeeks\cms\backend\themes\BackendTheme`. It owns:

- `themeMode`, its storage key, client-selection permission and normalization;
- the transitional `color_scheme` alias for existing configurations;
- root, header and menu asset hooks;
- logo title/source/link hooks;
- semantic header and sidebar modifier hooks;
- the default path map entry for `@skeeks/cms/backend/views`.

Its default root bundle is `BackendAppAsset`, so a new product theme receives
the semantic UI, shell and common application behavior without choosing an
Unify application asset. Legacy icons remain opt-in.

Product themes should extend this class directly when they no longer require
legacy providers. The standard administration uses
`skeeks\cms\admin\themes\AdminTheme`, which directly extends it and adds only
administration assets/slots and the `AdminSelectField` compatibility alias.
`UnifyThemeAdmin` extends it only to supply temporary Unify asset defaults,
default logo and remaining legacy-only mappings.
Do not add another copy of theme-mode or shell properties to an Unify or
project subclass.

Project administration themes should subclass `AdminTheme`, point
`appAssetClass` at a thin project bundle whose first dependency is
`BackendAdminAppAsset`, and add only an optional brand asset after it. A
module-level `beforeRun` hook that no longer emits Unify markup must instantiate
that project theme and call its inherited `initBeforeRender()` directly; do
not compensate by registering an Unify-dependent project bundle during
`EVENT_END_BODY`. On 2026-07-31 the skeeks.com hosting module moved to this
path with unchanged 21 CSS / 51 JS response counts, zero legacy class tokens
and zero horizontal overflow in both themes. Legacy CRM modules are outside
this migration and must remain untouched.

`BackendTheme::initBeforeRender()` owns the product-neutral runtime provider
bootstrap. It maps Yii jQuery/Bootstrap bundles to `BackendJqueryAsset`,
`BackendBootstrapAsset` and `BackendBootstrapPluginAsset`, selects
`BackendSelectField` and `BackendSortableWidget`, maps Bootstrap 3
form/alert/modal entry points to the supported Bootstrap 4 implementations
and normalizes LinkPager classes. `BackendSortableWidget` owns the selective
jQuery UI asset formerly supplied by the Unify theme; old Unify widget/asset
class names are thin compatibility subclasses.
`UnifyThemeAdmin::initBeforeRender()` must call the parent and add only
historical compatibility mappings. Product themes must not duplicate this
provider configuration.

The common document and content composition is
`@skeeks/cms/backend/views/layouts/main.php`. The historical Unify
`layouts/main.php` is a thin view alias only. Active backend/cabinet path maps
must search in this order:

1. product or administration slot overrides;
2. `@skeeks/cms/backend/views`;
3. temporary Unify views only in an explicitly selected compatibility theme.

The common layout exposes `data-sx-shell-layout="backend"` on the document
root as a runtime ownership marker. It registers the configured root asset,
pre-registers shared header/menu assets before `<head>`, applies the no-flash
theme bootstrap, composes the shared sidebar and common controller/model
action regions, and renders product slot views for brand/context/actions and
exceptional integrations.

The outer shell flex geometry is semantic: `sx-main-wrapper` and
`sx-main-col` own display, sizing and zero-gutter behavior. Do not add
Bootstrap `row`, `no-gutters` or `col` back to the common layout. The
2026-07-30 migration preserved the exact desktop rectangles on both reference
pages: UPA `264px + 1001px`, administration `250px + 1015px` at a `1265px`
viewport, with zero horizontal overflow.

Optional product context before the shell columns uses
`sx-shell-context-bar`, `__link` and `__actions`. Its structure, responsive
stacking, palette and focus state belong to `shell.css`; a project supplies
only destination, label and domain actions. Do not rebuild this strip with
Unify `u-side-*`/`g-*` utilities or inline spacing. The `skeeks.com` VPS
branch and generic return-to-cabinet branch were migrated to this contract
while the ordinary UPA branch continued to render only its navigation
backdrop.

`@skeeks/cms/backend/views/layouts/_menu.php` is the default semantic menu
slot. It resolves the configured menu asset hook and delegates recursive HTML
to `BackendShellMenuWidget`. The old Unify `_menu.php` view is an alias only.

The backend package also owns product-neutral defaults for `_header.php`,
`_breadcrumbs.php`, `_before-content.php`, `_before-menu.php`,
`_after-menu.php`, `_container-begin.php`, `_modals.php` and `_end-body.php`.
The default breadcrumbs contain no hosting, CRM or other product branches.
Administration-only mobile schedule markup lives in
`cms-backend-admin/views/layouts/_before-menu.php`. Product path-map entries
may override these slots, but theme packages must not become their functional
owner.

Administration quick-access data/markup and SkeekS administration footer
content live in `cms-backend-admin/views/layouts/_quick-access.php` and
`_footer.php`. The shared quick-access behavior and footer frame remain in
`cms-backend`. Historical Unify views are aliases only. Use
`data-sx-slot-owner="cms-backend-admin"` as the runtime ownership marker when
verifying the active administration path map.

Backend components must call `initBeforeRender()` on the instantiated theme
class (`$theme::initBeforeRender()`), not hard-code
`UnifyThemeAdmin::initBeforeRender()`. The base method installs the
backend-owned product-neutral providers; compatibility subclasses may add
only their legacy providers. This allows a backend theme to operate without
loading Unify bootstrapping.

The 2026-07-30 runtime migration verified both
`/~upa/upa-support` and `/~sxx/cms/admin-cms-task/index` on the backend-owned
layout. Both retained their desktop geometry, stored dark mode, shared font
stack and zero horizontal overflow. The UPA mobile shell was also verified at
375px: the sidebar moved from `-264px` to `0`, updated `aria-expanded`, closed
on Escape and produced no horizontal overflow. The active sidebar/menu HTML
contained no `u-*`, `u-side-*` or `g-*` class tokens.

After the shell application move, both pages published
`backend-blocker.js`/`backend-app.js` from `cms-backend` and no longer
published the historical Unify copies. The administration quick-access panel
was opened and closed through the moved runtime; both pages retained dark
mode, zero legacy class tokens and zero horizontal overflow. Font Awesome
compatibility was also resolved through `BackendLegacyIconAsset` while the
old Unify class remained a thin inheritance alias.

After direct UPA detachment, `ClientPortalTheme` published no
`unify-theme.css` and no Unify JavaScript. The populated support sample
dropped to 24 CSS and 47 JavaScript requests while retaining the exact
`64px` header and `264px` sidebar geometry.

The verified reference-cabinet presentation now lives in
`cms-backend\BackendCabinetAsset`. It is opt-in over `BackendAppAsset` and
owns the semantic client header/profile, sidebar/menu/help, content frame,
footer, responsive drawer and standard cabinet page adapters. A project
cabinet asset depends on it and normally publishes only its token/brand file
plus exceptional product screens. Do not copy the reference shell back into
`client-portal.css`; change reusable cabinet behavior in
`BackendCabinetAsset` and customize geometry or branding through `--sx-*`.
The `skeeks.com` migration removed the 20 KB project `client-portal.css` from
the active asset graph and retained only `client-portal-theme.css`.

Legacy service-status renderers must emit
`sx-cabinet-service-status` with semantic success/danger/info modifiers.
Do not preserve `u-tags-v1`, `g-bg-*` or inline status colors in a project
controller merely to match the reference cabinet; the shared cabinet asset
owns this adapter and maps it to the common status tokens.

After direct administration detachment,
`cms-backend-admin\AdminTheme -> BackendTheme` published no Unify CSS or
JavaScript. The populated task reference loaded 26 CSS / 64 JavaScript
resources, retained its exact `65px` header, `250px` sidebar, Inter stack,
stored light/dark choice and zero horizontal overflow. Quick access still
opened/closed through `BackendAppAsset`. The live Sortable consumer published
the backend-owned selective jQuery UI modules.

The standard guest administration path is also independent of Unify.
`cms-backend-admin` owns `unauthorized.php`, `main-empty.php`, its
administration-specific `AuthWidget` and the small guest asset. The layout
uses the same pre-CSS theme bootstrap and `BackendThemeAsset`, so a stored
light/dark choice is applied before paint. Authentication endpoints and state
remain in `skeeks/cms`; only guest-screen composition and client behavior live
in `cms-backend-admin`. Do not move this widget into product-neutral
`cms-backend` while it knows CMS authentication providers and routes.

After migrating these views, `cms-backend-admin` has neither a Composer edge
to `cms-theme-unify-v2` nor a Unify path-map fallback. A clean guest browser
test verified the authorization page with zero Unify assets, zero
`u-*`/`u-side-*`/`g-*` class tokens, no console errors, working phone/email
switching and password visibility, and persisted dark mode. Old Unify theme,
compact asset and Sortable class names remain compatibility entry points only
for explicitly selected legacy themes and must not be dependencies of the
standard admin package.

The post-migration cross-check also covered populated standard collections
`/~upa/upa-sites` and `/~sxx/cms/admin-cms-company/index`, plus the real
empty-layout create URLs produced by both controllers. All four used the
backend layout marker and shared font, had no horizontal overflow, emitted no
legacy class tokens and produced no console errors. Both create forms retained
readable semantic surface/input colors in light and dark modes; empty layouts
correctly omitted the header and sidebar. Restore the stored user theme after
this matrix.

## Safe migration sequence

1. Inventory the actual AssetBundle graph and raw/gzip file sizes.
2. Separate theme foundation from semantic UI while preserving the old public
   entry point.
3. Replace isolated legacy icon/font dependencies.
4. Move reusable header/sidebar/content geometry into a backend shell asset.
5. Replace `g-*`, `u-*` and `u-side-*` classes in shared layouts and widgets
   with semantic `sx-*` classes.
6. Keep a temporary Unify compatibility asset for unmigrated packages.
7. Use compact asset hooks only for explicitly verified shells. Keep legacy
   compatibility opt-in and outside the standard administration dependency
   graph.
8. Remove a mandatory Unify dependency only after testing simple and
   multi-action controllers, empty/populated lists, filters, modals and
   light/dark themes, plus the guest authorization and lock screens.

The verified local matrix includes the compact UPA support and sites standard
controllers plus the legacy admin task and company standard controllers.
Check both themes for hard-coded white surfaces, keep the legacy admin
scrollbar wrapped, and confirm the compact shell does not publish Malihu.
Before interacting with the admin theme switch, dismiss idle-work or other
system modals: they may transparently cover the fixed header while the theme
button itself remains visible in the DOM.

Do not clear published assets as part of this workflow when timestamped asset
URLs already invalidate changed files.

## Semantic markup boundary

New and migrated backend shell HTML must expose only the SkeekS semantic
contract. Do not emit Unify utility or navigation classes such as `u-*`,
`u-side-*` or `g-*` from the shared header, sidebar, menu, content or footer
renderers. The same rule applies to project-era namespaces such as
`portal-*`: a migrated cabinet may use product-specific `sx-cabinet-*`,
`sx-gpd-*` or another precise `sx-*` component name, but its live HTML and
active CSS selectors must not keep a parallel non-semantic shell vocabulary.
Asset class and file names may remain stable compatibility entry points; the
restriction is about rendered classes and selector ownership.

The shared sidebar menu renderer is
`skeeks\cms\backend\widgets\BackendShellMenuWidget`. It consumes
`BackendMenuItem[]`, owns the recursive `sx-shell-menu*` HTML, registers
`BackendShellMenuAsset` and keeps submenu state in `--open` plus
`aria-expanded`. A legacy theme `_menu.php` may register its compatibility
bundle and delegate to this widget, but must not duplicate the renderer.

When `activeBranchesOnly` omits an inactive submenu from the DOM, the parent
link must not expose `data-sx-shell-menu-target`, `aria-controls` or
`aria-expanded` for that nonexistent node. It remains a normal navigation
link and reveals its children after navigation activates the branch. Emit the
toggle contract only when the corresponding `<ul>` is actually rendered.

The outer sidebar frame is owned by
`skeeks\cms\backend\widgets\BackendShellSidebarWidget`. It renders the
semantic `<aside>` and the `beforeMenu`, `menu` and `afterMenu` slots. The
widget always supplies `sx-sidebar sx-shell-sidebar`; a product theme may
return only an additional `sx-shell-sidebar--*` modifier from its historical
`slideNavClasses` hook. Do not return Bootstrap grid classes, `u-sidebar-*`
classes or `js-scrollbar` from a migrated theme.

The shared sidebar uses native vertical overflow and the
`--sx-shell-sidebar-*` token family for background, text, muted/icon, border,
opened, active and special states. Keep `--sx-unify-sidebar-*` only inside the
temporary compatibility adapter for old HTML. The active UPA and
administration renderers must not depend on those aliases or on the Malihu
scrollbar plugin.

The shared header frame is
`skeeks\cms\backend\widgets\BackendShellHeaderWidget`. It owns the semantic
`<header> / surface / nav` structure and exposes `brand`, `context`, `actions`
and terminal `profile` slots plus option arrays for product modifiers. The
profile slot is appended inside the actions region without another structural
wrapper, so extracting account composition does not change established flex
geometry. Product themes should keep `_header.php` as orchestration and render
focused `_header-brand`, `_header-context`, `_header-actions` and
`_header-profile` partials; they must not duplicate the outer header structure.
The verified `skeeks.com` UPA and standard administration both use this
composition while preserving their 64px and 65px visual baselines.

Compact header dropdowns use the shared `sx-shell-header__menu`,
`sx-shell-header__menu-item` and `sx-shell-header__menu-separator` contract.
Use it for quick-create and account/profile menus instead of keeping separate
admin and cabinet padding, width, hover and mobile positioning rules. Menu
icons use semantic `BackendIcon` SVGs at the same size. The 2026-07-30
cross-check verified 34px items in admin quick-create, admin profile and UPA
profile menus, full-width mobile popovers with 8px viewport insets, both
themes, zero horizontal overflow and no `portal-*`/Unify class tokens in UPA.

`BackendShellHeaderAsset` owns the mobile navigation behavior through
`data-sx-shell-nav-toggle` and `data-sx-shell-nav-backdrop`. The shared script
updates `aria-expanded`, the `sx-shell-nav-open` body state and the sidebar
`active` compatibility state, and closes on Escape. Do not register separate
inline jQuery click handlers in UPA or administration headers.

The shared footer frame is
`skeeks\cms\backend\widgets\BackendShellFooterWidget`. Products pass footer
content plus a semantic modifier; UPA uses its normal-flow portal footer and
administration uses `sx-shell-footer--sticky`. The old
`u-footer--bottom-sticky` class must not be emitted by migrated layouts.

The active `cms-backend-admin` header and the administration footer must also
keep their internal shell markup semantic. Use
`sx-shell-header__mobile-button`, `sx-shell-header__avatar`,
`sx-shell-header__label`, `sx-shell-hidden-*` and
`sx-shell-footer__*` contracts instead of Unify position, visibility, spacing
or color utilities. On 2026-07-30 the rendered administration header,
sidebar/menu and footer contained zero `u-*`, `u-side-*` or `g-*` class
tokens. Footer link color, 14px typography and list spacing remained equal to
the pre-migration computed baseline.

Task/work-state rows use `sx-row-in-work`. Keep `.g-bg-in-work` only as a CSS
compatibility alias for markup emitted by older installed packages; new PHP
views and JavaScript must add the semantic class. The migrated administration
task page rendered its active row with `sx-row-in-work` and contained no
remaining `u-*` or `g-*` tokens anywhere in the page DOM.

Administration dashboard panels use `AdminPanelAsset` and the
`sx-admin-panel*` contract. `AdminPanelWidget` must not emit Unify
`g-*`/`u-link-*` utilities or recreate them in page-local CSS. Keep panel
geometry in the widget-level asset so ordinary backend pages do not download
dashboard CSS. The verified dark/light dashboard emitted zero legacy class
tokens and retained `3px` radius, `20px` spacing and responsive
`20px 30px 15px` header padding.

Before removing an old class from live markup, move every required geometry,
responsive state and interaction hook to an equivalent `sx-*` selector and
verify the current appearance. Keep old selectors only in a temporary
compatibility adapter for screens whose HTML has not yet migrated; do not keep
both class families indefinitely on newly rendered nodes. JavaScript must use
stable `data-sx-*` attributes or semantic `sx-*` hooks rather than Unify class
names.

On `skeeks.com`, the visual reference for this cleanup is the existing UPA
shell, not a redesign. The header, sidebar, footer, profile menu, help block,
dashboard, standard data page and project GPD screens were migrated from
`portal-*` to `sx-shell-*`, `sx-cabinet-*`, `sx-page-*`, `sx-gpd-*` and
`sx-store-*` while retaining their current geometry and both themes. Runtime
checks on sites, support, orders, profile, dashboard and supplier-store index
reported zero `portal-*` class tokens.

## Typography tokens

The default backend/cabinet font stack is exposed as
`--sx-font-family-base` and currently matches the verified `skeeks.com` UPA:
`Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`.
`shell.css` applies this token to the document and makes buttons, inputs,
selects and textareas inherit it. Project CSS may replace the token for a
genuinely branded typeface; it must not repeat a body-and-controls selector.

The default stack does not require a bundled or remote webfont. Do not add a
large mandatory font payload merely to expose the `Inter` preference: the
system fallbacks are part of the contract. On 2026-07-30 the UPA retained its
existing computed family for body, heading, menu, controls and grid, while the
administration moved from legacy Open Sans to the same shared stack.

## Theme mode ownership

The reusable light/dark mode contract belongs to `skeeks/cms-backend`:

- `BackendThemeAsset` publishes the single shared `theme-mode.js`;
- `BackendThemeModeSwitcher` owns the semantic switcher markup;
- `@skeeks/cms/backend/views/layouts/_theme-mode-bootstrap` applies the
  persisted mode before stylesheet links are parsed, preventing a flash of the
  wrong theme;
- the document root exposes `data-sx-theme`, `data-sx-theme-mode`,
  `data-sx-theme-source` and `data-sx-theme-storage-key`.

Render the no-flash bootstrap inside `<head>` before `$this->head()` and before
any stylesheet links. A shell response must publish exactly one active
`theme-mode.js`. Do not add project or Unify copies of the switcher behavior.

The historical Unify `ThemeModeSwitcher` class and
`_theme-mode-bootstrap.php` view remain thin compatibility aliases only.
They must delegate to `cms-backend`; no new theme-mode code, tokens or markup
belongs in `cms-theme-unify-v2`. Unauthorized layouts follow the same rule and
receive the shared script through `BackendThemeAsset`.

When verifying persistence, switch both UPA and administration from the
stored mode to the opposite mode, reload, confirm
`data-sx-theme-source="user"`, then restore the user's original choice. Check
that the inline bootstrap appears before the first stylesheet and that both
responses contain one shared theme script.

## Manual controller-action links

`BackendModelAction` and the standard backend action widgets register their
own JavaScript assets. Client-facing Grid/List cells may hide
`ControllerActionsColumn` and manually emit a call such as:

```php
new sx.classes.backend.widgets.Action({...}).go();
```

`BackendGridModelAction` therefore owns and registers
`skeeks\cms\backend\widgets\assets\ControllerActionsWidgetAsset` for its
standard collection contract. Do not repeat this registration in every
project controller. A completely custom action/view outside
`BackendGridModelAction` must either register the bundle itself or use a
standard backend action presentation. Verify both the published
`controller-actions-widget.js` script and
`typeof sx.classes.backend.widgets.Action === "function"` before testing the
drawer click.

## Collection density and hierarchy

Keep the visual role of a cell distinct from its data type:

- the primary entity in a row may use the stronger collection title;
- related people, statuses and counters in ordinary administration grids are
  supporting information and must remain compact;
- customer-facing service grids opt into their own hierarchy explicitly with
  `presentation => 'client'` / `.sx-backend-grid--client`.

Do not make every `sx-preview-card__title` look like the row title. In the
default data-dense administration presentation, person previews inside a grid
use inherited table typography and normal weight. Scope this rule with
`.sx-backend-grid:not(.sx-backend-grid--client)` so that changing admin density
does not silently redesign cabinet collections.

When diagnosing an apparently oversized row, inspect the complete row before
reducing global padding. Long legacy entity markup can wrap because its
children no longer fit the available column width; relation-card typography
and row padding are separate causes and should be corrected independently.

## Performance guardrails

- Any new global CSS/JS dependency needs an explicit shell-wide reason.
- Prefer one semantic primitive over project copies and per-controller inline
  CSS.
- Record raw and gzip deltas for a global asset change.
- Avoid automatic selector purging in shared packages without a safelist for
  installed modules and dynamic classes.
- Treat request count and font files as part of the budget, not only CSS
  bytes.
- Keep old AssetBundle class names as compatibility entry points when
  splitting internals.

The compact UPA shell is now the reference consumer. The next optimization
targets are remaining project CSS duplication, migration of the separate
legacy admin Fancybox boundary and gradual admin markup migration. `theme.css`
and `ui.css` are comparatively small and widely shared; do not prioritize
shaving them before screen-level and legacy-shell dependencies.

`client-portal.css` is not an administration stylesheet. During migration it
may contain project adjustments for the current cabinet, but its HTML hooks
must be semantic `sx-*` classes. `client-portal-theme.css` should primarily
assign shared `--sx-*` brand and geometry tokens; do not create a second
`--portal-*` token graph and then alias it back into `--sx-*`. Promote only a
confirmed semantic primitive already needed by both shells. Prefer reusing an
existing vendor primitive first: for example, a custom cabinet heading should
use `sx-collection-page-header` instead of copying its title and description
typography. If preserving the reference appearance requires a large selector
block, first identify the missing backend contract rather than duplicating the
whole shell in the project.

The 2026-07-30 post-Fontello request-count sample for user `1` was:

- `upa-home`: 14 CSS / 14 JS;
- populated `upa-support`: 26 CSS / 56 JS;
- populated `upa-sites`: 22 CSS / 52 JS;
- populated `upa-bill`: 26 CSS / 56 JS;
- legacy admin task and company collections: 30 CSS / 62 JS.

The large difference between the dashboard and collection pages remains
intentional widget ownership: grid, filter, Select2, sortable, IAS and PJAX
assets are registered only when their corresponding controls are rendered.

After removing the unused compact-portal brand stylesheet and splitting
empty-layout and profile-only rules into conditional assets, the rendered
client stylesheet matrix became:

- `upa-home`: 13 CSS;
- populated `upa-support`: 25 CSS;
- populated `upa-sites`: 21 CSS;
- populated `upa-bill`: 25 CSS.

Normal list routes load neither `client-portal-action.css` nor
`client-portal-profile.css`. Empty backend actions load the action asset,
while `/upa-personal/*` routes load the profile asset. Recheck this ownership
matrix in both light and dark modes whenever bundle dependencies or
route-level registration conditions change.

## Semantic shell icons

New shared backend chrome must render icons through
`skeeks\cms\backend\helpers\BackendIcon`. Use semantic registry names and
`currentColor`; do not add another icon font dependency. Controller action
descriptors can opt into this contract with `icon => 'svg:<name>'`.
`ControllerActionsWidget` keeps ordinary CSS class strings as the legacy
fallback.

The compact admin shell header, notifications, schedule controls, theme
switcher, breadcrumbs and menu chevrons use this SVG contract. CSS-only
chevrons are appropriate for pseudo-elements such as Bootstrap dropdown
arrows and collapsible fieldset indicators, because pseudo-elements cannot
contain inline SVG.

Keep the theme switcher track and thumb geometry independent from icon
geometry. The verified compact dimensions are a `64px × 34px` track with
`14px × 14px` sun and moon SVGs. Do not size the SVG itself to the active
thumb.

The legacy `UnifyAdminIconsAsset` and `UnifyAdminAsset` remain compatibility
entry points for old themes. They must not enter the compact
`BackendTheme`/`UnifyAdminCompactAppAsset` dependency graph.
