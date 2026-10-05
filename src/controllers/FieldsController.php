<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Signature;
use verbb\formie\fields\Summary;
use verbb\formie\helpers\SignatureAccess;

use Craft;
use craft\helpers\Db;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

use Throwable;

class FieldsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['get-summary-html', 'get-signature-image'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if ($action->id === 'get-summary-html' || $action->id === 'get-signature-image') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/fields', []);
    }

    public function actionGetElementSelectOptions(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->_requireFormAuthoringPermission();

        $elements = [];

        $fieldData = $this->request->getParam('field');

        if (!is_array($fieldData) || !is_string($fieldData['type'] ?? null)) {
            throw new BadRequestHttpException('Invalid element field data.');
        }

        $fieldSettings = $fieldData['settings'] ?? [];

        if (!is_array($fieldSettings)) {
            throw new BadRequestHttpException('Invalid element field settings.');
        }

        $field = $this->_getRegisteredElementField($fieldData['type']);

        try {
            $field->setAttributes(array_intersect_key(
                $fieldSettings,
                array_flip($field->settingsAttributes())
            ), false);

            // Fetch the element query for the field, so we can fetch the content (limited)
            $elements = $field->getPreviewElements();
        } catch (Throwable $e) {
            Formie::error('Unable to fetch element select options: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $this->asJson($elements);
    }

    public function actionGetPredefinedOptions(): Response
    {
        $type = $this->request->getParam('option');

        $options = Formie::$plugin->getPredefinedOptions()->getPredefinedOptionsForType($type);

        return $this->asJson($options);
    }

    public function actionGetSummaryHtml(): string
    {
        $fieldId = (int)$this->request->getParam('fieldId');
        $submissionUid = $this->request->getParam('submissionUid');

        // Ensure things are properly escaped
        $submissionUid = Db::escapeParam($submissionUid);

        // Use UID to prevent easy-guessing of submission to scrape data
        if ($submissionUid && $fieldId) {
            $submission = Submission::find()->uid($submissionUid)->isIncomplete(null)->one();

            if ($submission && $form = $submission->getForm()) {
                // This endpoint is anonymous for Ajax forms, so never render arbitrary field values.
                $field = $form->getFieldById($fieldId);

                if ($field instanceof Summary) {
                    $value = $submission->getFieldValue($field->fieldKey);

                    return $field->getFrontEndInputHtml($form, $value);
                }
            }
        }

        return '';
    }

    public function actionGetSignatureImage(): ?Response
    {
        $fieldId = (int)$this->request->getParam('fieldId');
        $submissionUid = $this->request->getParam('submissionUid');
        $accessToken = $this->request->getParam('accessToken');
        $fieldKey = $this->request->getParam('fieldKey');
        $siteId = (int)$this->request->getParam('siteId');

        // Ensure things are properly escaped
        $submissionUid = Db::escapeParam($submissionUid);

        // Use UID to prevent easy-guessing of submission to scrape data
        if ($submissionUid && $fieldId) {
            $submissionQuery = Submission::find()->uid($submissionUid);

            if (is_string($accessToken) && $accessToken !== '') {
                if ($siteId <= 0 || !is_string($fieldKey) || $fieldKey === '') {
                    return null;
                }

                $submissionQuery->siteId($siteId);
            }

            $submission = $submissionQuery->one();

            if ($submission && $form = $submission->getForm()) {
                if (is_string($accessToken) && $accessToken !== '') {
                    if (!SignatureAccess::validateAccessToken($submission, $fieldId, $fieldKey, $accessToken)) {
                        return null;
                    }

                    $field = $this->_findFieldById($form->getFields(), $fieldId);
                    $valueKey = $fieldKey;
                } else {
                    if (SignatureAccess::requiresAccessToken($submission) || !Formie::$plugin->getSettings()->allowLegacySignatureImageUrls) {
                        return null;
                    }

                    $field = $form->getFieldById($fieldId);
                    $valueKey = $field?->fieldKey;
                }

                if ($field instanceof Signature) {
                    $value = (string)$submission->getFieldValue($valueKey);

                    if ($value === '') {
                        return null;
                    }

                    $base64 = explode('base64,', $value);
                    $image = base64_decode(end($base64));

                    $response = Craft::$app->getResponse();
                    $response->setCacheHeaders();
                    $response->getHeaders()->set('Content-Type', 'image/png');

                    return $this->asRaw($image);
                }
            }
        }

        return null;
    }


    // Private Methods
    // =========================================================================

    private function _getRegisteredElementField(string $type): object
    {
        foreach (Formie::$plugin->getFields()->getRegisteredFields(false) as $field) {
            if (get_class($field) === $type && is_callable([$field, 'getPreviewElements'])) {
                return clone $field;
            }
        }

        throw new BadRequestHttpException('Invalid element field type.');
    }

    private function _requireFormAuthoringPermission(): void
    {
        $user = Craft::$app->getUser();

        if ($user->checkPermission('formie-createForms') || $user->checkPermission('formie-manageForms') || $user->checkPermission('formie-accessSettings')) {
            return;
        }

        $identity = $user->getIdentity();

        if ($identity && $identity->id) {
            $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($identity->id);

            foreach ($permissions as $permission) {
                if (str_starts_with(strtolower($permission), 'formie-manageforms:')) {
                    return;
                }
            }
        }

        throw new ForbiddenHttpException('User is not permitted to perform this action.');
    }

    private function _findFieldById(array $fields, int $fieldId): ?FieldInterface
    {
        foreach ($fields as $field) {
            if ((int)$field->id === $fieldId) {
                return $field;
            }

            if ($field instanceof NestedFieldInterface) {
                $nestedField = $this->_findFieldById($field->getFields(), $fieldId);

                if ($nestedField) {
                    return $nestedField;
                }
            }
        }

        return null;
    }

}
