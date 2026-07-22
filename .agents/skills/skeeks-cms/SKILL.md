---
name: skeeks-cms
description: Develop SkeekS CMS/Yii PHP code and handle advanced or unfamiliar site content, AI-managed themes and HTML pages, shop, MCP, REST and OAuth2 workflows. Use for custom site headers and footers, site-wide CSS or scripts, and html-content pages. Routine central CRM reads use the global AGENTS direct-first client and do not require this skill.
---

# SkeekS CMS

## Start here

Treat a project as SkeekS CMS when the user says so or `composer.json` depends
on a `skeeks/*` package.

Choose the target before acting:

- team CRM entities such as companies, projects, tasks, deals and finance use
  the configured central CRM REST API, normally `https://skeeks.com/cms/rest-api`;
- pages, publications, files, settings and products of a project website use
  that website's site-specific MCP server when it is available;
- use the website REST adapter when MCP is unavailable or the client is a script.

Read `.codex/skeeks.json` from the current project when present. It is a small,
non-secret routing profile:

```json
{
  "profile_version": 1,
  "site": "example.com",
  "crm": "skeeks.com",
  "site_transport": "rest",
  "crm_transport": "rest"
}
```

## Create the project AI profile

When the user says "создай файл в проекте для быстрого взаимодействия с ИИ",
"подготовь SkeekS-проект для ИИ", "создай профиль SkeekS" or an equivalent
phrase, create `<project-root>/.codex/skeeks.json` with the schema above.

- Determine `site` from the project's configured public host/domain. Prefer an
  explicit project domain over the current directory name. Remove the scheme,
  path and trailing slash.
- Set `crm` to the project's configured central CRM domain; use `skeeks.com`
  when the project has no explicit override.
- Default to `site_transport: "rest"` and `crm_transport: "rest"`.
- Create the `.codex` directory when it does not exist.
- Do not put OAuth credentials, tokens, client secrets or cookies in this file.
- If the profile already exists, read and preserve valid project-specific
  values. Do not overwrite a different site or CRM silently.
- If the site domain cannot be determined reliably, ask only for the domain;
  do not start OAuth or guess a production target.
- After creating the file, report its path and resolved routing. Authorization
  is a separate, one-time action and should run only when access is requested.

Do not read package source, inspect OAuth files, call site context or download
the tool catalog before an ordinary known read.

## Direct-first REST path

On Windows, locate the installed `skeeks/cms-mcp` package and invoke its fast
client with Windows PowerShell 5.1 outside the sandbox:

```powershell
$powershell = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$api = '<cms-mcp-dir>\scripts\skeeks-api.ps1'
& $powershell -NoProfile -ExecutionPolicy Bypass -File $api -Operation company.search -Query 'SkeekS' -Limit 10
& $powershell -NoProfile -ExecutionPolicy Bypass -File $api -Operation task.mine.active -Limit 20
```

Stable fast operations:

- `company.search -Query <text>` and `company.get -Id <id>`;
- `project.search -Query <text>`;
- `worker.search -Query <text>`;
- `task.mine.active`, optionally with `-ProjectId` or `-CompanyId`;
- `task.search`, optionally with query, executor, project or company;
- `task.day`, optionally with date or executor;
- `site.context`;
- `site.info`, optionally with `-Id`;
- `tree.list`, optionally with `-ParentId`;
- `content.list`, optionally with `-ContentId` or `-ParentId`;
- `saved-filter.search`, optionally with `-Query` or `-TreeId`, and
  `saved-filter.get -Id <id>`;
- `form.search -Query <text>`, `form.schema -Id <id>` and
  `form.submissions`, optionally with `-FormId` and `-Status`;
- `product.resolve`, with an exact `-Id`, `-Code`, `-BrandSku` or `-Barcode`;
- `store-product.resolve`, with `-Id` or a store plus product/external id;
- `tool.call -ToolName <name> -ArgumentsBase64 <json-base64>`.

These operations execute the known tool immediately. Do not preflight `/tools`.
If the direct call fails because the tool is unknown or its arguments changed,
the client fetches only that tool schema. Only then inspect the schema or the
compact tools index.
List operations return compact records by default; pass `-Full` only when the
user actually needs complete descriptions or configuration.

For shop imports and catalog synchronization, never page through the complete
product or store-position catalog to find one record. Resolve it exactly with
`shop_product_resolve` or `shop_store_product_resolve`, then use the matching
`*_upsert` tool. Use `shop_product_batch_upsert` (up to 20 items) and
`shop_store_product_batch_upsert` (up to 50 items) for bounded batches. Product
and store-position writes are separate OAuth scopes; upload files separately,
then pass their stored references into product data. Stock `quantity` remains
read-only and changes only through inventory movement documents.

For dynamic content fields, start with the compact
`cms_content_property_list`. Read one full definition with
`cms_content_property_get`, and load enum options only through
`cms_content_property_enum_list`. Do not request every component setting or
enum value when only field names and codes are needed.

For sites with `skeeks/cms-module-form2`, manage `/form2/admin-form` through
the `form2_*` tools. Read `form2_form_property_component_list` before choosing
field types. Use `form2_form_create_full` for atomic creation of a form with
fields and list options, and verify it with `form2_form_schema_get`. Reordering
requires the complete current ID sequence. Form submissions are available via
`form2_form_send_*`; only their status and manager comment are editable, and
the API intentionally omits stored server, session, cookie and raw request
dumps. Do not confuse these Form2 fields with `cms_content_property_*`.

Manage `/cms/admin-cms-saved-filter` through `cms_saved_filter_*`. These records
are public SEO landing pages for catalog filters, not personal admin-grid
presets. Resolve a section and exactly one selector (content element, property
enum, shop brand or country); element and enum selectors require the matching
content property. Use `cms_saved_filter_resolve` before creation, upload an
optional image through storage, validate the record, then create or update it.
Creation is idempotent for an exact section/selector duplicate. No delete tool
is available.

For general site identity and contacts, use the table-oriented site tools:

- `cms_site_info_get` and `cms_site_update` for the name, logo, favicon and
  work schedule from `/cms/admin-cms-site-info`;
- `cms_site_phone_*`, `cms_site_email_*`, `cms_site_address_*` and
  `cms_site_social_*` for site contacts;
- `cms_site_social_type_list` before choosing `social_type`;
- `cms_site_domain_*` for hostnames, HTTPS and the main-domain flag.

Upload logo, favicon and address images with `cms_storage_file_upload`, then
pass their IDs. Contact/domain lists default to the current site. Read existing
records first, create or partially update only the requested values, and read
them back afterward. Setting a domain's `is_main` flag replaces the previous
main domain. No delete tools are available.

For an unfamiliar tool, use the lower-level client:

```powershell
$rest = '<cms-mcp-dir>\scripts\skeeks-rest.ps1'
& $powershell -NoProfile -ExecutionPolicy Bypass -File $rest -Site 'example.com' -Action tool-schema -ToolName 'cms_company_stats'
$json = '{"group_by":"status"}'
$arguments = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($json))
& $powershell -NoProfile -ExecutionPolicy Bypass -File $rest -Site 'example.com' -Action execute -ToolName 'cms_company_stats' -ArgumentsBase64 $arguments
```

The REST client handles DPAPI, access-token refresh, refresh-token rotation,
UTF-8 and the credential-specific tool cache. Never decrypt or print OAuth
credentials manually. Every execution returns API version, server version and
the authorized tools revision; reuse known schemas until an error or revision
change requires refresh.

## One-time REST authorization

If no credential store exists, run the canonical login client once:

```powershell
& $powershell -NoProfile -ExecutionPolicy Bypass -File '<cms-mcp-dir>\scripts\skeeks-rest-login.ps1' -Site 'example.com'
```

It owns metadata discovery, dynamic registration, PKCE S256, browser launch,
loopback callback and DPAPI storage. Do not replace it with an ad-hoc listener,
inspect browser cookies or poll credential files. Use `-ForceAuthorization`
only after refresh genuinely failed, the user revoked the connection, or an
explicit scope upgrade is required for newly deployed tools. A changed tools
revision alone is not a reason to force authorization.

### OAuth scope upgrades after API expansion

When a site deploys new REST tools, an existing refresh token may remain valid
but lack their new scopes. If the user requests reauthorization, or an expected
tool is absent from the authorized catalog, run the canonical login client once
with `-ForceAuthorization`. Let the browser complete the normal consent and
loopback callback; do not inspect or copy tokens.

After authorization, read `tools-index` and verify the exact required tool names
and scopes, not only the total tool count. Record the returned `tools_revision`
for diagnostics. Common expanded families include:

- `cms_saved_filter_*`: `cms.saved_filter.read` / `cms.saved_filter.write`;
- `form2_form_*`: `cms.form.read` / `cms.form.write`;
- `form2_form_send_*`: `cms.form_send.read` / `cms.form_send.write`;
- `cms_site_phone_*`, `cms_site_email_*`, `cms_site_address_*` and
  `cms_site_social_*`: `cms.site_contact.read` / `cms.site_contact.write`;
- `cms_site_info_get`, `cms_site_update` and `cms_site_domain_*`:
  `cms.site.read` / `cms.site.write`.

Do not repeat forced authorization when the required methods are already in the
authorized catalog. A schema change with the same scopes only requires the
relevant tool schema or cache refresh.

## Connected site MCP

The canonical project endpoint and OAuth resource are identical:

```toml
[mcp_servers.example]
url = "https://example.com/cms/mcp"
oauth_resource = "https://example.com/cms/mcp"
```

Name each server after its domain. After adding it, complete OAuth once and
restart the client or open a new task if the server is absent from the current
tool registry. Do not attempt to hot-load a newly configured MCP server.

For content generation, first read site context, active theme and effective
settings; resolve section/content types and required properties; upload images;
create a draft; validate; publish only when requested; then return the URL.

## AI-managed site design

Use the theme API and the `html-content` section type when the user wants an
agent to design a site without filesystem or PHP-template access.

### Unique site header and footer

1. Read `cms_site_context_get`, then read the active theme with
   `cms_theme_get_active` or `cms_theme_get`. Preserve its id and existing
   effective configuration.
2. For an Unify-based theme, enable custom markup with a partial
   `cms_theme_update` call:

   ```json
   {
     "id": 5,
     "config": {
       "header": "custom",
       "header_custom_html": "<header>...</header>",
       "footer": "custom",
       "footer_custom_html": "<footer>...</footer>"
     }
   }
   ```

3. Send only the keys being changed. Never copy the complete effective config
   back into `cms_theme_update`: it also contains form metadata and may hide
   newer settings. The service validates the editable keys and merges the
   partial config.
4. `header_custom_html` and `footer_custom_html` are trusted HTML strings, not
   PHP/Yii view files. Do not generate PHP code. Avoid third-party scripts,
   trackers and remote assets unless the user explicitly requests them.
5. The `cms.theme.write` OAuth scope is required. An older token created before
   that scope was installed must be authorized again.

### Site-wide CSS

The Unify theme setting `css_code` is registered on every normal page of the
theme. Read the current theme first, then update it through the same partial
`cms_theme_update` call:

```json
{
  "id": 5,
  "config": {
    "css_code": ":root { --brand: #2457ff; }\n.site-hero { ... }"
  }
}
```

Store CSS only, without a surrounding `<style>` tag. Prefer scoped class names,
responsive rules and CSS variables. Preserve existing CSS unless the user asks
for a replacement; when extending it, read and deliberately merge the current
value. Custom header/footer markup may rely on these global styles.

### Global head and end-of-body code

The optional `skeeks/cms-seo` package exposes the application component
`seo` (`skeeks\\cms\\seo\\CmsSeoComponent`). It provides two site-wide trusted
HTML settings:

- `header_content` is inserted immediately before `</head>` on normal HTML
  responses;
- `countersContent` is inserted near the end of `<body>` in a hidden container
  and is intended for counters, analytics, chat widgets and other global code.
  A `<script>` stored there still executes.

Use `cms_component_settings_get_effective` with `component: "seo"` and the
target `cms_site_id` before changing either field. Then call
`cms_component_settings_update` with only the attributes that must change:

```json
{
  "component": "seo",
  "cms_site_id": 6,
  "attributes": {
    "header_content": "<script>...</script>",
    "countersContent": "<script>...</script>"
  }
}
```

`cms_component_settings_update` writes only the site-level override, validates
the component's safe attributes, and merges the partial values. It requires
the `cms.settings.write` OAuth scope; older clients must authorize again.

Use `header_content` for code that is required in `<head>` (verification tags,
preloads or scripts whose vendor explicitly requires head placement). Use
`countersContent` for analytics and deferred integrations. Do not put CSS here
when the theme's `css_code` setting is sufficient. Never replace existing
analytics, verification or consent code implicitly: read it, preserve it, and
append or remove a clearly identified block only when requested. Do not add
trackers, remote scripts or credentials without explicit user authorization.

### Full-control HTML pages

`html-content` is the canonical `cms_tree_type.code` for a clean HTML page. Its
view renders `CmsTree.description_full` directly, without the theme content
container, breadcrumbs, sidebars or automatic content blocks. The site header,
footer, global assets and `css_code` still apply.

1. Resolve the type through `cms_tree_type_list` and match the exact code
   `html-content`; do not guess its numeric id. If it is absent, report that the
   site needs the current `skeeks/cms` and Unify theme migrations/package
   versions rather than silently using another type.
   Treat `text-full` as an unrelated legacy or project-specific type. Never
   rename it, modify it, or use it as an automatic fallback for `html-content`.
2. Resolve the parent section. Create the page as a draft with
   `cms_tree_create`, passing `tree_type_id` and the complete page markup in
   `description_full` (top-level or inside `attributes`, according to the
   runtime tool schema).
3. Use semantic HTML and make the page responsive. Do not include `<html>`,
   `<head>` or `<body>` because the theme layout owns the document shell.
4. Validate with `cms_tree_validate`, publish only when requested, read the
   page back, and verify its public URL in a browser when browser control is
   available.

For a fully custom visual system, normally combine one custom header, one
custom footer, global `css_code`, uploaded site assets, and one or more
`html-content` pages. Keep navigation URLs and asset URLs based on records
resolved from the target site rather than invented paths.

## Mutation safety

Known read operations may run immediately. Before a mutation:

1. resolve referenced records and meaningful type/status/category choices;
2. check duplicates where applicable;
3. let OAuth determine creator/owner fields;
4. stop when a tool returns `requires_confirmation`;
5. read the changed object back and report its id, status and URL.

There are no delete operations. Do not guess IDs. Create pages, publications
and products as drafts unless the user explicitly requests publication. Never
change stock quantity directly; use inventory movement documents and explicit
approval.

## Package development

Package ownership:

- `skeeks/cms` owns CMS models, reusable domain behavior and administration;
- `skeeks/cms-mcp` owns MCP/REST transports, tool contracts and API services;
- `skeeks/cms-oauth2-server` owns OAuth resources, clients, codes and tokens.

For code changes, locate the active Composer package, read that package's
`AGENTS.md` completely and preserve unrelated work. Use `ast-index` before raw
search for PHP symbols, usages, inheritance and callers. Do not update the
shared vendor index unless the user explicitly asks. Keep transport tools thin
and business logic in services/models. Run PHP syntax checks when PHP is
available and the narrowest relevant tests.

Project-only MCP providers belong in the application, for example under
`common/mcp`, and are registered through `cmsMcp.toolProviders`. Consult
`skeeks/cms-mcp/AGENTS.md` for the complete service map, tool inventory,
extension configuration, logging categories and shop invariants only when the
task actually changes package or project code.
