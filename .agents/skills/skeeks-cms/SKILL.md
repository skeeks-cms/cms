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

For a site with `skeeks/cms-mcp` installed, derive its MCP endpoint from the
site's canonical public origin:

```text
https://<site-domain>/cms/mcp
```

In Codex, configure the server URL and OAuth resource to that exact endpoint:

```toml
[mcp_servers.skeeks]
url = "https://<site-domain>/cms/mcp"
oauth_resource = "https://<site-domain>/cms/mcp"
```

Use the actual target site's public domain and preserve any intentional
deployment base path. Do not use the obsolete `/cms/mcp-task/create` endpoint.
After changing global MCP configuration, tell the user that restarting the
client or opening a new task may be required to reload the tool registry.

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
