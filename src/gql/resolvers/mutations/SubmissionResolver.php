<?php
namespace verbb\formie\gql\resolvers\mutations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;

use Craft;
use craft\base\ElementInterface;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Db;
use craft\helpers\Gql;
use craft\helpers\Json;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

class SubmissionResolver extends ElementMutationResolver
{
    // Properties
    // =========================================================================

    protected array $immutableAttributes = ['id', 'uid'];


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

        if (is_array($fields)) {
            $mutationArguments = array_merge($mutationArguments, $fields);
        }

        if (is_array($captchas)) {
            $mutationArguments = array_merge($mutationArguments, $captchas);
        }

        $resolver = SubmissionMutation::createConfiguredResolver($form);

        return $resolver->saveSubmission($source, $mutationArguments, $context, $resolveInfo);
    }

    public function saveSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo): ?ElementInterface
    {
        $form = $this->getResolutionData('form');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();

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

        $submission = $this->populateElementWithData($submission, $arguments, $resolveInfo);

        if (!empty($arguments['status'])) {
            $submission->setStatus($arguments['status']);
        }

        // Populate captcha token payloads from GraphQL args.
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);

        foreach ($captchas as $captcha) {
            $handle = $captcha->getGqlHandle();

            if (isset($arguments[$handle])) {
                $submission->setCaptchaData($handle, $arguments[$handle]);
            }
        }

        $result = Formie::$plugin->getSubmissionProcessor()->executeMutation($form, $submission, $arguments);
        $response = $result->response;
        $success = $response->success;

        if (!$success || $submission->hasErrors() || !$submission->id) {
            $errors = StringHelper::sanitizeMessageHtmlRecursive($submission->getErrors());

            throw new Error(Json::encode($errors), null, null, [], null, null, [
                'category' => 'validation',
                'errors' => $errors,
            ]);
        }

        // Refresh data from the DB
        return Craft::$app->getElements()->createElementQuery(Submission::class)
            ->id($submission->id)
            ->siteId($submission->siteId)
            ->status(null)
            ->isSpam(null)
            ->isIncomplete(null)
            ->one();
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

        $formUid = Db::uidById(Table::FORMIE_FORMS, $submission->getForm()->id);

        $scope = 'formieSubmissions.' . $formUid;
        $canDeleteAll = Gql::canSchema('formieSubmissions.all', 'delete');
        $canDelete = Gql::canSchema($scope, 'delete');

        if (!$canDeleteAll && !$canDelete) {
            throw new Error('Unable to perform the action.');
        }

        return $elementService->deleteElementById($submissionId, Submission::class, $siteId);
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
