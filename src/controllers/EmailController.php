<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Notification;
use verbb\formie\services\Permissions;

use Craft;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EmailController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        // Email preview/test is a CP-only form builder concern — never allow front-end sessions.
        // Check before parent::beforeAction(): site-classified requests can hit Craft's
        // ServiceUnavailable gate (system offline) before we can reject non-CP access.
        $this->requireCpRequest();

        return parent::beforeAction($action);
    }

    public function actionPreview(): Response
    {
        $this->requirePostRequest();

        $notification = new Notification();
        $submission = new Submission();

        // Populate the submission and notification
        $this->_populateFromPost($notification, $submission);

        $emailRender = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

        if (isset($emailRender['error']) && $emailRender['error']) {
            Formie::error($emailRender['error']);

            // Output the full exception if available
            if (isset($emailRender['exception']) && $emailRender['exception']) {
                Formie::error($emailRender['exception']);
            }

            return $this->asJson([
                'error' => $emailRender['error'],
            ]);
        }

        $email = $emailRender['email'];

        return $this->asJson([
            'from' => $email->getFrom(),
            'to' => $email->getTo(),
            'bcc' => $email->getBcc(),
            'cc' => $email->getCc(),
            'sender' => $email->getSender(),
            'replyTo' => $email->getReplyTo(),
            'subject' => $email->getSubject(),
            'body' => $email->getSymfonyEmail()->getHtmlBody(),
        ]);
    }

    public function actionSendTestEmail(): Response
    {
        $this->requirePostRequest();

        $request = $this->request;

        $notification = new Notification();
        $submission = new Submission();

        // Populate the submission and notification
        $this->_populateFromPost($notification, $submission);

        // Override the 'to' field
        $notification->to = $request->getParam('to');

        $sentResponse = Formie::$plugin->getEmails()->sendEmail($notification, $submission, null, false);
        $success = $sentResponse['success'] ?? false;
        $error = $sentResponse['error'] ?? false;

        return $this->asJson([
            'success' => $success,
            'error' => $error,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _populateFromPost(Notification $notification, Submission $submission): void
    {
        $request = $this->request;
        $formId = $request->getParam('formId');
        $handle = $request->getParam('handle');
        $isStencil = StringHelper::toBoolean((string)$request->getParam('isStencil'));

        // Create a new Notification model from this - it'll be a serialized array from React
        if ($notificationParams = $request->getParam('notification')) {
            if (!is_array($notificationParams)) {
                throw new BadRequestHttpException('Invalid notification payload.');
            }

            $notificationParams = $this->_normalizeNotificationParams($notificationParams);

            // Only assign known Notification attributes (ignores React-only keys)
            $notification->setAttributes(array_intersect_key(
                $notificationParams,
                $notification->getAttributes()
            ), false);
        }

        // Ensure some settings are type-cast
        $notification->enabled = StringHelper::toBoolean((string)$notification->enabled);
        $notification->attachFiles = StringHelper::toBoolean((string)$notification->attachFiles);
        $notification->attachPdf = StringHelper::toBoolean((string)$notification->attachPdf);
        $notification->enableConditions = StringHelper::toBoolean((string)$notification->enableConditions);

        // Prefer an explicit form ID when provided.
        if ($formId) {
            $form = Formie::$plugin->getForms()->getFormById($formId);

            if (!$form) {
                throw new NotFoundHttpException('Form not found.');
            }

            $this->_requireFormNotificationAccess($form);
        } else {
            $form = null;

            // For stencil previews, hydrate a throwaway Form from the stencil.
            if ($isStencil && $handle) {
                // Stencil editing lives under Formie settings
                $this->requirePermission(Permissions::PERM_ACCESS_SETTINGS);

                $stencil = Formie::$plugin->getStencils()->getStencilByHandle($handle);

                if (!$stencil) {
                    throw new NotFoundHttpException('Stencil not found.');
                }

                $form = new Form();
                $stencil->applyStencilToForm($form);
            }

            // Fallback for non-stencil previews/tests that only provide a handle.
            if (!$form && $handle) {
                $form = Formie::$plugin->getForms()->getFormByHandle($handle);

                if (!$form) {
                    throw new NotFoundHttpException('Form not found.');
                }

                $this->_requireFormNotificationAccess($form);
            }
        }

        if (!$form) {
            throw new BadRequestHttpException('Form context is required for email preview.');
        }

        // Always hydrate a fake submission so preview rendering can resolve the
        // same field references and sample values as a real send.
        $submission->setForm($form);

        // Populate all fields with fake content
        Formie::$plugin->getSubmissions()->populateFakeSubmission($submission, $notification);
    }

    private function _requireFormNotificationAccess(Form $form): void
    {
        $user = Craft::$app->getUser()->getIdentity();
        $permissions = Formie::$plugin->getPermissions();

        if (!$permissions->canManageForm($user, $form)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        if (!$permissions->canShowFormBuilderTab($user, $form, 'formie-showNotifications')) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    private function _normalizeNotificationParams(array $notificationParams): array
    {
        // React form state can post rich-text content as node arrays, but Notification::$content is typed string.
        if (array_key_exists('content', $notificationParams) && is_array($notificationParams['content'])) {
            $notificationParams['content'] = RichTextHelper::getHtmlContent($notificationParams['content'], null, false);
        }

        return $notificationParams;
    }
}
