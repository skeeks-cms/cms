---
name: skeeks-cms
description: Develop and operate SkeekS CMS and Yii/PHP projects that depend on any Composer package matching skeeks/*. Use for SkeekS CMS internals, models, controllers, components, package extensions, usages, inheritance, dependencies, connected SkeekS MCP site operations, content, CRM, shop management, or OAuth2 server architecture.
---

# SkeekS CMS

## Overview

Use this skill for development and operations involving SkeekS CMS, its Yii2
packages, and sites that expose SkeekS MCP tools.

## Detection

Treat a project as a SkeekS CMS project when either condition is true:

- The user says the project uses SkeekS CMS.
- The project `composer.json` contains a dependency or development dependency
  whose package name matches `skeeks/*`.

Check `composer.json` early for PHP project tasks unless the user only asks a
general question. Do not load the entire Composer vendor directory into context.

## Package discovery

When package internals are needed, locate the active Composer package rather
than assuming a machine-specific path. Prefer these sources in order:

1. The current repository when its `composer.json` names the requested package.
2. The project's `vendor/skeeks/<package>` directory.
3. The installed path reported by Composer.

Use the package version installed by the current project unless the user
explicitly asks to inspect another checkout or version.

## Code search workflow

For PHP symbols, classes, usages, implementations, callers, inheritance, and
dependencies, use `ast-index` before raw text search when it is available:

```powershell
ast-index search "Query"
ast-index class "ClassName"
ast-index symbol "SymbolName"
ast-index usages "SymbolName"
ast-index implementations "InterfaceName"
ast-index callers "methodName"
ast-index outline "path\to\file.php"
```

Run indexed searches from the Composer vendor root or the indexed repository
root. Use `rg` or an equivalent text search when:

- `ast-index` returns no useful result or is unavailable.
- The task involves raw text, configuration, translations, markup, or regular
  expressions.
- The search targets application code outside the indexed vendor tree.

Do not rebuild or update a shared index unless the user explicitly requests it.

## Development rules

When working in an application project:

- Read the local `composer.json`, repository instructions, and relevant
  application files first.
- Use vendor lookups only for framework or package behavior and symbol
  discovery.
- Prefer existing project conventions over generic Yii or SkeekS assumptions.
- Keep edits scoped to the application or package requested by the user.
- Do not modify installed vendor code unless the user explicitly asks to change
  package internals in a source checkout.

When working in a SkeekS package repository:

- Read its `AGENTS.md` or equivalent repository instructions completely before
  editing.
- Check Git status and preserve unrelated work.
- Avoid unrelated refactors in shared packages.
- Verify public contracts and usages before changing them.
- Run `php -l` for changed PHP files and the narrowest relevant tests.

## Core package boundaries

The `skeeks/cms` package owns reusable CMS models, controllers, migrations,
widgets, components, and configuration. Keep browser and administration
behavior in backend controllers, shared business behavior in services or
models, and transport controllers narrow.

Use code search to verify current definitions and contracts. Common starting
points include:

- `CmsTree` for site sections and pages.
- `CmsContent` for configured content containers.
- `CmsContentType` for section and content type metadata.
- `CmsContentElement` for publications and other content records.
- Storage models and services for uploaded files.
- Task models and services for CRM task behavior.

## MCP and OAuth packages

Keep package ownership separated:

- `skeeks/cms-mcp` owns MCP transport, tool contracts, providers, and
  MCP-specific application services.
- `skeeks/cms-oauth2-server` owns OAuth controllers, models, migrations,
  resources, and scopes.
- `skeeks/cms` owns reusable CMS domain behavior and must not absorb MCP or
  OAuth transport code.

For MCP or OAuth work, read the target package's repository instructions before
editing. Keep tools thin and delegate model access, validation, transactions,
and business rules to domain services. Do not introduce delete tools; create
drafts first and publish explicitly.

## Connected SkeekS sites

For a site with `skeeks/cms-mcp` installed, two OAuth-protected transports expose
the same authorized tool registry and domain services:

```text
MCP:  https://<site-domain>/cms/mcp
REST: https://<site-domain>/cms/rest-api
```

Use MCP when the current AI client can register an MCP server. Use REST for
clients, scripts or environments that can make OAuth bearer HTTP requests but
cannot load an MCP server. REST is not a separate or reduced business API: it
executes the same tools and applies the same OAuth scopes and CMS RBAC checks.

Derive the MCP endpoint from the site's canonical public origin:

```text
https://<site-domain>/cms/mcp
```

In Codex, configure the server URL and OAuth resource to that exact endpoint.
Name the MCP server after the specific site, not generically after SkeekS,
so multiple project sites can coexist without collisions. Use a stable
lowercase ASCII key derived from the domain, for example `blizco` for
`bliz.co`:

```toml
[mcp_servers.<site-key>]
url = "https://<site-domain>/cms/mcp"
oauth_resource = "https://<site-domain>/cms/mcp"
```

For `https://bliz.co/cms/mcp`, the project-scoped configuration should be:

```toml
[mcp_servers.blizco]
url = "https://bliz.co/cms/mcp"
oauth_resource = "https://bliz.co/cms/mcp"
```

Use the actual target site's public domain and preserve any intentional
deployment base path. Do not use the obsolete `/cms/mcp-task/create` endpoint.
After changing global MCP configuration, tell the user that restarting the
client or opening a new task may be required to reload the tool registry.

### REST connection

Do not assume that a SkeekS CMS site exposes a static conventional CRUD API.
When `skeeks/cms-mcp` is installed, use the self-describing REST adapter rooted
at the site's canonical public origin:

```text
GET  /cms/rest-api                  authenticated API metadata
GET  /cms/rest-api/tools            authorized tools and JSON Schemas
GET  /cms/rest-api/tools/index      compact authorized tool index
GET  /cms/rest-api/tools/{name}     one authorized tool schema
GET  /cms/rest-api/context          site-context shortcut
GET  /cms/rest-api/openapi          authorized OpenAPI 3.0 document
POST /cms/rest-api/tools/{tool_name} execute a tool
```

The OAuth protected-resource identifier is the exact absolute REST root, for
example `https://example.com/cms/rest-api`; it is distinct from the MCP
resource `https://example.com/cms/mcp`. Discover OAuth server metadata through
the site's well-known endpoints. The standard SkeekS endpoints include
`/cms/oauth/authorize`, `/cms/oauth/token` and `/cms/oauth/register`.

For every REST workflow:

1. Complete authorization code + PKCE for the REST resource and requested
   scopes. Store client credentials and tokens only in an operating-system
   credential store or an equivalently protected local store.
2. Treat `GET /cms/rest-api/tools` as the source of truth, but persist its
   credential-specific ETag and response outside the chat. Revalidate with
   `If-None-Match`; reuse the cached schemas on `304 Not Modified`. Optional
   packages, project providers, OAuth scopes and CMS permissions change the
   authorized `tools_revision`, so do not key the cache only by package version.
3. Call `GET /cms/rest-api/context` when site context is needed.
4. Execute a discovered tool with `POST /cms/rest-api/tools/<url-encoded-name>`,
   `Content-Type: application/json`, and its arguments as the top-level JSON
   object.
5. Refresh an expired or nearly expired access token through
   `/cms/oauth/token` with `grant_type=refresh_token`. Refresh tokens rotate:
   atomically save both returned tokens and never retry a token after a
   successful rotation.
6. If refresh is revoked or expired, repeat authorization code + PKCE. Never
   print, log, commit or place decrypted credentials in command arguments.

#### Fast Windows REST client

When Codex runs on Windows, locate the active `skeeks/cms-mcp` package. If the
site does not yet have a DPAPI-protected credential store at
`%USERPROFILE%\.codex\oauth\<domain>-rest-api.json`, run its canonical login
client once:

```powershell
& "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -ExecutionPolicy Bypass -File '<cms-mcp-dir>\scripts\skeeks-rest-login.ps1' -Site 'example.com'
```

The login client discovers OAuth metadata, dynamically registers an expandable
client, creates PKCE S256, starts a loopback callback, opens the user's default
browser and saves the resulting token pair with DPAPI. It deliberately ignores
browser callback requests that do not contain `code` or `error`; do not replace
it with an ad-hoc listener, inspect browser cookies, poll for a credential file
or register another client while it is running. If a valid credential store
already exists, the script exits without opening OAuth; use
`-ForceAuthorization` only after refresh has genuinely failed or the connection
was revoked.

After authorization, use `scripts/skeeks-rest.ps1` instead of writing inline
PowerShell for DPAPI or HTTP.
The helper handles hex-encoded DPAPI values, refresh-token rotation with an
inter-process lock, atomic credential-store replacement, UTF-8 output and REST
requests without exposing tokens in command arguments or output. It also keeps
the authorized tool catalog under
`%USERPROFILE%\.codex\cache\skeeks-cms\<domain>` and revalidates it by ETag.
The cache contains schemas and revision metadata only, never OAuth secrets.

Run the helper with Windows PowerShell outside the sandbox on the first attempt:
DPAPI `CurrentUser` keys may be unavailable inside an isolated process. Request
approval for the fixed script command when necessary; do not first experiment
with `ProtectedData`, encodings or decrypted values in ad-hoc commands.

```powershell
$powershell = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$restClient = '<cms-mcp-dir>\scripts\skeeks-rest.ps1'
& $powershell -NoProfile -ExecutionPolicy Bypass -File $restClient -Site 'example.com' -Action tools -ToolPattern '^cms_task_'
& $powershell -NoProfile -ExecutionPolicy Bypass -File $restClient -Site 'example.com' -Action context
$json = '{"named_filters":["mine","active"],"limit":100}'
$arguments = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($json))
& $powershell -NoProfile -ExecutionPolicy Bypass -File $restClient -Site 'example.com' -Action execute -ToolName 'cms_task_list' -ArgumentsBase64 $arguments
```

Prefer `-ArgumentsBase64` for generated arguments because native Windows
PowerShell invocation can remove JSON quotes. Use `-ArgumentsPath <json-file>`
when the JSON already exists as a file. Reserve `-ArgumentsJson` for invocation
contexts that preserve arguments without native shell re-parsing.
The output contains request duration and the REST response. A missing credential
store means that the one-time REST OAuth authorization must be completed; do
not fall back to inventing credentials or reading an MCP token for the distinct
REST resource.

For speed, let `-Action tools` reuse or conditionally revalidate its persistent
catalog, filter the cached result with `-ToolPattern`, then execute the resolved
tool directly. The output `cache_status` is `hit`, `validated` or `updated`.
Use `-Action tool-schema -ToolName <name>` only when one uncached schema is
needed, and `-Action tools-index` for a compact inventory. Do not call `context`
for ordinary CRM requests such as
"my active tasks": filters such as `mine` derive the user from OAuth, and site
theme context is irrelevant. Do not inspect the credential JSON, probe DPAPI or
repeat OAuth while the helper succeeds. A typical CRM read should require one
cache lookup or lightweight ETag revalidation and one execute request.

The catalog exposes `api_version`, `server_version` and `tools_revision`.
`tools_revision` is the only reliable schema cache validator because it covers
the exact OAuth/RBAC-authorized inventory. Reuse known schemas across Codex
tasks instead of making the model rediscover every method. The server also
supports `/tools?prefix=cms_task_`, comma-separated `names`, and `q` filters for
clients that do not use the helper.

Before a REST mutation, resolve referenced records and check duplicates. Let
the server derive ownership from the OAuth identity, honor confirmation
responses before consequential actions, then read the changed record back and
report its identifier and state.

When project instructions designate a central SkeekS CRM, use that CRM's REST
root for companies, projects, tasks and finance. For pages, content, settings
and other website-level work, use the current website's own MCP or REST root.
If the intended site remains ambiguous and a mutation could affect the wrong
project, ask the user to identify it.

### Connection lifecycle and fast path

Treat the MCP tool registry as fixed for the lifetime of the current Codex
task or process. Configuration changes and newly completed OAuth sessions do
not hot-load a missing server into an already running task.

- If the site's MCP tools are already present, call the available site-context
  tool immediately. Do not run CLI discovery or repeat OAuth first.
- If a newly added server is absent from the current tool registry, do not try
  to hot-reload it, launch a nested `codex exec`, wait for every global MCP
  server to initialize, or bypass Codex with a direct HTTP request using stored
  OAuth credentials.
- Complete OAuth once through **Settings > MCP servers > Authenticate** or
  `codex mcp login <site-key>`, then tell the user to restart Codex or open a
  new task. Stop there when the current task still lacks the server; the next
  task should call the site-context tool first.
- Prefer project-scoped `.codex/config.toml` entries for site-specific MCP
  servers. Keep unused global servers disabled so unrelated Codex processes do
  not initialize them.
- Use `enabled_tools` only to limit the exposed tool surface; do not claim that
  it hot-loads a server or guarantees faster authentication.

### Operating a connected site

Use runtime `tools/list` as the source of truth because installed optional
packages and project providers change the tool inventory. Never invent a
missing tool or rely on a copied static inventory.

Start a new site workflow by reading the available site-context tool. Read the
active theme and effective component settings before generating content.
Resolve foreign keys, types, statuses, categories, workers, tree parents,
content types, and price types with read tools before mutations.

For every mutation:

1. Check prerequisites and possible duplicates.
2. Create or update under the OAuth identity; do not pass ownership fields that
   the server derives from authentication.
3. Respect confirmation responses before retrying calls, messages, duplicate
   creation, publication, or inventory approval.
4. Read the record back and report its identifier, state, and verification URL
   when available.

Create pages, publications, and product cards as drafts unless the user
explicitly asks to publish. Prefer server-side filters, date ranges, pagination,
and statistics tools over loading complete tables.

When the user asks to create a SkeekS CMS task, use the task-creation tool
actually exposed by the connected server. Resolve the executor from project
context or ask the user. Apply a default executor only when local project
instructions define one.
