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

## Verification

Before finishing a change:

1. Check the package Git status and preserve unrelated work.
2. Run `php -l` for changed PHP files.
3. Run the narrowest relevant automated tests or smoke checks available.
4. Verify package boundaries and configuration wiring.
5. Stage only intended files; never stage `.idea/`.

Keep durable agent knowledge in this root `AGENTS.md`. Do not add auxiliary Markdown documentation directories unless the user explicitly changes this convention.
