<?php

declare(strict_types=1);

namespace Tests\General;

use Craft;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private string $templateModeBeforeTest;
    private array $serverBeforeTest;
    private array $generalConfigBeforeTest = [];
    private array $pluginSettingsBeforeTest = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureCraftBootstrapped();
        $this->templateModeBeforeTest = Craft::$app->getView()->getTemplateMode();
        $this->serverBeforeTest = $_SERVER;
        $this->generalConfigBeforeTest = get_object_vars(Craft::$app->getConfig()->getGeneral());
        $this->pluginSettingsBeforeTest = \verbb\formie\Formie::$plugin->getSettings()->getAttributes();
    }

    protected function tearDown(): void
    {
        try {
            $_SERVER = $this->serverBeforeTest;
            Craft::$app->getView()->setTemplateMode($this->templateModeBeforeTest);
            Craft::configure(Craft::$app->getConfig()->getGeneral(), $this->generalConfigBeforeTest);
            \verbb\formie\Formie::$plugin->getSettings()->setAttributes($this->pluginSettingsBeforeTest, false);
            \verbb\formie\Formie::$plugin->getIntegrations()->resetCaptchaCaches();
            \verbb\formie\Formie::$plugin->getRenderCache()->reset();
        } finally {
            parent::tearDown();
        }
    }

    protected function ensureCraftBootstrapped(): void
    {
        if (!class_exists(Craft::class) || !Craft::$app) {
            throw new RuntimeException('Craft application must be bootstrapped before running integration tests.');
        }
    }
}
