<?php
namespace verbb\formie\gql\resolvers\mutations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\fields\Payment;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\models\SubmissionErrors;

use Craft;
use craft\base\ElementInterface;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Gql;
use craft\helpers\Json;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Value;

use yii\web\HttpException;

class SubmissionResolver extends ElementMutationResolver
{
    // Properties
    // =========================================================================

    protected array $immutableAttributes = ['id', 'uid'];

    private array $_previousFieldValues = [];


    // Public Methods
    // =========================================================================

    public function saveSubmissionByHandle($source, array $arguments, $context, ResolveInfo $resolveInfo): ?ElementInterface
    {
        $formHandle = $arguments['formHandle'] ?? null;

        if (!is_string($formHandle) || $formHandle === '') {
            throw new Error('Form handle is required.');
        }

        $siteId = isset($arguments['siteId']) ? (int)$arguments['siteId'] : null;
        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle, $siteId);

        if (!$form) {
            throw new Error('No such form exists');
        }

        if (!$this->_canMutateSubmissionForForm($form, $arguments)) {
            throw new Error('Unable to perform the action.');
        }

        $fields = $arguments['fields'] ?? [];
        $captchas = $arguments['captchas'] ?? [];

        if ($fields !== null && !is_array($fields)) {
            throw new Error('Invalid fields argument.');
        }

        if ($captchas !== null && !is_array($captchas)) {
            throw new Error('Invalid captchas argument.');
        }

        $mutationArguments = $arguments;
        unset($mutationArguments['formHandle'], $mutationArguments['fields'], $mutationArguments['captchas']);

        $mutation = SubmissionMutation::createSaveMutation($form);
        $resolver = $mutation['resolve'][0];
        $fieldDefinition = FieldDefinition::create($mutation);

        // Generic maps bypass GraphQL's typed argument validation. Keep each map in its own
        // namespace and coerce its values before handing them to the form-specific resolver.
        $payloads = [
            'fields' => [$fields ?? [], $resolver->getResolutionData(self::CONTENT_FIELD_KEY)],
            'captchas' => [$captchas ?? [], Formie::$plugin->getIntegrations()->getGqlCaptchaArgumentsForForm($form)],
        ];

        foreach ($payloads as $payloadName => [$values, $definitions]) {
            foreach ($values as $handle => $value) {
                if (!array_key_exists($handle, $definitions)) {
                    throw new Error('Unknown ' . $payloadName . ' argument: ' . $handle . '.');
                }

                $coerced = Value::coerceValue($value, $fieldDefinition->getArg($handle)->getType());

                if (!empty($coerced['errors'])) {
                    throw new Error('Invalid ' . $payloadName . ' argument: ' . $coerced['errors'][0]->getMessage());
                }

                $mutationArguments[$handle] = $coerced['value'];
            }
        }

        // Craft recursively normalizes against ResolveInfo, which originally describes the
        // generic maps rather than the flattened form fields. Preserve the caller's context.
        $formResolveInfo = clone $resolveInfo;
        $formResolveInfo->fieldDefinition = $fieldDefinition;

        return $resolver->saveSubmission($source, $mutationArguments, $context, $formResolveInfo);
    }

    public function saveSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo): ?ElementInterface
    {
        $form = $this->getResolutionData('form');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();
        $isPublic = Craft::$app->getGql()->getActiveSchema()->isPublic;

        if ($isPublic) {
            // Anyone can call a public schema, so it can submit the form as a visitor would, but
            // can't reach existing submissions or leave drafts that could never be continued.
            if ($canIdentify || !empty($arguments['isIncomplete'])) {
                throw new Error('Unable to perform the action.');
            }

            // A payment's confirmation steps and safe retries need a session, which this route doesn't have.
            foreach ($form->getFields() as $field) {
                if ($field instanceof Payment && !$field->getIsDisabled()) {
                    throw new Error('Payment forms can\'t be submitted through a public schema. Use `submitFormieClientForm` instead.');
                }
            }

            foreach (['title', 'enabled', 'status', 'statusId', 'operationId', 'requestToken'] as $argument) {
                if (!$form->getFieldByHandle($argument)) {
                    unset($arguments[$argument]);
                }
            }
        }

        $canCreateAll = Gql::canSchema('formieSubmissions.all', 'create');
        $canSaveAll = Gql::canSchema('formieSubmissions.all', 'save');

        $scope = 'formieSubmissions.' . $form->uid;
        $canCreate = Gql::canSchema($scope, 'create');
        $canSave = Gql::canSchema($scope, 'save');

        $submission = null;

        if ($canIdentify) {
            if (!$canSaveAll && !$canSave) {
                throw new Error('Unable to perform the action.');
            }

            $query = $elementService->createElementQuery(Submission::class)->status(null)->isSpam(null)->isIncomplete(null);

            if (!empty($arguments['uid'])) {
                /* @var Submission $submission */
                $submission = $query->uid($arguments['uid'])->one();
            } else {
                /* @var Submission $submission */
                $submission = $query->id($arguments['id'])->one();
            }

            if (!$submission) {
                throw new Error('No such submission exists');
            }
        } else {
            if (!$canCreateAll && !$canCreate) {
                throw new Error('Unable to perform the action.');
            }

            /* @var Submission $submission */
            $submission = $elementService->createElement(['type' => Submission::class, 'formId' => $form->id]);
        }

        if ($submission->formId != $form->id) {
            throw new Error('Impossible to change the form of an existing submission');
        }

        $populate = function() use ($form, $submission, $arguments, $resolveInfo): void {
            // Merge edits while arguments still have request shapes, before Craft calls
            // setFieldValueFromRequest(). Normalized field values must not cross that boundary again.
            $previous = $this->_previousFieldValues;
            $this->_previousFieldValues = [];

            try {
                if ($submission->id) {
                    foreach ($submission->getForm()->getFields() as $field) {
                        if (array_key_exists($field->handle, $arguments)) {
                            $this->_previousFieldValues[$field->handle] = $field->serializeValueForClientInput($submission->getFieldValue($field->handle), $submission);
                        }
                    }
                }

                $this->populateElementWithData($submission, $arguments, $resolveInfo);
            } finally {
                $this->_previousFieldValues = $previous;
            }

            if (!empty($arguments['status']) && !$form->getFieldByHandle('status')) {
                $submission->setStatus($arguments['status']);
            }

            // Populate captcha token payloads from GraphQL args.
            $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form, null, true);

            foreach ($captchas as $captcha) {
                $handle = $captcha->getGqlHandle();

                if (isset($arguments[$handle])) {
                    $submission->setCaptchaData($handle, $arguments[$handle]);
                }
            }
        };

        try {
            if ($isPublic) {
                BrowserRequestProfile::enterPublicSubmission();
            } else {
                BrowserRequestProfile::enterAdministrative();
            }

            $result = Formie::$plugin->getSubmissionRequests()->executeMutation($form, $submission, $arguments, $populate);
        } catch (HttpException $e) {
            // GraphQL hides unexpected exceptions, and these all tell the sender what to change.
            throw new Error($e->getMessage());
        }

        $response = $result->response;
        $success = $response->success;

        // A retried operation returns the first result, so this request's new element was never saved.
        $submission = $response->submission ?? $submission;

        // Spam that's discarded under simulated success has nothing to return.
        if ($success && !$submission->id && ($response->outcome->data['fakeSuccess'] ?? false)) {
            return null;
        }

        if (!$success || $submission->hasErrors() || !$submission->id) {
            $errors = SubmissionErrors::fromSubmission($submission)->toValuePathMap();

            if ($response->outcome->type === SubmissionOutcomeType::STATE_CONFLICT) {
                $errors = ['form' => [$this->_conflictMessage((string)($response->outcome->data['reason'] ?? ''))]];
            }

            // A payment waiting on a follow-up step isn't invalid, so it has no field errors to report.
            if (!$errors) {
                $errors = ['form' => [$response->payment['message'] ?? $form->settings->getErrorMessage()]];
            }

            throw new Error(Json::encode($errors), null, null, [], null, null, [
                'category' => 'validation',
                'errors' => $errors,
            ]);
        }

        // Refresh data from the DB
        $saved = Craft::$app->getElements()->createElementQuery(Submission::class)
            ->id($submission->id)
            ->siteId($submission->siteId)
            ->status(null)
            ->isSpam(null)
            ->isIncomplete(null)
            ->one();

        // Simulated success only works if the response doesn't reveal the verdict.
        if ($isPublic && $saved?->isSpam) {
            $saved->isSpam = false;
            $saved->spamReason = null;
            $saved->spamClass = null;
        }

        return $saved;
    }

    public function deleteSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo): bool
    {
        $submissionId = $arguments['id'];
        $siteId = $arguments['siteId'] ?? null;

        $elementService = Craft::$app->getElements();
        $submission = $elementService->getElementById($submissionId, Submission::class, $siteId);

        if (!$submission) {
            return false;
        }

        $scope = 'formieSubmissions.' . $submission->getForm()->uid;
        $canDeleteAll = Gql::canSchema('formieSubmissions.all', 'delete');
        $canDelete = Gql::canSchema($scope, 'delete');

        if (!$canDeleteAll && !$canDelete) {
            throw new Error('Unable to perform the action.');
        }

        BrowserRequestProfile::enterAdministrative();

        return $elementService->deleteElementById($submissionId, Submission::class, $siteId);
    }


    // Protected Methods
    // =========================================================================

    protected function recursivelyNormalizeArgumentValues(ResolveInfo $resolveInfo, array $mutationArguments): array
    {
        foreach ($mutationArguments as $handle => $value) {
            $definition = $resolveInfo->fieldDefinition->getArg($handle);

            if (!$definition) {
                throw new Error('Unknown submission argument: ' . $handle . '.');
            }

            $normalized = $this->_normalizeArgumentValue($value, $definition->getType());

            if (array_key_exists($handle, $this->_previousFieldValues)) {
                $field = $this->getResolutionData('form')->getFieldByHandle($handle);
                $normalized = $field->mergePartialRequestValue($this->_previousFieldValues[$handle], $this->_partsSent($normalized, $value));
            }

            $mutationArguments[$handle] = $normalized;
        }

        return $mutationArguments;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeArgumentValue(mixed $value, Type $type, bool $applyNormalizer = true): mixed
    {
        // Craft's traversal assumes input objects are arrays, including nullable ones.
        // Follow the input type at each level so clears stay null and sibling handles
        // cannot overwrite each other's type definitions during recursive normalization.
        if ($value === null) {
            return null;
        }

        if ($type instanceof NonNull) {
            return $this->_normalizeArgumentValue($value, $type->getWrappedType(), $applyNormalizer);
        }

        if ($type instanceof ListOfType) {
            // Craft input types such as uploads normalize the whole list. Normalize each
            // item's children first, then invoke that list-level normalizer only once.
            $value = array_map(fn($item) => $this->_normalizeArgumentValue($item, $type->getWrappedType(), false), $value);
            $type = Type::getNamedType($type);
        } elseif ($type instanceof InputObjectType) {
            $definitions = $type->getFields();

            foreach ($value as $handle => $item) {
                if (!isset($definitions[$handle])) {
                    throw new Error('Unknown nested submission argument: ' . $handle . '.');
                }

                $value[$handle] = $this->_normalizeArgumentValue($item, $definitions[$handle]->getType());
            }
        }

        $normalizer = $type->config['normalizeValue'] ?? null;

        return $applyNormalizer && is_callable($normalizer) ? $normalizer($value) : $value;
    }

    /** Input types fill in absent parts or build value objects, which no longer show which parts were sent. */
    private function _partsSent(mixed $normalized, mixed $sent): mixed
    {
        // Lists, such as uploads and rows, are replaced as a whole.
        if (!is_array($sent) || array_is_list($sent)) {
            return $normalized;
        }

        if (is_object($normalized)) {
            return $sent;
        }

        $parts = [];

        foreach ($sent as $key => $value) {
            $parts[$key] = is_array($normalized) && array_key_exists($key, $normalized) ? $this->_partsSent($normalized[$key], $value) : $value;
        }

        return $parts;
    }

    private function _conflictMessage(string $reason): string
    {
        return match ($reason) {
            'operationInputChanged' => 'This `operationId` was already used with different values.',
            'operationRequiresReconciliation' => 'An earlier request with this `operationId` didn\'t finish. Check the submission before retrying with a new `operationId`.',
            default => 'This submission\'s version doesn\'t match. Send its current `stateVersion` as `expectedVersion`.',
        };
    }

    private function _canMutateSubmissionForForm(Form $form, array $arguments): bool
    {
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $scope = 'formieSubmissions.' . $form->uid;

        $canCreateAll = Gql::canSchema('formieSubmissions.all', 'create');
        $canSaveAll = Gql::canSchema('formieSubmissions.all', 'save');
        $canCreate = Gql::canSchema($scope, 'create');
        $canSave = Gql::canSchema($scope, 'save');

        if ($canIdentify) {
            return $canSaveAll || $canSave;
        }

        return $canCreateAll || $canCreate;
    }
}
