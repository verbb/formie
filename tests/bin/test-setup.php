<?php

declare(strict_types=1);

use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\fs\Local;
use craft\fs\MissingFs;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\TagGroup;
use craft\models\UserGroup;
use craft\models\Volume;
use yii\console\ExitCode;

$pluginRoot = dirname(__DIR__, 2);
$envExample = $pluginRoot . '/.env.testing.example';
$envFile = $pluginRoot . '/.env.testing';

if (!file_exists($envExample)) {
    fwrite(STDERR, "Missing .env template at {$envExample}\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

if (!file_exists($envFile)) {
    if (!copy($envExample, $envFile)) {
        fwrite(STDERR, "Unable to create {$envFile}\n");
        exit(ExitCode::UNSPECIFIED_ERROR);
    }

    fwrite(STDOUT, "Created {$envFile} from .env.testing.example\n");
} else {
    fwrite(STDOUT, "{$envFile} already exists\n");
}

putenv('DOTENV_FILE=.env.testing');
putenv('ENVIRONMENT=testing');

require $pluginRoot . '/tests/bootstrap.php';

/** @var craft\console\Application $app */
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

$environment = getenv('ENVIRONMENT') ?: '';
if ($environment !== 'testing') {
    fwrite(STDERR, "Refusing to setup outside ENVIRONMENT=testing.\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

$runAction = static function(string $route, array $params = []) use ($app): int {
    fwrite(STDOUT, "Running `craft {$route}`...\n");
    return (int)$app->runAction($route, $params);
};

$driver = getenv('CRAFT_DB_DRIVER') ?: 'mysql';
$server = getenv('CRAFT_DB_SERVER') ?: '127.0.0.1';
$port = getenv('CRAFT_DB_PORT') ?: ($driver === 'pgsql' ? '5432' : '3306');
$database = getenv('CRAFT_DB_DATABASE') ?: 'formie_react_test';
$user = getenv('CRAFT_DB_USER') ?: 'root';
$password = getenv('CRAFT_DB_PASSWORD');
$password = $password !== false ? $password : '';
$schema = getenv('CRAFT_DB_SCHEMA') ?: 'public';
$tablePrefix = getenv('CRAFT_DB_TABLE_PREFIX') ?: '';
$unixSocket = getenv('CRAFT_DB_UNIX_SOCKET') ?: '';

if ($unixSocket !== '' && $driver === 'mysql') {
    // setup/db does not expose a unix-socket option, so skip it for socket-based local setups.
    fwrite(STDOUT, "Detected CRAFT_DB_UNIX_SOCKET; skipping `craft setup/db` and relying on .env.testing values.\n");
} else {
    $dbExit = $runAction('setup/db', [
        'interactive' => 0,
        'driver' => $driver,
        'server' => $server,
        'port' => $port,
        'database' => $database,
        'user' => $user,
        'password' => $password,
        'schema' => $schema,
        'tablePrefix' => $tablePrefix,
    ]);

    if ($dbExit !== ExitCode::OK) {
        fwrite(STDERR, "Database setup failed. Check your .env.testing CRAFT_DB_* values.\n");
        exit($dbExit);
    }
}

try {
    $db = Craft::$app->getDb();
    $db->open();
} catch (Throwable $e) {
    $socketHint = $unixSocket !== '' ? " (using unix socket: {$unixSocket})" : '';
    fwrite(STDERR, "Database connectivity preflight failed{$socketHint}: {$e->getMessage()}\n");
    fwrite(STDERR, "Check your .env.testing CRAFT_DB_* values and ensure the target database exists.\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

$tableNames = $db->getSchema()->getTableNames();

if (!$tableNames) {
    fwrite(STDOUT, "No existing tables found. Continuing with fresh install.\n");
} else {
    fwrite(STDOUT, "Dropping " . count($tableNames) . " existing tables...\n");
}

try {
    if ($db->driverName === 'mysql') {
        $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
    } elseif ($db->driverName === 'sqlite') {
        $db->createCommand('PRAGMA foreign_keys = OFF')->execute();
    }

    foreach ($tableNames as $tableName) {
        $db->createCommand()->dropTable($tableName)->execute();
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Failed dropping existing tables: {$e->getMessage()}\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
} finally {
    try {
        if ($db->driverName === 'mysql') {
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
        } elseif ($db->driverName === 'sqlite') {
            $db->createCommand('PRAGMA foreign_keys = ON')->execute();
        }
    } catch (Throwable) {
    }
}

$siteUrl = getenv('PRIMARY_SITE_URL') ?: 'http://localhost:8080';
$installExit = $runAction('install/craft', [
    'interactive' => 0,
    'username' => 'admin',
    'email' => 'admin@example.test',
    'password' => 'password123',
    'siteName' => 'Formie Test',
    'siteUrl' => $siteUrl,
    'language' => 'en-US',
]);

if ($installExit !== ExitCode::OK) {
    fwrite(STDERR, "Craft install failed. Verify DB connectivity and rerun `composer test:setup`.\n");
    exit($installExit);
}

$checkExit = $runAction('install/check');
if ($checkExit !== ExitCode::OK) {
    fwrite(STDERR, "Install finished without a healthy install state. Please inspect the console output above.\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

Craft::$app->setEdition(CmsEdition::Pro);

// Seed baseline fixtures.
$site = Craft::$app->getSites()->getPrimarySite();
$siteId = $site->id;

$ensureUserGroup = static function(string $handle, string $name): UserGroup {
    $service = Craft::$app->getUserGroups();
    $group = $service->getGroupByHandle($handle);

    if ($group) {
        fwrite(STDOUT, "User group `{$handle}` already exists.\n");
        return $group;
    }

    $group = new UserGroup(['handle' => $handle, 'name' => $name]);

    if (!$service->saveGroup($group)) {
        throw new RuntimeException("Failed creating user group `{$handle}`: " . json_encode($group->getErrors()));
    }

    fwrite(STDOUT, "Created user group `{$handle}`.\n");
    return $group;
};

$ensureTagGroup = static function(string $handle, string $name): TagGroup {
    $service = Craft::$app->getTags();
    $group = $service->getTagGroupByHandle($handle);

    if ($group) {
        fwrite(STDOUT, "Tag group `{$handle}` already exists.\n");
        return $group;
    }

    $group = new TagGroup(['handle' => $handle, 'name' => $name]);

    if (!$service->saveTagGroup($group)) {
        throw new RuntimeException("Failed creating tag group `{$handle}`: " . json_encode($group->getErrors()));
    }

    fwrite(STDOUT, "Created tag group `{$handle}`.\n");
    return $group;
};

$ensureCategoryGroup = static function(string $handle, string $name, int $siteId): CategoryGroup {
    $service = Craft::$app->getCategories();
    $group = $service->getGroupByHandle($handle);

    if ($group) {
        fwrite(STDOUT, "Category group `{$handle}` already exists.\n");
        return $group;
    }

    $siteSettings = new CategoryGroup_SiteSettings([
        'siteId' => $siteId,
        'hasUrls' => true,
        'uriFormat' => 'formie-test-categories/{slug}',
        'template' => '_formie-test/category',
    ]);

    $group = new CategoryGroup([
        'handle' => $handle,
        'name' => $name,
        'maxLevels' => null,
    ]);
    $group->setSiteSettings([$siteId => $siteSettings]);

    if (!$service->saveGroup($group)) {
        throw new RuntimeException("Failed creating category group `{$handle}`: " . json_encode($group->getErrors()));
    }

    fwrite(STDOUT, "Created category group `{$handle}`.\n");
    return $group;
};

$ensureVolume = static function(string $handle, string $name): ?Volume {
    $fsService = Craft::$app->getFs();
    $filesystem = $fsService->getFilesystemByHandle('general');

    if (!$filesystem || $filesystem instanceof MissingFs) {
        $uploadsPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'formie-test-uploads';
        if (!is_dir($uploadsPath) && !mkdir($uploadsPath, 0775, true) && !is_dir($uploadsPath)) {
            throw new RuntimeException("Failed creating uploads directory `{$uploadsPath}`.");
        }

        $filesystem = new Local([
            'name' => 'General',
            'handle' => 'general',
            'path' => $uploadsPath,
        ]);

        if (!$fsService->saveFilesystem($filesystem)) {
            throw new RuntimeException('Failed creating filesystem `general`: ' . json_encode($filesystem->getErrors()));
        }
    }

    $service = Craft::$app->getVolumes();
    $volume = $service->getVolumeByHandle($handle);

    if ($volume) {
        fwrite(STDOUT, "Volume `{$handle}` already exists.\n");
        return $volume;
    }

    $fsHandle = 'general';
    $filesystem = $fsService->getFilesystemByHandle($fsHandle);

    if (!$filesystem) {
        $allFilesystems = Craft::$app->getFs()->getAllFilesystems();
        $filesystem = $allFilesystems[0] ?? null;
        $fsHandle = $filesystem->handle ?? null;
    }

    if (!$fsHandle) {
        fwrite(STDOUT, "Skipping volume seed: no filesystem handles are available.\n");
        return null;
    }

    $volume = new Volume([
        'handle' => $handle,
        'name' => $name,
        'fsHandle' => $fsHandle,
        'sortOrder' => 1,
    ]);

    if (!$service->saveVolume($volume)) {
        throw new RuntimeException("Failed creating volume `{$handle}`: " . json_encode($volume->getErrors()));
    }

    fwrite(STDOUT, "Created volume `{$handle}` (fs: {$fsHandle}).\n");
    return $volume;
};

$ensureUser = static function(string $username, string $email): User {
    $existing = User::find()->status(null)->username($username)->one();

    if ($existing) {
        fwrite(STDOUT, "User `{$username}` already exists.\n");
        return $existing;
    }

    $user = new User([
        'username' => $username,
        'email' => $email,
        'newPassword' => 'password123',
        'firstName' => 'Formie',
        'lastName' => 'Seed',
    ]);
    $user->pending = false;

    if (!Craft::$app->getElements()->saveElement($user)) {
        throw new RuntimeException("Failed creating user `{$username}`: " . json_encode($user->getErrors()));
    }

    fwrite(STDOUT, "Created user `{$username}`.\n");
    return $user;
};

$ensureTag = static function(TagGroup $group, int $siteId): Tag {
    $title = 'Formie Seed Tag';
    $existing = Tag::find()->status(null)->groupId($group->id)->title($title)->siteId($siteId)->one();

    if ($existing) {
        fwrite(STDOUT, "Tag `{$title}` already exists.\n");
        return $existing;
    }

    $tag = new Tag([
        'groupId' => $group->id,
        'title' => $title,
        'siteId' => $siteId,
    ]);

    if (!Craft::$app->getElements()->saveElement($tag)) {
        throw new RuntimeException("Failed creating tag `{$title}`: " . json_encode($tag->getErrors()));
    }

    fwrite(STDOUT, "Created tag `{$title}`.\n");
    return $tag;
};

$ensureCategory = static function(CategoryGroup $group, int $siteId): Category {
    $title = 'Formie Seed Category';
    $existing = Category::find()->status(null)->groupId($group->id)->title($title)->siteId($siteId)->one();

    if ($existing) {
        fwrite(STDOUT, "Category `{$title}` already exists.\n");
        return $existing;
    }

    $category = new Category([
        'groupId' => $group->id,
        'title' => $title,
        'siteId' => $siteId,
    ]);

    if (!Craft::$app->getElements()->saveElement($category)) {
        throw new RuntimeException("Failed creating category `{$title}`: " . json_encode($category->getErrors()));
    }

    fwrite(STDOUT, "Created category `{$title}`.\n");
    return $category;
};

$ensureSection = static function(int $siteId): Section {
    $entriesService = Craft::$app->getEntries();
    $sectionHandle = 'formieTestEntries';
    $sectionName = 'Formie Test Entries';
    $entryTypeHandle = $sectionHandle . 'Type';

    $section = $entriesService->getSectionByHandle($sectionHandle);
    if ($section) {
        fwrite(STDOUT, "Section `{$sectionHandle}` already exists.\n");
        return $section;
    }

    $entryType = $entriesService->getEntryTypeByHandle($entryTypeHandle);
    if (!$entryType) {
        $entryType = new EntryType([
            'name' => 'Formie Test Entry',
            'handle' => $entryTypeHandle,
            'hasTitleField' => true,
        ]);

        if (!$entriesService->saveEntryType($entryType)) {
            throw new RuntimeException("Failed creating entry type `{$entryTypeHandle}`: " . json_encode($entryType->getErrors()));
        }
    }

    $section = new Section([
        'name' => $sectionName,
        'handle' => $sectionHandle,
        'type' => Section::TYPE_CHANNEL,
    ]);
    $section->setEntryTypes([$entryType]);
    $section->setSiteSettings([
        new Section_SiteSettings([
            'siteId' => $siteId,
            'enabledByDefault' => true,
            'hasUrls' => true,
            'uriFormat' => $sectionHandle . '/{slug}',
            'template' => '_formie-test/entry',
        ]),
    ]);

    if (!$entriesService->saveSection($section)) {
        throw new RuntimeException("Failed creating section `{$sectionHandle}`: " . json_encode($section->getErrors()));
    }

    $savedSection = $entriesService->getSectionByHandle($sectionHandle);
    if (!$savedSection) {
        throw new RuntimeException("Section `{$sectionHandle}` was created but could not be reloaded.");
    }

    fwrite(STDOUT, "Created section `{$sectionHandle}`.\n");
    return $savedSection;
};

$ensureEntry = static function(Section $section, int $siteId): Entry {
    $entriesService = Craft::$app->getEntries();
    $entryType = $entriesService->getEntryTypesBySectionId($section->id)[0] ?? null;

    if (!$entryType) {
        throw new RuntimeException("Section `{$section->handle}` has no entry types after seed setup.");
    }

    $slug = 'formie-seed-entry';
    $existing = Entry::find()
        ->status(null)
        ->sectionId($section->id)
        ->typeId($entryType->id)
        ->slug($slug)
        ->siteId($siteId)
        ->one();

    if ($existing) {
        fwrite(STDOUT, "Entry `{$slug}` already exists in section `{$section->handle}`.\n");
        return $existing;
    }

    $entry = new Entry([
        'sectionId' => $section->id,
        'typeId' => $entryType->id,
        'title' => 'Formie Seed Entry',
        'slug' => $slug,
        'siteId' => $siteId,
        'postDate' => new DateTime(),
        'enabled' => true,
    ]);

    if (!Craft::$app->getElements()->saveElement($entry)) {
        throw new RuntimeException("Failed creating entry `{$slug}`: " . json_encode($entry->getErrors()));
    }

    fwrite(STDOUT, "Created entry `{$slug}` in section `{$section->handle}`.\n");
    return $entry;
};

try {
    $ensureUserGroup('formieTestUsers', 'Formie Test Users');
    $tagGroup = $ensureTagGroup('formieTestTags', 'Formie Test Tags');
    $categoryGroup = $ensureCategoryGroup('formieTestCategories', 'Formie Test Categories', $siteId);
    $ensureVolume('formieTestUploads', 'Formie Test Uploads');

    $ensureUser('formie-seed-user', 'formie-seed-user@example.test');
    $ensureTag($tagGroup, $siteId);
    $ensureCategory($categoryGroup, $siteId);
    $section = $ensureSection($siteId);
    $ensureEntry($section, $siteId);
} catch (Throwable $e) {
    fwrite(STDERR, "Baseline seed failed: {$e->getMessage()}\n");
    exit(ExitCode::UNSPECIFIED_ERROR);
}

fwrite(STDOUT, "Test setup complete: install reset and baseline seed finished. You can now run `composer test`.\n");
exit(ExitCode::OK);

