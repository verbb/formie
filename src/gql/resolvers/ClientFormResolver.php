<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\elements\Form;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\helpers\CrossOriginRequestHelper;
use verbb\formie\helpers\Gql as GqlHelper;
use verbb\formie\models\FormSubmitRequest;
use verbb\formie\models\FormSubmitResult;
use verbb\formie\models\PageTransitionRequest;
use verbb\formie\models\SessionRefreshRequest;

use Craft;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

use GraphQL\Error\Error;

class ClientFormResolver
{
    // Static Methods
    // =========================================================================

    public static function resolveForm(mixed $source, array $arguments): array
    {
        BrowserRequestProfile::enter(true);
        CrossOriginRequestHelper::applyHeaders(Craft::$app->getRequest(), Craft::$app->getResponse());
        $form = GqlHelper::findReadableFormByHandle(
            (string)($arguments['handle'] ?? ''),
            isset($arguments['siteId']) ? (int)$arguments['siteId'] : null
        );

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
            'handle' => (string)($arguments['handle'] ?? ''),
            'siteId' => isset($arguments['siteId']) ? (int)$arguments['siteId'] : null,
            'locale' => $arguments['locale'] ?? null,
            'query' => (array)($arguments['query'] ?? []),
            'grantToken' => ($arguments['grantToken'] ?? null),
            'grantPurpose' => (string)($arguments['grantPurpose'] ?? 'continue-incomplete'),
            'draftContext' => ($arguments['draftContext'] ?? null),
        ]));

        return $bootstrap->toArrayRecursive();
    }

    public static function refreshSession(mixed $source, array $arguments): array
    {
        BrowserRequestProfile::enter(false);
        CrossOriginRequestHelper::applyHeaders(Craft::$app->getRequest(), Craft::$app->getResponse());
        $payload = $arguments['input'];
        $form = Formie::$plugin->getSubmissionRequests()->requireFormByHandle(
            (string)($payload['handle'] ?? ''),
            isset($payload['siteId']) ? (int)$payload['siteId'] : null
        );

        if (!GqlHelper::canReadForm($form) || !self::_canMutateSubmissionsForForm($form, $payload)) {
            throw new Error('Unable to perform the action.');
        }

        if (!Craft::$app->getRequest()->getIsPost()) {
            throw new MethodNotAllowedHttpException('POST request required.');
        }

        if (Craft::$app->getRequest()->getHeaders()->get('X-Formie-Profile', 'same-origin-browser') === 'same-origin-browser'
            && !Craft::$app->getRequest()->validateCsrfToken($payload['session']['tokens']['csrf']['value'] ?? null)) {
            throw new BadRequestHttpException('Unable to verify your data submission.');
        }

        try {
            $session = Formie::$plugin->getFormSessions()->refreshSession(new SessionRefreshRequest([
                'handle' => (string)($payload['handle'] ?? ''),
                'siteId' => isset($payload['siteId']) ? (int)$payload['siteId'] : null,
                'session' => (array)($payload['session'] ?? []),
                'scopeAuthorizer' => self::_authorizeResolvedScope(...),
            ]), true);
        } catch (ForbiddenHttpException) {
            throw new Error('Unable to perform the action.');
        }

        return $session->toArrayRecursive();
    }

    public static function setPage(mixed $source, array $arguments): array
    {
        BrowserRequestProfile::enter(false);
        CrossOriginRequestHelper::applyHeaders(Craft::$app->getRequest(), Craft::$app->getResponse());
        $payload = $arguments['input'];
        $form = Formie::$plugin->getSubmissionRequests()->requireFormByHandle(
            (string)($payload['handle'] ?? ''),
            isset($payload['siteId']) ? (int)$payload['siteId'] : null
        );

        if (!GqlHelper::canReadForm($form) || !self::_canMutateSubmissionsForForm($form, $payload, 'back')) {
            return FormSubmitResult::rejection(403)->toArrayRecursive();
        }

        if (!Craft::$app->getRequest()->getIsPost()) {
            throw new MethodNotAllowedHttpException('POST request required.');
        }

        if (Craft::$app->getRequest()->getHeaders()->get('X-Formie-Profile', 'same-origin-browser') === 'same-origin-browser'
            && !Craft::$app->getRequest()->validateCsrfToken($payload['session']['tokens']['csrf']['value'] ?? null)) {
            throw new BadRequestHttpException('Unable to verify your data submission.');
        }

        $session = Formie::$plugin->getFormSessions()->persistPageState(new PageTransitionRequest([
            'scopeAuthorizer' => self::_authorizeResolvedScope(...),
            'handle' => (string)($payload['handle'] ?? ''),
            'siteId' => isset($payload['siteId']) ? (int)$payload['siteId'] : null,
            'currentPageId' => $payload['currentPageId'] ?? null,
            'targetPageId' => $payload['targetPageId'] ?? null,
            'session' => (array)($payload['session'] ?? []),
            'operationId' => $payload['operationId'] ?? null,
            'values' => (array)($payload['values'] ?? []),
        ]));

        return $session->toArrayRecursive();
    }

    public static function submitForm(mixed $source, array $arguments): array
    {
        BrowserRequestProfile::enter(false);
        CrossOriginRequestHelper::applyHeaders(Craft::$app->getRequest(), Craft::$app->getResponse());
        $payload = $arguments['input'];
        $form = Formie::$plugin->getSubmissionRequests()->requireFormByHandle(
            (string)($payload['handle'] ?? ''),
            isset($payload['siteId']) ? (int)$payload['siteId'] : null
        );

        if (!GqlHelper::canReadForm($form) || !self::_canMutateSubmissionsForForm($form, $payload, (string)($payload['action'] ?? 'submit'))) {
            return FormSubmitResult::rejection(403)->toArrayRecursive();
        }

        if (!Craft::$app->getRequest()->getIsPost()) {
            throw new MethodNotAllowedHttpException('POST request required.');
        }

        if (Craft::$app->getRequest()->getHeaders()->get('X-Formie-Profile', 'same-origin-browser') === 'same-origin-browser'
            && !Craft::$app->getRequest()->validateCsrfToken($payload['session']['tokens']['csrf']['value'] ?? null)) {
            throw new BadRequestHttpException('Unable to verify your data submission.');
        }

        $result = Formie::$plugin->getSubmissionRequests()->execute(new FormSubmitRequest([
            'scopeAuthorizer' => self::_authorizeResolvedScope(...),
            'handle' => (string)($payload['handle'] ?? ''),
            'action' => (string)($payload['action'] ?? 'submit'),
            'browserData' => (array)($payload['browserData'] ?? []),
            'siteId' => isset($payload['siteId']) ? (int)$payload['siteId'] : null,
            'session' => (array)($payload['session'] ?? []),
            'operationId' => $payload['operationId'] ?? null,
            'values' => (array)($payload['values'] ?? []),
        ]), SubmissionAuthorityType::VISITOR);

        return $result->toArrayRecursive();
    }

    private static function _canMutateSubmissionsForForm(Form $form, array $payload, string $action = 'submit'): bool
    {
        try {
            $scope = Formie::$plugin->getSubmissionRequests()->getRequiredSessionMutationScope(
                $form,
                (array)($payload['session'] ?? []),
                $action,
            );
        } catch (ForbiddenHttpException) {
            return false;
        }

        return self::_authorizeResolvedScope($form, $scope);
    }

    private static function _authorizeResolvedScope(Form $form, string $scope): bool
    {
        return GqlHelper::canReadForm($form) && ($scope === 'save'
            ? GqlHelper::canSaveSubmissionsForForm($form)
            : GqlHelper::canCreateSubmissionsForForm($form));
    }
}
