<?php

declare(strict_types=1);

use craft\models\GqlSchema;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;

function regionalRegistryForm(): \verbb\formie\elements\Form
{
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Registry region', 'handle' => 'registryRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    return formie()->form(['handle' => 'regionalRegistry' . bin2hex(random_bytes(6)), 'groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId])
        ->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
}

it('registers regional forms in role and graphql permission choices', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $form = regionalRegistryForm();
    $permissions = Formie::$plugin->getPermissions();
    $forms = Formie::$plugin->getForms();
    expect(array_column($forms->getAllForms(), 'id'))->not->toContain($form->id);
    expect($permissions->getSubmissionPermissionDefinitions())->toHaveKey(Permissions::PERM_VIEW_SUBMISSIONS . ':' . $form->uid);
    expect(json_encode(Craft::$app->getGql()->getAllSchemaComponents()))->toContain('formieForms.' . $form->uid . ':read');
    expect(array_column($forms->getAllForms(), 'id'))->not->toContain($form->id);
    expect($forms->getFormById($form->id))->toBeNull();
});

it('builds graphql types for regional forms while honoring the schema scope', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $form = regionalRegistryForm();
    $other = regionalRegistryForm();
    $submission = new \verbb\formie\elements\Submission(['siteId' => $form->siteId, 'title' => 'Regional GraphQL']);
    $submission->setForm($form);
    $submission->setFieldValue('message', 'Regional GraphQL value');
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $gql = Craft::$app->getGql();
    $previous = null;
    try { $previous = $gql->getActiveSchema(); } catch (\craft\errors\GqlException) {}
    $schema = new GqlSchema(['name' => 'Regional schema', 'uid' => \craft\helpers\StringHelper::UUID(),
        'scope' => ['sites.' . Craft::$app->getSites()->getSiteById($form->siteId)->uid . ':read', 'formieForms.' . $form->uid . ':read', 'formieSubmissions.' . $form->uid . ':read']]);
    $gql->flushCaches();
    $gql->setActiveSchema($schema);
    try {
        expect(array_keys(\verbb\formie\gql\types\generators\FormGenerator::generateTypes()))->toContain(\verbb\formie\elements\Form::gqlTypeNameByContext($form));
        $result = $gql->executeQuery($schema, '{ formieForms(siteId: ' . $form->siteId . ') { id handle } }');
        expect($result['errors'] ?? [])->toBe([]);
        expect(array_map('intval', array_column($result['data']['formieForms'], 'id')))->toBe([(int)$form->id]);
        expect(array_map('intval', \verbb\formie\elements\Submission::find()->form($form->handle)->ids()))->toBe([(int)$submission->id]);
        expect(array_map('intval', \verbb\formie\elements\Submission::find()->form([$form->handle])->ids()))->toBe([(int)$submission->id]);
        $type = \verbb\formie\elements\Submission::gqlTypeNameByContext($form);
        $result = $gql->executeQuery($schema, '{ formieSubmissions(id: ' . $submission->id . ', siteId: ' . $form->siteId . ') { id ... on ' . $type . ' { message } } }');
        expect($result['errors'] ?? [])->toBe([]);
        expect($result['data']['formieSubmissions'])->toBe([['id' => (string)$submission->id, 'message' => 'Regional GraphQL value']]);
    } finally { $gql->flushCaches(); $gql->setActiveSchema($previous); }
});
