---
name: skeeks-cms
description: Develop SkeekS CMS/Yii PHP code and handle advanced or unfamiliar site content, shop, MCP, REST and OAuth2 workflows. Routine central CRM reads use the global AGENTS direct-first client and do not require this skill.
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
- `tree.list`, optionally with `-ParentId`;
- `content.list`, optionally with `-ContentId` or `-ParentId`;
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
only after refresh genuinely failed or the user revoked the connection.

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
