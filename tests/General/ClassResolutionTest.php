<?php

use Composer\Autoload\ClassLoader;
use Tests\Support\StaticAnalysis\ClassReferences;

it('resolves every static class reference in production PHP', function() {
    $sources = [];
    $root = dirname(__DIR__, 2);
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $sources[substr($file->getPathname(), strlen($root) + 1)] = file_get_contents($file->getPathname());
        }
    }
    expect($sources)->not->toBeEmpty();

    // Loading every integration would execute optional plugin code. Resolve files without autoloading them.
    $scanner = new ClassReferences();
    $exists = static function(string $name) use ($scanner): bool {
        if (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false)) {
            return (new ReflectionClass($name))->getName() === $name;
        }
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            if ($file = $loader->findFile($name)) {
                // A case-insensitive filesystem can find a differently cased dependency file.
                // Verify the declaration too, so Linux-only autoload failures cannot pass here.
                return in_array($name, $scanner->inspect(file_get_contents($file))['declarations'], true);
            }
        }
        return false;
    };
    $optional = require __DIR__ . '/../Support/StaticAnalysis/optional-classes.php';
    foreach (array_keys($optional) as $name) {
        expect(str_starts_with($name, 'verbb\\formie\\'))->toBeFalse('Optional dependencies must not hide missing Formie classes.');
    }
    $errors = $scanner->unresolved($sources, $exists, $optional);
    expect($errors)->toBe([], implode("\n", $errors));
});

it('detects unresolved references in every class-bearing syntax position', function() {
    $code = <<<'SOURCE'
<?php
namespace Audit;
#[MissingAttribute]
class Sample extends MissingBase implements MissingInterface {
    use MissingTrait;
    public MissingProperty $property;
    public function run(MissingParameter $value): MissingReturn {
        try {
            new MissingConstructor();
            MissingCall::run();
            MissingPropertyOwner::$value;
            $type = MissingLiteral::class;
            $value instanceof MissingCheck;
        } catch (MissingException $exception) {}
    }
}
SOURCE;
    $errors = (new ClassReferences())->unresolved(['fixture.php' => $code], fn() => false);
    foreach (['Attribute', 'Base', 'Interface', 'Trait', 'Property', 'Parameter', 'Return', 'Constructor', 'Call', 'PropertyOwner', 'Literal', 'Check', 'Exception'] as $suffix) {
        expect(implode("\n", $errors))->toContain('Audit\\Missing' . $suffix);
    }
    expect($errors)->toHaveCount(13);
});

it('resolves aliases and namespace imports without treating functions or constants as classes', function() {
    $code = <<<'SOURCE'
<?php
namespace Library { interface Contract {} trait Shared {} class Item {} }
namespace Audit {
    use Library\{Item as Renamed, Contract};
    use Library as Group;
    use function Vendor\helper;
    use const Vendor\FLAG;
    class Sample implements Contract {
        use \Library\Shared;
        public function run(Renamed|\Library\Item|null $item): Group\Item {
            helper(FLAG);
            new Renamed();
            new Group\Item();
            self::run($item);
            return $item;
        }
    }
    class_alias(Sample::class, Legacy::class);
    new Legacy();
}
SOURCE;
    expect((new ClassReferences())->unresolved(['fixture.php' => $code], fn() => false))->toBe([]);
});

it('rejects missing imports and incorrect aliases even inside an optional integration', function() {
    $optional = ['Vendor\\Item' => 'vendor/plugin'];
    $code = '<?php namespace Audit; use Vendor\Item as Imported; new Imported(); new Item(); new \Vendor\Typo();';
    $errors = (new ClassReferences())->unresolved(['fixture.php' => $code], fn() => false, $optional);
    expect($errors)->toHaveCount(2)
        ->and(implode("\n", $errors))->toContain('Audit\\Item', 'Vendor\\Typo');
});

it('rejects class-name casing that would fail on a case-sensitive autoloader', function() {
    $code = '<?php namespace Library; class Item {} new item();';
    expect((new ClassReferences())->unresolved(['fixture.php' => $code], fn() => false))
        ->toBe(['fixture.php:1: Library\\item']);
});
