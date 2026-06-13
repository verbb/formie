<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\queue\BaseJob as CraftBaseJob;

use Exception;

class TriggerIntegration extends CraftBaseJob implements DebuggableJobInterface
{
    use DebuggableJobTrait;

    // Properties
    // =========================================================================

    public ?int $submissionId = null;
    public ?int $integrationId = null;
    public ?string $integrationHandle = null;
    public array $stepHandles = [];
    public string $processMode = SubmissionWorkflow::PROCESS_MODE_SUBMIT;
    public bool $runAfterNotifications = false;
    public ?int $formId = null;
    public ?string $formHandle = null;
    public ?string $formTitle = null;
    public array $integrationContext = [];
    public ?string $triggerEvent = null;
    public bool $operatorInitiated = false;
    public mixed $payload = null;

    public array $integrationData = [];
    public array $referenceMap = [];


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $this->setProgress($queue, 0.25);

        // Allow incomplete submissions
        $submission = Submission::find()
            ->id($this->submissionId)
            ->isIncomplete(null)
            ->status(null)
            ->one();

        $this->setProgress($queue, 0.5);

        if (!$submission) {
            throw new Exception('Unable to find submission: ' . $this->submissionId . '.');
        }

        // Ensure we set the correct language for a potential CLI request
        Craft::$app->language = $submission->getSite()->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($submission->getSite()->language));
        Craft::$app->getSites()->setCurrentSite($submission->getSite());

        if ($this->stepHandles) {
            Formie::$plugin->getIntegrationExecutor()->runQueuedJob(
                $submission,
                $this->stepHandles,
                $this->processMode,
                [
                    'processMode' => $this->processMode,
                    'isSubmissionEdit' => $this->processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
                    'triggerEvent' => $this->triggerEvent ?? IntegrationTriggerEvents::resolveFromProcessMode($this->processMode),
                    'operatorInitiated' => $this->operatorInitiated,
                ],
                $this->runAfterNotifications,
            );

            $this->setProgress($queue, 1);

            return;
        }

        $integration = $this->_resolveIntegration($submission);

        if (!$integration) {
            throw new Exception('Unable to find integration: ' . ($this->integrationId ?: $this->integrationHandle ?: 'unknown') . '.');
        }

        // Always reset submit-time context. Queue workers can be long-lived and
        // the integration service may return a cached instance from an earlier job.
        $integration->context = $this->integrationContext;

        $this->integrationData = $this->_getIntegrationData($integration);
        $this->referenceMap = $this->_getReferenceMap($submission);

        // Pass a reference of this class to the integration, to assist with debugging.
        // Set with a private variable, so it doesn't appear in the queue job data which would be mayhem.
        $integration->setQueueJob($this);

        if (!$integration->shouldTrigger($submission, [
            'triggerEvent' => $this->triggerEvent ?? IntegrationTriggerEvents::SUBMIT,
            'operatorInitiated' => $this->operatorInitiated,
        ])) {
            Integration::info($integration, 'Integration skipped due to conditions not being met.');

            $this->setProgress($queue, 1);

            return;
        }

        $this->setProgress($queue, 0.75);

        $response = Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);

        // Check if some integrations return a response object for more detail
        if (($response instanceof IntegrationResponse) && !$response->success) {
            throw new Exception('Failed to trigger integration: ' . Json::encode($response->message) . '.');
        }

        if (!$response) {
            throw new Exception('Failed to trigger integration. Check the Formie log files.');
        }

        $this->setProgress($queue, 1);
    }
    

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        if ($this->stepHandles) {
            $form = $this->formHandle ?: ($this->formTitle ?: ($this->formId ?? Craft::t('formie', 'unknown')));

            return Craft::t('formie', 'Running integration dispatch for form “{form}”.', [
                'form' => $form,
            ]);
        }

        $integration = $this->integrationHandle ?: ($this->integrationId ?? Craft::t('formie', 'unknown'));
        $form = $this->formHandle ?: ($this->formTitle ?: ($this->formId ?? Craft::t('formie', 'unknown')));

        return Craft::t('formie', 'Triggering “{integration}” integration for form “{form}”.', [
            'integration' => $integration,
            'form' => $form,
        ]);
    }

    protected function updateDebugJobData(mixed $job, mixed $jobData): void
    {
        $payload = $job->payload;

        // For element integrations, add in custom fields with a bit more context
        if ($payload instanceof ElementInterface) {
            $element = $job->payload;
            $payload = Json::decode(Json::encode($payload));

            if ($fieldLayout = $element->getFieldLayout()) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $payload['fields'][] = [
                        'type' => get_class($field),
                        'handle' => $field->handle,
                        'value' => $element->getFieldValue($field->handle),
                    ];
                }
            }
        }

        // Set the payload attribute to be updated
        $jobData->payload = $payload;
        $submission = Submission::find()->id($job->submissionId)->isIncomplete(null)->status(null)->one();
        $jobData->integrationData = $job->integrationData ?: $this->_getIntegrationData($this->_resolveIntegration($submission, $job));

        if ($submission) {
            $jobData->referenceMap = $job->referenceMap ?: $this->_getReferenceMap($submission);
            $jobData->form = $this->_getFormContext($submission, $job);
        }
    }


    // Private Methods
    // =========================================================================

    private function _getFormContext(Submission $submission, self $job): array
    {
        if ($job->formId || $job->formHandle || $job->formTitle) {
            return array_filter([
                'id' => $job->formId,
                'handle' => $job->formHandle,
                'title' => $job->formTitle,
            ], fn($value) => $value !== null && $value !== '');
        }

        $form = $submission->getForm();

        if (!$form) {
            return [];
        }

        return array_filter([
            'id' => $form->id,
            'handle' => $form->handle,
            'title' => $form->title,
        ], fn($value) => $value !== null && $value !== '');
    }

    private function _resolveIntegration(?Submission $submission = null, ?self $job = null): ?Integration
    {
        $job ??= $this;

        if (!$job->integrationId) {
            return null;
        }

        if ($submission && $form = $submission->getForm()) {
            foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form) as $integration) {
                if ((int)$integration->id === (int)$job->integrationId) {
                    return $integration instanceof Integration ? $integration : null;
                }
            }

            return null;
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($job->integrationId);

        return $integration instanceof Integration ? $integration : null;
    }

    private function _getIntegrationData(?Integration $integration): array
    {
        if (!$integration) {
            return [];
        }

        $integrationData = Json::decode(Json::encode($integration->toArray())) ?: [];
        $cache = $integrationData['cache'] ?? [];

        // Keep the debug context compact. The full cache can contain large provider schemas,
        // but the summary is enough to confirm whether cached settings were involved.
        unset($integrationData['cache']);
        unset($integrationData['context']);

        $integrationData['class'] = get_class($integration);
        $integrationData['cacheSummary'] = $this->_getCacheSummary(is_array($cache) ? $cache : []);

        return $this->_redactSensitiveValues($integrationData);
    }

    private function _getCacheSummary(array $cache): array
    {
        $summary = [];

        if (array_key_exists('connection', $cache)) {
            $summary['connection'] = $cache['connection'];
        }

        if (isset($cache['settings']) && is_array($cache['settings'])) {
            $summary['settingsKeys'] = array_keys($cache['settings']);
        }

        return $summary;
    }

    private function _getReferenceMap(Submission $submission): array
    {
        $fields = [];

        foreach ($submission->getFields() as $field) {
            $reference = trim((string)($field->reference ?? ''));

            if ($reference === '') {
                continue;
            }

            $fields[$reference] = [
                'fieldId' => $field->fieldId ?? null,
                'uid' => $field->uid ?? null,
                'handle' => $field->handle ?? null,
                'label' => $field->label ?? null,
                'type' => get_class($field),
            ];
        }

        return [
            'fields' => $fields,
        ];
    }

    private function _redactSensitiveValues(array $data): array
    {
        $sensitiveKeys = ['apiKey', 'accessToken', 'refreshToken', 'clientSecret', 'password', 'secret', 'token', 'auth'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->_redactSensitiveValues($value);
                continue;
            }

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos((string)$key, $sensitiveKey) !== false && $value !== null && $value !== '') {
                    $data[$key] = $this->_redactSensitiveValue($value);
                    break;
                }
            }
        }

        return $data;
    }

    private function _redactSensitiveValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return '[redacted]';
        }

        if (str_starts_with($value, '$')) {
            return $value;
        }

        return '[redacted]';
    }
}
