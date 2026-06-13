<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\elements\Submission;

use Craft;
use craft\queue\BaseJob as CraftBaseJob;

use Exception;

class TriggerSubmissionDispatch extends CraftBaseJob implements DebuggableJobInterface
{
    use DebuggableJobTrait;

    // Properties
    // =========================================================================

    public ?int $submissionId = null;
    public string $processMode = 'submit';
    public array $stepHandles = [];
    public bool $runAfterNotifications = false;
    public ?string $triggerEvent = null;
    public bool $operatorInitiated = false;
    public ?int $formId = null;
    public ?string $formHandle = null;
    public ?string $formTitle = null;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $this->setProgress($queue, 0.25);

        $submission = Submission::find()
            ->id($this->submissionId)
            ->isIncomplete(null)
            ->status(null)
            ->one();

        if (!$submission) {
            throw new Exception('Unable to find submission: ' . $this->submissionId . '.');
        }

        $this->setProgress($queue, 0.5);

        Craft::$app->language = $submission->getSite()->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($submission->getSite()->language));
        Craft::$app->getSites()->setCurrentSite($submission->getSite());

        Formie::$plugin->getIntegrationDispatch()->runQueuedSteps(
            $submission,
            $this->stepHandles,
            $this->processMode,
            $this->runAfterNotifications,
            [
                'processMode' => $this->processMode,
                'isSubmissionEdit' => $this->processMode === 'editExisting',
                'triggerEvent' => $this->triggerEvent ?? IntegrationTriggerEvents::resolveFromProcessMode($this->processMode),
                'operatorInitiated' => $this->operatorInitiated,
            ],
        );

        $this->setProgress($queue, 1);
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        $form = $this->formHandle ?: ($this->formTitle ?: ($this->formId ?? Craft::t('formie', 'unknown')));

        return Craft::t('formie', 'Running integration dispatch for form “{form}”.', [
            'form' => $form,
        ]);
    }
}
