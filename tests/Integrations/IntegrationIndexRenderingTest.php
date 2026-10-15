<?php

declare(strict_types=1);

function renderIntegrationIndexFallback(array $tableData, bool $supportsConnection = false): string
{
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/templates/settings/integrations/index.html');
    $start = strpos($source, '{% block settingsContent %}');
    $end = strpos($source, '{% endblock %}', $start);
    $template = substr($source, $start + strlen('{% block settingsContent %}'), $end - $start - strlen('{% block settingsContent %}'));

    return Craft::$app->getView()->renderString($template, [
        'tableData' => $tableData,
        'supportsConnection' => $supportsConnection,
        'title' => 'CRM',
    ]);
}

it('mounts the enhanced integration table on the rendered container', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/templates/settings/integrations/index.html');

    expect($source)
        ->toContain("container: '#integrations-vue-admin-table'")
        ->not->toContain("'integrations-vue-admin-table' | namespaceInputId('settings')");
});

it('keeps configured integrations visible before the enhanced table mounts', function (): void {
    $html = renderIntegrationIndexFallback([[
        'id' => 1,
        'title' => 'Primary Salesforce',
        'url' => '/admin/formie/integrations/crm/1',
        'enabled' => true,
        'connected' => true,
        'scope' => 'Project',
        'type' => [
            'isMissing' => false,
            'label' => 'Salesforce',
        ],
    ]], true);

    expect($html)
        ->toContain('<table class="data fullwidth">')
        ->toContain('href="/admin/formie/integrations/crm/1"')
        ->toContain('Primary Salesforce')
        ->toContain('Salesforce')
        ->toContain('Connected')
        ->not->toContain('No integrations exist yet.');
});

it('shows the empty state only when no integrations exist', function (): void {
    $html = renderIntegrationIndexFallback([]);

    expect($html)
        ->toContain('No integrations exist yet.')
        ->not->toContain('<table');
});
