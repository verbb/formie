<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use Tests\Support\ResetTestDatabase;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\queries\FormQuery;
use verbb\formie\gql\queries\SubmissionQuery;
use verbb\formie\gql\types\generators\FormGenerator;
use verbb\formie\gql\types\generators\SubmissionGenerator;
use verbb\formie\helpers\Table;
use verbb\formie\models\ClientModule;
use yii\console\ExitCode;

$pluginRoot = dirname(__DIR__, 2);

putenv('DOTENV_FILE=' . (getenv('DOTENV_FILE') ?: '.env.testing'));
putenv('ENVIRONMENT=' . (getenv('ENVIRONMENT') ?: 'testing'));

require $pluginRoot . '/tests/bootstrap.php';
require_once $pluginRoot . '/tests/Support/Factories/functions.php';
require_once $pluginRoot . '/tests/Support/ResetTestDatabase.php';

class FormiePerfCommand extends yii\db\Command
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

$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

if ((getenv('ENVIRONMENT') ?: '') !== 'testing') {
    fwrite(STDERR, "Refusing to run perf harness outside ENVIRONMENT=testing.\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

if (!Craft::$app->getDb()->tableExists('{{%plugins}}')) {
    fwrite(STDERR, "Testing database is not installed. Run `composer test:setup` first.\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

ensurePerfPluginReady();

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
    $options['format'] = in_array($options['format'], ['json', 'pretty', 'ndjson'], true) ? $options['format'] : 'ndjson';

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
        ],
        'medium' => [
            'name' => 'medium',
            'forms' => 8,
            'fieldsPerForm' => 15,
            'submissions' => 100,
            'nestedFieldSets' => 2,
        ],
        'large' => [
            'name' => 'large',
            'forms' => 20,
            'fieldsPerForm' => 25,
            'submissions' => 250,
            'nestedFieldSets' => 3,
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
    ];
}

function ensurePerfPluginReady(): void
{
    $projectConfig = Craft::$app->getProjectConfig();
    $pluginRows = (new craft\db\Query())
        ->select(['handle', 'schemaVersion'])
        ->from('{{%plugins}}')
        ->where(['handle' => ['formie', 'freeform']])
        ->all();

    foreach ($pluginRows as $pluginRow) {
        $handle = (string)($pluginRow['handle'] ?? '');

        if ($handle === '') {
            continue;
        }

        $key = 'plugins.' . $handle;
        $pluginConfig = $projectConfig->get($key);

        if (!$pluginConfig || empty($pluginConfig['enabled'])) {
            $projectConfig->set($key, [
                ...($pluginConfig ?: []),
                'edition' => $handle === 'freeform' ? 'express' : 'standard',
                'enabled' => true,
                'schemaVersion' => (string)($pluginConfig['schemaVersion'] ?? $pluginRow['schemaVersion'] ?? ''),
            ]);
        }
    }

    // Craft can load the plugin service before this isolated test project config
    // has plugin keys, which makes installed plugins appear absent and triggers a
    // slow reinstall. Reload after hydrating the keys so the harness measures the
    // scenario, not project-config recovery work.
    reloadPerfPluginService();

    if (!Craft::$app->plugins->isPluginEnabled('formie')) {
        Craft::$app->plugins->installPlugin('formie');
    }
}

function reloadPerfPluginService(): void
{
    $plugins = Craft::$app->plugins;
    $reflection = new ReflectionClass($plugins);

    foreach ([
        '_pluginsLoaded' => false,
        '_loadingPlugins' => false,
        '_plugins' => [],
    ] as $propertyName => $value) {
        if (!$reflection->hasProperty($propertyName)) {
            continue;
        }

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($plugins, $value);
    }
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
        ])->multiPage(2);

        $builder
            ->onPage(1)
            ->singleLineTextField('fullName')
            ->emailField('email')
            ->numberField('score');

        for ($fieldIndex = 1; $fieldIndex <= $profile['fieldsPerForm']; $fieldIndex++) {
            $builder->singleLineTextField("text{$formIndex}_{$fieldIndex}");
        }

        $nestedRows = [[
            'fields' => [[
                'type' => verbb\formie\fields\SingleLineText::class,
                'handle' => 'innerText',
                'label' => 'Inner Text',
            ]],
        ]];

        $builder->onPage(2);

        for ($nestedIndex = 1; $nestedIndex <= $profile['nestedFieldSets']; $nestedIndex++) {
            $builder
                ->groupField("group{$nestedIndex}", ['rows' => $nestedRows])
                ->repeaterField("lineItems{$nestedIndex}", ['rows' => $nestedRows]);
        }

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
        $moduleCounts[] = count(Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND));
    }

    return ['moduleCounts' => summarizePerfValues($moduleCounts)];
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

    // The programmatic form factory intentionally keeps form handles tiny so
    // generated GraphQL names stay readable in tests. Keep perf handles inside
    // that contract while still reserving distinct profile namespaces.
    return strtolower($profile['name'][0]) . $letter;
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
