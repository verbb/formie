# Formie Plugin Docs

Formie’s docs are structured to help you install the plugin, build forms, put them on the site, style them, connect them to other systems, and extend them when needed.

## Screenshot automation

Screenshots are generated with **`@verbb/docs-screenshots`**, the published npm package for Craft CP screenshot automation. This docs package depends on it in **devDependencies** and exposes it as **`npm run docs:screenshots`** (run that from the **Formie plugin root** — the directory that contains the plugin’s main `composer.json` and the npm script that invokes this docs workspace).

The harness creates its own disposable Craft install and database. Point it at a dedicated database server with **`CRAFT_SCREENSHOT_DB_*`** environment variables, then install the browser binary once with **`npx playwright install chromium`**.

**Layout**

- **`@verbb/docs-screenshots`** — CLI and shared capture tooling (see the package’s own README on npm for flags and behaviour).
- **Formie** — one **`.screenshot.ts` scenario** beside each page it illustrates (for example `forms/form-builder.screenshot.ts`), plus plugin-local bootstrap and fixtures under **`.screenshots/`** (Formie-specific helpers under **`.screenshots/formie/`**). Generated assets land under **`_screenshots/`** in this docs tree.

**Typical workflow**

1. `npm run docs:screenshots -- prepare`
2. `npm run docs:screenshots -- preview --reuse-install last --filter form-builder`
3. Adjust the scenario while the preview overlay shows the capture region.
4. `npm run docs:screenshots -- capture --reuse-install last --filter form-builder`

**Useful flags** (see upstream docs for the full set)

- `--preview <id>` — headed preview with capture overlay.
- `--inspect <id>` — preview and pause in Playwright Inspector.
- `--headed` — visible browser without extra debug behaviour.
- `--save-from-preview` — persist framing from preview to the scenario output path.
- `--reuse-install last` — skip Craft bootstrap; reuse the latest prepared install.
- `--keep-install` — keep the temporary install for manual reuse.

**List scenario ids** (from the root of **this** VitePress site — the folder that contains this `README.md`):

```bash
rg -n "id:" . -g "*.screenshot.ts"
```

## Sections

- [Get Started](/get-started/installation-setup) for installation, requirements, configuration, troubleshooting, and upgrading.
- [Forms](/forms/form-builder) for building forms, configuring behavior, spam protection, [submission screening](/forms/submission-screening), and setting up notifications.
- [Submissions](/submissions/overview) for understanding saved records, statuses, exports, relations, and editing patterns.
- [Fields](/fields/address) for choosing field types and understanding the settings that actually affect behavior.
- [Templates](/templates/rendering-forms) for Twig and Craft implementation patterns.
- [Theming](/theming/overview) for theme config, overrides, and custom rendering.
- [Frontend](/frontend/frontend-assets) for frontend packages, cached forms, and tracking patterns.
- [GraphQL](/graphql/query-forms) for headless querying and submission workflows.
- [Getting Elements](/getting-elements/form-queries) for form and submission queries.
- [Integrations](/integrations/address-providers/) for provider families and setup patterns.
- [Developers](/developers/events/form-events) for extension points, events, and command-line tooling.
- [Reference](/reference/form) for the core public objects most template authors work with.
- [Guides](/guides/migrations-upgrades/) for long-form walkthroughs — including [migrating from Freeform or Sprout Forms](/guides/migrations-upgrades/migrating-from-freeform), version upgrades, and more. Browse by category in the sidebar.
