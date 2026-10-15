<?php

declare(strict_types=1);

use craft\elements\User;
use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\integrations\crm\Salesforce;

function renderIntegrationIndexPage(array $integrations): string
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($integrations): string {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());

        return Craft::$app->getView()->renderPageTemplate('formie/settings/integrations/index', [
            'integrations' => $integrations,
            'typeName' => 'CRM',
        ], View::TEMPLATE_MODE_CP);
    }, ['requestUri' => '/admin/formie/integrations/crm']);
}

it('mounts the enhanced integration table on the rendered container', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/templates/settings/integrations/index.html');

    expect($source)
        ->toContain("container: '#integrations-vue-admin-table'")
        ->not->toContain("'integrations-vue-admin-table' | namespaceInputId('settings')");
});

it('keeps configured integrations visible before the enhanced table mounts', function (): void {
    $html = renderIntegrationIndexPage([new Salesforce([
        'id' => 1,
        'name' => 'Primary Salesforce',
        'handle' => 'primarySalesforce',
        'enabled' => true,
    ])]);

    expect($html)
        ->toContain('<table class="data fullwidth">')
        ->toContain('id="integrations-vue-admin-table"')
        ->toContain('Primary Salesforce')
        ->toContain('Salesforce')
        ->toContain('Connected')
        ->not->toContain('<div class="zilch">');
});

it('shows the empty state only when no integrations exist', function (): void {
    $html = renderIntegrationIndexPage([]);

    expect($html)
        ->toContain('id="integrations-vue-admin-table"')
        ->toContain('<div class="zilch">No integrations exist yet.</div>')
        ->not->toContain('<table class="data fullwidth">');
});
