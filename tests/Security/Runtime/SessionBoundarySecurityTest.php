<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\client\FormsController as ClientFormsController;
use verbb\formie\models\Settings;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\client\models\SessionRefreshRequest;
use verbb\formie\state\DraftSubmissionState;
use verbb\formie\services\SubmissionWorkflow;
use craft\helpers\UrlHelper;
use yii\web\MethodNotAllowedHttpException;
use yii\web\TooManyRequestsHttpException;

it('binds draft context tokens to the issuing form', function (): void {
    $formA = formie()
        ->form(['title' => 'Draft Context Form A'])
        ->singleLineTextField('fullName')
        ->create();

    $formB = formie()
        ->form(['title' => 'Draft Context Form B'])
        ->singleLineTextField('fullName')
        ->create();

    $formA->setDraftContext('ctx:security:a');
    $token = $formA->getDraftContextToken();

    expect($token)->not->toBeNull()
        ->and($formA->resolveDraftContextToken($token))->toBe('ctx:security:a')
        ->and($formB->resolveDraftContextToken($token))->toBeNull();
})->group('security');

it('enforces resume token capabilities without allowing escalation', function (): void {
    $form = formie()
        ->form(['title' => 'Resume Capability Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionDrafts = new SubmissionDrafts();
    $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'security-resume-capability',
        'instance' => 'read-only',
    ]);
    $state = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['fullName' => 'Security Tester'],
        'snapshot' => [],
        'version' => 1,
    ]);

    $submissionDrafts->saveDraftState($state);
    $token = $submissionDrafts->issueResumeToken($state, [SubmissionDrafts::RESUME_CAPABILITY_READ]);

    expect($submissionDrafts->verifyResumeToken($token->token, [SubmissionDrafts::RESUME_CAPABILITY_READ]))->not->toBeNull()
        ->and($submissionDrafts->verifyResumeToken($token->token, [SubmissionDrafts::RESUME_CAPABILITY_UPDATE]))->toBeNull();
})->group('security');

it('rejects revoked resume tokens', function (): void {
    $form = formie()
        ->form(['title' => 'Resume Revocation Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionDrafts = new SubmissionDrafts();
    $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'security-resume-revoke',
        'instance' => 'revoke',
    ]);
    $state = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['fullName' => 'Security Tester'],
        'snapshot' => [],
        'version' => 1,
    ]);

    $submissionDrafts->saveDraftState($state);
    $token = $submissionDrafts->issueResumeToken($state);

    expect($submissionDrafts->revokeResumeToken($token->token))->toBeTrue()
        ->and($submissionDrafts->verifyResumeToken($token->token, [SubmissionDrafts::RESUME_CAPABILITY_READ]))->toBeNull();
})->group('security');

it('invalidates resume tokens when their draft state is deleted', function (): void {
    $form = formie()
        ->form(['title' => 'Resume Delete Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionDrafts = new SubmissionDrafts();
    $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'security-resume-delete',
        'instance' => 'delete',
    ]);
    $state = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['fullName' => 'Security Tester'],
        'snapshot' => [],
        'version' => 1,
    ]);

    $savedState = $submissionDrafts->saveDraftState($state);
    $token = $submissionDrafts->issueResumeToken($savedState);
    $submissionDrafts->deleteDraftState($savedState);

    expect($submissionDrafts->verifyResumeToken($token->token, [SubmissionDrafts::RESUME_CAPABILITY_READ]))->toBeNull();
})->group('security');

it('reissues refresh-session tokens instead of trusting attacker supplied token blobs', function (): void {
    $form = formie()
        ->form(['title' => 'Refresh Session Security'])
        ->singleLineTextField('fullName')
        ->create();

    $pageId = (string)($form->getCurrentPage()?->id ?? '');

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $pageId): void {
        $session = Formie::$plugin->getClientSessionService()->refreshSession(new SessionRefreshRequest([
            'handle' => (string)$form->handle,
            'siteId' => (int)$form->siteId,
            'session' => [
                'currentPageId' => $pageId,
                'tokens' => [
                    'request' => 'attacker-request-token',
                    'render' => 'attacker-render-id',
                    'csrf' => [
                        'name' => 'CRAFT_CSRF_TOKEN',
                        'value' => 'attacker-csrf-token',
                    ],
                ],
            ],
        ]));

        expect($session->currentPageId)->toBe($pageId)
            ->and($session->tokens['request'])->not->toBe('attacker-request-token')
            ->and($session->tokens['render'])->not->toBe('attacker-render-id')
            ->and($session->tokens['csrf']['value'] ?? null)->not->toBe('attacker-csrf-token');
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('only allows same-origin bases for save-resume capability urls', function (): void {
    $processor = Formie::$plugin->getSubmissionProcessor();
    $trustedUrl = UrlHelper::siteUrl('contact', ['foo' => 'bar']);
    $fallbackUrl = UrlHelper::siteUrl('contact');

    expect($processor->resolveTrustedResumeBaseUrl($trustedUrl, 'contact'))
        ->toBe($trustedUrl)
        ->and($processor->resolveTrustedResumeBaseUrl('https://evil.example.com/phish', 'contact'))
        ->toBe($fallbackUrl);
})->group('security');

it('requires post requests for runtime html page transitions', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime HTML Set Page Security'])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('firstName')
        ->onPage(2)
        ->singleLineTextField('lastName')
        ->create();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setQueryParams([
            'handle' => (string)$form->handle,
            'pageId' => (string)$form->getPages()[1]->id,
        ]);

        $controller = new ClientFormsController('formie-client-forms-security', Craft::$app);

        expect(fn() => $controller->actionPage())
            ->toThrow(MethodNotAllowedHttpException::class);
    }, [
        'method' => 'GET',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('requires post requests for runtime rest form bootstrap', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime REST Load Security'])
        ->singleLineTextField('firstName')
        ->create();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setQueryParams([
            'handle' => (string)$form->handle,
        ]);

        $controller = new ClientFormsController('formie-client-forms-security', Craft::$app);

        expect(fn() => $controller->actionLoad())
            ->toThrow(MethodNotAllowedHttpException::class);
    }, [
        'method' => 'GET',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('rate limits anonymous runtime bootstrap session minting', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Bootstrap Rate Limit'])
        ->singleLineTextField('firstName')
        ->create();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalBootstrapLimit = $settings->anonymousClientBootstrapRateLimit;
    $originalWindow = $settings->anonymousClientRateWindowSeconds;

    try {
        $settings->anonymousClientBootstrapRateLimit = 1;
        $settings->anonymousClientRateWindowSeconds = 60;

        WebRequestTestHelper::withWebRequestContext(function ($request, $response) use ($form): void {
            Formie::$plugin->getClientSessionService()->issueInitialSession($form, null, true);

            expect(fn() => Formie::$plugin->getClientSessionService()->issueInitialSession($form, null, true))
                ->toThrow(TooManyRequestsHttpException::class);

            expect((int)$response->getHeaders()->get('Retry-After'))->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(60);
        }, [
            'method' => 'POST',
            'remoteAddr' => '198.51.100.10',
            'headers' => [
                'User-Agent' => 'Phase4SecurityBootstrap/1.0',
            ],
        ]);
    } finally {
        $settings->anonymousClientBootstrapRateLimit = $originalBootstrapLimit;
        $settings->anonymousClientRateWindowSeconds = $originalWindow;
    }
})->group('security');

it('shares refresh abuse limits across token refresh and session refresh endpoints', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Refresh Rate Limit'])
        ->singleLineTextField('firstName')
        ->create();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalRefreshLimit = $settings->anonymousClientRefreshRateLimit;
    $originalWindow = $settings->anonymousClientRateWindowSeconds;

    try {
        $settings->anonymousClientRefreshRateLimit = 1;
        $settings->anonymousClientRateWindowSeconds = 60;

        WebRequestTestHelper::withWebRequestContext(function ($request, $response) use ($form): void {
            Formie::$plugin->getClientSessionService()->buildTokenPayload($form, true);

            expect(fn() => Formie::$plugin->getClientSessionService()->refreshSession(new SessionRefreshRequest([
                'handle' => (string)$form->handle,
                'siteId' => (int)$form->siteId,
                'session' => [],
            ]), true))->toThrow(TooManyRequestsHttpException::class);

            expect((int)$response->getHeaders()->get('Retry-After'))->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(60);
        }, [
            'method' => 'POST',
            'remoteAddr' => '198.51.100.11',
            'headers' => [
                'User-Agent' => 'Phase4SecurityRefresh/1.0',
            ],
        ]);
    } finally {
        $settings->anonymousClientRefreshRateLimit = $originalRefreshLimit;
        $settings->anonymousClientRateWindowSeconds = $originalWindow;
    }
})->group('security');

it('shares refresh abuse limits across token refresh and page transitions', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Page Transition Rate Limit'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('firstName')
        ->onPage(2)->singleLineTextField('emailAddress')
        ->create();
    $pages = $form->getPages();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalRefreshLimit = $settings->anonymousClientRefreshRateLimit;
    $originalWindow = $settings->anonymousClientRateWindowSeconds;

    try {
        $settings->anonymousClientRefreshRateLimit = 1;
        $settings->anonymousClientRateWindowSeconds = 60;

        WebRequestTestHelper::withWebRequestContext(function ($request, $response) use ($form, $pages): void {
            $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive();
            Formie::$plugin->getClientSessionService()->buildTokenPayload($form, true);

            expect(fn() => Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
                'handle' => (string)$form->handle,
                'siteId' => (int)$form->siteId,
                'targetPageId' => (string)$pages[1]->id,
                'session' => $session,
                'values' => [],
            ]), true))->toThrow(TooManyRequestsHttpException::class);

            expect((int)$response->getHeaders()->get('Retry-After'))->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(60);
        }, [
            'method' => 'POST',
            'remoteAddr' => '198.51.100.12',
            'headers' => [
                'User-Agent' => 'Phase4SecurityPage/1.0',
            ],
        ]);
    } finally {
        $settings->anonymousClientRefreshRateLimit = $originalRefreshLimit;
        $settings->anonymousClientRateWindowSeconds = $originalWindow;
    }
})->group('security');

it('rate limits anonymous runtime submit requests with the shared refresh abuse budget', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Submit Rate Limit'])
        ->singleLineTextField('firstName')
        ->create();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalRefreshLimit = $settings->anonymousClientRefreshRateLimit;
    $originalWindow = $settings->anonymousClientRateWindowSeconds;

    try {
        $settings->anonymousClientRefreshRateLimit = 1;
        $settings->anonymousClientRateWindowSeconds = 60;

        WebRequestTestHelper::withWebRequestContext(function ($request, $response) use ($form): void {
            Formie::$plugin->getClientSessionService()->buildTokenPayload($form, true);

            expect(fn() => Formie::$plugin->getSubmissionProcessor()->execute(new SubmitRequest([
                'handle' => (string)$form->handle,
                'siteId' => (int)$form->siteId,
                'session' => Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive(),
                'values' => [
                    'firstName' => 'Security Tester',
                ],
            ])))->toThrow(TooManyRequestsHttpException::class);

            expect((int)$response->getHeaders()->get('Retry-After'))->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(60);
        }, [
            'method' => 'POST',
            'remoteAddr' => '198.51.100.13',
            'headers' => [
                'User-Agent' => 'Phase4SecuritySubmit/1.0',
            ],
        ]);
    } finally {
        $settings->anonymousClientRefreshRateLimit = $originalRefreshLimit;
        $settings->anonymousClientRateWindowSeconds = $originalWindow;
    }
})->group('security');

it('clears conditionally hidden field values submitted through the client runtime path', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        $form = formie()->conditionForms()->optionsValueVisibility([
            'title' => 'Client Conditional Tamper Security',
        ]);
        $submission = new \verbb\formie\elements\Submission();
        $submission->setForm($form);
        $submission->setFieldValues([
            'enquiryType' => 'general',
            'otherReason' => 'tampered hidden content',
        ]);
        $request = new \verbb\formie\models\SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'siteId' => (int)$form->siteId,
            'clearConditionallyHiddenFields' => true,
        ]);

        $response = Formie::$plugin->getSubmissionWorkflow()->processSubmissionRequest($request);

        $savedSubmission = \verbb\formie\elements\Submission::find()
            ->id((int)$submission->id)
            ->status(null)
            ->one();

        expect($response->success)->toBeTrue()
            ->and($savedSubmission)->not->toBeNull()
            ->and($savedSubmission->getFieldValue('otherReason'))->toBeNull();
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.14',
    ]);
})->group('security');
