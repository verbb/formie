# Formie Testing Strategy (Craft + Pest)

## Goal

Build an integration-first suite that boots real Craft and exercises Formie behavior across real forms, fields, submissions, queries, and submit flows.

## Core Principles

- All automated tests are integration tests.
- Every test runs with a real Craft application instance.
- Formie is installed in test setup before each test.
- Craft boots once per test process via PHPUnit bootstrap.
- Tests should run against an isolated test environment/database, not your active local site DB.

## Isolated Test Install

1. Run `composer test:setup` from the plugin root.
2. Edit plugin-local `.env.testing` only if you need to change DB credentials/defaults.
3. Run tests via `composer test` (or `test:slow`, `test:perf`, `test:all`).

`test:setup` will:
- create `.env.testing` from `.env.testing.example` if needed;
- run Craft DB credential setup in non-interactive mode using `CRAFT_DB_*` values from `.env.testing`;
- drop all existing tables in the configured test database;
- install Craft fresh with sane defaults;
- run baseline idempotent seed tasks (user/tag/category groups, upload volume, and sample user/tag/category/entry elements).

Note: the target database itself must already exist and be reachable by the configured DB user.
If your local DB uses a unix socket, set `CRAFT_DB_UNIX_SOCKET` in `.env.testing`.
When `CRAFT_DB_UNIX_SOCKET` is set for MySQL, `test:setup` skips `craft setup/db` and uses env-driven DB config directly.

The test bootstrap hard-fails unless `ENVIRONMENT=testing`, which protects against accidentally running the suite on your active dev/site database.

### Automatic Test Data Reset

The test bootstrap can clear Formie test data at suite start:
- reset Formie data at test suite start (always on).

Reset scope is Formie-owned test data (forms/submissions + submission state/workflow tables), leaving core Craft install metadata intact.

## Stack

- Pest (authoring + runner)
- PHPUnit (engine)
- Craft CMS test runtime + Craft bootstrap

## Current Structure

```text
tests/
  Pest.php
  bootstrap-craft.php
  Fields/
  Forms/
  Submissions/
  Queries/
  Integrations/
```

## Factory Shortcuts

Use `formie()` helpers to keep tests intent-focused:

```php
$form = formie()
    ->form()
    ->multiPage(2)
    ->onPage(1)
    ->singleLineTextField('fullName')
    ->required('fullName')
    ->onPage(2)
    ->emailField('email')
    ->multiLineTextField('message')
    ->create();

$submission = formie()
    ->submission($form)
    ->with(['email' => 'bad'])
    ->allowValidationFailure()
    ->save();

$redirectForm = formie()
    ->form()
    ->singleLineTextField('fullName')
    ->submitAction('url', [
        'url' => 'https://example.test/thanks',
        'tab' => 'new-tab',
        'method' => 'ajax',
    ])
    ->create();
```

## Coverage Targets

- Field normalization and validation through real element saves
- Submission lifecycle and status behavior
- Multi-page progression and storage state continuity
- Seeded-fixture relation/file value contracts (categories/tags/users/entries/assets)
- Multi-page file-upload regression continuity (top-level, group, repeater + back/forward nav)
- Workflow/page/spam/idempotency matrix coverage
- Submit actions (redirect, refresh, message/JSON behavior)
- Submission query behavior
- Integration flow behavior with external providers mocked where appropriate

## Explicitly Out of Scope

- Craft core internals
- Yii internals
- Craft ElementQuery internals
- CP UI rendering details
