<?php

declare(strict_types=1);

use craft\elements\User;
use craft\services\UserPermissions;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\Formie;
use verbb\formie\integrations\payments\GoCardless;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Subscription;
use verbb\formie\models\SubscriptionPlan;
use verbb\formie\services\Permissions;

function subscriptionSummaryFixture(string $provider): array
{
    $integration = $provider === 'stripe'
        ? new Stripe(['name' => 'Security Stripe', 'handle' => 'securityStripe' . bin2hex(random_bytes(6))])
        : new GoCardless(['name' => 'Security GoCardless', 'handle' => 'securityGoCardless' . bin2hex(random_bytes(6))]);

    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $form = formie()
        ->form(['title' => 'Subscription Summary Authorization'])
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => $integration::class,
        ])
        ->create();
    $submission = formie()->submission($form)->save();
    $field = $form->getFieldByHandle('payment');
    $planId = null;

    if ($integration instanceof Stripe) {
        $plan = new SubscriptionPlan([
            'integrationId' => $integration->id,
            'name' => 'Security Plan',
            'handle' => 'securityPlan' . bin2hex(random_bytes(6)),
            'reference' => 'price_' . bin2hex(random_bytes(6)),
            'enabled' => true,
            'isArchived' => false,
            'amountMinor' => '1000',
            'currency' => 'AUD',
            'interval' => 'month',
            'intervalCount' => 1,
            'planData' => ['amount' => 1000, 'currency' => 'AUD'],
        ]);
        expect(Formie::$plugin->getPlans()->savePlan($plan, false))->toBeTrue();
        $planId = $plan->id;
    }

    $subscription = new Subscription([
        'integrationId' => $integration->id,
        'submissionId' => $submission->id,
        'fieldId' => $field->id,
        'planId' => $planId,
        'reference' => 'subscription_' . bin2hex(random_bytes(6)),
        'status' => SubscriptionStatus::ACTIVE,
    ]);
    expect(Formie::$plugin->getSubscriptions()->saveSubscription($subscription, false))->toBeTrue();

    return compact('integration', 'form', 'submission', 'field', 'subscription');
}

function subscriptionSummaryUser(string $prefix, array $permissions): User
{
    $username = $prefix . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions))->toBeTrue();

    return $user;
}

function renderSubscriptionSummary(Payment $integration, Submission $submission, PaymentField $field): string
{
    return (string)$integration->getSubmissionSummaryHtml($submission, $field);
}

it('only exposes control panel subscription cancellation links to submission managers', function (): void {
    $fixture = subscriptionSummaryFixture('stripe');
    Craft::$app->set('userPermissions', new UserPermissions());
    $permissions = Formie::$plugin->getPermissions();
    $formScope = $permissions->groupScope($permissions->getFormGroupHandle($fixture['form']));
    $basePermissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_SUBMISSIONS,
    ];
    $viewUser = subscriptionSummaryUser('viewSubscription', [
        ...$basePermissions,
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $formScope),
    ]);
    $saveUser = subscriptionSummaryUser('saveSubscription', [
        ...$basePermissions,
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $formScope),
        $permissions->scopedPermission(Permissions::PERM_SAVE_SUBMISSIONS, $formScope),
    ]);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($fixture, $viewUser, $saveUser): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($viewUser->id)->status(null)->one());

        $viewHtml = renderSubscriptionSummary($fixture['integration'], $fixture['submission'], $fixture['field']);
        expect($viewHtml)
            ->toContain('Subscription')
            ->not->toContain('payment-subscriptions/cancel');

        Craft::$app->getUser()->setIdentity(User::find()->id($saveUser->id)->status(null)->one());
        $saveHtml = renderSubscriptionSummary($fixture['integration'], $fixture['submission'], $fixture['field']);
        expect($saveHtml)->toContain('payment-subscriptions/cancel');

        $request->setIsCpRequest(false);
        Craft::$app->getUser()->setIdentity(User::find()->id($viewUser->id)->status(null)->one());
        $siteHtml = renderSubscriptionSummary($fixture['integration'], $fixture['submission'], $fixture['field']);
        expect($siteHtml)->toContain('payment-subscriptions/cancel');
    });
})->group('security');

it('applies the same subscription cancellation guard to GoCardless summaries', function (): void {
    $fixture = subscriptionSummaryFixture('go-cardless');
    $variables = [
        'integration' => $fixture['integration'],
        'payments' => [],
        'monetaryPayments' => [],
        'subscriptions' => [$fixture['subscription']],
        'canManagePayments' => false,
    ];
    $viewHtml = Craft::$app->getView()->renderTemplate('formie/integrations/payments/go-cardless/_submission-summary', $variables);
    expect($viewHtml)
        ->toContain('Subscription')
        ->not->toContain('payment-subscriptions/cancel');

    $variables['canManagePayments'] = true;
    $saveHtml = Craft::$app->getView()->renderTemplate('formie/integrations/payments/go-cardless/_submission-summary', $variables);
    expect($saveHtml)->toContain('payment-subscriptions/cancel');
})->group('security');
