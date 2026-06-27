# Migrations & Upgrades

Upgrading between Formie versions and migrating from other form plugins.


##### [Major upgrade — real-project checklist](/guides/migrations-upgrades/major-upgrade-real-project-checklist)

Major Formie upgrades touch templates, custom fields, integrations, front-end JavaScript, spam settings, and status APIs. This checklist distils [Upgrading From v3](/get-started/upgrading-from-v3) into a practical project runbook — for upgrading an older Formie codebase.

##### [Migrating from Freeform](/guides/migrations-upgrades/migrating-from-freeform)

If your Craft site runs [Solspace Freeform](https://docs.solspace.com/craft/freeform/v5/), Formie's migration tool copies forms, email notifications, and submissions into Formie without modifying Freeform data. This guide walks through the migration UI, handle collisions, unsupported fields, and what to verify before switching production forms to Formie.

##### [Migrating from Sprout Forms](/guides/migrations-upgrades/migrating-from-sprout-forms)

If your Craft site uses [Sprout Forms](https://sprout.barrelstrengthdesign.com/docs/forms/), Formie's migration tool copies forms, email notifications, and submissions into Formie without modifying Sprout data. Sprout Forms layouts are typically simpler than Freeform — migration is often straightforward with few unsupported field types.

##### [Template compatibility audit after upgrade](/guides/migrations-upgrades/template-compatibility-audit-after-upgrade)

After a major upgrade, most regressions show up in Twig templates and front-end JavaScript — renamed render helpers, asset flags, submission value methods, and DOM event names. This audit is a systematic search-and-verify pass you can run on any project before production cutover.
