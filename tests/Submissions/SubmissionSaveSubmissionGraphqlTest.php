<?php

declare(strict_types=1);

use craft\db\Query;
use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\base\Captcha;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;
use verbb\formie\integrations\captchas\Question;
use verbb\formie\integrations\payments\Eway;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\Notification;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\PaymentFieldPayload;
use verbb\formie\models\Settings;
use verbb\formie\services\Emails;
use verbb\formie\services\Integrations;
use verbb\formie\services\SubmissionDispatches;
use yii\base\Event;

class MutationDeliveryProvider extends Integration
{
    public static array $calls = [];
    public static function displayName(): string { return 'Mutation delivery fixture'; }
    public function fetchConfig(): IntegrationConfig { return new IntegrationConfig(); }
    public function sendPayload(Submission $submission): IntegrationResult {
        self::$calls[] = $this->handle;
        return IntegrationResult::succeeded();
    }
}

class MutationQuestionCaptcha extends Question
{
    public array $questions = [['question' => 'What colour is the sky?', 'answers' => 'blue']];
}

class MutationContentCaptcha extends Captcha
{
    public static function displayName(): string { return 'Mutation content captcha fixture'; }
    public function fetchConfig(): IntegrationConfig { return new IntegrationConfig(); }
}

class MutationDeliveryCaptcha extends Captcha
{
    public static function displayName(): string { return 'Mutation captcha fixture'; }
    public function fetchConfig(): IntegrationConfig { return new IntegrationConfig(); }
    public function requiresClientToken(): bool { return true; }
    public function validateSubmission(Submission $submission): bool {
        if ($this->getCaptchaValue($submission, 'token') === 'human') {
            return true;
        }
        $this->spamReason = 'Token rejected';
        return false;
    }
}

class MutationPaymentProvider extends Eway
{
    public static bool $approve = true;
    public static int $requests = 0;
    public static ?PaymentDecision $decision = null;
    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload {
        return new PaymentFieldPayload($this->handle, 'payment', ['ewayTokenData' => ['cardNumber' => 'test-encrypted-card', 'securityCode' => 'test-cvn', 'expiryDate' => '12/30']]);
    }
    public function resolvePaymentDecision(Submission $submission): PaymentDecision {
        return self::$decision ?? parent::resolvePaymentDecision($submission);
    }
    public function request(string $method, string $uri, array $options = []): mixed {
        self::$requests++;
        return ['TransactionStatus' => self::$approve, 'TransactionID' => 'test-' . uniqid(), 'ResponseCode' => self::$approve ? '00' : '05', 'ResponseMessage' => self::$approve ? 'A2000' : 'D4405'];
    }
}

function mutationPaymentForm(): Form
{
    $integration = new MutationPaymentProvider(['enabled' => true, 'name' => 'Mutation payment', 'handle' => 'mutationPayment' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form(['title' => 'Mutation payment ' . uniqid()])
        ->singleLineTextField('message')
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => MutationPaymentProvider::class,
            'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
        ])
        ->settings(['disableCaptchas' => true])
        ->create();
    $form->setNotifications([new Notification(['name' => 'Enquiry', 'handle' => 'enquiry' . uniqid(), 'enabled' => true, 'subject' => 'Enquiry', 'to' => 'owner@example.test'])]);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    MutationPaymentProvider::$approve = true;
    MutationPaymentProvider::$requests = 0;
    MutationPaymentProvider::$decision = null;

    return $form;
}

function mutationDeliveryForm(array $settings = ['disableCaptchas' => true], array $notification = [], int $pages = 1): Form
{
    $factory = formie()->form(['title' => 'Mutation delivery ' . uniqid()]);

    if ($pages > 1) {
        $factory = $factory->multiPage($pages)->onPage(1)->singleLineTextField('message')->onPage($pages)->singleLineTextField('finalMessage');
    } else {
        $factory = $factory->singleLineTextField('message');
    }

    $form = $factory->settings($settings)->create();
    $form->setNotifications([new Notification(array_merge([
        'name' => 'Enquiry',
        'handle' => 'enquiry' . uniqid(),
        'enabled' => true,
        'subject' => 'Enquiry',
        'to' => 'owner@example.test',
    ], $notification))]);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return $form;
}

function mutationPageSettings(object $page, array $settings): void
{
    $current = (new Query())->select('settings')->from('{{%formie_fieldlayout_pages}}')->where(['id' => $page->id])->scalar();
    Craft::$app->getDb()->createCommand()->update('{{%formie_fieldlayout_pages}}', ['settings' => json_encode(array_merge((array)json_decode((string)$current, true), $settings))], ['id' => $page->id])->execute();
    Craft::$app->getElements()->invalidateCachesForElementType(Form::class);
}

function mutationPagesForm(): Form
{
    $form = formie()->form(['title' => 'Mutation pages ' . uniqid()])->multiPage(2)
        ->onPage(1)->singleLineTextField('agree')
        ->onPage(2)->singleLineTextField('finalMessage')
        ->settings(['disableCaptchas' => true])->create();
    mutationPageSettings($form->getPages()[0], [
        'enableNextButtonConditions' => true,
        'nextButtonConditions' => ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => 'agree', 'condition' => '=', 'value' => 'yes']]],
    ]);

    return $form;
}

function mutationDeliverySave(Form $form, array $arguments): ?Submission
{
    $info = test()->createMock(ResolveInfo::class);
    $info->fieldDefinition = FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    return Craft::createObject(SubmissionResolver::class)->saveSubmissionByHandle(null, ['formHandle' => (string)$form->handle] + $arguments, null, $info);
}

/** Runs real mutations in a web request, recording emails and integration deliveries without sending them. */
function withMutationDelivery(Form $form, callable $callback, array $integrations = [MutationDeliveryProvider::class => 'remote'], array $settings = [], bool $isPublic = false): array
{
    $plugin = Formie::$plugin;
    $emails = new class extends Emails {
        public array $sent = [];
        public function sendEmail(Notification $notification, Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array {
            $this->sent[] = $notification->name;
            return ['success' => true];
        }
    };
    $previousEmails = $plugin->getEmails();
    $previousSettings = $plugin->getSettings()->getAttributes();
    $plugin->set('emails', $emails);
    $plugin->getSettings()->setAttributes(array_merge(['useQueueForNotifications' => false, 'useQueueForIntegrations' => false], $settings), false);
    $listener = static function($event) use ($form, $integrations): void {
        if ((int)($event->form?->id ?? 0) === (int)$form->id) {
            foreach ($integrations as $class => $handle) {
                $event->integrations[] = new $class(['name' => ucfirst($handle), 'handle' => $handle, 'enabled' => true]);
            }
        }
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    MutationDeliveryProvider::$calls = [];

    try {
        WebRequestTestHelper::withWebRequestContext(fn() => withSaveSubmissionGraphqlScope([
            'formieSubmissions.' . $form->uid . ':create',
            'formieSubmissions.' . $form->uid . ':save',
        ], $callback, $isPublic), ['method' => 'POST']);

        return ['emails' => $emails->sent, 'integrations' => MutationDeliveryProvider::$calls];
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
        $plugin->set('emails', $previousEmails);
        $plugin->getSettings()->setAttributes($previousSettings, false);
    }
}

function mutationDispatchRows(?Submission $submission): array
{
    return (new Query())->select(['kind', 'status'])->from(SubmissionDispatches::TABLE)->where(['submissionId' => $submission?->id])->all();
}

it('registers a generic saveSubmission mutation when submission scopes are available', function (): void {
    withSaveSubmissionGraphqlScope([
        'formieSubmissions.all:create',
    ], function (): void {
        $mutations = SubmissionMutation::getMutations();

        expect($mutations)->toHaveKey('saveSubmission')
            ->and($mutations['saveSubmission']['name'])->toBe('saveSubmission')
            ->and(array_keys($mutations['saveSubmission']['args']))->toContain('formHandle', 'fields', 'captchas');
    });
});

it('creates a submission through saveSubmission with a fields map', function (): void {
    $form = formie()
        ->form([
            'title' => 'Generic GraphQL Save ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->emailField('emailAddress', ['required' => true])
        ->create();

    $initialCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    $submission = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($resolver, $form, $resolveInfo): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'fields' => [
                'yourName' => 'Peter Sherman',
                'emailAddress' => 'peter@example.test',
            ],
        ], null, $resolveInfo);
    });

    $finalCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();

    expect($submission)->toBeInstanceOf(Submission::class)
        ->and($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('yourName'))->toBe('Peter Sherman')
        ->and($submission->getFieldValue('emailAddress'))->toBe('peter@example.test')
        ->and($finalCount)->toBe($initialCount + 1);
});

it('applies the form\'s availability rules to mutation submissions', function (array $settings, string $message): void {
    $form = mutationDeliveryForm(['disableCaptchas' => true] + $settings);
    $seeded = isset($settings['limitSubmissions']);

    if ($seeded) {
        formie()->submission($form)->with(['message' => 'Earlier'])->save();
    }

    $user = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(null);

    try {
        $delivered = withMutationDelivery($form, function() use ($form, $message): void {
            expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]))->toThrow(Error::class, $message);
        });
    } finally {
        Craft::$app->getUser()->setIdentity($user);
    }

    expect($delivered)->toBe(['emails' => [], 'integrations' => []])
        ->and((int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count())->toBe($seeded ? 1 : 0);
})->with([
    'requires a user' => [['requireUser' => true], 'You must be logged in'],
    'scheduled to open later' => [['scheduleForm' => true, 'scheduleFormStart' => new DateTime('+1 year')], 'This form is not available'],
    'at its submission limit' => [['limitSubmissions' => true, 'limitSubmissionsNumber' => 1, 'limitSubmissionsType' => 'total'], 'allowed submissions'],
]);

it('follows field conditions on mutation submissions', function (array $fields, string $stored): void {
    $form = formie()->form(['title' => 'Mutation conditions ' . uniqid()])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('message')
        ->singleLineTextField('detail', ['required' => true, 'enableConditions' => true, 'conditions' => [
            'showRule' => 'show',
            'conditionRule' => 'all',
            'conditions' => [['field' => '{field:' . bin2hex(random_bytes(1)) . '}', 'condition' => '=', 'value' => 'show']],
        ]])
        ->create();
    $detail = $form->getFieldByHandle('detail');
    $detail->conditions['conditions'][0]['field'] = '{field:' . $form->getFieldByHandle('message')->uid . '}';
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $saved = null;
    withMutationDelivery($form, function() use ($form, $fields, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => $fields]);
    }, []);

    expect($saved->getFieldValue('detail'))->toBe($stored);
})->with([
    'hidden value is cleared' => [['message' => 'hide', 'detail' => 'secret'], ''],
    'hidden required field is not required' => [['message' => 'hide'], ''],
    'visible value is kept' => [['message' => 'show', 'detail' => 'shown'], 'shown'],
]);

it('still requires a visible required field on mutation submissions', function (): void {
    $form = formie()->form(['title' => 'Mutation required ' . uniqid()])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('message')
        ->singleLineTextField('detail', ['required' => true])
        ->create();
    withMutationDelivery($form, function() use ($form): void {
        expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]))->toThrow(Error::class, 'cannot be blank');
    }, []);
});

it('applies default values and captures request details on mutation submissions', function (): void {
    $form = formie()->form(['title' => 'Mutation defaults ' . uniqid()])
        ->settings(['disableCaptchas' => true, 'collectIp' => true])
        ->singleLineTextField('message')
        ->singleLineTextField('detail', ['defaultValue' => 'fallback'])
        ->create();
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]);
    }, [], isPublic: true);

    expect($saved->getFieldValue('detail'))->toBe('fallback')
        ->and($saved->getMetadata('request'))->toHaveKey('userAgent')
        ->and((new Query())->select('ipAddress')->from('{{%formie_submissions}}')->where(['id' => $saved->id])->scalar())->not->toBeEmpty();
});

it('charges a payment field once when a mutation completes the submission', function (): void {
    $form = mutationPaymentForm();
    $saved = null;
    $delivered = withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Paying']]);
    }, []);

    expect(MutationPaymentProvider::$requests)->toBe(1)
        ->and($saved->isIncomplete)->toBeFalse()
        ->and(Formie::$plugin->getPayments()->getSubmissionPayments($saved))->toHaveCount(1)
        ->and($delivered['emails'])->toBe(['Enquiry']);
});

it('reports a declined payment on a mutation without delivering the submission', function (): void {
    $form = mutationPaymentForm();
    MutationPaymentProvider::$approve = false;
    $delivered = withMutationDelivery($form, function() use ($form): void {
        expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Paying']]))->toThrow(Error::class);
    }, []);

    expect(MutationPaymentProvider::$requests)->toBe(1)
        ->and($delivered['emails'])->toBe([]);
});

it('reports a payment that needs a follow-up step instead of an empty error', function (): void {
    $form = mutationPaymentForm();
    MutationPaymentProvider::$decision = PaymentDecision::requiresAction('ref-1', PaymentAction::redirect('mutationPayment'), ['message' => 'Confirm this payment with your bank.']);
    $delivered = withMutationDelivery($form, function() use ($form): void {
        expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Paying']]))->toThrow(Error::class, 'Confirm this payment');
    }, []);

    expect($delivered['emails'])->toBe([]);
});

it('creates and delivers a submission through a public schema', function (): void {
    $form = mutationDeliveryForm();
    $saved = null;
    $delivered = withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]);
    }, isPublic: true);

    expect($saved->isIncomplete)->toBeFalse()
        ->and($saved->getFieldValue('message'))->toBe('Hello')
        ->and($delivered)->toBe(['emails' => ['Enquiry'], 'integrations' => ['remote']]);
});

it('screens submissions made through a public schema', function (): void {
    $form = mutationDeliveryForm(settings: []);
    $saved = null;
    $delivered = withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]);
    }, [MutationDeliveryCaptcha::class => 'token'], ['spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS, 'saveSpam' => true, 'spamEmailNotifications' => false], true);

    expect((bool)(new Query())->select('isSpam')->from('{{%formie_submissions}}')->where(['id' => $saved->id])->scalar())->toBeTrue()
        ->and($delivered['emails'])->toBe([]);
});

it('blocks editing a submission by ID through a public schema', function (string $identifier): void {
    $form = mutationDeliveryForm();
    $created = formie()->submission($form)->with(['message' => 'Original'])->save();
    withMutationDelivery($form, function() use ($form, $created, $identifier): void {
        expect(fn() => mutationDeliverySave($form, [$identifier => $created->{$identifier}, 'expectedVersion' => $created->stateVersion, 'fields' => ['message' => 'Overwritten']]))
            ->toThrow(Error::class, 'Unable to perform the action.');
    }, isPublic: true);

    $stored = Submission::find()->id($created->id)->status(null)->one();
    expect($stored->getFieldValue('message'))->toBe('Original');
})->with(['id', 'uid']);

it('doesn\'t reveal whether a submission exists through a public schema', function (): void {
    $form = mutationDeliveryForm();
    withMutationDelivery($form, function() use ($form): void {
        expect(fn() => mutationDeliverySave($form, ['id' => 2147483000, 'fields' => ['message' => 'Hello']]))->toThrow(Error::class, 'Unable to perform the action.');
    }, isPublic: true);
});

it('blocks saving drafts through a public schema, which could never be continued', function (): void {
    $form = mutationDeliveryForm();
    withMutationDelivery($form, function() use ($form): void {
        expect(fn() => mutationDeliverySave($form, ['isIncomplete' => true, 'fields' => ['message' => 'Draft']]))->toThrow(Error::class, 'Unable to perform the action.');
    }, isPublic: true);

    expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(0);
});

it('ignores arguments a visitor couldn\'t set through a public schema', function (): void {
    $form = mutationDeliveryForm();
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['title' => 'Forced title', 'status' => 'forced', 'enabled' => false, 'fields' => ['message' => 'Hello']]);
    }, [], isPublic: true);

    expect($saved->title)->not->toBe('Forced title')
        ->and($saved->enabled)->toBeTrue()
        ->and($saved->statusId)->toBe($form->getDefaultStatus()->id);
});

it('doesn\'t replay an earlier result by operation ID through a public schema', function (): void {
    $form = mutationDeliveryForm();
    $first = null;
    $second = null;
    withMutationDelivery($form, function() use ($form, &$first, &$second): void {
        $first = mutationDeliverySave($form, ['operationId' => 'shared-guess', 'fields' => ['message' => 'First']]);
        $second = mutationDeliverySave($form, ['operationId' => 'shared-guess', 'fields' => ['message' => 'First']]);
    }, [], isPublic: true);

    expect($second->id)->not->toBe($first->id);
});

it('applies abuse limits to public schema submissions but not private ones', function (array $settings, bool $isPublic, int $saved): void {
    $form = mutationDeliveryForm(['disableCaptchas' => true, 'collectIp' => true]);
    Craft::$app->getCache()->flush();
    withMutationDelivery($form, function() use ($form, $isPublic, $saved): void {
        mutationDeliverySave($form, ['fields' => ['message' => 'First']]);
        $second = fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Second']]);

        if ($saved === 1) {
            expect($second)->toThrow(Error::class);
        } else {
            $second();
        }
    }, [], $settings, $isPublic);

    expect((int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->count())->toBe($saved);
})->with([
    'anonymous rate limit, public' => [['anonymousFormRefreshRateLimit' => 1], true, 1],
    'global throttle, public' => [['enableGlobalSubmissionThrottling' => true, 'globalSubmissionThrottleLimit' => 1, 'globalSubmissionThrottleWindowSeconds' => 60], true, 1],
    'IP throttle, public' => [['enableIpSubmissionThrottling' => true, 'ipSubmissionThrottleMinutes' => 5], true, 1],
    'anonymous rate limit, private' => [['anonymousFormRefreshRateLimit' => 1], false, 2],
    'IP throttle, private' => [['enableIpSubmissionThrottling' => true, 'ipSubmissionThrottleMinutes' => 5], false, 2],
]);

it('requires proof of ownership to attach an existing asset through a public schema', function (): void {
    UploadTestHelper::ensureUploadVolume();
    $asset = UploadTestHelper::seedAsset('private.txt', 'Private content');
    $form = formie()->form(['title' => 'Mutation upload ' . uniqid()])->settings(['disableCaptchas' => true])->fileUploadField('files', ['restrictFiles' => false])->create();
    withMutationDelivery($form, function() use ($form, $asset): void {
        expect(fn() => mutationDeliverySave($form, ['fields' => ['files' => [['assetId' => (int)$asset->id]]]]))->toThrow(Error::class, 'attachment capability');
    }, [], isPublic: true);

    expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(0);
});

it('accepts a new file through a public schema', function (): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['title' => 'Mutation upload ' . uniqid()])->settings(['disableCaptchas' => true])->fileUploadField('files', ['restrictFiles' => false])->create();
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['files' => [['filename' => 'added.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('New content')]]]]);
    }, [], isPublic: true);

    expect(file_get_contents($saved->getFieldValue('files')->one()->getCopyOfFile()))->toBe('New content');
});

it('saves incomplete mutation submissions as drafts without requiring every field', function (): void {
    $form = formie()->form(['title' => 'Mutation draft ' . uniqid()])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('message')
        ->singleLineTextField('detail', ['required' => true])
        ->create();
    $draft = null;
    $next = null;
    $delivered = withMutationDelivery($form, function() use ($form, &$draft, &$next): void {
        $draft = mutationDeliverySave($form, ['isIncomplete' => true, 'fields' => ['message' => 'Partial']]);
        $next = mutationDeliverySave($form, ['id' => $draft->id, 'expectedVersion' => $draft->stateVersion, 'fields' => ['message' => 'More']]);
    }, []);

    expect($draft->isIncomplete)->toBeTrue()
        ->and($next->isIncomplete)->toBeTrue()
        ->and($next->getFieldValue('message'))->toBe('More')
        ->and($delivered['emails'])->toBe([]);
});

it('delivers a completed mutation submission and leaves a dispatch record', function (): void {
    $form = mutationDeliveryForm();
    $saved = null;
    $delivered = withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]);
    });

    expect($saved->isIncomplete)->toBeFalse()
        ->and($saved->isSpam)->toBeFalse()
        ->and($delivered)->toBe(['emails' => ['Enquiry'], 'integrations' => ['remote']])
        ->and(mutationDispatchRows($saved))->toBe([['kind' => 'completion', 'status' => 'completed']]);
});

it('doesn\'t offer arguments to skip delivery', function (array $arguments): void {
    $form = mutationDeliveryForm();
    withMutationDelivery($form, function() use ($form, $arguments): void {
        expect(fn() => mutationDeliverySave($form, $arguments + ['fields' => ['message' => 'Hello']]))->toThrow(Error::class, 'Unknown submission argument');
    });
})->with([
    'notifications' => [['sendNotifications' => false]],
    'integrations' => [['triggerIntegrations' => false]],
]);

it('fires the completion event once when delivering a mutation submission', function (): void {
    $form = mutationDeliveryForm();
    $completed = 0;
    $listener = static function() use (&$completed): void { $completed++; };
    Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, $listener);

    try {
        withMutationDelivery($form, fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]));
    } finally {
        Event::off(Submission::class, Submission::EVENT_AFTER_COMPLETE, $listener);
    }

    expect($completed)->toBe(1);
});

it('delivers once when a mutation completes an incomplete submission', function (): void {
    $form = mutationDeliveryForm();
    $draft = null;
    $completed = null;
    $drafted = withMutationDelivery($form, function() use ($form, &$draft): void {
        $draft = mutationDeliverySave($form, ['isIncomplete' => true, 'fields' => ['message' => 'Draft']]);
    });

    expect($draft->isIncomplete)->toBeTrue()
        ->and($drafted)->toBe(['emails' => [], 'integrations' => []])
        ->and(mutationDispatchRows($draft))->toBe([]);

    $delivered = withMutationDelivery($form, function() use ($form, $draft, &$completed): void {
        $completed = mutationDeliverySave($form, ['id' => $draft->id, 'expectedVersion' => $draft->stateVersion, 'isIncomplete' => false, 'fields' => ['message' => 'Final']]);
    });

    expect($completed->isIncomplete)->toBeFalse()
        ->and($delivered)->toBe(['emails' => ['Enquiry'], 'integrations' => ['remote']]);
});

it('does not deliver again when a mutation edits a completed submission', function (): void {
    $form = mutationDeliveryForm();
    $created = null;
    withMutationDelivery($form, function() use ($form, &$created): void {
        $created = mutationDeliverySave($form, ['fields' => ['message' => 'Original']]);
    });
    $edited = null;
    $delivered = withMutationDelivery($form, function() use ($form, $created, &$edited): void {
        $edited = mutationDeliverySave($form, ['id' => $created->id, 'expectedVersion' => $created->stateVersion, 'fields' => ['message' => 'Edited']]);
    });

    expect($edited->getFieldValue('message'))->toBe('Edited')
        ->and($delivered)->toBe(['emails' => [], 'integrations' => []]);
});

it('screens completed mutation submissions for spam', function (string $behaviour, bool $saveSpam, bool $throws, bool $saved): void {
    $form = mutationDeliveryForm();
    $before = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
    $result = 'unset';
    $delivered = withMutationDelivery($form, function() use ($form, $throws, &$result): void {
        $save = fn() => mutationDeliverySave($form, ['fields' => ['message' => 'contains blocked-keyword content']]);

        if ($throws) {
            expect($save)->toThrow(Error::class, 'Not accepted');
        } else {
            $result = $save();
        }
    }, settings: [
        'spamKeywords' => 'blocked-keyword',
        'spamBehaviour' => $behaviour,
        'spamBehaviourMessage' => 'Not accepted',
        'saveSpam' => $saveSpam,
        'spamEmailNotifications' => false,
        'enableSuspiciousTextDetection' => false,
    ]);
    $stored = Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->all();

    expect($delivered)->toBe(['emails' => [], 'integrations' => []])
        ->and(count($stored) - $before)->toBe($saved ? 1 : 0);

    if ($saved) {
        expect($stored[0]->isSpam)->toBeTrue()->and($stored[0]->spamReason)->not->toBeEmpty();
    }

    if (!$throws) {
        expect($saved ? $result?->id : $result)->toBe($saved ? $stored[0]->id : null);
    }
})->with([
    'success, saved' => [Settings::SPAM_BEHAVIOUR_SUCCESS, true, false, true],
    'success, discarded' => [Settings::SPAM_BEHAVIOUR_SUCCESS, false, false, false],
    'message, saved' => [Settings::SPAM_BEHAVIOUR_MESSAGE, true, true, true],
    'message, discarded' => [Settings::SPAM_BEHAVIOUR_MESSAGE, false, true, false],
]);

it('sends spam notifications for mutation submissions when enabled', function (): void {
    $form = mutationDeliveryForm();
    $delivered = withMutationDelivery($form, fn() => mutationDeliverySave($form, ['fields' => ['message' => 'contains blocked-keyword content']]), settings: [
        'spamKeywords' => 'blocked-keyword',
        'spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS,
        'saveSpam' => true,
        'spamEmailNotifications' => true,
        'enableSuspiciousTextDetection' => false,
    ]);

    expect($delivered)->toBe(['emails' => ['Enquiry'], 'integrations' => []]);
});

it('checks captcha tokens from completing mutations', function (int $pages, array $captchas, bool $spam): void {
    $form = mutationDeliveryForm(settings: [], pages: $pages);
    $saved = null;
    $delivered = withMutationDelivery($form, function() use ($form, $captchas, &$saved): void {
        $saved = mutationDeliverySave($form, ['captchas' => $captchas, 'fields' => ['message' => 'Hello']]);
    }, [MutationDeliveryProvider::class => 'remote', MutationDeliveryCaptcha::class => 'token'], ['spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS, 'saveSpam' => true, 'spamEmailNotifications' => false]);

    expect($saved->isSpam)->toBe($spam)
        ->and($delivered)->toBe($spam ? ['emails' => [], 'integrations' => []] : ['emails' => ['Enquiry'], 'integrations' => ['remote']]);
})->with([
    'wrong token' => [1, ['tokenCaptcha' => ['name' => 'token', 'value' => 'bot']], true],
    'valid token' => [1, ['tokenCaptcha' => ['name' => 'token', 'value' => 'human']], false],
    'valid token, multi-page' => [2, ['tokenCaptcha' => ['name' => 'token', 'value' => 'human']], false],
]);

it('tells a private caller when a captcha token is missing, instead of silently marking it as spam', function (int $pages): void {
    $form = mutationDeliveryForm(settings: [], pages: $pages);
    $delivered = withMutationDelivery($form, function() use ($form): void {
        expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]))->toThrow(Error::class, 'tokenCaptcha');
    }, [MutationDeliveryCaptcha::class => 'token'], ['spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS, 'saveSpam' => true]);

    expect($delivered['emails'])->toBe([])
        ->and((int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count())->toBe(0);
})->with(['single page' => 1, 'multi-page' => 2]);

it('doesn\'t ask for a token from captchas that only check the content', function (): void {
    $form = mutationDeliveryForm(settings: []);
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]);
    }, [MutationContentCaptcha::class => 'content'], ['saveSpam' => true]);

    expect($saved->isSpam)->toBeFalse();
});

it('updates an existing submission through saveSubmission', function (): void {
    $form = formie()
        ->form([
            'title' => 'Generic GraphQL Save Update ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();

    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    $created = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
        'formieSubmissions.' . $form->uid . ':save',
    ], function () use ($resolver, $form, $resolveInfo): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'fields' => [
                'yourName' => 'Before',
            ],
        ], null, $resolveInfo);
    });

    $updated = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
        'formieSubmissions.' . $form->uid . ':save',
    ], function () use ($resolver, $form, $resolveInfo, $created): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'id' => $created->id,
            'expectedVersion' => $created->stateVersion,
            'fields' => [
                'yourName' => 'After',
            ],
        ], null, $resolveInfo);
    });

    expect($updated->id)->toBe($created->id)
        ->and($updated->getFieldValue('yourName'))->toBe('After');
});

it('requires create or save scopes for the targeted form', function (): void {
    $allowedForm = formie()
        ->form([
            'title' => 'Allowed Generic GraphQL Save ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();
    $blockedForm = formie()
        ->form([
            'title' => 'Blocked Generic GraphQL Save ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();

    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $allowedForm->uid . ':create',
    ], function () use ($resolver, $blockedForm, $resolveInfo): void {
        expect(fn() => $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$blockedForm->handle,
            'fields' => [
                'yourName' => 'Nope',
            ],
        ], null, $resolveInfo))->toThrow(Error::class, 'Unable to perform the action.');
    });
});

it('rejects saveSubmission updates without save scope', function (): void {
    $form = formie()
        ->form([
            'title' => 'Generic GraphQL Save Scope ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();

    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    $created = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($resolver, $form, $resolveInfo): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'fields' => [
                'yourName' => 'Initial',
            ],
        ], null, $resolveInfo);
    });

    withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($resolver, $form, $resolveInfo, $created): void {
        expect(fn() => $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'id' => $created->id,
            'expectedVersion' => $created->stateVersion,
            'fields' => [
                'yourName' => 'Updated',
            ],
        ], null, $resolveInfo))->toThrow(Error::class, 'Unable to perform the action.');
    });
});

function withSaveSubmissionGraphqlScope(array $scope, callable $callback, bool $isPublic = false): mixed
{
    $gqlService = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie Generic Save Submission Test Schema',
        'scope' => $scope,
        'isPublic' => $isPublic,
    ]));

    try {
        return $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }
}

function saveSubmissionGraphqlHandle(): string
{
    return 'test' . bin2hex(random_bytes(8));
}

it('executes generic submission mutations with typed nested field values', function (): void {
    $form = formie()->form(['handle' => saveSubmissionGraphqlHandle()])
        ->settings(['disableCaptchas' => true])
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new \verbb\formie\fields\Name())->getSubFields()])
        ->repeaterField('items', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class,
            'handle' => 'description',
            'label' => 'Description',
        ]]]]])
        ->create();

    withSaveSubmissionGraphqlScope(['formieSubmissions.all:create', 'formieSubmissions.all:read'], function () use ($form): void {
        $schema = new \GraphQL\Type\Schema([
            'query' => new \GraphQL\Type\Definition\ObjectType([
                'name' => 'AuditQuery',
                'fields' => ['ping' => \GraphQL\Type\Definition\Type::string()],
            ]),
            'mutation' => new \GraphQL\Type\Definition\ObjectType([
                'name' => 'AuditMutation',
                'fields' => ['saveSubmission' => SubmissionMutation::createGenericSaveMutation()],
            ]),
            'types' => [\verbb\formie\gql\types\generators\SubmissionGenerator::generateType($form)],
        ]);
        $result = \GraphQL\GraphQL::executeQuery($schema,
            'mutation ($handle: String!, $fields: Array) { saveSubmission(formHandle: $handle, fields: $fields) { id } }',
            null, null, [
                'handle' => $form->handle,
                'fields' => [
                    'person' => ['firstName' => 'Jane', 'lastName' => 'Doe'],
                    'items' => ['rows' => [['description' => 'First'], ['description' => 'Second']]],
                ],
            ]);

        expect(array_map(fn($error) => $error->getMessage(), $result->errors))->toBe([]);
        $submission = Submission::find()->id($result->data['saveSubmission']['id'])->status(null)->isIncomplete(null)->isSpam(null)->one();
        expect($submission->getFieldValue('person')->firstName)->toBe('Jane');
        $rows = $submission->getFieldValue('items');
        expect($rows[0]['description'] ?? null)->toBe('First')
            ->and($rows[1]['description'] ?? null)->toBe('Second');
    });
});

it('rejects unknown or malformed generic field and captcha maps before saving', function (array $payload, string $message): void {
    $form = formie()->form(['handle' => saveSubmissionGraphqlHandle()])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new \verbb\formie\fields\Name())->getSubFields()])
        ->create();
    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    withSaveSubmissionGraphqlScope(['formieSubmissions.all:create'], function () use ($form, $resolver, $resolveInfo, $payload, $message): void {
        expect(fn() => $resolver->saveSubmissionByHandle(null, ['formHandle' => $form->handle] + $payload, null, $resolveInfo))
            ->toThrow(Error::class, $message);
        expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count())->toBe(0);
    });
})->with([
    'element attribute in fields' => [['fields' => ['formId' => 123]], 'Unknown fields argument'],
    'identifier in fields' => [['fields' => ['id' => 123]], 'Unknown fields argument'],
    'field in captchas' => [['captchas' => ['yourName' => 'Changed']], 'Unknown captchas argument'],
    'wrong scalar type' => [['fields' => ['yourName' => ['nested']]], 'Invalid fields argument'],
    'unknown nested field' => [['fields' => ['person' => ['unknown' => 'value']]], 'Invalid fields argument'],
]);

it('clears optional nested fields through GraphQL submission updates', function (string $fieldHandle, bool $generic): void {
    $form = formie()->form()->settings(['disableCaptchas' => true])
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new \verbb\formie\fields\Name())->getSubFields()])
        ->groupField('details', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'note', 'label' => 'Note',
        ]]]]])
        ->repeaterField('items', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'description', 'label' => 'Description',
        ]]]]])
        ->create();
    $submission = formie()->submission($form)->with([
        'person' => ['firstName' => 'Before'],
        'details' => ['note' => 'Before'],
        'items' => [['description' => 'Before']],
    ])->save();

    $arguments = $generic
        ? ['id' => $submission->id, 'expectedVersion' => $submission->stateVersion, 'formHandle' => $form->handle, 'fields' => [$fieldHandle => null]]
        : ['id' => $submission->id, 'expectedVersion' => $submission->stateVersion, $fieldHandle => null];

    $saved = withSaveSubmissionGraphqlScope(['formieSubmissions.all:save'], function() use ($generic, $form, $arguments) {
        $mutation = $generic ? SubmissionMutation::createGenericSaveMutation() : SubmissionMutation::createSaveMutation($form);
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create($mutation);
        return ($mutation['resolve'])(null, $arguments, null, $resolveInfo);
    });
    expect($form->getFieldByHandle($fieldHandle)->isValueEmpty($saved->getFieldValue($fieldHandle), $saved))->toBeTrue();
})->with(['person', 'details', 'items'])->with([true, false]);

it('rescores completed quiz answers after administrative GraphQL edits', function (): void {
    $form = formie()->form()->settings(['scoringEnabled' => true, 'disableCaptchas' => true])
        ->addField(\verbb\formie\fields\Quiz::class, 'answer', ['options' => [
            ['label' => 'Right', 'value' => 'right', 'isCorrect' => true],
            ['label' => 'Wrong', 'value' => 'wrong', 'isCorrect' => false],
        ]])->create();
    $submission = formie()->submission($form)->with(['answer' => 'right'])->save();
    $submission->isIncomplete = false;
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $scoring = \verbb\formie\Formie::$plugin->getQuestionnaireScoring();
    expect($scoring->scoreSubmission($submission)->score)->toBe(1.0);
    withSaveSubmissionGraphqlScope(['formieSubmissions.all:save'], function () use ($form, $submission): void {
        $result = \verbb\formie\Formie::$plugin->getSubmissionRequests()->executeMutation($form, $submission,
            ['expectedVersion' => $submission->stateVersion], fn() => $submission->setFieldValue('answer', 'wrong'));
        expect($result->response->outcome->type->value)->toBe('revised');
    });
    expect($scoring->getQuizResultForSubmission($submission->id)->score)->toBe(0.0);
});

it('lets visitors submit a form at the same time through a mutation', function (bool $isPublic): void {
    $form = mutationDeliveryForm();
    $inner = null;
    $nested = false;
    $listener = static function($event) use ($form, &$inner, &$nested): void {
        // A second visitor arrives while the first is still inside the workflow, holding any locks it takes.
        if (!$nested && $event->stage === Stage::VALIDATE->value && (int)$event->command->form->id === (int)$form->id) {
            $nested = true;
            $inner = mutationDeliverySave($form, ['fields' => ['message' => 'Second visitor']]);
        }
    };
    $outer = null;
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $listener);

    try {
        withMutationDelivery($form, function() use ($form, &$outer): void {
            $outer = mutationDeliverySave($form, ['fields' => ['message' => 'First visitor']]);
        }, [], isPublic: $isPublic);
    } finally {
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $listener);
    }

    expect($inner?->id)->not->toBeNull()->and($outer->id)->not->toBe($inner->id);
})->with(['private' => false, 'public' => true]);

it('doesn\'t treat a server\'s IP as the visitor\'s for private tokens', function (): void {
    $form = mutationDeliveryForm(['disableCaptchas' => true, 'collectIp' => true, 'limitSubmissions' => 'ipAddress', 'limitSubmissionsIpAddressNumber' => 1, 'limitSubmissionsIpAddressType' => 'total']);
    $first = null;
    $second = null;
    withMutationDelivery($form, function() use ($form, &$first, &$second): void {
        $first = mutationDeliverySave($form, ['fields' => ['message' => 'First visitor']]);
        $second = mutationDeliverySave($form, ['fields' => ['message' => 'Second visitor']]);
    }, []);

    expect($second?->id)->not->toBeNull()
        ->and((new Query())->select('ipAddress')->from('{{%formie_submissions}}')->where(['id' => $first->id])->scalar())->toBeNull();
});

it('still applies per-IP limits through a public schema', function (): void {
    $form = mutationDeliveryForm(['disableCaptchas' => true, 'collectIp' => true, 'limitSubmissions' => 'ipAddress', 'limitSubmissionsIpAddressNumber' => 1, 'limitSubmissionsIpAddressType' => 'total']);
    withMutationDelivery($form, function() use ($form): void {
        mutationDeliverySave($form, ['fields' => ['message' => 'First visitor']]);
        expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Second visitor']]))->toThrow(Error::class);
    }, [], isPublic: true);
});

it('returns the real reason when an origin is refused', function (bool $isPublic): void {
    $form = mutationDeliveryForm();
    WebRequestTestHelper::withWebRequestContext(function() use ($form, $isPublic): void {
        withSaveSubmissionGraphqlScope(['formieSubmissions.' . $form->uid . ':create'], function() use ($form): void {
            expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]))->toThrow(Error::class, 'allowedOrigins');
        }, $isPublic);
    }, ['method' => 'POST', 'headers' => ['Origin' => 'https://app.example.test']]);
})->with(['private' => false, 'public' => true]);

it('returns the original submission when a create is retried with the same operation ID', function (array $arguments): void {
    $form = mutationDeliveryForm();
    $first = null;
    $second = null;
    $delivered = withMutationDelivery($form, function() use ($form, $arguments, &$first, &$second): void {
        $first = mutationDeliverySave($form, $arguments + ['operationId' => 'retry-a', 'fields' => ['message' => 'Hello']]);
        $second = mutationDeliverySave($form, $arguments + ['operationId' => 'retry-a', 'fields' => ['message' => 'Hello']]);
    }, []);

    expect($second->id)->toBe($first->id)
        ->and((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(1)
        ->and(count($delivered['emails']))->toBe(isset($arguments['isIncomplete']) ? 0 : 1);
})->with(['completed' => [[]], 'draft' => [['isIncomplete' => true]]]);

it('keeps values a token edit didn\'t send', function (): void {
    $form = formie()->form(['title' => 'Mutation edit ' . uniqid()])->settings(['disableCaptchas' => true])
        ->singleLineTextField('message')
        ->singleLineTextField('note', ['visibility' => 'disabled'])
        ->singleLineTextField('extra', ['defaultValue' => 'fallback'])
        ->hiddenField('stamp', ['valueSource' => 'dateInt'])
        ->create();
    $saved = null;
    $edited = null;
    $corrected = null;
    withMutationDelivery($form, function() use ($form, &$saved, &$edited, &$corrected): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'First']]);
        $state = $saved->getContentState();
        $content = json_decode((string)(new Query())->select('content')->from('{{%formie_submissions}}')->where(['id' => $saved->id])->scalar(), true);
        $content[$form->getFieldByHandle('note')->uid] = 'Internal note';
        $content[$form->getFieldByHandle('stamp')->uid] = 'captured';
        unset($content[$form->getFieldByHandle('extra')->uid]);
        Craft::$app->getDb()->createCommand()->update('{{%formie_submissions}}', ['content' => json_encode($content)], ['id' => $saved->id])->execute();
        $edited = mutationDeliverySave($form, ['id' => $saved->id, 'expectedVersion' => $saved->stateVersion, 'fields' => ['message' => 'Second']]);
        $corrected = mutationDeliverySave($form, ['id' => $saved->id, 'expectedVersion' => $edited->stateVersion, 'fields' => ['note' => 'Corrected note']]);
    }, []);

    expect($edited->getFieldValue('message'))->toBe('Second')
        ->and($edited->getFieldValue('note'))->toBe('Internal note')
        ->and($edited->getFieldValue('stamp'))->toBe('captured')
        ->and($edited->getFieldValue('extra'))->not->toBe('fallback')
        ->and($corrected->getFieldValue('note'))->toBe('Corrected note');
});

it('checks next button conditions on every page a mutation passes through', function (array $fields, bool $accepted): void {
    $form = mutationPagesForm();
    $saved = null;
    withMutationDelivery($form, function() use ($form, $fields, $accepted, &$saved): void {
        $save = fn() => mutationDeliverySave($form, ['fields' => $fields]);

        if ($accepted) {
            $saved = $save();
        } else {
            expect($save)->toThrow(Error::class, 'Complete the requirements');
        }
    }, []);

    if ($accepted) {
        expect($saved->isIncomplete)->toBeFalse();
    }
})->with([
    'condition unmet' => [['agree' => 'no', 'finalMessage' => 'x'], false],
    'condition met' => [['agree' => 'yes', 'finalMessage' => 'x'], true],
]);

it('treats the last page a submission reaches as its last page', function (): void {
    $form = formie()->form(['title' => 'Mutation hidden page ' . uniqid()])->multiPage(2)
        ->onPage(1)->singleLineTextField('route')
        ->onPage(2)->singleLineTextField('finalMessage')
        ->settings(['disableCaptchas' => true])->create();
    $pages = $form->getPages();
    mutationPageSettings($pages[1], [
        'enablePageConditions' => true,
        'pageConditions' => ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => 'route', 'condition' => '=', 'value' => 'long']]],
        'enableNextButtonConditions' => true,
        'nextButtonConditions' => ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => 'finalMessage', 'condition' => '=', 'value' => 'never']]],
    ]);
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['route' => 'short']]);
    }, []);

    expect($saved->isIncomplete)->toBeFalse();
});

it('keeps every page\'s answers when only current page payloads are set', function (): void {
    $form = mutationDeliveryForm(pages: 2);
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Page one', 'finalMessage' => 'Page two']]);
    }, [], ['setOnlyCurrentPagePayload' => true]);

    expect($saved->getFieldValue('message'))->toBe('Page one')
        ->and($saved->getFieldValue('finalMessage'))->toBe('Page two');
});

it('accepts and ignores the Formie 3 isNewSubmission argument', function (bool $value): void {
    $form = mutationDeliveryForm();
    $saved = null;
    $delivered = withMutationDelivery($form, function() use ($form, $value, &$saved): void {
        $saved = mutationDeliverySave($form, ['isNewSubmission' => $value, 'fields' => ['message' => 'Hello']]);
    }, []);

    expect($saved->id)->not->toBeNull()->and($delivered['emails'])->toBe(['Enquiry']);
})->with([true, false]);

it('explains version and operation conflicts', function (): void {
    $form = mutationDeliveryForm();
    withMutationDelivery($form, function() use ($form): void {
        $saved = mutationDeliverySave($form, ['operationId' => 'conflict', 'fields' => ['message' => 'Hello']]);
        expect(fn() => mutationDeliverySave($form, ['id' => $saved->id, 'fields' => ['message' => 'Missing version']]))->toThrow(Error::class, 'expectedVersion');
        expect(fn() => mutationDeliverySave($form, ['id' => $saved->id, 'expectedVersion' => 99, 'fields' => ['message' => 'Stale version']]))->toThrow(Error::class, 'expectedVersion');
        expect(fn() => mutationDeliverySave($form, ['operationId' => 'conflict', 'fields' => ['message' => 'Changed']]))->toThrow(Error::class, 'operationId');
    }, []);
});

it('treats a field with a reserved-looking handle as an answer', function (string $handle, bool $isPublic): void {
    $form = formie()->form(['title' => 'Mutation handle ' . uniqid()])->settings(['disableCaptchas' => true])->singleLineTextField($handle)->create();
    $first = null;
    $second = null;
    withMutationDelivery($form, function() use ($form, $handle, &$first, &$second): void {
        $first = mutationDeliverySave($form, ['fields' => [$handle => 'an answer']]);
        $second = mutationDeliverySave($form, ['fields' => [$handle => 'an answer']]);
    }, [], isPublic: $isPublic);

    expect($first->getFieldValue($handle))->toBe('an answer')
        ->and($first->statusId)->toBe($form->getDefaultStatus()->id)
        ->and($second->id)->not->toBe($first->id);
})->with(['status', 'operationId', 'requestToken'])->with(['private' => false, 'public' => true]);

it('doesn\'t tell a public caller that a submission was caught as spam', function (bool $isPublic): void {
    $form = mutationDeliveryForm();
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'contains blocked-keyword content']]);
    }, [], ['spamKeywords' => 'blocked-keyword', 'spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS, 'saveSpam' => true, 'spamEmailNotifications' => false, 'enableSuspiciousTextDetection' => false], $isPublic);

    expect($saved->isSpam)->toBe(!$isPublic)
        ->and((bool)$saved->spamReason)->toBe(!$isPublic)
        ->and((bool)(new Query())->select('isSpam')->from('{{%formie_submissions}}')->where(['id' => $saved->id])->scalar())->toBeTrue();
})->with(['private' => false, 'public' => true]);

it('refuses public submissions that aren\'t POST requests', function (): void {
    $form = mutationDeliveryForm();
    WebRequestTestHelper::withWebRequestContext(function() use ($form): void {
        withSaveSubmissionGraphqlScope(['formieSubmissions.' . $form->uid . ':create'], function() use ($form): void {
            expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'From a link']]))->toThrow(Error::class, 'POST');
        }, true);
    }, ['method' => 'GET']);

    expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(0);
});

it('only changes the sub-values an edit sends', function (): void {
    $form = formie()->form(['title' => 'Mutation partial ' . uniqid()])->settings(['disableCaptchas' => true])
        ->nameField('person', ['useMultipleFields' => true])
        ->create();
    $saved = null;
    $edited = null;
    withMutationDelivery($form, function() use ($form, &$saved, &$edited): void {
        $saved = mutationDeliverySave($form, ['fields' => ['person' => ['firstName' => 'Ada', 'lastName' => 'Lovelace']]]);
        $edited = mutationDeliverySave($form, ['id' => $saved->id, 'expectedVersion' => $saved->stateVersion, 'fields' => ['person' => ['firstName' => 'Grace']]]);
    }, []);

    expect($edited->getFieldValue('person')->firstName)->toBe('Grace')
        ->and($edited->getFieldValue('person')->lastName)->toBe('Lovelace');
});

it('enforces limits and fires the completion event when a mutation completes a draft', function (): void {
    $form = mutationDeliveryForm(['disableCaptchas' => true, 'limitSubmissions' => true, 'limitSubmissionsNumber' => 1, 'limitSubmissionsType' => 'total']);
    $draft = null;
    $completed = 0;
    $listener = static function() use (&$completed): void { $completed++; };
    Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, $listener);

    try {
        withMutationDelivery($form, function() use ($form, &$draft): void {
            $draft = mutationDeliverySave($form, ['isIncomplete' => true, 'fields' => ['message' => 'Draft']]);
            $finished = mutationDeliverySave($form, ['id' => $draft->id, 'expectedVersion' => $draft->stateVersion, 'isIncomplete' => false, 'fields' => ['message' => 'Done']]);
            expect($finished->isIncomplete)->toBeFalse();
            $other = mutationDeliverySave($form, ['isIncomplete' => true, 'fields' => ['message' => 'Second draft']]);
            expect(fn() => mutationDeliverySave($form, ['id' => $other->id, 'expectedVersion' => $other->stateVersion, 'isIncomplete' => false, 'fields' => ['message' => 'Over the limit']]))
                ->toThrow(Error::class, 'allowed submissions');
        }, []);
    } finally {
        Event::off(Submission::class, Submission::EVENT_AFTER_COMPLETE, $listener);
    }

    expect($completed)->toBe(1);
});

it('lets a private token correct a submission on a login-only or closed form', function (array $settings): void {
    $form = mutationDeliveryForm(['disableCaptchas' => true]);
    $created = formie()->submission($form)->with(['message' => 'Original'])->save();
    $form->settings->setAttributes($settings, false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $user = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(null);
    $edited = null;

    try {
        withMutationDelivery($form, function() use ($form, $created, &$edited): void {
            $edited = mutationDeliverySave($form, ['id' => $created->id, 'expectedVersion' => $created->stateVersion, 'fields' => ['message' => 'Corrected']]);
        }, []);
    } finally {
        Craft::$app->getUser()->setIdentity($user);
    }

    expect($edited->getFieldValue('message'))->toBe('Corrected');
})->with([
    'login only' => [['requireUser' => true]],
    'closed' => [['scheduleForm' => true, 'scheduleFormStart' => new DateTime('+1 year')]],
]);

it('only stores a request IP when the form collects one and it is the visitor\'s', function (bool $collect, bool $isPublic, bool $stored): void {
    $form = mutationDeliveryForm(['disableCaptchas' => true, 'collectIp' => $collect]);
    $saved = null;
    withMutationDelivery($form, function() use ($form, &$saved): void {
        $saved = mutationDeliverySave($form, ['fields' => ['message' => 'Hello']]);
    }, [], isPublic: $isPublic);

    $metadata = json_decode((string)(new Query())->select('metadata')->from('{{%formie_submissions}}')->where(['id' => $saved->id])->scalar(), true);
    $metadata = is_string($metadata) ? json_decode($metadata, true) : $metadata;
    expect(!empty($metadata['request']['ipAddress']))->toBe($stored);
})->with([
    'public, collecting' => [true, true, true],
    'public, not collecting' => [false, true, false],
    'private, collecting' => [true, false, false],
]);

it('accepts a Question captcha answer through a mutation', function (string $answer, bool $spam): void {
    $form = mutationDeliveryForm(settings: []);
    $saved = null;
    withMutationDelivery($form, function() use ($form, $answer, &$saved): void {
        $saved = mutationDeliverySave($form, ['captchas' => ['questionCaptcha' => ['name' => 'formieCaptchaQuestion', 'value' => json_encode(['index' => 0, 'answer' => $answer])]], 'fields' => ['message' => 'Hello']]);
    }, [MutationQuestionCaptcha::class => 'question'], ['spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS, 'saveSpam' => true, 'spamEmailNotifications' => false]);

    expect((bool)(new Query())->select('isSpam')->from('{{%formie_submissions}}')->where(['id' => $saved->id])->scalar())->toBe($spam);
})->with([
    'right answer' => ['blue', false],
    'wrong answer' => ['red', true],
]);

it('refuses payment forms through a public schema without contacting the provider', function (): void {
    $form = mutationPaymentForm();
    withMutationDelivery($form, function() use ($form): void {
        expect(fn() => mutationDeliverySave($form, ['fields' => ['message' => 'Paying']]))->toThrow(Error::class, 'submitFormieClientForm');
    }, [], isPublic: true);

    expect(MutationPaymentProvider::$requests)->toBe(0)
        ->and((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(0);
});
