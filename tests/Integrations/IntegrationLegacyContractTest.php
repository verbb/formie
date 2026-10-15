<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

class LegacyBoolPayloadContract extends Freshdesk
{
    public int $oldCalls = 0;
    public int $nativeCalls = 0;
    public IntegrationResult $nativeResult;
    public bool $branch = false;
    public bool $fail = false;
    public function sendPayload(Submission $submission): bool
    {
        ++$this->oldCalls;
        $result = parent::sendPayload($submission);
        if ($this->fail) throw new RuntimeException('legacy failure');
        return $this->branch ? ($result ? true : false) : $result;
    }
    protected function executePayload(Submission $submission): IntegrationResult
    {
        ++$this->nativeCalls;
        return $this->nativeResult;
    }
}

it('preserves bool parent returns and rich canonical results across legacy providers', function(string $status, bool $branch) {
    $provider = new LegacyBoolPayloadContract();
    $provider->branch = $branch;
    $provider->nativeResult = (match ($status) {
        'succeeded' => IntegrationResult::succeeded('provider-id'),
        'skipped' => IntegrationResult::skipped('cancelled'),
        'failed' => IntegrationResult::failed('retryable', true),
        'unknown' => IntegrationResult::unknown('uncertain'),
        'rejected' => IntegrationResult::rejected('denied'),
    })->withOutputs(['elementId' => 42]);
    $submission = new Submission();
    $context = new IntegrationRunContext($submission, new IntegrationExecutionContext(1, 1, 'legacy', 'legacy-run'), 'attempt');
    expect($provider->execute($context)->toStorage())->toBe($provider->nativeResult->toStorage())
        ->and($provider->oldCalls)->toBe(1)->and($provider->nativeCalls)->toBe(1)
        ->and($provider->getDeliveryAttemptUid())->toBe('attempt');
    expect($provider->sendPayload($submission))->toBe(in_array($status, ['succeeded', 'skipped'], true))
        ->and($provider->oldCalls)->toBe(2)->and($provider->nativeCalls)->toBe(2);
})->with(['succeeded', 'skipped', 'failed', 'unknown', 'rejected'])->with([false, true]);

class LegacyPostParentDeliveryContract extends Freshdesk
{
    public IntegrationResult $nativeResult;
    public string $effect = '';
    public bool $legacyChannel = false;
    public bool $wrapResponse = false;
    public bool $nativeWriteAccepted = false;
    public bool $nativeHandledError = false;

    public function sendPayload(Submission $submission): bool|IntegrationResponse
    {
        $result = parent::sendPayload($submission);
        if ($this->effect !== '') {
            if ($this->legacyChannel) {
                $this->context[$this->effect === 'uncertain' ? 'deliveryUncertain' : 'deliveryWriteAccepted'] = true;
            } else {
                $this->_deliveryState->{$this->effect} = true;
            }
        }
        $this->_deliveryState->outputs['extensionId'] = 43;
        return $this->wrapResponse ? new IntegrationResponse($result, ['custom' => 'message']) : $result;
    }

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->_deliveryState->writeAccepted = $this->nativeWriteAccepted;
        if ($this->nativeHandledError) {
            $this->_deliveryState->error = $this->nativeResult;
            $this->_deliveryState->handledError = true;
        }
        return $this->nativeResult;
    }
}

it('requires reconciliation for uncertainty recorded after a legacy parent call', function(string $status, bool $legacyChannel, bool $wrapResponse) {
    $provider = new LegacyPostParentDeliveryContract();
    $provider->nativeResult = new IntegrationResult(IntegrationStatus::from($status), 'parent message', 'parent_code', true, 'provider-id', ['detail' => 'parent'], ['elementId' => 42], false);
    $provider->nativeHandledError = $status === 'failed';
    $provider->effect = 'uncertain';
    $provider->legacyChannel = $legacyChannel;
    $provider->wrapResponse = $wrapResponse;
    $context = new IntegrationRunContext(new Submission(), new IntegrationExecutionContext(1, 1, 'legacy', 'legacy-run'), 'attempt');
    $result = $provider->execute($context);
    expect($result->requiresReconciliation())->toBeTrue()
        ->and($result->retryable)->toBeFalse()
        ->and($result->shouldFailQueue())->toBeTrue()
        ->and($result->outputs)->toBe(['extensionId' => 43, 'elementId' => 42]);
    if ($status === 'unknown') {
        expect($result->toStorage())->toBe($provider->nativeResult->withOutputs(['extensionId' => 43])->toStorage());
    }
    if ($wrapResponse) {
        expect($provider->getLegacyIntegrationResponse()->message)->toBe(['custom' => 'message']);
    }
})->with(['succeeded', 'skipped', 'failed', 'unknown'])->with([false, true])->with([false, true]);

it('requires reconciliation for accepted writes recorded after a failed legacy parent call', function(bool $legacyChannel, bool $nativeWriteAccepted, bool $wrapResponse) {
    $provider = new LegacyPostParentDeliveryContract();
    $provider->nativeResult = IntegrationResult::failed('retryable_parent', true)->withOutputs(['elementId' => 42]);
    $provider->nativeHandledError = true;
    $provider->nativeWriteAccepted = $nativeWriteAccepted;
    $provider->effect = 'writeAccepted';
    $provider->legacyChannel = $legacyChannel;
    $provider->wrapResponse = $wrapResponse;
    $context = new IntegrationRunContext(new Submission(), new IntegrationExecutionContext(1, 1, 'legacy', 'legacy-run'), 'attempt');
    $result = $provider->execute($context);
    expect($result->requiresReconciliation())->toBeTrue()
        ->and($result->retryable)->toBeFalse()
        ->and($result->outputs)->toBe(['extensionId' => 43, 'elementId' => 42]);
})->with([
    'typed state' => [false, false],
    'legacy channel' => [true, false],
    'legacy channel after native write' => [true, true],
])->with([false, true]);

it('preserves safe parent result details even when the parent has accepted a write', function(string $status) {
    $provider = new LegacyPostParentDeliveryContract();
    $provider->nativeResult = new IntegrationResult(IntegrationStatus::from($status), 'parent message', 'parent_code', true, 'provider-id', ['detail' => 'parent'], ['elementId' => 42], false);
    $provider->nativeWriteAccepted = true;
    $context = new IntegrationRunContext(new Submission(), new IntegrationExecutionContext(1, 1, 'legacy', 'legacy-run'), 'attempt');
    expect($provider->execute($context)->toStorage())->toBe($provider->nativeResult->withOutputs(['extensionId' => 43])->toStorage());
})->with(['succeeded', 'skipped', 'failed', 'unknown', 'rejected']);

it('does not replay legacy writes after the parent result becomes uncertain', function(string $effect) {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $provider = new class(['name' => 'Parent recovery fixture', 'handle' => 'parentRecoveryFixture']) extends LegacyPostParentDeliveryContract {
        public static int $calls = 0;
        protected function executePayload(Submission $submission): IntegrationResult
        {
            ++self::$calls;
            return parent::executePayload($submission);
        }
    };
    $provider::$calls = 0;
    $provider->nativeResult = IntegrationResult::failed('retryable_parent', true)->withOutputs(['elementId' => 42]);
    $provider->effect = $effect;
    $provider->legacyChannel = true;
    $handler = function(\verbb\formie\events\ModifyFormIntegrationsEvent $event) use ($form, $provider): void {
        if ($event->form->id === $form->id) $event->integrations[] = $provider;
    };
    \yii\base\Event::on(\verbb\formie\services\Integrations::class, \verbb\formie\services\Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    try {
        $run = fn() => \verbb\formie\Formie::$plugin->getIntegrationRunner()->runSteps($submission, [$provider->handle], ['triggerEvent' => 'submit'], null, 'parent-recovery-job');
        expect($run()->results()[0]['result']->requiresReconciliation())->toBeTrue();
        expect($run()->results()[0]['result']->requiresReconciliation())->toBeTrue();
        expect($provider::$calls)->toBe(1);
    } finally {
        \yii\base\Event::off(\verbb\formie\services\Integrations::class, \verbb\formie\services\Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
})->with(['uncertain', 'writeAccepted']);

it('restores payload guards after a legacy parent call throws', function() {
    $provider = new LegacyBoolPayloadContract();
    $provider->nativeResult = IntegrationResult::failed('first');
    $provider->fail = true;
    $context = new IntegrationRunContext(new Submission(), new IntegrationExecutionContext(1, 1, 'legacy', 'legacy-run'), 'attempt');
    expect(fn() => $provider->execute($context))->toThrow(RuntimeException::class);
    $provider->fail = false;
    $provider->nativeResult = IntegrationResult::succeeded();
    expect($provider->execute($context)->isSuccessful())->toBeTrue()
        ->and($provider->oldCalls)->toBe(2)->and($provider->nativeCalls)->toBe(2);
});

it('preserves legacy response messages when an override wraps its parent boolean', function() {
    $provider = new class extends Freshdesk {
        public function sendPayload(Submission $submission): bool|IntegrationResponse { return new IntegrationResponse(parent::sendPayload($submission), ['custom' => 'message']); }
        protected function executePayload(Submission $submission): IntegrationResult { return IntegrationResult::failed('provider_rejected', true); }
    };
    $context = new IntegrationRunContext(new Submission(), new IntegrationExecutionContext(1, 1, 'legacy', 'legacy-run'), 'attempt');
    expect($provider->execute($context)->code)->toBe('provider_rejected')
        ->and($provider->getLegacyIntegrationResponse()->success)->toBeFalse()
        ->and($provider->getLegacyIntegrationResponse()->message)->toBe(['custom' => 'message']);
});

// These declarations intentionally use the exact Formie 3 signatures. PHP rejects the file if any contract narrows.
class LegacySignatureContract extends Crm
{
    public array $calls = [];
    public function getFormSettingValue(string $key) { $this->calls[] = 'value'; return parent::getFormSettingValue($key); }
    public function populateContext(): void { $this->calls[] = 'context'; parent::populateContext(); }
    public function enforceOptInField(Submission $submission): bool { $this->calls[] = 'optIn'; return parent::enforceOptInField($submission); }
    public function getMappedFieldValue(string $mappedFieldValue, Submission $submission, IntegrationField $integrationField): mixed { $this->calls[] = 'mapping'; return parent::getMappedFieldValue($mappedFieldValue, $submission, $integrationField); }
}
class LegacyNativeContextContract extends \verbb\formie\integrations\crm\HubSpot
{
    public int $calls = 0;
    public function populateContext(): void { ++$this->calls; parent::populateContext(); }
}
class LegacyNativeMappingContract extends \verbb\formie\integrations\crm\Salesforce
{
    public int $calls = 0;
    public function getMappedFieldValue(string $mappedFieldValue, Submission $submission, IntegrationField $integrationField): mixed { ++$this->calls; return parent::getMappedFieldValue($mappedFieldValue, $submission, $integrationField); }
}

it('calls unchanged hooks with parent calls and named arguments through current consumers', function() {
    $provider = new LegacySignatureContract();
    $submission = new Submission();
    $submission->setForm(formie()->form()->create());
    $submission->metadata = ['v' => 1, 'request' => ['referrer' => 'https://example.test/stored'], 'custom' => []];
    WebRequestTestHelper::withWebRequestContext(function() use ($provider, $submission) {
        $provider->populateSubmissionContext($submission);
        expect($provider->context['referrer'])->toBe('https://example.test/stored');
        $native = new LegacyNativeContextContract();
        $native->populateSubmissionContext($submission);
        expect($native->calls)->toBe(1)->and($native->context['referrer'])->toBe('https://example.test/stored');
    });
    expect($provider->enforceOptInField($submission))->toBeTrue()
        ->and($provider->getConfigValue('missing'))->toBe([])
        ->and($provider->getMappedFieldValue(mappedFieldValue: 'hello', submission: $submission, integrationField: new IntegrationField()))->toBe('hello')
        ->and($provider->calls)->toBe(['context', 'optIn', 'value', 'mapping']);
    $native = new LegacyNativeMappingContract();
    expect($native->resolveMappedFieldValue(['kind' => 'literal', 'value' => ['one', 'two']], $submission, new IntegrationField(['type' => IntegrationField::TYPE_ARRAY])))->toBe('one;two')
        ->and($native->calls)->toBe(1);
});

it('retains slot semantics and calls legacy mapping overrides once', function(string $kind, string $expected) {
    $provider = new LegacySignatureContract();
    $submission = new Submission();
    $submission->setForm(formie()->form()->create());
    $submission->metadata = ['v' => 1, 'request' => [], 'custom' => ['label' => 'resolved']];
    expect($provider->resolveMappedFieldValue(['kind' => $kind, 'value' => '{metadata:custom.label}'], $submission, new IntegrationField()))->toBe($expected)
        ->and($provider->calls)->toBe(['mapping']);
})->with([['literal', '{metadata:custom.label}'], ['reference', 'resolved'], ['text', 'resolved']]);

class LegacyMetadataReadContract extends Crm
{
    public int $reads = 0;
    public int $values = 0;
    public int $fetches = 0;
    public bool $cancel = false;
    public bool $fail = false;
    public static function supportsConnection(): bool { return false; }
    public function fetchConfig(): IntegrationConfig { ++$this->fetches; return new IntegrationConfig(['native' => 'yes']); }
    public function getFormSettings(bool $useCache = true): bool|IntegrationFormSettings
    {
        ++$this->reads;
        if ($this->cancel) return false;
        $settings = parent::getFormSettings($useCache);
        if ($this->fail) throw new RuntimeException('read failed');
        if ($settings !== false) $settings->setSettingsByKey('legacy', 'present');
        return $settings;
    }
    public function getFormSettingValue(string $key) { ++$this->values; return $key === 'custom' ? 'custom-value' : parent::getFormSettingValue($key); }
}

it('adapts metadata read overrides with parent calls and one refresh lifecycle', function(bool $legacy) {
    $provider = new LegacyMetadataReadContract();
    $events = 0;
    $provider->on(Integration::EVENT_AFTER_FETCH_CONFIG, function() use (&$events) { ++$events; });
    $result = $legacy ? $provider->getFormSettings(false)->toConfig() : $provider->refreshConfig();
    expect($result->all())->toBe(['native' => 'yes', 'legacy' => 'present'])
        ->and($provider->reads)->toBe(1)->and($provider->fetches)->toBe(1)->and($events)->toBe(1)
        ->and($provider->getConfig()->isStale())->toBeFalse()
        ->and($provider->getConfig()->invalidationKey)->toBe($provider->getIntegrationConfigKey())
        ->and($provider->getConfigValue('custom'))->toBe('custom-value')->and($provider->values)->toBe(1);
})->with([false, true]);

it('preserves metadata cancellation and restores read guards after errors', function() {
    $provider = new LegacyMetadataReadContract();
    $provider->fail = true;
    expect(fn() => $provider->getConfig())->toThrow(RuntimeException::class);
    $provider->fail = false;
    expect($provider->getConfig()->get('legacy'))->toBe('present')->and($provider->reads)->toBe(2);
    $provider->cancel = true;
    expect($provider->refreshConfig()->all())->toBe([])->and($provider->getFormSettings(false))->toBeFalse()->and($provider->fetches)->toBe(0);
});

it('gives derived canonical metadata reads precedence over inherited legacy reads', function() {
    $provider = new class extends LegacyMetadataReadContract {
        public int $newCalls = 0;
        public function getConfig(bool $useCache = true): IntegrationConfig { ++$this->newCalls; return new IntegrationConfig(parent::getConfig($useCache)->all() + ['canonical' => true]); }
        public function getConfigValue(string $key): mixed { return $key === 'custom' ? 'canonical-value' : parent::getConfigValue($key); }
    };
    expect($provider->getConfig()->get('canonical'))->toBeTrue()->and($provider->reads)->toBe(0)->and($provider->newCalls)->toBe(1)
        ->and($provider->getConfigValue('custom'))->toBe('canonical-value')->and($provider->values)->toBe(0);
});

class OldCaptchaContract extends \verbb\formie\base\Captcha
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getRefreshJsVariables(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { ++$this->oldCalls; return parent::getRefreshJsVariables($form, $page); }

    public function invokeCanonical(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { return $this->getRefreshData($form, $page); }
public function invokeLegacy(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { return $this->getRefreshJsVariables($form, $page); }
}
class NewCaptchaContract extends OldCaptchaContract
{
    public function getRefreshData(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::getRefreshData($form, $page); }
}
class SameCaptchaContract extends \verbb\formie\base\Captcha
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getRefreshJsVariables(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { ++$this->oldCalls; return parent::getRefreshJsVariables($form, $page); }
    public function getRefreshData(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::getRefreshData($form, $page); }

    public function invokeCanonical(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { return $this->getRefreshData($form, $page); }
public function invokeLegacy(\verbb\formie\elements\Form $form, ?\verbb\formie\models\FieldLayoutPage $page = null): array { return $this->getRefreshJsVariables($form, $page); }
}
class OldMappingContract extends \verbb\formie\base\Crm
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getIntegrationFieldMappingField(array $config = []): array { ++$this->oldCalls; return parent::getIntegrationFieldMappingField($config); }

    public function invokeCanonical(array $config = []): array { return $this->createFieldMappingField($config); }
public function invokeLegacy(array $config = []): array { return $this->getIntegrationFieldMappingField($config); }
}
class NewMappingContract extends OldMappingContract
{
    public function createFieldMappingField(array $config = []): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::createFieldMappingField($config); }
}
class SameMappingContract extends \verbb\formie\base\Crm
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getIntegrationFieldMappingField(array $config = []): array { ++$this->oldCalls; return parent::getIntegrationFieldMappingField($config); }
    public function createFieldMappingField(array $config = []): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::createFieldMappingField($config); }

    public function invokeCanonical(array $config = []): array { return $this->createFieldMappingField($config); }
public function invokeLegacy(array $config = []): array { return $this->getIntegrationFieldMappingField($config); }
}
class OldDiagnosticContract extends \verbb\formie\base\Crm
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType): void { ++$this->oldCalls; parent::populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType); }

    public function invokeCanonical(Submission $submission, string $endpoint, mixed $payload, string $method, string $contentType): void { $this->recordDeliveryRequest($submission, $endpoint, $payload, $method, $contentType); }
public function invokeLegacy(Submission $submission, string $endpoint, mixed $payload, string $method, string $contentType): void { $this->populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType); }
}
class NewDiagnosticContract extends OldDiagnosticContract
{
    public function recordDeliveryRequest(Submission $submission, string $endpoint, mixed $payload, string $method, string $contentType): void { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); parent::recordDeliveryRequest($submission, $endpoint, $payload, $method, $contentType); }
}
class SameDiagnosticContract extends \verbb\formie\base\Crm
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType): void { ++$this->oldCalls; parent::populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType); }
    public function recordDeliveryRequest(Submission $submission, string $endpoint, mixed $payload, string $method, string $contentType): void { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); parent::recordDeliveryRequest($submission, $endpoint, $payload, $method, $contentType); }

    public function invokeCanonical(Submission $submission, string $endpoint, mixed $payload, string $method, string $contentType): void { $this->recordDeliveryRequest($submission, $endpoint, $payload, $method, $contentType); }
public function invokeLegacy(Submission $submission, string $endpoint, mixed $payload, string $method, string $contentType): void { $this->populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType); }
}
class OldPaymentContract extends \verbb\formie\base\Payment
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getTransaction(\verbb\formie\models\Payment $payment): void { ++$this->oldCalls; parent::getTransaction($payment); }
    protected function refreshPaymentState(\verbb\formie\models\Payment $payment): void {}
    public function invokeCanonical(\verbb\formie\models\Payment $payment): void { $this->refreshPayment($payment); }
public function invokeLegacy(\verbb\formie\models\Payment $payment): void { $this->getTransaction($payment); }
}
class NewPaymentContract extends OldPaymentContract
{
    public function refreshPayment(\verbb\formie\models\Payment $payment): void { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); parent::refreshPayment($payment); }
}
class SamePaymentContract extends \verbb\formie\base\Payment
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getTransaction(\verbb\formie\models\Payment $payment): void { ++$this->oldCalls; parent::getTransaction($payment); }
    public function refreshPayment(\verbb\formie\models\Payment $payment): void { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); parent::refreshPayment($payment); }
    protected function refreshPaymentState(\verbb\formie\models\Payment $payment): void {}
    public function invokeCanonical(\verbb\formie\models\Payment $payment): void { $this->refreshPayment($payment); }
public function invokeLegacy(\verbb\formie\models\Payment $payment): void { $this->getTransaction($payment); }
}
class OldGqlContract extends \verbb\formie\base\Payment
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getGraphqlPaymentInputFieldKeys(\verbb\formie\base\FieldInterface $field): array { ++$this->oldCalls; return parent::getGraphqlPaymentInputFieldKeys($field); }

    public function invokeCanonical(\verbb\formie\base\FieldInterface $field): array { return $this->getGqlPaymentInputFieldKeys($field); }
public function invokeLegacy(\verbb\formie\base\FieldInterface $field): array { return $this->getGraphqlPaymentInputFieldKeys($field); }
}
class NewGqlContract extends OldGqlContract
{
    public function getGqlPaymentInputFieldKeys(\verbb\formie\base\FieldInterface $field): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::getGqlPaymentInputFieldKeys($field); }
}
class SameGqlContract extends \verbb\formie\base\Payment
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function getGraphqlPaymentInputFieldKeys(\verbb\formie\base\FieldInterface $field): array { ++$this->oldCalls; return parent::getGraphqlPaymentInputFieldKeys($field); }
    public function getGqlPaymentInputFieldKeys(\verbb\formie\base\FieldInterface $field): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::getGqlPaymentInputFieldKeys($field); }

    public function invokeCanonical(\verbb\formie\base\FieldInterface $field): array { return $this->getGqlPaymentInputFieldKeys($field); }
public function invokeLegacy(\verbb\formie\base\FieldInterface $field): array { return $this->getGraphqlPaymentInputFieldKeys($field); }
}
class OldOptionalGqlContract extends \verbb\formie\integrations\payments\Stripe
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    protected function getOptionalGraphqlPaymentInputFieldKeys(): array { ++$this->oldCalls; return parent::getOptionalGraphqlPaymentInputFieldKeys(); }

    public function invokeCanonical(): array { return $this->getOptionalGqlPaymentInputFieldKeys(); }
public function invokeLegacy(): array { return $this->getOptionalGraphqlPaymentInputFieldKeys(); }
}
class NewOptionalGqlContract extends OldOptionalGqlContract
{
    protected function getOptionalGqlPaymentInputFieldKeys(): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::getOptionalGqlPaymentInputFieldKeys(); }
}
class SameOptionalGqlContract extends \verbb\formie\integrations\payments\Stripe
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    protected function getOptionalGraphqlPaymentInputFieldKeys(): array { ++$this->oldCalls; return parent::getOptionalGraphqlPaymentInputFieldKeys(); }
    protected function getOptionalGqlPaymentInputFieldKeys(): array { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::getOptionalGqlPaymentInputFieldKeys(); }

    public function invokeCanonical(): array { return $this->getOptionalGqlPaymentInputFieldKeys(); }
public function invokeLegacy(): array { return $this->getOptionalGraphqlPaymentInputFieldKeys(); }
}
class OldOAuthContract extends \verbb\formie\base\Crm
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function OAuthRequest(string $method = 'GET', string $uri = '', array $options = []) { ++$this->oldCalls; return parent::OAuthRequest($method, $uri, $options); }
    protected function sendOAuthRequest(string $method = 'GET', string $uri = '', array $options = []) { return ['native']; }
    public function invokeCanonical(string $method = 'GET', string $uri = '', array $options = []): mixed { return $this->requestWithOAuthProvider($method, $uri, $options); }
public function invokeLegacy(string $method = 'GET', string $uri = '', array $options = []): mixed { return $this->OAuthRequest($method, $uri, $options); }
}
class NewOAuthContract extends OldOAuthContract
{
    protected function requestWithOAuthProvider(string $method = 'GET', string $uri = '', array $options = []): mixed { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::requestWithOAuthProvider($method, $uri, $options); }
}
class SameOAuthContract extends \verbb\formie\base\Crm
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public bool $failNew = false;
    public function OAuthRequest(string $method = 'GET', string $uri = '', array $options = []) { ++$this->oldCalls; return parent::OAuthRequest($method, $uri, $options); }
    protected function requestWithOAuthProvider(string $method = 'GET', string $uri = '', array $options = []): mixed { ++$this->newCalls; if ($this->failNew) throw new RuntimeException('canonical failure'); return parent::requestWithOAuthProvider($method, $uri, $options); }
    protected function sendOAuthRequest(string $method = 'GET', string $uri = '', array $options = []) { return ['native']; }
    public function invokeCanonical(string $method = 'GET', string $uri = '', array $options = []): mixed { return $this->requestWithOAuthProvider($method, $uri, $options); }
public function invokeLegacy(string $method = 'GET', string $uri = '', array $options = []): mixed { return $this->OAuthRequest($method, $uri, $options); }
}

it('runs mixed provider rename chains once with canonical precedence and exception recovery', function(string $family, bool $sameClass) {
    $class = ($sameClass ? 'Same' : 'New') . $family . 'Contract';
    $provider = new $class();
    $args = match ($family) {
        'Captcha' => [new \verbb\formie\elements\Form()],
        'Mapping' => [[]],
        'Diagnostic' => [new Submission(), 'contacts', [], 'POST', 'json'],
        'Payment' => [new \verbb\formie\models\Payment()],
        'Gql' => [new \verbb\formie\fields\Payment()],
        'OptionalGql' => [],
        'OAuth' => ['GET', 'contacts'],
    };
    $provider->invokeCanonical(...$args);
    expect($provider->newCalls)->toBe(1)->and($provider->oldCalls)->toBe(0);
    $provider->invokeLegacy(...$args);
    expect($provider->newCalls)->toBe(2)->and($provider->oldCalls)->toBe(1);
    $provider->failNew = true;
    expect(fn() => $provider->invokeLegacy(...$args))->toThrow(RuntimeException::class);
    $provider->failNew = false;
    $provider->invokeCanonical(...$args);
    expect($provider->newCalls)->toBe(4)->and($provider->oldCalls)->toBe(2);
})->with(['Captcha', 'Mapping', 'Diagnostic', 'Payment', 'Gql', 'OptionalGql', 'OAuth'])->with([false, true]);

class OldStripeReturnContract extends \verbb\formie\integrations\payments\Stripe
{
    public int $oldCalls = 0;
    public int $newCalls = 0;
    public function getReturnUrl(Submission $submission): string { ++$this->oldCalls; return parent::getReturnUrl($submission); }
}
class NewStripeReturnContract extends OldStripeReturnContract
{
    public function createPaymentReturnUrl(Submission $submission): string { ++$this->newCalls; return parent::createPaymentReturnUrl($submission); }
}

it('prepares a native Stripe return URL once through mixed legacy inheritance', function() {
    $plugin = \verbb\formie\Formie::$plugin;
    $original = $plugin->getPayments();
    $payments = new class extends \verbb\formie\services\Payments {
        public int $calls = 0;
        public function prepareAttempt(\verbb\formie\base\Payment $integration, Submission $submission, ?bool $monetary = null, ?string $operation = null): \verbb\formie\models\Payment { ++$this->calls; return new \verbb\formie\models\Payment(); }
    };
    $plugin->set('payments', $payments);
    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($payments) {
            $provider = new NewStripeReturnContract();
            expect($provider->createPaymentReturnUrl(new Submission()))->toContain('formie/payment-return/index');
            expect($provider->newCalls)->toBe(1)->and($provider->oldCalls)->toBe(0)->and($payments->calls)->toBe(1);
            expect($provider->getReturnUrl(new Submission()))->toContain('formie/payment-return/index');
            expect($provider->newCalls)->toBe(2)->and($provider->oldCalls)->toBe(1)->and($payments->calls)->toBe(2);
        });
    } finally { $plugin->set('payments', $original); }
});

it('restores submission context after an old native override throws', function() {
    $provider = new class extends \verbb\formie\integrations\crm\Pardot {
        public bool $fail = true;
        public function populateContext(): void { parent::populateContext(); if ($this->fail) throw new RuntimeException('context failed'); }
    };
    $submission = new Submission();
    $submission->metadata = ['v' => 1, 'request' => ['referrer' => 'https://example.test/old-submission'], 'custom' => []];
    WebRequestTestHelper::withWebRequestContext(function() use ($provider, $submission) {
        expect(fn() => $provider->populateSubmissionContext($submission))->toThrow(RuntimeException::class);
        $provider->fail = false;
        $provider->populateContext();
        expect($provider->context['referrer'] ?? null)->not->toBe('https://example.test/old-submission');
    });
});

it('preserves event cancellation through legacy read parents without fetching twice', function() {
    $provider = new LegacyMetadataReadContract();
    $provider->on(Integration::EVENT_BEFORE_FETCH_CONFIG, function($event) { $event->isValid = false; });
    expect($provider->getFormSettings(false))->toBeFalse()->and($provider->reads)->toBe(1)->and($provider->fetches)->toBe(0);
    expect($provider->refreshConfig()->all())->toBe([])->and($provider->reads)->toBe(2)->and($provider->fetches)->toBe(0);
});

it('keeps the current run state when a legacy send calls its native parent', function() {
    $provider = new class extends Freshdesk {
        public function sendPayload(Submission $submission): bool { return parent::sendPayload($submission); }
        protected function executePayload(Submission $submission): IntegrationResult { return $this->resultForPayload(false); }
    };
    $context = new IntegrationRunContext(new Submission(), new IntegrationExecutionContext(1, 1, 'state', 'run-state'), 'attempt-state');
    $context->state->uncertain = true;
    $context->state->outputs = ['preserved' => true];
    expect($provider->execute($context)->status)->toBe(IntegrationStatus::Unknown)
        ->and($provider->execute($context)->outputs)->toBe(['preserved' => true]);
});

it('honours unchanged single-value overrides during bulk provider mappings with one event', function() {
    $provider = new class extends Crm {
        public int $calls = 0;
        public function getMappedFieldValue(string $mappedFieldValue, Submission $submission, IntegrationField $integrationField): mixed { ++$this->calls; return parent::getMappedFieldValue($mappedFieldValue, $submission, $integrationField) . '!'; }
    };
    $events = 0;
    $provider->on(Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE, function() use (&$events) { ++$events; });
    $submission = new Submission();
    $submission->setForm(formie()->form()->create());
    expect($provider->getFieldMappingValues($submission, ['destination' => ['kind' => 'literal', 'value' => 'hello']]))->toBe(['destination' => 'hello!'])
        ->and($provider->calls)->toBe(1)->and($events)->toBe(1);
});
