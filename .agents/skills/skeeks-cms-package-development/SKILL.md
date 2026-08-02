---
name: skeeks-cms-package-development
description: "Develop and evolve shared SkeekS CMS Composer packages such as skeeks/cms and skeeks/cms-backend. Use only for internal package engineering: public PHP contracts, reusable backend controllers, grids, filters, actions, widgets, assets, migrations, package architecture and cross-project compatibility. Do not use for routine website content, CRM operations or project-only customization."
---

# SkeekS CMS package development

## Establish scope

Treat changes as shared framework work. They may affect every project that
installs the package.

Choose the owning package before editing:

- `skeeks/cms`: CMS models, domain behavior and administration;
- `skeeks/cms-backend`: reusable backend and cabinet controllers, actions,
  widgets, filters and presentation assets;
- `skeeks/cms-backend-admin`: administration-specific component wiring,
  theme subclass, header/footer/quick-access slots, guest/auth layouts,
  administration auth composition and admin-only integrations;
- `skeeks/cms-theme-unify-v2`: temporary compatibility package for old Unify
  layouts, assets and markup while they are being migrated; do not add new
  reusable UI, shell behavior or semantic renderers here;
- `skeeks/cms-backend`: target owner of reusable backend/cabinet shell
  renderers, theme behavior and semantic UI components;
- `skeeks/cms-mcp`: MCP/REST transports, tool contracts and API services;
- `skeeks/cms-oauth2-server`: OAuth resources, clients, codes and tokens.

`skeeks/crm` is a legacy package scheduled for removal. Do not add features,
UI migrations, compatibility work or other new changes there. Treat existing
uncommitted changes in that repository as a separate legacy workstream and do
not include them in new shared UI/theme stages. Put reusable replacement
contracts in their current owning packages, primarily `skeeks/cms` and
`skeeks/cms-backend`, after inspecting active non-CRM consumers.

Keep project-specific text, access rules and visual identity in the project.
Move behavior into a shared package only when at least two controllers,
projects or cabinet types can use the same contract.

## Work conservatively

1. Read the target package's `AGENTS.md` completely.
2. Inspect its Git status and preserve unrelated changes.
3. Use `ast-index` from the shared vendor root before raw search for PHP
   symbols, inheritance, usages and callers.
4. Inspect existing implementations and consumers before changing a public
   property, event, callback signature or default.
5. Keep existing administration backward-compatible. Prefer explicit opt-in
   for new presentation or behavior.
6. Put reusable PHP, markup, JS and structural CSS in the owning package.
7. Expose theme differences through semantic CSS variables instead of copying
   component CSS into every project.
8. New and migrated cabinet HTML uses only semantic `sx-*` classes and
   `data-sx-*` behavior hooks. Remove project-era `portal-*` and Unify
   `u-*`/`u-side-*`/`g-*` classes after moving their required presentation to
   the semantic contract.
9. Project theme CSS assigns shared `--sx-*` brand values directly; do not
   create a parallel project token graph that only aliases back to `--sx-*`.
10. Domain screens that are explicitly project-owned, such as the current
    `skeeks.com` GPD/store workflow, keep their established layout and
    project CSS. A namespace cleanup to `sx-gpd-*`/`sx-store-*` does not make
    that markup a reusable backend contract; promote only independently
    proven common primitives and do not redesign those screens incidentally.
11. Do not routinely clear published assets when keyed asset URLs already
   provide cache busting.

Do not update the shared vendor index unless the user explicitly asks.

## Use package references

Read the relevant reference completely before acting:

- For `BackendModelStandartController`, `BackendGridModelAction`, collection
  renderers, page actions, empty states, adaptive filters and theme tokens,
  read
  [references/backend-model-controller.md](references/backend-model-controller.md).
- For personal accounts and customer cabinets built on the SkeekS backend
  foundation, read both
  [references/backend-model-controller.md](references/backend-model-controller.md)
  and [references/customer-cabinets.md](references/customer-cabinets.md).
- For backend/UPA CSS and JS ownership, AssetBundle dependencies, payload
  budgets, icon providers and migration away from mandatory Unify assets, read
  [references/backend-ui-assets.md](references/backend-ui-assets.md).

Add future package knowledge as focused files under `references/`. Keep this
main workflow concise and do not duplicate the same contract in multiple
references. Record a mechanism only after implementing or verifying it.
Executable source remains the final source of truth.

## Validate shared changes

Verify in proportion to the blast radius:

1. Run `php -l` for every changed PHP file.
2. Run the narrowest available package tests or a deterministic runtime smoke
   test.
3. Exercise both old default behavior and the new opt-in behavior.
4. For UI work, test regular and privileged users, empty and populated data,
   active filters, simple controllers and multi-action controllers.
5. Check light and dark semantic variables when adding reusable CSS.
6. Recheck Git diff and do not stage or commit unrelated work.
