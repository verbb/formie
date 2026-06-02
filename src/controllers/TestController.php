<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Settings;
use verbb\formie\services\SubmissionWorkflow;

use craft\helpers\App;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

class TestController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = [
        'submit' => self::ALLOW_ANONYMOUS_LIVE,
        'query-submissions' => self::ALLOW_ANONYMOUS_LIVE,
    ];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['submit', 'query-submissions'], true) && $this->_isTestEndpointEnabled()) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionSubmit(): ?Response
    {
        $this->requirePostRequest();
        $this->_enforceTestEndpointAccess();

        $this->_applyRequestOverridesForTesting();

        $submissionsController = new SubmissionsController('submissions', Formie::$plugin);
        $submissionsController->setAllowTestOverrides(true);

        return $submissionsController->processSubmissionRequest(SubmissionWorkflow::PROCESS_MODE_SUBMIT);
    }

    public function actionQuerySubmissions(): Response
    {
        $this->requirePostRequest();
        $this->_enforceTestEndpointAccess();

        $formHandle = $this->request->getBodyParam('handle');
        $fieldHandle = $this->request->getBodyParam('fieldHandle');
        $value = $this->request->getBodyParam('value');
        $limit = (int)$this->request->getBodyParam('limit', 50);

        if (!is_string($fieldHandle) || $fieldHandle === '') {
            return $this->asJson([
                'success' => false,
                'errorMessage' => 'Missing `fieldHandle`.',
            ]);
        }

        $limit = max(1, min(500, $limit));

        $query = Submission::find()->limit($limit);

        if (is_string($formHandle) && $formHandle !== '') {
            $query->form($formHandle);
        }

        $query->{$fieldHandle}($value);

        return $this->asJson([
            'success' => true,
            'ids' => $query->ids(),
            'total' => $query->count(),
        ]);
    }

    // Private Methods
    // =========================================================================

    private function _isTestEndpointEnabled(): bool
    {
        return $this->_isTestRuntime() && (bool)App::parseBooleanEnv(App::env('FORMIE_ENABLE_TEST_ENDPOINTS') ?: false);
    }

    private function _enforceTestEndpointAccess(): void
    {
        if (!$this->_isTestRuntime()) {
            throw new ForbiddenHttpException('Test endpoint is only available in the test environment.');
        }

        if (!$this->_isTestEndpointEnabled()) {
            throw new ForbiddenHttpException('Test endpoint is disabled.');
        }

        $expectedKey = App::env('FORMIE_TEST_ENDPOINT_KEY');

        if (!is_string($expectedKey) || trim($expectedKey) === '') {
            throw new ForbiddenHttpException('Test endpoint key is not configured.');
        }

        $providedKey = $this->request->getHeaders()->get('X-Formie-Test-Key');

        if (!is_string($providedKey) || !hash_equals(trim($expectedKey), trim($providedKey))) {
            throw new ForbiddenHttpException('Invalid test endpoint key.');
        }
    }

    private function _isTestRuntime(): bool
    {
        return (getenv('ENVIRONMENT') ?: '') === 'testing';
    }

    private function _applyRequestOverridesForTesting(): void
    {
        $settings = Formie::$plugin->getSettings();

        $spamKeywords = $this->request->getBodyParam('__formieSpamKeywords');
        if (is_string($spamKeywords)) {
            $settings->spamKeywords = $spamKeywords;
        }

        $spamBehaviour = $this->request->getBodyParam('__formieSpamBehaviour');
        if (is_string($spamBehaviour) && in_array($spamBehaviour, [Settings::SPAM_BEHAVIOUR_SUCCESS, Settings::SPAM_BEHAVIOUR_MESSAGE], true)) {
            $settings->spamBehaviour = $spamBehaviour;
        }
    }
}
