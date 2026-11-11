<?php

declare(strict_types=1);

use craft\elements\User;
use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\base\Crm;

/**
 * Renders each provider's settings in the control panel and returns the integration guide links.
 *
 * @return array<string, string[]>
 */
function renderIntegrationGuideLinks(array $integrationClasses): array
{
    return WebRequestTestHelper::withWebRequestContext(function($request) use ($integrationClasses): array {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);

        $links = [];

        foreach ($integrationClasses as $integrationClass) {
            $html = (string)(new $integrationClass())->getSettingsHtml();

            if (!str_contains($html, 'integration guide')) {
                continue;
            }

            preg_match_all('/<a href="([^"]+)"[^>]*>[^<]*integration guide<\/a>/', $html, $matches);
            $links[$integrationClass] = $matches[1];
        }

        return $links;
    }, ['requestUri' => '/admin/formie/integrations/crm/new']);
}

it('links every provider settings page to its own documentation page', function(): void {
    $integrationClasses = array_merge(...array_values(Formie::$plugin->getIntegrations()->getAllIntegrationTypes()));
    $links = renderIntegrationGuideLinks(array_unique($integrationClasses));
    $docsPath = dirname(__DIR__, 2) . '/docs/integrations';

    // The CRM, email marketing, payments and other connected providers all render the shared guide line.
    expect(count($links))->toBeGreaterThan(80);

    foreach ($links as $integrationClass => $urls) {
        expect($urls)->toHaveCount(1, "{$integrationClass} should render one guide link");

        $matched = preg_match('#^https://verbb\.io/craft-plugins/formie/docs/v4/integrations/([a-z-]+)/([a-z0-9-]+)$#', $urls[0], $parts);

        expect($matched)->toBe(1, "{$integrationClass} links to {$urls[0]}");
        expect(is_file("{$docsPath}/{$parts[1]}/{$parts[2]}.md"))->toBeTrue("{$integrationClass} links to missing page {$urls[0]}");
    }
});

it('links Pardot to the Pardot guide', function(): void {
    $links = renderIntegrationGuideLinks([\verbb\formie\integrations\crm\Pardot::class]);

    expect($links[\verbb\formie\integrations\crm\Pardot::class])
        ->toBe(['https://verbb.io/craft-plugins/formie/docs/v4/integrations/crm/pardot']);
});

it('omits the guide link for providers outside Formie', function(): void {
    $integration = new class extends Crm {
        public static function displayName(): string
        {
            return 'Example CRM';
        }

        public function getDescription(): string
        {
            return '';
        }

        public function fetchFormSettings(): \verbb\formie\models\IntegrationFormSettings
        {
            return new \verbb\formie\models\IntegrationFormSettings();
        }

        public function sendPayload(\verbb\formie\elements\Submission $submission): bool
        {
            return true;
        }
    };

    expect($integration->getDocumentationUrl())->toBeNull();
});
