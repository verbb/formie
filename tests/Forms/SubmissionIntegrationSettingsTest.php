<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\base\Integration;
use verbb\formie\models\IntegrationResponse;

it('isolates per-form integration settings in bulk commands', function (): void {
    $originalIntegrations = Formie::$plugin->getIntegrations();
    $originalTriggers = Formie::$plugin->getIntegrationTriggers();
    try {
    $factory = Formie::$plugin->getFactories();
    $forms = [];
    $submissions = [];
    foreach (['firstOverride' => ['useDoubleOptIn' => true], 'secondDefault' => []] as $label => $overrides) {
        $form = $factory->form(['title' => 'Integration command ' . $label])->singleLineTextField('message')->integrations(['privateFixture' => array_merge(['enabled' => true, 'listId' => 'fixture-list'], $overrides)])->create();
        $forms[$label] = Form::find()->id($form->id)->status(null)->one();
        $submissions[$label] = $factory->submission($forms[$label])->with(['message' => $label])->save();
        expect($forms[$label]->settings->integrations)->toHaveKey('privateFixture');
    }
    // The command uses newest-first ordering, independently of the supplied ID order.
    \craft\helpers\Db::update('{{%elements}}', ['dateCreated' => '2026-01-02 00:00:00'], ['id' => $submissions['firstOverride']->id]);
    \craft\helpers\Db::update('{{%elements}}', ['dateCreated' => '2026-01-01 00:00:00'], ['id' => $submissions['secondDefault']->id]);
    // Replace registry enumeration only. Inherited lookup caching, form cloning,
    // the actual Mailchimp model, and CLI attribute application remain unchanged.
    $registry = new class extends \verbb\formie\services\Integrations {
        public Integration $fixture;
        public function getAllIntegrations(): array { return [$this->fixture]; }
        public function getAllCaptchas(): array { return []; }
    };
    $registry->fixture = new \verbb\formie\integrations\emailmarketing\Mailchimp(['name' => 'Private registry fixture', 'handle' => 'privateFixture', 'enabled' => true]);
    $dispatch = new class extends \verbb\formie\services\IntegrationTriggers {
        public array $seen = [];
        public function dispatchManualIntegration(Integration $integration, Submission $submission): bool|IntegrationResponse {
            $this->seen[] = ['submissionId' => $submission->id, 'form' => $submission->getForm()->title, 'useDoubleOptIn' => $integration->useDoubleOptIn, 'listId' => $integration->listId];
            return true;
        }
    };
    Formie::$plugin->set('integrations', $registry);
    Formie::$plugin->set('integrationTriggers', $dispatch);
    foreach ($forms as $label => $form) {
        $prepared = $registry->getAllEnabledIntegrationsForForm($form)[0];
        expect($prepared->useDoubleOptIn)->toBe($label === 'firstOverride');
    }
    expect($registry->fixture->useDoubleOptIn)->toBeFalse();
    $cli = new class('submissions', Formie::$plugin) extends \verbb\formie\console\controllers\SubmissionsController {
        public string $out = '';
        public function stdout($string) { $this->out .= $string; return strlen($string); }
    };
    $cli->submissionId = implode(',', array_map(fn($submission) => $submission->id, $submissions));
    $cli->integration = 'privateFixture';
    expect($cli->actionRunIntegration())->toBe(0);
    expect(array_column($dispatch->seen, 'submissionId'))->toBe([$submissions['firstOverride']->id, $submissions['secondDefault']->id]);
    $byId = array_column($dispatch->seen, 'useDoubleOptIn', 'submissionId');
    expect($byId[$submissions['firstOverride']->id])->toBeTrue();
    expect($byId[$submissions['secondDefault']->id])->toBeFalse();
    expect($registry->fixture->useDoubleOptIn)->toBeFalse();
    expect($registry->fixture->listId)->toBeNull();
    } finally {
        Formie::$plugin->set('integrations', $originalIntegrations);
        Formie::$plugin->set('integrationTriggers', $originalTriggers);
    }
});
