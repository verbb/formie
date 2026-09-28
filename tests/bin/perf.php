<?php

declare(strict_types=1);

ob_start();

use craft\errors\GqlException;
use craft\models\GqlSchema;
use Tests\Support\ResetTestDatabase;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\controllers\FieldsController;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\helpers\FieldAccess;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\queries\FormQuery;
use verbb\formie\gql\queries\SubmissionQuery;
use verbb\formie\gql\types\generators\FormGenerator;
use verbb\formie\gql\types\generators\SubmissionGenerator;
use verbb\formie\helpers\Table;
use verbb\formie\models\BrowserModule;
use verbb\formie\services\SubmissionGrants;
use yii\console\ExitCode;

$pluginRoot = dirname(__DIR__, 2);

require $pluginRoot . '/tests/bootstrap.php';
require_once $pluginRoot . '/tests/Support/Factories/functions.php';
require_once $pluginRoot . '/tests/Support/ResetTestDatabase.php';
require_once $pluginRoot . '/tests/Support/submission-workflow.php';

class FormiePerfCommand extends craft\db\Command
{
    private static bool $recording = false;
    private static array $queries = [];

    public static function startRecording(): void
    {
        self::$recording = true;
        self::$queries = [];
    }

    public static function stopRecording(): array
    {
        self::$recording = false;

        return self::$queries;
    }

    public function execute()
    {
        if (!self::$recording) {
            return parent::execute();
        }

        $started = microtime(true);

        try {
            return parent::execute();
        } finally {
            $this->recordQuery('execute', $started);
        }
    }

    protected function queryInternal($method, $fetchMode = null)
    {
        if (!self::$recording) {
            return parent::queryInternal($method, $fetchMode);
        }

        $started = microtime(true);

        try {
            return parent::queryInternal($method, $fetchMode);
        } finally {
            $this->recordQuery('query', $started);
        }
    }

    private function recordQuery(string $type, float $started): void
    {
        $rawSql = (string)$this->getRawSql();

        self::$queries[] = [
            'type' => $type,
            'elapsedMs' => round((microtime(true) - $started) * 1000, 3),
            'sql' => $rawSql,
            'normalizedSql' => normalizePerfSql($rawSql),
        ];
    }
}

require $pluginRoot . '/tests/bootstrap-craft.php';

if ((getenv('ENVIRONMENT') ?: '') !== 'testing') {
    fwrite(STDERR, "Refusing to run perf harness outside ENVIRONMENT=testing.\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

if (!Craft::$app->getDb()->tableExists('{{%plugins}}')) {
    fwrite(STDERR, "Testing database is not installed. Run `ddev test` first.\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}


// Swap in an instrumented command class after Craft has booted so measured
// scenarios can collect query counts without changing application code.
Craft::$app->getDb()->commandClass = FormiePerfCommand::class;

$options = parsePerfOptions($argv);
$command = $options['command'];
$profile = getPerfProfile((string)$options['profile']);

try {
    if ($command === 'list') {
        writePerfOutput([
            'profiles' => array_keys(perfProfiles()),
            'scenarios' => array_keys(perfScenarios()),
        ], (string)$options['format']);

        exit(ExitCode::OK);
    }

    if ($command === 'seed') {
        $seed = ensurePerfSeed($profile, (bool)$options['fresh']);
        writePerfOutput(['seed' => $seed], (string)$options['format']);

        exit(ExitCode::OK);
    }

    if ($command !== 'run') {
        fwrite(STDERR, "Unknown command `{$command}`. Use `list`, `seed`, or `run`.\n");
        exit(ExitCode::UNSPECIFIED_ERROR);
    }

    $seed = ensurePerfSeed($profile, (bool)$options['fresh']);
    $scenarioName = (string)$options['scenario'];
    $scenarios = perfScenarios();
    $selectedScenarios = $scenarioName === 'all' ? array_keys($scenarios) : [$scenarioName];
    $results = [];

    foreach ($selectedScenarios as $selectedScenario) {
        if (!isset($scenarios[$selectedScenario])) {
            fwrite(STDERR, "Unknown scenario `{$selectedScenario}`. Run `php tests/bin/perf.php list`.\n");
            exit(ExitCode::UNSPECIFIED_ERROR);
        }

        $result = measurePerfScenario($selectedScenario, $profile, (int)$options['iterations'], $scenarios[$selectedScenario]);
        $result['seed'] = $seed;
        $results[] = $result;

        if ($options['format'] === 'ndjson') {
            fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL);
        }
    }

    if ($options['format'] !== 'ndjson') {
        if ($options['format'] === 'summary') {
            $results = array_map('summarizePerfResult', $results);
        }

        writePerfOutput($scenarioName === 'all' ? $results : $results[0], (string)$options['format']);
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e::class . ': ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    exit(ExitCode::UNSPECIFIED_ERROR);
}

function parsePerfOptions(array $argv): array
{
    $args = array_slice($argv, 1);
    $command = $args[0] ?? 'run';

    if ($command !== 'list' && $command !== 'seed' && $command !== 'run') {
        $command = 'run';
        array_unshift($args, 'run');
    }

    $options = [
        'command' => $command,
        'scenario' => $command === 'run' ? ($args[1] ?? 'all') : 'all',
        'profile' => 'small',
        'iterations' => 25,
        'fresh' => false,
        'format' => 'ndjson',
    ];

    foreach ($args as $arg) {
        if ($arg === '--fresh') {
            $options['fresh'] = true;
            continue;
        }

        if (!str_starts_with($arg, '--')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, true);

        if (array_key_exists($name, $options)) {
            $options[$name] = $value;
        }
    }

    $options['iterations'] = max(1, (int)$options['iterations']);
    $options['format'] = in_array($options['format'], ['json', 'pretty', 'ndjson', 'summary'], true) ? $options['format'] : 'ndjson';

    return $options;
}

function perfProfiles(): array
{
    return [
        'small' => [
            'name' => 'small',
            'forms' => 3,
            'fieldsPerForm' => 8,
            'submissions' => 30,
            'nestedFieldSets' => 1,
            'pages' => 1,
            'advancedFields' => false,
        ],
        'medium' => [
            'name' => 'medium',
            'forms' => 8,
            'fieldsPerForm' => 15,
            'submissions' => 100,
            'nestedFieldSets' => 2,
            'pages' => 3,
            'advancedFields' => true,
        ],
        'large' => [
            'name' => 'large',
            'forms' => 20,
            'fieldsPerForm' => 25,
            'submissions' => 250,
            'nestedFieldSets' => 3,
            'pages' => 5,
            'advancedFields' => true,
        ],
        'installation' => [
            'name' => 'installation',
            'forms' => 10,
            'fieldsPerForm' => 196,
            'submissions' => 1,
            'nestedFieldSets' => 0,
            'pages' => 5,
            'advancedFields' => false,
        ],
    ];
}

function getPerfProfile(string $name): array
{
    $profiles = perfProfiles();

    if (!isset($profiles[$name])) {
        fwrite(STDERR, "Unknown profile `{$name}`. Available: " . implode(', ', array_keys($profiles)) . "\n");
        exit(ExitCode::UNSPECIFIED_ERROR);
    }

    return $profiles[$name];
}

function perfScenarios(): array
{
    return [
        'forms:list' => 'runFormsListPerfScenario',
        'fields:for-forms' => 'runFieldsForFormsPerfScenario',
        'fields:config-vs-hydrated' => 'runFieldsConfigVsHydratedPerfScenario',
        'submissions:query' => 'runSubmissionsQueryPerfScenario',
        'submissions:form-handle-query' => 'runSubmissionsFormHandleQueryPerfScenario',
        'submissions:save' => 'runSubmissionsSavePerfScenario',
        'submissions:project' => 'runSubmissionsProjectPerfScenario',
        'graphql:schema' => 'runGraphqlSchemaPerfScenario',
        'client:manifest' => 'runClientManifestPerfScenario',
        'builder:load' => 'runBuilderLoadPerfScenario',
        'builder:edit-save' => 'runBuilderEditSavePerfScenario',
        'builder:export' => 'runBuilderExportPerfScenario',
        'render:form' => 'runFormRenderPerfScenario',
        'render:assets' => 'runFormAssetsPerfScenario',
        'render:summary-fragment' => 'runSummaryFragmentPerfScenario',
        'client:bootstrap' => 'runClientBootstrapPerfScenario',
        'submit:complete' => 'runCompleteSubmitPerfScenario',
        'resume:load' => 'runResumeLoadPerfScenario',
        'revise:submit' => 'runReviseSubmitPerfScenario',
    ];
}

function ensurePerfSeed(array $profile, bool $fresh): array
{
    if ($fresh) {
        ResetTestDatabase::resetFormieData();
        resetPerfRuntimeCaches();
    }

    $mainForm = findPerfMainForm($profile);

    if (!$mainForm) {
        seedPerfForms($profile);
        $mainForm = findPerfMainForm($profile);
    }

    if (!$mainForm) {
        throw new RuntimeException('Unable to seed perf harness forms.');
    }

    $existingSubmissions = (int)Submission::find()
        ->formId((int)$mainForm->id)
        ->anyStatus()
        ->count();

    if ($existingSubmissions < $profile['submissions']) {
        seedPerfSubmissions($mainForm, $profile, $profile['submissions'] - $existingSubmissions, $existingSubmissions);
    }

    resetPerfRuntimeCaches();

    return [
        'profile' => $profile['name'],
        'forms' => count(findPerfProfileForms($profile)),
        'mainFormId' => (int)$mainForm->id,
        'mainFormHandle' => (string)$mainForm->handle,
        'submissions' => (int)Submission::find()->formId((int)$mainForm->id)->anyStatus()->count(),
    ];
}

function seedPerfForms(array $profile): void
{
    for ($formIndex = 1; $formIndex <= $profile['forms']; $formIndex++) {
        $builder = formie()->form([
            'title' => "Perf Harness {$profile['name']} {$formIndex}",
            'handle' => perfHandle($profile, (string)$formIndex),
        ])->multiPage($profile['pages']);

        $builder
            ->onPage(1)
            ->singleLineTextField('fullName')
            ->emailField('email')
            ->numberField('score');

        for ($fieldIndex = 1; $fieldIndex <= $profile['fieldsPerForm']; $fieldIndex++) {
            $builder->onPage(($fieldIndex % $profile['pages']) + 1);
            $builder->singleLineTextField("text{$formIndex}_{$fieldIndex}");
        }

        if ($profile['advancedFields']) {
            $builder
                ->onPage(min(2, $profile['pages']))
                ->nameField('person', [
                    'useMultipleFields' => true,
                    'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
                ])
                ->addressField('address', [
                    'rows' => (new Address())->getSubFields(),
                ])
                ->entriesField('relatedEntries');
        }

        $nestedRows = [[
            'fields' => [[
                'type' => verbb\formie\fields\SingleLineText::class,
                'handle' => 'innerText',
                'label' => 'Inner Text',
            ]],
        ]];

        $builder->onPage($profile['pages']);

        for ($nestedIndex = 1; $nestedIndex <= $profile['nestedFieldSets']; $nestedIndex++) {
            $builder
                ->groupField("group{$nestedIndex}", ['rows' => $nestedRows])
                ->repeaterField("lineItems{$nestedIndex}", ['rows' => $nestedRows]);
        }

        $builder->summaryField('summary');

        $builder->create();
    }
}

function seedPerfSubmissions(Form $form, array $profile, int $count, int $offset): void
{
    $formNumber = perfFormIndexFromHandle((string)$form->handle);

    for ($submissionIndex = 1; $submissionIndex <= $count; $submissionIndex++) {
        $absoluteIndex = $offset + $submissionIndex;
        $payload = [
            'fullName' => "Perf User {$absoluteIndex}",
            'email' => "perf{$absoluteIndex}@example.test",
            'score' => (string)$absoluteIndex,
        ];

        for ($fieldIndex = 1; $fieldIndex <= $profile['fieldsPerForm']; $fieldIndex++) {
            $payload["text{$formNumber}_{$fieldIndex}"] = "value-{$absoluteIndex}-{$fieldIndex}";
        }

        for ($nestedIndex = 1; $nestedIndex <= $profile['nestedFieldSets']; $nestedIndex++) {
            $payload["group{$nestedIndex}"] = ['innerText' => "Group {$absoluteIndex} {$nestedIndex}"];
            $payload["lineItems{$nestedIndex}"] = [
                ['innerText' => "Line {$absoluteIndex} {$nestedIndex} A"],
                ['innerText' => "Line {$absoluteIndex} {$nestedIndex} B"],
            ];
        }

        formie()->submission($form)->with($payload)->save();
    }
}

function measurePerfScenario(string $name, array $profile, int $iterations, callable $callback): array
{
    resetPerfRuntimeCaches();

    if (function_exists('memory_reset_peak_usage')) {
        memory_reset_peak_usage();
    }

    FormiePerfCommand::startRecording();
    $started = microtime(true);
    $result = $callback($profile, $iterations);
    $elapsedMs = round((microtime(true) - $started) * 1000, 3);
    $queries = FormiePerfCommand::stopRecording();

    return [
        'scenario' => $name,
        'profile' => $profile['name'],
        'iterations' => $iterations,
        'elapsedMs' => $elapsedMs,
        'memoryPeakBytes' => memory_get_peak_usage(true),
        'queries' => summarizePerfQueries($queries),
        'result' => $result,
    ];
}

function runFormsListPerfScenario(array $profile, int $iterations): array
{
    $counts = [];

    for ($i = 0; $i < $iterations; $i++) {
        resetPerfRuntimeCaches();
        $counts[] = count(Formie::$plugin->getForms()->getAllFormsWithLayouts());
    }

    return ['formCounts' => summarizePerfValues($counts)];
}

function runFieldsForFormsPerfScenario(array $profile, int $iterations): array
{
    $fieldGroupCounts = [];
    $fieldCounts = [];

    for ($i = 0; $i < $iterations; $i++) {
        resetPerfRuntimeCaches();
        $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();
        $formIds = array_values(array_map(static fn(Form $form): int => (int)$form->id, $forms));
        $fieldsByForm = Formie::$plugin->getFields()->getAllFieldsForForms($formIds);

        $fieldGroupCounts[] = count($fieldsByForm);
        $fieldCounts[] = array_sum(array_map('count', $fieldsByForm));
    }

    return [
        'fieldGroupCounts' => summarizePerfValues($fieldGroupCounts),
        'fieldCounts' => summarizePerfValues($fieldCounts),
    ];
}

function runFieldsConfigVsHydratedPerfScenario(array $profile, int $iterations): array
{
    $configMs = [];
    $hydratedMs = [];
    $configCounts = [];
    $hydratedCounts = [];

    for ($i = 0; $i < $iterations; $i++) {
        resetPerfRuntimeCaches();
        $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();
        $formIds = array_values(array_map(static fn(Form $form): int => (int)$form->id, $forms));

        $started = microtime(true);
        $configsByForm = Formie::$plugin->getFields()->getAllFieldConfigsForForms($formIds);
        $configMs[] = round((microtime(true) - $started) * 1000, 3);
        $configCounts[] = array_sum(array_map('count', $configsByForm));

        // Measure hydrated fields from a fresh cache so this captures the full
        // config-load plus `createField()` cost paid by element/model consumers.
        resetPerfRuntimeCaches();
        $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();
        $formIds = array_values(array_map(static fn(Form $form): int => (int)$form->id, $forms));

        $started = microtime(true);
        $fieldsByForm = Formie::$plugin->getFields()->getAllFieldsForForms($formIds);
        $hydratedMs[] = round((microtime(true) - $started) * 1000, 3);
        $hydratedCounts[] = array_sum(array_map('count', $fieldsByForm));
    }

    return [
        'configMs' => summarizePerfValues($configMs),
        'hydratedMs' => summarizePerfValues($hydratedMs),
        'configCounts' => summarizePerfValues($configCounts),
        'hydratedCounts' => summarizePerfValues($hydratedCounts),
    ];
}

function runSubmissionsQueryPerfScenario(array $profile, int $iterations): array
{
    $form = requirePerfMainForm($profile);
    $hits = 0;

    for ($i = 1; $i <= $iterations; $i++) {
        $score = (string)((($i - 1) % $profile['submissions']) + 1);
        $hits += count(Submission::find()
            ->formId((int)$form->id)
            ->field('score', $score)
            ->all());
    }

    return ['hits' => $hits];
}

function runSubmissionsFormHandleQueryPerfScenario(array $profile, int $iterations): array
{
    $form = requirePerfMainForm($profile);
    $formIdHits = 0;
    $formHandleHits = 0;
    $formIdMs = [];
    $formHandleMs = [];

    for ($i = 1; $i <= $iterations; $i++) {
        $score = (string)((($i - 1) % $profile['submissions']) + 1);

        $started = microtime(true);
        $formIdHits += count(Submission::find()
            ->formId((int)$form->id)
            ->field('score', $score)
            ->all());
        $formIdMs[] = round((microtime(true) - $started) * 1000, 3);

        $started = microtime(true);
        $formHandleHits += count(Submission::find()
            ->form((string)$form->handle)
            ->field('score', $score)
            ->all());
        $formHandleMs[] = round((microtime(true) - $started) * 1000, 3);
    }

    return [
        'formIdHits' => $formIdHits,
        'formHandleHits' => $formHandleHits,
        'formIdMs' => summarizePerfValues($formIdMs),
        'formHandleMs' => summarizePerfValues($formHandleMs),
    ];
}

function runSubmissionsSavePerfScenario(array $profile, int $iterations): array
{
    $form = requirePerfMainForm($profile);
    $before = (int)Submission::find()->formId((int)$form->id)->anyStatus()->count();

    seedPerfSubmissions($form, $profile, $iterations, $before + 100000);

    return [
        'saved' => $iterations,
        'before' => $before,
        'after' => (int)Submission::find()->formId((int)$form->id)->anyStatus()->count(),
    ];
}

function runSubmissionsProjectPerfScenario(array $profile, int $iterations): array
{
    $form = requirePerfMainForm($profile);
    $submission = Submission::find()->formId((int)$form->id)->anyStatus()->one();

    if (!$submission) {
        throw new RuntimeException('No seeded submission found.');
    }

    $summaryCounts = [];

    for ($i = 0; $i < $iterations; $i++) {
        $summaryCounts[] = count($submission->getValuesForSummary());
        $submission->getValuesForExport();
        $submission->getFieldValuesForField(verbb\formie\fields\SingleLineText::class);
    }

    return ['summaryCounts' => summarizePerfValues($summaryCounts)];
}

function runGraphqlSchemaPerfScenario(array $profile, int $iterations): array
{
    $counts = [];

    withPerfGqlSchema(function () use ($iterations, &$counts): void {
        for ($i = 0; $i < $iterations; $i++) {
            Craft::$app->getGql()->flushCaches();
            Formie::$plugin->getForms()->invalidateFormCaches();

            $formQueries = FormQuery::getQueries(false);
            $submissionQueries = SubmissionQuery::getQueries(false);
            $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();
            $submissionMutations = [];
            $formTypes = [];
            $submissionTypes = [];

            // Generate per-form artifacts directly so this scenario measures
            // type/mutation construction even in console contexts where token
            // scope helpers can short-circuit aggregate GraphQL lists.
            foreach ($forms as $form) {
                $submissionMutations[] = SubmissionMutation::createSaveMutation($form);
                $formTypes[] = FormGenerator::generateType($form);
                $submissionTypes[] = SubmissionGenerator::generateType($form);
            }

            $counts[] = [
                'formQueries' => count($formQueries),
                'submissionQueries' => count($submissionQueries),
                'submissionMutations' => count($submissionMutations),
                'formTypes' => count($formTypes),
                'submissionTypes' => count($submissionTypes),
            ];
        }
    });

    return ['lastCounts' => $counts[array_key_last($counts)] ?? []];
}

function runClientManifestPerfScenario(array $profile, int $iterations): array
{
    $form = requirePerfMainForm($profile);
    $moduleCounts = [];

    for ($i = 0; $i < $iterations; $i++) {
        $moduleCounts[] = count(Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->entries);
    }

    return ['moduleCounts' => summarizePerfValues($moduleCounts)];
}

function runBuilderLoadPerfScenario(array $profile, int $iterations): array
{
    $coldMs = [];
    $warmMs = [];
    $pageCounts = [];
    $fieldCounts = [];

    for ($i = 0; $i < $iterations; $i++) {
        resetPerfRuntimeCaches();

        $started = microtime(true);
        $form = requirePerfMainForm($profile);
        $config = $form->getFormBuilderConfig();
        $coldMs[] = round((microtime(true) - $started) * 1000, 3);

        $started = microtime(true);
        $form->getFormBuilderConfig();
        $warmMs[] = round((microtime(true) - $started) * 1000, 3);

        $pageCounts[] = count($config['pages'] ?? []);
        $fieldCounts[] = count($form->getFieldsRecursively());
    }

    return [
        'coldMs' => summarizePerfValues($coldMs),
        'warmMs' => summarizePerfValues($warmMs),
        'pageCounts' => summarizePerfValues($pageCounts),
        'fieldCounts' => summarizePerfValues($fieldCounts),
    ];
}

function runBuilderEditSavePerfScenario(array $profile, int $iterations): array
{
    $saved = 0;

    for ($i = 0; $i < $iterations; $i++) {
        resetPerfRuntimeCaches();
        $form = requirePerfMainForm($profile);
        $form->getFormBuilderConfig();
        $form->settings->displayFormTitle = !$form->settings->displayFormTitle;

        if (!Craft::$app->getElements()->saveElement($form)) {
            throw new RuntimeException('Unable to save builder performance fixture: ' . json_encode($form->getErrors()));
        }

        $saved++;
    }

    return ['saved' => $saved];
}

function runBuilderExportPerfScenario(array $profile, int $iterations): array
{
    $pageCounts = [];
    $fieldCounts = [];
    $exportBytes = [];

    for ($i = 0; $i < $iterations; $i++) {
        resetPerfRuntimeCaches();
        $export = ImportExportHelper::generateFormExport(requirePerfMainForm($profile));
        $pageCounts[] = count($export['pages'] ?? []);
        $fieldCounts[] = countPerfSerializedFields($export['pages'] ?? []);
        $exportBytes[] = strlen(json_encode($export, JSON_UNESCAPED_SLASHES) ?: '');
    }

    return [
        'pageCounts' => summarizePerfValues($pageCounts),
        'fieldCounts' => summarizePerfValues($fieldCounts),
        'exportBytes' => summarizePerfValues($exportBytes),
    ];
}

function countPerfSerializedFields(array $pages): int
{
    $countRows = function(array $rows) use (&$countRows): int {
        $count = 0;

        foreach ($rows as $row) {
            foreach ($row['fields'] ?? [] as $field) {
                $count++;
                $count += $countRows($field['settings']['rows'] ?? []);
            }
        }

        return $count;
    };
    $count = 0;

    foreach ($pages as $page) {
        $count += $countRows($page['rows'] ?? []);
    }

    return $count;
}

function runFormRenderPerfScenario(array $profile, int $iterations): array
{
    $lengths = [];

    for ($i = 0; $i < $iterations; $i++) {
        $form = clone requirePerfMainForm($profile);
        $lengths[] = WebRequestTestHelper::withWebRequestContext(static fn(): int => strlen((string)Formie::$plugin->getRendering()->renderForm($form, [
                'includeCss' => false,
                'includeJs' => false,
                'theme' => 'formie',
            ])));
    }

    return ['htmlBytes' => summarizePerfValues($lengths)];
}

function runFormAssetsPerfScenario(array $profile, int $iterations): array
{
    $lengths = [];

    for ($i = 0; $i < $iterations; $i++) {
        $form = clone requirePerfMainForm($profile);
        $lengths[] = WebRequestTestHelper::withWebRequestContext(static fn(): int => strlen((string)Formie::$plugin->getRendering()->formAssets($form, [
                'includeCss' => true,
                'includeJs' => false,
                'theme' => 'formie',
            ])));
    }

    return ['assetHtmlBytes' => summarizePerfValues($lengths)];
}

function runSummaryFragmentPerfScenario(array $profile, int $iterations): array
{
    $form = requirePerfMainForm($profile);
    $submission = Submission::find()->formId((int)$form->id)->anyStatus()->one();
    $summary = $form->getFieldByHandle('summary');

    if (!$submission || !$summary) {
        throw new RuntimeException('Summary performance fixture is incomplete.');
    }

    $lengths = [];

    for ($i = 0; $i < $iterations; $i++) {
        $lengths[] = WebRequestTestHelper::withWebRequestContext(function () use ($submission, $summary): int {
            $token = FieldAccess::issueAccessToken($submission, (int)$summary->id);
            Craft::$app->getRequest()->setBodyParams(['accessToken' => $token]);

            return strlen((new FieldsController('perf-summary', Craft::$app))->actionGetSummaryHtml());
        }, ['method' => 'POST']);
    }

    return ['htmlBytes' => summarizePerfValues($lengths)];
}

function runClientBootstrapPerfScenario(array $profile, int $iterations): array
{
    $definitionFieldCounts = [];

    for ($i = 0; $i < $iterations; $i++) {
        $form = clone requirePerfMainForm($profile);
        $bootstrap = WebRequestTestHelper::withWebRequestContext(static fn() => Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
                'handle' => $form->handle,
                'siteId' => $form->siteId,
                'query' => [],
            ])));
        $definition = $bootstrap->definition->toArrayRecursive();
        $count = 0;

        foreach ($definition['pages'] ?? [] as $page) {
            foreach ($page['rows'] ?? [] as $row) {
                $count += count($row['fields'] ?? []);
            }
        }

        $definitionFieldCounts[] = $count;
    }

    return ['definitionFieldCounts' => summarizePerfValues($definitionFieldCounts)];
}

function runCompleteSubmitPerfScenario(array $profile, int $iterations): array
{
    $outcomes = [];
    $lastErrors = [];

    for ($i = 0; $i < $iterations; $i++) {
        $result = WebRequestTestHelper::withWebRequestContext(function () use ($profile, $i) {
            $form = requirePerfMainForm($profile);
            $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext(['handle' => $form->handle]));
            $session = $bootstrap->session->toArrayRecursive();
            $result = null;

            foreach (array_values($form->getPages()) as $pageIndex => $page) {
                $result = runClientSubmission(new SubmitRequest([
                    'handle' => $form->handle,
                    'operationId' => "perf-submit-{$profile['name']}-{$i}-{$pageIndex}",
                    'action' => 'submit',
                    'session' => $session,
                    'values' => perfSubmissionValues($profile, $i + 1000000),
                ]));

                if ($result->session) {
                    $session = $result->session->toArrayRecursive();
                }

                if (!$result->success) {
                    break;
                }
            }

            return $result;
        }, ['method' => 'POST']);
        $outcomes[] = $result->outcome;
        $lastErrors = $result->errors;
    }

    return ['outcomes' => array_count_values($outcomes), 'lastErrors' => $lastErrors];
}

function runResumeLoadPerfScenario(array $profile, int $iterations): array
{
    $versions = [];

    for ($i = 0; $i < $iterations; $i++) {
        $versions[] = WebRequestTestHelper::withWebRequestContext(function () use ($profile, $i): int {
            $form = requirePerfMainForm($profile);
            $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext(['handle' => $form->handle]));
            $saved = runClientSubmission(new SubmitRequest([
                'handle' => $form->handle,
                'operationId' => "perf-resume-save-{$profile['name']}-{$i}",
                'action' => 'save',
                'session' => $bootstrap->session->toArrayRecursive(),
                'values' => perfSubmissionValues($profile, $i + 2000000),
            ]));
            $resumed = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
                'handle' => $form->handle,
                'grantToken' => $saved->resumeToken,
            ]));

            return (int)$resumed->session->version;
        }, ['method' => 'POST']);
    }

    return ['versions' => summarizePerfValues($versions)];
}

function runReviseSubmitPerfScenario(array $profile, int $iterations): array
{
    $form = requirePerfMainForm($profile);
    $submission = Submission::find()->formId((int)$form->id)->isIncomplete(false)->one()
        ?? Submission::find()->formId((int)$form->id)->anyStatus()->one();

    if (!$submission) {
        throw new RuntimeException('Revision performance fixture has no submission.');
    }

    $outcomes = [];

    for ($i = 0; $i < $iterations; $i++) {
        $outcomes[] = WebRequestTestHelper::withWebRequestContext(function () use ($form, &$submission, $profile, $i): string {
            $grant = Formie::$plugin->getSubmissionGrants()->issue($submission, SubmissionGrants::REVISE);
            $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
                'handle' => $form->handle,
                'grantToken' => $grant->token,
                'grantPurpose' => SubmissionGrants::REVISE,
            ]));
            $result = runClientSubmission(new SubmitRequest([
                'handle' => $form->handle,
                'operationId' => "perf-revise-{$profile['name']}-{$i}",
                'action' => 'submit',
                'session' => $bootstrap->session->toArrayRecursive(),
                'values' => perfSubmissionValues($profile, $i + 3000000),
            ]));
            $submission = Submission::find()->id($submission->id)->status(null)->one();

            return $result->outcome;
        }, ['method' => 'POST']);
    }

    return ['outcomes' => array_count_values($outcomes)];
}

function perfSubmissionValues(array $profile, int $index): array
{
    $values = [
        'fullName' => "Perf workflow {$index}",
        'email' => "workflow{$index}@example.test",
        'score' => (string)$index,
    ];

    if ($profile['advancedFields']) {
        $values['person'] = [
            'firstName' => 'Performance',
            'lastName' => (string)$index,
        ];
        $values['address'] = [
            'address1' => "{$index} Profile Street",
            'city' => 'Melbourne',
            'state' => 'VIC',
            'zip' => '3000',
            'country' => 'AU',
        ];
    }

    for ($fieldIndex = 1; $fieldIndex <= $profile['fieldsPerForm']; $fieldIndex++) {
        $values["text1_{$fieldIndex}"] = "workflow-{$index}-{$fieldIndex}";
    }

    for ($nestedIndex = 1; $nestedIndex <= $profile['nestedFieldSets']; $nestedIndex++) {
        $values["group{$nestedIndex}"] = ['innerText' => "group-{$index}-{$nestedIndex}"];
        $values["lineItems{$nestedIndex}"] = [[
            'innerText' => "line-{$index}-{$nestedIndex}",
        ]];
    }

    return $values;
}

function summarizePerfQueries(array $queries): array
{
    $normalized = array_column($queries, 'normalizedSql');
    $counts = array_count_values($normalized);
    arsort($counts);

    $duplicates = array_filter($counts, static fn(int $count): bool => $count > 1);
    $slowQueries = $queries;
    usort($slowQueries, static fn(array $a, array $b): int => $b['elapsedMs'] <=> $a['elapsedMs']);

    return [
        'count' => count($queries),
        'uniqueCount' => count($counts),
        'duplicateCount' => array_sum(array_map(static fn(int $count): int => $count - 1, $duplicates)),
        'slowest' => array_map(static fn(array $query): array => [
            'elapsedMs' => $query['elapsedMs'],
            'type' => $query['type'],
            'sql' => shortenPerfSql($query['sql']),
        ], array_slice($slowQueries, 0, 5)),
        'duplicates' => array_map(static fn(string $sql, int $count): array => [
            'count' => $count,
            'sql' => shortenPerfSql($sql),
        ], array_keys(array_slice($duplicates, 0, 5, true)), array_values(array_slice($duplicates, 0, 5, true))),
    ];
}

function normalizePerfSql(string $sql): string
{
    $sql = preg_replace("/'[^']*'/", "'?'", $sql) ?? $sql;
    $sql = preg_replace('/\b\d+\b/', '?', $sql) ?? $sql;
    $sql = preg_replace('/\s+/', ' ', trim($sql)) ?? $sql;

    return $sql;
}

function shortenPerfSql(string $sql): string
{
    $sql = preg_replace('/\s+/', ' ', trim($sql)) ?? $sql;

    return strlen($sql) > 500 ? substr($sql, 0, 497) . '...' : $sql;
}

function summarizePerfValues(array $values): array
{
    if (!$values) {
        return ['min' => 0, 'max' => 0, 'last' => 0];
    }

    return [
        'min' => min($values),
        'max' => max($values),
        'last' => $values[array_key_last($values)],
    ];
}

function resetPerfRuntimeCaches(): void
{
    Formie::$plugin->getForms()->invalidateFormCaches();

    $fieldsService = Formie::$plugin->getFields();
    $reset = new ReflectionMethod($fieldsService, '_resetFieldCaches');
    $reset->setAccessible(true);
    $reset->invoke($fieldsService);

    Craft::$app->getGql()->flushCaches();
}

function withPerfGqlSchema(callable $callback): void
{
    $gql = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $gql->flushCaches();
    $gql->setActiveSchema(new GqlSchema([
        'name' => 'Formie Perf Harness',
        'scope' => [
            'formieForms.all',
            'formieForms.all:read',
            'formieSubmissions.all',
            'formieSubmissions.all:read',
            'formieSubmissions.all:create',
            'formieSubmissions.all:save',
            'formieSubmissions.all:delete',
        ],
    ]));

    try {
        $callback();
    } finally {
        $gql->setActiveSchema($activeSchema);
        $gql->flushCaches();
    }
}

function findPerfMainForm(array $profile): ?Form
{
    return Form::find()->handle(perfHandle($profile, '1'))->one();
}

function findPerfProfileForms(array $profile): array
{
    $handles = [];

    for ($formIndex = 1; $formIndex <= $profile['forms']; $formIndex++) {
        $handles[] = perfHandle($profile, (string)$formIndex);
    }

    return Form::find()->handle($handles)->all();
}

function requirePerfMainForm(array $profile): Form
{
    $form = findPerfMainForm($profile);

    if (!$form) {
        throw new RuntimeException('Perf harness seed is missing. Run `php tests/bin/perf.php seed`.');
    }

    return $form;
}

function perfHandle(array $profile, string $suffix): string
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';
    $index = max(1, (int)$suffix);
    $letter = $alphabet[($index - 1) % 26];
    $prefix = $profile['name'] === 'installation' ? 'x' : strtolower($profile['name'][0]);

    // The programmatic form factory intentionally keeps form handles tiny so
    // generated GraphQL names stay readable in tests. Keep perf handles inside
    // that contract while still reserving distinct profile namespaces.
    return $prefix . $letter;
}

function perfFormIndexFromHandle(string $handle): int
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';
    $letter = $handle[1] ?? 'a';
    $index = strpos($alphabet, $letter);

    return $index === false ? 1 : $index + 1;
}

function writePerfOutput(mixed $payload, string $format): void
{
    $flags = JSON_UNESCAPED_SLASHES;

    if ($format === 'pretty') {
        $flags |= JSON_PRETTY_PRINT;
    }

    fwrite(STDOUT, json_encode($payload, $flags) . PHP_EOL);
}

function summarizePerfResult(array $result): array
{
    return [
        'scenario' => $result['scenario'],
        'profile' => $result['profile'],
        'iterations' => $result['iterations'],
        'elapsedMs' => $result['elapsedMs'],
        'memoryPeakBytes' => $result['memoryPeakBytes'],
        'queryCount' => $result['queries']['count'],
        'duplicateQueryCount' => $result['queries']['duplicateCount'],
        'result' => $result['result'],
    ];
}
