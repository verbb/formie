<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\client\models\SessionRefreshRequest;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\client\models\SubmitResult;
use verbb\formie\elements\Form;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\helpers\CrossOriginRequestHelper;
use verbb\formie\helpers\Gql as GqlHelper;

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

        $session = Formie::$plugin->getClientSessionService()->refreshSession(new SessionRefreshRequest([
            'handle' => (string)($payload['handle'] ?? ''),
            'siteId' => isset($payload['siteId']) ? (int)$payload['siteId'] : null,
            'session' => (array)($payload['session'] ?? []),
        ]), true);

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
            return SubmitResult::rejection(403)->toArrayRecursive();
        }

        if (!Craft::$app->getRequest()->getIsPost()) {
            throw new MethodNotAllowedHttpException('POST request required.');
        }

        if (Craft::$app->getRequest()->getHeaders()->get('X-Formie-Profile', 'same-origin-browser') === 'same-origin-browser'
            && !Craft::$app->getRequest()->validateCsrfToken($payload['session']['tokens']['csrf']['value'] ?? null)) {
            throw new BadRequestHttpException('Unable to verify your data submission.');
        }

        $session = Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
            'handle' => (string)($payload['handle'] ?? ''),
            'siteId' => isset($payload['siteId']) ? (int)$payload['siteId'] : null,
            'currentPageId' => $payload['currentPageId'] ?? null,
            'targetPageId' => $payload['targetPageId'] ?? null,
            'session' => (array)($payload['session'] ?? []),
            'operationId' => $payload['operationId'] ?? null,
            'values' => (array)($payload['values'] ?? []),
        ]), true);

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
            return SubmitResult::rejection(403)->toArrayRecursive();
        }

        if (!Craft::$app->getRequest()->getIsPost()) {
            throw new MethodNotAllowedHttpException('POST request required.');
        }

        if (Craft::$app->getRequest()->getHeaders()->get('X-Formie-Profile', 'same-origin-browser') === 'same-origin-browser'
            && !Craft::$app->getRequest()->validateCsrfToken($payload['session']['tokens']['csrf']['value'] ?? null)) {
            throw new BadRequestHttpException('Unable to verify your data submission.');
        }

        $result = Formie::$plugin->getSubmissionRequests()->execute(new SubmitRequest([
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
            $scope = Formie::$plugin->getSubmissionRequests()->getRequiredClientMutationScope(
                $form,
                (array)($payload['session'] ?? []),
                $action,
            );
        } catch (ForbiddenHttpException) {
            return false;
        }

        return $scope === 'save'
            ? GqlHelper::canSaveSubmissionsForForm($form)
            : GqlHelper::canCreateSubmissionsForForm($form);
    }
}
