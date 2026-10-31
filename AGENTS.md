# SkeekS CMS

This is the shared `skeeks/cms` package: a reusable Yii2 content-management core consumed by many SkeekS projects. Changes here affect every consuming project, so keep package-level behavior conservative, reusable and backward-aware.

## Package role

- The package provides CMS models, controllers, migrations, widgets, components and configuration under `src/`.
- Site structure and pages are represented by CMS tree models; publications, news, products and similar records are content elements.
- Content types and their dynamic properties allow projects to add fields through administration without creating a dedicated model for every content kind.
- User access is governed by the existing CMS/RBAC mechanisms.
- The package is a Yii2 extension configured through `src/config/common.php`, `src/config/web.php` and `src/config/console.php`.

Official links:

- Website: `https://cms.skeeks.com`
- Source: `https://github.com/skeeks-cms/cms`
- Issues: `https://github.com/skeeks-cms/cms/issues`

## Architecture and engineering rules

- Keep browser and administration behavior in backend/admin controllers.
- Keep protocol and API integrations separate from backend UI controllers.
- Put shared business behavior and write operations in services or models.
- Keep transport controllers narrow: parse requests, authenticate, authorize and format responses.
- Do not duplicate model validation or bypass Yii validation, permissions, ownership checks or transactions.
- Prefer existing package and project conventions over generic Yii assumptions.
- Avoid unrelated refactors in this shared package.
- Inspect current implementations and usages before changing public model or component contracts.

## Important CMS domains

Use `ast-index` to discover the current definitions and contracts instead of relying on memory. Common starting points are:

- `CmsTree` for site sections and pages;
- `CmsContent` for configured content containers;
- `CmsContentType` for section/content type metadata;
- `CmsContentElement` for publications and other content records;
- storage-file models and services for uploaded files;
- task models and services for CRM task behavior.

The exact namespace, active implementation and extension points must be verified before editing.

## MCP and OAuth ownership

MCP and OAuth are deliberately outside this package:

- `skeeks/cms-mcp` owns the MCP endpoint, tool contracts, providers and MCP-specific application services.
- `skeeks/cms-oauth2-server` owns OAuth controllers, models, migrations, resources and scopes.
- Do not add MCP or OAuth transport code back to `skeeks/cms`.
- Read the target package's `AGENTS.md` completely before changing MCP or OAuth behavior.

For MCP, tools must remain thin and delegate validation, model access, transactions and business rules to services. There are no delete tools: create drafts first and publish explicitly.

For a site with `skeeks/cms-mcp` installed, the canonical endpoint is `https://<site-domain>/cms/mcp`. In Codex, both `url` and `oauth_resource` must contain that exact site-specific URL. Never configure the obsolete `/cms/mcp-task/create` route. Use the actual target site's public domain rather than assuming `skeeks.com`.

The same authorized tools are available to non-MCP AI clients at
`https://<site-domain>/cms/rest-api`. The catalog has three identifiers:
`api_version`, `server_version` and the OAuth/RBAC-specific `tools_revision`.
Persist the `/tools` response outside the chat and revalidate its private ETag;
reuse cached schemas after `304 Not Modified`. Do not download and reinterpret
the complete catalog for every user request. Use `/tools/index`,
`/tools/{tool_name}` or the `prefix`, `names` and `q` catalog filters when only
a small part of an uncached inventory is needed. Never share catalog caches
between OAuth credential stores.
The Windows REST helper is owned by `skeeks/cms-mcp` at
`scripts/skeeks-rest.ps1`; this core package only documents how agents locate
and invoke the installed helper.
Common reads should use the package's `scripts/skeeks-api.ps1` direct-first
client. Known operations execute immediately; catalog or schema discovery is a
fallback after an unknown-tool or validation error, not a mandatory preflight.

When the user asks to create a SkeekS CMS task through MCP, use `create_cms_task`. Default `executor_id` is `1` unless the user specifies another executor.

## AI-assisted content direction

CMS-facing services may support AI clients that:

- inspect site, theme, content-type and field metadata;
- create or edit sections and pages;
- create or edit publications;
- upload or select files and insert their URLs into content;
- validate drafts and publish explicitly;
- return a public URL for verification.

Preserve existing content structure unless the user requests a rewrite. Resolve explicit entities for updates, validate dynamic fields for the selected content type, and ask the user when a required type or required field cannot be inferred safely.

## Code search

For PHP symbols, classes, usages, inheritance and dependencies in shared vendor code, run `ast-index` first from:

```text
C:\SkeekS\dev\php\vendor
```

Useful commands:

```powershell
ast-index search "Query"
ast-index class "ClassName"
ast-index symbol "SymbolName"
ast-index usages "SymbolName"
ast-index implementations "InterfaceName"
ast-index callers "methodName"
ast-index outline "path\to\file.php"
```

Use `rg` only for raw strings, config, markup, regular expressions, application-project searches, or when `ast-index` returns no useful result.

Only update the shared vendor index when the user explicitly asks `обнови вендоры`, `обнови индекс` or `обнови индекс вендоров`. Do not try `ast-index update` first. Run the known-good command immediately with elevated permissions:

```powershell
cd C:\SkeekS\dev\php\vendor
$env:PATH='C:\Users\User\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin;' + $env:PATH
& 'C:\Users\User\.cache\codex-runtimes\codex-primary-runtime\dependencies\bin\fallback\pnpm.cmd' dlx @ast-index/cli update
```

## Lead visibility and recipients

Unassigned leads without company evidence are administrator triage. Ordinary
employees see a lead through an available company: an explicit company link,
a linked client/submitter/partner in CmsCompany2user, or an exact normalized
phone/email shared with that company or its contact. Names and short phone
fragments do not grant access. Explicit executors and their superiors retain
the assigned-work scope. Never automatically link CRM records while reading.
New-lead recipients are active site workers with cms/admin-lead permission
whose explicit-identity CmsLeadQuery::forManager() contains the lead. Source
ingestion selects recipients after saving contacts; repeated contact sync
checks existing new-lead notifications before emitting another one.
Test: tests/cms-lead-company-access.php with Composer autoload, isolated SQLite.

## Duplicates, «Неразобранное» and assignment (П2а)

Status `new` is «Неразобранное» for employees (`CmsLead::staffStatuses()`,
`staffStatusName`); partners keep `statuses()`. The one duplicate check is
`crm\CrmMatcher` (component `crmMatcher`), employee CRM rights are
`crm\CrmAccess` (component `crmAccess`), sorting and the client of a deal are
`crm\LeadTriage`, access requests `crm\CrmAccessRequests`
(`cms_crm_access_request`), "Это другая компания" `cms_crm_duplicate_mark`
(migration m261028_100000). Actions: `controllers\traits\LeadTriageActions`
(create window with "Кто это", triage, identity, assign, match, requests).
Sorted deals: `triage_result` (merged, support, spam), `merged_into_id`; out
of the report and the kanban "Отказ". Contract and order of the create
window: skill `references/crm-duplicates-and-triage.md`.
Links employee ↔ company/client carry `access_level` (full, sales; migration
m261028_110000); «Передать клиента» is `crm\CrmClientTransfer` behind
`controllers\traits\ClientTransferActions` in the company and user cards.
Every CRM change in the core must keep a simple site (no cms-communication, no
CRM data) working as before — the "base site" rule of the skill reference.
Test: tests/crm-lead-triage-stand.php (CRM_TRIAGE_STAND_TEST=local, stand DB,
rolled back).
П2г: a deal made by an employee always has a client (`LeadTriage::requireClient()`
in `createLead()` and the create window, except a record source); phones,
emails, addresses, requisites and links of a deal are read-only request data
(`AdminCmsLead{Phone,Email,Address,Link,Contractor}Controller`: create/update
answer 403), moved to the client by «Указать клиента» (`defaultContacts()`,
`copyRequestDataToCompany()`). Contract: skill
`references/crm-duplicates-and-triage.md`, «A deal by an employee always has a client».

## Verification

`WidgetRenderable` logs rendering exceptions and PHP errors server-side and
returns a neutral message in normal mode. With YII_DEBUG, render the original
failure's getTraceAsString() inline. Never expose exception text/traces in public widget HTML
or cache a failed render. `tests/widget-render-error.php` checks privacy,
logging, recovery and successful-render caching.

Before finishing a change:

1. Check the package Git status and preserve unrelated work.
2. Run `php -l` for changed PHP files.
3. Run the narrowest relevant automated tests or smoke checks available.
4. Verify package boundaries and configuration wiring.
5. Stage only intended files; never stage `.idea/`.

Keep durable agent knowledge in this root `AGENTS.md`. Do not add auxiliary Markdown documentation directories unless the user explicitly changes this convention.

## Native scheduled maintenance

The existing cms/cache/flush-all and ajaxfileupload/cleanup route keys now
resolve to cms.flush-cache and cms.cleanup-temporary-files native job types.
Keep their schedule identities, intervals, activation and execution dates.
Manual execution uses the existing CMS admin permission.

CacheFlusher is shared with the CLI. It discovers configured CacheInterface
components using the Yii console cache rules, skips APC under CLI and raises
an error when flush returns false. Native runs report skipped caches as warnings.
It flushes cache components only; runtime directories and published assets are
not part of this operation.

The upload handler delegates to yii2-ajax-file-upload's TemporaryFileCleanup
service (release 2.0.2 or newer), which the upload CLI also uses. No console
subprocess is involved. CMS owns job registration; the upload library owns file
expiry behavior and has no cms-job dependency.

Keep cms-job optional at the CMS core dependency boundary to avoid a cycle.
Queued maintenance requires cms-job and its migrations/workers at installation
level. Both jobs fail closed without that runtime. Deploy new services/classes
before configuration and restart maintenance consumers. This change adds no
schema migration.

tests/native-cache-smoke.php uses only in-memory caches; the upload package's
tests/temporary-cleanup-smoke.php uses its own disposable filesystem directory.

## Category synchronization protection

Tree/CmsTree owns `is_sx_info_update` (boolean, default 1), exposed in the existing
admin-tree form for records linked by sx_id. Migration m260917_210000 protects
pre-existing GPD categories (`sx_id > 0`) by setting it to 0 once, across all sites.
Do not repeat this backfill at runtime or reset a user's later choice. Import
writers in cms-shop must honor the flag before preparing media or modifying data.
The migration intentionally refuses automatic rollback to avoid removing protection.
`tests/tree-gpd-sync-migration.php` verifies old/new and prefixed-table behavior.
## List value ownership
PropertyTypeList adds an ownership validator through the property's getEnums relation. It validates single and multiple selections before RelatedPropertiesModel saves/replaces rows; empty optional selections remain allowed. This is validation, not a database constraint: save(false) and SQL can bypass it. tests/property-list-ownership.php covers cross-property, missing, empty and duplicate selections.

## Main domain query cache

CmsSite::getCmsSiteMainDomain keeps its ActiveQuery relation contract. For a
concrete site ID it caches SQL for 28800 seconds using the CmsSiteDomain table
tag (HasTableCache) and the site's getCacheTag() (AdminCacheController button).
The global table tag covers a domain moving between sites. Eager loading from
an unpopulated site model remains uncached. Opt in only when the DB queryCache
resolves to the application's cache used by HasTableCache; callers can use
noCache() explicitly. Direct SQL/updateAll writers must invalidate the tags,
as they bypass model events. Loaded AR relations still follow Yii's normal
object lifetime and need refresh/reload to observe later changes.
Test: tests/site-main-domain-cache.php with Composer autoload; isolated SQLite
checks cache hits, empty results, site isolation, manual invalidation, actual
model save/delete/main-domain changes, moves and a separate queryCache.

## Scoped site and address reads

CmsSite::getById caches its SQL for eight hours with the site table tag and
record/site cache tag. Its existing public static sites array remains a
per-process snapshot; resetting a persistent cache does not refresh already
loaded objects in that array. CmsSite::getCmsSiteAddress caches only the first
address query with the address table tag and owning site tag, retaining its
priority order and limit. Other address queries are unchanged. Both opt-ins
require queryCache to resolve to the cache used by HasTableCache.
UrlRuleTree URL creation reuses an already loaded matching cms.currentTree only
in web applications. Parsing URLs must not add models to the static map: that
would retain trees and relations across long-running console work. Console
lookup follows its previous path. The web test also checks 1000 replacements
of currentTree without growing the static map.
Tests: site-settings-cache.php and tree-route-reuse.php (Composer autoload).

## Image preview response

ImagePreviewController returns the saved preview through Yii sendFile with
inline disposition. Do not reopen it with Imagine show(): that encodes it a
second time and can lose the effective quality on the first response.
Thumbnail keeps the existing quality contract: explicit q is in the URL;
otherwise quality comes from seo.img_preview_quality during generation.
Existing static previews are not regenerated by a settings change.
The isolated tests/webp-quality.php accepts Composer vendor directory,
controller source path and a JPEG fixture path. It checks real WebP quality,
site-default equivalence, first-response byte identity and JPEG/PNG output,
using temporary files without bootstrapping the site or connecting to its DB.

## Lead tasks

CmsTask.cms_lead_id (migration m261012_230000, FK SET NULL) links a task to a
lead independently of the exclusive company/client/project relation. The lead
card shows open tasks above the comment form and a "Задачи" related tab; its
"Поставить задачу" button opens task create with CmsTask[cms_lead_id]. The task
form keeps the lead only if CmsLeadQuery::forManager() returns it, and fills
the lead's company (else client) when no relation was chosen. Subtasks copy
the lead. cms-mcp task create/update/list accept cms_lead_id with the same
lead-access check.
Next step: an open task is one in CmsTask::activeStatuses() (new, accepted,
in work, paused; on-check is done from the executor's side). A lead in work
without one is "next step missing"; CmsLeadQuery::nextStep() backs the list
filter and CmsLead::nextTask the list column and card surface. "Не дозвонились"
(no-answer action, CmsLeadNoAnswerForm + CmsLeadNoAnswerService) increments
cms_lead.no_answer_count (m261013_100000), moves the actor's nearest open task
on the lead or creates "Перезвонить: …", writes a lead comment; closing as
unreachable rejects the lead and cancels the actor's open lead tasks through
TaskWorkflow.

## Task start, views and reminders

Every active task has a start (owner's decision 31.10.2026): `is_fixed_start`
tells a time chosen by a person from one given by `services\TaskPlanner`
(free time of `work_shedule` after fixed tasks, queue `executor_sort`).
Migrations m261031_100000 (mode, old tasks laid out) and m261031_120000
(reminders), m261031_130000 (`remind_by`, «перенесена»). The planner runs
only on events (no replan on page open or by a timer); agent `cms/task/remind` (1 min).
Views of `admin-cms-task`: «Мои задачи» (day list, start time), «Сроки»
(`CmsTaskDeadlinesWidget`), «Мой календарь» (`CmsTaskCalendarWidget`, month,
week, day), «Гант» (`CmsTaskGanttWidget`); the last one opened is remembered
(cookie `sx-task-view`). Every move goes through `actionReschedule`. Tests:
`tests/task-planner-stand.php`, `tests/task-reminder-stand.php`. The contract
is in the skill, `references/task-workflow.md`.

## Lead stages and kanban

CmsLeadStage (m261013_120000, per site, unique name, sort, optional #rrggbb
color) labels steps inside the system status "in work"; cms_lead.cms_lead_stage_id
is nullable (FK SET NULL, deletion refused while leads use the stage). The four
system statuses stay authoritative for access, notifications, partner rewards
and validation. CmsLeadStatusWidget shows the stage only with showStage=true:
the UPA partner cabinet uses the widget and must not see internal stages.
The admin "Канбан" action (CmsLeadBoardService) has columns new, "in work"
without stage, one per stage, success, rejected (last 30 days). A drop into a
stage claims a new lead or reopens a terminal one through normal validation;
a drop into a terminal column only returns the process form URL with ?status=
preselected, because a result or reason is required; a drop into "new" is
refused. The board lives in skeeks/cms until a second consumer justifies a
shared cms-backend renderer. Stages are managed by administrators in
"Этапы лидов" (AdminCmsLeadStageController).
The lead card shows the same moves as a stage bar (_stage-bar.php,
CmsLeadBoardService::steps() and denyReason()); both post to kanban-move.
The process form receives a target status as ?status= and only preselects the
control (data-sx-lead-status-preselect): never assign it to the model, the
window header is rendered from the same instance.
A lead that enters "in work" without a stage (claim, creation with a manager,
reopening) gets the first stage of its site in CmsLead::beforeSave; a lead
already in work and sites without stages are untouched. The stage is kept when
a lead is closed, so reopening returns it to its previous stage.
With stages configured, the kanban hides the empty "in work without stage"
column: it only holds leads taken into work before stages existed.
AdminCmsLeadController::$defaultAction is "kanban" (tabs: Канбан, Таблица,
Новый лид); related lead tabs in company/client cards still render the table.

## Lead reject reasons

CmsLeadRejectReason (m261013_140000, per site, unique name, sort, one
is_no_answer default) is managed by administrators in "Причины отказа лидов".
Once a site has reasons, a rejected lead must have cms_lead_reject_reason_id
and reject_reason becomes an optional comment; without reasons the text stays
required as before. beforeSave copies the reason name into an empty
reject_reason, because the UPA partner cabinet, notifications and API read the
text. The no-answer form preselects the is_no_answer reason when closing.
CmsLeadStatusWidget(showStage) also shows the reason of a rejected lead.

## Lead deal amount

cms_lead.deal_amount (m261013_160000, decimal 18.2, RUB, one-off) is required
when a lead becomes successful (earlier successes are not forced) and cleared
when it leaves "success". It is employee data: shown only through
CmsLeadStatusWidget(showStage) and the kanban card. The process form offers
10/20/30% shortcuts that fill partner_reward_value from it; the reward stays
editable. Net profit (contractor costs) belongs to deals/projects, not leads.

## Lead report

The "Отчёт" tab (CmsLeadReportService, views/admin-cms-lead/report.php) counts
leads created in the period (a cohort) in their current state, within
forManager()->cmsSite(). Conversion = successful / target leads; rejections
with a reason marked is_not_target (m261013_180000: spam, duplicates) are not
target. Deal sum and average check use successful leads with deal_amount; the
partner share uses only leads that have both an amount and a reward. Partner
rewards come from EVENT_PARTNER_REWARDS (LeadReportPartnerRewardsEvent), which
skeeks/cms-shop answers from shop_partner_lead: the core does not depend on it.
A successful lead's amount is corrected through the hidden deal-amount action
(header button "Сумма сделки", executor or administrator, DynamicModel form).
It saves only deal_amount: the status does not change, so EVENT_PARTNER_SUCCESS
and the partner reward are not triggered again.

## CRM extension points for other packages

Packages such as skeeks/cms-communication extend CRM cards without core
changes. Skeeks::$logRenderers maps a log type to a CmsLogRendererInterface
class/config or a callable; CmsLog::render() calls it before the built-in
branches, so a registered type renders itself (trusted HTML: encode values).
Skeeks::$activityLogTypes adds types to the lead, company and client feeds
(CmsLogQuery::activity(), defaults: phone_call, comment); registrations merge
and never replace. Label new types through Skeeks::$logTypes.
Phone and email rows of the lead, company and client cards render their
actions through CrmContactBlockWidget::contactActions(): the card passes its
defaults (`sms`, `call`, `mailto`; none for a lead the employee cannot work
with) and the class-level EVENT_CONTACT_ACTIONS (CrmContactActionsEvent with
contactType, value, owner, actions) lets handlers replace, add or remove
entries. Handlers check their own permissions. CmsLead sources include
`email` and `max` for leads created from mailboxes and MAX bots.
"Создание из источника": Skeeks::$recordSources maps a source type to a
source\RecordSourceInterface; every standard create window
(BackendModelCreateAction) opened with `?source=<type>:<id>` is handled by
source\RecordSources (class-level events attached in Skeeks::bootstrap for
web apps): find() checks the employee's access, prefill() fills the new
record on opening (posted values win), a line from describe() stands above
the fields, enforce() sets source-owned attributes after the controller's
own beforeValidate handlers, created() links back in the save transaction
(a failure rolls the record back). Unknown, hidden or unsupported sources
are ignored. Core keeps no knowledge of the source (mail is the first one).
A source that also implements source\RecordOriginInterface says where a
saved record came from: source\RecordOriginWidget at the top of the task,
lead and deal cards shows "Создано из письма «…»" (label plain text, html
trusted, a link only when the employee may open the source); nothing when
the record has no origin.
The task card feed uses CmsLogQuery::activity() like lead/company/client,
so package log types (letters) show there. CmsTaskQuery/CmsDealQuery
forManager($user) pass the explicit user into nested company/client/project
scopes.
Package blocks in cards: widgets\admin\CrmCardBlocksWidget is rendered in
the aside of the lead, company and client (admin-user) cards and under the deal card
(POSITION_MAIN); handlers of the class-level EVENT_BLOCKS
(CrmCardBlocksEvent: owner, position, blocks id => trusted HTML, priority)
add their own surfaces ("КП" of skeeks/cms-proposal). Core has no package
blocks of its own.
Test: tests/crm-extension-points.php (Composer autoload, SQLite memory for
record sources).

## Client access to deals ("Сделка как услуга", steps У1–У2)

Client people (not employees) are checked by one service, the `dealAccess`
component (deal\DealAccess): access areas with integer levels, grants on a deal
or on all deals of a company (cms_client_access + _area), editable roles
(cms_deal_role), what a deal type/deal opens (cms_deal_type_area,
cms_deal_area), deal composition (cms_deal_item), resources from package
providers. CmsDealQuery::forClient($user, $area) and CmsTaskQuery::forClient()
use it; forClient() without an area means full access only, so screens that
do not ask for an area keep the old boundary and never widen to limited roles;
a task of a deal is decided only by the deal rights, an internal task is
hidden from everyone, a task without a deal (company or client only) is never
shown to the client. is_root of CmsCompany2user is kept equal to a company-wide
full grant in one transaction. A deal with client grants or tasks of its
company cannot be moved to another client (CmsDeal::validateClientChange).
Migrations m261026_200000 (tables, roles, carry-over of is_root and contacts of
company deals), m261026_200100 (cms_task.cms_deal_id, resource, is_internal)
and m261026_200200 (journal of moving projects).
Projects → deals: console cms/deal-access/migrate-projects (plan by default,
--apply journals each project in the same transaction, revert --run=<key>
keeps grants changed by hand). UI: «Роли доступа клиентов»
(Справочники), «Доступ клиента», «Ресурсы» and «Задачи» in the deal card,
«Сделка», «Ресурс сделки», «Внутренняя» in the task form. Full contract:
skeeks-cms-package-development references/deal-client-access.md.
Test: tests/deal-client-access.php (Composer autoload, SQLite memory).

## Contractor delivery address

cms_contractor.delivery_address (m261015_020000, varchar 255, null) is the
consignee address used in documents. It is edited in the client contractor
form after the postcode and shown in the card; "our" contractors do not have
it. skeeks/cms-shop ShopDocument::createFromBill fills consignee_address of
UPD, waybill and invoice-facture from it, falling back to the buyer address.
Model rules, form and card access it through hasAttribute(), so code works
before the migration is applied. The API writes it through safeAttributes.

## Rich-text editor

RichTextInputWidget (widgets/formInputs/richText) replaces yii2-ckeditor in
the task description, CRM comments (CmsCommentWidget, comment editing, UPA
support), the visual mode of ComboTextInputWidget (always RichText, trusted
HTML; pictures only with RichTextBehavior on the model) and the
cms-communication letter form; yii2-ckeditor is removed. A preset
(basic, standard, full; RichTextHtml::PRESETS) sets the toolbar and the
server cleaning together; the font family is opt-in and used only by
letters. RichTextBehavior cleans changed attributes only and moves pasted
images from TemporaryUploads into the storage on save
(cms/admin-rich-text/upload). Full contract: skeeks-cms-package-development
references/rich-text-editor.md. Test: tests/rich-text-editor.php.

## Planned: storage file usage registry

Status: plan agreed with the owner on 2026-10-21, not started. Nothing below
exists in code yet; do not document it in the skill until it is implemented.
`skeeks/cms-backend` is out of scope (the owner postponed base backend changes).

Why. `cms_storage_file` does not know where a file is used. HasStorageFile /
HasStorageFileMulti delete a file when it is removed from its record, and a
manual delete in "Файловое хранилище" silently removes the file from every
record that refers to it (cascading foreign keys). This is tolerable while one
file belongs to one record. It stops being safe once a file is shared: mail
already shares one stored file between letters with the same content, and
next come retention cleanup of letter files, "create a task from a letter"
with attachments, a shared rich-text editor with images in tasks and news, and
reuse of identical content across the platform.

Design: the registry asks the owners, it does not store references. A
separate "file -> usage" table is rejected: every writer in every package
would have to keep it in sync, and one missed writer lets a cleanup delete a
live file. Instead:

1. A provider contract in `skeeks/cms` (working name
   `StorageFileUsageProviderInterface`): for a list of storage file ids return
   where each one is used — the kind of record ("Письмо", "Задача", "Товар"),
   record id, its title and its admin URL. A provider reads the same data its
   section works with, so the answer is always current.
2. `Yii::$app->storage` (`components\storage\Storage`) keeps the list of
   providers, filled from package configuration (an optional package registers
   its own provider; core never depends on it), and exposes "where is this
   file used" and "is this file used".
3. Core providers only for relations verified in code: task files
   (`CmsTaskFile`), images and files of content elements and trees, user
   images and the like. Other packages (shop, news, products) connect
   gradually, each with its own provider.
4. The storage file card in "Файловое хранилище" (`AdminStorageFilesController`
   update page) shows "Где используется" with links; deleting a used file warns
   instead of removing it everywhere.

First candidate: mail (`skeeks/cms-communication`). It already keeps its own
registry, `cms_comm_message_file` (letter -> storage file, name, attachment or
inline image, `content_hash`, size; see its `ARCHITECTURE.md`, "Файлы писем").
Connecting it is one provider class over that table (letter subject and a link
to the letter) registered in the package config; the rest of mail stays as is.

Rules until the registry covers every package that stores files:

- A file is handed from one section to another as a copy, not as a reference
  to the same storage file (e.g. "create a task from a letter"). References
  become allowed once both sections are connected.
- A section's own cleanup deletes a storage file only when its own records no
  longer refer to it and, once the registry exists, when "is this file used"
  answers no.
- While coverage is partial, "not used" means "not found in connected
  sections": do not enable a platform-wide cleanup of unused files or reuse of
  identical content across sections until every file-storing package is
  connected.

Done when: the storage card of a letter file (on the skeeks.com stand, the
files of letter #234) shows "Где используется" with a link to the letter, an
unused file shows "nowhere", deleting a used file warns, and the verified
contract is recorded in the `skeeks-cms-package-development` skill with the
partial-coverage caveat.
