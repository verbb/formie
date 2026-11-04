<?php

declare(strict_types=1);

use craft\db\Query;
use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;
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

class MutationDeliveryCaptcha extends Captcha
{
    public static function displayName(): string { return 'Mutation captcha fixture'; }
    public function fetchConfig(): IntegrationConfig { return new IntegrationConfig(); }
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

function mutationDeliverySave(Form $form, array $arguments): ?Submission
{
    $info = test()->createMock(ResolveInfo::class);
    $info->fieldDefinition = FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    return Craft::createObject(SubmissionResolver::class)->saveSubmissionByHandle(null, ['formHandle' => (string)$form->handle] + $arguments, null, $info);
}

/** Runs real mutations in a web request, recording emails and integration deliveries without sending them. */
function withMutationDelivery(Form $form, callable $callback, array $integrations = [MutationDeliveryProvider::class => 'remote'], array $settings = []): array
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
        ], $callback), ['method' => 'POST']);

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
    }, []);

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

it('requires a valid captcha token from completing mutations', function (int $pages, array $captchas, bool $spam): void {
    $form = mutationDeliveryForm(settings: [], pages: $pages);
    $saved = null;
    $delivered = withMutationDelivery($form, function() use ($form, $captchas, &$saved): void {
        $saved = mutationDeliverySave($form, ['captchas' => $captchas, 'fields' => ['message' => 'Hello']]);
    }, [MutationDeliveryProvider::class => 'remote', MutationDeliveryCaptcha::class => 'token'], ['spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS, 'saveSpam' => true, 'spamEmailNotifications' => false]);

    expect($saved->isSpam)->toBe($spam)
        ->and($delivered)->toBe($spam ? ['emails' => [], 'integrations' => []] : ['emails' => ['Enquiry'], 'integrations' => ['remote']]);
})->with([
    'missing token' => [1, [], true],
    'wrong token' => [1, ['tokenCaptcha' => ['name' => 'token', 'value' => 'bot']], true],
    'valid token' => [1, ['tokenCaptcha' => ['name' => 'token', 'value' => 'human']], false],
    'missing token, multi-page' => [2, [], true],
    'valid token, multi-page' => [2, ['tokenCaptcha' => ['name' => 'token', 'value' => 'human']], false],
]);

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

function withSaveSubmissionGraphqlScope(array $scope, callable $callback): mixed
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
