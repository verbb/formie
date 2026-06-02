<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\client\models\SessionRefreshRequest;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\helpers\Gql as GqlHelper;

use GraphQL\Error\Error;
use yii\web\NotFoundHttpException;

class ClientFormResolver
{
    // Static Methods
    // =========================================================================

    public static function resolveForm(mixed $source, array $arguments): array
    {
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
        ]));

        return $bootstrap->toArrayRecursive();
    }

    public static function refreshSession(mixed $source, array $arguments): array
    {
        $payload = $arguments['input'];
        $form = Formie::$plugin->getSubmissionProcessor()->requireFormByHandle(
            (string)($payload['handle'] ?? ''),
            isset($payload['siteId']) ? (int)$payload['siteId'] : null
        );

        if (!GqlHelper::canReadForm($form) || !GqlHelper::canMutateSubmissionsForForm($form)) {
            throw new Error('Unable to perform the action.');
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
        $payload = $arguments['input'];
        $form = Formie::$plugin->getSubmissionProcessor()->requireFormByHandle(
            (string)($payload['handle'] ?? ''),
            isset($payload['siteId']) ? (int)$payload['siteId'] : null
        );

        if (!GqlHelper::canReadForm($form) || !GqlHelper::canMutateSubmissionsForForm($form)) {
            throw new Error('Unable to perform the action.');
        }

        $session = Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
            'handle' => (string)($payload['handle'] ?? ''),
            'siteId' => isset($payload['siteId']) ? (int)$payload['siteId'] : null,
            'currentPageId' => $payload['currentPageId'] ?? null,
            'targetPageId' => $payload['targetPageId'] ?? null,
            'session' => (array)($payload['session'] ?? []),
            'values' => (array)($payload['values'] ?? []),
        ]), true);

        return $session->toArrayRecursive();
    }

    public static function submitForm(mixed $source, array $arguments): array
    {
        $payload = $arguments['input'];
        $form = Formie::$plugin->getSubmissionProcessor()->requireFormByHandle(
            (string)($payload['handle'] ?? ''),
            isset($payload['siteId']) ? (int)$payload['siteId'] : null
        );

        if (!GqlHelper::canReadForm($form) || !GqlHelper::canMutateSubmissionsForForm($form)) {
            throw new Error('Unable to perform the action.');
        }

        $result = Formie::$plugin->getSubmissionProcessor()->execute(new SubmitRequest([
            'handle' => (string)($payload['handle'] ?? ''),
            'action' => (string)($payload['action'] ?? 'submit'),
            'siteId' => isset($payload['siteId']) ? (int)$payload['siteId'] : null,
            'session' => (array)($payload['session'] ?? []),
            'values' => (array)($payload['values'] ?? []),
        ]));

        return $result->toArrayRecursive();
    }
}
