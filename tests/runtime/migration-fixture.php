<?php
require __DIR__ . '/verify.php';
$fixture = \Tests\Support\Fixtures\Freeform5FixtureFactory::createLargeFixture(true);
unset($fixture['freeformForm']);
file_put_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/migration-fixture.json', json_encode($fixture));
