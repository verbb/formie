<?php
namespace verbb\formie\workflow\tasks\screen;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\helpers\References;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use Craft;

class RunSpamChecksTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SCREEN->value;
    }

    public function getName(): string
    {
        return Task::SCREEN_RUN_SPAM_CHECKS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;

        if ($request->submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return TaskResult::continue();
        }

        $submission = $request->submission;
        $settings = Formie::$plugin->getSettings();

        if ($submission->isSpam) {
            return TaskResult::continue();
        }

        $excludes = $this->_getArrayFromMultiline($settings->spamKeywords);
        $extraExcludes = [];

        foreach ($excludes as $key => $exclude) {
            if (str_contains($exclude, '{')) {
                unset($excludes[$key]);

                $parsedString = $this->_getArrayFromMultiline(References::parseContent($exclude, $submission));
                $extraExcludes[] = $parsedString;
            }
        }

        $excludes = array_merge($excludes, ...$extraExcludes);
        $fieldValues = $this->_buildSpamKeywordHaystack($submission->getValuesAsString());

        foreach ($excludes as $exclude) {
            if (strtolower($exclude) && str_contains(strtolower($fieldValues), strtolower($exclude))) {
                $submission->isSpam = true;
                $submission->spamReason = Craft::t('formie', 'Contains banned keyword: “{c}”', ['c' => $exclude]);

                break;
            }

            if ($submission->ipAddress && $submission->ipAddress === $exclude) {
                $submission->isSpam = true;
                $submission->spamReason = Craft::t('formie', 'Contains banned IP: “{c}”', ['c' => $exclude]);

                break;
            }
        }

        return TaskResult::continue();
    }


    // Private Methods
    // =========================================================================

    private function _getArrayFromMultiline(?string $string): array
    {
        $array = [];

        if ($string) {
            $array = array_map('trim', explode(PHP_EOL, $string));
        }

        return array_filter($array);
    }

    private function _buildSpamKeywordHaystack(array $values): string
    {
        $parts = [];

        foreach ($values as $value) {
            if (is_scalar($value)) {
                $parts[] = (string)$value;
                continue;
            }

            if (is_array($value)) {
                $parts[] = $this->_buildSpamKeywordHaystack($value);
            }
        }

        $haystack = trim(implode(' ', array_filter($parts)));

        // Keyword scans only need enough context to match banned terms; cap work
        // when bots post oversized payloads.
        if (strlen($haystack) > 65536) {
            return substr($haystack, 0, 65536);
        }

        return $haystack;
    }
}
