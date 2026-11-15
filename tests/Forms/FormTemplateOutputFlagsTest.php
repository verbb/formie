<?php

use verbb\formie\Formie;
use verbb\formie\models\FormTemplate;

use craft\events\ConfigEvent;
use craft\helpers\StringHelper;

dataset('formie 3 asset flags', [
    'layout off leaves the nested theme setting on' => [['outputCssLayout' => false, 'outputCssTheme' => true], 'outputCss', false],
    'layout on without the theme' => [['outputCssLayout' => true, 'outputCssTheme' => false], 'outputCss', true],
    'both on' => [['outputCssLayout' => true, 'outputCssTheme' => true], 'outputCss', true],
    'base off leaves the nested theme setting on' => [['outputJsBase' => false, 'outputJsTheme' => true], 'outputJs', false],
    'base on without the theme' => [['outputJsBase' => true, 'outputJsTheme' => false], 'outputJs', true],
]);

it('follows the Formie 3 parent asset setting when loading a template', function (array $config, string $flag, bool $expected): void {
    expect((new FormTemplate(['name' => 'Legacy', 'handle' => 'legacy'] + $config))->{$flag})->toBe($expected);
})->with('formie 3 asset flags');

it('follows the Formie 3 parent asset setting when applying project config', function (array $config, string $flag, bool $expected): void {
    $uid = StringHelper::UUID();
    $handle = 'legacyFlags' . StringHelper::randomString(6);

    Formie::$plugin->getFormTemplates()->handleChangedTemplate(new ConfigEvent([
        'tokenMatches' => [$uid],
        'newValue' => ['name' => 'Legacy', 'handle' => $handle, 'template' => null, 'useCustomTemplates' => false, 'outputCssLocation' => 'page-header', 'outputJsLocation' => 'page-footer', 'sortOrder' => 1] + $config,
    ]));

    $value = (new craft\db\Query())->select($flag)->from('{{%formie_formtemplates}}')->where(['uid' => $uid])->scalar();

    expect((bool)$value)->toBe($expected);
})->with('formie 3 asset flags');

it('follows the Formie 3 parent asset setting in render options', function (array $config, string $flag, bool $expected): void {
    $method = new ReflectionMethod(\verbb\formie\services\Rendering::class, '_normalizeRenderOptions');

    expect($method->invoke(Formie::$plugin->getRendering(), $config)[$flag])->toBe($expected);
})->with('formie 3 asset flags');

it('leaves the template setting alone when render options only name the nested theme setting', function (): void {
    $method = new ReflectionMethod(\verbb\formie\services\Rendering::class, '_normalizeRenderOptions');

    expect($method->invoke(Formie::$plugin->getRendering(), ['outputCssTheme' => false, 'outputJsTheme' => false]))
        ->not->toHaveKey('outputCss')
        ->not->toHaveKey('outputJs');
});

it('repairs templates that upgraded with assets on that Formie 3 had turned off', function (): void {
    $db = Craft::$app->getDb();
    $table = '{{%formie_formtemplates}}';
    $legacy = ['outputCssLayout', 'outputCssTheme', 'outputJsBase', 'outputJsTheme'];
    $added = [];

    foreach ($legacy as $column) {
        if (!$db->columnExists($table, $column)) {
            $db->createCommand()->addColumn($table, $column, 'boolean DEFAULT true')->execute();
            $added[] = $column;
        }
    }

    $db->getSchema()->refresh();
    $projectConfig = Craft::$app->getProjectConfig();
    $templates = [];

    try {
        // One template still holds its Formie 3 settings, and one has since been saved in Formie 4.
        foreach (['legacy' => false, 'saved' => true] as $handle => $savedInFormie4) {
            $template = new FormTemplate(['name' => ucfirst($handle), 'handle' => $handle . StringHelper::randomString(6), 'outputCss' => true, 'outputJs' => true]);
            expect(Formie::$plugin->getFormTemplates()->saveTemplate($template))->toBeTrue();
            $templates[$handle] = $template;

            $db->createCommand()->update($table, ['outputCssLayout' => false, 'outputCssTheme' => true, 'outputJsBase' => false, 'outputJsTheme' => true, 'outputCss' => true, 'outputJs' => true], ['id' => $template->id])->execute();

            if (!$savedInFormie4) {
                $path = 'formie.formTemplates.' . $template->uid;
                $config = $projectConfig->get($path);
                unset($config['outputCss'], $config['outputJs']);
                $projectConfig->set($path, $config + ['outputCssLayout' => false, 'outputCssTheme' => true, 'outputJsBase' => false, 'outputJsTheme' => true]);
                $db->createCommand()->update($table, ['outputCss' => true, 'outputJs' => true], ['id' => $template->id])->execute();
            }
        }

        foreach ([1, 2] as $run) {
            expect((new \verbb\formie\migrations\m261115_050000_form_template_output_flags())->safeUp())->toBeTrue();
        }

        $flags = fn(FormTemplate $template) => (new craft\db\Query())->select(['outputCss', 'outputJs'])->from($table)->where(['id' => $template->id])->one();

        expect(array_map('boolval', $flags($templates['legacy'])))->toBe(['outputCss' => false, 'outputJs' => false])
            ->and(array_map('boolval', $flags($templates['saved'])))->toBe(['outputCss' => true, 'outputJs' => true]);
    } finally {
        foreach ($templates as $template) {
            Formie::$plugin->getFormTemplates()->deleteTemplateById($template->id);
        }

        foreach ($added as $column) {
            $db->createCommand()->dropColumn($table, $column)->execute();
        }

        $db->getSchema()->refresh();
    }
});
